<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * Class CRM_Core_Error_Log
 *
 * A PSR-3 wrapper for CRM_Core_Error.
 */
class CRM_Core_Error_Log extends \Psr\Log\AbstractLogger {

  /**
   * CRM_Core_Error_Log constructor.
   */
  public function __construct() {
    $this->map = array(
      \Psr\Log\LogLevel::DEBUG => PEAR_LOG_DEBUG,
      \Psr\Log\LogLevel::INFO => PEAR_LOG_INFO,
      \Psr\Log\LogLevel::NOTICE => PEAR_LOG_NOTICE,
      \Psr\Log\LogLevel::WARNING => PEAR_LOG_WARNING,
      \Psr\Log\LogLevel::ERROR => PEAR_LOG_ERR,
      \Psr\Log\LogLevel::CRITICAL => PEAR_LOG_CRIT,
      \Psr\Log\LogLevel::ALERT => PEAR_LOG_ALERT,
      \Psr\Log\LogLevel::EMERGENCY => PEAR_LOG_EMERG,
    );
  }

  /**
   * Logs with an arbitrary level.
   *
   * @param mixed $level
   * @param string $message
   * @param array $context
   */
  public function log($level, $message, array $context = array()) {
    // FIXME: This flattens a $context a bit prematurely. When integrating
    // with external/CMS logs, we should pass through $context.
    if (!empty($context)) {
      if (isset($context['exception'])) {
        $context['exception'] = CRM_Core_Error::formatTextException($context['exception']);
      }
      $message .= "\n" . print_r($context, 1);

      if (CRM_Utils_System::isDevelopment() && CRM_Utils_Array::value('civi.tag', $context) === 'deprecated') {
        trigger_error($message, E_USER_DEPRECATED);
      }
      // Interpolate context into message.
      if (FALSE !== strpos($message, '{')) {
        $replacements = [];
        foreach ($context as $key => $val) {
          if (is_null($val) || is_scalar($val) || (is_object($val) && method_exists($val, "__toString"))) {
            $replacements['{' . $key . '}'] = $val;
          }
          elseif (is_object($val)) {
            $replacements['{' . $key . '}'] = '[object ' . get_class($val) . ']';
          }
          else {
            $replacements['{' . $key . '}'] = '[' . gettype($val) . ']';
          }
        }
        $message = strtr($message, $replacements);
        $message .= "\n" . print_r($context, 1);
      }
    }
    CRM_Core_Error::debug_log_message($message, FALSE, '', $this->map[$level]);
  }

  /**
   * Adds a log record at the INFO level.
   *
   * This method allows for compatibility with common interfaces.
   *
   * @param string $message
   * @param array $context
   * @return null|void
   */
  public function debug($message, array $context = array()) {
    return $this->log(\Psr\Log\LogLevel::DEBUG, $message, $context);
  }

  /**
   * Adds a log record at the DEBUG level.
   *
   * This method allows for compatibility with common interfaces.
   *
   * @param string $message
   * @param array $context
   * @return null|void
   */
  public function info($message, array $context = array()) {
    return $this->log(\Psr\Log\LogLevel::INFO, $message, $context);
  }

  /**
   * Adds a log record at the NOTICE level.
   *
   * This method allows for compatibility with common interfaces.
   *
   * @param string $message
   * @param array $context
   * @return null|void
   */
  public function notice($message, array $context = array()) {
    return $this->log(\Psr\Log\LogLevel::NOTICE, $message, $context);
  }

  /**
   * Adds a log record at the WARNING level.
   *
   * This method allows for compatibility with common interfaces.
   *
   * @param string $message
   * @param array $context
   * @return null|void
   */
  public function warning($message, array $context = array()) {
    return $this->log(\Psr\Log\LogLevel::WARNING, $message, $context);
  }

  /**
   * Adds a log record at the ERROR level.
   *
   * This method allows for compatibility with common interfaces.
   *
   * @param string $message
   * @param array $context
   * @return null|void
   */
  public function error($message, array $context = array()) {
    return $this->log(\Psr\Log\LogLevel::ERROR, $message, $context);
  }

  /**
   * Adds a log record at the CRITICAL level.
   *
   * This method allows for compatibility with common interfaces.
   *
   * @param string $message
   * @param array $context
   * @return null|void
   */
  public function critical($message, array $context = array()) {
    return $this->log(\Psr\Log\LogLevel::CRITICAL, $message, $context);
  }

  /**
   * Adds a log record at the ALERT level.
   *
   * This method allows for compatibility with common interfaces.
   *
   * @param string $message
   * @param array $context
   * @return null|void
   */
  public function alert($message, array $context = array()) {
    return $this->log(\Psr\Log\LogLevel::ALERT, $message, $context);
  }

  /**
   * Adds a log record at the EMERGENCY level.
   *
   * This method allows for compatibility with common interfaces.
   *
   * @param string $message
   * @param array $context
   * @return null|void
   */
  public function emergency($message, array $context = array()) {
    return $this->log(\Psr\Log\LogLevel::EMERGENCY, $message, $context);
  }

}
