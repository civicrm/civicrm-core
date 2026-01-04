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
class CRM_Admin_Form_Setting_Mail extends CRM_Admin_Form_Generic {

  public function preProcess() {
    parent::preProcess();
    $this->sections = [
      'mailer' => [
        'title' => ts('Outbound Mailing'),
        'icon' => 'fa-server',
        'weight' => 10,
      ],
      'reply' => [
        'title' => ts('Reply and Unsubscribe'),
        'icon' => 'fa-comment-dots',
        'weight' => 20,
      ],
    ];
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
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
    if (!empty($fields['mailerJobSize'])) {
      // This wouldn't work well as a validate_callback because both settings could be changed in one request and the validate might act prematurely
      if ($fields['mailerJobSize'] < ($fields['mailerBatchLimit'] ?? 0)) {
        $errors['mailerJobSize'] = ts('A job size smaller than the batch limit will negate the effect of the batch limit.');
      }
    }
    return empty($errors) ? TRUE : $errors;
  }

}
