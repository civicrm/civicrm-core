<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * Class CRM_Core_Payment_BaseIPN.
 */
class CRM_Core_Payment_BaseIPN {

  static $_now = NULL;

  /**
   * Input parameters from payment processor. Store these so that
   * the code does not need to keep retrieving from the http request
   * @var array
   */
  protected $_inputParameters = [];

  /**
   * Only used by AuthorizeNetIPN.
   *
   * @deprecated
   *
   * @var bool
   */
  protected $_isRecurring = FALSE;

  /**
   * Only used by AuthorizeNetIPN.
   *
   * @deprecated
   *
   * @var bool
   */
  protected $_isFirstOrLastRecurringPayment = FALSE;

  /**
   * Constructor.
   */
  public function __construct() {
    self::$_now = date('YmdHis');
  }

  /**
   * Store input array on the class.
   *
   * @param array $parameters
   *
   * @throws CRM_Core_Exception
   */
  public function setInputParameters($parameters) {
    if (!is_array($parameters)) {
      throw new CRM_Core_Exception('Invalid input parameters');
    }
    $this->_inputParameters = $parameters;
  }

  /**
   * Validate incoming data.
   *
   * This function is intended to ensure that incoming data matches
   * It provides a form of pseudo-authentication - by checking the calling fn already knows
   * the correct contact id & contribution id (this can be problematic when that has changed in
   * the meantime for transactions that are delayed & contacts are merged in-between. e.g
   * Paypal allows you to resend Instant Payment Notifications if you, for example, moved site
   * and didn't update your IPN URL.
   *
   * @param array $input
   *   Interpreted values from the values returned through the IPN.
   * @param array $ids
   *   More interpreted values (ids) from the values returned through the IPN.
   * @param array $objects
   *   An empty array that will be populated with loaded object.
   * @param bool $required
   *   Boolean Return FALSE if the relevant objects don't exist.
   * @param int $paymentProcessorID
   *   Id of the payment processor ID in use.
   *
   * @return bool
   */
  public function validateData(&$input, &$ids, &$objects, $required = TRUE, $paymentProcessorID = NULL) {

    // make sure contact exists and is valid
    $contact = new CRM_Contact_BAO_Contact();
    $contact->id = $ids['contact'];
    if (!$contact->find(TRUE)) {
      CRM_Core_Error::debug_log_message("Could not find contact record: {$ids['contact']} in IPN request: " . print_r($input, TRUE));
      echo "Failure: Could not find contact record: {$ids['contact']}<p>";
      return FALSE;
    }

    // make sure contribution exists and is valid
    $contribution = new CRM_Contribute_BAO_Contribution();
    $contribution->id = $ids['contribution'];
    if (!$contribution->find(TRUE)) {
      CRM_Core_Error::debug_log_message("Could not find contribution record: {$contribution->id} in IPN request: " . print_r($input, TRUE));
      echo "Failure: Could not find contribution record for {$contribution->id}<p>";
      return FALSE;
    }
    $contribution->receive_date = CRM_Utils_Date::isoToMysql($contribution->receive_date);
    $contribution->receipt_date = CRM_Utils_Date::isoToMysql($contribution->receipt_date);

    $objects['contact'] = &$contact;
    $objects['contribution'] = &$contribution;

    // CRM-19478: handle oddity when p=null is set in place of contribution page ID,
    if (!empty($ids['contributionPage']) && !is_numeric($ids['contributionPage'])) {
      // We don't need to worry if about removing contribution page id as it will be set later in
      //  CRM_Contribute_BAO_Contribution::loadRelatedObjects(..) using $objects['contribution']->contribution_page_id
      unset($ids['contributionPage']);
    }

    if (!$this->loadObjects($input, $ids, $objects, $required, $paymentProcessorID)) {
      return FALSE;
    }
    //the process is that the loadObjects is kind of hacked by loading the objects for the original contribution and then somewhat inconsistently using them for the
    //current contribution. Here we ensure that the original contribution is available to the complete transaction function
    //we don't want to fix this in the payment processor classes because we would have to fix all of them - so better to fix somewhere central
    if (isset($objects['contributionRecur'])) {
      $objects['first_contribution'] = $objects['contribution'];
    }
    return TRUE;
  }

  /**
   * Load objects related to contribution.
   *
   * @input array information from Payment processor
   *
   * @param array $input
   * @param array $ids
   * @param array $objects
   * @param bool $required
   * @param int $paymentProcessorID
   * @param array $error_handling
   *
   * @return bool|array
   */
  public function loadObjects(&$input, &$ids, &$objects, $required, $paymentProcessorID, $error_handling = NULL) {
    if (empty($error_handling)) {
      // default options are that we log an error & echo it out
      // note that we should refactor this error handling into error code @ some point
      // but for now setting up enough separation so we can do unit tests
      $error_handling = [
        'log_error' => 1,
        'echo_error' => 1,
      ];
    }
    $ids['paymentProcessor'] = $paymentProcessorID;
    if (is_a($objects['contribution'], 'CRM_Contribute_BAO_Contribution')) {
      $contribution = &$objects['contribution'];
    }
    else {
      //legacy support - functions are 'used' to be able to pass in a DAO
      $contribution = new CRM_Contribute_BAO_Contribution();
      $contribution->id = CRM_Utils_Array::value('contribution', $ids);
      $contribution->find(TRUE);
      $objects['contribution'] = &$contribution;
    }
    try {
      $success = $contribution->loadRelatedObjects($input, $ids);
      if ($required && empty($contribution->_relatedObjects['paymentProcessor'])) {
        throw new CRM_Core_Exception("Could not find payment processor for contribution record: " . $contribution->id);
      }
    }
    catch (Exception $e) {
      $success = FALSE;
      if (!empty($error_handling['log_error'])) {
        CRM_Core_Error::debug_log_message($e->getMessage());
      }
      if (!empty($error_handling['echo_error'])) {
        echo $e->getMessage();
      }
      if (!empty($error_handling['return_error'])) {
        return [
          'is_error' => 1,
          'error_message' => ($e->getMessage()),
        ];
      }
    }
    $objects = array_merge($objects, $contribution->_relatedObjects);
    return $success;
  }

  /**
   * Set contribution to failed.
   *
   * @param array $objects
   * @param object $transaction
   * @param array $input
   *
   * @return bool
   */
  public function failed(&$objects, &$transaction, $input = []) {
    $contribution = &$objects['contribution'];
    $memberships = [];
    if (!empty($objects['membership'])) {
      $memberships = &$objects['membership'];
      if (is_numeric($memberships)) {
        $memberships = [$objects['membership']];
      }
    }

    $addLineItems = FALSE;
    if (empty($contribution->id)) {
      $addLineItems = TRUE;
    }
    $participant = &$objects['participant'];

    // CRM-15546
    $contributionStatuses = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'contribution_status_id', [
        'labelColumn' => 'name',
        'flip' => 1,
      ]);
    $contribution->receive_date = CRM_Utils_Date::isoToMysql($contribution->receive_date);
    $contribution->receipt_date = CRM_Utils_Date::isoToMysql($contribution->receipt_date);
    $contribution->thankyou_date = CRM_Utils_Date::isoToMysql($contribution->thankyou_date);
    $contribution->contribution_status_id = $contributionStatuses['Failed'];
    $contribution->save();

    // Add line items for recurring payments.
    if (!empty($objects['contributionRecur']) && $objects['contributionRecur']->id && $addLineItems) {
      CRM_Contribute_BAO_ContributionRecur::addRecurLineItems($objects['contributionRecur']->id, $contribution);
    }

    //add new soft credit against current contribution id and
    //copy initial contribution custom fields for recurring contributions
    if (!empty($objects['contributionRecur']) && $objects['contributionRecur']->id) {
      CRM_Contribute_BAO_ContributionRecur::addrecurSoftCredit($objects['contributionRecur']->id, $contribution->id);
      CRM_Contribute_BAO_ContributionRecur::copyCustomValues($objects['contributionRecur']->id, $contribution->id);
    }

    if (empty($input['IAmAHorribleNastyBeyondExcusableHackInTheCRMEventFORMTaskClassThatNeedsToBERemoved'])) {
      if (!empty($memberships)) {
        // if transaction is failed then set "Cancelled" as membership status
        $membershipStatuses = CRM_Core_PseudoConstant::get('CRM_Member_DAO_Membership', 'status_id', [
            'labelColumn' => 'name',
            'flip' => 1,
          ]);
        foreach ($memberships as $membership) {
          if ($membership) {
            $membership->status_id = $membershipStatuses['Cancelled'];
            $membership->save();

            //update related Memberships.
            $params = ['status_id' => $membershipStatuses['Cancelled']];
            CRM_Member_BAO_Membership::updateRelatedMemberships($membership->id, $params);
          }
        }
      }

      if ($participant) {
        $participantParams['id'] = $participant->id;
        $participantParams['status_id'] = 'Cancelled';
        civicrm_api3('Participant', 'create', $participantParams);
      }
    }

    $transaction->commit();
    CRM_Core_Error::debug_log_message("Setting contribution status to failed");
    //echo "Success: Setting contribution status to failed<p>";
    return TRUE;
  }

  /**
   * Handled pending contribution status.
   *
   * @param array $objects
   * @param object $transaction
   *
   * @return bool
   */
  public function pending(&$objects, &$transaction) {
    $transaction->commit();
    CRM_Core_Error::debug_log_message("returning since contribution status is pending");
    echo "Success: Returning since contribution status is pending<p>";
    return TRUE;
  }

  /**
   * Process cancelled payment outcome.
   *
   * @param array $objects
   * @param CRM_Core_Transaction $transaction
   * @param array $input
   *
   * @return bool
   */
  public function cancelled(&$objects, &$transaction, $input = []) {
    $contribution = &$objects['contribution'];
    $memberships = &$objects['membership'];
    if (is_numeric($memberships)) {
      $memberships = [$objects['membership']];
    }

    $participant = &$objects['participant'];
    $addLineItems = FALSE;
    if (empty($contribution->id)) {
      $addLineItems = TRUE;
    }
    $contributionStatuses = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'contribution_status_id', [
        'labelColumn' => 'name',
        'flip' => 1,
      ]);
    $contribution->contribution_status_id = $contributionStatuses['Cancelled'];
    $contribution->cancel_date = self::$_now;
    $contribution->cancel_reason = CRM_Utils_Array::value('reasonCode', $input);
    $contribution->receive_date = CRM_Utils_Date::isoToMysql($contribution->receive_date);
    $contribution->receipt_date = CRM_Utils_Date::isoToMysql($contribution->receipt_date);
    $contribution->thankyou_date = CRM_Utils_Date::isoToMysql($contribution->thankyou_date);
    $contribution->save();

    //add lineitems for recurring payments
    if (!empty($objects['contributionRecur']) && $objects['contributionRecur']->id && $addLineItems) {
      CRM_Contribute_BAO_ContributionRecur::addRecurLineItems($objects['contributionRecur']->id, $contribution);
    }

    //add new soft credit against current $contribution and
    //copy initial contribution custom fields for recurring contributions
    if (!empty($objects['contributionRecur']) && $objects['contributionRecur']->id) {
      CRM_Contribute_BAO_ContributionRecur::addrecurSoftCredit($objects['contributionRecur']->id, $contribution->id);
      CRM_Contribute_BAO_ContributionRecur::copyCustomValues($objects['contributionRecur']->id, $contribution->id);
    }

    if (empty($input['IAmAHorribleNastyBeyondExcusableHackInTheCRMEventFORMTaskClassThatNeedsToBERemoved'])) {
      if (!empty($memberships)) {
        $membershipStatuses = CRM_Core_PseudoConstant::get('CRM_Member_DAO_Membership', 'status_id', [
            'labelColumn' => 'name',
            'flip' => 1,
          ]);
        // Cancel only Pending memberships
        // CRM-18688
        $pendingStatusId = $membershipStatuses['Pending'];
        foreach ($memberships as $membership) {
          if ($membership && ($membership->status_id == $pendingStatusId)) {
            $membership->status_id = $membershipStatuses['Cancelled'];
            $membership->save();

            //update related Memberships.
            $params = ['status_id' => $membershipStatuses['Cancelled']];
            CRM_Member_BAO_Membership::updateRelatedMemberships($membership->id, $params);
          }
        }
      }

      if ($participant) {
        $participantParams['id'] = $participant->id;
        $participantParams['status_id'] = 'Cancelled';
        civicrm_api3('Participant', 'create', $participantParams);
      }
    }
    $transaction->commit();
    CRM_Core_Error::debug_log_message("Setting contribution status to cancelled");
    //echo "Success: Setting contribution status to cancelled<p>";
    return TRUE;
  }

  /**
   * Rollback unhandled outcomes.
   *
   * @param array $objects
   * @param CRM_Core_Transaction $transaction
   *
   * @return bool
   */
  public function unhandled(&$objects, &$transaction) {
    $transaction->rollback();
    CRM_Core_Error::debug_log_message("returning since contribution status: is not handled");
    echo "Failure: contribution status is not handled<p>";
    return FALSE;
  }

  /**
   * @deprecated
   *
   * Jumbled up function.
   *
   * The purpose of this function is to transition a pending transaction to Completed including updating any
   * related entities.
   *
   * It has been overloaded to also add recurring transactions to the database, cloning the original transaction and
   * updating related entities.
   *
   * It is recommended to avoid calling this function directly and call the api functions:
   *  - contribution.completetransaction
   *  - contribution.repeattransaction
   *
   * These functions are the focus of testing efforts and more accurately reflect the division of roles
   * (the job of the IPN class is to determine the outcome, transaction id, invoice id & to validate the source
   * and from there it should be possible to pass off transaction management.)
   *
   * This function has been problematic for some time but there are now several tests via the api_v3_Contribution test
   * and the Paypal & Authorize.net IPN tests so any refactoring should be done in conjunction with those.
   *
   * This function needs to have the 'body' moved to the CRM_Contribute_BAO_Contribute class and to undergo
   * refactoring to separate the complete transaction and repeat transaction functionality into separate functions with
   * a shared function that updates related components.
   *
   * Note that it is not necessary payment processor extension to implement an IPN class now. In general the code on the
   * IPN class is better accessed through the api which de-jumbles it a bit.
   *
   * e.g the payment class can have a function like (based on Omnipay extension):
   *
   *   public function handlePaymentNotification() {
   *     $response = $this->getValidatedOutcome();
   *     if ($response->isSuccessful()) {
   *      try {
   *        // @todo check if it is a repeat transaction & call repeattransaction instead.
   *        civicrm_api3('contribution', 'completetransaction', array('id' => $this->transaction_id));
   *      }
   *     catch (CiviCRM_API3_Exception $e) {
   *     if (!stristr($e->getMessage(), 'Contribution already completed')) {
   *       $this->handleError('error', $this->transaction_id  . $e->getMessage(), 'ipn_completion', 9000, 'An error may
   *         have occurred. Please check your receipt is correct');
   *       $this->redirectOrExit('success');
   *     }
   *     elseif ($this->transaction_id) {
   *        civicrm_api3('contribution', 'create', array('id' => $this->transaction_id, 'contribution_status_id' =>
   *        'Failed'));
   *     }
   *
   * @param array $input
   * @param array $ids
   * @param array $objects
   * @param CRM_Core_Transaction $transaction
   * @param bool $recur
   */
  public function completeTransaction(&$input, &$ids, &$objects, &$transaction, $recur = FALSE) {
    $contribution = &$objects['contribution'];

    CRM_Contribute_BAO_Contribution::completeOrder($input, $ids, $objects, $transaction, $recur, $contribution);
  }

  /**
   * Get site billing ID.
   *
   * @param array $ids
   *
   * @return bool
   */
  public function getBillingID(&$ids) {
    $ids['billing'] = CRM_Core_BAO_LocationType::getBilling();
    if (!$ids['billing']) {
      CRM_Core_Error::debug_log_message(ts('Please set a location type of %1', [1 => 'Billing']));
      echo "Failure: Could not find billing location type<p>";
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @deprecated
   *
   * @todo confirm this function is not being used by any payment processor outside core & remove.
   *
   * Note that the compose message part has been moved to contribution
   * In general LoadObjects is called first to get the objects but the composeMessageArray function now calls it
   *
   * @param array $input
   *   Incoming data from Payment processor.
   * @param array $ids
   *   Related object IDs.
   * @param array $objects
   * @param array $values
   *   Values related to objects that have already been loaded.
   * @param bool $recur
   *   Is it part of a recurring contribution.
   * @param bool $returnMessageText
   *   Should text be returned instead of sent. This.
   *   is because the function is also used to generate pdfs
   *
   * @return array
   */
  public function sendMail(&$input, &$ids, &$objects, &$values, $recur = FALSE, $returnMessageText = FALSE) {
    return CRM_Contribute_BAO_Contribution::sendMail($input, $ids, $objects['contribution']->id, $values,
      $returnMessageText);
  }

}
