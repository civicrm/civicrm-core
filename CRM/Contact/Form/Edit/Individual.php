<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
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
   * @param CRM_Core_Form $form form object
   * @param int $inlineEditMode ( 1 for contact summary
   * top bar form and 2 for display name edit )
   *
   * @access public
   * @return void
   */
  public static function buildQuickForm(&$form, $inlineEditMode = NULL) {
    $form->applyFilter('__ALL__', 'trim');

    if ( !$inlineEditMode || $inlineEditMode == 1 ) {
      $nameFields = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'contact_edit_options', TRUE, NULL,
        FALSE, 'name', TRUE, 'AND v.filter = 2'
      );

      //prefix
      $prefix = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'prefix_id');
      if (isset($nameFields['Prefix']) && !empty($prefix)) {
        $form->addSelect('prefix_id', array('class' => 'eight', 'placeholder' => ' ', 'label' => ts('Prefix')));
      }

      $attributes = CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact');

      if (isset($nameFields['Formal Title'])) {
        $form->addElement('text', 'formal_title', ts('Title'), $attributes['formal_title']);
      }

      // first_name
      if (isset($nameFields['First Name'])) {
        $form->addElement('text', 'first_name', ts('First Name'), $attributes['first_name']);
      }

      //middle_name
      if (isset($nameFields['Middle Name'])) {
        $form->addElement('text', 'middle_name', ts('Middle Name'), $attributes['middle_name']);
      }

      // last_name
      if (isset($nameFields['Last Name'])) {
        $form->addElement('text', 'last_name', ts('Last Name'), $attributes['last_name']);
      }

      // suffix
      $suffix = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'suffix_id');
      if (isset($nameFields['Suffix']) && $suffix) {
        $form->addSelect('suffix_id', array('class' => 'eight', 'placeholder' => ' ', 'label' => ts('Suffix')));
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
      $props = array(
        'api' => array('params' => array('contact_type' => 'Organization')),
        'create' => TRUE,
      );
      $form->addEntityRef('employer_id', ts('Current Employer'), $props);
      $attributes['source']['class'] = 'big';
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
      $form->add('text', 'external_identifier', ts('External ID'),
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
   * @param array $fields the input form values
   * @param array $files the uploaded files if any
   * @param null $contactID
   *
   * @internal param array $options additional user data
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $contactID = NULL) {
    $errors = array();
    $primaryID = CRM_Contact_Form_Contact::formRule($fields, $errors, $contactID);

    // make sure that firstName and lastName or a primary OpenID is set
    if (!$primaryID && (empty($fields['first_name']) || empty($fields['last_name']))) {
      $errors['_qf_default'] = ts('First Name and Last Name OR an email OR an OpenID in the Primary Location should be set.');
    }

    //check for duplicate - dedupe rules
    CRM_Contact_Form_Contact::checkDuplicateContacts($fields, $errors, $contactID, 'Individual');

    return empty($errors) ? TRUE : $errors;
  }
}

