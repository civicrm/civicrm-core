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
    // CRM-19676
    $params = (!empty($inputData['custom'])) ?
      array_merge($inputData, json_decode($inputData['custom'], TRUE)) :
      $inputData;
    $this->setInputParameters($params);
    parent::__construct();
  }

  /**
   * @param string $name
   * @param string $type
   * @param bool $abort
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  public function retrieve($name, $type, $abort = TRUE) {
    $value = CRM_Utils_Type::validate(CRM_Utils_Array::value($name, $this->_inputParameters), $type, FALSE);
    if ($abort && $value === NULL) {
      Civi::log()->debug("PayPalIPN: Could not find an entry for $name");
      echo "Failure: Missing Parameter<p>" . CRM_Utils_Type::escape($name, 'String');
      throw new CRM_Core_Exception("PayPalIPN: Could not find an entry for $name");
    }
    return $value;
  }

  /**
   * @param array $input
   * @param array $ids
   * @param array $objects
   * @param bool $first
   *
   * @return void
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function recur(&$input, &$ids, &$objects, $first) {
    if (!isset($input['txnType'])) {
      Civi::log()->debug('PayPalIPN: Could not find txn_type in input request');
      echo "Failure: Invalid parameters<p>";
      return;
    }

    if ($input['txnType'] == 'subscr_payment' &&
      $input['paymentStatus'] != 'Completed'
    ) {
      Civi::log()->debug('PayPalIPN: Ignore all IPN payments that are not completed');
      echo "Failure: Invalid parameters<p>";
      return;
    }

    $recur = &$objects['contributionRecur'];

    // make sure the invoice ids match
    // make sure the invoice is valid and matches what we have in the contribution record
    if ($recur->invoice_id != $input['invoice']) {
      Civi::log()->debug('PayPalIPN: Invoice values dont match between database and IPN request (RecurID: ' . $recur->id . ').');
      echo "Failure: Invoice values dont match between database and IPN request<p>";
      return;
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
    // set transaction type
    $txnType = $this->retrieve('txn_type', 'String');
    $contributionStatuses = array_flip(CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'validate'));
    switch ($txnType) {
      case 'subscr_signup':
        $recur->create_date = $now;
        // sometimes subscr_signup response come after the subscr_payment and set to pending mode.

        $statusID = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur',
          $recur->id, 'contribution_status_id'
        );
        if ($statusID != $contributionStatuses['In Progress']) {
          $recur->contribution_status_id = $contributionStatuses['Pending'];
        }
        $recur->processor_id = $this->retrieve('subscr_id', 'String');
        $recur->trxn_id = $recur->processor_id;
        $sendNotification = TRUE;
        $subscriptionPaymentStatus = CRM_Core_Payment::RECURRING_PAYMENT_START;
        break;

      case 'subscr_eot':
        if ($recur->contribution_status_id != $contributionStatuses['Cancelled']) {
          $recur->contribution_status_id = $contributionStatuses['Completed'];
        }
        $recur->end_date = $now;
        $sendNotification = TRUE;
        $subscriptionPaymentStatus = CRM_Core_Payment::RECURRING_PAYMENT_END;
        break;

      case 'subscr_cancel':
        $recur->contribution_status_id = $contributionStatuses['Cancelled'];
        $recur->cancel_date = $now;
        break;

      case 'subscr_failed':
        $recur->contribution_status_id = $contributionStatuses['Failed'];
        $recur->modified_date = $now;
        break;

      case 'subscr_modify':
        Civi::log()->debug('PayPalIPN: We do not handle modifications to subscriptions right now  (RecurID: ' . $recur->id . ').');
        echo "Failure: We do not handle modifications to subscriptions right now<p>";
        return;

      case 'subscr_payment':
        if ($first) {
          $recur->start_date = $now;
        }
        else {
          $recur->modified_date = $now;
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

    if ($txnType != 'subscr_payment') {
      return;
    }

    if (!$first) {
      // check if this contribution transaction is already processed
      // if not create a contribution and then get it processed
      $contribution = new CRM_Contribute_BAO_Contribution();
      $contribution->trxn_id = $input['trxn_id'];
      if ($contribution->trxn_id && $contribution->find()) {
        Civi::log()->debug('PayPalIPN: Returning since contribution has already been handled (trxn_id: ' . $contribution->trxn_id . ')');
        echo "Success: Contribution has already been handled<p>";
        return;
      }

      if ($input['paymentStatus'] != 'Completed') {
        throw new CRM_Core_Exception("Ignore all IPN payments that are not completed");
      }

      // In future moving to create pending & then complete, but this OK for now.
      // Also consider accepting 'Failed' like other processors.
      $input['contribution_status_id'] = $contributionStatuses['Completed'];
      $input['original_contribution_id'] = $ids['contribution'];
      $input['contribution_recur_id'] = $ids['contributionRecur'];

      civicrm_api3('Contribution', 'repeattransaction', $input);
      return;
    }

    $this->single($input, $ids, $objects,
      TRUE, $first
    );
  }

  /**
   * @param array $input
   * @param array $ids
   * @param array $objects
   * @param bool $recur
   * @param bool $first
   *
   * @return void
   */
  public function single(&$input, &$ids, &$objects, $recur = FALSE, $first = FALSE) {
    $contribution = &$objects['contribution'];

    // make sure the invoice is valid and matches what we have in the contribution record
    if ((!$recur) || ($recur && $first)) {
      if ($contribution->invoice_id != $input['invoice']) {
        Civi::log()->debug('PayPalIPN: Invoice values dont match between database and IPN request. (ID: ' . $contribution->id . ').');
        echo "Failure: Invoice values dont match between database and IPN request<p>";
        return;
      }
    }
    else {
      $contribution->invoice_id = md5(uniqid(rand(), TRUE));
    }

    if (!$recur) {
      if ($contribution->total_amount != $input['amount']) {
        Civi::log()->debug('PayPalIPN: Amount values dont match between database and IPN request. (ID: ' . $contribution->id . ').');
        echo "Failure: Amount values dont match between database and IPN request<p>";
        return;
      }
    }
    else {
      $contribution->total_amount = $input['amount'];
    }

    $transaction = new CRM_Core_Transaction();

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
    $completedStatusId = CRM_Core_Pseudoconstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
    if ($contribution->contribution_status_id == $completedStatusId) {
      $transaction->commit();
      Civi::log()->debug('PayPalIPN: Returning since contribution has already been handled. (ID: ' . $contribution->id . ').');
      echo "Success: Contribution has already been handled<p>";
      return;
    }

    $this->completeTransaction($input, $ids, $objects, $transaction, $recur);
  }

  /**
   * Main function.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
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

    $paymentProcessorID = self::getPayPalPaymentProcessorID($input, $ids);

    Civi::log()->debug('PayPalIPN: Received (ContactID: ' . $ids['contact'] . '; trxn_id: ' . $input['trxn_id'] . ').');

    if (!$this->validateData($input, $ids, $objects, TRUE, $paymentProcessorID)) {
      return;
    }

    self::$_paymentProcessor = &$objects['paymentProcessor'];
    if ($component == 'contribute') {
      if ($ids['contributionRecur']) {
        // check if first contribution is completed, else complete first contribution
        $first = TRUE;
        $completedStatusId = CRM_Core_Pseudoconstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
        if ($objects['contribution']->contribution_status_id == $completedStatusId) {
          $first = FALSE;
        }
        $this->recur($input, $ids, $objects, $first);
        return;
      }
    }
    $this->single($input, $ids, $objects, FALSE, FALSE);
  }

  /**
   * @param array $input
   * @param array $ids
   *
   * @throws \CRM_Core_Exception
   */
  public function getInput(&$input, &$ids) {
    if (!$this->getBillingID($ids)) {
      return;
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

    $paymentDate = $this->retrieve('payment_date', 'String', FALSE);
    if (!empty($paymentDate)) {
      $receiveDateTime = new DateTime($paymentDate);
      $input['receive_date'] = $receiveDateTime->format('YmdHis');
    }
  }


  /**
   * Gets PaymentProcessorID for PayPal
   *
   * @param array $input
   * @param array $ids
   *
   * @return int
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function getPayPalPaymentProcessorID($input, $ids) {
    // First we try and retrieve from POST params
    $paymentProcessorID = $this->retrieve('processor_id', 'Integer', FALSE);
    if (!empty($paymentProcessorID)) {
      return $paymentProcessorID;
    }

    // Then we try and get it from recurring contribution ID
    if (!empty($ids['contributionRecur'])) {
      $contributionRecur = civicrm_api3('ContributionRecur', 'getsingle', array(
        'id' => $ids['contributionRecur'],
        'return' => ['payment_processor_id'],
      ));
      if (!empty($contributionRecur['payment_processor_id'])) {
        return $contributionRecur['payment_processor_id'];
      }
    }

    // This is an unreliable method as there could be more than one instance.
    // Recommended approach is to use the civicrm/payment/ipn/xx url where xx is the payment
    // processor id & the handleNotification function (which should call the completetransaction api & by-pass this
    // entirely). The only thing the IPN class should really do is extract data from the request, validate it
    // & call completetransaction or call fail? (which may not exist yet).

    Civi::log()->warning('Unreliable method used to get payment_processor_id for PayPal IPN - this will cause problems if you have more than one instance');
    // Then we try and retrieve based on business email ID
    $paymentProcessorTypeID = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_PaymentProcessorType', 'PayPal_Standard', 'id', 'name');
    $processorParams = [
      'user_name' => $this->retrieve('business', 'String', FALSE),
      'payment_processor_type_id' => $paymentProcessorTypeID,
      'is_test' => empty($input['is_test']) ? 0 : 1,
      'options' => ['limit' => 1],
      'return' => ['id'],
    ];
    $paymentProcessorID = civicrm_api3('PaymentProcessor', 'getvalue', $processorParams);
    if (empty($paymentProcessorID)) {
      Throw new CRM_Core_Exception('PayPalIPN: Could not get Payment Processor ID');
    }
    return $paymentProcessorID;
  }

}
