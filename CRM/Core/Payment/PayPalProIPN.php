<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Core_Payment_PayPalProIPN extends CRM_Core_Payment_BaseIPN {

  static $_paymentProcessor = NULL;

  /**
   * Input parameters from payment processor. Store these so that
   * the code does not need to keep retrieving from the http request
   * @var array
   */
  protected $_inputParameters = array();

  /**
   * store for the variables from the invoice string
   * @var array
   */
  protected $_invoiceData = array();

  /**
   * Is this a payment express transaction
   */
  protected $_isPaymentExpress = FALSE;

  /**
   * Are we dealing with an event an 'anything else' (contribute)
   * @var string component
   */
  protected $_component = 'contribute';
  /**
   * constructor function
   */
  function __construct($inputData) {
    $this->setInputParameters($inputData);
    $this->setInvoiceData();
    parent::__construct();
  }

  /**
   * function exists to get the values from the rp_invoice_id string
   * @param string $name e.g. i, values are stored in the string with letter codes
   * @param boolean $abort fatal if not found?
   * @return unknown
   */
  function getValue($name, $abort = TRUE) {
    if ($abort && empty($this->_invoiceData[$name])) {
      throw new CRM_Core_Exception("Failure: Missing Parameter $name");
    }
    else {
      return CRM_Utils_Array::value($name, $this->_invoiceData);
    }
  }

  /**
   * Set $this->_invoiceData from the input array
   */
  function setInvoiceData() {
    if(empty($this->_inputParameters['rp_invoice_id'])) {
      $this->_isPaymentExpress = TRUE;
      return;
    }
    $rpInvoiceArray = explode('&', $this->_inputParameters['rp_invoice_id']);
    // for clarify let's also store without the single letter unreadable
    //@todo after more refactoring we might ditch storing the one letter stuff
    $mapping = array(
      'i' => 'invoice_id',
      'm' => 'component',
      'c' => 'contact_id',
      'b' => 'contribution_id',
      'r' => 'contribution_recur_id',
      'p' => 'participant_id',
      'e' => 'event_id',
    );
    foreach ($rpInvoiceArray as $rpInvoiceValue) {
      $rpValueArray = explode('=', $rpInvoiceValue);
      $this->_invoiceData[$rpValueArray[0]] = $rpValueArray[1];
      $this->_inputParameters[$mapping[$rpValueArray[0]]] = $rpValueArray[1];
      // p has been overloaded & could mean contribution page or participant id. Clearly we need an
      // alphabet with more letters.
      // the mode will always be resolved before the mystery p is reached
      if($rpValueArray[1] == 'contribute') {
        $mapping['p'] = 'contribution_page_id';
      }
    }
  }

  /**
   * @param string $name of variable to return
   * @param string $type data type
   *   - String
   *   - Integer
   * @param string $location - deprecated
   * @param boolean $abort abort if empty
   * @return Ambigous <mixed, NULL, value, unknown, array, number>
   */
  function retrieve($name, $type, $location = 'POST', $abort = TRUE) {
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
   * Process recurring contributions
   * @param array $input
   * @param array $ids
   * @param array $objects
   * @param boolean $first
   * @return void|boolean
   */
  function recur(&$input, &$ids, &$objects, $first) {
    if (!isset($input['txnType'])) {
      CRM_Core_Error::debug_log_message("Could not find txn_type in input request");
      echo "Failure: Invalid parameters<p>";
      return FALSE;
    }

    if ($input['txnType'] == 'recurring_payment' &&
      $input['paymentStatus'] != 'Completed'
    ) {
      CRM_Core_Error::debug_log_message("Ignore all IPN payments that are not completed");
      echo "Failure: Invalid parameters<p>";
      return FALSE;
    }

    $recur = &$objects['contributionRecur'];

    // make sure the invoice ids match
    // make sure the invoice is valid and matches what we have in
    // the contribution record
    if ($recur->invoice_id != $input['invoice']) {
      CRM_Core_Error::debug_log_message("Invoice values dont match between database and IPN request recur is " . $recur->invoice_id . " input is " . $input['invoice']);
      echo "Failure: Invoice values dont match between database and IPN request recur is " . $recur->invoice_id . " input is " . $input['invoice'];
      return FALSE;
    }

    $now = date('YmdHis');

    // fix dates that already exist
    $dates = array('create', 'start', 'end', 'cancel', 'modified');
    foreach ($dates as $date) {
      $name = "{$date}_date";
      if ($recur->$name) {
        $recur->$name = CRM_Utils_Date::isoToMysql($recur->$name);
      }
    }

    $sendNotification = FALSE;
    $subscriptionPaymentStatus = NULL;
    //List of Transaction Type
    /*
         recurring_payment_profile_created          RP Profile Created
         recurring_payment           RP Sucessful Payment
         recurring_payment_failed                               RP Failed Payment
         recurring_payment_profile_cancel           RP Profile Cancelled
         recurring_payment_expired         RP Profile Expired
         recurring_payment_skipped        RP Profile Skipped
         recurring_payment_outstanding_payment      RP Sucessful Outstanding Payment
         recurring_payment_outstanding_payment_failed          RP Failed Outstanding Payment
         recurring_payment_suspended        RP Profile Suspended
         recurring_payment_suspended_due_to_max_failed_payment  RP Profile Suspended due to Max Failed Payment
        */


    //set transaction type
    $txnType = $this->retrieve('txn_type', 'String');
    //Changes for paypal pro recurring payment

    switch ($txnType) {
      case 'recurring_payment_profile_created':
        $recur->create_date = $now;
        $recur->contribution_status_id = 2;
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
          $recur->modified_date = $now;
        }

        //contribution installment is completed
        if ($this->retrieve('profile_status', 'String') == 'Expired') {
          $recur->contribution_status_id = 1;
          $recur->end_date = $now;
          $sendNotification = TRUE;
          $subscriptionPaymentStatus = CRM_Core_Payment::RECURRING_PAYMENT_END;
        }

        // make sure the contribution status is not done
        // since order of ipn's is unknown
        if ($recur->contribution_status_id != 1) {
          $recur->contribution_status_id = 5;
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

    if (!$first) {
      //check if this contribution transaction is already processed
      //if not create a contribution and then get it processed
      $contribution = new CRM_Contribute_BAO_Contribution();
      $contribution->trxn_id = $input['trxn_id'];
      if ($contribution->trxn_id && $contribution->find()) {
        CRM_Core_Error::debug_log_message("returning since contribution has already been handled");
        echo "Success: Contribution has already been handled<p>";
        return TRUE;
      }

      $contribution->contact_id = $recur->contact_id;
      $contribution->financial_type_id  = $objects['contributionType']->id;
      $contribution->contribution_page_id = $ids['contributionPage'];
      $contribution->contribution_recur_id = $ids['contributionRecur'];
      $contribution->currency = $objects['contribution']->currency;
      $contribution->payment_instrument_id = $objects['contribution']->payment_instrument_id;
      $contribution->amount_level = $objects['contribution']->amount_level;
      $contribution->honor_contact_id = $objects['contribution']->honor_contact_id;
      $contribution->honor_type_id = $objects['contribution']->honor_type_id;
      $contribution->campaign_id = $objects['contribution']->campaign_id;
      $objects['contribution'] = &$contribution;
    }
    // CRM-13737 - am not aware of any reason why payment_date would not be set - this if is a belt & braces
    $objects['contribution']->receive_date = !empty($input['payment_date']) ? date('YmdHis', strtotime($input['payment_date'])): $now;

    $this->single($input, $ids, $objects,
      TRUE, $first
    );
  }

  function single(&$input, &$ids, &$objects, $recur = FALSE, $first = FALSE) {
    $contribution = &$objects['contribution'];

    // make sure the invoice is valid and matches what we have in the contribution record
    if ((!$recur) || ($recur && $first)) {
      if ($contribution->invoice_id != $input['invoice']) {
        CRM_Core_Error::debug_log_message("Invoice values dont match between database and IPN request");
        echo "Failure: Invoice values dont match between database and IPN request<p>contribution is" . $contribution->invoice_id . " and input is " . $input['invoice'];
        return FALSE;
      }
    }
    else {
      $contribution->invoice_id = md5(uniqid(rand(), TRUE));
    }

    if (!$recur) {
      if ($contribution->total_amount != $input['amount']) {
        CRM_Core_Error::debug_log_message("Amount values dont match between database and IPN request");
        echo "Failure: Amount values dont match between database and IPN request<p>";
        return FALSE;
      }
    }
    else {
      $contribution->total_amount = $input['amount'];
    }

    $transaction = new CRM_Core_Transaction();

    $participant = &$objects['participant'];
    $membership = &$objects['membership'];

    $status = $input['paymentStatus'];
    if ($status == 'Denied' || $status == 'Failed' || $status == 'Voided') {
      return $this->failed($objects, $transaction);
    }
    elseif ($status == 'Pending') {
      return $this->pending($objects, $transaction);
    }
    elseif ($status == 'Refunded' || $status == 'Reversed') {
      return $this->cancelled($objects, $transaction);
    }
    elseif ($status != 'Completed') {
      return $this->unhandled($objects, $transaction);
    }

    // check if contribution is already completed, if so we ignore this ipn
    if ($contribution->contribution_status_id == 1) {
      $transaction->commit();
      CRM_Core_Error::debug_log_message("returning since contribution has already been handled");
      echo "Success: Contribution has already been handled<p>";
      return TRUE;
    }

    $this->completeTransaction($input, $ids, $objects, $transaction, $recur);
  }

  /**
   * This is the main function to call. It should be sufficient to instantiate the class
   * (with the input parameters) & call this & all will be done
   *
   * @todo the references to POST throughout this class need to be removed
   * @return void|boolean|Ambigous <void, boolean>
   */
  function main() {
    CRM_Core_Error::debug_var('GET', $_GET, TRUE, TRUE);
    CRM_Core_Error::debug_var('POST', $_POST, TRUE, TRUE);
    if($this->_isPaymentExpress) {
      $this->handlePaymentExpress();
      return;
    }
    $objects = $ids = $input = array();
    $this->_component  = $input['component'] = self::getValue('m');

    // get the contribution and contact ids from the GET params
    $ids['contact'] = self::getValue('c', TRUE);
    $ids['contribution'] = self::getValue('b', TRUE);

    $this->getInput($input, $ids);

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
      $sqlParams = array(1 => array($ids['contribution'], 'Integer'),
        2 => array($ids['contributionRecur'], 'Integer'),
      );
      if ($membershipId = CRM_Core_DAO::singleValueQuery($sql, $sqlParams)) {
        $ids['membership'] = $membershipId;
      }
    }

    $paymentProcessorID = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_PaymentProcessorType',
      'PayPal', 'id', 'name'
    );

    if (!$this->validateData($input, $ids, $objects, TRUE, $paymentProcessorID)) {
      return FALSE;
    }

    self::$_paymentProcessor = &$objects['paymentProcessor'];
    //?? how on earth would we not have component be one of these?
    // they are the only valid settings & this IPN file can't even be called without one of them
    // grepping for this class doesn't find other paths to call this class
    if ($this->_component == 'contribute' || $this->_component == 'event') {
      if ($ids['contributionRecur']) {
        // check if first contribution is completed, else complete first contribution
        $first = TRUE;
        if ($objects['contribution']->contribution_status_id == 1) {
          $first = FALSE;
        }
        return $this->recur($input, $ids, $objects, $first);
      }
      else {
        return $this->single($input, $ids, $objects, FALSE, FALSE);
      }
    }
    else {
      return $this->single($input, $ids, $objects, FALSE, FALSE);
    }
  }

  function getInput(&$input, &$ids) {

    if (!$this->getBillingID($ids)) {
      return FALSE;
    }

    $input['txnType'] = self::retrieve('txn_type', 'String', 'POST', FALSE);
    $input['paymentStatus'] = self::retrieve('payment_status', 'String', 'POST', FALSE);
    $input['invoice'] = self::getValue('i', TRUE);

    $input['amount'] = self::retrieve('mc_gross', 'Money', 'POST', FALSE);
    $input['reasonCode'] = self::retrieve('ReasonCode', 'String', 'POST', FALSE);

    $billingID = $ids['billing'];
    $lookup = array(
      "first_name" => 'first_name',
      "last_name" => 'last_name',
      "street_address-{$billingID}" => 'address_street',
      "city-{$billingID}" => 'address_city',
      "state-{$billingID}" => 'address_state',
      "postal_code-{$billingID}" => 'address_zip',
      "country-{$billingID}" => 'address_country_code',
    );
    foreach ($lookup as $name => $paypalName) {
      $value = self::retrieve($paypalName, 'String', 'POST', FALSE);
      $input[$name] = $value ? $value : NULL;
    }

    $input['is_test']    = self::retrieve('test_ipn', 'Integer', 'POST', FALSE);
    $input['fee_amount'] = self::retrieve('mc_fee', 'Money', 'POST', FALSE);
    $input['net_amount'] = self::retrieve('settle_amount', 'Money', 'POST', FALSE);
    $input['trxn_id']    = self::retrieve('txn_id', 'String', 'POST', FALSE);
    $input['payment_date'] = self::retrieve('payment_date', 'String', 'POST', FALSE);
  }

  /**
   * Handle payment express IPNs
   * For one off IPNS no actual response is required
   * Recurring is more difficult as we have limited confirmation material
   */
  function handlePaymentExpress() {
    throw new CRM_Core_Exception('Payment Express IPNS not currently handled');
  }
}

