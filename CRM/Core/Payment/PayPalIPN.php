<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */
class CRM_Core_Payment_PayPalIPN extends CRM_Core_Payment_BaseIPN {

  static $_paymentProcessor = NULL;

  /**
   * Input parameters from payment processor. Store these so that
   * the code does not need to keep retrieving from the http request
   * @var array
   */
  protected $_inputParameters = array();

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
    parent::__construct();
  }

  /**
   * @param string $name
   * @param $type
   * @param bool $abort
   *
   * @return mixed
   */
  public function retrieve($name, $type, $abort = TRUE) {
    static $store = NULL;
    $value = CRM_Utils_Type::validate(
      CRM_Utils_Array::value($name, $this->_inputParameters),
      $type,
      FALSE
    );
    if ($abort && $value === NULL) {
      CRM_Core_Error::debug_log_message("Could not find an entry for $name");
      echo "Failure: Missing Parameter<p>" . CRM_Utils_Type::escape($name, 'String');
      exit();
    }
    return $value;
  }

  /**
   * @param $input
   * @param $ids
   * @param $objects
   * @param $first
   *
   * @return bool
   */
  public function recur(&$input, &$ids, &$objects, $first) {
    if (!isset($input['txnType'])) {
      CRM_Core_Error::debug_log_message("Could not find txn_type in input request");
      echo "Failure: Invalid parameters<p>";
      return FALSE;
    }

    if ($input['txnType'] == 'subscr_payment' &&
      $input['paymentStatus'] != 'Completed'
    ) {
      CRM_Core_Error::debug_log_message("Ignore all IPN payments that are not completed");
      echo "Failure: Invalid parameters<p>";
      return FALSE;
    }

    $recur = &$objects['contributionRecur'];

    // make sure the invoice ids match
    // make sure the invoice is valid and matches what we have in the contribution record
    if ($recur->invoice_id != $input['invoice']) {
      CRM_Core_Error::debug_log_message("Invoice values dont match between database and IPN request");
      echo "Failure: Invoice values dont match between database and IPN request<p>";
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
    //set transaction type
    $txnType = $this->retrieve('txn_type', 'String');
    switch ($txnType) {
      case 'subscr_signup':
        $recur->create_date = $now;
        //some times subscr_signup response come after the
        //subscr_payment and set to pending mode.
        $statusID = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur',
          $recur->id, 'contribution_status_id'
        );
        if ($statusID != 5) {
          $recur->contribution_status_id = 2;
        }
        $recur->processor_id = $this->retrieve('subscr_id', 'String');
        $recur->trxn_id = $recur->processor_id;
        $sendNotification = TRUE;
        $subscriptionPaymentStatus = CRM_Core_Payment::RECURRING_PAYMENT_START;
        break;

      case 'subscr_eot':
        if ($recur->contribution_status_id != 3) {
          $recur->contribution_status_id = 1;
        }
        $recur->end_date = $now;
        $sendNotification = TRUE;
        $subscriptionPaymentStatus = CRM_Core_Payment::RECURRING_PAYMENT_END;
        break;

      case 'subscr_cancel':
        $recur->contribution_status_id = 3;
        $recur->cancel_date = $now;
        break;

      case 'subscr_failed':
        $recur->contribution_status_id = 4;
        $recur->modified_date = $now;
        break;

      case 'subscr_modify':
        CRM_Core_Error::debug_log_message("We do not handle modifications to subscriptions right now");
        echo "Failure: We do not handle modifications to subscriptions right now<p>";
        return FALSE;

      case 'subscr_payment':
        if ($first) {
          $recur->start_date = $now;
        }
        else {
          $recur->modified_date = $now;
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

    if ($txnType != 'subscr_payment') {
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

      $contribution->contact_id = $ids['contact'];
      $contribution->financial_type_id = $objects['contributionType']->id;
      $contribution->contribution_page_id = $ids['contributionPage'];
      $contribution->contribution_recur_id = $ids['contributionRecur'];
      $contribution->receive_date = $now;
      $contribution->currency = $objects['contribution']->currency;
      $contribution->payment_instrument_id = $objects['contribution']->payment_instrument_id;
      $contribution->amount_level = $objects['contribution']->amount_level;
      $contribution->campaign_id = $objects['contribution']->campaign_id;

      $objects['contribution'] = &$contribution;
    }

    $this->single($input, $ids, $objects,
      TRUE, $first
    );
  }

  /**
   * @param $input
   * @param $ids
   * @param $objects
   * @param bool $recur
   * @param bool $first
   *
   * @return bool
   */
  public function single(
    &$input, &$ids, &$objects,
    $recur = FALSE,
    $first = FALSE
  ) {
    $contribution = &$objects['contribution'];

    // make sure the invoice is valid and matches what we have in the contribution record
    if ((!$recur) || ($recur && $first)) {
      if ($contribution->invoice_id != $input['invoice']) {
        CRM_Core_Error::debug_log_message("Invoice values dont match between database and IPN request");
        echo "Failure: Invoice values dont match between database and IPN request<p>";
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
   * Main function.
   *
   * @return bool
   */
  public function main() {

    $objects = $ids = $input = array();
    $component = $this->retrieve('module', 'String');
    $input['component'] = $component;

    $ids['contact'] = $this->retrieve('contactID', 'Integer', TRUE);
    $ids['contribution'] = $this->retrieve('contributionID', 'Integer', TRUE);

    $this->getInput($input, $ids);

    if ($component == 'event') {
      $ids['event'] = $this->retrieve('eventID', 'Integer', TRUE);
      $ids['participant'] = $this->retrieve('participantID', 'Integer', TRUE);
    }
    else {
      // get the optional ids
      $ids['membership'] = $this->retrieve('membershipID', 'Integer', FALSE);
      $ids['contributionRecur'] = $this->retrieve('contributionRecurID', 'Integer', FALSE);
      $ids['contributionPage'] = $this->retrieve('contributionPageID', 'Integer', FALSE);
      $ids['related_contact'] = $this->retrieve('relatedContactID', 'Integer', FALSE);
      $ids['onbehalf_dupe_alert'] = $this->retrieve('onBehalfDupeAlert', 'Integer', FALSE);
    }

    $paymentProcessorID = $this->retrieve('processor_id', 'Integer', FALSE);
    if (empty($paymentProcessorID)) {
      $processorParams = array(
        'user_name' => $this->retrieve('receiver_email', 'String', FALSE),
        'payment_processor_type_id' => CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_PaymentProcessorType', 'PayPal_Standard', 'id', 'name'),
        'is_test' => empty($input['is_test']) ? 0 : 1,
      );

      $processorInfo = array();
      if (!CRM_Financial_BAO_PaymentProcessor::retrieve($processorParams, $processorInfo)) {
        return FALSE;
      }
      $paymentProcessorID = $processorInfo['id'];
    }

    if (!$this->validateData($input, $ids, $objects, TRUE, $paymentProcessorID)) {
      return FALSE;
    }

    self::$_paymentProcessor = &$objects['paymentProcessor'];
    if ($component == 'contribute') {
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

  /**
   * @param $input
   * @param $ids
   *
   * @return bool
   */
  public function getInput(&$input, &$ids) {
    if (!$this->getBillingID($ids)) {
      return FALSE;
    }

    $input['txnType'] = $this->retrieve('txn_type', 'String', FALSE);
    $input['paymentStatus'] = $this->retrieve('payment_status', 'String', FALSE);
    $input['invoice'] = $this->retrieve('invoice', 'String', TRUE);
    $input['amount'] = $this->retrieve('mc_gross', 'Money', FALSE);
    $input['reasonCode'] = $this->retrieve('ReasonCode', 'String', FALSE);

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
      $value = $this->retrieve($paypalName, 'String', FALSE);
      $input[$name] = $value ? $value : NULL;
    }

    $input['is_test'] = $this->retrieve('test_ipn', 'Integer', FALSE);
    $input['fee_amount'] = $this->retrieve('mc_fee', 'Money', FALSE);
    $input['net_amount'] = $this->retrieve('settle_amount', 'Money', FALSE);
    $input['trxn_id'] = $this->retrieve('txn_id', 'String', FALSE);
  }

}
