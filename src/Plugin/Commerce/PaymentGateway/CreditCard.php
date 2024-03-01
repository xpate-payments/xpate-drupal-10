<?php

namespace Drupal\commerce_ginger\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_ginger\Plugin\Commerce\PaymentGateway\BaseOffsitePaymentGateway;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the CreditCard offsite Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "credit-card",
 *   label = @Translation("Credit Card (Off-site redirect)"),
 *   display_label = @Translation("Credit Card"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_ginger\PluginForm\AbstractPayment",
 *   }
 * )
 */
class CreditCard extends BaseOffsitePaymentGateway
{

}
