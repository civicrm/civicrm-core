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
 * Form helper class for contact name section.
 */
class CRM_Contact_Form_Inline_ContactName extends CRM_Contact_Form_Inline {

  /**
   * Build the form object elements.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    // Build contact type specific fields
    $class = 'CRM_Contact_Form_Edit_' . $this->_contactType;
    $class::buildQuickForm($this, 1);
    $this->addFormRule(['CRM_Contact_Form_Inline_ContactName', 'formRule'], $this);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   * @param array $errors
   *   List of errors to be posted back to the form.
   * @param CRM_Contact_Form_Inline_ContactName $form
   *
   * @return array
   */
  public static function formRule($fields, $errors, $form) {
    if (empty($fields['first_name']) && empty($fields['last_name'])
      && empty($fields['organization_name'])
      && empty($fields['household_name'])) {
      $emails = civicrm_api3('Email', 'getcount', ['contact_id' => $form->_contactId]);
      if (!$emails) {
        $errorField = $form->_contactType == 'Individual' ? 'last' : strtolower($form->_contactType);
        $errors[$errorField . '_name'] = ts('Contact with no email must have a name.');
      }
    }
    return $errors;
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    $params = $this->exportValues();

    // Process / save contact info
    $params['contact_type'] = $this->_contactType;
    $params['contact_id'] = $this->_contactId;

    if (!empty($this->_contactSubType)) {
      $params['contact_sub_type'] = $this->_contactSubType;
    }

    CRM_Contact_BAO_Contact::create($params);

    $this->response();
  }

}
