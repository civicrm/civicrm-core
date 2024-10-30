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
  public function setProfile(array $fields, bool $isSingleField = FALSE, bool $flag = FALSE): void {
    $this->assign('previewField', $isSingleField);
    $this->assign('viewOnly', !$flag);
    $this->set('fieldId', NULL);
    $this->assign('fields', $fields);
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
    $defaults = [];
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
