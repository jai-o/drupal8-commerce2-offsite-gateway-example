<?php

namespace Drupal\commerce_custom\PluginForm;

use Drupal;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\user\Entity\User;

/**
 * Class RedirectCheckoutForm.
 *
 * This defines a form that Drupal Commerce will redirect to, when the user
 * clicks the Pay and complete purchase button.
 *
 * This class only needs to implement one method: buildConfigurationForm().
 * However, must first:
 *  - Do anything else you need to do, validate or get auth
 *  - Then submit payment request to the server.
 *
 * @package Drupal\commerce_custom\PluginForm
 */
class RedirectCheckoutForm extends PaymentOffsiteForm {

  use Drupal\commerce_custom\RedirectTrait;

  /**
   * Payment gateway configuration settings.
   */
  protected $customConfig;

  /**
   * Module url settings.
   */
  protected $customUrls;

  /**
   * Module log setting.
   */
  private $log;

  /**
   * Creates the checkout form.
   *
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Validate payment and order number.
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    if ($payment->getAmount() === NULL) {
      Drupal::logger('commerce_custom')->error('Payment total is missing or 0.');
      return Drupal::messenger()->addMessage('Payment total is missing or 0');
    }
    $total = $payment->getAmount()->getNumber();

    $order = Order::load($payment->getOrderId());
    if ($order === NULL) {
      Drupal::logger('commerce_custom')->error('Order ID is missing.');
      return Drupal::messenger()->addMessage('Order ID is missing.');
    }

    $settings = Drupal::config('commerce_custom.settings');
    $this->log = $settings->get('log');

    // Validate configuration and gateway settings.
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $payment_gateway_configuration = $payment_gateway_plugin->getConfiguration();
    $payment_gateway_id = $payment_gateway_plugin->getBaseId();

    // Validate Id settings.
    if ($payment_gateway_configuration['example_user_id'] === NULL||
      $payment_gateway_configuration['example_user_cred'] === NULL) {

      Drupal::logger('commerce_custom')->error('The payment gateway is missing 
        one or more settings.');
      return Drupal::messenger()->addMessage('The payment gateway is not configured properly.');
    }

    $this->customConfig['example_user_id'] = $payment_gateway_configuration['example_user_id'];
    $this->customConfig['example_user_cred'] = $payment_gateway_configuration['example_user_cred'];

    // Valdiate URL settings.
    if ($settings->get('live_mode') === 1) {
      if ($settings->get('auth_url') === NULL ||
        $settings->get('pay_url') === NULL) {
        Drupal::logger('commerce_custom')->error('The payment gateway is 
          missing one or more URL settings. Please update your configuration 
          /admin/config/system/commerce_custom');
        return Drupal::messenger()->addMessage('The payment gateway is not configured properly.');
      }
      $this->customUrls['auth'] = $settings->get('auth_url');
      $this->customUrls['pay'] = $settings->get('pay_url');
    }
    else {
      if ($settings->get('test_auth_url') === NULL ||
        $settings->get('test_pay_url') === NULL) {
        Drupal::logger('commerce_custom')->error('The payment gateway is 
          missing Test URLs when in test mode. Please update your configuration 
          /admin/config/system/commerce_custom.');
        return Drupal::messenger()->addMessage('The testing URLs in the payment gateway is not configured properly.');
      }
      $this->customUrls['auth'] = $settings->get('test_auth_url');
      $this->customUrls['pay'] = $settings->get('test_pay_url');
    }

    $checkout_url = Url::fromRoute('commerce_checkout.form', [
      'commerce_order' => $order->id(),
      'step' => 'review',
    ], ['absolute' => TRUE])->toString();

    // Your code here. Authorize, validate, etc.

    // Get payment request URL.
    $payment_request_url = $this->buildPaymentRequestUrl(
      $session_ticket,
      $total,
      $order->id(),
      $payment_gateway_id);

    return $this->buildRedirectForm(
      $form,
      $form_state,
      $payment_request_url,
      [],
      PaymentOffsiteForm::REDIRECT_POST
    );
  }

  /**
   * Build payment request url for Custom payment gateway, if you need it.
   *
   */
  private function buildPaymentRequestUrl($session_ticket, $total, $order_id, $payment_gateway_id) {

    // "Commerce 2" default route for onNotify() is /payment/notify/$payment_gateway_id.
    $notify_url = Url::fromRoute('commerce_payment.notify', [
      'commerce_payment_gateway' => 'custom',
    ], ['absolute' => TRUE])->toString();
    $continue_url = Url::fromRoute('commerce_custom.finish', [],
      ['absolute' => TRUE])->toString();

    $pay_url_data = [
      // your data to send to offsite system.
    ];

   // build your payment url, if needed.

    return $pay_url;
  }

}
