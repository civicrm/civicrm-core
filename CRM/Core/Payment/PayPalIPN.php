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
  public function recur($input) {
    $contribution = $this->getContribution();
    $contributionRecur = new CRM_Contribute_BAO_ContributionRecur();
    $contributionRecur->id = $this->getContributionRecurID();
    if (!$contributionRecur->find(TRUE)) {
      throw new CRM_Core_Exception('Could not find contribution contributionRecur record');
    }
    // check if first contribution is completed, else complete first contribution
    $first = TRUE;
    $completedStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
    if ($contribution->contribution_status_id == $completedStatusId) {
      $first = FALSE;
    }
    // make sure the invoice ids match
    // make sure the invoice is valid and matches what we have in the contribution record
    if ($contributionRecur->invoice_id != $input['invoice']) {
      Civi::log()->debug('PayPalIPN: Invoice values dont match between database and IPN request (RecurID: ' . $contributionRecur->id . ').');
      throw new CRM_Core_Exception("Failure: Invoice values dont match between database and IPN request");
    }

    $now = date('YmdHis');

    // set transaction type
    $contributionStatuses = array_flip(CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'validate'));
    switch ($this->getTrxnType()) {
      case 'subscr_signup':
        $contributionRecur->create_date = $now;
        // sometimes subscr_signup response come after the subscr_payment and set to pending mode.

        $statusID = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur',
          $contributionRecur->id, 'contribution_status_id'
        );
        if ($statusID != $contributionStatuses['In Progress']) {
          $contributionRecur->contribution_status_id = $contributionStatuses['Pending'];
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
        if ($contributionRecur->contribution_status_id != $contributionStatuses['Cancelled']) {
          $contributionRecur->contribution_status_id = $contributionStatuses['Completed'];
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
        $contributionRecur->contribution_status_id = $contributionStatuses['Cancelled'];
        $contributionRecur->cancel_date = $now;
        $contributionRecur->save();
        return;

      case 'subscr_failed':
        $contributionRecur->contribution_status_id = $contributionStatuses['Failed'];
        $contributionRecur->modified_date = $now;
        $contributionRecur->save();
        break;

      case 'subscr_modify':
        Civi::log()->debug('PayPalIPN: We do not handle modifications to subscriptions right now  (RecurID: ' . $contributionRecur->id . ').');
        echo 'Failure: We do not handle modifications to subscriptions right now<p>';
        return;

    }

    if ($this->getTrxnType() !== 'subscr_payment') {
      return;
    }
    if ($input['paymentStatus'] !== 'Completed') {
      Civi::log()->debug('PayPalIPN: Ignore all IPN payments that are not completed');
      echo 'Failure: Invalid parameters<p>';
      return;
    }

    if (!$first) {
      // check if this contribution transaction is already processed
      // if not create a contribution and then get it processed
      $contribution = new CRM_Contribute_BAO_Contribution();
      $contribution->trxn_id = $input['trxn_id'];
      if ($contribution->trxn_id && $contribution->find()) {
        Civi::log()->debug('PayPalIPN: Returning since contribution has already been handled (trxn_id: ' . $contribution->trxn_id . ')');
        echo 'Success: Contribution has already been handled<p>';
        return;
      }

      if ($input['paymentStatus'] !== 'Completed') {
        throw new CRM_Core_Exception("Ignore all IPN payments that are not completed");
      }

      // In future moving to create pending & then complete, but this OK for now.
      // Also consider accepting 'Failed' like other processors.
      $input['contribution_status_id'] = $contributionStatuses['Completed'];
      $input['original_contribution_id'] = $contribution->id;
      $input['contribution_recur_id'] = $contributionRecur->id;

      civicrm_api3('Contribution', 'repeattransaction', $input);
      return;
    }

    $this->single($input, TRUE);
  }

  /**
   * @param array $input
   * @param bool $recur
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function single($input, $recur = FALSE) {
    $contribution = $this->getContribution();
    // make sure the invoice is valid and matches what we have in the contribution record
    if ($contribution->invoice_id != $input['invoice']) {
      Civi::log()->debug('PayPalIPN: Invoice values dont match between database and IPN request. (ID: ' . $contribution->id . ').');
      echo "Failure: Invoice values dont match between database and IPN request<p>";
      return;
    }

    if (!$recur) {
      if ($contribution->total_amount != $input['total_amount']) {
        Civi::log()->debug('PayPalIPN: Amount values dont match between database and IPN request. (ID: ' . $contribution->id . ').');
        echo "Failure: Amount values dont match between database and IPN request<p>";
        return;
      }
    }
    else {
      $contribution->total_amount = $input['total_amount'];
    }

    // check if contribution is already completed, if so we ignore this ipn
    $completedStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
    if ($contribution->contribution_status_id == $completedStatusId) {
      Civi::log()->debug('PayPalIPN: Returning since contribution has already been handled. (ID: ' . $contribution->id . ').');
      echo 'Success: Contribution has already been handled<p>';
      return;
    }

    CRM_Contribute_BAO_Contribution::completeOrder($input, $this->getContributionRecurID(), $contribution->id ?? NULL);
  }

  /**
   * Main function.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function main() {
    try {
      $input = [];
      $component = $this->retrieve('module', 'String');
      $input['component'] = $component;

      $contributionID = $this->getContributionID();
      $this->getInput($input);

      $paymentProcessorID = $this->getPayPalPaymentProcessorID($input, $this->getContributionRecurID());

      Civi::log()->debug('PayPalIPN: Received (ContactID: ' . $this->getContactID() . '; trxn_id: ' . $input['trxn_id'] . ').');

      $input['payment_processor_id'] = $paymentProcessorID;

      if ($this->getContributionRecurID()) {
        $this->recur($input);
        return;
      }

      $status = $input['paymentStatus'];
      if ($status === 'Denied' || $status === 'Failed' || $status === 'Voided') {
        Contribution::update(FALSE)->setValues([
          'cancel_date' => 'now',
          'contribution_status_id:name' => 'Failed',
        ])->addWhere('id', '=', $contributionID)->execute();
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
        ])->addWhere('id', '=', $contributionID)->execute();
        Civi::log()->debug("Setting contribution status to Cancelled");
        return;
      }
      if ($status !== 'Completed') {
        Civi::log()->debug('Returning since contribution status is not handled');
        return;
      }
      $this->single($input);
    }
    catch (CRM_Core_Exception $e) {
      Civi::log()->debug($e->getMessage() . ' input {input}', ['input' => $input]);
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
      $contributionRecur = civicrm_api3('ContributionRecur', 'getsingle', [
        'id' => $contributionRecurID,
        'return' => ['payment_processor_id'],
      ]);
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
    $id = $this->retrieve('contributionRecurID', 'Integer', FALSE);
    return $id ? (int) $id : NULL;
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
   * @return int
   *
   * @throws \CRM_Core_Exception
   */
  protected function getContactID(): int {
    return (int) $this->retrieve('contactID', 'Integer', TRUE);
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
        CRM_Core_Error::debug_log_message("Contact ID in IPN not found but contact_id found in contribution.");
      }
    }
    return $this->contribution;
  }

}
