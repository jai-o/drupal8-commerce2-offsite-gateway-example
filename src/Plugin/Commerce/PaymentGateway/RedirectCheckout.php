<?php

namespace Drupal\commerce_custom\Plugin\Commerce\PaymentGateway;

use Drupal;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Price;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitGatewayBase;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the offsite Custom payment gateway.
 *
 * This class needs these methods: defaultConfiguration(),
 * buildConfigurationForm(), and submitConfigurationForm() methods.
 *
 * @CommercGateway(
 *   id = "custom",
 *   label = @Translation("Commerce Custom (Off-site redirect)"),
 *   display_label =  @Translation("Custom"),
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_custom\PluginForm\RedirectCheckoutForm",
 *   },
 *   requires_billing_information = FALSE,
 * )
 *
 * @package Drupal\commerce_custom\Plugin\Commerce\PaymentGateway
 */
class RedirectCheckout extends OffsitGatewayBase {

  use Drupal\commerce_custom\RedirectTrait;

  /**
   * Module log setting.
   */
  private $log;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'example_user_id'     => '',
      'example_user_cred'   => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['example_user_id'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Merchant Authentication ID'),
      '#size'          => 60,
      '#default_value' => $this->configuration['example_user_id'],
      '#description'   => $this->t('The merchant user name'),
      '#required'      => TRUE,
    ];
    $form['example_user_cred'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Merchant Authentication Credentials'),
      '#size'          => 60,
      '#default_value' => $this->configuration['example_user_cred'],
      '#description'   => $this->t('The merchant password'),
      '#required'      => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['example_user_id'] = $values['example_user_id'];
      $this->configuration['example_user_cred'] = $values['example_user_cred'];
    }
  }

  /**
   * Check if successful and reply.
   *
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    parent::onNotify($request);

    if ($request->getMethod() === 'GET') {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }


    // Your code here. Validate, etc. Save Order, Save Payment.


    $order = Order::load((int) $offsite_system_order_id);
    if ($order === NULL) {
      $err_msg = new FormattableMarkup(
        'No such order #: @order_id',
        [
          '@order_id' => $offsite_system_order_id,
        ]
      );

      Drupal::logger('commerce_custom')->warning($err_msg);
      return $this->notifySaveFailed();
    }

    if ($this->log === 1) {
      $err_msg = new FormattableMarkup(
        'MESSAGE:  @msg',
        [
          '@msg' => $notified_msg,
        ]
      );
      Drupal::logger('commerce_custom')->debug($err_msg);
    }

    try {
      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
      $payment_storage = Drupal::entityTypeManager()->getStorage('commerce_payment');
      $payment = $payment_storage->create([
        'payment_method' => 'custom',
        'payment_gateway' => 'custom',
        'order_id' => $offsite_system_order_id,
      ]);
      // @TODO use getCurrencyCode()
      $payment->setAmount(new Price($notified_amount, 'CAD'));
      $payment->setState('completed');
      $payment->setRemoteId($offsite_system_example_id);
      $payment->setRemoteState($offsite_system_status_code);
    }
    catch (\Exception $e) {
      Drupal::logger('commerce_payment')->error("Unable to create payment 
        storage! Order # $offsite_system_order_id / " . $e->getMessage());

      return $this->notifySaveFailed();
    }

    try {
      $payment->save();
    }
    catch (\Exception $e) {
      $err_msg = new FormattableMarkup(
        'Payment Save Failed! Order #',
        [
          '@order_id' => $offsite_system_order_id,
        ]
      );
      Drupal::logger('commerce_payment')->error($err_msg . $e->getMessage());

      return $this->notifySaveFailed();
    }

    $order->set('state', 'completed');
    $order->setData('example_id', $offsite_system_example_id);
    $order->setData('merchant_id', $offsite_system_order_id);
    $order->setData('status_code', $offsite_system_status_code);
    $order->setData('amount', $notified_amount);
    $order->setData('message', $notified_msg);

    try {
      $order->save();
    }
    catch (\Exception $e) {
      $err_msg = new FormattableMarkup(
        'Order Save Failed! Order #',
        [
          '@order_id' => $offsite_system_order_id,
        ]
      );
      Drupal::logger('commerce_payment')->error($err_msg . $e->getMessage());

      return $this->notifySaveFailed();
    }

    // Response.
    return $this->notifySaveSuccess($offsite_system_example_id, $offsite_system_order_id);
  }

  /**
   * Reply to Offsite.
   *
   * @param string $example_id
   * @param string $merch_id
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function notifySaveSuccess($example_id, $merch_id) {

    $response = new Response();

    $response->setContent('Tell offsite saved ok!');

    return $response;
  }

  /**
   * Reply to Offiste.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function notifySaveFailed() {
    $response = new Response();

    $response->setContent('Tell offsite system save failed.');

    return $response;
  }
}
