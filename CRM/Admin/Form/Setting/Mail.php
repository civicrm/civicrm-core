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

  protected $_settings = [
    'mailerBatchLimit' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'mailThrottleTime' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'mailerJobSize' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'mailerJobsMax' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'verpSeparator' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
    'replyTo' => CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
  ];

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Settings - CiviMail'));
    $this->addFormRule(['CRM_Admin_Form_Setting_Mail', 'formRule']);

    parent::buildQuickForm();
  }

  /**
   * @param $fields
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

    return empty($errors) ? TRUE : $errors;
  }

}
