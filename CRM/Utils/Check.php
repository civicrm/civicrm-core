<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
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
   * Display daily system status alerts (admin only).
   */
  public function showPeriodicAlerts() {
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $session = CRM_Core_Session::singleton();
      if ($session->timer('check_' . __CLASS__, self::CHECK_TIMER)) {

        // Best attempt at re-securing folders
        $config = CRM_Core_Config::singleton();
        $config->cleanup(0, FALSE);

        $statusMessages = array();
        $maxSeverity = 0;
        foreach ($this->checkAll() as $message) {
          if (!$message->isVisible()) {
            continue;
          }
          if ($message->getLevel() >= 3) {
            $maxSeverity = max($maxSeverity, $message->getLevel());
            $statusMessage = $message->getMessage();
            $statusMessages[] = $statusTitle = $message->getTitle();
          }
        }

        if ($statusMessages) {
          if (count($statusMessages) > 1) {
            $statusTitle = self::toStatusLabel($maxSeverity);
            $statusMessage = '<ul><li>' . implode('</li><li>', $statusMessages) . '</li></ul>';
          }

          $statusMessage .= '<p><a href="' . CRM_Utils_System::url('civicrm/a/#/status') . '">' . ts('View details and manage alerts') . '</a></p>';

          $statusType = $maxSeverity >= 4 ? 'error' : 'alert';
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
    $aSeverity = $a->getLevel();
    $bSeverity = $b->getLevel();
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
   * @param array|NULL $messages
   *   [CRM_Utils_Check_Message]
   * @param string $threshold
   *
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public function assertValid($messages = NULL, $threshold = \Psr\Log\LogLevel::ERROR) {
    if ($messages === NULL) {
      $messages = $this->checkAll();
    }
    $minLevel = self::severityMap($threshold);
    $errors = array();
    foreach ($messages as $message) {
      if ($message->getLevel() >= $minLevel) {
        $errors[] = $message->toArray();
      }
    }
    if ($errors) {
      throw new Exception("System $threshold: " . print_r($errors, TRUE));
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
    $messages = array();
    foreach (glob(__DIR__ . '/Check/Component/*.php') as $filePath) {
      $className = 'CRM_Utils_Check_Component_' . basename($filePath, '.php');
      /* @var CRM_Utils_Check_Component $check */
      $check = new $className();
      if ($check->isEnabled()) {
        $messages = array_merge($messages, $check->checkAll());
      }
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

    Civi::cache('checks')->set('systemStatusCheckResult', $maxSeverity);

    return ($max) ? $maxSeverity : $messages;
  }

  /**
   * @param int $level
   * @return string
   */
  public static function toStatusLabel($level) {
    switch ($level) {
      case 7:
        return ts('System Status: Emergency');

      case 6:
        return ts('System Status: Alert');

      case 5:
        return ts('System Status: Critical');

      case 4:
        return ts('System Status: Error');

      case 3:
        return ts('System Status: Warning');

      case 2:
        return ts('System Status: Notice');

      default:
        return ts('System Status: Ok');
    }
  }

}
