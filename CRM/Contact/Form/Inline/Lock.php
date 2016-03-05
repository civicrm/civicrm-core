<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
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
    $form->addElement('hidden', 'oplock_ts', $timestamps['modified_date'], array('id' => 'oplock_ts'));
    $form->addFormRule(array('CRM_Contact_Form_Inline_Lock', 'formRule'), $contactID);
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
    $errors = array();

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
    return array('oplock_ts' => $timestamps['modified_date']);
  }

}
