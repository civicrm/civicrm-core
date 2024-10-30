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
class CRM_Report_Form_Register extends CRM_Core_Form {
  public $_id;
  protected $_values = NULL;

  public function preProcess() {
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE);
    $this->_id = CRM_Utils_Request::retrieve('id', 'String', $this, FALSE);

    $this->setTitle(ts('Report Template'));

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->_opID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup',
      'report_template', 'id', 'name'
    );

    $instanceInfo = [];
  }

  /**
   * This virtual function is used to set the default values of.
   * various form elements
   *
   * access        public
   *
   * @return array
   *   reference to the array of default values
   */
  public function setDefaultValues() {
    $defaults = [];
    if ($this->_action & CRM_Core_Action::DELETE) {
      return $defaults;
    }
    if ($this->_id) {
      $params = ['id' => $this->_id];
      $defaults = [];
      CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_OptionValue', $params, $defaults);
    }
    else {
      $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue',
        ['option_group_id' => $this->_opID]
      );
    }
    return $defaults;
  }

  public function buildQuickForm() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons([
          [
            'type' => 'next',
            'name' => ts('Delete'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'isDefault' => TRUE,
          ],
          [
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ],
      ]);
      return;
    }

    $this->add('text', 'label', ts('Title'), ['size' => 40], TRUE);
    $this->add('text', 'value', ts('URL'), ['size' => 40], TRUE);
    $this->add('text', 'name', ts('Class'), ['size' => 40], TRUE);
    $element = $this->add('number', 'weight', ts('Order'), ['size' => 4], TRUE);
    // $element->freeze( );
    $this->add('text', 'description', ts('Description'), ['size' => 40], TRUE);

    $this->add('checkbox', 'is_active', ts('Enabled?'));
    $this->_components = CRM_Core_Component::getComponents();
    //unset the report component
    unset($this->_components['CiviReport']);

    $components = [];
    foreach ($this->_components as $name => $object) {
      $components[$object->componentID] = $object->info['translatedName'];
    }

    $this->add('select', 'component_id', ts('Component'), ['' => ts('Contact')] + $components);

    $this->addButtons([
        [
          'type' => 'upload',
          'name' => ts('Save'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
    ]);
    $this->addFormRule(['CRM_Report_Form_Register', 'formRule'], $this);
  }

  /**
   * @param $fields
   * @param $files
   * @param self $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];
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
   * Process the form submission.
   *
   *
   * @return void
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {

      if (CRM_Core_BAO_OptionValue::deleteRecord(['id' => $this->_id])) {
        CRM_Core_Session::setStatus(ts('Selected %1 Report has been deleted.', [1 => $this->_GName]), ts('Record Deleted'), 'success');
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/report/options/report_template', "reset=1"));
      }
      else {
        CRM_Core_Session::setStatus(ts('Selected %1 type has not been deleted.', [1 => $this->_GName]), '', 'info');
        CRM_Utils_Weight::correctDuplicateWeights('CRM_Core_DAO_OptionValue', $fieldValues);
      }
    }
    else {
      // get the submitted form values.
      $params = $this->controller->exportValues($this->_name);

      $optionValue = CRM_Core_OptionValue::addOptionValue($params, 'report_template', $this->_action, $this->_id);
      CRM_Core_Session::setStatus(ts('The %1 \'%2\' has been saved.', [
        1 => 'Report Template',
        2 => $optionValue->label,
      ]), ts('Saved'), 'success');
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/report/options/report_template', "reset=1"));
    }
  }

}
