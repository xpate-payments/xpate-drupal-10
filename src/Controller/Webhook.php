<?php

namespace Drupal\commerce_ginger\Controller;

use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\commerce_ginger\Builder\OrderBuilder;
use Drupal\commerce_ginger\Redefiner\BuilderRedefiner;
use Drupal\commerce_ginger\Helper\OrderHelper;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\commerce_ginger\PSP\PSPconfig;
use GingerPluginSdk\Client;

class Webhook
{

  use StringTranslationTrait;

  private $builderRedefiner;

  /**
   * @var Client
   */
  private Client $client;

  public function __construct()
  {
    $this->builderRedefiner = new OrderBuilder();
    $this->client = $this->builderRedefiner->getClient();
  }

  /**
   * @param  array  $webhookData
   * @param $entityTypeManager
   *
   * @return void
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  public function processWebhook(array $webhookData, $entityTypeManager): void
  {
    $orderId = filter_var($webhookData['order_id'], FILTER_SANITIZE_STRING);
    $payment = OrderController::getOrderPaymentByTransactionId(
      $orderId,
      $entityTypeManager
    );
    $this->processOrderStatus($orderId, $payment);
  }

  /**
   * @param $orderId
   * @param $payment
   *
   * @return void
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  public function processOrderStatus($orderId, $payment): void
  {
    $apiOrder = $this->client->getOrder($orderId);
    $status = $apiOrder->getStatus()->get();

    if ($status) {
      $payment->setState($status);
      $payment->save();
    }
    switch ($status) {
      case 'error':
        \Drupal::logger(PSPconfig::getLoggerChanel())->error(
          'Order #'.$payment->getOrderId().' Message:'.(current(
            $apiOrder->toArray()['transactions']
          )['customer_message'] ?? '').' Reason:'.(current(
            $apiOrder->toArray()['transactions']
          )['reason']) ?? ''
        );
        \Drupal::messenger()->addWarning(
          current(
            $apiOrder->toArray()['transactions']
          )['customer_message'] ?? $this->t(
          'Something went wrong, please try again later'
        )
        );
        throw new NeedsRedirectException(OrderHelper::getCancelUrl($payment));
      case 'expired':
      case 'cancelled':
        \Drupal::messenger()->addWarning(
          $this->t(sprintf('Your order is %s, please try again later', $status))
        );
        throw new NeedsRedirectException(OrderHelper::getCancelUrl($payment));
        break;
      case 'completed':
        \Drupal::messenger()->addMessage($this->t('Thanks for order!'));

        return;
      case 'processing':
        \Drupal::messenger()->addMessage(
          $this->t('Your order is processing. Thanks!')
        );

        return;
      case 'new':
        if ($apiOrder->getCurrentTransaction()->getPaymentMethod()->get(
          ) == 'bank-transfer') {
          break;
        }

      default:
        throw new NeedsRedirectException(OrderHelper::getCancelUrl($payment));
    }
  }

}
