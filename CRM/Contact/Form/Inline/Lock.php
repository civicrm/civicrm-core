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
class CRM_Contact_Form_Inline_Lock {

  /**
   * This function provides the HTML form elements.
   *
   * @param CRM_Core_Form $form
   *   Form object.
   * @param int $contactID
   */
  public static function buildQuickForm(&$form, $contactID) {
    // We provide a value for oplock_ts to client, but JS uses it carefully
    // -- i.e.  when loading the first inline form, JS copies oplock_ts to a
    // global value, and that global value is used for future form submissions.
    // Any time a form is submitted, the value will be updated.  This
    // handles cases like:
    // - V1:open V1.phone:open V1.email:open V1.email:submit V1.phone:submit
    // - V1:open E1:open E1:submit V1.email:open V1.email:submit
    // - V1:open V1.email:open E1:open E1:submit V1.email:submit V1:lock
    $timestamps = CRM_Contact_BAO_Contact::getTimestamps($contactID);
    $form->addElement('hidden', 'oplock_ts', $timestamps['modified_date'], ['id' => 'oplock_ts']);
    $form->addFormRule(['CRM_Contact_Form_Inline_Lock', 'formRule'], $contactID);
  }

  /**
   * Ensure that oplock_ts hasn't changed in the underlying DB.
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
    if ($fields['oplock_ts'] != $timestamps['modified_date']) {
      // Inline buttons generated via JS
      $open = sprintf("<div class='update_oplock_ts' data:update_oplock_ts='%s'>", $timestamps['modified_date']);
      $close = "</div>";
      $errors['oplock_ts'] = $open . ts('This record was modified by another user!') . $close;
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Return any post-save data.
   *
   * @param int $contactID
   *
   * @return array
   *   extra options to return in JSON
   */
  public static function getResponse($contactID) {
    $timestamps = CRM_Contact_BAO_Contact::getTimestamps($contactID);
    return ['oplock_ts' => $timestamps['modified_date']];
  }

}
