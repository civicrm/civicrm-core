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
 * Auxiliary class to provide support to the Contact Form class.
 *
 * Does this by implementing a small set of static methods.
 */
class CRM_Contact_Form_Edit_Organization {

  /**
   * This function provides the HTML form elements that are specific to the Organization Contact Type.
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
      // Organization_name
      $form->addField('organization_name', ['title' => ts('Organization Name')]);
    }

    if (!$inlineEditMode || $inlineEditMode == 2) {
      // legal_name
      $form->addField('legal_name', ['title' => ts('Legal Name')]);

      // nick_name
      $form->addField('nick_name', ['title' => ts('Nickname')]);

      // sic_code
      $form->addField('sic_code', ['title' => ts('SIC Code')]);

      $form->addField('is_deceased', ['entity' => 'contact', 'label' => ts('Organization is Closed')]);
      $form->addField('deceased_date', ['entity' => 'contact', 'label' => ts('Closed Date')], FALSE, FALSE);

      $form->addField('contact_source');
    }

    if (!$inlineEditMode) {
      $form->addField('external_identifier', ['label' => ts('External ID')]);
      $form->addRule('external_identifier',
        ts('External ID already exists in Database.'),
        'objectExists',
        ['CRM_Contact_DAO_Contact', $form->_contactId, 'external_identifier']
      );
    }
  }

  /**
   * @param $fields
   * @param $files
   * @param int $contactID
   *
   * @return array|bool
   */
  public static function formRule($fields, $files, $contactID = NULL) {
    $errors = [];
    $primaryID = CRM_Contact_Form_Contact::formRule($fields, $errors, $contactID, 'Organization');

    // make sure that organization name is set
    if (empty($fields['organization_name'])) {
      $errors['organization_name'] = ts('Organization Name should be set.');
    }

    // add code to make sure that the uniqueness criteria is satisfied
    return empty($errors) ? TRUE : $errors;
  }

}
