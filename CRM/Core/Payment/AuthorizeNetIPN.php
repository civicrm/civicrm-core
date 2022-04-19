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

use Civi\Api4\PaymentProcessor;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_Payment_AuthorizeNetIPN extends CRM_Core_Payment_BaseIPN {

  /**
   * Constructor function.
   *
   * @param array $inputData
   *   contents of HTTP REQUEST.
   *
   * @throws CRM_Core_Exception
   */
  public function __construct($inputData) {
    $this->setInputParameters($inputData);
    parent::__construct();
  }

  /**
   * Main IPN processing function.
   *
   * @return bool|void
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \API_Exception
   */
  public function main() {
    try {
      //we only get invoice num as a key player from payment gateway response.
      //for ARB we get x_subscription_id and x_subscription_paynum
      $x_subscription_id = $this->getRecurProcessorID();
      $ids = $input = [];

      $input['component'] = 'contribute';

      // load post vars in $input
      $this->getInput($input);

      // load post ids in $ids
      $this->getIDs($ids);
      $paymentProcessorID = $this->getPaymentProcessorID();

      // Check if the contribution exists
      // make sure contribution exists and is valid
      $contribution = new CRM_Contribute_BAO_Contribution();
      $contribution->id = $contributionID = $this->getContributionID();
      if (!$contribution->find(TRUE)) {
        throw new CRM_Core_Exception('Failure: Could not find contribution record for ' . (int) $contribution->id, NULL, ['context' => "Could not find contribution record: {$contribution->id} in IPN request: " . print_r($input, TRUE)]);
      }

      $ids['contributionPage'] = $contribution->contribution_page_id;

      $contributionRecur = new CRM_Contribute_BAO_ContributionRecur();
      $contributionRecur->id = $ids['contributionRecur'];
      if (!$contributionRecur->find(TRUE)) {
        throw new CRM_Core_Exception("Could not find contribution recur record: {$ids['ContributionRecur']} in IPN request: " . print_r($input, TRUE));
      }
      // do a subscription check
      if ($contributionRecur->processor_id != $this->getRecurProcessorID()) {
        throw new CRM_Core_Exception('Unrecognized subscription.');
      }

      // check if first contribution is completed, else complete first contribution
      $first = TRUE;
      if ($contribution->contribution_status_id == 1) {
        $first = FALSE;
        //load new contribution object if required.
        // create a contribution and then get it processed
        $contribution = new CRM_Contribute_BAO_Contribution();
      }
      $input['payment_processor_id'] = $paymentProcessorID;
      $isFirstOrLastRecurringPayment = $this->recur($input, $contributionRecur, $contribution, $first);

      if ($isFirstOrLastRecurringPayment) {
        //send recurring Notification email for user
        CRM_Contribute_BAO_ContributionPage::recurringNotify($contributionID, TRUE,
          $contributionRecur
        );
      }

      return TRUE;
    }
    catch (CRM_Core_Exception $e) {
      Civi::log()->debug($e->getMessage());
      echo 'Invalid or missing data';
    }
  }

  /**
   * @param array $input
   * @param \CRM_Contribute_BAO_ContributionRecur $recur
   * @param \CRM_Contribute_BAO_Contribution $contribution
   * @param bool $first
   *
   * @return bool
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function recur($input, $recur, $contribution, $first) {

    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

    $now = date('YmdHis');

    $isFirstOrLastRecurringPayment = FALSE;
    if ($this->isSuccess()) {
      // Approved
      if ($first) {
        $recur->trxn_id = $recur->processor_id;
        $isFirstOrLastRecurringPayment = CRM_Core_Payment::RECURRING_PAYMENT_START;
      }

      if (($recur->installments > 0) &&
        ($input['subscription_paynum'] >= $recur->installments)
      ) {
        // this is the last payment
        $recur->end_date = $now;
        $isFirstOrLastRecurringPayment = CRM_Core_Payment::RECURRING_PAYMENT_END;
        // This end date update should occur in ContributionRecur::updateOnNewPayment
        // testIPNPaymentRecurNoReceipt has test cover.
        $recur->save();
      }
    }
    else {
      // Declined
      // failed status
      $recur->contribution_status_id = array_search('Failed', $contributionStatus);
      $recur->cancel_date = $now;
      $recur->save();

      $message = ts('Subscription payment failed - %1', [1 => htmlspecialchars($input['response_reason_text'])]);
      CRM_Core_Error::debug_log_message($message);

      // the recurring contribution has declined a payment or has failed
      // so we just fix the recurring contribution and not change any of
      // the existing contributions
      // CRM-9036
      return FALSE;
    }

    CRM_Contribute_BAO_Contribution::completeOrder($input, $recur->id, $contribution->id ?? NULL);
    return $isFirstOrLastRecurringPayment;
  }

  /**
   * Get the input from passed in fields.
   *
   * @param array $input
   *
   * @throws \CRM_Core_Exception
   */
  public function getInput(&$input) {
    $input['amount'] = $this->retrieve('x_amount', 'String');
    $input['subscription_id'] = $this->getRecurProcessorID();
    $input['response_reason_code'] = $this->retrieve('x_response_reason_code', 'String', FALSE);
    $input['response_reason_text'] = $this->retrieve('x_response_reason_text', 'String', FALSE);
    $input['subscription_paynum'] = $this->retrieve('x_subscription_paynum', 'Integer', FALSE, 0);
    $input['trxn_id'] = $this->retrieve('x_trans_id', 'String', FALSE);
    $input['receive_date'] = $this->retrieve('receive_date', 'String', FALSE, date('YmdHis', strtotime('now')));

    if ($input['trxn_id']) {
      $input['is_test'] = 0;
    }
    // Only assume trxn_id 'should' have been returned for success.
    // Per CRM-17611 it would also not be passed back for a decline.
    elseif ($this->isSuccess()) {
      $input['is_test'] = 1;
      $input['trxn_id'] = md5(uniqid(rand(), TRUE));
    }

    $billingID = CRM_Core_BAO_LocationType::getBilling();
    $params = [
      'first_name' => 'x_first_name',
      'last_name' => 'x_last_name',
      "street_address-{$billingID}" => 'x_address',
      "city-{$billingID}" => 'x_city',
      "state-{$billingID}" => 'x_state',
      "postal_code-{$billingID}" => 'x_zip',
      "country-{$billingID}" => 'x_country',
      "email-{$billingID}" => 'x_email',
    ];
    foreach ($params as $civiName => $resName) {
      $input[$civiName] = $this->retrieve($resName, 'String', FALSE);
    }
  }

  /**
   * Was the transaction successful.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  private function isSuccess(): bool {
    return $this->retrieve('x_response_code', 'Integer') === 1;
  }

  /**
   * Get ids from input.
   *
   * @param array $ids
   *
   * @throws \CRM_Core_Exception
   */
  public function getIDs(&$ids) {
    $ids['contribution'] = $this->getContributionID();
    $ids['contributionRecur'] = $this->getContributionRecurID();
  }

  /**
   * @param string $name
   *   Parameter name.
   * @param string $type
   *   Parameter type.
   * @param bool $abort
   *   Abort if not present.
   * @param null $default
   *   Default value.
   *
   * @throws CRM_Core_Exception
   * @return mixed
   */
  public function retrieve($name, $type, $abort = TRUE, $default = NULL) {
    $value = CRM_Utils_Type::validate(
      empty($this->_inputParameters[$name]) ? $default : $this->_inputParameters[$name],
      $type,
      FALSE
    );
    if ($abort && $value === NULL) {
      throw new CRM_Core_Exception("Could not find an entry for $name");
    }
    return $value;
  }

  /**
   * Get membership id, if any.
   *
   * @param int $contributionID
   * @param int $contributionRecurID
   *
   * @return int|null
   */
  protected function getMembershipID(int $contributionID, int $contributionRecurID): ?int {
    // Get membershipId. Join with membership payment table for additional checks
    $sql = "
    SELECT m.id
      FROM civicrm_membership m
INNER JOIN civicrm_membership_payment mp ON m.id = mp.membership_id AND mp.contribution_id = {$contributionID}
     WHERE m.contribution_recur_id = {$contributionRecurID}
     LIMIT 1";
    return CRM_Core_DAO::singleValueQuery($sql);
  }

  /**
   * Get the recurring contribution object.
   *
   * @param string $processorID
   * @param int $contactID
   * @param int $contributionID
   *
   * @return \CRM_Core_DAO|\DB_Error|object
   * @throws \CRM_Core_Exception
   */
  protected function getContributionRecurObject(string $processorID, int $contactID, int $contributionID) {
    // joining with contribution table for extra checks
    $sql = "
    SELECT cr.id, cr.contact_id
      FROM civicrm_contribution_recur cr
INNER JOIN civicrm_contribution co ON co.contribution_recur_id = cr.id
     WHERE cr.processor_id = '{$processorID}' AND
           (cr.contact_id = $contactID OR co.id = $contributionID)
     LIMIT 1";
    $contRecur = CRM_Core_DAO::executeQuery($sql);
    if (!$contRecur->fetch()) {
      throw new CRM_Core_Exception('Could not find contributionRecur id');
    }
    if ($contactID != $contRecur->contact_id) {
      $message = ts("Recurring contribution appears to have been re-assigned from id %1 to %2, continuing with %2.", [1 => $contactID, 2 => $contRecur->contact_id]);
      CRM_Core_Error::debug_log_message($message);
    }
    return $contRecur;
  }

  /**
   * Get the payment processor id.
   *
   * @return int
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  protected function getPaymentProcessorID(): int {
    // Attempt to get payment processor ID from URL
    if (!empty($this->_inputParameters['processor_id']) &&
      'AuthNet' === PaymentProcessor::get(FALSE)
        ->addSelect('payment_processor_type_id:name')
        ->addWhere('id', '=', $this->_inputParameters['processor_id'])
        ->execute()->first()['payment_processor_type_id:name']
    ) {
      return (int) $this->_inputParameters['processor_id'];
    }
    // This is an unreliable method as there could be more than one instance.
    // Recommended approach is to use the civicrm/payment/ipn/xx url where xx is the payment
    // processor id & the handleNotification function (which should call the completetransaction api & by-pass this
    // entirely). The only thing the IPN class should really do is extract data from the request, validate it
    // & call completetransaction or call fail? (which may not exist yet).
    Civi::log()->warning('Unreliable method used to get payment_processor_id for AuthNet IPN - this will cause problems if you have more than one instance');
    $paymentProcessorTypeID = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_PaymentProcessorType',
      'AuthNet', 'id', 'name'
    );
    return (int) civicrm_api3('PaymentProcessor', 'getvalue', [
      'is_test' => 0,
      'options' => ['limit' => 1],
      'payment_processor_type_id' => $paymentProcessorTypeID,
      'return' => 'id',
    ]);
  }

  /**
   * Get the processor_id for the recurring.
   *
   * This is the value stored in civicrm_contribution_recur.processor_id,
   * sometimes called subscription_id.
   *
   * @return string
   *
   * @throws \CRM_Core_Exception
   */
  protected function getRecurProcessorID(): string {
    return $this->retrieve('x_subscription_id', 'String');
  }

  /**
   * Get the contribution ID to be updated.
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
   */
  protected function getContributionID(): int {
    return (int) $this->retrieve('x_invoice_num', 'Integer');
  }

  /**
   * Get the id of the recurring contribution.
   *
   * @return int
   * @throws \CRM_Core_Exception
   */
  protected function getContributionRecurID(): int {
    $contributionRecur = $this->getContributionRecurObject($this->getRecurProcessorID(), (int) $this->retrieve('x_cust_id', 'Integer', FALSE, 0), $this->getContributionID());
    return (int) $contributionRecur->id;
  }

}
