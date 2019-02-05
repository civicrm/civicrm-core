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
    $this->addFormRule(array('CRM_Contact_Form_Inline_ContactName', 'formRule'), $this);
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
      $emails = civicrm_api3('Email', 'getcount', array('contact_id' => $form->_contactId));
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
