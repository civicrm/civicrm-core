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
    if (isset($fields['mailerJobSize']) && $fields['mailerJobSize'] > 0) {
      if ($fields['mailerJobSize'] < 1000) {
        $errors['mailerJobSize'] = ts('The job size must be at least 1000 or set to 0 (unlimited).');
      }
      elseif ($fields['mailerJobSize'] < ($fields['mailerBatchLimit'] ?? 0)) {
        $errors['mailerJobSize'] = ts('A job size smaller than the batch limit will negate the effect of the batch limit.');
      }
    }
    // dev/core#1768 Check the civimail_sync_interval setting.
    if (($fields['civimail_sync_interval'] ?? 0) < 1) {
      $errors['civimail_sync_interval'] = ts('Error - the synchronization interval must be at least 1');
    }
    return empty($errors) ? TRUE : $errors;
  }

}
