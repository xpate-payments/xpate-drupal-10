<?php

namespace Drupal\commerce_ginger\Builder;

use Drupal\commerce_ginger\PSP\PSPconfig;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use GingerPluginSdk\Collections\OrderLines;
use GingerPluginSdk\Collections\Transactions;
use GingerPluginSdk\Entities\Extra;
use GingerPluginSdk\Entities\Line;
use GingerPluginSdk\Entities\Order;
use GingerPluginSdk\Entities\PaymentMethodDetails;
use GingerPluginSdk\Entities\Transaction;
use GingerPluginSdk\Properties\Amount;
use GingerPluginSdk\Properties\Currency;
use GingerPluginSdk\Properties\Percentage;
use GingerPluginSdk\Properties\VatPercentage;
use GingerPluginSdk\Properties\RawCost;
use GingerPluginSdk\Entities\Customer;
use GingerPluginSdk\Client;
use Drupal\commerce_ginger\Helper\OrderHelper;
use GingerPluginSdk\Entities\Client as EntitiesClient;

/**
 * Class OrderBuilder.
 *
 * This class contain methods for creating an order
 *
 * @package Drupal\commerce_ginger\Builder
 */
class OrderBuilder extends CustomerBuilder
{

  /**
   * @param  string  $issuer_id
   *
   * @return \GingerPluginSdk\Entities\PaymentMethodDetails
   */
  public function preparePaymentMethodDetails(
    string $issuer_id,
  ): PaymentMethodDetails {
    return new PaymentMethodDetails(
      array_filter([
        'issuer_id' => $issuer_id,
        //        'verified_terms_of_service' => $verifiedTerms,
        //        'customer' => 'customer'
      ])
    );
  }

  /**
   * @param  string  $payment_method
   * @param  \GingerPluginSdk\Entities\PaymentMethodDetails|null  $payment_method_details
   *
   * @return \GingerPluginSdk\Collections\Transactions
   */
  public function prepareTransaction(
    string $payment_method,
    PaymentMethodDetails $payment_method_details = null
  ): Transactions {
    return new Transactions(
      new Transaction(
        paymentMethod: $payment_method,
        paymentMethodDetails: $payment_method_details
      )
    );
  }

  /**
   * @param  \Drupal\commerce_payment\Entity\PaymentInterface  $payment
   *
   * @return \GingerPluginSdk\Collections\OrderLines
   */
  public function getOrderLines(PaymentInterface $payment): OrderLines
  {
    $payment_amount = $payment->getAmount();
    $raw_amount = $payment_amount->getNumber();

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $payment->getOrder();
    $order_items_info = $this->getOrderItemsInfo($order);

    $order_lines = new OrderLines();
    foreach ($order_items_info as $order_item) {
      $order_lines->addLine(
        new Line(
          type: 'physical',
          merchantOrderLineId: $order_item['id'],
          name: $order_item['name'],
          quantity: $order_item['quantity'],
          amount: new Amount(floatval($order_item['price']) * 100),
          vatPercentage: new VatPercentage(new Percentage(0)),
          currency: new Currency(
            'EUR'
          )
        )
      );
    }
    if (!$order->hasField('shipments') || $order->get('shipments')->isEmpty()) {
      return $order_lines;
    }
    $shipments = $order->get('shipments');
    foreach ($order->get('shipments')->referencedEntities() as $shipment) {
      if ($shipment->get('shipping_profile')->isEmpty()) {
        continue;
      }

      $order_lines->addLine(
        new Line(
          type: 'shipping_fee',
          merchantOrderLineId: 'shipping',
          name: $shipment->getShippingMethod()->getName(),
          quantity: 1,
          amount: new Amount(
            floatval($shipment->getAmount()->getNumber()) * 100
          ),
          vatPercentage: new VatPercentage(new Percentage(0)),
          currency: new Currency(
            $shipment->getAmount()->getCurrencyCode()
          )
        )
      );
    }
    return $order_lines;
  }

  /**
   * Get info about ordered products.
   *          currency: new Currency(
   *
   * @param  \Drupal\commerce_order\Entity\OrderInterface  $order
   *
   * @return array
   */
  protected function getOrderItemsInfo(OrderInterface $order): array
  {
    $order_items_info = [];
    $order_items = $order->getItems();
    foreach ($order_items as $order_item) {
      if (empty($order_item)) {
        continue;
      }
      $order_items_info[] = [
        'id' => $order_item->id(),
        'quantity' => number_format($order_item->getQuantity()),
        'name' => $order_item->getTitle(),
        'price' => $order_item->getUnitPrice()->getNumber(),
      ];
    }

    return $order_items_info;
  }

  /**
   * @return \GingerPluginSdk\Entities\Client
   */
  public function getEntitiesClient(): EntitiesClient
  {
    return new EntitiesClient(
      $this->getUserAgent(),
      PSPconfig::getPlatformName(),
      null, // For now no ways to gat platform version were found
      PSPconfig::getPluginName(),
      PSPconfig::getPluginVersion()
    );
  }

  /**
   * Collect data for extra_lines
   *
   * @return array
   */
  public function getExtraLines(): array
  {
    return [
      'user_agent' => $this->getUserAgent(),
      'platform_name' => PSPconfig::getPlatformName(),
      'plugin_name' => PSPconfig::getPluginName(),
      'plugin_version' => PSPconfig::getPluginVersion(),
    ];
  }

  /**
   * @param  string  $order_id
   *
   * @return string
   */
  public function getDescription(string $order_id): string
  {
    return sprintf("%s: %s", t('Order number'), $order_id);
  }

  /**
   * @param  \Drupal\commerce_payment\Entity\PaymentInterface  $payment
   * @param  \GingerPluginSdk\Client  $client
   * @param  \GingerPluginSdk\Entities\Customer  $customer
   * @param  string  $payment_method
   * @param  string|null  $issuer_id
   *
   * @return \GingerPluginSdk\Entities\Order
   * @throws \GingerPluginSdk\Exceptions\APIException
   */
  public function createOrder(
    PaymentInterface $payment,
    Client $client,
    Customer $customer,
    string $payment_method,
    string $issuer_id = null,
  ): Order {
    $payment_amount = $payment->getAmount();
    $raw_amount = $payment_amount->getNumber();
    $transactions = $this->prepareTransaction(
      $payment_method,
      $this->preparePaymentMethodDetails($issuer_id)
    );

    $order = new Order(
      currency: new Currency($payment_amount->getCurrencyCode()),
      amount: new Amount(new RawCost(floatval($raw_amount))),
      transactions: $transactions,
      customer: $customer,
      orderLines: $this->getOrderLines($payment),
      extra: new Extra(
        $this->getExtraLines()
      ),
      client: $this->getEntitiesClient(),
      webhook_url: OrderHelper::getWebhookUrl($payment),
      return_url: OrderHelper::getReturnUrl($payment),
      merchantOrderId: $payment->getOrderId(),
      description: $this->getDescription($payment->getOrderId()),
    );

    return $client->sendOrder($order);
  }

}
