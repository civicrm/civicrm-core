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

use Civi\Api4\Contribution;
use Civi\Payment\Exception\PaymentProcessorException;

/**
 * This form records additional payments needed when event/contribution is partially paid.
 */
class CRM_Contribute_Form_AdditionalPayment extends CRM_Contribute_Form_AbstractEditPayment {
  use CRM_Contact_Form_ContactFormTrait;
  use CRM_Contribute_Form_ContributeFormTrait;
  use CRM_Event_Form_EventFormTrait;
  use CRM_Custom_Form_CustomDataTrait;

  /**
   * Id of the component entity
   * @var int
   */
  public $_id = NULL;

  protected $entity = 'Contribution';

  protected $amountDue;

  protected $_owed = NULL;

  protected $_refund = NULL;

  /**
   * @var int
   * @deprecated - use parent $this->contactID
   */
  protected $_contactId = NULL;

  protected $_contributorDisplayName = NULL;

  protected $_contributorEmail = NULL;

  protected $_toDoNotEmail = NULL;

  protected $_paymentType = NULL;

  /**
   * Internal property for contribution ID - use getContributionID().
   *
   * @var int
   *
   * @internal
   */
  protected $_contributionId;

  protected $fromEmailId = NULL;

  protected $_view = NULL;

  public $_action = NULL;

  /**
   * Pre process form.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {

    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    parent::preProcess();
    $this->_contactId = $this->_contactID;
    $this->_component = CRM_Utils_Request::retrieve('component', 'String', $this, FALSE, 'contribution');
    $this->_view = CRM_Utils_Request::retrieve('view', 'String', $this, FALSE);
    $this->assign('component', $this->_component);
    $this->assign('id', $this->_id);
    $this->assign('suppressPaymentFormButtons', $this->isBeingCalledFromSelectorContext());
    $isShowPayments = $this->isViewMode();
    $this->assign('transaction', $isShowPayments);
    if ($isShowPayments) {
      $paymentInfo = CRM_Contribute_BAO_Contribution::getPaymentInfo($this->getContributionID(), 'contribution', TRUE);
      $this->assign('payments', $paymentInfo['transaction'] ?? NULL);
      $this->assign('paymentLinks', $paymentInfo['payment_links']);
      $title = ts('View Payment');
      if (!empty($this->_component) && $this->_component === 'event') {
        $info = CRM_Event_BAO_Participant::participantDetails($this->_id);
        $title .= " - {$info['title']}";
      }
      $this->setTitle($title);
      return;
    }

    $paymentAmt = $this->getAmountDue();

    if ($paymentAmt >= 0) {
      $this->_owed = $paymentAmt;
    }

    $this->_paymentType = $this->getPaymentType();

    if ($this->isARefund() && !CRM_Core_Permission::check('refund contributions')) {
      throw new CRM_Core_Exception('You do not have permission to refund contributions.');
    }

    if (!empty($this->_mode) && $this->isARefund()) {
      throw new CRM_Core_Exception(ts('Credit card payment is not for Refund payments use'));
    }

    [$this->_contributorDisplayName, $this->_contributorEmail] = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactID);

    $this->assign('contributionMode', $this->_mode);
    $this->assign('contactId', $this->_contactID);
    $this->assign('paymentType', $this->_paymentType);
    $this->assign('paymentAmt', $paymentAmt);
    // It's easier to strip the minus sign for display purposes in php than smarty.
    $this->assign('absolutePaymentAmount', abs($paymentAmt));

    $this->setPageTitle($this->isARefund() ? ts('Refund') : ts('Payment'));
  }

  /**
   * Is this function being called from a datatable selector.
   *
   * If so we don't want to show the buttons.
   *
   * @throws \CRM_Core_Exception
   */
  protected function isBeingCalledFromSelectorContext() {
    return CRM_Utils_Request::retrieve('selector', 'Positive');
  }

  /**
   * This virtual function is used to set the default values of
   * various form elements
   *
   * access        public
   *
   * @return array
   *   reference to the array of default values
   */

  /**
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function setDefaultValues(): ?array {
    if ($this->isViewMode()) {
      return NULL;
    }
    $defaults = [];
    if ($this->_mode) {
      CRM_Core_Payment_Form::setDefaultValues($this, $this->_contactId);
      $defaults = array_merge($defaults, $this->_defaults);
    }

    if (empty($defaults['trxn_date'])) {
      $defaults['trxn_date'] = date('Y-m-d H:i:s');
    }

    if ($this->isARefund()) {
      if ($this->amountDue < 0) {
        $defaults['total_amount'] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency(abs($this->amountDue));
      }
    }
    elseif ($this->_owed) {
      $defaults['total_amount'] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($this->_owed);
    }

    // Set $newCredit variable in template to control whether link to credit card mode is included
    $this->assign('newCredit', CRM_Core_Config::isEnabledBackOfficeCreditCardPayments());

    $defaults['payment_instrument_id'] = Contribution::get(FALSE)
      ->addSelect('payment_instrument_id')
      ->addWhere('id', '=', $this->_contributionId)
      ->execute()->first()['payment_instrument_id'];

    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    if ($this->_view === 'transaction' && ($this->_action & CRM_Core_Action::BROWSE)) {
      $this->addButtons([
        [
          'type' => 'cancel',
          'name' => ts('Done'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
      ]);
      return;
    }

    CRM_Core_Payment_Form::buildPaymentForm($this, $this->_paymentProcessor, FALSE, TRUE, CRM_Utils_Request::retrieve('payment_instrument_id', 'Integer'));
    $this->addPaymentProcessorSelect(FALSE);

    $attributes = CRM_Core_DAO::getAttribute('CRM_Financial_DAO_FinancialTrxn');

    $this->addMoney('total_amount',
      $this->isARefund() ? ts('Refund Amount') : ts('Payment Amount'),
      TRUE,
      $attributes['total_amount'],
      TRUE, 'currency', NULL
    );

    //add receipt for offline contribution
    $this->addElement('checkbox', 'is_email_receipt', ts('Send Receipt?'));

    if ($this->_component === 'event') {
      $eventID = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $this->_id, 'event_id', 'id');
    }

    $this->add('select', 'from_email_address', ts('Receipt From'), CRM_Financial_BAO_Payment::getValidFromEmailsForPayment($eventID ?? NULL), FALSE, ['class' => 'crm-select2 huge']);

    $this->add('textarea', 'receipt_text', ts('Confirmation Message'));

    $this->addField('trxn_date', ['entity' => 'FinancialTrxn', 'label' => $this->isARefund() ? ts('Refund Date') : ts('Contribution Date'), 'context' => 'Contribution'], FALSE, FALSE);
    $this->assign('eventName', $this->getEventValue('title'));

    $this->assign('displayName', $this->getContactValue('display_name'));
    $this->assign('component', $this->_component);
    $this->assign('email', $this->_contributorEmail);
    if ($this->isSubmitted()) {
      // The custom data fields are added to the form by an ajax form.
      // However, if they are not present in the element index they will
      // not be available from `$this->getSubmittedValue()` in post process.
      // We do not have to set defaults or otherwise render - just add to the element index.
      $this->addCustomDataFieldsToForm('FinancialTrxn');
    }

    $js = NULL;
    // render backoffice payment fields only on offline mode
    if (!$this->_mode) {
      $js = ['onclick' => 'return verify( );'];

      $this->add('select', 'payment_instrument_id',
        ts('Payment Method'),
        ['' => ts('- select -')] + CRM_Contribute_BAO_Contribution::buildOptions('payment_instrument_id', 'create', ['filter' => 0]),
        TRUE,
        ['onChange' => "return showHideByValue('payment_instrument_id','4','checkNumber','table-row','select',false);", 'class' => 'crm-select2']
      );

      $this->add('text', 'check_number', ts('Check Number'), $attributes['financial_trxn_check_number']);
      $this->add('text', 'trxn_id', ts('Transaction ID'), ['class' => 'twelve'] + $attributes['trxn_id']);

      $this->add('text', 'fee_amount', ts('Fee Amount'),
        $attributes['fee_amount']
      );
      $this->addRule('fee_amount', ts('Please enter a valid monetary value for Fee Amount.'), 'money');
      $buttonName = $this->isARefund() ? ts('Record Refund') : ts('Record Payment');
    }
    else {
      $buttonName = ts('Submit Payment');
    }

    $this->addButtons([
      [
        'type' => 'upload',
        'name' => $buttonName,
        'js' => $js,
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
    $mailingInfo = Civi::settings()->get('mailing_backend');
    $this->assign('outBound_option', $mailingInfo['outBound_option']);

    $this->addFormRule(['CRM_Contribute_Form_AdditionalPayment', 'formRule'], $this);
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
    if ($self->_paymentProcessor['id'] === 0 && empty($fields['payment_instrument_id'])) {
      $errors['payment_instrument_id'] = ts('Payment method is a required field');
    }

    return $errors;
  }

  /**
   * Process the form submission.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    $this->submit($this->getSubmittedValues());
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view',
      "reset=1&cid={$this->_contactId}&selectedChild=" . $this->getParticipantID() ? 'participant' : 'contribute'
    ));
  }

  /**
   * Process Payments.
   *
   * @param array $submittedValues
   *
   * @throws \CRM_Core_Exception
   */
  public function submit($submittedValues) {
    if ($this->_mode) {
      $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment(
        $this->getSubmittedValue('payment_processor_id'),
        ($this->_mode === 'test')
      );
    }

    $paymentResult = [];
    if ($this->_mode) {
      // process credit card
      $paymentResult = $this->processCreditCard();
    }

    $totalAmount = $this->getSubmittedValue('total_amount');
    $trxnsData = [
      'total_amount' => $this->isARefund() ? -$totalAmount : $totalAmount,
      'check_number' => $this->getSubmittedValue('check_number'),
      'fee_amount' => $paymentResult['fee_amount'] ?? ($this->getSubmittedValue('fee_amount') ?? 0),
      'contribution_id' => $this->getContributionID(),
      'payment_processor_id' => $this->getPaymentProcessorID(),
      'card_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_FinancialTrxn', 'card_type_id', $this->getSubmittedValue('credit_card_type')),
      'pan_truncation' => $this->getSubmittedValue('pan_truncation') ?: substr((string) $this->getSubmittedValue('credit_card_number'), -4),
      'trxn_result_code' => $paymentResult['trxn_result_code'] ?? NULL,
      'payment_instrument_id' => $this->getSubmittedValue('payment_instrument_id'),
      'trxn_id' => $paymentResult['trxn_id'] ?? ($this->getSubmittedValue('trxn_id') ?? NULL),
      'trxn_date' => $this->getSubmittedValue('trxn_date'),
      // This form sends payment notification only, for historical reasons.
      'is_send_contribution_notification' => FALSE,
    ] + $this->getSubmittedCustomFields();
    $paymentID = civicrm_api3('Payment', 'create', $trxnsData)['id'];
    $contributionAddressID = CRM_Contribute_BAO_Contribution::createAddress($this->getSubmittedValues());
    if ($contributionAddressID) {
      Contribution::update(FALSE)->addWhere('id', '=', $this->getContributionID())
        ->setValues(['address_id' => $contributionAddressID])->execute();
    }
    if ($this->getContributionID() && CRM_Core_Permission::access('CiviMember')) {
      $membershipCount = CRM_Contact_BAO_Contact::getCountComponent('membership', $this->_contactID);
      // @fixme: Probably don't need a variable here but the old code counted MembershipPayment records and only returned a count if > 0
      if ($membershipCount) {
        $this->ajaxResponse['updateTabs']['#tab_member'] = $membershipCount;
      }
    }
    if ($this->getContributionID() && CRM_Core_Permission::access('CiviEvent')) {
      $participantPaymentCount = civicrm_api3('ParticipantPayment', 'getCount', ['contribution_id' => $this->_contributionId]);
      if ($participantPaymentCount) {
        $this->ajaxResponse['updateTabs']['#tab_participant'] = CRM_Contact_BAO_Contact::getCountComponent('participant', $this->_contactID);
      }
    }

    $statusMsg = ts('The payment record has been processed.');
    // send email
    if (!empty($paymentID) && $this->getSubmittedValue('is_email_receipt')) {
      $sendResult = civicrm_api3('Payment', 'sendconfirmation', ['id' => $paymentID, 'from' => $submittedValues['from_email_address']])['values'][$paymentID];
      if ($sendResult['is_sent']) {
        $statusMsg .= ' ' . ts('A receipt has been emailed to the contributor.');
      }
    }

    CRM_Core_Session::setStatus($statusMsg, ts('Saved'), 'success');
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function processCreditCard(): ?array {
    // we need to retrieve email address
    if ($this->_context === 'standalone' && $this->getSubmittedValue('is_email_receipt')) {
      [$displayName] = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactId);
    }

    // at this point we've created a contact and stored its address etc
    // all the payment processors expect the name and address to be in the
    // so we copy stuff over to first_name etc.
    $paymentParams = $this->prepareParamsForPaymentProcessor($this->getSubmittedValues() + [
      'currency' => $this->getCurrency(),
      'amount' => $this->getSubmittedValue('total_amount'),
      'contact_id' => $this->getContactID(),
      'is_email_receipt' => (bool) $this->getSubmittedValue('is_email_receipt'),
      'email' => $this->getContactValue('email_primary.email'),
    ]);

    if ($paymentParams['amount'] > 0.0) {
      if (!empty($this->contributionID)) {
        if (empty($paymentParams['description'])) {
          $paymentParams['description'] = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $this->_contributionId, 'source');
        }

        if (empty($paymentParams['financial_type_id'])) {
          $financialTypeID = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $this->_contributionId, 'financial_type_id');
          $paymentParams['financial_type_id'] = CRM_Financial_BAO_FinancialAccount::getAccountingCode($financialTypeID);
        }

        if (empty($paymentParams['contributionType_accounting_code'])) {
          // anticipate standardizing on 'financial_type_id' but until the payment processor code is updated, we need to set this param
          $paymentParams['contributionType_accounting_code'] = $paymentParams['financial_type_id'];
        }
      }

      try {
        // Re-retrieve the payment processor in case the form changed it, CRM-7179
        $payment = \Civi\Payment\System::singleton()->getById($this->getPaymentProcessorID());
        $result = $payment->doPayment($paymentParams);
        return [
          'fee_amount' => $result['fee_amount'] ?? 0,
          'trxn_id' => $result['trxn_id'] ?? NULL,
          'trxn_result_code' => $result['trxn_result_code'] ?? NULL,
        ];
      }
      catch (PaymentProcessorException $e) {
        Civi::log()->error('Payment processor exception: ' . $e->getMessage());
        $urlParams = "action=add&cid={$this->_contactId}&id={$this->_contributionId}&component={$this->_component}&mode={$this->_mode}";
        CRM_Core_Error::statusBounce($e->getMessage(), CRM_Utils_System::url('civicrm/payment/add', $urlParams));
      }
    }
    return [];
  }

  /**
   * Wrapper for unit testing the post process submit function.
   *
   * @deprecated since 5.69 will be removed around 5.75
   *
   * @param array $params
   * @param string|null $creditCardMode
   * @param string $entityType
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmit($params, $creditCardMode = NULL, $entityType = 'contribute') {
    CRM_Core_Error::deprecatedFunctionWarning('use FormTrait in tests');
    $this->_bltID = 5;
    // Required because processCreditCard calls set method on this.
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $this->controller = new CRM_Core_Controller();

    $this->assignPaymentRelatedVariables();

    if (!empty($params['contribution_id'])) {
      $this->_contributionId = $params['contribution_id'];

      $paymentAmount = $this->getAmountDue();

      if ($paymentAmount > 0) {
        $this->_owed = $paymentAmount;
      }
      $this->_paymentType = $this->getPaymentType();
    }

    if (!empty($params['contact_id'])) {
      $this->_contactId = $params['contact_id'];
    }

    if ($creditCardMode) {
      $this->_mode = $creditCardMode;
    }

    $this->_fields = [];
    $this->set('cid', $this->_contactId);
    parent::preProcess();
    $this->submit($params);
  }

  /**
   * Get the 'payment type' - ie is it a payment or a refund.
   *
   * We prefer the url action param but fall back on a guess from the balance.
   *
   * The refund|owed is not great - perhaps move to positive & negative.
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  protected function getPaymentType():string {
    $urlParam = CRM_Utils_Request::retrieve('is_refund', 'Int', $this);
    if ($urlParam === 0) {
      return 'owed';
    }
    if ($urlParam === 1) {
      return 'refund';
    }

    return $this->getAmountDue() < 0 ? 'refund' : 'owed';
  }

  /**
   * Is the form processing a refund.
   *
   * @return bool
   *
   * @throws \CRM_Core_Exception
   */
  protected function isARefund() {
    return $this->getPaymentType() === 'refund';
  }

  /**
   * @return float
   */
  protected function getAmountDue(): float {
    if (!isset($this->amountDue)) {
      $this->amountDue = CRM_Contribute_BAO_Contribution::getContributionBalance($this->getContributionID());
    }
    return $this->amountDue;
  }

  /**
   * Get the selected Contribution ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getContributionID(): int {
    if (!$this->_contributionId) {
      $component = CRM_Utils_Request::retrieve('component', 'String', $this, FALSE, 'contribution');
      if ($component === 'event') {
        $this->_contributionId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment', $this->_id, 'contribution_id', 'participant_id');
      }
      else {
        $this->_contributionId = $this->_id;
      }
    }
    return (int) $this->_contributionId;
  }

  /**
   * Get the contact ID in use.
   *
   * @api supported for external use.
   *
   * @return int
   */
  public function getContactID(): int {
    if ($this->_contactID === NULL) {
      $this->_contactID = $this->getContributionValue('contact_id');
    }
    return (int) $this->_contactID;
  }

  /**
   * Get the relevant participant ID, if any, in use.
   *
   * @return int
   *
   * @api supported for external use.
   *
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getParticipantID(): ?int {
    if (CRM_Utils_Request::retrieve('component', 'String', $this, FALSE, 'contribution') !== 'event') {
      return NULL;
    }
    return (int) CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
  }

  /**
   * Get the payment processor ID.
   *
   * @return int
   */
  public function getPaymentProcessorID(): int {
    return (int) ($this->getSubmittedValue('payment_processor_id') ?: $this->_paymentProcessor['id']);
  }

  /**
   * @return bool
   */
  public function isViewMode(): bool {
    $isShowPayments = $this->_view === 'transaction' && ($this->_action & CRM_Core_Action::BROWSE);
    return $isShowPayments;
  }

}
