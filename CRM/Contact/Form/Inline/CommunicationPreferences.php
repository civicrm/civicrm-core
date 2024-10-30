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
 * Form helper class for communication preferences inline edit section.
 */
class CRM_Contact_Form_Inline_CommunicationPreferences extends CRM_Contact_Form_Inline {

  /**
   * Build the form object elements for communication preferences.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    CRM_Contact_Form_Edit_CommunicationPreferences::buildQuickForm($this);
    $this->addFormRule(['CRM_Contact_Form_Edit_CommunicationPreferences', 'formRule'], $this);
  }

  /**
   * Set defaults for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    if (!empty($defaults['preferred_language'])) {
      $languages = CRM_Contact_BAO_Contact::buildOptions('preferred_language');
      $defaults['preferred_language'] = CRM_Utils_Array::key($defaults['preferred_language'], $languages);
    }

    // CRM-19135: where CRM_Core_BAO_Contact::getValues() set label as a default value instead of reserved 'value',
    // the code is to ensure we always set default to value instead of label

    if (empty($defaults['communication_style_id'])) {
      $defaults['communication_style_id'] = array_pop(CRM_Core_OptionGroup::values('communication_style', TRUE, NULL, NULL, 'AND is_default = 1'));
    }

    foreach (CRM_Contact_BAO_Contact::$_greetingTypes as $greeting) {
      $name = "{$greeting}_display";
      $this->assign($name, $defaults[$name] ?? NULL);
    }
    return $defaults;
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    $params = $this->exportValues();

    // Process / save communication preferences

    // this is a chekbox, so mark false if we dont get a POST value
    $params['is_opt_out'] ??= FALSE;
    $params['contact_type'] = $this->_contactType;
    $params['contact_id'] = $this->_contactId;

    if (!empty($this->_contactSubType)) {
      $params['contact_sub_type'] = $this->_contactSubType;
    }

    if (!isset($params['preferred_communication_method'])) {
      $params['preferred_communication_method'] = 'null';
    }
    CRM_Contact_BAO_Contact::create($params);

    $this->response();
  }

}
