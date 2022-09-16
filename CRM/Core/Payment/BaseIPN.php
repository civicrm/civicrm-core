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
 * Class CRM_Core_Payment_BaseIPN.
 */
class CRM_Core_Payment_BaseIPN {

  public static $_now = NULL;

  /**
   * Input parameters from payment processor. Store these so that
   * the code does not need to keep retrieving from the http request
   * @var array
   */
  protected $_inputParameters = [];

  /**
   * Only used by AuthorizeNetIPN.
   * @var bool
   *
   * @deprecated
   *
   */
  protected $_isRecurring = FALSE;

  /**
   * Only used by AuthorizeNetIPN.
   * @var bool
   *
   * @deprecated
   *
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
   * Set contribution to failed.
   *
   * @param array $objects
   *
   * @deprecated use the api.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public function failed($objects) {
    CRM_Core_Error::deprecatedFunctionWarning('use the api');
    $contribution = &$objects['contribution'];
    $memberships = [];
    if (!empty($objects['membership'])) {
      $memberships = &$objects['membership'];
      if (is_numeric($memberships)) {
        $memberships = [$objects['membership']];
      }
    }

    $addLineItems = empty($contribution->id);
    $participant = &$objects['participant'];
    $contribution->contribution_status_id = CRM_Core_PseudoConstant::getKey('CRM_Contribute_DAO_Contribution', 'contribution_status_id', 'Failed');
    $contribution->save();

    // Add line items for recurring payments.
    if (!empty($objects['contributionRecur']) && $objects['contributionRecur']->id && $addLineItems) {
      CRM_Contribute_BAO_ContributionRecur::addRecurLineItems($objects['contributionRecur']->id, $contribution);
    }

    if (!empty($memberships)) {
      foreach ($memberships as $membership) {
        // @fixme Should we cancel only Pending memberships? per cancelled()
        $this->cancelMembership($membership, $membership->status_id, FALSE);
      }
    }

    if ($participant) {
      $this->cancelParticipant($participant->id);
    }

    Civi::log()->debug("Setting contribution status to Failed");
    return TRUE;
  }

  /**
   * Handled pending contribution status.
   *
   * @deprecated
   *
   * @param array $objects
   * @param object $transaction
   *
   * @return bool
   */
  public function pending(&$objects, &$transaction) {
    CRM_Core_Error::deprecatedFunctionWarning('This function will be removed at some point');
    $transaction->commit();
    Civi::log()->debug('Returning since contribution status is Pending');
    echo 'Success: Returning since contribution status is pending<p>';
    return TRUE;
  }

  /**
   * Process cancelled payment outcome.
   *
   * @deprecated The intended replacement code is
   *
   * Contribution::update(FALSE)->setValues([
   *  'cancel_date' => 'now',
   *  'contribution_status_id:name' => 'Cancelled',
   * ])->addWhere('id', '=', $contribution->id)->execute();
   *
   * @param array $objects
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public function cancelled($objects) {
    CRM_Core_Error::deprecatedFunctionWarning('Use Contribution create api to cancel the contribution');
    $contribution = &$objects['contribution'];

    if (empty($contribution->id)) {
      // This code is believed to be unreachable.
      // this entire function is due to be deprecated in the near future so
      // this code will live in a deprecated function until it gets removed.
      $addLineItems = TRUE;
      // CRM-15546
      $contributionStatuses = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'contribution_status_id', [
        'labelColumn' => 'name',
        'flip' => 1,
      ]);
      $contribution->contribution_status_id = $contributionStatuses['Cancelled'];
      $contribution->cancel_date = self::$_now;
      $contribution->save();
      // Add line items for recurring payments.
      if (!empty($objects['contributionRecur']) && $objects['contributionRecur']->id && $addLineItems) {
        CRM_Contribute_BAO_ContributionRecur::addRecurLineItems($objects['contributionRecur']->id, $contribution);
      }
      $memberships = [];
      if (!empty($objects['membership'])) {
        $memberships = &$objects['membership'];
        if (is_numeric($memberships)) {
          $memberships = [$objects['membership']];
        }
      }
      if (!empty($memberships)) {
        foreach ($memberships as $membership) {
          if ($membership) {
            $this->cancelMembership($membership, $membership->status_id);
          }
        }
      }
      $participant = &$objects['participant'];

      if ($participant) {
        $this->cancelParticipant($participant->id);
      }
    }
    else {
      Contribution::update(FALSE)->setValues([
        'cancel_date' => 'now',
        'contribution_status_id:name' => 'Cancelled',
      ])->addWhere('id', '=', $contribution->id)->execute();
    }

    Civi::log()->debug("Setting contribution status to Cancelled");
    return TRUE;
  }

  /**
   * Rollback unhandled outcomes.
   *
   * @deprecated
   *
   * @param array $objects
   * @param CRM_Core_Transaction $transaction
   *
   * @return bool
   */
  public function unhandled(&$objects, &$transaction) {
    CRM_Core_Error::deprecatedFunctionWarning('This function will be removed at some point');
    $transaction->rollback();
    Civi::log()->debug('Returning since contribution status is not handled');
    echo 'Failure: contribution status is not handled<p>';
    return FALSE;
  }

  /**
   * Logic to cancel a participant record when the related contribution changes to failed/cancelled.
   * @todo This is part of a bigger refactor for dev/core/issues/927 - "duplicate" functionality exists in CRM_Contribute_BAO_Contribution::cancel()
   *
   * @deprecated
   *
   * @param $participantID
   *
   * @throws \CRM_Core_Exception
   */
  private function cancelParticipant($participantID) {
    // @fixme https://lab.civicrm.org/dev/core/issues/927 Cancelling membership etc is not desirable for all use-cases and we should be able to disable it
    $participantParams['id'] = $participantID;
    $participantParams['status_id'] = 'Cancelled';
    civicrm_api3('Participant', 'create', $participantParams);
  }

  /**
   * Logic to cancel a membership record when the related contribution changes to failed/cancelled.
   * @todo This is part of a bigger refactor for dev/core/issues/927 - "duplicate" functionality exists in CRM_Contribute_BAO_Contribution::cancel()
   * @param \CRM_Member_BAO_Membership $membership
   * @param int $membershipStatusID
   * @param bool $onlyCancelPendingMembership
   *   Do we only cancel pending memberships? OR memberships in any status? (see CRM-18688)
   * @fixme Historically failed() cancelled membership in any status, cancelled() cancelled only pending memberships so we retain that behaviour for now.
   * @deprecated
   */
  private function cancelMembership($membership, $membershipStatusID, $onlyCancelPendingMembership = TRUE) {
    CRM_Core_Error::deprecatedFunctionWarning('use the api');
    // @fixme https://lab.civicrm.org/dev/core/issues/927 Cancelling membership etc is not desirable for all use-cases and we should be able to disable it
    // Cancel only Pending memberships
    $pendingMembershipStatusId = CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Pending');
    if (($membershipStatusID == $pendingMembershipStatusId) || ($onlyCancelPendingMembership == FALSE)) {
      $cancelledMembershipStatusId = CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Cancelled');

      $membership->status_id = $cancelledMembershipStatusId;
      $membership->save();

      $params = ['status_id' => $cancelledMembershipStatusId];
      CRM_Member_BAO_Membership::updateRelatedMemberships($membership->id, $params);

      // @todo Convert the above to API
      // $membershipParams = [
      //   'id' => $membership->id,
      //   'status_id' => $cancelledMembershipStatusId,
      // ];
      // civicrm_api3('Membership', 'create', $membershipParams);
      // CRM_Member_BAO_Membership::updateRelatedMemberships($membershipParams['id'], ['status_id' => $cancelledMembershipStatusId]);
    }

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
   *     catch (CRM_Core_Exception $e) {
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
   *
   * @throws \CRM_Core_Exception
   */
  public function completeTransaction($input, $ids, $objects) {
    CRM_Core_Error::deprecatedFunctionWarning('Use Payment.create api');
    CRM_Contribute_BAO_Contribution::completeOrder($input, !empty($objects['contributionRecur']) ? $objects['contributionRecur']->id : NULL, $objects['contribution']->id ?? NULL);
  }

  /**
   * @deprecated
   * Get site billing ID.
   *
   * @param array $ids
   *
   * @return bool
   */
  public function getBillingID(&$ids) {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Core_BAO_LocationType::getBilling()');
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
   *
   * @throws \CRM_Core_Exception
   */
  public function sendMail($input, $ids, $objects) {
    CRM_Core_Error::deprecatedFunctionWarning('this should be done via completetransaction api');
    civicrm_api3('Contribution', 'sendconfirmation', [
      'id' => $objects['contribution']->id,
    ]);
  }

}
