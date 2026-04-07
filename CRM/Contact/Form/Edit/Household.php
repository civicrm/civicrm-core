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
class CRM_Contact_Form_Edit_Household {

  /**
   * This function provides the HTML form elements that are specific to the Household Contact Type.
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
      // household_name
      $form->addField('household_name');
    }

    if (!$inlineEditMode || $inlineEditMode == 2) {
      // nick_name
      $form->addField('nick_name');
      $form->addField('is_deceased', ['entity' => 'contact', 'label' => ts('Household is Closed')]);
      $form->addField('deceased_date', ['entity' => 'contact', 'label' => ts('Closed Date')], FALSE, FALSE);
      $form->addField('contact_source', ['label' => ts('Contact Source')]);
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
   * Add rule for household.
   *
   * @param array $fields
   *   Array of form values.
   * @param array $files
   *   Unused.
   * @param int $contactID
   *
   * @return array|bool
   *   $error
   */
  public static function formRule($fields, $files, $contactID = NULL) {
    $errors = [];
    $primaryID = CRM_Contact_Form_Contact::formRule($fields, $errors, $contactID, 'Household');

    // make sure that household name is set
    if (empty($fields['household_name'])) {
      $errors['household_name'] = ts('Household Name should be set.');
    }

    return empty($errors) ? TRUE : $errors;
  }

}
