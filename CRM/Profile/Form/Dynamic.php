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
 * This class generates form components for custom data
 *
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 *
 */
class CRM_Profile_Form_Dynamic extends CRM_Profile_Form {

  /**
   * Pre processing work done here.
   *
   */
  public function preProcess(): void {
    if ($this->get('register')) {
      $this->_mode = CRM_Profile_Form::MODE_REGISTER;
    }
    else {
      $this->_mode = CRM_Profile_Form::MODE_EDIT;
    }

    if ($this->get('skipPermission')) {
      $this->_skipPermission = TRUE;
    }

    // also allow dupes to be updated for edit in my account (CRM-2232)
    $this->_isUpdateDupe = TRUE;

    parent::preProcess();
  }

  /**
   * Build the form object.
   *
   */
  public function buildQuickForm(): void {
    $this->addButtons([
      [
        'type' => 'upload',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ],
    ]);

    // also add a hidden element for to trick drupal
    $this->addElement('hidden', "edit[civicrm_dummy_field]", "CiviCRM Dummy Field for Drupal");
    parent::buildQuickForm();

    $this->addFormRule(['CRM_Profile_Form_Dynamic', 'formRule'], $this);
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param CRM_Core_Form $form
   *
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $form) {
    $errors = [];

    // if no values, return
    if (empty($fields) || empty($fields['edit'])) {
      return TRUE;
    }

    return CRM_Profile_Form::formRule($fields, $files, $form);
  }

}
