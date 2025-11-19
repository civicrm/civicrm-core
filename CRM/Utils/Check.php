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
   * @return array[]
   */
  public static function getSeverityOptions() {
    return [
      ['id' => 0, 'name' => \Psr\Log\LogLevel::DEBUG, 'label' => ts('Debug')],
      ['id' => 1, 'name' => \Psr\Log\LogLevel::INFO, 'label' => ts('Info')],
      ['id' => 2, 'name' => \Psr\Log\LogLevel::NOTICE, 'label' => ts('Notice')],
      ['id' => 3, 'name' => \Psr\Log\LogLevel::WARNING, 'label' => ts('Warning')],
      ['id' => 4, 'name' => \Psr\Log\LogLevel::ERROR, 'label' => ts('Error')],
      ['id' => 5, 'name' => \Psr\Log\LogLevel::CRITICAL, 'label' => ts('Critical')],
      ['id' => 6, 'name' => \Psr\Log\LogLevel::ALERT, 'label' => ts('Alert')],
      ['id' => 7, 'name' => \Psr\Log\LogLevel::EMERGENCY, 'label' => ts('Emergency')],
    ];
  }

  /**
   * Display daily system status alerts (admin only).
   */
  public function showPeriodicAlerts() {
    if (CRM_Core_Permission::check('administer CiviCRM system')) {
      $userId = CRM_Core_Session::getLoggedInContactID();
      $statusCheckedForUser = Civi::cache('checks')->get('status_checked_for_user_' . $userId);
      if (!$statusCheckedForUser) {
        $statusMessages = Civi::cache('checks')->get('status_messages');
        if (!is_array($statusMessages)) {
          // Best attempt at re-securing folders
          $config = CRM_Core_Config::singleton();
          $config->cleanup(0, FALSE);
          $statusMessages = [];
          foreach (self::checkAll() as $message) {
            if (!$message->isVisible()) {
              continue;
            }
            if ($message->getLevel() >= 3) {
              $statusMessage = $message->getMessage();
              $statusMessages[] = $statusTitle = $message->getTitle();
            }
          }
          Civi::cache('checks')->set('status_messages', $statusMessages, self::CHECK_TIMER);
        }

        if ($statusMessages) {
          $maxSeverity = self::getMaxSeverity(TRUE);
          $statusTitle = self::toStatusLabel($maxSeverity);
          $statusMessage = '<ul><li>' . implode('</li><li>', $statusMessages) . '</li></ul>';

          $statusMessage .= '<p><a href="' . CRM_Utils_System::url('civicrm/a/#/status') . '">' . ts('View details and manage alerts') . '</a></p>';

          $statusType = $maxSeverity >= 4 ? 'error' : 'alert';
          CRM_Core_Session::setStatus($statusMessage, $statusTitle, $statusType);
        }
        Civi::cache('checks')->set('status_checked_for_user_' . $userId, TRUE, self::CHECK_TIMER);
      }
    }
  }

  /**
   * Returns the max severity of the status checks.
   * The result is cahced as this status is shown in the footer of the CiviCRM page. So no need to refresh this.
   *
   * @param bool $force
   *   Force refresh the max severity calculation.
   * @return int
   */
  public static function getMaxSeverity(bool $force = FALSE): int {
    $maxSeverity = Civi::cache('checks')->get('systemStatusCheckResult');
    if ($maxSeverity === NULL || $force) {
      $maxSeverity = 1;
      foreach (self::checkAll() as $message) {
        if ($message->isVisible()) {
          $maxSeverity = max($maxSeverity, $message->getLevel());
        }
      }
      Civi::cache('checks')->set('systemStatusCheckResult', $maxSeverity, self::CHECK_TIMER);
    }
    return $maxSeverity ?? 0;
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
   * @param array|null $messages
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
   * @return CRM_Utils_Check_Message[]
   */
  public static function checkAll() {
    if (!isset(\Civi::$statics['CRM_Utils_Check']['messages'])) {
      \Civi::$statics['CRM_Utils_Check']['messages'] = self::checkStatus();
    }
    return \Civi::$statics['CRM_Utils_Check']['messages'];
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
      /** @var CRM_Utils_Check_Component $component */
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
    if ($level > 1) {
      $options = array_column(self::getSeverityOptions(), 'label', 'id');
      return ts('System Status: %1', [1 => $options[$level]]);
    }
    return ts('System Status: Ok');
  }

}
