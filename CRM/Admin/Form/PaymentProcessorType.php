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
 * This class generates form components for Location Type.
 */
class CRM_Admin_Form_PaymentProcessorType extends CRM_Admin_Form {
  protected $_id = NULL;

  protected $_fields = NULL;

  public function preProcess() {
    parent::preProcess();

    $this->_fields = [
      [
        'name' => 'name',
        'label' => ts('Name'),
        'required' => TRUE,
      ],
      [
        'name' => 'title',
        'label' => ts('Title'),
        'required' => TRUE,
      ],
      [
        'name' => 'billing_mode',
        'label' => ts('Billing Mode'),
        'required' => TRUE,
        'rule' => 'positiveInteger',
        'msg' => ts('Enter a positive integer'),
      ],
      [
        'name' => 'description',
        'label' => ts('Description'),
      ],
      [
        'name' => 'user_name_label',
        'label' => ts('User Name Label'),
      ],
      [
        'name' => 'password_label',
        'label' => ts('Password Label'),
      ],
      [
        'name' => 'signature_label',
        'label' => ts('Signature Label'),
      ],
      [
        'name' => 'subject_label',
        'label' => ts('Subject Label'),
      ],
      [
        'name' => 'class_name',
        'label' => ts('PHP class name'),
        'required' => TRUE,
      ],
      [
        'name' => 'url_site_default',
        'label' => ts('Live Site URL'),
        'required' => TRUE,
        'rule' => 'url',
        'msg' => ts('Enter a valid URL'),
      ],
      [
        'name' => 'url_api_default',
        'label' => ts('Live API URL'),
        'required' => FALSE,
        'rule' => 'url',
        'msg' => ts('Enter a valid URL'),
      ],
      [
        'name' => 'url_recur_default',
        'label' => ts('Live Recurring Payments URL'),
        'required' => TRUE,
        'rule' => 'url',
        'msg' => ts('Enter a valid URL'),
      ],
      [
        'name' => 'url_button_default',
        'label' => ts('Live Button URL'),
        'rule' => 'url',
        'msg' => ts('Enter a valid URL'),
      ],
      [
        'name' => 'url_site_test_default',
        'label' => ts('Test Site URL'),
        'required' => TRUE,
        'rule' => 'url',
        'msg' => ts('Enter a valid URL'),
      ],
      [
        'name' => 'url_api_test_default',
        'label' => ts('Test API URL'),
        'required' => FALSE,
        'rule' => 'url',
        'msg' => ts('Enter a valid URL'),
      ],
      [
        'name' => 'url_recur_test_default',
        'label' => ts('Test Recurring Payments URL'),
        'required' => TRUE,
        'rule' => 'url',
        'msg' => ts('Enter a valid URL'),
      ],
      [
        'name' => 'url_button_test_default',
        'label' => ts('Test Button URL'),
        'rule' => 'url',
        'msg' => ts('Enter a valid URL'),
      ],
    ];
  }

  /**
   * Build the form object.
   *
   * @param bool $check
   */
  public function buildQuickForm($check = FALSE) {
    parent::buildQuickForm();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_Financial_DAO_PaymentProcessorType');

    foreach ($this->_fields as $field) {
      $required = CRM_Utils_Array::value('required', $field, FALSE);
      $this->add('text', $field['name'],
        $field['label'], $attributes['name'], $required
      );
      if (!empty($field['rule'])) {
        $this->addRule($field['name'], $field['msg'], $field['rule']);
      }
    }

    // is this processor active ?
    $this->add('checkbox', 'is_active', ts('Is this Payment Processor Type active?'));
    $this->add('checkbox', 'is_default', ts('Is this Payment Processor Type the default?'));
    $this->add('checkbox', 'is_recur', ts('Does this Payment Processor Type support recurring donations?'));
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];

    if (!$this->_id) {
      $defaults['is_active'] = $defaults['is_default'] = 1;
      $defaults['user_name_label'] = ts('User Name');
      $defaults['password_label'] = ts('Password');
      $defaults['signature_label'] = ts('Signature');
      $defaults['subject_label'] = ts('Subject');
      return $defaults;
    }

    $dao = new CRM_Financial_DAO_PaymentProcessorType();
    $dao->id = $this->_id;

    if (!$dao->find(TRUE)) {
      return $defaults;
    }

    CRM_Core_DAO::storeValues($dao, $defaults);

    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    CRM_Utils_System::flushCache();

    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Financial_BAO_PaymentProcessorType::del($this->_id);
      return;
    }

    $values = $this->controller->exportValues($this->_name);

    if (!empty($values['is_default'])) {
      $query = "
UPDATE civicrm_payment_processor SET is_default = 0";
      CRM_Core_DAO::executeQuery($query);
    }

    $dao = new CRM_Financial_DAO_PaymentProcessorType();

    $dao->id = $this->_id;
    $dao->is_default = CRM_Utils_Array::value('is_default', $values, 0);
    $dao->is_active = CRM_Utils_Array::value('is_active', $values, 0);
    $dao->is_recur = CRM_Utils_Array::value('is_recur', $values, 0);

    $dao->name = $values['name'];
    $dao->description = $values['description'];

    foreach ($this->_fields as $field) {
      $dao->{$field['name']} = trim($values[$field['name']]);
      if (empty($dao->{$field['name']})) {
        $dao->{$field['name']} = 'null';
      }
    }
    $dao->save();
  }

}
