<?php

namespace Drupal\commerce_ginger\PluginForm;

require_once __DIR__.'/../../vendor/autoload.php';

use Drupal;
use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\commerce_ginger\Builder\OrderBuilder;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\user\Entity\User;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_ginger\Redefiner\BuilderRedefiner;
use Drupal\commerce_ginger\Helper\Helper;
use Drupal\commerce_ginger\PSP\PSPconfig;
use Drupal\commerce_ginger\Controller\OrderController;
use GingerPluginSdk\Properties\Birthdate;

/**
 * Class AbstractPayment.
 *
 * This defines a payment form that Drupal Commerce will redirect to, when the
 * user clicks the Pay and complete purchase button.
 *
 * @package Drupal\commerce_ginger\PluginForm
 */
class AbstractPayment extends PaymentOffsiteForm
{

  use Drupal\commerce_ginger\RedirectTrait;

  /**
   * @var string
   */
  public $name;

  /**
   * @var \GingerPluginSdk\Client
   */
  public $client;

  /**
   * @var Helper
   */
  public $helper;

  public $builderRedefiner;

  public function __construct()
  {
    $this->helper = new Helper();
    $this->builderRedefiner = new OrderBuilder();
    $this->client = $this->builderRedefiner->getClient();
  }

  /**
   * Creates the checkout form.
   *
   * {@inheritdoc}
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state
  ) {
    $this->paymentMethod = $this->entity->getPaymentGateway()->getPluginId();
    $form = parent::buildConfigurationForm($form, $form_state);

    $paymentForm = $this->entity->getPaymentGateway()->getPlugin()->prepareForm(
      $form
    );
    if ($paymentForm) {
      return $paymentForm;
    }
    $order = $this->startTransaction($form, $form_state);
    $form = $this->helper->setBanktransferForm($form, $order->toArray());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(
    array &$form,
    FormStateInterface $form_state
  ) {
    if ($this->entity->getPaymentGateway()->getPluginId() == 'bank-transfer') {
      $order = $this->entity->getOrder();
      $params['subject'] = 'Order payment information!';
      $params['body'] = $form['banktransfer_info'];
      $options = [
        'langcode' => 'en',
      ];
      $message['from'] = \Drupal::config('system.site')->get('mail');
      \Drupal::service(
        'plugin.manager.mail'
      )->mail(
        'commerce_ginger',
        'order_mail',
        $order->getEmail(),
        $this->builderRedefiner->getLangCode($this->entity),
        $params
      );
    }
    $this->startTransaction($form, $form_state);
  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  public function startTransaction(array &$form, FormStateInterface $form_state)
  {
    $this->paymentMethod = $this->entity->getPaymentGateway()->getPluginId();
    $payment = $this->entity;

    $values = $form_state->getValue($form['#parents']) ?? [];
    $issuerId = OrderController::getIssuerId($values, $form_state);
    $verifiedTerms = OrderController::getTermsState($values);
    $birthdate = OrderController::getBirthdate($values) ? new Birthdate(
      OrderController::getBirthdate($values)
    ) : null;
    $gender = OrderController::getCustomerGender($values, $form_state);

    $customer = $this->builderRedefiner->getCustomerData(
      $payment,
      $birthdate,
      $gender
    );

    $order = $this->builderRedefiner->createOrder(
      $payment,
      $this->client,
      $customer,
      $this->paymentMethod,
      $issuerId,
      $verifiedTerms
    );

    $payment->setRemoteId($order->getId()->get());

    $payment->getOrder()->setOrderNumber($payment->getOrderId());
    $payment->save();

    if ($this->paymentMethod != 'bank-transfer') {
      throw new NeedsRedirectException($order->getPaymentUrl());
    }

    return $order;
  }

}
