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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Check {
  // How often to run checks and notify admins about issues.
  const CHECK_TIMER = 86400;

  /**
   * @var array
   * @link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
   */
  protected static $severityList = [
    \Psr\Log\LogLevel::DEBUG,
    \Psr\Log\LogLevel::INFO,
    \Psr\Log\LogLevel::NOTICE,
    \Psr\Log\LogLevel::WARNING,
    \Psr\Log\LogLevel::ERROR,
    \Psr\Log\LogLevel::CRITICAL,
    \Psr\Log\LogLevel::ALERT,
    \Psr\Log\LogLevel::EMERGENCY,
  ];

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
    if (CRM_Core_Permission::check('administer CiviCRM system')) {
      $session = CRM_Core_Session::singleton();
      if ($session->timer('check_' . __CLASS__, self::CHECK_TIMER)) {

        // Best attempt at re-securing folders
        $config = CRM_Core_Config::singleton();
        $config->cleanup(0, FALSE);

        $statusMessages = [];
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
    $errors = [];
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
   * Run all enabled system checks.
   *
   * This functon is wrapped by the System.check api.
   *
   * Calls hook_civicrm_check() for extensions to add or modify messages.
   * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_check/
   *
   * @param bool $max
   *   Whether to return just the maximum non-hushed severity
   *
   * @return CRM_Utils_Check_Message[]
   */
  public static function checkAll($max = FALSE) {
    $messages = self::checkStatus();

    uasort($messages, [__CLASS__, 'severitySort']);

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
   * @param array $statusNames
   *   Optionally specify the names of specific checks to run, or leave empty to run all
   * @param bool $includeDisabled
   *   Run checks that have been explicitly disabled (default false)
   *
   * @return CRM_Utils_Check_Message[]
   */
  public static function checkStatus($statusNames = [], $includeDisabled = FALSE) {
    $messages = [];
    $checksNeeded = $statusNames;
    foreach (glob(__DIR__ . '/Check/Component/*.php') as $filePath) {
      $className = 'CRM_Utils_Check_Component_' . basename($filePath, '.php');
      /* @var CRM_Utils_Check_Component $component */
      $component = new $className();
      if ($includeDisabled || $component->isEnabled()) {
        $messages = array_merge($messages, $component->checkAll($statusNames, $includeDisabled));
      }
      if ($statusNames) {
        // Early return if we have already run (or skipped) all the requested checks.
        $checksNeeded = array_diff($checksNeeded, $component->getAllChecks());
        if (!$checksNeeded) {
          return $messages;
        }
      }
    }

    CRM_Utils_Hook::check($messages, $statusNames, $includeDisabled);

    return $messages;
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
