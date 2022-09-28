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

use Civi\Api4\Contribution;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_Payment_PayPalProIPN extends CRM_Core_Payment_BaseIPN {

  /**
   * Input parameters from payment processor. Store these so that
   * the code does not need to keep retrieving from the http request
   * @var array
   */
  protected $_inputParameters = [];

  /**
   * Store for the variables from the invoice string.
   * @var array
   */
  protected $_invoiceData = [];

  /**
   * Is this a payment express transaction.
   * @var bool
   */
  protected $_isPaymentExpress = FALSE;

  /**
   * Recurring contribution ID.
   *
   * @var int|null
   */
  protected $contributionRecurID;

  /**
   * Recurring contribution object.
   *
   * @var \CRM_Contribute_BAO_ContributionRecur
   */
  protected $contributionRecurObject;

  /**
   * Contribution object.
   *
   * @var \CRM_Contribute_BAO_Contribution
   */
  protected $contributionObject;
  /**
   * Contribution ID.
   *
   * @var int
   */
  protected $contributionID;

  /**
   * Get the recurring contribution ID, if any.
   *
   * @return int|null
   *
   * @throws \CRM_Core_Exception
   */
  public function getContributionRecurID(): ?int {
    if (!$this->contributionRecurID && $this->getValue('r', FALSE)) {
      $this->contributionRecurID = (int) $this->getValue('r', FALSE);
    }
    return $this->contributionRecurID;
  }

  /**
   * Get the relevant contribution ID.
   *
   * This is the contribution being paid or the original in the
   * recurring series.
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
   */
  protected function getContributionID(): int {
    if (!$this->contributionID && $this->getValue('b', TRUE)) {
      $this->contributionID = (int) $this->getValue('b', TRUE);
    }
    return $this->contributionID;
  }

  /**
   * @param int|null $contributionRecurID
   */
  public function setContributionRecurID(?int $contributionRecurID): void {
    $this->contributionRecurID = $contributionRecurID;
  }

  /**
   * Set contribution ID.
   *
   * @param int $contributionID
   */
  public function setContributionID(int $contributionID): void {
    $this->contributionID = $contributionID;
  }

  /**
   * Component.
   *
   * Are we dealing with an event an 'anything else' (contribute).
   *
   * @var string
   */
  protected $_component = 'contribute';

  /**
   * Constructor function.
   *
   * @param array $inputData
   *   Contents of HTTP REQUEST.
   *
   * @throws CRM_Core_Exception
   */
  public function __construct($inputData) {
    $this->setInputParameters($inputData);
    $this->setInvoiceData();
    parent::__construct();
  }

  /**
   * get the values from the rp_invoice_id string.
   *
   * @param string $name
   *   E.g. i, values are stored in the string with letter codes.
   * @param bool $abort
   *   Throw exception if not found
   *
   * @throws CRM_Core_Exception
   * @return mixed
   */
  public function getValue($name, $abort = TRUE) {
    if ($abort && empty($this->_invoiceData[$name])) {
      throw new CRM_Core_Exception("Failure: Missing Parameter $name");
    }
    else {
      return $this->_invoiceData[$name] ?? NULL;
    }
  }

  /**
   * Set $this->_invoiceData from the input array
   */
  public function setInvoiceData() {
    if (empty($this->_inputParameters['rp_invoice_id'])) {
      $this->_isPaymentExpress = TRUE;
      return;
    }
    $rpInvoiceArray = explode('&', $this->_inputParameters['rp_invoice_id']);
    // for clarify let's also store without the single letter unreadable
    //@todo after more refactoring we might ditch storing the one letter stuff
    $mapping = [
      'i' => 'invoice_id',
      'm' => 'component',
      'c' => 'contact_id',
      'b' => 'contribution_id',
      'r' => 'contribution_recur_id',
      'p' => 'participant_id',
      'e' => 'event_id',
    ];
    foreach ($rpInvoiceArray as $rpInvoiceValue) {
      $rpValueArray = explode('=', $rpInvoiceValue);
      $this->_invoiceData[$rpValueArray[0]] = $rpValueArray[1];
      $this->_inputParameters[$mapping[$rpValueArray[0]]] = $rpValueArray[1];
      // p has been overloaded & could mean contribution page or participant id. Clearly we need an
      // alphabet with more letters.
      // the mode will always be resolved before the mystery p is reached
      if ($rpValueArray[1] === 'contribute') {
        $mapping['p'] = 'contribution_page_id';
      }
    }
    if (empty($this->_inputParameters['component'])) {
      $this->_isPaymentExpress = TRUE;
    }
  }

  /**
   * @param string $name
   *   Of variable to return.
   * @param string $type
   *   Data type.
   *   - String
   *   - Integer
   * @param string $location
   *   Deprecated.
   * @param bool $abort
   *   Abort if empty.
   *
   * @throws CRM_Core_Exception
   * @return mixed
   */
  public function retrieve($name, $type, $location = 'POST', $abort = TRUE) {
    $value = CRM_Utils_Type::validate(
      CRM_Utils_Array::value($name, $this->_inputParameters),
      $type,
      FALSE
    );
    if ($abort && $value === NULL) {
      throw new CRM_Core_Exception("Could not find an entry for $name in $location");
    }
    return $value;
  }

  /**
   * Process recurring contributions.
   *
   * @param array $input
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function recur(array $input): void {
    // check if first contribution is completed, else complete first contribution
    $first = !$this->isContributionCompleted();
    $recur = $this->getContributionRecurObject();
    if (!isset($input['txnType'])) {
      Civi::log()->debug('PayPalProIPN: Could not find txn_type in input request.');
      echo 'Failure: Invalid parameters<p>';
      return;
    }

    // make sure the invoice ids match
    // make sure the invoice is valid and matches what we have in
    // the contribution record
    if ($recur->invoice_id != $input['invoice']) {
      Civi::log()->debug('PayPalProIPN: Invoice values dont match between database and IPN request recur is ' . $recur->invoice_id . ' input is ' . $input['invoice']);
      echo 'Failure: Invoice values dont match between database and IPN request recur is ' . $recur->invoice_id . " input is " . $input['invoice'];
      return;
    }

    $now = date('YmdHis');

    $sendNotification = FALSE;
    $subscriptionPaymentStatus = NULL;
    //List of Transaction Type
    /*
    recurring_payment_profile_created          RP Profile Created
    recurring_payment           RP Successful Payment
    recurring_payment_failed                               RP Failed Payment
    recurring_payment_profile_cancel           RP Profile Cancelled
    recurring_payment_expired         RP Profile Expired
    recurring_payment_skipped        RP Profile Skipped
    recurring_payment_outstanding_payment      RP Successful Outstanding Payment
    recurring_payment_outstanding_payment_failed          RP Failed Outstanding Payment
    recurring_payment_suspended        RP Profile Suspended
    recurring_payment_suspended_due_to_max_failed_payment  RP Profile Suspended due to Max Failed Payment
     */

    //set transaction type
    $txnType = $this->retrieve('txn_type', 'String');
    //Changes for paypal pro recurring payment
    $contributionStatuses = array_flip(CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'validate'));
    switch ($txnType) {
      case 'recurring_payment_profile_created':
        if (in_array($recur->contribution_status_id, [
          $contributionStatuses['Pending'],
          $contributionStatuses['In Progress'],
        ])
          && !empty($recur->processor_id)
        ) {
          echo "already handled";
          return;
        }
        $recur->create_date = $now;
        $recur->contribution_status_id = $contributionStatuses['Pending'];
        $recur->processor_id = $this->retrieve('recurring_payment_id', 'String');
        $recur->trxn_id = $recur->processor_id;
        $subscriptionPaymentStatus = CRM_Core_Payment::RECURRING_PAYMENT_START;
        $sendNotification = TRUE;
        break;

      case 'recurring_payment':
        if ($first) {
          $recur->start_date = $now;
        }
        else {
          if ($input['paymentStatus'] !== 'Completed') {
            throw new CRM_Core_Exception("Ignore all IPN payments that are not completed");
          }

          // In future moving to create pending & then complete, but this OK for now.
          // Also consider accepting 'Failed' like other processors.
          $input['contribution_status_id'] = $contributionStatuses['Completed'];
          $input['invoice_id'] = md5(uniqid(rand(), TRUE));
          $input['original_contribution_id'] = $this->getContributionID();
          $input['contribution_recur_id'] = $this->getContributionRecurID();

          civicrm_api3('Contribution', 'repeattransaction', $input);
          return;
        }

        //contribution installment is completed
        if ($this->retrieve('profile_status', 'String') == 'Expired') {
          if (!empty($recur->end_date)) {
            echo "already handled";
            return;
          }
          $recur->contribution_status_id = $contributionStatuses['Completed'];
          $recur->end_date = $now;
          $sendNotification = TRUE;
          $subscriptionPaymentStatus = CRM_Core_Payment::RECURRING_PAYMENT_END;
        }

        // make sure the contribution status is not done
        // since order of ipn's is unknown
        if ($recur->contribution_status_id != $contributionStatuses['Completed']) {
          $recur->contribution_status_id = $contributionStatuses['In Progress'];
        }
        break;
    }

    $recur->save();

    if ($sendNotification) {
      //send recurring Notification email for user
      CRM_Contribute_BAO_ContributionPage::recurringNotify(
        $this->getContributionID(),
        $subscriptionPaymentStatus,
        $recur
      );
    }

    if ($txnType !== 'recurring_payment') {
      return;
    }

    $this->single($input);
  }

  /**
   * @param array $input
   *
   * @return void
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  protected function single(array $input): void {

    // make sure the invoice is valid and matches what we have in the contribution record
    if (!$this->isContributionCompleted()) {
      if ($this->getContributionObject()->invoice_id !== $input['invoice']) {
        throw new CRM_Core_Exception('PayPalProIPN: Invoice values dont match between database and IPN request.');
      }
      if (!$this->getContributionRecurID() && $this->getContributionObject()->total_amount != $input['amount']) {
        throw new CRM_Core_Exception('PayPalProIPN: Amount values dont match between database and IPN request.');
      }
    }

    $status = $input['paymentStatus'];
    if ($status === 'Denied' || $status === 'Failed' || $status === 'Voided') {
      Contribution::update(FALSE)->setValues([
        'cancel_date' => 'now',
        'contribution_status_id:name' => 'Failed',
      ])->addWhere('id', '=', $this->getContributionID())->execute();
      Civi::log()->debug('Setting contribution status to Failed');
      return;
    }
    if ($status === 'Pending') {
      Civi::log()->debug('Returning since contribution status is Pending');
      return;
    }
    if ($status === 'Refunded' || $status === 'Reversed') {
      Contribution::update(FALSE)->setValues([
        'cancel_date' => 'now',
        'contribution_status_id:name' => 'Cancelled',
      ])->addWhere('id', '=', $this->getContributionID())->execute();
      Civi::log()->debug('Setting contribution status to Cancelled');
      return;
    }
    if ($status !== 'Completed') {
      Civi::log()->debug('Returning since contribution status is not handled');
      return;
    }

    if ($this->isContributionCompleted()) {
      Civi::log()->debug('PayPalProIPN: Returning since contribution has already been handled.');
      echo 'Success: Contribution has already been handled<p>';
      return;
    }

    CRM_Contribute_BAO_Contribution::completeOrder($input, $this->getContributionRecurID(), $this->getContributionID());
  }

  /**
   * Gets PaymentProcessorID for PayPal
   *
   * @return int
   */
  public function getPayPalPaymentProcessorID() {
    // This is an unreliable method as there could be more than one instance.
    // Recommended approach is to use the civicrm/payment/ipn/xx url where xx is the payment
    // processor id & the handleNotification function (which should call the completetransaction api & by-pass this
    // entirely). The only thing the IPN class should really do is extract data from the request, validate it
    // & call completetransaction or call fail? (which may not exist yet).

    Civi::log()->warning('Unreliable method used to get payment_processor_id for PayPal Pro IPN - this will cause problems if you have more than one instance');

    $paymentProcessorTypeID = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_PaymentProcessorType',
      'PayPal', 'id', 'name'
    );
    return (int) civicrm_api3('PaymentProcessor', 'getvalue', [
      'is_test' => 0,
      'options' => ['limit' => 1],
      'payment_processor_type_id' => $paymentProcessorTypeID,
      'return' => 'id',
    ]);

  }

  /**
   * This is the main function to call. It should be sufficient to instantiate the class
   * (with the input parameters) & call this & all will be done
   *
   * @todo the references to POST throughout this class need to be removed
   * @return void
   */
  public function main(): void {
    CRM_Core_Error::debug_var('GET', $_GET, TRUE, TRUE);
    CRM_Core_Error::debug_var('POST', $_POST, TRUE, TRUE);
    $input = [];
    try {
      if ($this->_isPaymentExpress) {
        $this->handlePaymentExpress();
        return;
      }
      if ($this->getValue('m') === 'event') {
        // Validate required params.
        $this->getValue('e');
        $this->getValue('p');
      }
      $input['invoice'] = $this->getValue('i');
      if ($this->getContributionObject()->contact_id !== $this->getContactID()) {
        // If the ids do not match then it is possible the contact id in the IPN has been merged into another contact which is why we use the contact_id from the contribution
        CRM_Core_Error::debug_log_message('Contact ID in IPN ' . $this->getContactID() . ' not found but contact_id found in contribution ' . $this->getContributionID() . ' used instead');
        echo 'WARNING: Could not find contact record: ' . $this->getContactID() . '<p>';
      }

      $this->getInput($input);
      $input['payment_processor_id'] = $this->_inputParameters['processor_id'] ?? $this->getPayPalPaymentProcessorID();

      if ($this->getContributionRecurID()) {
        $this->recur($input);
        return;
      }

      $this->single($input);
    }
    catch (Exception $e) {
      Civi::log()->debug($e->getMessage() . ' input {input}', ['input' => $input]);
      echo 'Invalid or missing data';
    }
  }

  /**
   * @param array $input
   *
   * @return void
   * @throws CRM_Core_Exception
   */
  public function getInput(&$input) {
    $billingID = CRM_Core_BAO_LocationType::getBilling();

    $input['txnType'] = self::retrieve('txn_type', 'String', 'POST', FALSE);
    $input['paymentStatus'] = self::retrieve('payment_status', 'String', 'POST', FALSE);

    $input['amount'] = self::retrieve('mc_gross', 'Money', 'POST', FALSE);
    $input['reasonCode'] = self::retrieve('ReasonCode', 'String', 'POST', FALSE);

    $lookup = [
      "first_name" => 'first_name',
      "last_name" => 'last_name',
      "street_address-{$billingID}" => 'address_street',
      "city-{$billingID}" => 'address_city',
      "state-{$billingID}" => 'address_state',
      "postal_code-{$billingID}" => 'address_zip',
      "country-{$billingID}" => 'address_country_code',
    ];
    foreach ($lookup as $name => $paypalName) {
      $value = self::retrieve($paypalName, 'String', 'POST', FALSE);
      $input[$name] = $value ? $value : NULL;
    }

    $input['is_test'] = self::retrieve('test_ipn', 'Integer', 'POST', FALSE);
    $input['fee_amount'] = self::retrieve('mc_fee', 'Money', 'POST', FALSE);
    $input['net_amount'] = self::retrieve('settle_amount', 'Money', 'POST', FALSE);
    $input['trxn_id'] = self::retrieve('txn_id', 'String', 'POST', FALSE);
    $input['payment_date'] = $input['receive_date'] = self::retrieve('payment_date', 'String', 'POST', FALSE);
    $input['total_amount'] = $input['amount'];
  }

  /**
   * Handle payment express IPNs.
   *
   * For one off IPNS no actual response is required
   * Recurring is more difficult as we have limited confirmation material
   * lets look up invoice id in recur_contribution & rely on the unique
   * transaction id to ensure no duplicated this may not be acceptable to all
   * sites - e.g. if they are shipping or delivering something in return then
   * the quasi security of the ids array might be required - although better to
   * http://stackoverflow.com/questions/4848227/validate-that-ipn-call-is-from-paypal
   * but let's assume knowledge on invoice id & schedule is enough for now esp
   * for donations only contribute is handled
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function handlePaymentExpress(): void {
    $input = ['invoice' => $this->getValue('i', FALSE)];
    //Avoid return in case of unit test.
    if (empty($input['invoice']) && empty($this->_inputParameters['is_unit_test'])) {
      return;
    }
    $input['txnType'] = $this->retrieve('txn_type', 'String');
    $contributionRecur = civicrm_api3('contribution_recur', 'getsingle', [
      'return' => 'contact_id, id, payment_processor_id',
      'invoice_id' => $input['invoice'],
    ]);
    $this->setContributionRecurID((int) $contributionRecur['id']);

    if ($input['txnType'] !== 'recurring_payment' && $input['txnType'] !== 'recurring_payment_profile_created') {
      throw new CRM_Core_Exception('Paypal IPNS not handled other than recurring_payments');
    }

    $this->getInput($input);
    if ($input['txnType'] === 'recurring_payment' && $this->transactionExists($input['trxn_id'])) {
      throw new CRM_Core_Exception('This transaction has already been processed');
    }
    $result = civicrm_api3('contribution', 'getsingle', ['invoice_id' => $input['invoice'], 'contribution_test' => '']);
    $this->setContributionID((int) $result['id']);
    $input['trxn_date'] = date('Y-m-d H:i:s', strtotime($this->retrieve('time_created', 'String')));
    $this->recur($input);
  }

  /**
   * Function check if transaction already exists.
   * @param string $trxn_id
   * @return bool|void
   */
  public function transactionExists($trxn_id) {
    if (CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_contribution WHERE trxn_id = %1",
      [
        1 => [$trxn_id, 'String'],
      ])
    ) {
      return TRUE;
    }
  }

  /**
   * Get the recurring contribution object.
   *
   * @return \CRM_Contribute_BAO_ContributionRecur
   * @throws \CRM_Core_Exception
   */
  protected function getContributionRecurObject(): CRM_Contribute_BAO_ContributionRecur {
    if (!$this->contributionRecurObject) {
      $contributionRecur = new CRM_Contribute_BAO_ContributionRecur();
      $contributionRecur->id = $this->getContributionRecurID();
      if (!$contributionRecur->find(TRUE)) {
        throw new CRM_Core_Exception('Failure: Could not find contribution recur record');
      }
      return $this->contributionRecurObject = $contributionRecur;
    }
    return $this->contributionRecurObject;
  }

  /**
   * @return \CRM_Contribute_BAO_Contribution
   * @throws \CRM_Core_Exception
   */
  protected function getContributionObject(): CRM_Contribute_BAO_Contribution {
    if (!$this->contributionObject) {
      // Check if the contribution exists
      // make sure contribution exists and is valid
      $contribution = new CRM_Contribute_BAO_Contribution();
      $contribution->id = $this->getContributionID();
      if (!$contribution->find(TRUE)) {
        throw new CRM_Core_Exception('Failure: Could not find contribution record');
      }
      // The DAO types it as int but doesn't return it as int.
      $contribution->contact_id = (int) $contribution->contact_id;
      $this->contributionObject = $contribution;
    }
    return $this->contributionObject;
  }

  /**
   * Get the relevant contact ID.
   *
   * @return int
   * @throws \CRM_Core_Exception
   */
  protected function getContactID(): int {
    return $this->getValue('c', TRUE);
  }

  /**
   * Is the original contribution completed.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  private function isContributionCompleted(): bool {
    $status = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $this->getContributionObject()->contribution_status_id);
    return $status === 'Completed';
  }

}
