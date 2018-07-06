<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Auxiliary class to provide support to the Contact Form class.
 *
 * Does this by implementing a small set of static methods.
 */
class CRM_Contact_Form_Edit_Individual {

  /**
   * This function provides the HTML form elements that are specific to the Individual Contact Type.
   *
   * @param CRM_Core_Form $form
   *   Form object.
   * @param int $inlineEditMode
   *   ( 1 for contact summary.
   * top bar form and 2 for display name edit )
   */
  public static function buildQuickForm(&$form, $inlineEditMode = NULL) {
    $form->applyFilter('__ALL__', 'trim');

    if (!$inlineEditMode || $inlineEditMode == 1) {
      $nameFields = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'contact_edit_options', TRUE, NULL,
        FALSE, 'name', TRUE, 'AND v.filter = 2'
      );

      // Use names instead of labels to build form.
      $nameFields = array_keys($nameFields);

      // Fixme: dear god why? these come out in a format that is NOT the name of the fields.
      foreach ($nameFields as &$fix) {
        $fix = str_replace(' ', '_', strtolower($fix));
        if ($fix == 'prefix' || $fix == 'suffix') {
          // God, why god?
          $fix .= '_id';
        }
      }

      foreach ($nameFields as $name) {
        $props = array();
        if ($name == 'prefix_id' || $name == 'suffix_id') {
          //override prefix/suffix label name as Prefix/Suffix respectively and adjust select size
          $props = array('class' => 'eight', 'placeholder' => ' ', 'label' => $name == 'prefix_id' ? ts('Prefix') : ts('Suffix'));
        }
        $form->addField($name, $props);
      }
    }

    if (!$inlineEditMode || $inlineEditMode == 2) {
      // nick_name
      $form->addField('nick_name');

      // job title
      // override the size for UI to look better
      $form->addField('job_title', array('size' => '30'));

      //Current Employer Element
      $props = array(
        'api' => array('params' => array('contact_type' => 'Organization')),
        'create' => TRUE,
      );
      $form->addField('employer_id', $props);
      $form->addField('contact_source', array('class' => 'big'));
    }

    if (!$inlineEditMode) {
      //External Identifier Element
      $form->addField('external_identifier', array('label' => 'External ID'));

      $form->addRule('external_identifier',
        ts('External ID already exists in Database.'),
        'objectExists',
        array('CRM_Contact_DAO_Contact', $form->_contactId, 'external_identifier')
      );
      CRM_Core_ShowHideBlocks::links($form, 'demographics', '', '');
    }
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param int $contactID
   *
   * @return bool
   *   TRUE if no errors, else array of errors.
   */
  public static function formRule($fields, $files, $contactID = NULL) {
    $errors = array();
    $primaryID = CRM_Contact_Form_Contact::formRule($fields, $errors, $contactID, 'Individual');

    // make sure that firstName and lastName or a primary OpenID is set
    if (!$primaryID && (empty($fields['first_name']) || empty($fields['last_name']))) {
      $errors['_qf_default'] = ts('First Name and Last Name OR an email OR an OpenID in the Primary Location should be set.');
    }

    return empty($errors) ? TRUE : $errors;
  }

}
