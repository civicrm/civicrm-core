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

    // CRM-7119: set preferred_language to default if unset
    if (empty($defaults['preferred_language'])) {
      $config = CRM_Core_Config::singleton();
      $defaults['preferred_language'] = $config->lcMessages;
    }

    // CRM-19135: where CRM_Core_BAO_Contact::getValues() set label as a default value instead of reserved 'value',
    // the code is to ensure we always set default to value instead of label
    if (!empty($defaults['preferred_mail_format'])) {
      $defaults['preferred_mail_format'] = array_search($defaults['preferred_mail_format'], CRM_Core_SelectValues::pmf());
    }

    if (empty($defaults['communication_style_id'])) {
      $defaults['communication_style_id'] = array_pop(CRM_Core_OptionGroup::values('communication_style', TRUE, NULL, NULL, 'AND is_default = 1'));
    }

    foreach (CRM_Contact_BAO_Contact::$_greetingTypes as $greeting) {
      $name = "{$greeting}_display";
      $this->assign($name, CRM_Utils_Array::value($name, $defaults));
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
    $params['is_opt_out'] = CRM_Utils_Array::value('is_opt_out', $params, FALSE);
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
