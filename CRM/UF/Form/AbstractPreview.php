<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
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
   * The fields needed to build this form.
   *
   * @var array
   */
  public $_fields;

  /**
   * Set the profile/field structure for this form
   *
   * @param array $fields
   *   List of fields per CRM_Core_BAO_UFGroup::formatUFFields or CRM_Core_BAO_UFGroup::getFields.
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
   * Set the default form values.
   *
   *
   * @return array
   *   the default array reference
   */
  public function setDefaultValues() {
    $defaults = array();
    foreach ($this->_fields as $name => $field) {
      if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($field['name'])) {
        CRM_Core_BAO_CustomField::setProfileDefaults($customFieldID, $name, $defaults, NULL, CRM_Profile_Form::MODE_REGISTER);
      }
    }

    //set default for country.
    CRM_Core_BAO_UFGroup::setRegisterDefaults($this->_fields, $defaults);

    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    foreach ($this->_fields as $name => $field) {
      if (empty($field['is_view'])) {
        CRM_Core_BAO_UFGroup::buildProfile($this, $field, CRM_Profile_Form::MODE_CREATE);
      }
    }
  }

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */
  /**
   * @return string
   */
  public function getTemplateFileName() {
    return 'CRM/UF/Form/Preview.tpl';
  }

}
