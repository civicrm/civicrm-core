<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Queue_Page_AJAX
 */
class CRM_Queue_Page_AJAX {

  /**
   * Run the next task and return status information.
   *
   * Outputs JSON: array(
   *   is_error => bool,
   *   is_continue => bool,
   *   numberOfItems => int,
   *   exception => htmlString
   * )
   */
  public static function runNext() {
    $errorPolicy = new CRM_Queue_ErrorPolicy();
    $errorPolicy->call(function () {
      global $activeQueueRunner;
      $qrid = CRM_Utils_Request::retrieve('qrid', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'POST');
      $activeQueueRunner = CRM_Queue_Runner::instance($qrid);
      if (!is_object($activeQueueRunner)) {
        throw new Exception('Queue runner must be configured before execution.');
      }
      $result = $activeQueueRunner->runNext(TRUE);
      CRM_Queue_Page_AJAX::_return('runNext', $result);
    });
  }

  /**
   * Run the next task and return status information.
   *
   * Outputs JSON: array(
   *   is_error => bool,
   *   is_continue => bool,
   *   numberOfItems => int,
   *   exception => htmlString
   * )
   */
  public static function skipNext() {
    $errorPolicy = new CRM_Queue_ErrorPolicy();
    $errorPolicy->call(function () {
      global $activeQueueRunner;
      $qrid = CRM_Utils_Request::retrieve('qrid', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'POST');
      $activeQueueRunner = CRM_Queue_Runner::instance($qrid);
      if (!is_object($activeQueueRunner)) {
        throw new Exception('Queue runner must be configured before execution.');
      }
      $result = $activeQueueRunner->skipNext(TRUE);
      CRM_Queue_Page_AJAX::_return('skipNext', $result);
    });
  }

  /**
   * Run the next task and return status information.
   *
   * Outputs JSON: array(
   *   is_error => bool,
   *   is_continue => bool,
   *   numberOfItems => int,
   *   exception => htmlString
   * )
   */
  public static function onEnd() {
    $errorPolicy = new CRM_Queue_ErrorPolicy();
    $errorPolicy->call(function () {
      global $activeQueueRunner;
      $qrid = CRM_Utils_Request::retrieve('qrid', 'String', CRM_Core_DAO::$_nullObject, TRUE, NULL, 'POST');
      $activeQueueRunner = CRM_Queue_Runner::instance($qrid);
      if (!is_object($activeQueueRunner)) {
        throw new Exception('Queue runner must be configured before execution. - onEnd');
      }
      $result = $activeQueueRunner->handleEnd(FALSE);
      CRM_Queue_Page_AJAX::_return('onEnd', $result);
    });
  }

  /**
   * Performing any view-layer filtering on result and send to client.
   *
   * @param string $op
   * @param array $result
   */
  public static function _return($op, $result) {
    if ($result['is_error']) {
      if (is_object($result['exception'])) {
        CRM_Core_Error::debug_var("CRM_Queue_Page_AJAX_{$op}_error", CRM_Core_Error::formatTextException($result['exception']));

        $config = CRM_Core_Config::singleton();
        if ($config->backtrace || CRM_Core_Config::isUpgradeMode()) {
          $result['exception'] = CRM_Core_Error::formatHtmlException($result['exception']);
        }
        else {
          $result['exception'] = $result['exception']->getMessage();
        }
      }
      else {
        CRM_Core_Error::debug_var("CRM_Queue_Page_AJAX_{$op}_error", $result);
      }
    }
    CRM_Utils_JSON::output($result);
  }

}
