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
 */

/**
 * This class generates form components for Payment Processor.
 */
class CRM_Admin_Form_PaymentProcessor extends CRM_Admin_Form {
  protected $_id = NULL;

  protected $_testID = NULL;

  protected $_fields = NULL;

  protected $_ppDAO;

  public function preProcess() {
    if (!CRM_Core_Permission::check('administer payment processors')) {
      CRM_Core_Error::statusBounce('The \'administer payment processors\' permission is required to add or edit a payment processor.');
    }
    parent::preProcess();

    CRM_Utils_System::setTitle(ts('Settings - Payment Processor'));

    // get the payment processor meta information

    if ($this->_id) {
      $this->_ppType = CRM_Utils_Request::retrieve('pp', 'String', $this, FALSE, NULL);
      if (!$this->_ppType) {
        $this->_ppType = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_PaymentProcessor',
          $this->_id,
          'payment_processor_type_id'
        );
      }
      $this->set('pp', $this->_ppType);
    }
    else {
      $this->_ppType = CRM_Utils_Request::retrieve('pp', 'String', $this, TRUE, NULL);
    }

    $this->assign('ppType', $this->_ppType);
    $ppTypeName = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_PaymentProcessorType',
      $this->_ppType,
      'name'
    );
    $this->assign('ppTypeName', $ppTypeName);

    $this->_ppDAO = new CRM_Financial_DAO_PaymentProcessorType();
    $this->_ppDAO->id = $this->_ppType;

    if (!$this->_ppDAO->find(TRUE)) {
      CRM_Core_Error::fatal(ts('Could not find payment processor meta information'));
    }

    if ($this->_id) {
      $refreshURL = CRM_Utils_System::url('civicrm/admin/paymentProcessor',
        "reset=1&action=update&id={$this->_id}",
        FALSE, NULL, FALSE
      );
    }
    else {
      $refreshURL = CRM_Utils_System::url('civicrm/admin/paymentProcessor',
        "reset=1&action=add",
        FALSE, NULL, FALSE
      );
    }

    //CRM-4129
    $destination = CRM_Utils_Request::retrieve('civicrmDestination', 'String', $this);
    if ($destination) {
      $destination = urlencode($destination);
      $refreshURL .= "&civicrmDestination=$destination";
    }

    $this->assign('refreshURL', $refreshURL);

    $this->assign('is_recur', $this->_ppDAO->is_recur);

    $this->_fields = array(
      array(
        'name' => 'user_name',
        'label' => $this->_ppDAO->user_name_label,
      ),
      array(
        'name' => 'password',
        'label' => $this->_ppDAO->password_label,
      ),
      array(
        'name' => 'signature',
        'label' => $this->_ppDAO->signature_label,
      ),
      array(
        'name' => 'subject',
        'label' => $this->_ppDAO->subject_label,
      ),
      array(
        'name' => 'url_site',
        'label' => ts('Site URL'),
        'rule' => 'url',
        'msg' => ts('Enter a valid URL'),
      ),
    );

    if ($this->_ppDAO->is_recur) {
      $this->_fields[] = array(
        'name' => 'url_recur',
        'label' => ts('Recurring Payments URL'),
        'rule' => 'url',
        'msg' => ts('Enter a valid URL'),
      );
    }

    if (!empty($this->_ppDAO->url_button_default)) {
      $this->_fields[] = array(
        'name' => 'url_button',
        'label' => ts('Button URL'),
        'rule' => 'url',
        'msg' => ts('Enter a valid URL'),
      );
    }

    if (!empty($this->_ppDAO->url_api_default)) {
      $this->_fields[] = array(
        'name' => 'url_api',
        'label' => ts('API URL'),
        'rule' => 'url',
        'msg' => ts('Enter a valid URL'),
      );
    }
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

    $attributes = CRM_Core_DAO::getAttribute('CRM_Financial_DAO_PaymentProcessor');

    $this->add('text', 'name', ts('Name'),
      $attributes['name'], TRUE
    );

    $this->addRule('name', ts('Name already exists in Database.'), 'objectExists', array(
        'CRM_Financial_DAO_PaymentProcessor',
        $this->_id,
      ));

    $this->add('text', 'description', ts('Description'),
      $attributes['description']
    );

    $types = CRM_Core_PseudoConstant::paymentProcessorType();
    $this->add('select', 'payment_processor_type_id', ts('Payment Processor Type'), $types, TRUE,
      array('onchange' => "reload(true)")
    );

    // Financial Account of account type asset CRM-11515
    $accountType = CRM_Core_PseudoConstant::accountOptionValues('financial_account_type', NULL, " AND v.name = 'Asset' ");
    $financialAccount = CRM_Contribute_PseudoConstant::financialAccount(NULL, key($accountType));
    if ($fcount = count($financialAccount)) {
      $this->assign('financialAccount', $fcount);
    }
    $this->add('select', 'financial_account_id', ts('Financial Account'),
      array('' => ts('- select -')) + $financialAccount,
      TRUE
    );
    $this->addSelect('payment_instrument_id',
      array(
        'entity' => 'contribution',
        'label' => ts('Payment Method'),
        'placeholder'  => NULL,
      )
    );

    // is this processor active ?
    $this->add('checkbox', 'is_active', ts('Is this Payment Processor active?'));
    $this->add('checkbox', 'is_default', ts('Is this Payment Processor the default?'));

    foreach ($this->_fields as $field) {
      if (empty($field['label'])) {
        continue;
      }

      $this->add('text', $field['name'],
        $field['label'], $attributes[$field['name']]
      );
      $this->add('text', "test_{$field['name']}",
        $field['label'], $attributes[$field['name']]
      );
      if (!empty($field['rule'])) {
        $this->addRule($field['name'], $field['msg'], $field['rule']);
        $this->addRule("test_{$field['name']}", $field['msg'], $field['rule']);
      }
    }

    $this->addFormRule(array('CRM_Admin_Form_PaymentProcessor', 'formRule'));
  }

  /**
   * @param $fields
   *
   * @return array|bool
   */
  public static function formRule($fields) {

    // make sure that at least one of live or test is present
    // and we have at least name and url_site
    // would be good to make this processor specific
    $errors = array();

    if (!(self::checkSection($fields, $errors) ||
      self::checkSection($fields, $errors, 'test')
    )
    ) {
      $errors['_qf_default'] = ts('You must have at least the test or live section filled');
    }

    if (!empty($errors)) {
      return $errors;
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * @param $fields
   * @param $errors
   * @param null $section
   *
   * @return bool
   */
  public static function checkSection(&$fields, &$errors, $section = NULL) {
    $names = array('user_name');

    $present = FALSE;
    $allPresent = TRUE;
    foreach ($names as $name) {
      if ($section) {
        $name = "{$section}_$name";
      }
      if (!empty($fields[$name])) {
        $present = TRUE;
      }
      else {
        $allPresent = FALSE;
      }
    }

    if ($present) {
      if (!$allPresent) {
        $errors['_qf_default'] = ts('You must have at least the user_name specified');
      }
    }
    return $present;
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    $defaults = array();

    if (!$this->_id) {
      $defaults['is_active'] = $defaults['is_default'] = 1;
      $defaults['url_site'] = $this->_ppDAO->url_site_default;
      $defaults['url_api'] = $this->_ppDAO->url_api_default;
      $defaults['url_recur'] = $this->_ppDAO->url_recur_default;
      $defaults['url_button'] = $this->_ppDAO->url_button_default;
      $defaults['test_url_site'] = $this->_ppDAO->url_site_test_default;
      $defaults['test_url_api'] = $this->_ppDAO->url_api_test_default;
      $defaults['test_url_recur'] = $this->_ppDAO->url_recur_test_default;
      $defaults['test_url_button'] = $this->_ppDAO->url_button_test_default;
      $defaults['payment_instrument_id'] = $this->_ppDAO->payment_instrument_id;
      // When user changes payment processor type, it is passed in via $this->_ppType so update defaults array.
      if ($this->_ppType) {
        $defaults['payment_processor_type_id'] = $this->_ppType;
      }
      return $defaults;
    }
    $domainID = CRM_Core_Config::domainID();

    $dao = new CRM_Financial_DAO_PaymentProcessor();
    $dao->id = $this->_id;
    $dao->domain_id = $domainID;
    if (!$dao->find(TRUE)) {
      return $defaults;
    }

    CRM_Core_DAO::storeValues($dao, $defaults);
    // When user changes payment processor type, it is passed in via $this->_ppType so update defaults array.
    if ($this->_ppType) {
      $defaults['payment_processor_type_id'] = $this->_ppType;
    }

    // now get testID
    $testDAO = new CRM_Financial_DAO_PaymentProcessor();
    $testDAO->name = $dao->name;
    $testDAO->is_test = 1;
    $testDAO->domain_id = $domainID;
    if ($testDAO->find(TRUE)) {
      $this->_testID = $testDAO->id;

      foreach ($this->_fields as $field) {
        $testName = "test_{$field['name']}";
        $defaults[$testName] = $testDAO->{$field['name']};
      }
    }
    $defaults['financial_account_id'] = CRM_Financial_BAO_FinancialTypeAccount::getFinancialAccount($dao->id, 'civicrm_payment_processor', 'financial_account_id');

    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {

    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Financial_BAO_PaymentProcessor::del($this->_id);
      CRM_Core_Session::setStatus("", ts('Payment Processor Deleted.'), "success");
      return NULL;
    }

    $values = $this->controller->exportValues($this->_name);
    $domainID = CRM_Core_Config::domainID();

    if (!empty($values['is_default'])) {
      $query = "UPDATE civicrm_payment_processor SET is_default = 0 WHERE domain_id = $domainID";
      CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    }

    $this->updatePaymentProcessor($values, $domainID, FALSE);
    $this->updatePaymentProcessor($values, $domainID, TRUE);
    CRM_Core_Session::setStatus(ts('Payment processor %1 has been saved.', array(1 => "<em>{$values['name']}</em>")), ts('Saved'), 'success');
  }

  /**
   * Save a payment processor.
   *
   * @param array $values
   * @param int $domainID
   * @param bool $test
   */
  public function updatePaymentProcessor(&$values, $domainID, $test) {
    $params  = array_merge(array(
      'id' => $test ? $this->_testID : $this->_id,
      'domain_id' => $domainID,
      'is_test' => $test,
      'is_active' => 0,
      'is_default' => 0,
      'is_recur' => $this->_ppDAO->is_recur,
      'billing_mode' => $this->_ppDAO->billing_mode,
      'class_name' => $this->_ppDAO->class_name,
      'payment_type' => $this->_ppDAO->payment_type,
      'payment_instrument_id' => $this->_ppDAO->payment_instrument_id,
      'financial_account_id' => $values['financial_account_id'],
    ), $values);

    civicrm_api3('PaymentProcessor', 'create', $params);
  }

}
