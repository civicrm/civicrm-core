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
 * The logging-mailer is a utility to wrap an existing PEAR Mail class
 * and apply extra logging functionality.
 *
 * It replaces a set of patches which had been previously applied directly
 * to a few specific PEAR Mail classes.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Mail_LoggingMailer extends Mail {

  /**
   * @var Mail
   */
  protected $delegate;

  /**
   * @param Mail $delegate
   */
  public function __construct($delegate) {
    $this->delegate = $delegate;
  }

  public function send($recipients, $headers, $body) {
    if (defined('CIVICRM_MAIL_LOG')) {
      CRM_Utils_Mail::logger($recipients, $headers, $body);
      if (!defined('CIVICRM_MAIL_LOG_AND_SEND') && !defined('CIVICRM_MAIL_LOG_AND SEND')) {
        return TRUE;
      }
    }

    if (!is_array($headers)) {
      return PEAR::raiseError('$headers must be an array');
    }

    return $this->delegate->send($recipients, $headers, $body);
  }

  public function &__get($name) {
    return $this->delegate->{$name};
  }

  public function __set($name, $value) {
    return $this->delegate->{$name} = $value;
  }

  public function __isset($name) {
    return isset($this->delegate->{$name});
  }

  public function __unset($name) {
    unset($this->delegate->{$name});
  }

}
