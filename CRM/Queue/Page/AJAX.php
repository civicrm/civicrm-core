<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
