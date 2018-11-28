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
   * @param string $component
   *
   * @return bool|void
   */
  public function main($component = 'contribute') {

    //we only get invoice num as a key player from payment gateway response.
    //for ARB we get x_subscription_id and x_subscription_paynum
    $x_subscription_id = $this->retrieve('x_subscription_id', 'String');
    $ids = $objects = $input = array();

    if ($x_subscription_id) {
      // Presence of the id means it is approved.
      $input['component'] = $component;

      // load post vars in $input
      $this->getInput($input, $ids);

      // load post ids in $ids
      $this->getIDs($ids, $input);

      // Attempt to get payment processor ID from URL
      if (!empty($this->_inputParameters['processor_id'])) {
        $paymentProcessorID = $this->_inputParameters['processor_id'];
      }
      else {
        // This is an unreliable method as there could be more than one instance.
        // Recommended approach is to use the civicrm/payment/ipn/xx url where xx is the payment
        // processor id & the handleNotification function (which should call the completetransaction api & by-pass this
        // entirely). The only thing the IPN class should really do is extract data from the request, validate it
        // & call completetransaction or call fail? (which may not exist yet).
        Civi::log()->warning('Unreliable method used to get payment_processor_id for AuthNet IPN - this will cause problems if you have more than one instance');
        $paymentProcessorTypeID = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_PaymentProcessorType',
          'AuthNet', 'id', 'name'
        );
        $paymentProcessorID = (int) civicrm_api3('PaymentProcessor', 'getvalue', array(
          'is_test' => 0,
          'options' => array('limit' => 1),
          'payment_processor_type_id' => $paymentProcessorTypeID,
           'return' => 'id',
        ));
      }

      if (!$this->validateData($input, $ids, $objects, TRUE, $paymentProcessorID)) {
        return FALSE;
      }
      if (!empty($ids['paymentProcessor']) && $objects['contributionRecur']->payment_processor_id != $ids['paymentProcessor']) {
        Civi::log()->warning('Payment Processor does not match the recurring processor id.', array('civi.tag' => 'deprecated'));
      }

      if ($component == 'contribute' && $ids['contributionRecur']) {
        // check if first contribution is completed, else complete first contribution
        $first = TRUE;
        if ($objects['contribution']->contribution_status_id == 1) {
          $first = FALSE;
        }
        return $this->recur($input, $ids, $objects, $first);
      }
    }
    return TRUE;
  }

  /**
   * @param array $input
   * @param array $ids
   * @param array $objects
   * @param $first
   *
   * @return bool
   */
  public function recur(&$input, &$ids, &$objects, $first) {
    $this->_isRecurring = TRUE;
    $recur = &$objects['contributionRecur'];
    $paymentProcessorObject = $objects['contribution']->_relatedObjects['paymentProcessor']['object'];

    // do a subscription check
    if ($recur->processor_id != $input['subscription_id']) {
      CRM_Core_Error::debug_log_message("Unrecognized subscription.");
      echo "Failure: Unrecognized subscription<p>";
      return FALSE;
    }

    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

    $transaction = new CRM_Core_Transaction();

    $now = date('YmdHis');

    // fix dates that already exist
    $dates = array('create_date', 'start_date', 'end_date', 'cancel_date', 'modified_date');
    foreach ($dates as $name) {
      if ($recur->$name) {
        $recur->$name = CRM_Utils_Date::isoToMysql($recur->$name);
      }
    }

    //load new contribution object if required.
    if (!$first) {
      // create a contribution and then get it processed
      $contribution = new CRM_Contribute_BAO_Contribution();
      $contribution->contact_id = $ids['contact'];
      $contribution->financial_type_id = $objects['contributionType']->id;
      $contribution->contribution_page_id = $ids['contributionPage'];
      $contribution->contribution_recur_id = $ids['contributionRecur'];
      $contribution->receive_date = $input['receive_date'];
      $contribution->currency = $objects['contribution']->currency;
      $contribution->payment_instrument_id = $objects['contribution']->payment_instrument_id;
      $contribution->amount_level = $objects['contribution']->amount_level;
      $contribution->address_id = $objects['contribution']->address_id;
      $contribution->campaign_id = $objects['contribution']->campaign_id;
      $contribution->_relatedObjects = $objects['contribution']->_relatedObjects;

      $objects['contribution'] = &$contribution;
    }
    $objects['contribution']->invoice_id = md5(uniqid(rand(), TRUE));
    $objects['contribution']->total_amount = $input['amount'];
    $objects['contribution']->trxn_id = $input['trxn_id'];

    $this->checkMD5($paymentProcessorObject, $input);

    $isFirstOrLastRecurringPayment = FALSE;
    if ($input['response_code'] == 1) {
      // Approved
      if ($first) {
        $recur->start_date = $now;
        $recur->trxn_id = $recur->processor_id;
        $isFirstOrLastRecurringPayment = CRM_Core_Payment::RECURRING_PAYMENT_START;
      }
      $statusName = 'In Progress';
      if (($recur->installments > 0) &&
        ($input['subscription_paynum'] >= $recur->installments)
      ) {
        // this is the last payment
        $statusName = 'Completed';
        $recur->end_date = $now;
        $isFirstOrLastRecurringPayment = CRM_Core_Payment::RECURRING_PAYMENT_END;
      }
      $recur->modified_date = $now;
      $recur->contribution_status_id = array_search($statusName, $contributionStatus);
      $recur->save();
    }
    else {
      // Declined
      // failed status
      $recur->contribution_status_id = array_search('Failed', $contributionStatus);
      $recur->cancel_date = $now;
      $recur->save();

      $message = ts("Subscription payment failed - %1", array(1 => htmlspecialchars($input['response_reason_text'])));
      CRM_Core_Error::debug_log_message($message);

      // the recurring contribution has declined a payment or has failed
      // so we just fix the recurring contribution and not change any of
      // the existing contributions
      // CRM-9036
      return TRUE;
    }

    // check if contribution is already completed, if so we ignore this ipn
    if ($objects['contribution']->contribution_status_id == 1) {
      $transaction->commit();
      CRM_Core_Error::debug_log_message("Returning since contribution has already been handled.");
      echo "Success: Contribution has already been handled<p>";
      return TRUE;
    }

    $this->completeTransaction($input, $ids, $objects, $transaction, $recur);

    // Only Authorize.net does this so it is on the a.net class. If there is a need for other processors
    // to do this we should make it available via the api, e.g as a parameter, changing the nuance
    // from isSentReceipt to an array of which receipts to send.
    // Note that there is site-by-site opinions on which notifications are good to send.
    if ($isFirstOrLastRecurringPayment) {
      CRM_Contribute_BAO_ContributionRecur::sendRecurringStartOrEndNotification($ids, $recur,
        $isFirstOrLastRecurringPayment);
    }

  }

  /**
   * Get the input from passed in fields.
   *
   * @param array $input
   * @param array $ids
   *
   * @return bool
   */
  public function getInput(&$input, &$ids) {
    $input['amount'] = $this->retrieve('x_amount', 'String');
    $input['subscription_id'] = $this->retrieve('x_subscription_id', 'Integer');
    $input['response_code'] = $this->retrieve('x_response_code', 'Integer');
    $input['MD5_Hash'] = $this->retrieve('x_MD5_Hash', 'String', FALSE, '');
    $input['response_reason_code'] = $this->retrieve('x_response_reason_code', 'String', FALSE);
    $input['response_reason_text'] = $this->retrieve('x_response_reason_text', 'String', FALSE);
    $input['subscription_paynum'] = $this->retrieve('x_subscription_paynum', 'Integer', FALSE, 0);
    $input['trxn_id'] = $this->retrieve('x_trans_id', 'String', FALSE);
    $input['trxn_id'] = $this->retrieve('x_trans_id', 'String', FALSE);
    $input['receive_date'] = $this->retrieve('receive_date', 'String', FALSE, 'now');

    if ($input['trxn_id']) {
      $input['is_test'] = 0;
    }
    // Only assume trxn_id 'should' have been returned for success.
    // Per CRM-17611 it would also not be passed back for a decline.
    elseif ($input['response_code'] == 1) {
      $input['is_test'] = 1;
      $input['trxn_id'] = md5(uniqid(rand(), TRUE));
    }

    if (!$this->getBillingID($ids)) {
      return FALSE;
    }
    $billingID = $ids['billing'];
    $params = array(
      'first_name' => 'x_first_name',
      'last_name' => 'x_last_name',
      "street_address-{$billingID}" => 'x_address',
      "city-{$billingID}" => 'x_city',
      "state-{$billingID}" => 'x_state',
      "postal_code-{$billingID}" => 'x_zip',
      "country-{$billingID}" => 'x_country',
      "email-{$billingID}" => 'x_email',
    );
    foreach ($params as $civiName => $resName) {
      $input[$civiName] = $this->retrieve($resName, 'String', FALSE);
    }
  }

  /**
   * Get ids from input.
   *
   * @param array $ids
   * @param array $input
   *
   * @throws \CRM_Core_Exception
   */
  public function getIDs(&$ids, &$input) {
    $ids['contact'] = $this->retrieve('x_cust_id', 'Integer', FALSE, 0);
    $ids['contribution'] = $this->retrieve('x_invoice_num', 'Integer');

    // joining with contribution table for extra checks
    $sql = "
    SELECT cr.id, cr.contact_id
      FROM civicrm_contribution_recur cr
INNER JOIN civicrm_contribution co ON co.contribution_recur_id = cr.id
     WHERE cr.processor_id = '{$input['subscription_id']}' AND
           (cr.contact_id = {$ids['contact']} OR co.id = {$ids['contribution']})
     LIMIT 1";
    $contRecur = CRM_Core_DAO::executeQuery($sql);
    $contRecur->fetch();
    $ids['contributionRecur'] = $contRecur->id;
    if ($ids['contact'] != $contRecur->contact_id) {
      $message = ts("Recurring contribution appears to have been re-assigned from id %1 to %2, continuing with %2.", array(1 => $ids['contact'], 2 => $contRecur->contact_id));
      CRM_Core_Error::debug_log_message($message);
      $ids['contact'] = $contRecur->contact_id;
    }
    if (!$ids['contributionRecur']) {
      $message = ts("Could not find contributionRecur id");
      $log = new CRM_Utils_SystemLogger();
      $log->error('payment_notification', array('message' => $message, 'ids' => $ids, 'input' => $input));
      throw new CRM_Core_Exception($message);
    }

    // get page id based on contribution id
    $ids['contributionPage'] = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution',
      $ids['contribution'],
      'contribution_page_id'
    );

    if ($input['component'] == 'event') {
      // FIXME: figure out fields for event
    }
    else {
      // Get membershipId. Join with membership payment table for additional checks
      $sql = "
    SELECT m.id
      FROM civicrm_membership m
INNER JOIN civicrm_membership_payment mp ON m.id = mp.membership_id AND mp.contribution_id = {$ids['contribution']}
     WHERE m.contribution_recur_id = {$ids['contributionRecur']}
     LIMIT 1";
      if ($membershipId = CRM_Core_DAO::singleValueQuery($sql)) {
        $ids['membership'] = $membershipId;
      }

      // FIXME: todo related_contact and onBehalfDupeAlert. Check paypalIPN.
    }
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
   * Check and validate gateway MD5 response if present.
   *
   * @param CRM_Core_Payment_AuthorizeNet $paymentObject
   * @param array $input
   *
   * @throws CRM_Core_Exception
   */
  public function checkMD5($paymentObject, $input) {
    if (empty($input['trxn_id'])) {
      // For decline we have nothing to check against.
      return;
    }
    if (!$paymentObject->checkMD5($input['MD5_Hash'], $input['trxn_id'], $input['amount'], TRUE)) {
      $message = "Failure: Security verification failed";
      $log = new CRM_Utils_SystemLogger();
      $log->error('payment_notification', array('message' => $message, 'input' => $input));
      throw new CRM_Core_Exception($message);
    }
  }

}
