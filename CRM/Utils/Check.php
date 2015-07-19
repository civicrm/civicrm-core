<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 * $Id: $
 *
 */
class CRM_Utils_Check {
  const
    // How often to run checks and notify admins about issues.
    CHECK_TIMER = 86400;

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
          if ($filter === TRUE || $message->getSeverity() >= 3) {
            $statusType = $message->getSeverity() >= 4 ? 'error' : $statusType;
            $statusMessage = $message->getMessage();
            $statusMessages[] = $statusTitle = $message->getTitle();
          }
        }

        if (count($statusMessages)) {
          if (count($statusMessages) > 1) {
            $statusTitle = ts('Multiple Alerts');
            $statusMessage = '<ul><li>' . implode('</li><li>', $statusMessages) . '</li></ul>';
          }

          // TODO: add link to status page
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
  public function severitySort($a, $b) {
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
   * @param int|const $severity
   *   the value to look up
   * @param bool $reverse
   *   whether to find the constant from the integer
   * @return bool
   */
  public static function severityMap($severity, $reverse = FALSE) {
    // Lowercase string-based severities
    if (!$reverse) {
      $severity = strtolower($severity);
    }

    // See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
    $levels = array(
      \Psr\Log\LogLevel::EMERGENCY => 7,
      \Psr\Log\LogLevel::ALERT => 6,
      \Psr\Log\LogLevel::CRITICAL => 5,
      \Psr\Log\LogLevel::ERROR => 4,
      \Psr\Log\LogLevel::WARNING => 3,
      \Psr\Log\LogLevel::NOTICE => 2,
      \Psr\Log\LogLevel::INFO => 1,
      \Psr\Log\LogLevel::DEBUG => 0,
    );
    return ($reverse) ? array_search($severity, $levels) : $levels[$severity];
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
   * Run some sanity checks.
   *
   * This could become a hook so that CiviCRM can run both built-in
   * configuration & sanity checks, and modules/extensions can add
   * their own checks.
   *
   * We might even expose the results of these checks on the Wordpress
   * plugin status page or the Drupal admin/reports/status path.
   *
   * @return array
   *   Array of messages
   * @link https://api.drupal.org/api/drupal/modules%21system%21system.api.php/function/hook_requirements
   */
  public function checkAll($showHushed = FALSE) {
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

    if (!$showHushed) {
      foreach ($messages as $key => $message) {
        $hush = self::checkHushSnooze($message);
        if ($hush) {
          unset($messages[$key]);
        }
      }
    }
    uasort($messages, array(__CLASS__, 'severitySort'));

    return $messages;
  }

  /**
   * Evaluate if a system check should be hushed/snoozed.
   *
   * @return bool
   *   TRUE means hush/snooze, FALSE means display.
   */
  public function checkHushSnooze($message) {
    $statusPreferenceParams = array(
      'name' => $message->getName(),
      'domain_id' => CRM_Core_Config::domainID(),
    );
    // Check if there's a StatusPreference matching this name/domain.
    $statusPreference = civicrm_api3('StatusPreference', 'get', $statusPreferenceParams);
    $spid = FALSE;
    if (isset($statusPreference['id'])) {
      $spid = $statusPreference['id'];
    }
    if ($spid) {
      // If so, compare severity to StatusPreference->severity.
      $severity = $message->getSeverity();
      if ($severity <= $statusPreference['values'][$spid]['ignore_severity']) {
        // A hush or a snooze has been set.  Find out which.
        if (isset($statusPreference['values'][$spid]['hush_until'])) {
          // Snooze is set.
          $today = new DateTime();
          $snoozeDate = new DateTime($statusPreference['values'][$spid]['hush_until']);
          if ($today > $snoozeDate) {
            // Snooze is expired.
            return FALSE;
          }
          else {
            // Snooze is active.
            return TRUE;
          }
        }
        else {
          // Hush.
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
