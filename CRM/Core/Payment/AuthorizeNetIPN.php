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
use Civi\Api4\PaymentProcessor;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_Payment_AuthorizeNetIPN {

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
   *   contents of HTTP REQUEST.
   *
   * @throws CRM_Core_Exception
   */
  public function __construct($inputData) {
    if (!is_array($inputData)) {
      throw new CRM_Core_Exception('Invalid input parameters');
    }
    $this->_inputParameters = $inputData;
  }

  /**
   * @var string
   */
  protected $transactionID;

  /**
   * @var string
   */
  protected $contributionStatus;

  /**
   * Main IPN processing function.
   */
  public function main() {
    try {
      //we only get invoice num as a key player from payment gateway response.
      //for ARB we get x_subscription_id and x_subscription_paynum
      // @todo - no idea what the above comment means. The do-nothing line below
      // this is only still here as it might relate???
      $x_subscription_id = $this->getRecurProcessorID();

      if (!$this->isSuccess()) {
        $errorMessage = ts('Subscription payment failed - %1', [1 => htmlspecialchars($this->getInput()['response_reason_text'])]);
        ContributionRecur::update(FALSE)
          ->addWhere('id', '=', $this->getContributionRecurID())
          ->setValues([
            'contribution_status_id:name' => 'Failed',
            'cancel_date' => 'now',
            'cancel_reason' => $errorMessage,
          ])->execute();
        \Civi::log('authorize_net')->info($errorMessage);
        return;
      }
      if ($this->getContributionStatus() !== 'Completed') {
        ContributionRecur::update(FALSE)->addWhere('id', '=', $this->getContributionRecurID())
          ->setValues(['trxn_id' => $this->getRecurProcessorID()])->execute();
        $contributionID = $this->getContributionID();
      }
      else {
        $contribution = civicrm_api3('Contribution', 'repeattransaction', [
          'contribution_recur_id' => $this->getContributionRecurID(),
          'receive_date' => $this->getInput()['receive_date'],
          'payment_processor_id' => $this->getPaymentProcessorID(),
          'trxn_id' => $this->getInput()['trxn_id'],
          'amount' => $this->getAmount(),
        ]);
        $contributionID = $contribution['id'];
      }
      civicrm_api3('Payment', 'create', [
        'trxn_id' => $this->getInput()['trxn_id'],
        'trxn_date' => $this->getInput()['receive_date'],
        'payment_processor_id' => $this->getPaymentProcessorID(),
        'contribution_id' => $contributionID,
        'total_amount' => $this->getAmount(),
        'is_send_contribution_notification' => $this->getContributionRecur()->is_email_receipt,
      ]);
      $this->notify();
    }
    catch (CRM_Core_Exception $e) {
      Civi::log('authorize_net')->debug($e->getMessage());
      echo 'Invalid or missing data';
    }
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function notify() {
    $recur = $this->getContributionRecur();
    $input = $this->getInput();
    $input['payment_processor_id'] = $this->getPaymentProcessorID();

    $isFirstOrLastRecurringPayment = FALSE;
    if ($this->isSuccess()) {
      // Approved
      if ($this->getContributionStatus() !== 'Completed') {
        $isFirstOrLastRecurringPayment = CRM_Core_Payment::RECURRING_PAYMENT_START;
      }

      if (($recur->installments > 0) &&
        ($input['subscription_paynum'] >= $recur->installments)
      ) {
        // this is the last payment
        $isFirstOrLastRecurringPayment = CRM_Core_Payment::RECURRING_PAYMENT_END;
      }
    }

    if ($isFirstOrLastRecurringPayment) {
      //send recurring Notification email for user
      CRM_Contribute_BAO_ContributionPage::recurringNotify($this->getContributionID(), TRUE,
        $this->getContributionRecur()
      );
    }
  }

  /**
   * Get the input from passed in fields.
   *
   * @throws \CRM_Core_Exception
   */
  public function getInput(): array {
    $input = [];
    // This component is probably obsolete & will go once we stop calling completeOrder directly.
    $input['component'] = 'contribute';
    $input['amount'] = $this->retrieve('x_amount', 'String');
    $input['subscription_id'] = $this->getRecurProcessorID();
    $input['response_reason_code'] = $this->retrieve('x_response_reason_code', 'String', FALSE);
    $input['response_reason_text'] = $this->retrieve('x_response_reason_text', 'String', FALSE);
    $input['subscription_paynum'] = $this->retrieve('x_subscription_paynum', 'Integer', FALSE, 0);
    $input['trxn_id'] = $this->retrieve('x_trans_id', 'String', FALSE);
    $input['receive_date'] = $this->retrieve('receive_date', 'String', FALSE, date('YmdHis', time()));

    if ($input['trxn_id']) {
      $input['is_test'] = 0;
    }
    // Only assume trxn_id 'should' have been returned for success.
    // Per CRM-17611 it would also not be passed back for a decline.
    elseif ($this->isSuccess()) {
      $input['is_test'] = 1;
      $input['trxn_id'] = $this->transactionID ?: bin2hex(random_bytes(16));
    }
    $this->transactionID = $input['trxn_id'];

    // None of this is used...
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
    return $input;
  }

  /**
   * Get amount.
   *
   * @return string
   *
   * @throws \CRM_Core_Exception
   */
  protected function getAmount(): string {
    return $this->retrieve('x_amount', 'String');
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
    $sql = '
    SELECT cr.id, cr.contact_id
      FROM civicrm_contribution_recur cr
INNER JOIN civicrm_contribution co ON co.contribution_recur_id = cr.id
     WHERE cr.processor_id = %1 AND
           (cr.contact_id = %2 OR co.id = %3)
     LIMIT 1';
    $contributionRecur = CRM_Core_DAO::executeQuery($sql, [
      1 => [$processorID, 'String'],
      2 => [$contactID, 'Integer'],
      3 => [$contributionID, 'Integer'],
    ]);
    if (!$contributionRecur->fetch()) {
      throw new CRM_Core_Exception('Could not find contributionRecur id');
    }
    if ($contactID != $contributionRecur->contact_id) {
      $message = ts('Recurring contribution appears to have been re-assigned from id %1 to %2, continuing with %2.', [1 => $contactID, 2 => $contributionRecur->contact_id]);
      \Civi::log('authorize_net')->warning($message);
    }
    return $contributionRecur;
  }

  /**
   * Get the payment processor id.
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
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
    $id = $this->retrieve('x_invoice_num', 'String');

    /* Fix for https://lab.civicrm.org/dev/core/-/issues/4833 :
     * There's a potential conflict in the way x_invoice_num is used currently,
     * vs the way it was used in the past. This problem will affect long-running
     * ARB (recurring) subscriptions, if they're old enough (e.g. created before Nov 2021,
     * though admittedly I'm not sure of that cutoff date).
     *
     * Summary:
     * - Currently: x_invoice_num is an integer equal to civicrm_contribution.id;
     * - Previously, x_invoice_num was a 20-character alphanumeric string,
     * stored in the comma-delimited value of civicrm_contribution.trxn_id.
     *
     * Therefore: If x_invoice_num is not an integer, AND it's 20 characters long,
     * we'll assume we might be processing a payment on a long-running "old-style"
     * ARB subscription and attempt to match on (civicrm_contribution.trxn_id contains
     * x_invoice_num).
     */
    if (
      ($id !== (int) $id)
      && (strlen($id) == 20)
    ) {
      $contribution = Contribution::get(FALSE)
        ->addSelect('id')
        ->addWhere('trxn_id', 'LIKE', "$id,%")
        ->execute()
        ->first();
      $id = $contribution['id'];
    }
    return (int) $id;
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

  /**
   *
   * @return \CRM_Contribute_BAO_ContributionRecur
   * @throws \CRM_Core_Exception
   */
  private function getContributionRecur(): CRM_Contribute_BAO_ContributionRecur {
    $contributionRecur = new CRM_Contribute_BAO_ContributionRecur();
    $contributionRecur->id = $this->getContributionRecurID();
    if (!$contributionRecur->find(TRUE)) {
      throw new CRM_Core_Exception('Could not find contribution recur record: ' . $this->getContributionRecurID() . ' in IPN request: ' . print_r($this->getInput(), TRUE));
    }
    // do a subscription check
    if ($contributionRecur->processor_id != $this->getRecurProcessorID()) {
      throw new CRM_Core_Exception('Unrecognized subscription.');
    }
    return $contributionRecur;
  }

  /**
   * Get the relevant contribution status.
   *
   * @return string $status
   *
   * @throws \CRM_Core_Exception
   */
  private function getContributionStatus(): string {
    if (!$this->contributionStatus) {
      // Check if the contribution exists
      // make sure contribution exists and is valid
      $contribution = Contribution::get(FALSE)
        ->addWhere('id', '=', $this->getContributionID())
        ->addSelect('contribution_status_id:name')->execute()->first();
      if (empty($contribution)) {
        throw new CRM_Core_Exception('Failure: Could not find contribution record for ' . $this->getContributionID(), NULL, ['context' => 'Could not find contribution record: ' . $this->getContributionID() . ' in IPN request: ' . print_r($this->getInput(), TRUE)]);
      }
      $this->contributionStatus = $contribution['contribution_status_id:name'];
    }
    return $this->contributionStatus;
  }

}
