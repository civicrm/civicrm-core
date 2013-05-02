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
class CRM_Contact_Form_Edit_Individual {

  /**
   * This function provides the HTML form elements that are specific 
   * to the Individual Contact Type
   *
   * @param object $form form object
   * @param int $inlineEditMode ( 1 for contact summary 
   * top bar form and 2 for display name edit )
   *
   * @access public
   * @return void 
   */
  public static function buildQuickForm(&$form, $inlineEditMode = NULL) {
    $form->applyFilter('__ALL__', 'trim');

    if ( !$inlineEditMode || $inlineEditMode == 1 ) {
      //prefix
      $prefix = CRM_Core_PseudoConstant::individualPrefix();
      if (!empty($prefix)) {
        $form->addElement('select', 'prefix_id', ts('Prefix'), array('' => '') + $prefix);
      }

      $attributes = CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact');

      // first_name
      $form->addElement('text', 'first_name', ts('First Name'), $attributes['first_name']);

      //middle_name
      $form->addElement('text', 'middle_name', ts('Middle Name'), $attributes['middle_name']);

      // last_name
      $form->addElement('text', 'last_name', ts('Last Name'), $attributes['last_name']);

      // suffix
      $suffix = CRM_Core_PseudoConstant::individualSuffix();
      if ($suffix) {
        $form->addElement('select', 'suffix_id', ts('Suffix'), array('' => '') + $suffix);
      }
    }

    if ( !$inlineEditMode || $inlineEditMode == 2 ) {
      // nick_name
      $form->addElement('text', 'nick_name', ts('Nickname'),
        CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'nick_name')
      );

      // job title
      // override the size for UI to look better
      $attributes['job_title']['size'] = 30;
      $form->addElement('text', 'job_title', ts('Job Title'), $attributes['job_title'], 'size="30"');

      //Current Employer Element
      $employerDataURL = CRM_Utils_System::url('civicrm/ajax/rest', 'className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&context=contact&org=1&employee_id=' . $form->_contactId, FALSE, NULL, FALSE);
      $form->assign('employerDataURL', $employerDataURL);

      $form->addElement('text', 'current_employer', ts('Current Employer'), '');
      $form->addElement('hidden', 'current_employer_id', '', array('id' => 'current_employer_id'));
      $form->addElement('text', 'contact_source', ts('Source'), CRM_Utils_Array::value('source', $attributes));
    }

    if ( !$inlineEditMode ) {
      $checkSimilar = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'contact_ajax_check_similar',
        NULL,
        TRUE
      );

      if ( $checkSimilar == null ) {
        $checkSimilar = 0;
      }
      $form->assign('checkSimilar', $checkSimilar);

      //External Identifier Element
      $form->add('text', 'external_identifier', ts('External Id'),
        CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'external_identifier'), FALSE
      );

      $form->addRule('external_identifier',
        ts('External ID already exists in Database.'),
        'objectExists',
        array('CRM_Contact_DAO_Contact', $form->_contactId, 'external_identifier')
      );
      $config = CRM_Core_Config::singleton();
      CRM_Core_ShowHideBlocks::links($form, 'demographics', '', '');
    }
  }

  /**
   * global form rule
   *
   * @param array $fields  the input form values
   * @param array $files   the uploaded files if any
   * @param array $options additional user data
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $contactID = NULL) {
    $errors = array();
    $primaryID = CRM_Contact_Form_Contact::formRule($fields, $errors, $contactID);

    // make sure that firstName and lastName or a primary OpenID is set
    if (!$primaryID && (!CRM_Utils_Array::value('first_name', $fields) ||
        !CRM_Utils_Array::value('last_name', $fields)
      )) {
      $errors['_qf_default'] = ts('First Name and Last Name OR an email OR an OpenID in the Primary Location should be set.');
    }

    //check for duplicate - dedupe rules
    CRM_Contact_Form_Contact::checkDuplicateContacts($fields, $errors, $contactID, 'Individual');

    return empty($errors) ? TRUE : $errors;
  }
}

