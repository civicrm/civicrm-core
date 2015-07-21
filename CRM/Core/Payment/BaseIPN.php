<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
  protected $_inputParameters = array();

  protected $_isRecurring = FALSE;

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

    $objects['contact'] = &$contact;
    $objects['contribution'] = &$contribution;
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
   * @param $input
   * @param array $ids
   * @param array $objects
   * @param bool $required
   * @param int $paymentProcessorID
   * @param array $error_handling
   *
   * @return bool
   */
  public function loadObjects(&$input, &$ids, &$objects, $required, $paymentProcessorID, $error_handling = NULL) {
    if (empty($error_handling)) {
      // default options are that we log an error & echo it out
      // note that we should refactor this error handling into error code @ some point
      // but for now setting up enough separation so we can do unit tests
      $error_handling = array(
        'log_error' => 1,
        'echo_error' => 1,
      );
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
      $success = $contribution->loadRelatedObjects($input, $ids, $required);
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
        return array(
          'is_error' => 1,
          'error_message' => ($e->getMessage()),
        );
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
  public function failed(&$objects, &$transaction, $input = array()) {
    $contribution = &$objects['contribution'];
    $memberships = array();
    if (!empty($objects['membership'])) {
      $memberships = &$objects['membership'];
      if (is_numeric($memberships)) {
        $memberships = array($objects['membership']);
      }
    }

    $addLineItems = FALSE;
    if (empty($contribution->id)) {
      $addLineItems = TRUE;
    }
    $participant = &$objects['participant'];

    //CRM-15546
    $contributionStatuses = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'contribution_status_id', array(
        'labelColumn' => 'name',
        'flip' => 1,
      ));
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

    if (empty($input['skipComponentSync'])) {
      if (!empty($memberships)) {
        // if transaction is failed then set "Cancelled" as membership status
        $membershipStatuses = CRM_Core_PseudoConstant::get('CRM_Member_DAO_Membership', 'status_id', array(
            'labelColumn' => 'name',
            'flip' => 1,
          ));
        foreach ($memberships as $membership) {
          if ($membership) {
            $membership->status_id = $membershipStatuses['Cancelled'];
            $membership->save();

            //update related Memberships.
            $params = array('status_id' => $membershipStatuses['Cancelled']);
            CRM_Member_BAO_Membership::updateRelatedMemberships($membership->id, $params);
          }
        }
      }

      if ($participant) {
        $participantStatuses = CRM_Core_PseudoConstant::get('CRM_Event_DAO_Participant', 'status_id', array(
            'labelColumn' => 'name',
            'flip' => 1,
          ));
        $participant->status_id = $participantStatuses['Cancelled'];
        $participant->save();
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
   * @param $objects
   * @param $transaction
   * @param array $input
   *
   * @return bool
   */
  public function cancelled(&$objects, &$transaction, $input = array()) {
    $contribution = &$objects['contribution'];
    $memberships = &$objects['membership'];
    if (is_numeric($memberships)) {
      $memberships = array($objects['membership']);
    }

    $participant = &$objects['participant'];
    $addLineItems = FALSE;
    if (empty($contribution->id)) {
      $addLineItems = TRUE;
    }
    $contributionStatuses = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'contribution_status_id', array(
        'labelColumn' => 'name',
        'flip' => 1,
      ));
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

    if (empty($input['skipComponentSync'])) {
      if (!empty($memberships)) {
        $membershipStatuses = CRM_Core_PseudoConstant::get('CRM_Member_DAO_Membership', 'status_id', array(
            'labelColumn' => 'name',
            'flip' => 1,
          ));
        foreach ($memberships as $membership) {
          if ($membership) {
            $membership->status_id = $membershipStatuses['Cancelled'];
            $membership->save();

            //update related Memberships.
            $params = array('status_id' => $membershipStatuses['Cancelled']);
            CRM_Member_BAO_Membership::updateRelatedMemberships($membership->id, $params);
          }
        }
      }

      if ($participant) {
        $participantStatuses = CRM_Core_PseudoConstant::get('CRM_Event_DAO_Participant', 'status_id', array(
            'labelColumn' => 'name',
            'flip' => 1,
          ));
        $participant->status_id = $participantStatuses['Cancelled'];
        $participant->save();
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
   * @param $objects
   * @param $transaction
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
   * This function needs to have the 'body' moved to the CRM_Contribution_BAO_Contribute class and to undergo
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
   * @param $transaction
   * @param bool $recur
   */
  public function completeTransaction(&$input, &$ids, &$objects, &$transaction, $recur = FALSE) {
    $contribution = &$objects['contribution'];

    $primaryContributionID = isset($contribution->id) ? $contribution->id : $objects['first_contribution']->id;

    $memberships = &$objects['membership'];
    if (is_numeric($memberships)) {
      $memberships = array($objects['membership']);
    }
    $participant = &$objects['participant'];

    $changeToday = CRM_Utils_Array::value('trxn_date', $input, self::$_now);
    $recurContrib = &$objects['contributionRecur'];

    $values = array();
    if (isset($input['is_email_receipt'])) {
      $values['is_email_receipt'] = $input['is_email_receipt'];
    }
    $source = NULL;
    if ($input['component'] == 'contribute') {
      if ($contribution->contribution_page_id) {
        CRM_Contribute_BAO_ContributionPage::setValues($contribution->contribution_page_id, $values);
        $source = ts('Online Contribution') . ': ' . $values['title'];
      }
      elseif ($recurContrib && $recurContrib->id) {
        $contribution->contribution_page_id = NULL;
        $values['amount'] = $recurContrib->amount;
        $values['financial_type_id'] = $objects['contributionType']->id;
        $values['title'] = $source = ts('Offline Recurring Contribution');
        $domainValues = CRM_Core_BAO_Domain::getNameAndEmail();
        $values['receipt_from_name'] = $domainValues[0];
        $values['receipt_from_email'] = $domainValues[1];
      }

      if ($recurContrib && $recurContrib->id && !isset($input['is_email_receipt'])) {
        //CRM-13273 - is_email_receipt setting on recurring contribution should take precedence over contribution page setting
        // but CRM-16124 if $input['is_email_receipt'] is set then that should not be overridden.
        $values['is_email_receipt'] = $recurContrib->is_email_receipt;
      }

      $contribution->source = $source;
      if (!empty($values['is_email_receipt'])) {
        $contribution->receipt_date = self::$_now;
      }

      if (!empty($memberships)) {
        $membershipsUpdate = array();
        foreach ($memberships as $membershipTypeIdKey => $membership) {
          if ($membership) {
            $format = '%Y%m%d';

            $currentMembership = CRM_Member_BAO_Membership::getContactMembership($membership->contact_id,
              $membership->membership_type_id,
              $membership->is_test, $membership->id
            );

            // CRM-8141 update the membership type with the value recorded in log when membership created/renewed
            // this picks up membership type changes during renewals
            $sql = "
SELECT    membership_type_id
FROM      civicrm_membership_log
WHERE     membership_id=$membership->id
ORDER BY  id DESC
LIMIT 1;";
            $dao = new CRM_Core_DAO();
            $dao->query($sql);
            if ($dao->fetch()) {
              if (!empty($dao->membership_type_id)) {
                $membership->membership_type_id = $dao->membership_type_id;
                $membership->save();
              }
              // else fall back to using current membership type
            }
            // else fall back to using current membership type
            $dao->free();

            $num_terms = $contribution->getNumTermsByContributionAndMembershipType($membership->membership_type_id, $primaryContributionID);
            if ($currentMembership) {
              /*
               * Fixed FOR CRM-4433
               * In BAO/Membership.php(renewMembership function), we skip the extend membership date and status
               * when Contribution mode is notify and membership is for renewal )
               */
              CRM_Member_BAO_Membership::fixMembershipStatusBeforeRenew($currentMembership, $changeToday);

              // @todo - we should pass membership_type_id instead of null here but not
              // adding as not sure of testing
              $dates = CRM_Member_BAO_MembershipType::getRenewalDatesForMembershipType($membership->id,
                $changeToday, NULL, $num_terms
              );

              $dates['join_date'] = CRM_Utils_Date::customFormat($currentMembership['join_date'], $format);
            }
            else {
              $dates = CRM_Member_BAO_MembershipType::getDatesForMembershipType($membership->membership_type_id, NULL, NULL, NULL, $num_terms);
            }

            //get the status for membership.
            $calcStatus = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($dates['start_date'],
              $dates['end_date'],
              $dates['join_date'],
              'today',
              TRUE,
              $membership->membership_type_id,
              (array) $membership
            );

            $formatedParams = array(
              'status_id' => CRM_Utils_Array::value('id', $calcStatus, 2),
              'join_date' => CRM_Utils_Date::customFormat(CRM_Utils_Array::value('join_date', $dates), $format),
              'start_date' => CRM_Utils_Date::customFormat(CRM_Utils_Array::value('start_date', $dates), $format),
              'end_date' => CRM_Utils_Date::customFormat(CRM_Utils_Array::value('end_date', $dates), $format),
            );
            //we might be renewing membership,
            //so make status override false.
            $formatedParams['is_override'] = FALSE;
            $membership->copyValues($formatedParams);
            $membership->save();

            //updating the membership log
            $membershipLog = array();
            $membershipLog = $formatedParams;

            $logStartDate = $formatedParams['start_date'];
            if (!empty($dates['log_start_date'])) {
              $logStartDate = CRM_Utils_Date::customFormat($dates['log_start_date'], $format);
              $logStartDate = CRM_Utils_Date::isoToMysql($logStartDate);
            }

            $membershipLog['start_date'] = $logStartDate;
            $membershipLog['membership_id'] = $membership->id;
            $membershipLog['modified_id'] = $membership->contact_id;
            $membershipLog['modified_date'] = date('Ymd');
            $membershipLog['membership_type_id'] = $membership->membership_type_id;

            CRM_Member_BAO_MembershipLog::add($membershipLog, CRM_Core_DAO::$_nullArray);

            //update related Memberships.
            CRM_Member_BAO_Membership::updateRelatedMemberships($membership->id, $formatedParams);

            //update the membership type key of membership relatedObjects array
            //if it has changed after membership update
            if ($membershipTypeIdKey != $membership->membership_type_id) {
              $membershipsUpdate[$membership->membership_type_id] = $membership;
              $contribution->_relatedObjects['membership'][$membership->membership_type_id] = $membership;
              unset($contribution->_relatedObjects['membership'][$membershipTypeIdKey]);
              unset($memberships[$membershipTypeIdKey]);
            }
          }
        }
        //update the memberships object with updated membershipTypeId data
        //if membershipTypeId has changed after membership update
        if (!empty($membershipsUpdate)) {
          $memberships = $memberships + $membershipsUpdate;
        }
      }
    }
    else {
      // event
      $eventParams = array('id' => $objects['event']->id);
      $values['event'] = array();

      CRM_Event_BAO_Event::retrieve($eventParams, $values['event']);

      //get location details
      $locationParams = array('entity_id' => $objects['event']->id, 'entity_table' => 'civicrm_event');
      $values['location'] = CRM_Core_BAO_Location::getValues($locationParams);

      $ufJoinParams = array(
        'entity_table' => 'civicrm_event',
        'entity_id' => $ids['event'],
        'module' => 'CiviEvent',
      );

      list($custom_pre_id,
        $custom_post_ids
        ) = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);

      $values['custom_pre_id'] = $custom_pre_id;
      $values['custom_post_id'] = $custom_post_ids;
      //for tasks 'Change Participant Status' and 'Batch Update Participants Via Profile' case
      //and cases involving status updation through ipn
      $values['totalAmount'] = $input['amount'];

      $contribution->source = ts('Online Event Registration') . ': ' . $values['event']['title'];

      if ($values['event']['is_email_confirm']) {
        $contribution->receipt_date = self::$_now;
        $values['is_email_receipt'] = 1;
      }
      if (empty($input['skipComponentSync'])) {
        $participantStatuses = CRM_Core_PseudoConstant::get('CRM_Event_DAO_Participant', 'status_id', array(
            'labelColumn' => 'name',
            'flip' => 1,
          ));
        $participant->status_id = $participantStatuses['Registered'];
      }
      $participant->save();
    }

    if (CRM_Utils_Array::value('net_amount', $input, 0) == 0 &&
      CRM_Utils_Array::value('fee_amount', $input, 0) != 0
    ) {
      $input['net_amount'] = $input['amount'] - $input['fee_amount'];
    }
    // This complete transaction function is being overloaded to create new contributions too.
    // here we record if it is a new contribution.
    // @todo separate the 2 more appropriately.
    $isNewContribution = FALSE;
    if (empty($contribution->id)) {
      $isNewContribution = TRUE;
      if (!empty($input['amount']) &&  $input['amount'] != $contribution->total_amount) {
        $contribution->total_amount = $input['amount'];
        // The BAO does this stuff but we are actually kinda bypassing it here (bad code! go sit in the corner)
        // so we have to handle net_amount in this (naughty) code.
        if (isset($input['fee_amount']) && is_numeric($input['fee_amount'])) {
          $contribution->fee_amount = $input['fee_amount'];
        }
        $contribution->net_amount = $contribution->total_amount - $contribution->fee_amount;
      }
      if (!empty($input['campaign_id'])) {
        $contribution->campaign_id = $input['campaign_id'];
      }
    }

    $contributionStatuses = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'contribution_status_id', array(
        'labelColumn' => 'name',
        'flip' => 1,
      ));

    // @todo this section should call the api  in order to have hooks called &
    // because all this 'messiness' setting variables could be avoided
    // by letting the api resolve pseudoconstants & copy set values and format dates.
    $contribution->contribution_status_id = $contributionStatuses['Completed'];
    $contribution->is_test = $input['is_test'];

    // CRM-15960 If we don't have a value we 'want' for the amounts, leave it to the BAO to sort out.
    if (isset($input['net_amount'])) {
      $contribution->fee_amount = CRM_Utils_Array::value('fee_amount', $input, 0);
    }
    if (isset($input['net_amount'])) {
      $contribution->net_amount = $input['net_amount'];
    }

    $contribution->trxn_id = $input['trxn_id'];
    $contribution->receive_date = CRM_Utils_Date::isoToMysql($contribution->receive_date);
    $contribution->thankyou_date = CRM_Utils_Date::isoToMysql($contribution->thankyou_date);
    $contribution->receipt_date = CRM_Utils_Date::isoToMysql($contribution->receipt_date);
    $contribution->cancel_date = 'null';

    if (!empty($input['check_number'])) {
      $contribution->check_number = $input['check_number'];
    }

    if (!empty($input['payment_instrument_id'])) {
      $contribution->payment_instrument_id = $input['payment_instrument_id'];
    }

    if (!empty($contribution->id)) {
      $contributionId['id'] = $contribution->id;
      $input['prevContribution'] = CRM_Contribute_BAO_Contribution::getValues($contributionId, CRM_Core_DAO::$_nullArray, CRM_Core_DAO::$_nullArray);
    }

    $contribution->save();

    // Add new soft credit against current $contribution.
    if (CRM_Utils_Array::value('contributionRecur', $objects) && $objects['contributionRecur']->id) {
      CRM_Contribute_BAO_ContributionRecur::addrecurSoftCredit($objects['contributionRecur']->id, $contribution->id);
    }

    //add line items for recurring payments
    if (!empty($contribution->contribution_recur_id)) {
      if ($isNewContribution) {
        $input['line_item'] = CRM_Contribute_BAO_ContributionRecur::addRecurLineItems($contribution->contribution_recur_id, $contribution);
      }
      else {
        // this is just to prevent e-notices when we call recordFinancialAccounts - per comments on that line - intention is somewhat unclear
        $input['line_item'] = array();
      }
    }

    //copy initial contribution custom fields for recurring contributions
    if ($recurContrib && $recurContrib->id) {
      CRM_Contribute_BAO_ContributionRecur::copyCustomValues($recurContrib->id, $contribution->id);
    }

    $paymentProcessorId = '';
    if (isset($objects['paymentProcessor'])) {
      if (is_array($objects['paymentProcessor'])) {
        $paymentProcessorId = $objects['paymentProcessor']['id'];
      }
      else {
        $paymentProcessorId = $objects['paymentProcessor']->id;
      }
    }
    //it's hard to see how it could reach this point without a contributon id as it is saved in line 511 above
    // which raised the question as to whether this check preceded line 511 & if so whether something could be broken
    // From a lot of code reading /debugging I'm still not sure the intent WRT first & subsequent payments in this code
    // it would be good if someone added some comments or refactored this
    if ($contribution->id) {
      $contributionStatuses = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'contribution_status_id', array(
          'labelColumn' => 'name',
          'flip' => 1,
        ));
      if ((empty($input['prevContribution']) && $paymentProcessorId) || (!$input['prevContribution']->is_pay_later && $input['prevContribution']->contribution_status_id == $contributionStatuses['Pending'])) {
        $input['payment_processor'] = $paymentProcessorId;
      }
      $input['contribution_status_id'] = $contributionStatuses['Completed'];
      $input['total_amount'] = $input['amount'];
      $input['contribution'] = $contribution;
      $input['financial_type_id'] = $contribution->financial_type_id;

      if (!empty($contribution->_relatedObjects['participant'])) {
        $input['contribution_mode'] = 'participant';
        $input['participant_id'] = $contribution->_relatedObjects['participant']->id;
        $input['skipLineItem'] = 1;
      }
      elseif (!empty($contribution->_relatedObjects['membership'])) {
        $input['skipLineItem'] = TRUE;
        $input['contribution_mode'] = 'membership';
      }
      //@todo writing a unit test I was unable to create a scenario where this line did not fatal on second
      // and subsequent payments. In this case the line items are created at
      // CRM_Contribute_BAO_ContributionRecur::addRecurLineItems
      // and since the contribution is saved prior to this line there is always a contribution-id,
      // however there is never a prevContribution (which appears to mean original contribution not previous
      // contribution - or preUpdateContributionObject most accurately)
      // so, this is always called & only appears to succeed when prevContribution exists - which appears
      // to mean "are we updating an exisitng pending contribution"
      //I was able to make the unit test complete as fataling here doesn't prevent
      // the contribution being created - but activities would not be created or emails sent

      CRM_Contribute_BAO_Contribution::recordFinancialAccounts($input, NULL);
    }

    CRM_Contribute_BAO_ContributionRecur::updateRecurLinkedPledge($contribution);

    // create an activity record
    if ($input['component'] == 'contribute') {
      //CRM-4027
      $targetContactID = NULL;
      if (!empty($ids['related_contact'])) {
        $targetContactID = $contribution->contact_id;
        $contribution->contact_id = $ids['related_contact'];
      }
      CRM_Activity_BAO_Activity::addActivity($contribution, NULL, $targetContactID);
      // event
    }
    else {
      CRM_Activity_BAO_Activity::addActivity($participant);
    }

    CRM_Core_Error::debug_log_message("Contribution record updated successfully");
    $transaction->commit();

    // CRM-9132 legacy behaviour was that receipts were sent out in all instances. Still sending
    // when array_key 'is_email_receipt doesn't exist in case some instances where is needs setting haven't been set
    if (!array_key_exists('is_email_receipt', $values) ||
      $values['is_email_receipt'] == 1
    ) {
      self::sendMail($input, $ids, $objects, $values, $recur, FALSE);
      CRM_Core_Error::debug_log_message("Receipt sent");
    }

    CRM_Core_Error::debug_log_message("Success: Database updated");
    if ($this->_isRecurring) {
      CRM_Contribute_BAO_ContributionRecur::sendRecurringStartOrEndNotification($ids, $recur,
        $this->_isFirstOrLastRecurringPayment);
    }
  }

  /**
   * Get site billing ID.
   *
   * @param array $ids
   *
   * @return bool
   */
  public function getBillingID(&$ids) {
    // get the billing location type
    $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id', array(), 'validate');
    // CRM-8108 remove the ts around the Billing location type
    //$ids['billing'] =  array_search( ts('Billing'),  $locationTypes );
    $ids['billing'] = array_search('Billing', $locationTypes);
    if (!$ids['billing']) {
      CRM_Core_Error::debug_log_message(ts('Please set a location type of %1', array(1 => 'Billing')));
      echo "Failure: Could not find billing location type<p>";
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Send receipt from contribution.
   *
   * Note that the compose message part has been moved to contribution
   * In general LoadObjects is called first to get the objects but the composeMessageArray function now calls it
   *
   * @param array $input
   *   Incoming data from Payment processor.
   * @param array $ids
   *   Related object IDs.
   * @param $objects
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
    $contribution = &$objects['contribution'];
    $input['is_recur'] = $recur;
    // set receipt from e-mail and name in value
    if (!$returnMessageText) {
      $session = CRM_Core_Session::singleton();
      $userID = $session->get('userID');
      if (!empty($userID)) {
        list($userName, $userEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($userID);
        $values['receipt_from_email'] = CRM_Utils_Array::value('receipt_from_email', $input, $userEmail);
        $values['receipt_from_name'] = CRM_Utils_Array::value('receipt_from_name', $input, $userName);
      }
    }
    return $contribution->composeMessageArray($input, $ids, $values, $recur, $returnMessageText);
  }

}
