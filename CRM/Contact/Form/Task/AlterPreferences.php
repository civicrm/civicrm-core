<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This class provides the functionality to alter a privacy
 * options for selected contacts
 */
class CRM_Contact_Form_Task_AlterPreferences extends CRM_Contact_Form_Task {

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    // add select for preferences

    $options = array(ts('Add Selected Options'), ts('Remove selected options'));

    $this->addRadio('actionTypeOption', ts('actionTypeOption'), $options);

    $privacyOptions = CRM_Core_SelectValues::privacy();

    foreach ($privacyOptions as $prefID => $prefName) {
      $this->_prefElement = &$this->addElement('checkbox', "pref[$prefID]", NULL, $prefName);
    }

    $this->addDefaultButtons(ts('Set Privacy Options'));
  }

  public function addRules() {
    $this->addFormRule(array('CRM_Contact_Form_Task_AlterPreferences', 'formRule'));
  }

  /**
   * Set the default form values.
   *
   *
   * @return array
   *   the default array reference
   */
  public function setDefaultValues() {
    $defaults = array();

    $defaults['actionTypeOption'] = 0;
    return $defaults;
  }

  /**
   * @param CRM_Core_Form $form
   * @param $rule
   *
   * @return array
   */
  public static function formRule($form, $rule) {
    $errors = array();
    if (empty($form['pref']) && empty($form['contact_taglist'])) {
      $errors['_qf_default'] = ts("Please select at least one privacy option.");
    }
    return $errors;
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    //get the submitted values in an array
    $params = $this->controller->exportValues($this->_name);

    $actionTypeOption = CRM_Utils_Array::value('actionTypeOption', $params, NULL);
    // If remove option has been selected set new privacy value to "false"
    $privacyValueNew = empty($actionTypeOption);

    // check if any privay option has been checked
    if (!empty($params['pref'])) {
      $privacyValues = $params['pref'];
      $count = 0;
      foreach ($this->_contactIds as $contact_id) {
        $contact = new CRM_Contact_BAO_Contact();
        $contact->id = $contact_id;

        foreach ($privacyValues as $privacy_key => $privacy_value) {
          $contact->$privacy_key = $privacyValueNew;
        }
        $contact->save();
        $count++;
      }
      // Status message
      $privacyOptions = CRM_Core_SelectValues::privacy();
      $status = array();
      foreach ($privacyValues as $privacy_key => $privacy_value) {
        $label = $privacyOptions[$privacy_key];
        $status[] = $privacyValueNew ? ts("Added '%1'", array(1 => $label)) : ts("Removed '%1'", array(1 => $label));
      }

      $status = '<ul><li>' . implode('</li><li>', $status) . '</li></ul>';
      if ($count > 1) {
        $title = ts('%1 Contacts Updated', array(1 => $count));
      }
      else {
        $name = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contact_id, 'display_name');
        $title = ts('%1 Updated', array(1 => $name));
      }

      CRM_Core_Session::setStatus($status, $title, 'success');
    }
  }

}
