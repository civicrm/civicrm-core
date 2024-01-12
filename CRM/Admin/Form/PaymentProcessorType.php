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
 * This class generates form components for Location Type.
 */
class CRM_Admin_Form_PaymentProcessorType extends CRM_Admin_Form {
  public $_id = NULL;

  protected $_fields = NULL;

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

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
      $required = $field['required'] ?? FALSE;
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
    $dao->is_default = $values['is_default'] ?? 0;
    $dao->is_active = $values['is_active'] ?? 0;
    $dao->is_recur = $values['is_recur'] ?? 0;

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
