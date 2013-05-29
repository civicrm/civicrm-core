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
class CRM_Core_Payment_BaseIPN {

  static $_now = NULL;
  function __construct() {
    self::$_now = date('YmdHis');
  }

  function validateData(&$input, &$ids, &$objects, $required = TRUE, $paymentProcessorID = NULL) {

    // make sure contact exists and is valid
    $contact = new CRM_Contact_DAO_Contact();
    $contact->id = $ids['contact'];
    if (!$contact->find(TRUE)) {
      CRM_Core_Error::debug_log_message("Could not find contact record: {$ids['contact']} in IPN request: ".print_r($input, TRUE));
      echo "Failure: Could not find contact record: {$ids['contact']}<p>";
      return FALSE;
    }

    // make sure contribution exists and is valid
    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->id = $ids['contribution'];
    if (!$contribution->find(TRUE)) {
      CRM_Core_Error::debug_log_message("Could not find contribution record: {$contribution->id} in IPN request: ".print_r($input, TRUE));
      echo "Failure: Could not find contribution record for {$contribution->id}<p>";
      return FALSE;
    }
    $contribution->receive_date = CRM_Utils_Date::isoToMysql($contribution->receive_date);

    $objects['contact'] = &$contact;
    $objects['contribution'] = &$contribution;
    if (!$this->loadObjects($input, $ids, $objects, $required, $paymentProcessorID)) {
      return FALSE;
    }

    return TRUE;
  }

  function createContact(&$input, &$ids, &$objects) {
    $params    = array();
    $billingID = $ids['billing'];
    $lookup    = array(
      'first_name',
      'last_name',
      "street_address-{$billingID}",
      "city-{$billingID}",
      "state-{$billingID}",
      "postal_code-{$billingID}",
      "country-{$billingID}",
    );
    foreach ($lookup as $name) {
      $params[$name] = $input[$name];
    }
    if (!empty($params)) {
      // update contact record
      $contact = CRM_Contact_BAO_Contact::createProfileContact($params, CRM_Core_DAO::$_nullArray, $ids['contact']);
    }

    return TRUE;
  }

  /*
   * Load objects related to contribution
   *
   * @input array information from Payment processor
   */
  function loadObjects(&$input, &$ids, &$objects, $required, $paymentProcessorID, $error_handling = NULL) {
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
    catch(Exception$e) {
      if (CRM_Utils_Array::value('log_error', $error_handling)) {
        CRM_Core_Error::debug_log_message($e->getMessage());
      }
      if (CRM_Utils_Array::value('echo_error', $error_handling)) {
        echo ($e->getMessage());
      }
      if (CRM_Utils_Array::value('return_error', $error_handling)) {
        return array(
          'is_error' => 1,
          'error_message' => ($e->getMessage()),
        );
      }
    }
    $objects = array_merge($objects, $contribution->_relatedObjects);
    return $success;
  }

  function failed(&$objects, &$transaction, $input = array()) {
    $contribution = &$objects['contribution'];
    $memberships = array();
    if (CRM_Utils_Array::value('membership', $objects)) {
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

    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $contribution->contribution_status_id = array_search('Failed', $contributionStatus);
    $contribution->save();

    //add lineitems for recurring payments
    if (CRM_Utils_Array::value('contributionRecur', $objects) && $objects['contributionRecur']->id && $addLineItems) {
      $this->addrecurLineItems($objects['contributionRecur']->id, $contribution->id);
    }

    if (!CRM_Utils_Array::value('skipComponentSync', $input)) {
      if (!empty($memberships)) {
        foreach ($memberships as $membership) {
          if ($membership) {
            $membership->status_id = 4;
            $membership->save();

            //update related Memberships.
            $params = array('status_id' => 4);
            CRM_Member_BAO_Membership::updateRelatedMemberships($membership->id, $params);
          }
        }
      }

      if ($participant) {
        $participant->status_id = 4;
        $participant->save();
      }
    }

    $transaction->commit();
    CRM_Core_Error::debug_log_message("Setting contribution status to failed");
    //echo "Success: Setting contribution status to failed<p>";
    return TRUE;
  }

  function pending(&$objects, &$transaction) {
    $transaction->commit();
    CRM_Core_Error::debug_log_message("returning since contribution status is pending");
    echo "Success: Returning since contribution status is pending<p>";
    return TRUE;
  }

  function cancelled(&$objects, &$transaction, $input = array()) {
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
    $contribution->contribution_status_id = 3;
    $contribution->cancel_date = self::$_now;
    $contribution->cancel_reason = CRM_Utils_Array::value('reasonCode', $input);
    $contribution->receive_date = CRM_Utils_Date::isoToMysql($contribution->receive_date);
    $contribution->receipt_date = CRM_Utils_Date::isoToMysql($contribution->receipt_date);
    $contribution->thankyou_date = CRM_Utils_Date::isoToMysql($contribution->thankyou_date);
    $contribution->save();

    //add lineitems for recurring payments
    if (CRM_Utils_Array::value('contributionRecur', $objects) && $objects['contributionRecur']->id && $addLineItems) {
      $this->addrecurLineItems($objects['contributionRecur']->id, $contribution->id);
    }

    if (!CRM_Utils_Array::value('skipComponentSync', $input)) {
      if (!empty($memberships)) {
        foreach ($memberships as $membership) {
          if ($membership) {
            $membership->status_id = 6;
            $membership->save();

            //update related Memberships.
            $params = array('status_id' => 6);
            CRM_Member_BAO_Membership::updateRelatedMemberships($membership->id, $params);
          }
        }
      }

      if ($participant) {
        $participant->status_id = 4;
        $participant->save();
      }
    }
    $transaction->commit();
    CRM_Core_Error::debug_log_message("Setting contribution status to cancelled");
    //echo "Success: Setting contribution status to cancelled<p>";
    return TRUE;
  }

  function unhandled(&$objects, &$transaction) {
    $transaction->rollback();
    // we dont handle this as yet
    CRM_Core_Error::debug_log_message("returning since contribution status: $status is not handled");
    echo "Failure: contribution status $status is not handled<p>";
    return FALSE;
  }

  function completeTransaction(&$input, &$ids, &$objects, &$transaction, $recur = FALSE) {
    $contribution = &$objects['contribution'];
    $memberships = &$objects['membership'];
    if (is_numeric($memberships)) {
      $memberships = array($objects['membership']);
    }
    $participant  = &$objects['participant'];
    $event        = &$objects['event'];
    $changeToday  = CRM_Utils_Array::value('trxn_date', $input, self::$_now);
    $recurContrib = &$objects['contributionRecur'];

    $values = array();
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
        $values['is_email_receipt'] = $recurContrib->is_email_receipt;
        $domainValues = CRM_Core_BAO_Domain::getNameAndEmail();
        $values['receipt_from_name'] = $domainValues[0];
        $values['receipt_from_email'] = $domainValues[1];
      }

      $contribution->source = $source;
      if (CRM_Utils_Array::value('is_email_receipt', $values)) {
        $contribution->receipt_date = self::$_now;
      }

      if (!empty($memberships)) {
        $membershipsUpdate = array( );
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
            $dao = new CRM_Core_DAO;
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

            if ($currentMembership) {
              /*
               * Fixed FOR CRM-4433
               * In BAO/Membership.php(renewMembership function), we skip the extend membership date and status
               * when Contribution mode is notify and membership is for renewal )
               */
              CRM_Member_BAO_Membership::fixMembershipStatusBeforeRenew($currentMembership, $changeToday);

              $dates = CRM_Member_BAO_MembershipType::getRenewalDatesForMembershipType($membership->id,
                $changeToday
              );
              $dates['join_date'] = CRM_Utils_Date::customFormat($currentMembership['join_date'], $format);
            }
            else {
              $dates = CRM_Member_BAO_MembershipType::getDatesForMembershipType($membership->membership_type_id);
            }

            //get the status for membership.
            $calcStatus = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($dates['start_date'],
              $dates['end_date'],
              $dates['join_date'],
              'today',
              TRUE
            );

            $formatedParams = array('status_id' => CRM_Utils_Array::value('id', $calcStatus, 2),
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
            if (CRM_Utils_Array::value('log_start_date', $dates)) {
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
        'entity_id'    => $ids['event'],
        'module'       => 'CiviEvent',
      );

      list($custom_pre_id,
           $custom_post_ids
           ) = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);

      $values['custom_pre_id'] = $custom_pre_id;
      $values['custom_post_id'] = $custom_post_ids;

      $contribution->source = ts('Online Event Registration') . ': ' . $values['event']['title'];

      if ($values['event']['is_email_confirm']) {
        $contribution->receipt_date = self::$_now;
        $values['is_email_receipt'] = 1;
      }
      if (!CRM_Utils_Array::value('skipComponentSync', $input)) {
        $participant->status_id = 1;
      }
      $participant->save();
    }

    if (CRM_Utils_Array::value('net_amount', $input, 0) == 0 &&
      CRM_Utils_Array::value('fee_amount', $input, 0) != 0
    ) {
      $input['net_amount'] = $input['amount'] - $input['fee_amount'];
    }
    $addLineItems = FALSE;
    if (empty($contribution->id)) {
      $addLineItems = TRUE;
    }

    $contribution->contribution_status_id = 1;
    $contribution->is_test = $input['is_test'];
    $contribution->fee_amount = CRM_Utils_Array::value('fee_amount', $input, 0);
    $contribution->net_amount = CRM_Utils_Array::value('net_amount', $input, 0);
    $contribution->trxn_id = $input['trxn_id'];
    $contribution->receive_date = CRM_Utils_Date::isoToMysql($contribution->receive_date);
    $contribution->thankyou_date = CRM_Utils_Date::isoToMysql($contribution->thankyou_date);
    $contribution->cancel_date = 'null';

    if (CRM_Utils_Array::value('check_number', $input)) {
      $contribution->check_number = $input['check_number'];
    }

    if (CRM_Utils_Array::value('payment_instrument_id', $input)) {
      $contribution->payment_instrument_id = $input['payment_instrument_id'];
    }

    if ($contribution->id) {
      $contributionId['id'] = $contribution->id;
      $input['prevContribution'] = CRM_Contribute_BAO_Contribution::getValues($contributionId, CRM_Core_DAO::$_nullArray, CRM_Core_DAO::$_nullArray);
    }
    $contribution->save();

    //add lineitems for recurring payments
    if (CRM_Utils_Array::value('contributionRecur', $objects) && $objects['contributionRecur']->id && $addLineItems) {
      $this->addrecurLineItems($objects['contributionRecur']->id, $contribution->id);
    }

    // next create the transaction record
    $paymentProcessor = $paymentProcessorId = '';
    if (isset($objects['paymentProcessor'])) {
      if (is_array($objects['paymentProcessor'])) {
        $paymentProcessor = $objects['paymentProcessor']['payment_processor_type'];
        $paymentProcessorId = $objects['paymentProcessor']['id'];
      }
      else {
        $paymentProcessor = $objects['paymentProcessor']->payment_processor_type;
        $paymentProcessorId = $objects['paymentProcessor']->id;
      }
    }

    if ($contribution->id) {
      $contributionStatuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
      if (!$input['prevContribution']->is_pay_later &&
        $input['prevContribution']->contribution_status_id == array_search('Pending', $contributionStatuses)) {
        $input['payment_processor'] = $paymentProcessorId;
      }
      $input['contribution_status_id'] = array_search('Completed', $contributionStatuses);
      $input['total_amount'] = $input['amount'];
      $input['contribution'] = $contribution;
      $input['financial_type_id'] = $contribution->financial_type_id;

      if (CRM_Utils_Array::value('participant', $contribution->_relatedObjects)) {
        $input['contribution_mode'] = 'participant';
        $input['participant_id'] = $contribution->_relatedObjects['participant']->id;
        $input['skipLineItem'] = 1;
      }
      
      CRM_Contribute_BAO_Contribution::recordFinancialAccounts($input, NULL);
    }

    self::updateRecurLinkedPledge($contribution);

    // create an activity record
    if ($input['component'] == 'contribute') {
      //CRM-4027
      $targetContactID = NULL;
      if (CRM_Utils_Array::value('related_contact', $ids)) {
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
  }

  function getBillingID(&$ids) {
    // get the billing location type
    $locationTypes = CRM_Core_PseudoConstant::locationType();
    // CRM-8108 remove the ts around the Billing locationtype
    //$ids['billing'] =  array_search( ts('Billing'),  $locationTypes );
    $ids['billing'] = array_search('Billing', $locationTypes);
    if (!$ids['billing']) {
      CRM_Core_Error::debug_log_message(ts('Please set a location type of %1', array(1 => 'Billing')));
      echo "Failure: Could not find billing location type<p>";
      return FALSE;
    }
    return TRUE;
  }

  /*
   * Send receipt from contribution. Note that the compose message part has been moved to contribution
   * In general LoadObjects is called first to get the objects but the composeMessageArray function now calls it
   *
   * @params array $input Incoming data from Payment processor
   * @params array $ids Related object IDs
   * @params array $values values related to objects that have already been loaded
   * @params bool $recur is it part of a recurring contribution
   * @params bool $returnMessageText Should text be returned instead of sent. This
   * is because the function is also used to generate pdfs
   */
  function sendMail(&$input, &$ids, &$objects, &$values, $recur = FALSE, $returnMessageText = FALSE) {
    $contribution = &$objects['contribution'];
    $input['is_recur'] = $recur;
    // set receipt from e-mail and name in value
    if (!$returnMessageText) {
      $session = CRM_Core_Session::singleton();
      $userID = $session->get('userID');
      if (!empty($userID)) {
        list($userName, $userEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($userID);
        $values['receipt_from_email'] = $userEmail;
        $values['receipt_from_name'] = $userName;
      }
    }
    return $contribution->composeMessageArray($input, $ids, $values, $recur, $returnMessageText);
  }

  function updateContributionStatus(&$params) {
    // get minimum required values.
    $statusId       = CRM_Utils_Array::value('contribution_status_id', $params);
    $componentId    = CRM_Utils_Array::value('component_id', $params);
    $componentName  = CRM_Utils_Array::value('componentName', $params);
    $contributionId = CRM_Utils_Array::value('contribution_id', $params);

    if (!$contributionId || !$componentId || !$componentName || !$statusId) {
      return;
    }

    $input = $ids = $objects = array();

    //get the required ids.
    $ids['contribution'] = $contributionId;

    if (!$ids['contact'] = CRM_Utils_Array::value('contact_id', $params)) {
      $ids['contact'] = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution',
        $contributionId,
        'contact_id'
      );
    }

    if ($componentName == 'Event') {
      $name = 'event';
      $ids['participant'] = $componentId;

      if (!$ids['event'] = CRM_Utils_Array::value('event_id', $params)) {
        $ids['event'] = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant',
          $componentId,
          'event_id'
        );
      }
    }

    if ($componentName == 'Membership') {
      $name = 'contribute';
      $ids['membership'] = $componentId;
    }
    $ids['contributionPage'] = NULL;
    $ids['contributionRecur'] = NULL;
    $input['component'] = $name;

    $baseIPN = new CRM_Core_Payment_BaseIPN();
    $transaction = new CRM_Core_Transaction();

    // reset template values.
    $template = CRM_Core_Smarty::singleton();
    $template->clearTemplateVars();

    if (!$baseIPN->validateData($input, $ids, $objects, FALSE)) {
      CRM_Core_Error::fatal();
    }

    $contribution = &$objects['contribution'];

    $contributionStatuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $input['skipComponentSync'] = CRM_Utils_Array::value('skipComponentSync', $params);
    if ($statusId == array_search('Cancelled', $contributionStatuses)) {
      $baseIPN->cancelled($objects, $transaction, $input);
      $transaction->commit();
      return $statusId;
    }
    elseif ($statusId == array_search('Failed', $contributionStatuses)) {
      $baseIPN->failed($objects, $transaction, $input);
      $transaction->commit();
      return $statusId;
    }

    // status is not pending
    if ($contribution->contribution_status_id != array_search('Pending', $contributionStatuses)) {
      $transaction->commit();
      return;
    }

    //set values for ipn code.
    foreach (array(
      'fee_amount', 'check_number', 'payment_instrument_id') as $field) {
      if (!$input[$field] = CRM_Utils_Array::value($field, $params)) {
        $input[$field] = $contribution->$field;
      }
    }
    if (!$input['trxn_id'] = CRM_Utils_Array::value('trxn_id', $params)) {
      $input['trxn_id'] = $contribution->invoice_id;
    }
    if (!$input['amount'] = CRM_Utils_Array::value('total_amount', $params)) {
      $input['amount'] = $contribution->total_amount;
    }
    $input['is_test'] = $contribution->is_test;
    $input['net_amount'] = $contribution->net_amount;
    if (CRM_Utils_Array::value('fee_amount', $input) && CRM_Utils_Array::value('amount', $input)) {
      $input['net_amount'] = $input['amount'] - $input['fee_amount'];
    }

    //complete the contribution.
    $baseIPN->completeTransaction($input, $ids, $objects, $transaction, FALSE);

    // reset template values before processing next transactions
    $template->clearTemplateVars();

    return $statusId;
  }

  /*
   * Update pledge associated with a recurring contribution
   *
   * If the contribution has a pledge_payment record pledge, then update the pledge_payment record & pledge based on that linkage.
   *
   * If a previous contribution in the recurring contribution sequence is linked with a pledge then we assume this contribution
   * should be  linked with the same pledge also. Currently only back-office users can apply a recurring payment to a pledge &
   * it should be assumed they
   * do so with the intention that all payments will be linked
   *
   * The pledge payment record should already exist & will need to be updated with the new contribution ID.
   * If not the contribution will also need to be linked to the pledge
   */
  function updateRecurLinkedPledge(&$contribution) {
    $returnProperties = array('id', 'pledge_id');
    $paymentDetails   = $paymentIDs = array();

    if (CRM_Core_DAO::commonRetrieveAll('CRM_Pledge_DAO_PledgePayment', 'contribution_id', $contribution->id,
        $paymentDetails, $returnProperties
      )) {
      foreach ($paymentDetails as $key => $value) {
        $paymentIDs[] = $value['id'];
        $pledgeId = $value['pledge_id'];
      }
    }
    else {
      //payment is not already linked - if it is linked with a pledge we need to create a link.
      // return if it is not recurring contribution
      if (!$contribution->contribution_recur_id) {
        return;
      }

      $relatedContributions = new CRM_Contribute_DAO_Contribution();
      $relatedContributions->contribution_recur_id = $contribution->contribution_recur_id;
      $relatedContributions->find();

      while ($relatedContributions->fetch()) {
        CRM_Core_DAO::commonRetrieveAll('CRM_Pledge_DAO_PledgePayment', 'contribution_id', $relatedContributions->id,
          $paymentDetails, $returnProperties
        );
      }

      if (empty($paymentDetails)) {
        // payment is not linked with a pledge and neither are any other contributions on this
        return;
      }

      foreach ($paymentDetails as $key => $value) {
        $pledgeId = $value['pledge_id'];
      }

      // we have a pledge now we need to get the oldest unpaid payment
      $paymentDetails = CRM_Pledge_BAO_PledgePayment::getOldestPledgePayment($pledgeId);
      if(empty($paymentDetails['id'])){
        // we can assume this pledge is now completed
        // return now so we don't create a core error & roll back
        return;
      }
      $paymentDetails['contribution_id'] = $contribution->id;
      $paymentDetails['status_id'] = $contribution->contribution_status_id;
      $paymentDetails['actual_amount'] = $contribution->total_amount;

      // put contribution against it
      $payment = CRM_Pledge_BAO_PledgePayment::add($paymentDetails);
      $paymentIDs[] = $payment->id;
    }

    // update pledge and corresponding payment statuses
    CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($pledgeId, $paymentIDs, $contribution->contribution_status_id,
      NULL, $contribution->total_amount
    );
  }

  function addrecurLineItems($recurId, $contributionId) {
    $lineSets = $lineItems = array();

    //Get the first contribution id with recur id
    if ($recurId) {
      $contriID = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $recurId, 'id', 'contribution_recur_id');
      $lineItems = CRM_Price_BAO_LineItem::getLineItems($contriID, 'contribution');
      if (!empty($lineItems)) {
        foreach ($lineItems as $key => $value) {
          $pricesetID = new CRM_Price_DAO_Field();
          $pricesetID->id = $value['price_field_id'];
          $pricesetID->find(TRUE);
          $lineSets[$pricesetID->price_set_id][] = $value;
        }
      }

      CRM_Price_BAO_LineItem::processPriceSet($contributionId, $lineSets);
    }
  }
}

