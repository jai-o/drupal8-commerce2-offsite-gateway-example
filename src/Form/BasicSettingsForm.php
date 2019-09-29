<?php

namespace Drupal\commerce_custom\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder for the commerce_custom basic settings form.
 *
 * @package Drupal\commerce_custom\Form
 */
class BasicSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_custom_basic_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_custom.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_custom.settings');

    // Your form fields here.
    // Examples:

    $form['auth_url'] = [
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => $this->t('Authentication Server'),
      '#default_value' => $config->get('auth_url'),
      '#description' => $this->t('The authentication server URL for production.'),
      '#required' => TRUE,
    ];
    $form['pay_url'] = [
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => $this->t('Payment Web Service'),
      '#default_value' => $config->get('pay_url'),
      '#description' => $this->t('The Web Service URL for production.'),
      '#required' => TRUE,
    ];

    $form['test_auth_url'] = [
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => $this->t('Authentication Server (Testing)'),
      '#default_value' => $config->get('test_auth_url'),
      '#description' => $this->t('The authentication server URL for testing.'),
    ];
    $form['test_pay_url'] = [
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => $this->t(' Web Service (Testing)'),
      '#default_value' => $config->get('test_pay_url'),
      '#description' => $this->t('The Web Service URL for testing.'),
    ];

    $form['live_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use live production URLs'),
      '#default_value' => $config->get('live_mode'),
      '#description' => $this->t('Default: Will use testing URLs.'),
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

    $this->config('commerce_custom.settings')
      ->set('auth_url', $form_state->getValue('auth_url'))
      ->set('pay_url', $form_state->getValue('pay_url'))
      ->set('test_auth_url', $form_state->getValue('test_auth_url'))
      ->set('test_pay_url', $form_state->getValue('test_pay_url'))
      ->set('live_mode', $form_state->getValue('live_mode'))
      ->set('log', $form_state->getValue('log'))
      ->save();
  }

}
