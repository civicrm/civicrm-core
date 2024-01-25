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

/**
 * This class generates form components for CiviMail.
 */
class CRM_Admin_Form_Setting_Mail extends CRM_Admin_Form_Setting {

  /**
   * Subset of settings on the page as defined using the legacy method.
   *
   * @var array
   *
   * @deprecated - do not add new settings here - the page to display
   * settings on should be defined in the setting metadata.
   */
  protected $_settings = [
    // @todo remove these, define any not yet defined in the setting metadata.
    'mailerBatchLimit' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'mailThrottleTime' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'mailerJobSize' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'mailerJobsMax' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'verpSeparator' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    // dev/core#1768 Make this interval configurable.
    'civimail_sync_interval' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'replyTo' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'civimail_unsubscribe_methods' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
  ];

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->setTitle(ts('Settings - CiviMail'));
    $this->addFormRule(['CRM_Admin_Form_Setting_Mail', 'formRule']);
    parent::buildQuickForm();
  }

  /**
   * @param array $fields
   *
   * @return array|bool
   */
  public static function formRule($fields) {
    $errors = [];
    if (CRM_Utils_Array::value('mailerJobSize', $fields) > 0) {
      if (CRM_Utils_Array::value('mailerJobSize', $fields) < 1000) {
        $errors['mailerJobSize'] = ts('The job size must be at least 1000 or set to 0 (unlimited).');
      }
      elseif (CRM_Utils_Array::value('mailerJobSize', $fields) <
        CRM_Utils_Array::value('mailerBatchLimit', $fields)
      ) {
        $errors['mailerJobSize'] = ts('A job size smaller than the batch limit will negate the effect of the batch limit.');
      }
    }
    // dev/core#1768 Check the civimail_sync_interval setting.
    if (CRM_Utils_Array::value('civimail_sync_interval', $fields) < 1) {
      $errors['civimail_sync_interval'] = ts('Error - the synchronization interval must be at least 1');
    }
    return empty($errors) ? TRUE : $errors;
  }

}
