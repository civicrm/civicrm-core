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
 * Class CRM_Core_Error_Log
 *
 * A PSR-3 wrapper for CRM_Core_Error.
 */
class CRM_Core_Error_Log extends \Psr\Log\AbstractLogger {

  /**
   * @var array
   */
  public $map;

  /**
   * CRM_Core_Error_Log constructor.
   */
  public function __construct() {
    $this->map = self::getMap();
  }

  /**
   * @return array
   */
  public static function getMap():array {
    return [
      \Psr\Log\LogLevel::DEBUG => PEAR_LOG_DEBUG,
      \Psr\Log\LogLevel::INFO => PEAR_LOG_INFO,
      \Psr\Log\LogLevel::NOTICE => PEAR_LOG_NOTICE,
      \Psr\Log\LogLevel::WARNING => PEAR_LOG_WARNING,
      \Psr\Log\LogLevel::ERROR => PEAR_LOG_ERR,
      \Psr\Log\LogLevel::CRITICAL => PEAR_LOG_CRIT,
      \Psr\Log\LogLevel::ALERT => PEAR_LOG_ALERT,
      \Psr\Log\LogLevel::EMERGENCY => PEAR_LOG_EMERG,
    ];
  }

  /**
   * Logs with an arbitrary level.
   *
   * @param mixed $level
   * @param string $message
   * @param array $context
   */
  public function log($level, $message, array $context = []): void {
    // FIXME: This flattens a $context a bit prematurely. When integrating
    // with external/CMS logs, we should pass through $context.
    if (!empty($context)) {
      if (isset($context['exception'])) {
        $context['exception'] = CRM_Core_Error::formatTextException($context['exception']);
      }
      $fullContext = "\n" . print_r($context, 1);
      $seeLog = sprintf(' (<em>%s</em>)', ts('See log for details.'));
    }
    else {
      $fullContext = $seeLog = '';
    }
    $pearPriority = $this->map[$level];
    CRM_Core_Error::debug_log_message($message . $fullContext, FALSE, '', $pearPriority);
    if ($pearPriority <= PEAR_LOG_ERR && CRM_Core_Config::singleton()->debug && isset($_SESSION['CiviCRM'])) {
      CRM_Core_Session::setStatus($message . $seeLog, ts('Error'), 'error');
    }
  }

}
