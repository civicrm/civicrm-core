<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This class generates form components for Payment Processor.
 */
class CRM_Admin_Form_PaymentProcessor extends CRM_Admin_Form {
  protected $_id = NULL;

  protected $_testID = NULL;

  protected $_fields = NULL;

  protected $_paymentProcessorDAO;

  /**
   * @var int $_paymentProcessorType Payment processor Type ID
   */
  protected $_paymentProcessorType;

  /**
   * Get the name of the base entity being edited.
   *
   * @return string
   */
  public function getDefaultEntity() {
    return 'PaymentProcessor';
  }

  public function preProcess() {
    parent::preProcess();

    if ($this->_id) {
      $this->_paymentProcessorType = CRM_Utils_Request::retrieve('pp', 'String', $this, FALSE, NULL);
      if (!$this->_paymentProcessorType) {
        $this->_paymentProcessorType = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_PaymentProcessor',
          $this->_id,
          'payment_processor_type_id'
        );
      }
      $this->set('pp', $this->_paymentProcessorType);
    }
    else {
      $this->_paymentProcessorType = CRM_Utils_Request::retrieve('pp', 'String', $this, TRUE, NULL);
    }

    $this->assign('ppType', $this->_paymentProcessorType);
    $ppTypeName = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_PaymentProcessorType',
      $this->_paymentProcessorType,
      'name'
    );
    $this->assign('ppTypeName', $ppTypeName);

    $this->_paymentProcessorDAO = new CRM_Financial_DAO_PaymentProcessorType();
    $this->_paymentProcessorDAO->id = $this->_paymentProcessorType;

    $this->_paymentProcessorDAO->find(TRUE);

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

    $this->refreshURL = $refreshURL;
    $this->assign('refreshURL', $refreshURL);

    $this->assign('is_recur', $this->_paymentProcessorDAO->is_recur);

    $this->_fields = array(
      array(
        'name' => 'user_name',
        'label' => $this->_paymentProcessorDAO->user_name_label,
      ),
      array(
        'name' => 'password',
        'label' => $this->_paymentProcessorDAO->password_label,
      ),
      array(
        'name' => 'signature',
        'label' => $this->_paymentProcessorDAO->signature_label,
      ),
      array(
        'name' => 'subject',
        'label' => $this->_paymentProcessorDAO->subject_label,
      ),
      array(
        'name' => 'url_site',
        'label' => ts('Site URL'),
        'rule' => 'url',
        'msg' => ts('Enter a valid URL'),
      ),
    );

    if ($this->_paymentProcessorDAO->is_recur) {
      $this->_fields[] = array(
        'name' => 'url_recur',
        'label' => ts('Recurring Payments URL'),
        'rule' => 'url',
        'msg' => ts('Enter a valid URL'),
      );
    }

    if (!empty($this->_paymentProcessorDAO->url_button_default)) {
      $this->_fields[] = array(
        'name' => 'url_button',
        'label' => ts('Button URL'),
        'rule' => 'url',
        'msg' => ts('Enter a valid URL'),
      );
    }

    if (!empty($this->_paymentProcessorDAO->url_api_default)) {
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
        'name',
        CRM_Core_Config::domainID(),
      ));

    $this->add('text', 'description', ts('Description'),
      $attributes['description']
    );

    $this->add('select',
      'payment_processor_type_id',
      ts('Payment Processor Type'),
      CRM_Financial_BAO_PaymentProcessor::buildOptions('payment_processor_type_id'),
      TRUE,
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
    $creditCardTypes = CRM_Contribute_PseudoConstant::creditCard();
    $this->addCheckBox('accept_credit_cards', ts('Accepted Credit Card Type(s)'),
      $creditCardTypes, NULL, NULL, NULL, NULL, '&nbsp;&nbsp;&nbsp;');
    foreach ($this->_fields as $field) {
      if (empty($field['label'])) {
        continue;
      }

      $this->addField($field['name'], array('label' => $field['label']));

      $fieldSpec = civicrm_api3($this->getDefaultEntity(), 'getfield', array(
        'name' => $field['name'],
        'action' => 'create',
      ));
      $this->add($fieldSpec['values']['html']['type'], "test_{$field['name']}",
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
      $defaults['url_site'] = $this->_paymentProcessorDAO->url_site_default;
      $defaults['url_api'] = $this->_paymentProcessorDAO->url_api_default;
      $defaults['url_recur'] = $this->_paymentProcessorDAO->url_recur_default;
      $defaults['url_button'] = $this->_paymentProcessorDAO->url_button_default;
      $defaults['test_url_site'] = $this->_paymentProcessorDAO->url_site_test_default;
      $defaults['test_url_api'] = $this->_paymentProcessorDAO->url_api_test_default;
      $defaults['test_url_recur'] = $this->_paymentProcessorDAO->url_recur_test_default;
      $defaults['test_url_button'] = $this->_paymentProcessorDAO->url_button_test_default;
      $defaults['payment_instrument_id'] = $this->_paymentProcessorDAO->payment_instrument_id;
      // When user changes payment processor type, it is passed in via $this->_ppType so update defaults array.
      if ($this->_paymentProcessorType) {
        $defaults['payment_processor_type_id'] = $this->_paymentProcessorType;
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
    // If payment processor ID does not exist, $paymentProcessorName will be FALSE
    $paymentProcessorName = CRM_Core_PseudoConstant::getName('CRM_Financial_BAO_PaymentProcessor', 'payment_processor_type_id', $this->_paymentProcessorType);
    if ($this->_paymentProcessorType && $paymentProcessorName) {
      // When user changes payment processor type, it is passed in via $this->_ppType so update defaults array.
      $defaults['payment_processor_type_id'] = $this->_paymentProcessorType;
    }
    else {
      CRM_Core_Session::setStatus('Payment Processor Type (ID=' . $this->_paymentProcessorType . ') not found. Did you disable the payment processor extension?', 'Missing Payment Processor', 'alert');
    }

    $cards = json_decode(CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_PaymentProcessor',
          $this->_id,
          'accepted_credit_cards'
        ), TRUE);
    $acceptedCards = array();
    if (!empty($cards)) {
      foreach ($cards as $card => $val) {
        $acceptedCards[$card] = 1;
      }
    }
    $defaults['accept_credit_cards'] = $acceptedCards;
    unset($defaults['accepted_credit_cards']);
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
    $defaults['financial_account_id'] = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($dao->id, NULL, 'civicrm_payment_processor');

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
      CRM_Core_DAO::executeQuery($query);
    }

    if ($this->_paymentProcessorType !== $values['payment_processor_type_id']) {
      // If we changed the payment processor type, need to update the object as well
      $this->_paymentProcessorType = $values['payment_processor_type_id'];
      $this->_paymentProcessorDAO = new CRM_Financial_DAO_PaymentProcessorType();
      $this->_paymentProcessorDAO->id = $values['payment_processor_type_id'];
      $this->_paymentProcessorDAO->find(TRUE);
    }
    $this->updatePaymentProcessor($values, $domainID, FALSE);
    $this->updatePaymentProcessor($values, $domainID, TRUE);

    $processor = civicrm_api3('payment_processor', 'getsingle', array('name' => $values['name'], 'is_test' => 0));
    $errors = Civi\Payment\System::singleton()->checkProcessorConfig($processor);
    if ($errors) {
      CRM_Core_Session::setStatus($errors, 'Payment processor configuration invalid', 'error');
      Civi::log()->error('Payment processor configuration invalid: ' . $errors);
      CRM_Core_Session::singleton()->pushUserContext($this->refreshURL);
    }
    else {
      CRM_Core_Session::setStatus(ts('Payment processor %1 has been saved.', array(1 => "<em>{$values['name']}</em>")), ts('Saved'), 'success');
    }
  }

  /**
   * Save a payment processor.
   *
   * @param array $values
   * @param int $domainID
   * @param bool $test
   */
  public function updatePaymentProcessor(&$values, $domainID, $test) {
    if ($test) {
      foreach (array('user_name', 'password', 'signature', 'url_site', 'url_recur', 'url_api', 'url_button', 'subject') as $field) {
        $values[$field] = empty($values["test_{$field}"]) ? CRM_Utils_Array::value($field, $values) : $values["test_{$field}"];
      }
    }
    if (!empty($values['accept_credit_cards'])) {
      $creditCards = array();
      $accptedCards = array_keys($values['accept_credit_cards']);
      $creditCardTypes = CRM_Contribute_PseudoConstant::creditCard();
      foreach ($creditCardTypes as $type => $val) {
        if (in_array($type, $accptedCards)) {
          $creditCards[$type] = $creditCardTypes[$type];
        }
      }
      $creditCards = json_encode($creditCards);
    }
    else {
      $creditCards = "NULL";
    }
    $params  = array_merge(array(
      'id' => $test ? $this->_testID : $this->_id,
      'domain_id' => $domainID,
      'is_test' => $test,
      'is_active' => 0,
      'is_default' => 0,
      'is_recur' => $this->_paymentProcessorDAO->is_recur,
      'billing_mode' => $this->_paymentProcessorDAO->billing_mode,
      'class_name' => $this->_paymentProcessorDAO->class_name,
      'payment_type' => $this->_paymentProcessorDAO->payment_type,
      'payment_instrument_id' => $this->_paymentProcessorDAO->payment_instrument_id,
      'financial_account_id' => $values['financial_account_id'],
      'accepted_credit_cards' => $creditCards,
    ), $values);

    civicrm_api3('PaymentProcessor', 'create', $params);
  }

}
