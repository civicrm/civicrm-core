<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Auxilary class to provide support to the Contact Form class. Does this by implementing
 * a small set of static methods
 *
 */
class CRM_Contact_Form_Edit_Household {

 /**
   * This function provides the HTML form elements that are specific
   * to the Household Contact Type
   *
   * @param object $form form object
   * @param int $inlineEditMode ( 1 for contact summary
   * top bar form and 2 for display name edit )
   *
   * @access public
   * @return void
   */
  public static function buildQuickForm(&$form, $inlineEditMode = NULL) {
    $attributes = CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact');

    $form->applyFilter('__ALL__', 'trim');

    if ( !$inlineEditMode || $inlineEditMode == 1 ) {
      // household_name
      $form->add('text', 'household_name', ts('Household Name'), $attributes['household_name']);
    }

    if ( !$inlineEditMode || $inlineEditMode == 2 ) {
      // nick_name
      $form->addElement('text', 'nick_name', ts('Nickname'), $attributes['nick_name']);
      $form->addElement('text', 'contact_source', ts('Source'), CRM_Utils_Array::value('source', $attributes));
    }

    if ( !$inlineEditMode ) {
      $form->add('text', 'external_identifier', ts('External Id'), $attributes['external_identifier'], FALSE);
      $form->addRule('external_identifier',
        ts('External ID already exists in Database.'),
        'objectExists',
        array('CRM_Contact_DAO_Contact', $form->_contactId, 'external_identifier')
      );
    }
  }

  /**
   * add rule for household
   *
   * @params array $fields array of form values
   *
   * @return $error
   * @static
   * @public
   */
  static function formRule($fields, $files, $contactID = NULL) {
    $errors = array();
    $primaryID = CRM_Contact_Form_Contact::formRule($fields, $errors, $contactID);

    // make sure that household name is set
    if (!CRM_Utils_Array::value('household_name', $fields)) {
      $errors['household_name'] = 'Household Name should be set.';
    }

    //check for duplicate - dedupe rules
    CRM_Contact_Form_Contact::checkDuplicateContacts($fields, $errors, $contactID, 'Household');

    return empty($errors) ? TRUE : $errors;
  }
}

