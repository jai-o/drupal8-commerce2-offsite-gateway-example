<?php

namespace Drupal\commerce_custom\Controller;

use Drupal;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Endpoints for the routes defined.
 */
class ResultController extends ControllerBase {

  /**
   * onFinish action.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   *   A render array representing the administrative page content.
   */
  public function onFinish(Request $request) {

    // Your code here.

    return ['#markup' => $msg];
  }

  /**
   * Get status code error message.
   *
   * @param array $result
   * @return string
   */
  public function getStatusErrorMessage(array $result) {

    // Your code here.

    return $status_msg;
  }

  /**
   * Get error message.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return string
   *   Message.
   */
  public function getErrorMessage(Request $request) {

      // Your code here.

      return $error_msg;
  }

  /**
   * Get success message.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return string
   *   Message.
   */
  public function getSuccessMessage(Request $request) {

      // Your code here.

      return $success_msg;
  }

}
