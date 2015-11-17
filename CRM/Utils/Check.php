<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */
class CRM_Utils_Check {
  // How often to run checks and notify admins about issues.
  const CHECK_TIMER = 86400;

  /**
   * @var array
   * @link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
   */
  protected static $severityList = array(
    \Psr\Log\LogLevel::DEBUG,
    \Psr\Log\LogLevel::INFO,
    \Psr\Log\LogLevel::NOTICE,
    \Psr\Log\LogLevel::WARNING,
    \Psr\Log\LogLevel::ERROR,
    \Psr\Log\LogLevel::CRITICAL,
    \Psr\Log\LogLevel::ALERT,
    \Psr\Log\LogLevel::EMERGENCY,
  );

  /**
   * We only need one instance of this object, so we use the
   * singleton pattern and cache the instance in this variable
   *
   * @var object
   */
  static private $_singleton = NULL;

  /**
   * Provide static instance of CRM_Utils_Check.
   *
   * @return CRM_Utils_Check
   */
  public static function &singleton() {
    if (!isset(self::$_singleton)) {
      self::$_singleton = new CRM_Utils_Check();
    }
    return self::$_singleton;
  }

  /**
   * @return array
   */
  public static function getSeverityList() {
    return self::$severityList;
  }

  /**
   * Execute "checkAll".
   *
   * @param array|NULL $messages
   *   List of CRM_Utils_Check_Message; or NULL if the default list should be fetched.
   * @param array|string|callable $filter
   *   Restrict messages using a callback filter.
   *   By default, only show warnings and errors.
   *   Set TRUE to show all messages.
   */
  public function showPeriodicAlerts($messages = NULL, $filter = array(__CLASS__, 'severityMap')) {
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
        $statusMessages = array();
        $statusType = 'alert';
        foreach ($messages as $message) {
          if (!$message->isVisible()) {
            continue;
          }
          if ($filter === TRUE || $message->getSeverity() >= 3) {
            $statusType = $message->getSeverity() >= 4 ? 'error' : $statusType;
            $statusMessage = $message->getMessage();
            $statusMessages[] = $statusTitle = $message->getTitle();
          }
        }

        if (count($statusMessages)) {
          if (count($statusMessages) > 1) {
            $statusTitle = ts('Multiple Alerts');
            $statusMessage = ts('Please check your <a href="%1">status page</a> for a full list and further details.', array(1 => CRM_Utils_System::url('civicrm/a/#/status'))) . '<ul><li>' . implode('</li><li>', $statusMessages) . '</li></ul>';
          }

          // @todo add link to status page
          CRM_Core_Session::setStatus($statusMessage, $statusTitle, $statusType);
        }
      }
    }
  }

  /**
   * Sort messages based upon severity
   *
   * @param CRM_Utils_Check_Message $a
   * @param CRM_Utils_Check_Message $b
   * @return int
   */
  public static function severitySort($a, $b) {
    $aSeverity = $a->getSeverity();
    $bSeverity = $b->getSeverity();
    if ($aSeverity == $bSeverity) {
      return strcmp($a->getName(), $b->getName());
    }
    // The Message constructor guarantees that these will always be integers.
    return ($aSeverity < $bSeverity);
  }

  /**
   * Get the integer value (useful for thresholds) of the severity.
   *
   * @param int|string $severity
   *   the value to look up
   * @param bool $reverse
   *   whether to find the constant from the integer
   * @return string|int
   * @throws \CRM_Core_Exception
   */
  public static function severityMap($severity, $reverse = FALSE) {
    if ($reverse) {
      if (isset(self::$severityList[$severity])) {
        return self::$severityList[$severity];
      }
    }
    else {
      // Lowercase string-based severities
      $severity = strtolower($severity);
      if (in_array($severity, self::$severityList)) {
        return array_search($severity, self::$severityList);
      }
    }
    throw new CRM_Core_Exception('Invalid PSR Severity Level');
  }

  /**
   * Throw an exception if any of the checks fail.
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
   * Run all system checks.
   *
   * This functon is wrapped by the System.check api.
   *
   * Calls hook_civicrm_check() for extensions to add or modify messages.
   * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_check
   *
   * @param bool $max
   *   Whether to return just the maximum non-hushed severity
   *
   * @return array
   *   Array of CRM_Utils_Check_Message objects
   */
  public static function checkAll($max = FALSE) {
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

    CRM_Utils_Hook::check($messages);

    uasort($messages, array(__CLASS__, 'severitySort'));

    $maxSeverity = 1;
    foreach ($messages as $message) {
      if (!$message->isVisible()) {
        continue;
      }
      $maxSeverity = max(1, $message->getLevel());
      break;
    }

    Civi::cache()->set('systemCheckSeverity', $maxSeverity);
    $timestamp = time();
    Civi::cache()->set('systemCheckDate', $timestamp);

    return ($max) ? $maxSeverity : $messages;
  }

}
