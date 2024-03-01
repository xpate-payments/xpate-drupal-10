<?php

namespace Drupal\commerce_ginger\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_ginger\Plugin\Commerce\PaymentGateway\BaseOffsitePaymentGateway;
use Drupal\commerce_payment\Entity\PaymentInterface;

/**
 * Provides the Google Pay offsite Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "google-pay",
 *   label = @Translation("Google Pay (Off-site redirect)"),
 *   display_label = @Translation("Google Pay"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_ginger\PluginForm\AbstractPayment",
 *   }
 * )
 */
class GooglePay extends BaseOffsitePaymentGateway
{

}
