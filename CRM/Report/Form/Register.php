<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Report_Form_Register extends CRM_Core_Form {
  public $_id;
  protected $_values = NULL;

  public function preProcess() {
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE);
    $this->_id = CRM_Utils_Request::retrieve('id', 'String', $this, FALSE);

    CRM_Utils_System::setTitle(ts('Report Template'));

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    //   crm_core_error::debug("$this->_actions", $this->_action);
    $this->_opID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
      'report_template', 'id', 'name'
    );

    $instanceInfo = array();
  }

  /**
   * This virtual function is used to set the default values of
   * various form elements
   *
   * access        public
   *
   * @return array reference to the array of default values
   *
   */
  /**
   * @return array
   */
  function setDefaultValues() {
    $defaults = array();
    if ($this->_action & CRM_Core_Action::DELETE) {
      return $defaults;
    }
    if ($this->_id) {
      $params = array('id' => $this->_id);
      $defaults = array();
      CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_OptionValue', $params, $defaults);
    }
    else {
      $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue',
        array('option_group_id' => $this->_opID)
      );
    }
    return $defaults;
  }

  public function buildQuickForm() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons(array(
          array(
            'type' => 'next',
            'name' => ts('Delete'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
      return;
    }

    $this->add('text', 'label', ts('Title'), array('size' => 40), TRUE);
    $this->add('text', 'value', ts('URL'), array('size' => 40), TRUE);
    $this->add('text', 'name', ts('Class'), array('size' => 40), TRUE);
    $element = $this->add('text', 'weight', ts('Weight'), array('size' => 4), TRUE);
    // $element->freeze( );
    $this->add('text', 'description', ts('Description'), array('size' => 40), TRUE);

    $this->add('checkbox', 'is_active', ts('Enabled?'));
    $this->_components = CRM_Core_Component::getComponents();
    //unset the report component
    unset($this->_components['CiviReport']);

    $components = array();
    foreach ($this->_components as $name => $object) {
      $components[$object->componentID] = $object->info['translatedName'];
    }

    $this->add('select', 'component_id', ts('Component'), array('' => ts('Contact')) + $components);

    $this->addButtons(array(
        array(
          'type' => 'upload',
          'name' => ts('Save'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
    $this->addFormRule(array('CRM_Report_Form_Register', 'formRule'), $this);
  }

  /**
   * @param $fields
   * @param $files
   * @param $self
   *
   * @return array
   */
  static function formRule($fields, $files, $self) {
    $errors = array();
    $dupeClass = FALSE;
    $reportUrl = new CRM_Core_DAO_OptionValue();
    $reportUrl->option_group_id = $self->_opID;
    $reportUrl->value = $fields['value'];

    if ($reportUrl->find(TRUE) && $self->_id != $reportUrl->id) {
      $errors['value'] = ts('Url already exists in Database.');

      if ($reportUrl->name == $fields['name']) {
        $dupeClass = TRUE;
      }
    }
    if (!$dupeClass) {
      $reportClass = new CRM_Core_DAO_OptionValue();
      $reportClass->option_group_id = $self->_opID;
      $reportClass->name = $fields['name'];
      if ($reportClass->find(TRUE) && $self->_id != $reportClass->id) {
        $dupeClass = TRUE;
      }
    }

    if ($dupeClass) {
      $errors['name'] = ts('Class already exists in Database.');
    }
    return $errors;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {

      if (CRM_Core_BAO_OptionValue::del($this->_id)) {
        CRM_Core_Session::setStatus(ts('Selected %1 Report has been deleted.', array(1 => $this->_GName)), ts('Record Deleted'), 'success');
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/report/options/report_template', "reset=1"));
      }
      else {
        CRM_Core_Session::setStatus(ts('Selected %1 type has not been deleted.', array(1 => $this->_GName)), '', 'info');
        CRM_Utils_Weight::correctDuplicateWeights('CRM_Core_DAO_OptionValue', $fieldValues);
      }
    }
    else {
      // get the submitted form values.
      $params = $this->controller->exportValues($this->_name);
      $ids = array();

      $groupParams = array('name' => ('report_template'));
      $optionValue = CRM_Core_OptionValue::addOptionValue($params, $groupParams, $this->_action, $this->_id);
      CRM_Core_Session::setStatus(ts('The %1 \'%2\' has been saved.', array(1 => 'Report Template', 2 => $optionValue->label)), ts('Saved'), 'success');
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/report/options/report_template', "reset=1"));
    }
  }
}

