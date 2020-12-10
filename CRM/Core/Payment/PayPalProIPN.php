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
   * @param array $ids
   * @param \CRM_Contribute_BAO_ContributionRecur $recur
   * @param \CRM_Contribute_BAO_Contribution $contribution
   * @param bool $first
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function recur($input, $ids, $recur, $contribution, $first) {
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
          if ($input['paymentStatus'] != 'Completed') {
            throw new CRM_Core_Exception("Ignore all IPN payments that are not completed");
          }

          // In future moving to create pending & then complete, but this OK for now.
          // Also consider accepting 'Failed' like other processors.
          $input['contribution_status_id'] = $contributionStatuses['Completed'];
          $input['invoice_id'] = md5(uniqid(rand(), TRUE));
          $input['original_contribution_id'] = $ids['contribution'];
          $input['contribution_recur_id'] = $ids['contributionRecur'];

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
      $autoRenewMembership = FALSE;
      if ($recur->id &&
        isset($ids['membership']) && $ids['membership']
      ) {
        $autoRenewMembership = TRUE;
      }
      //send recurring Notification email for user
      CRM_Contribute_BAO_ContributionPage::recurringNotify($subscriptionPaymentStatus,
        $ids['contact'],
        $ids['contributionPage'],
        $recur,
        $autoRenewMembership
      );
    }

    if ($txnType != 'recurring_payment') {
      return;
    }

    // CRM-13737 - am not aware of any reason why payment_date would not be set - this if is a belt & braces
    $contribution->receive_date = !empty($input['payment_date']) ? date('YmdHis', strtotime($input['payment_date'])) : $now;

    $this->single($input, [
      'related_contact' => $ids['related_contact'] ?? NULL,
      'participant' => $ids['participant'] ?? NULL,
      'contributionRecur' => $recur->id ?? NULL,
    ], $contribution, TRUE, $first);
  }

  /**
   * @param array $input
   * @param array $ids
   * @param \CRM_Contribute_BAO_Contribution $contribution
   * @param bool $recur
   * @param bool $first
   *
   * @return void
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function single($input, $ids, $contribution, $recur = FALSE, $first = FALSE) {

    // make sure the invoice is valid and matches what we have in the contribution record
    if ((!$recur) || ($recur && $first)) {
      if ($contribution->invoice_id != $input['invoice']) {
        Civi::log()->debug('PayPalProIPN: Invoice values dont match between database and IPN request.');
        echo "Failure: Invoice values dont match between database and IPN request<p>contribution is" . $contribution->invoice_id . " and input is " . $input['invoice'];
        return;
      }
    }
    else {
      $contribution->invoice_id = md5(uniqid(rand(), TRUE));
    }

    if (!$recur) {
      if ($contribution->total_amount != $input['amount']) {
        Civi::log()->debug('PayPalProIPN: Amount values dont match between database and IPN request.');
        echo "Failure: Amount values dont match between database and IPN request<p>";
        return;
      }
    }
    else {
      $contribution->total_amount = $input['amount'];
    }

    $status = $input['paymentStatus'];
    if ($status === 'Denied' || $status === 'Failed' || $status === 'Voided') {
      Contribution::update(FALSE)->setValues([
        'cancel_date' => 'now',
        'contribution_status_id:name' => 'Failed',
      ])->addWhere('id', '=', $contribution->id)->execute();
      Civi::log()->debug("Setting contribution status to Failed");
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
      ])->addWhere('id', '=', $contribution->id)->execute();
      Civi::log()->debug("Setting contribution status to Cancelled");
      return;
    }
    elseif ($status !== 'Completed') {
      Civi::log()->debug('Returning since contribution status is not handled');
      return;
    }

    // check if contribution is already completed, if so we ignore this ipn
    $completedStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
    if ($contribution->contribution_status_id == $completedStatusId) {
      Civi::log()->debug('PayPalProIPN: Returning since contribution has already been handled.');
      echo 'Success: Contribution has already been handled<p>';
      return;
    }

    CRM_Contribute_BAO_Contribution::completeOrder($input, $ids, $contribution);
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
  public function main() {
    CRM_Core_Error::debug_var('GET', $_GET, TRUE, TRUE);
    CRM_Core_Error::debug_var('POST', $_POST, TRUE, TRUE);
    try {
      if ($this->_isPaymentExpress) {
        $this->handlePaymentExpress();
        return;
      }
      $objects = $ids = $input = [];
      $this->_component = $input['component'] = self::getValue('m');
      $input['invoice'] = self::getValue('i', TRUE);
      // get the contribution and contact ids from the GET params
      $ids['contact'] = self::getValue('c', TRUE);
      $ids['contribution'] = self::getValue('b', TRUE);

      $this->getInput($input);

      if ($this->_component == 'event') {
        $ids['event'] = self::getValue('e', TRUE);
        $ids['participant'] = self::getValue('p', TRUE);
        $ids['contributionRecur'] = self::getValue('r', FALSE);
      }
      else {
        // get the optional ids
        //@ how can this not be broken retrieving from GET as we are dealing with a POST request?
        // copy & paste? Note the retrieve function now uses data from _REQUEST so this will be included
        $ids['membership'] = self::retrieve('membershipID', 'Integer', 'GET', FALSE);
        $ids['contributionRecur'] = self::getValue('r', FALSE);
        $ids['contributionPage'] = self::getValue('p', FALSE);
        $ids['related_contact'] = self::retrieve('relatedContactID', 'Integer', 'GET', FALSE);
        $ids['onbehalf_dupe_alert'] = self::retrieve('onBehalfDupeAlert', 'Integer', 'GET', FALSE);
      }

      if (!$ids['membership'] && $ids['contributionRecur']) {
        $sql = "
    SELECT m.id
      FROM civicrm_membership m
INNER JOIN civicrm_membership_payment mp ON m.id = mp.membership_id AND mp.contribution_id = %1
     WHERE m.contribution_recur_id = %2
     LIMIT 1";
        $sqlParams = [
          1 => [$ids['contribution'], 'Integer'],
          2 => [$ids['contributionRecur'], 'Integer'],
        ];
        if ($membershipId = CRM_Core_DAO::singleValueQuery($sql, $sqlParams)) {
          $ids['membership'] = $membershipId;
        }
      }

      $paymentProcessorID = CRM_Utils_Array::value('processor_id', $this->_inputParameters);
      if (!$paymentProcessorID) {
        $paymentProcessorID = self::getPayPalPaymentProcessorID();
      }

      // Check if the contribution exists
      // make sure contribution exists and is valid
      $contribution = new CRM_Contribute_BAO_Contribution();
      $contribution->id = $ids['contribution'];
      if (!$contribution->find(TRUE)) {
        throw new CRM_Core_Exception('Failure: Could not find contribution record for ' . (int) $contribution->id, NULL, ['context' => "Could not find contribution record: {$contribution->id} in IPN request: " . print_r($input, TRUE)]);
      }

      // make sure contact exists and is valid
      // use the contact id from the contribution record as the id in the IPN may not be valid anymore.
      $contact = new CRM_Contact_BAO_Contact();
      $contact->id = $contribution->contact_id;
      $contact->find(TRUE);
      if ($contact->id != $ids['contact']) {
        // If the ids do not match then it is possible the contact id in the IPN has been merged into another contact which is why we use the contact_id from the contribution
        CRM_Core_Error::debug_log_message("Contact ID in IPN {$ids['contact']} not found but contact_id found in contribution {$contribution->contact_id} used instead");
        echo "WARNING: Could not find contact record: {$ids['contact']}<p>";
        $ids['contact'] = $contribution->contact_id;
      }

      if (!empty($ids['contributionRecur'])) {
        $contributionRecur = new CRM_Contribute_BAO_ContributionRecur();
        $contributionRecur->id = $ids['contributionRecur'];
        if (!$contributionRecur->find(TRUE)) {
          CRM_Core_Error::debug_log_message("Could not find contribution recur record: {$ids['ContributionRecur']} in IPN request: " . print_r($input, TRUE));
          echo "Failure: Could not find contribution recur record: {$ids['ContributionRecur']}<p>";
          return;
        }
      }

      $objects['contact'] = &$contact;
      $objects['contribution'] = &$contribution;

      // CRM-19478: handle oddity when p=null is set in place of contribution page ID,
      if (!empty($ids['contributionPage']) && !is_numeric($ids['contributionPage'])) {
        // We don't need to worry if about removing contribution page id as it will be set later in
        //  CRM_Contribute_BAO_Contribution::loadRelatedObjects(..) using $objects['contribution']->contribution_page_id
        unset($ids['contributionPage']);
      }

      if (!$this->loadObjects($input, $ids, $objects, TRUE, $paymentProcessorID)) {
        return;
      }

      $input['payment_processor_id'] = $paymentProcessorID;

      if ($ids['contributionRecur']) {
        // check if first contribution is completed, else complete first contribution
        $first = TRUE;
        $completedStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
        if ($objects['contribution']->contribution_status_id == $completedStatusId) {
          $first = FALSE;
        }
        $this->recur($input, $ids, $objects['contributionRecur'], $objects['contribution'], $first);
        return;
      }

      $this->single($input, [
        'related_contact' => $ids['related_contact'] ?? NULL,
        'participant' => $ids['participant'] ?? NULL,
        'contributionRecur' => $ids['contributionRecur'] ?? NULL,
      ], $objects['contribution'], FALSE, FALSE);
    }
    catch (CRM_Core_Exception $e) {
      Civi::log()->debug($e->getMessage());
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
   * lets look up invoice id in recur_contribution & rely on the unique transaction id to ensure no
   * duplicated
   * this may not be acceptable to all sites - e.g. if they are shipping or delivering something in return
   * then the quasi security of the ids array might be required - although better to
   * http://stackoverflow.com/questions/4848227/validate-that-ipn-call-is-from-paypal
   * but let's assume knowledge on invoice id & schedule is enough for now esp for donations
   * only contribute is handled
   */
  public function handlePaymentExpress() {
    //@todo - loads of copy & paste / code duplication but as this not going into core need to try to
    // keep discreet
    // also note that a lot of the complexity above could be removed if we used
    // http://stackoverflow.com/questions/4848227/validate-that-ipn-call-is-from-paypal
    // as membership id etc can be derived by the load objects fn
    $objects = $ids = $input = [];
    $isFirst = FALSE;
    $input['invoice'] = self::getValue('i', FALSE);
    //Avoid return in case of unit test.
    if (empty($input['invoice']) && empty($this->_inputParameters['is_unit_test'])) {
      return;
    }
    $input['txnType'] = $this->retrieve('txn_type', 'String');
    $contributionRecur = civicrm_api3('contribution_recur', 'getsingle', [
      'return' => 'contact_id, id, payment_processor_id',
      'invoice_id' => $input['invoice'],
    ]);

    if ($input['txnType'] !== 'recurring_payment' && $input['txnType'] !== 'recurring_payment_profile_created') {
      throw new CRM_Core_Exception('Paypal IPNS not handled other than recurring_payments');
    }

    $this->getInput($input, $ids);
    if ($input['txnType'] === 'recurring_payment' && $this->transactionExists($input['trxn_id'])) {
      throw new CRM_Core_Exception('This transaction has already been processed');
    }

    $ids['contact'] = $contributionRecur['contact_id'];
    $ids['contributionRecur'] = $contributionRecur['id'];
    $result = civicrm_api3('contribution', 'getsingle', ['invoice_id' => $input['invoice'], 'contribution_test' => '']);

    $ids['contribution'] = $result['id'];
    //@todo hardcoding 'pending' for now
    $pendingStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending');
    if ($result['contribution_status_id'] == $pendingStatusId) {
      $isFirst = TRUE;
    }
    // arg api won't get this - fix it
    $ids['contributionPage'] = CRM_Core_DAO::singleValueQuery("SELECT contribution_page_id FROM civicrm_contribution WHERE invoice_id = %1", [
      1 => [
        $ids['contribution'],
        'Integer',
      ],
    ]);
    // only handle component at this stage - not terribly sure how a recurring event payment would arise
    // & suspec main function may be a victom of copy & paste
    // membership would be an easy add - but not relevant to my customer...
    $this->_component = $input['component'] = 'contribute';
    $input['trxn_date'] = date('Y-m-d H:i:s', strtotime(self::retrieve('time_created', 'String')));
    $paymentProcessorID = $contributionRecur['payment_processor_id'];

    // Check if the contribution exists
    // make sure contribution exists and is valid
    $contribution = new CRM_Contribute_BAO_Contribution();
    $contribution->id = $ids['contribution'];
    if (!$contribution->find(TRUE)) {
      throw new CRM_Core_Exception('Failure: Could not find contribution record for ' . (int) $contribution->id, NULL, ['context' => "Could not find contribution record: {$contribution->id} in IPN request: " . print_r($input, TRUE)]);
    }

    if (!empty($ids['contributionRecur'])) {
      $contributionRecur = new CRM_Contribute_BAO_ContributionRecur();
      $contributionRecur->id = $ids['contributionRecur'];
      if (!$contributionRecur->find(TRUE)) {
        CRM_Core_Error::debug_log_message("Could not find contribution recur record: {$ids['ContributionRecur']} in IPN request: " . print_r($input, TRUE));
        echo "Failure: Could not find contribution recur record: {$ids['ContributionRecur']}<p>";
        return FALSE;
      }
    }

    $objects['contribution'] = &$contribution;

    // CRM-19478: handle oddity when p=null is set in place of contribution page ID,
    if (!empty($ids['contributionPage']) && !is_numeric($ids['contributionPage'])) {
      // We don't need to worry if about removing contribution page id as it will be set later in
      //  CRM_Contribute_BAO_Contribution::loadRelatedObjects(..) using $objects['contribution']->contribution_page_id
      unset($ids['contributionPage']);
    }

    if (!$this->loadObjects($input, $ids, $objects, TRUE, $paymentProcessorID)) {
      throw new CRM_Core_Exception('Data did not validate');
    }
    $this->recur($input, $ids, $objects['contributionRecur'], $objects['contribution'], $isFirst);
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

}
