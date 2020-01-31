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
 * Auxiliary class to provide support for locking (and ignoring locks on) contact records.
 */
class CRM_Contact_Form_Edit_Lock {

  /**
   * Build the form object.
   *
   * @param CRM_Core_Form $form
   *   Form object.
   */
  public static function buildQuickForm(&$form) {
    $form->addField('modified_date', ['type' => 'hidden', 'id' => 'modified_date', 'label' => '']);
  }

  /**
   * Ensure that modified_date has not changed in the underlying DB.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param int $contactID
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $contactID = NULL) {
    $errors = [];

    $timestamps = CRM_Contact_BAO_Contact::getTimestamps($contactID);
    if ($fields['modified_date'] != $timestamps['modified_date']) {
      // Inline buttons generated via JS
      $open = sprintf("<span id='update_modified_date' data:latest_modified_date='%s'>", $timestamps['modified_date']);
      $close = "</span>";
      $errors['modified_date'] = $open . ts('This record was modified by another user!') . $close;
    }

    return empty($errors) ? TRUE : $errors;
  }

}
