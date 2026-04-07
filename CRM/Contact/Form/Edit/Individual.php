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
  public static function buildQuickForm($form, $inlineEditMode = NULL): void {
    $form->addOptionalQuickFormElement('formal_title');
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
        if ($fix === 'prefix' || $fix === 'suffix') {
          // God, why god?
          $fix .= '_id';
        }
      }

      foreach ($nameFields as $name) {
        $props = [];
        if ($name == 'prefix_id' || $name == 'suffix_id') {
          //override prefix/suffix label name as Prefix/Suffix respectively and adjust select size
          $props = ['class' => 'eight', 'placeholder' => ' ', 'label' => $name == 'prefix_id' ? ts('Prefix') : ts('Suffix')];
        }
        $form->addField($name, $props);
      }
    }

    if (!$inlineEditMode || $inlineEditMode == 2) {
      // nick_name
      $form->addField('nick_name');

      // job title
      // override the size for UI to look better
      $form->addField('job_title', ['size' => '30']);

      //Current Employer Element
      $form->addField('employer_id', ['create' => TRUE]);
      $form->addField('contact_source', ['class' => 'big']);
    }

    if (!$inlineEditMode) {
      //External Identifier Element
      $form->addField('external_identifier', ['label' => ts('External ID')]);

      $form->addRule('external_identifier',
        ts('External ID already exists in Database.'),
        'objectExists',
        ['CRM_Contact_DAO_Contact', $form->_contactId, 'external_identifier']
      );
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
   * @return bool|array
   *   TRUE if no errors, else array of errors.
   */
  public static function formRule($fields, $files, $contactID = NULL) {
    $errors = [];
    $primaryID = CRM_Contact_Form_Contact::formRule($fields, $errors, $contactID, 'Individual');

    // make sure that firstName and lastName or a primary OpenID is set
    if (!$primaryID && (empty($fields['first_name']) && empty($fields['last_name']))) {
      $errors['_qf_default'] = ts('Please enter a First Name, Last Name or Email (Primary).');
    }

    return empty($errors) ? TRUE : $errors;
  }

}
