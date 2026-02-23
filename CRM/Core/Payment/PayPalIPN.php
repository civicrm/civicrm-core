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
use Civi\Api4\ContributionRecur;
use Civi\Api4\Payment;
use Civi\Payment\System;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_Payment_PayPalIPN {

  /**
   * Input parameters from payment processor. Store these so that
   * the code does not need to keep retrieving from the http request
   * @var array
   */
  protected $_inputParameters = [];

  /***
   * Loaded contribution object.
   *
   * @var \CRM_Contribute_BAO_Contribution
   */
  private $contribution;

  /**
   * The ContributionRecur ID (NULL if not set)
   *
   * @var int|null
   */
  private ?int $contributionRecurID = NULL;

  /**
   * The Contact ID associated with the IPN/Payment
   *
   * @var int|null
   */
  private ?int $contactID = NULL;

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
      array_merge($inputData, json_decode($inputData['custom'], TRUE) ?? []) :
      $inputData;

    if (!is_array($params)) {
      throw new CRM_Core_Exception('Invalid input parameters');
    }
    $this->_inputParameters = $params;
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
    $value = CRM_Utils_Type::validate($this->_inputParameters[$name] ?? NULL, $type, FALSE);
    if ($abort && $value === NULL) {
      throw new CRM_Core_Exception("PayPalIPN: Could not find an entry for $name");
    }
    return $value;
  }

  /**
   * @param array $input
   *
   * @return void
   *
   * @throws \CRM_Core_Exception
   */
  public function recur(array $input): void {
    $contributionRecur = new CRM_Contribute_BAO_ContributionRecur();
    $contributionRecur->id = $this->getContributionRecurID();
    if (!$contributionRecur->find(TRUE)) {
      throw new CRM_Core_Exception('Could not find contribution contributionRecur record');
    }

    $templateContribution = CRM_Contribute_BAO_ContributionRecur::getTemplateContribution($this->getContributionRecurID());

    // check if first contribution is completed, else complete first contribution
    $first = TRUE;
    $completedStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
    if ($templateContribution['contribution_status_id'] === $completedStatusId) {
      $first = FALSE;
    }
    // make sure the invoice ids match
    // make sure the invoice is valid and matches what we have in the contribution record
    if ($contributionRecur->invoice_id != $input['invoice']) {
      Civi::log('paypal_standard')->debug('PayPalIPN: Invoice values dont match between database and IPN request (RecurID: ' . $contributionRecur->id . ').');
      throw new CRM_Core_Exception("Failure: Invoice values dont match between database and IPN request");
    }

    $now = date('YmdHis');

    // set transaction type
    $recurStatuses = array_column(\Civi::entity('ContributionRecur')->getOptions('contribution_status_id'), 'id', 'name');
    switch ($this->getTrxnType()) {
      case 'subscr_signup':
        $contributionRecur->create_date = $now;
        // sometimes subscr_signup response come after the subscr_payment and set to pending mode.

        $statusID = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur',
          $contributionRecur->id, 'contribution_status_id'
        );
        if ($statusID != $recurStatuses['In Progress']) {
          $contributionRecur->contribution_status_id = $recurStatuses['Pending'];
        }
        $contributionRecur->processor_id = $this->retrieve('subscr_id', 'String');
        $contributionRecur->trxn_id = $contributionRecur->processor_id;
        $contributionRecur->save();
        //send recurring Notification email for user
        CRM_Contribute_BAO_ContributionPage::recurringNotify(
          $this->getContributionID(),
          CRM_Core_Payment::RECURRING_PAYMENT_START,
          $contributionRecur
        );
        return;

      case 'subscr_eot':
        if ($contributionRecur->contribution_status_id != $recurStatuses['Cancelled']) {
          $contributionRecur->contribution_status_id = $recurStatuses['Completed'];
        }
        $contributionRecur->end_date = $now;
        $contributionRecur->save();
        //send recurring Notification email for user
        CRM_Contribute_BAO_ContributionPage::recurringNotify(
          $this->getContributionID(),
          CRM_Core_Payment::RECURRING_PAYMENT_END,
          $contributionRecur
        );
        return;

      case 'subscr_cancel':
        $contributionRecur->contribution_status_id = $recurStatuses['Cancelled'];
        $contributionRecur->cancel_date = $now;
        $contributionRecur->save();
        return;

      case 'subscr_failed':
        $contributionRecur->contribution_status_id = $recurStatuses['Failed'];
        $contributionRecur->modified_date = $now;
        $contributionRecur->save();
        break;

      case 'subscr_modify':
        Civi::log('paypal_standard')->debug('PayPalIPN: We do not handle modifications to subscriptions right now  (RecurID: ' . $contributionRecur->id . ').');
        echo 'Failure: We do not handle modifications to subscriptions right now<p>';
        return;

    }

    if ($this->getTrxnType() !== 'subscr_payment') {
      return;
    }
    if ($input['paymentStatus'] !== 'Completed') {
      Civi::log('paypal_standard')->debug('PayPalIPN: Ignore all IPN payments that are not completed');
      echo 'Failure: Invalid parameters<p>';
      return;
    }

    $isEmailReceipt = ContributionRecur::get(FALSE)
      ->addSelect('is_email_receipt')
      ->addWhere('id', '=', $this->getContributionRecurID())
      ->execute()
      ->first()['is_email_receipt'];

    if ($first) {
      // Record the first subscription payment (complete the Pending Contribution)
      Payment::create(FALSE)
        ->setNotificationForCompleteOrder($isEmailReceipt)
        ->addValue('contribution_id', $templateContribution['id'])
        ->addValue('total_amount', $input['total_amount'])
        ->addValue('payment_processor_id', $input['payment_processor_id'])
        ->addValue('trxn_date', date('YmdHis', strtotime($input['receive_date'])))
        ->addValue('trxn_id', $input['trxn_id'])
        ->execute();
    }
    else {
      // check if this contribution transaction is already processed
      // if not create a contribution and then get it processed
      $contribution = new CRM_Contribute_BAO_Contribution();
      $contribution->trxn_id = $input['trxn_id'];
      if ($contribution->trxn_id && $contribution->find()) {
        Civi::log('paypal_standard')->debug('PayPalIPN: Returning since contribution has already been handled (trxn_id: ' . $contribution->trxn_id . ')');
        echo 'Success: Contribution has already been handled<p>';
        return;
      }

      // Consider accepting 'Failed' like other processors.
      $input['original_contribution_id'] = $contribution->id;
      $input['contribution_recur_id'] = $contributionRecur->id;

      $newContribution = civicrm_api3('Contribution', 'repeattransaction', $input);

      Payment::create(FALSE)
        ->setNotificationForCompleteOrder($isEmailReceipt)
        ->addValue('contribution_id', $newContribution['id'])
        ->addValue('total_amount', $input['total_amount'])
        ->addValue('payment_processor_id', $input['payment_processor_id'])
        ->addValue('trxn_date', date('YmdHis', strtotime($input['receive_date'])))
        ->addValue('trxn_id', $input['trxn_id'])
        ->execute();
    }
  }

  /**
   * @param array $input
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function single(array $input): void {
    $contribution = $this->getContribution();
    // make sure the invoice is valid and matches what we have in the contribution record
    if ($contribution->invoice_id != $input['invoice']) {
      Civi::log('paypal_standard')->debug('PayPalIPN: Invoice values dont match between database and IPN request. (ID: ' . $contribution->id . ').');
      echo "Failure: Invoice values dont match between database and IPN request<p>";
      return;
    }

    if ($contribution->total_amount != $input['total_amount']) {
      Civi::log('paypal_standard')->debug('PayPalIPN: Amount values dont match between database and IPN request. (ID: ' . $contribution->id . ').');
      echo "Failure: Amount values dont match between database and IPN request<p>";
      return;
    }

    // check if contribution is already completed, if so we ignore this ipn
    $completedStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
    if ($contribution->contribution_status_id == $completedStatusId) {
      Civi::log('paypal_standard')->debug('PayPalIPN: Returning since contribution has already been handled. (ID: ' . $contribution->id . ').');
      echo 'Success: Contribution has already been handled<p>';
      return;
    }

    Payment::create(FALSE)
      ->addValue('contribution_id', $contribution->id)
      ->addValue('total_amount', $input['total_amount'])
      ->addValue('payment_processor_id', $input['payment_processor_id'])
      ->addValue('trxn_date', date('YmdHis', strtotime($input['receive_date'])))
      ->addValue('trxn_id', $input['trxn_id'])
      ->execute();
  }

  /**
   * Main function.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function main(): void {
    try {
      $input = [];
      $component = $this->retrieve('module', 'String');
      $input['component'] = $component;
      $this->getInput($input);

      $paymentProcessorID = $this->getPayPalPaymentProcessorID($input, $this->getContributionRecurID());
      $paymentProcessor = System::singleton()->getById($paymentProcessorID);

      Civi::log('paypal_standard')->debug('PayPalIPN: Received (ContactID: ' . $this->getContactID() . '; trxn_id: ' . $input['trxn_id'] . ').');

      $input['payment_processor_id'] = $paymentProcessorID;

      if (!$paymentProcessor->verifyIPN()) {
        Civi::log('paypal_standard')->warning('PayPalIPN: Verification failed; input {input}', ['input' => $input]);
        return;
      }

      if ($this->getContributionRecurID()) {
        $this->recur($input);
        return;
      }

      // This will throw CRM_Core_Exception if `contributionID` is not in PayPal IPN params
      // That should be ok for a single (non-recur) payment since we don't have the problem
      //   of historical subscriptions created on (non-CiviCRM) system.
      $contributionID = $this->getContributionID();

      $status = $input['paymentStatus'];
      if ($status === 'Denied' || $status === 'Failed' || $status === 'Voided') {
        Contribution::update(FALSE)->setValues([
          'cancel_date' => 'now',
          'contribution_status_id:name' => 'Failed',
        ])->addWhere('id', '=', $contributionID)->execute();
        Civi::log('paypal_standard')->debug("Setting contribution status to Failed");
        return;
      }
      if ($status === 'Pending') {
        Civi::log('paypal_standard')->debug('Returning since contribution status is Pending');
        return;
      }
      if ($status === 'Refunded' || $status === 'Reversed') {
        Contribution::update(FALSE)->setValues([
          'cancel_date' => 'now',
          'contribution_status_id:name' => 'Cancelled',
        ])->addWhere('id', '=', $contributionID)->execute();
        Civi::log('paypal_standard')->debug("Setting contribution status to Cancelled");
        return;
      }
      if ($status !== 'Completed') {
        Civi::log('paypal_standard')->debug('Returning since contribution status is not handled');
        return;
      }
      $this->single($input);
    }
    catch (CRM_Core_Exception $e) {
      Civi::log('paypal_standard')->debug($e->getMessage() . ' input {input}', ['input' => $input]);
      echo 'Invalid or missing data';
    }
  }

  /**
   * @param array $input
   *
   * @throws \CRM_Core_Exception
   */
  public function getInput(&$input) {
    $billingID = CRM_Core_BAO_LocationType::getBilling();
    $input['paymentStatus'] = $this->retrieve('payment_status', 'String', FALSE);
    $input['invoice'] = $this->retrieve('invoice', 'String', TRUE);
    $input['total_amount'] = $this->retrieve('mc_gross', 'Money', FALSE);
    $input['reasonCode'] = $this->retrieve('ReasonCode', 'String', FALSE);

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
      $value = $this->retrieve($paypalName, 'String', FALSE);
      $input[$name] = $value ?: NULL;
    }

    $input['is_test'] = $this->retrieve('test_ipn', 'Integer', FALSE);
    $input['fee_amount'] = $this->retrieve('mc_fee', 'Money', FALSE);
    $input['net_amount'] = $this->retrieve('settle_amount', 'Money', FALSE);
    $input['trxn_id'] = $this->retrieve('txn_id', 'String', FALSE);

    $paymentDate = $this->retrieve('payment_date', 'String', FALSE);
    if (!empty($paymentDate)) {
      $receiveDateTime = new DateTime($paymentDate);
      /**
       * The `payment_date` that Paypal sends back is in their timezone. Example return: 08:23:05 Jan 11, 2019 PST
       * Subsequently, we need to account for that, otherwise the recieve time will be incorrect for the local system
       */
      $input['receive_date'] = CRM_Utils_Date::convertDateToLocalTime($receiveDateTime);
    }
  }

  /**
   * Get PaymentProcessorID for PayPal
   *
   * @param array $input
   * @param int|null $contributionRecurID
   *
   * @return int
   * @throws \CRM_Core_Exception
   */
  public function getPayPalPaymentProcessorID(array $input, ?int $contributionRecurID): int {
    // First we try and retrieve from POST params
    $paymentProcessorID = $this->retrieve('processor_id', 'Integer', FALSE);
    if (!empty($paymentProcessorID)) {
      return $paymentProcessorID;
    }

    // Then we try and get it from recurring contribution ID
    if ($contributionRecurID) {
      $contributionRecur = ContributionRecur::get(FALSE)
        ->addSelect('payment_processor_id')
        ->addWhere('id', '=', $contributionRecurID)
        ->execute()
        ->first();
      if (!empty($contributionRecur['payment_processor_id'])) {
        return $contributionRecur['payment_processor_id'];
      }
    }

    // This is an unreliable method as there could be more than one instance.
    // Recommended approach is to use the civicrm/payment/ipn/xx url where xx is the payment
    // processor id & the handleNotification function (which should call the completetransaction api & by-pass this
    // entirely). The only thing the IPN class should really do is extract data from the request, validate it
    // & call completetransaction or call fail? (which may not exist yet).
    Civi::log('paypal_standard')->warning('Unreliable method used to get payment_processor_id for PayPal IPN - this will cause problems if you have more than one instance');
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
      throw new CRM_Core_Exception('PayPalIPN: Could not get Payment Processor ID');
    }
    return (int) $paymentProcessorID;
  }

  /**
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  protected function getTrxnType() {
    return $this->retrieve('txn_type', 'String');
  }

  /**
   * Get the recurring contribution ID.
   *
   * @return int|null
   *
   * @throws \CRM_Core_Exception
   */
  protected function getContributionRecurID(): ?int {
    if (!$this->contributionRecurID) {
      $this->contributionRecurID = $this->retrieve('contributionRecurID', 'Integer', FALSE);
      if (!$this->contributionRecurID && !empty($this->retrieve('subscr_id', 'String', FALSE))) {
        // Match using Recurring Payment ID / Reference Trxn ID if available
        $contributionRecur = ContributionRecur::get(FALSE)
          ->addSelect('id', 'contact_id')
          ->addWhere('processor_id', '=', $this->retrieve('subscr_id', 'String', FALSE))
          ->execute()
          ->first();
        $this->contributionRecurID = $contributionRecur['id'] ?? NULL;
        if (!$this->getContactID()) {
          // Set Contact ID using the data from the contributionRecur
          $this->contactID = $contributionRecur['contact_id'];
        }
      }
    }
    return $this->contributionRecurID;
  }

  /**
   * Get Contribution ID.
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
   */
  protected function getContributionID(): int {
    return (int) $this->retrieve('contributionID', 'Integer', TRUE);
  }

  /**
   * Get contact id from parameters.
   *
   * @return int|null
   *
   * @throws \CRM_Core_Exception
   */
  protected function getContactID(): ?int {
    if (!$this->contactID) {
      $this->contactID = $this->retrieve('contactID', 'Integer', FALSE);
    }
    return $this->contactID;
  }

  /**
   * Get the contribution object.
   *
   * @return \CRM_Contribute_BAO_Contribution
   * @throws \CRM_Core_Exception
   */
  protected function getContribution(): CRM_Contribute_BAO_Contribution {
    if (!$this->contribution) {
      $this->contribution = new CRM_Contribute_BAO_Contribution();
      $this->contribution->id = $this->getContributionID();
      if (!$this->contribution->find(TRUE)) {
        throw new CRM_Core_Exception('Failure: Could not find contribution record for ' . (int) $this->contribution->id, NULL, ['context' => "Could not find contribution record: {$this->contribution->id} in IPN request: "]);
      }
      if ((int) $this->contribution->contact_id !== $this->getContactID()) {
        Civi::log('paypal_standard')->debug("Contact ID in IPN not found but contact_id found in contribution.");
      }
    }
    return $this->contribution;
  }

}
