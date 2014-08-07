<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id: $
 *
 */
class CRM_Utils_Check {
  CONST
    // How often to run checks and notify admins about issues.
    CHECK_TIMER = 86400;

  /**
   * We only need one instance of this object, so we use the
   * singleton pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * Provide static instance of CRM_Utils_Check.
   *
   * @return CRM_Utils_Check
   */
  static function &singleton() {
    if (!isset(self::$_singleton)) {
      self::$_singleton = new CRM_Utils_Check();
    }
    return self::$_singleton;
  }

  /**
   * Execute "checkAll"
   *
   * @param array|NULL $messages list of CRM_Utils_Check_Message; or NULL if the default list should be fetched
   */
  public function showPeriodicAlerts($messages = NULL) {
    if (CRM_Core_Permission::check('administer CiviCRM')
      && CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'securityAlert', NULL, TRUE)
    ) {
      $session = CRM_Core_Session::singleton();
      if ($session->timer('check_' . __CLASS__, self::CHECK_TIMER)) {

        // Best attempt at re-securing folders
        $config = CRM_Core_Config::singleton();
        $config->cleanup(0, FALSE);

        if ($messages === NULL) {
          $messages = $this->checkAll();
        }
        foreach ($messages as $message) {
          CRM_Core_Session::setStatus($message->getMessage(), $message->getTitle());
        }
      }
    }
  }

  /**
   * Throw an exception if any of the checks fail
   *
   * @param array|NULL $messages list of CRM_Utils_Check_Message; or NULL if the default list should be fetched
   *
   * @throws Exception
   */
  public function assertValid($messages = NULL) {
    if ($messages === NULL) {
      $messages = $this->checkAll();
    }
    if (!empty($messages)) {
      $messagesAsArray = array();
      foreach ($messages as $message) {
        $messagesAsArray[] = $message->toArray();
      }
      throw new Exception('There are configuration problems with this installation: ' . print_r($messagesAsArray, TRUE));
    }
  }

  /**
   * Run some sanity checks.
   *
   * This could become a hook so that CiviCRM can run both built-in
   * configuration & sanity checks, and modules/extensions can add
   * their own checks.
   *
   * We might even expose the results of these checks on the Wordpress
   * plugin status page or the Drupal admin/reports/status path.
   *
   * @return array of messages
   * @see Drupal's hook_requirements() -
   * https://api.drupal.org/api/drupal/modules%21system%21system.api.php/function/hook_requirements
   */
  public function checkAll() {
    $checks = array();
    $checks[] = new CRM_Utils_Check_Security();
    $checks[] = new CRM_Utils_Check_Env();

    $compInfo = CRM_Core_Component::getEnabledComponents();
    foreach ($compInfo as $compObj) {
      switch ($compObj->info['name']) {
        case 'CiviCase':
          $checks[] = new CRM_Utils_Check_Case(CRM_Case_XMLRepository::singleton(), CRM_Case_PseudoConstant::caseType('name'));
          break;
        default:
      }
    }

    $messages = array();
    foreach ($checks as $check) {
      $messages = array_merge($messages, $check->checkAll());
    }
    return $messages;
  }

}
