<?php

require_once 'contributioncancelactions.civix.php';
// phpcs:disable
use CRM_Contributioncancelactions_ExtensionUtil as E;
// phpcs:enable
use Civi\Api4\LineItem;
use Civi\Api4\Participant;

/**
 * Implements hook_civicrm_preProcess().
 *
 * This enacts the following
 * - find and cancel any related pending memberships
 * - (not yet implemented) find and cancel any related pending participant records
 * - (not yet implemented) find any related pledge payment records. Remove the contribution id.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_post
 */
function contributioncancelactions_civicrm_post($op, $objectName, $objectId, $objectRef) {
  if ($op === 'edit' && $objectName === 'Contribution') {
    if ('Cancelled' === CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $objectRef->contribution_status_id)) {
      contributioncancelactions_cancel_related_pending_memberships((int) $objectId);
      contributioncancelactions_cancel_related_pending_participant_records((int) $objectId);
      contributioncancelactions_update_related_pledge((int) $objectId, (int) $objectRef->contribution_status_id);
    }
  }
}

/**
 * Update any related pledge when a contribution is cancelled.
 *
 * This updates the status of the pledge and amount paid.
 *
 * The functionality should probably be give more thought in that it currently
 * does not un-assign the contribution id from the pledge payment. However,
 * at time of writing the goal is to move rather than fix functionality.
 *
 * @param int $contributionID
 * @param int $contributionStatusID
 *
 * @throws CiviCRM_API3_Exception
 */
function contributioncancelactions_update_related_pledge(int $contributionID, int $contributionStatusID) {
  $pledgePayments = civicrm_api3('PledgePayment', 'get', ['contribution_id' => $contributionID])['values'];
  if (!empty($pledgePayments)) {
    $pledgePaymentIDS = array_keys($pledgePayments);
    $pledgePayment = reset($pledgePayments);
    CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($pledgePayment['pledge_id'], $pledgePaymentIDS, $contributionStatusID);
  }
}

/**
 * Find and cancel any pending participant records.
 *
 * @param int $contributionID
 * @throws CiviCRM_API3_Exception
 */
function contributioncancelactions_cancel_related_pending_participant_records($contributionID): void {
  $pendingStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Pending'");
  $waitingStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Waiting'");
  $cancellableParticipantRecords = civicrm_api3('ParticipantPayment', 'get', [
    'contribution_id' => $contributionID,
    'participant_id.status_id' => ['IN' => array_merge(array_keys($pendingStatuses), array_keys($waitingStatuses))],
  ])['values'];
  if (empty($cancellableParticipantRecords)) {
    return;
  }
  Participant::update(FALSE)
    ->addWhere('id', 'IN', array_keys($cancellableParticipantRecords))
    ->setValues(['status_id:name' => 'Cancelled'])
    ->execute();
}

/**
 * Find and cancel any pending memberships.
 *
 * @param int $contributionID
 * @throws API_Exception
 * @throws CiviCRM_API3_Exception
 */
function contributioncancelactions_cancel_related_pending_memberships($contributionID): void {
  $connectedMemberships = (array) LineItem::get(FALSE)->setWhere([
    ['contribution_id', '=', $contributionID],
    ['entity_table', '=', 'civicrm_membership'],
  ])->execute()->indexBy('entity_id');

  if (empty($connectedMemberships)) {
    return;
  }
  // @todo we don't have v4 membership api yet so v3 for now.
  $connectedMemberships = array_keys(civicrm_api3('Membership', 'get', [
    'status_id' => 'Pending',
    'id' => ['IN' => array_keys($connectedMemberships)],
  ])['values']);
  if (empty($connectedMemberships)) {
    return;
  }
  foreach ($connectedMemberships as $membershipID) {
    civicrm_api3('Membership', 'create', ['status_id' => 'Cancelled', 'id' => $membershipID, 'is_override' => 1]);
  }
}
