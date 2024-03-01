<?php

namespace Drupal\commerce_ginger\Controller;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\Core\Form\FormStateInterface;

class OrderController
{

  /**
   * @param $transaction_id
   * @param $entity_type_manager
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface
   */
  public static function getOrderPaymentByTransactionId(
    $transaction_id,
    $entity_type_manager
  ): PaymentInterface {
    /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
    $payment_storage = $entity_type_manager->getStorage('commerce_payment');
    $payment = $payment_storage->loadByRemoteId($transaction_id);

    return $payment;
  }

  /**
   * @param  array  $values
   * @param  \Drupal\Core\Form\FormStateInterface  $form_state
   *
   * @return mixed
   */
  public static function getIssuerId(
    array $values,
    FormStateInterface $form_state
  ): mixed {
    return array_key_exists(
      'issuers',
      $values
    ) ? $values['issuers'][$form_state->getUserInput(
    )["payment_process"]["offsite_payment"]['issuers']] : false;
  }

  /**
   * @param  array  $values
   * @param  \Drupal\Core\Form\FormStateInterface  $form_state
   *
   * @return mixed
   */
  public static function getCustomerGender(
    array $values,
    FormStateInterface $form_state
  ): mixed {
    return array_key_exists(
      'gender',
      $values
    ) ? $values['gender'][$form_state->getUserInput(
    )["payment_process"]["offsite_payment"]['gender']] : false;
  }

  /**
   * @param  array  $values
   *
   * @return mixed
   */
  public static function getBirthdate(array $values): mixed
  {
    return array_key_exists(
      'birthdate',
      $values
    ) ? $values['birthdate'] : false;
  }

  public static function getTermsState(array $values): bool
  {
    return array_key_exists(
      'verified_terms_of_service',
      $values
    ) && $values['verified_terms_of_service'] == 1 ? true : false;
  }

}
