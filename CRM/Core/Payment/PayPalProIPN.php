<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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
  function __construct() {
    parent::__construct();
  }

  function getValue($name, $abort = TRUE) {

    if (!empty($_POST)) {
      $rpInvoiceArray = array();
      $value          = NULL;
      $rpInvoiceArray = explode('&', $_POST['rp_invoice_id']);
      foreach ($rpInvoiceArray as $rpInvoiceValue) {
        $rpValueArray = explode('=', $rpInvoiceValue);
        if ($rpValueArray[0] == $name) {
          $value = $rpValueArray[1];
        }
      }

      if ($value == NULL && $abort) {
        echo "Failure: Missing Parameter $name<p>";
        exit();
      }
      else {
        return $value;
      }
    }
    else {
      return NULL;
    }
  }

  static function retrieve($name, $type, $location = 'POST', $abort = TRUE) {
    static $store = NULL;
    $value = CRM_Utils_Request::retrieve($name, $type, $store,
      FALSE, NULL, $location
    );
    if ($abort && $value === NULL) {
      CRM_Core_Error::debug_log_message("Could not find an entry for $name in $location");
      echo "Failure: Missing Parameter<p>";
      exit();
    }
    return $value;
  }

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
    $txnType = $_POST['txn_type'];
    //Changes for paypal pro recurring payment

    switch ($txnType) {
      case 'recurring_payment_profile_created':
        $recur->create_date = $now;
        $recur->contribution_status_id = 2;
        $recur->processor_id = $_POST['recurring_payment_id'];
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
        if ($_POST['profile_status'] == 'Expired') {
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

      $contribution->contact_id = $ids['contact'];
      $contribution->financial_type_id  = $objects['contributionType']->id;
      $contribution->contribution_page_id = $ids['contributionPage'];
      $contribution->contribution_recur_id = $ids['contributionRecur'];
      $contribution->receive_date = $now;
      $contribution->currency = $objects['contribution']->currency;
      $contribution->payment_instrument_id = $objects['contribution']->payment_instrument_id;
      $contribution->amount_level = $objects['contribution']->amount_level;

      $objects['contribution'] = &$contribution;
    }

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

    // fix for CRM-2842
    //  if ( ! $this->createContact( $input, $ids, $objects ) ) {
    //       return false;
    //  }

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

  function main($component = 'contribute') {
    CRM_Core_Error::debug_var('GET', $_GET, TRUE, TRUE);
    CRM_Core_Error::debug_var('POST', $_POST, TRUE, TRUE);


    $objects = $ids = $input = array();
    $input['component'] = $component;

    // get the contribution and contact ids from the GET params
    $ids['contact'] = self::getValue('c', TRUE);
    $ids['contribution'] = self::getValue('b', TRUE);

    $this->getInput($input, $ids);

    if ($component == 'event') {
      $ids['event'] = self::getValue('e', TRUE);
      $ids['participant'] = self::getValue('p', TRUE);
      $ids['contributionRecur'] = self::getValue('r', FALSE);
    }
    else {
      // get the optional ids
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
    if ($component == 'contribute' || $component == 'event') {
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
  }
}

