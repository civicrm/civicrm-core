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
 * This class generates form components for Payment Processor.
 */
class CRM_Admin_Form_PaymentProcessor extends CRM_Admin_Form {
  use CRM_Core_Form_EntityFormTrait;

  /**
   * @var int
   * Test Payment Processor ID
   */
  protected $_testID;

  /**
   * @var \CRM_Financial_DAO_PaymentProcessorType
   * Payment Processor DAO Object
   */
  protected $_paymentProcessorDAO;

  /**
   * @var int
   * Payment processor Type ID
   */
  protected $_paymentProcessorType;

  /**
   * @var array|array[]
   */
  private $_fields;

  /**
   * Set entity fields to be assigned to the form.
   */
  protected function setEntityFields(): void {
    $this->entityFields = [
      'payment_processor_type_id' => [
        'name' => 'payment_processor_type_id',
        'required' => TRUE,
        // This is being double added - perhaps we can fix but for now....
        // dev/core#5266
        'not-auto-addable' => TRUE,
      ],
      'title' => [
        'name' => 'title',
        'required' => TRUE,
      ],
      'frontend_title' => [
        'name' => 'frontend_title',
        'required' => TRUE,
      ],
      'description' => [
        'name' => 'description',
      ],
    ];

    $this->setEntityFieldsMetadata();
  }

  /**
   * Get the name of the base entity being edited.
   *
   * @return string
   */
  public function getDefaultEntity() {
    return 'PaymentProcessor';
  }

  /**
   * Set the delete message.
   *
   * We do this from the constructor in order to do a translation.
   */
  public function setDeleteMessage() {
    $this->deleteMessage = ts('Deleting this Payment Processor may result in some transaction pages being rendered inactive.') . ' ' . ts('Do you want to continue?');
  }

  /**
   * Preprocess the form.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    parent::preProcess();
    CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/admin/paymentProcessor', ['reset' => 1], FALSE, NULL, FALSE));

    $this->setPaymentProcessorTypeID();
    $this->setPaymentProcessor();
    $this->assign('ppType', $this->_paymentProcessorType);
    $this->assign('ppTypeName', $this->_paymentProcessorDAO->name);

    $this->assign('refreshURL', $this->getRefreshURL());

    $this->assign('is_recur', $this->_paymentProcessorDAO->is_recur);

    $this->_fields = [
      [
        'name' => 'user_name',
        'label' => $this->_paymentProcessorDAO->user_name_label,
      ],
      [
        'name' => 'password',
        'label' => $this->_paymentProcessorDAO->password_label,
      ],
      [
        'name' => 'signature',
        'label' => $this->_paymentProcessorDAO->signature_label,
      ],
      [
        'name' => 'subject',
        'label' => $this->_paymentProcessorDAO->subject_label,
      ],
      [
        'name' => 'url_site',
        'label' => ts('Site URL'),
        'rule' => 'url',
        'msg' => ts('Enter a valid URL'),
      ],
    ];

    if ($this->_paymentProcessorDAO->is_recur) {
      $this->_fields[] = [
        'name' => 'url_recur',
        'label' => ts('Recurring Payments URL'),
        'rule' => 'url',
        'msg' => ts('Enter a valid URL'),
      ];
    }

    if (!empty($this->_paymentProcessorDAO->url_button_default)) {
      $this->_fields[] = [
        'name' => 'url_button',
        'label' => ts('Button URL'),
        'rule' => 'url',
        'msg' => ts('Enter a valid URL'),
      ];
    }

    if (!empty($this->_paymentProcessorDAO->url_api_default)) {
      $this->_fields[] = [
        'name' => 'url_api',
        'label' => ts('API URL'),
        'rule' => 'url',
        'msg' => ts('Enter a valid URL'),
      ];
    }
  }

  /**
   * Build the form object.
   *
   * @param bool $check
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm($check = FALSE) {
    $this->buildQuickEntityForm();

    if ($this->isDeleteContext()) {
      return;
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_Financial_DAO_PaymentProcessor');

    // @todo - remove this & let the entityForm do it - need to make sure we are handling the js though.
    $this->add('select',
      'payment_processor_type_id',
      ts('Payment Processor Type'),
      CRM_Financial_BAO_PaymentProcessor::buildOptions('payment_processor_type_id'),
      TRUE
    );

    // Financial Account of account type asset CRM-11515
    $accountType = CRM_Core_PseudoConstant::accountOptionValues('financial_account_type', NULL, " AND v.name = 'Asset' ");
    $financialAccount = CRM_Contribute_PseudoConstant::financialAccount(NULL, key($accountType));
    if ($fcount = count($financialAccount)) {
      $this->assign('financialAccount', $fcount);
    }
    $this->add('select', 'financial_account_id', ts('Financial Account'),
      ['' => ts('- select -')] + $financialAccount,
      TRUE
    );
    $this->addSelect('payment_instrument_id',
      [
        'entity' => 'contribution',
        'label' => ts('Payment Method'),
        'placeholder'  => NULL,
      ]
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

      $this->addField($field['name'], ['label' => $field['label']]);

      $fieldSpec = civicrm_api3($this->getDefaultEntity(), 'getfield', [
        'name' => $field['name'],
        'action' => 'create',
      ]);
      $this->add($fieldSpec['values']['html']['type'], "test_{$field['name']}",
        $field['label'], $attributes[$field['name']]
      );
      if (!empty($field['rule'])) {
        $this->addRule($field['name'], $field['msg'], $field['rule']);
        $this->addRule("test_{$field['name']}", $field['msg'], $field['rule']);
      }
    }

    $this->addFormRule(['CRM_Admin_Form_PaymentProcessor', 'formRule']);
  }

  /**
   * @param array $fields
   *
   * @return array|bool
   */
  public static function formRule($fields) {

    // make sure that at least one of live or test is present
    // and we have at least name and url_site
    // would be good to make this processor specific
    $errors = [];

    if (!(self::checkSection($fields, $errors) ||
      self::checkSection($fields, $errors, 'test')
    )
    ) {
      $errors['_qf_default'] = ts('You must have at least the test or live section filled');
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * @param array $fields
   * @param array $errors
   * @param string|null $section
   *
   * @return bool
   */
  public static function checkSection(&$fields, &$errors, $section = NULL): bool {
    $names = ['user_name'];

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
    $defaults = [];

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
      $defaults['financial_account_id'] = CRM_Financial_BAO_PaymentProcessor::getDefaultFinancialAccountID();
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
      CRM_Core_Session::setStatus(ts('Payment Processor Type (ID=%1) not found. Did you disable the payment processor extension?', [1 => $this->_paymentProcessorType]), ts('Missing Payment Processor'), 'alert');
    }

    $cards = json_decode(CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_PaymentProcessor',
          $this->_id,
          'accepted_credit_cards'
         ) ?? '', TRUE);
    $acceptedCards = [];
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
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {

    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Financial_BAO_PaymentProcessor::deleteRecord(['id' => $this->_id]);
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
    $this->updatePaymentProcessor($values, $domainID, TRUE);
    $paymentProcessorID = $this->updatePaymentProcessor($values, $domainID, FALSE);
    // Set the ID so that if it fails checkConfig the refreshUrl takes it into account.
    $this->_id = $paymentProcessorID;
    $processor = Civi\Payment\System::singleton()->getById($paymentProcessorID);
    $errors = $processor->checkConfig();
    if ($errors) {
      CRM_Core_Session::setStatus($errors, ts('Payment processor configuration invalid'), 'error');
      Civi::log()->error('Payment processor configuration invalid: ' . $errors);
      CRM_Core_Session::singleton()->pushUserContext($this->getRefreshURL());
    }
    else {
      CRM_Core_Session::setStatus(ts('Payment processor %1 has been saved.', [1 => "<em>{$values['title']}</em>"]), ts('Saved'), 'success');
    }
  }

  /**
   * Save a payment processor.
   *
   * @param array $values
   * @param int $domainID
   * @param bool $test
   *
   * @throws \CRM_Core_Exception
   */
  public function updatePaymentProcessor($values, $domainID, $test) {
    if ($test) {
      foreach (['user_name', 'password', 'signature', 'url_site', 'url_recur', 'url_api', 'url_button', 'subject'] as $field) {
        $values[$field] = empty($values["test_{$field}"]) ? CRM_Utils_Array::value($field, $values) : $values["test_{$field}"];
      }
    }
    if (!empty($values['accept_credit_cards'])) {
      $creditCards = [];
      $accptedCards = array_keys($values['accept_credit_cards']);
      $creditCardTypes = CRM_Contribute_PseudoConstant::creditCard();
      foreach ($creditCardTypes as $type => $val) {
        if (in_array($type, $accptedCards)) {
          $creditCards[$type] = $creditCardTypes[$type];
        }
      }
    }

    $params = array_merge([
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
      'accepted_credit_cards' => $creditCards ?? NULL,
    ], $values);

    return civicrm_api4('PaymentProcessor', 'save', [
      'records' => [$params],
    ])->first()['id'];
  }

  /**
   * Set the payment processor type id as a form property
   *
   * @throws \CRM_Core_Exception
   */
  protected function setPaymentProcessorTypeID(): void {
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
      $payPal = CRM_Core_PseudoConstant::getKey('CRM_Financial_DAO_PaymentProcessor', 'payment_processor_type_id', 'PayPal');
      $this->_paymentProcessorType = CRM_Utils_Request::retrieve('pp', 'String', $this, FALSE, $payPal);
    }
  }

  /**
   * Get the relevant payment processor type id.
   *
   * @return int
   */
  protected function getPaymentProcessorTypeID(): int {
    return (int) $this->_paymentProcessorType;
  }

  /**
   * Set the payment processor as a form property.
   */
  protected function setPaymentProcessor(): void {
    $this->_paymentProcessorDAO = new CRM_Financial_DAO_PaymentProcessorType();
    $this->_paymentProcessorDAO->id = $this->getPaymentProcessorTypeID();
    $this->_paymentProcessorDAO->find(TRUE);
  }

  /**
   * @return string
   * @throws \CRM_Core_Exception
   */
  private function getRefreshURL(): string {
    if ($this->_id) {
      $refreshURL = CRM_Utils_System::url('civicrm/admin/paymentProcessor/edit',
        "reset=1&action=update&id={$this->_id}",
        FALSE, NULL, FALSE
      );
    }
    else {
      $refreshURL = CRM_Utils_System::url('civicrm/admin/paymentProcessor/edit',
        'reset=1&action=add',
        FALSE, NULL, FALSE
      );
    }

    //CRM-4129
    $destination = CRM_Utils_Request::retrieve('civicrmDestination', 'String', $this);
    if ($destination) {
      $destination = urlencode($destination);
      $refreshURL .= "&civicrmDestination=$destination";
    }
    return $refreshURL;
  }

}
