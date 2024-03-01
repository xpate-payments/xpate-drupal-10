<?php

namespace Drupal\commerce_ginger\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_ginger\Plugin\Commerce\PaymentGateway\BaseOffsitePaymentGateway;
use Drupal\commerce_payment\Entity\PaymentInterface;

/**
 * Provides the ApplePay offsite Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "apple-pay",
 *   label = @Translation("Apple Pay (Off-site redirect)"),
 *   display_label = @Translation("Apple Pay"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_ginger\PluginForm\AbstractPayment",
 *   }
 * )
 */
class ApplePay extends BaseOffsitePaymentGateway
{

}
