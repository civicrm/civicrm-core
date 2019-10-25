<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
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
