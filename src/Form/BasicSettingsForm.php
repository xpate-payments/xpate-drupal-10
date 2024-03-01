<?php

namespace Drupal\commerce_ginger\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder for the commerce_ginger basic settings form.
 *
 * @package Drupal\commerce_ginger\Form
 */
class BasicSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_ginger_basic_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_ginger.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_ginger.settings');

    // Your form fields here.
    $form['api_key'] = [
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => $this->t('Merchant Api-Key'),
      '#default_value' => $config->get('api_key'),
      '#description' => $this->t('Api-Key required for making transactions.'),
      '#required' => TRUE,
    ];

    $form['log'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log the messages for debugging'),
      '#default_value' => $config->get('log'),
      '#description' => $this->t('Recommended even in production. Default: 0'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('commerce_ginger.settings')
      ->set('api_key',  $form_state->getValue('api_key'))
      ->set('log', $form_state->getValue('log'))
      ->save();
  }

}
