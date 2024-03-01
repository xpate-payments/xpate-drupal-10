<?php

namespace Drupal\commerce_ginger\Helper;

use Drupal\Core\Url;
use GingerPluginSdk\Entities\Order;

class OrderHelper
{

  public static function isOrderRefunded(Order $order)
  {
    $orderArray = $order->toArray();
    return in_array('has-refunds', ($orderArray['flags'] ?? []) );
  }

  public static function isOrderCapturable(Order $order)
  {
    return $order->getCurrentTransaction()->isCapturable();
  }

  public static function getWebhookUrl($payment)
  {
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    return $payment_gateway_plugin->getNotifyUrl()->toString();
  }


  public static function getReturnUrl($payment)
  {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $payment->getOrder();
    return Url::fromRoute('commerce_payment.checkout.return', [
      'commerce_order' => $order->id(),
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
  }

  public static function getCancelUrl($payment) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $payment->getOrder();

    return Url::fromRoute('commerce_payment.checkout.cancel', [
      'commerce_order' => $order->id(),
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
  }
}
