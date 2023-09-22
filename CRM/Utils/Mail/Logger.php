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
 * An attachment to PEAR Mail which logs emails to files based on
 * the CIVICRM_MAIL_LOG configuration.
 *
 * (Produced by refactoring; specifically, extracting log-related functions
 * from CRM_Utils_Mail.)
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Mail_Logger {

  /**
   * @param CRM_Utils_Mail_FilteredPearMailer $mailer
   * @param mixed $recipients
   * @param array $headers
   * @param string $body
   * @return mixed
   *   Normally returns null/void. But if the filter process is to be
   *   short-circuited, then returns a concrete value.
   */
  public static function filter($mailer, &$recipients, &$headers, &$body) {
    if (defined('CIVICRM_MAIL_LOG')) {
      static::log($recipients, $headers, $body);
      if (!defined('CIVICRM_MAIL_LOG_AND_SEND')) {
        return TRUE;
      }
    }
  }

  /**
   * @param string|string[] $to
   * @param string[] $headers
   * @param string $message
   */
  private static function log($to, $headers, $message) {
    if (is_array($to)) {
      $toString = implode(', ', $to);
      $fileName = $to[0];
    }
    else {
      $toString = $fileName = $to;
    }
    $content = 'To: ' . $toString . "\n";
    foreach ($headers as $key => $val) {
      $content .= "$key: $val\n";
    }
    $content .= "\n" . $message . "\n";

    if (is_numeric(CIVICRM_MAIL_LOG)) {
      $config = CRM_Core_Config::singleton();
      // create the directory if not there
      $dirName = $config->configAndLogDir . 'mail' . DIRECTORY_SEPARATOR;
      CRM_Utils_File::createDir($dirName);
      $fileName = md5(uniqid(CRM_Utils_String::munge($fileName))) . '.txt';
      file_put_contents($dirName . $fileName,
        $content
      );
    }
    else {
      file_put_contents(CIVICRM_MAIL_LOG, $content, FILE_APPEND);
    }
  }

}
