<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * form helper class for communication preferences inline edit section
 */
class CRM_Contact_Form_Inline_CommunicationPreferences extends CRM_Contact_Form_Inline {

  /**
   * build the form elements for communication preferences
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    CRM_Contact_Form_Edit_CommunicationPreferences::buildQuickForm($this);
    $this->addFormRule(array('CRM_Contact_Form_Edit_CommunicationPreferences', 'formRule'), $this);
  }

  /**
   * set defaults for the form
   *
   * @return array
   * @access public
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    if (!empty($defaults['preferred_language'])) {
      $languages = array_flip(CRM_Core_PseudoConstant::languages());
      $defaults['preferred_language'] = $languages[$defaults['preferred_language']];
    }

    // CRM-7119: set preferred_language to default if unset
    if (empty($defaults['preferred_language'])) {
      $config = CRM_Core_Config::singleton();
      $defaults['preferred_language'] = $config->lcMessages;
    }

    foreach (CRM_Contact_BAO_Contact::$_greetingTypes as $greeting) {
      $name = "{$greeting}_display";
      $this->assign($name, CRM_Utils_Array::value($name, $defaults));
    }
    return $defaults;
  }

  /**
   * process the form
   *
   * @return void
   * @access public
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

    CRM_Contact_BAO_Contact::create($params);

    $this->response();
  }
}
