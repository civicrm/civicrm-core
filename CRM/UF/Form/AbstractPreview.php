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
 * This class generates form components
 * for previewing Civicrm Profile Group
 *
 */
class CRM_UF_Form_AbstractPreview extends CRM_Core_Form {

  /**
   * the fields needed to build this form
   *
   * @var array
   */
  public $_fields;

  /**
   * Set the profile/field structure for this form
   *
   * @param array $fields list of fields per CRM_Core_BAO_UFGroup::formatUFFields or CRM_Core_BAO_UFGroup::getFields
   * @param bool $isSingleField
   * @param bool $flag
   */
  public function setProfile($fields, $isSingleField = FALSE, $flag = FALSE) {
    if ($isSingleField) {
      $this->assign('previewField', $isSingleField);
    }

    if ($flag) {
      $this->assign('viewOnly', FALSE);
    }
    else {
      $this->assign('viewOnly', TRUE);
    }

    $this->set('fieldId', NULL);
    $this->assign("fields", $fields);
    $this->_fields = $fields;
  }

  /**
   * Set the default form values
   *
   * @access protected
   *
   * @return array the default array reference
   */
  function setDefaultValues() {
    $defaults = array();
    $stateCountryMap = array();
    foreach ($this->_fields as $name => $field) {
      if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($field['name'])) {
        CRM_Core_BAO_CustomField::setProfileDefaults($customFieldID, $name, $defaults, NULL, CRM_Profile_Form::MODE_REGISTER);
      }

      //CRM-5403
      if ((substr($name, 0, 14) === 'state_province') || (substr($name, 0, 7) === 'country') || (substr($name, 0, 6) === 'county')) {
        list($fieldName, $index) = CRM_Utils_System::explode('-', $name, 2);
        if (!array_key_exists($index, $stateCountryMap)) {
          $stateCountryMap[$index] = array();
        }
        $stateCountryMap[$index][$fieldName] = $name;
      }
    }

    // also take care of state country widget
    if (!empty($stateCountryMap)) {
      CRM_Core_BAO_Address::addStateCountryMap($stateCountryMap, $defaults);
    }

    //set default for country.
    CRM_Core_BAO_UFGroup::setRegisterDefaults($this->_fields, $defaults);

    // now fix all state country selectors
    CRM_Core_BAO_Address::fixAllStateSelects($this, $defaults);

    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    foreach ($this->_fields as $name => $field) {
      if (!CRM_Utils_Array::value('is_view', $field)) {
        CRM_Core_BAO_UFGroup::buildProfile($this, $field, CRM_Profile_Form::MODE_CREATE);
      }
    }
  }

  public function getTemplateFileName() {
    return 'CRM/UF/Form/Preview.tpl';
  }
}
