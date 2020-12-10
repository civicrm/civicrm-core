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
class CRM_Core_Payment_PayPalIPN extends CRM_Core_Payment_BaseIPN {

  /**
   * Input parameters from payment processor. Store these so that
   * the code does not need to keep retrieving from the http request
   * @var array
   */
  protected $_inputParameters = [];

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
      throw new CRM_Core_Exception("PayPalIPN: Could not find an entry for $name");
    }
    return $value;
  }

  /**
   * @param array $input
   * @param array $ids
   * @param CRM_Contribute_BAO_ContributionRecur $recur
   * @param CRM_Contribute_BAO_Contribution $contribution
   * @param bool $first
   *
   * @return void
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function recur($input, $ids, $recur, $contribution, $first) {
    if (!isset($input['txnType'])) {
      Civi::log()->debug('PayPalIPN: Could not find txn_type in input request');
      echo "Failure: Invalid parameters<p>";
      return;
    }

    if ($input['txnType'] === 'subscr_payment' &&
      $input['paymentStatus'] !== 'Completed'
    ) {
      Civi::log()->debug('PayPalIPN: Ignore all IPN payments that are not completed');
      echo 'Failure: Invalid parameters<p>';
      return;
    }

    // make sure the invoice ids match
    // make sure the invoice is valid and matches what we have in the contribution record
    if ($recur->invoice_id != $input['invoice']) {
      Civi::log()->debug('PayPalIPN: Invoice values dont match between database and IPN request (RecurID: ' . $recur->id . ').');
      echo "Failure: Invoice values dont match between database and IPN request<p>";
      return;
    }

    $now = date('YmdHis');

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
        $subscriptionPaymentStatus = CRM_Core_Payment::RECURRING_PAYMENT_START;
        break;

      case 'subscr_eot':
        if ($recur->contribution_status_id != $contributionStatuses['Cancelled']) {
          $recur->contribution_status_id = $contributionStatuses['Completed'];
        }
        $recur->end_date = $now;
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

    if (in_array($this->retrieve('txn_type', 'String'), ['subscr_signup', 'subscr_eot'])) {
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

    $this->single($input, [
      'related_contact' => $ids['related_contact'] ?? NULL,
      'participant' => $ids['participant'] ?? NULL,
      'contributionRecur' => $recur->id,
    ], $contribution, TRUE);
  }

  /**
   * @param array $input
   * @param array $ids
   * @param \CRM_Contribute_BAO_Contribution $contribution
   * @param bool $recur
   *
   * @return void
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function single($input, $ids, $contribution, $recur = FALSE) {

    // make sure the invoice is valid and matches what we have in the contribution record
    if ($contribution->invoice_id != $input['invoice']) {
      Civi::log()->debug('PayPalIPN: Invoice values dont match between database and IPN request. (ID: ' . $contribution->id . ').');
      echo "Failure: Invoice values dont match between database and IPN request<p>";
      return;
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

    // check if contribution is already completed, if so we ignore this ipn
    $completedStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
    if ($contribution->contribution_status_id == $completedStatusId) {
      Civi::log()->debug('PayPalIPN: Returning since contribution has already been handled. (ID: ' . $contribution->id . ').');
      echo 'Success: Contribution has already been handled<p>';
      return;
    }

    CRM_Contribute_BAO_Contribution::completeOrder($input, $ids, $contribution);
  }

  /**
   * Main function.
   *
   * @throws \API_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function main() {
    try {
      $ids = $input = [];
      $component = $this->retrieve('module', 'String');
      $input['component'] = $component;

      $ids['contact'] = $this->retrieve('contactID', 'Integer', TRUE);
      $contributionID = $ids['contribution'] = $this->retrieve('contributionID', 'Integer', TRUE);
      $membershipID = $this->retrieve('membershipID', 'Integer', FALSE);
      $contributionRecurID = $this->retrieve('contributionRecurID', 'Integer', FALSE);

      $this->getInput($input);

      if ($component == 'event') {
        $ids['event'] = $this->retrieve('eventID', 'Integer', TRUE);
        $ids['participant'] = $this->retrieve('participantID', 'Integer', TRUE);
      }
      else {
        // get the optional ids
        $ids['membership'] = $membershipID;
        $ids['contributionRecur'] = $contributionRecurID;
        $ids['contributionPage'] = $this->retrieve('contributionPageID', 'Integer', FALSE);
        $ids['related_contact'] = $this->retrieve('relatedContactID', 'Integer', FALSE);
        $ids['onbehalf_dupe_alert'] = $this->retrieve('onBehalfDupeAlert', 'Integer', FALSE);
      }

      $paymentProcessorID = $this->getPayPalPaymentProcessorID($input, $ids);

      Civi::log()->debug('PayPalIPN: Received (ContactID: ' . $ids['contact'] . '; trxn_id: ' . $input['trxn_id'] . ').');

      // Debugging related to possible missing membership linkage
      if ($contributionRecurID && $this->retrieve('membershipID', 'Integer', FALSE)) {
        $templateContribution = CRM_Contribute_BAO_ContributionRecur::getTemplateContribution($contributionRecurID);
        $membershipPayment = civicrm_api3('MembershipPayment', 'get', [
          'contribution_id' => $templateContribution['id'],
          'membership_id' => $membershipID,
        ]);
        $lineItems = civicrm_api3('LineItem', 'get', [
          'contribution_id' => $templateContribution['id'],
          'entity_id' => $membershipID,
          'entity_table' => 'civicrm_membership',
        ]);
        Civi::log()->debug('PayPalIPN: Received payment for membership ' . (int) $membershipID
          . '. Original contribution was ' . (int) $contributionID . '. The template for this contribution is '
          . $templateContribution['id'] . ' it is linked to ' . $membershipPayment['count']
          . 'payments for this membership. It has ' . $lineItems['count'] . ' line items linked to  this membership.'
          . '  it is  expected the original contribution will be linked by both entities to the membership.'
        );
        if (empty($membershipPayment['count']) && empty($lineItems['count'])) {
          Civi::log()->debug('PayPalIPN: Will attempt to compensate');
          $input['membership_id'] = $this->retrieve('membershipID', 'Integer', FALSE);
        }
        if ($contributionRecurID) {
          $recurLinks = civicrm_api3('ContributionRecur', 'get', [
            'membership_id' => $membershipID,
            'contribution_recur_id' => $contributionRecurID,
          ]);
          Civi::log()->debug('PayPalIPN: Membership should be  linked to  contribution recur  record ' . $contributionRecurID
            . ' ' . $recurLinks['count'] . 'links found'
          );
        }
      }
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
          return FALSE;
        }
      }

      // CRM-19478: handle oddity when p=null is set in place of contribution page ID,
      if (!empty($ids['contributionPage']) && !is_numeric($ids['contributionPage'])) {
        // We don't need to worry if about removing contribution page id as it will be set later in
        //  CRM_Contribute_BAO_Contribution::loadRelatedObjects(..) using $objects['contribution']->contribution_page_id
        unset($ids['contributionPage']);
      }
      $ids['paymentProcessor'] = $paymentProcessorID;
      if (!$contribution->loadRelatedObjects($input, $ids)) {
        return;
      }

      $input['payment_processor_id'] = $paymentProcessorID;

      if (!empty($ids['contributionRecur'])) {
        // check if first contribution is completed, else complete first contribution
        $first = TRUE;
        $completedStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
        if ($contribution->contribution_status_id == $completedStatusId) {
          $first = FALSE;
        }
        $this->recur($input, $ids, $contributionRecur, $contribution, $first);
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
      $this->single($input, [
        'related_contact' => $ids['related_contact'] ?? NULL,
        'participant' => $ids['participant'] ?? NULL,
        'contributionRecur' => $contributionRecurID,
      ], $contribution);
    }
    catch (CRM_Core_Exception $e) {
      Civi::log()->debug($e->getMessage());
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
    $input['txnType'] = $this->retrieve('txn_type', 'String', FALSE);
    $input['paymentStatus'] = $this->retrieve('payment_status', 'String', FALSE);
    $input['invoice'] = $this->retrieve('invoice', 'String', TRUE);
    $input['amount'] = $this->retrieve('mc_gross', 'Money', FALSE);
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
      $input[$name] = $value ? $value : NULL;
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
      $contributionRecur = civicrm_api3('ContributionRecur', 'getsingle', [
        'id' => $ids['contributionRecur'],
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
    return $paymentProcessorID;
  }

}
