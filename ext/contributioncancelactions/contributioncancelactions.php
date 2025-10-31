<?php

require_once 'contributioncancelactions.civix.php';

use Civi\Api4\LineItem;
use Civi\Api4\Participant;

/**
 * Implements hook_civicrm_preProcess().
 *
 * This enacts the following
 * - find and cancel any related pending memberships
 * - (not yet implemented) find and cancel any related pending participant
 * records
 * - (not yet implemented) find any related pledge payment records. Remove the
 * contribution id.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_post
 *
 * @throws \CRM_Core_Exception
 */
function contributioncancelactions_civicrm_post($op, $objectName, $objectId, $objectRef) {
  if ($op === 'edit' && $objectName === 'Contribution'
    && in_array(CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $objectRef->contribution_status_id), ['Cancelled', 'Failed'], TRUE)
  ) {
    contributioncancelactions_cancel_related_pending_memberships((int) $objectId);
    contributioncancelactions_cancel_related_pending_participant_records((int) $objectId);
  }
}

/**
 * Find and cancel any pending participant records.
 *
 * @param int $contributionID
 *
 * @throws CRM_Core_Exception
 */
function contributioncancelactions_cancel_related_pending_participant_records(int $contributionID): void {
  $pendingStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Pending'");
  $waitingStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Waiting'");
  $cancellableParticipantRecords = civicrm_api3('ParticipantPayment', 'get', [
    'contribution_id' => $contributionID,
    'participant_id.status_id' => ['IN' => array_merge(array_keys($pendingStatuses), array_keys($waitingStatuses))],
  ])['values'];
  if (empty($cancellableParticipantRecords)) {
    return;
  }
  foreach ($cancellableParticipantRecords as $record) {
    $participantIDs[] = $record['participant_id'];
  }
  Participant::update(FALSE)
    ->addWhere('id', 'IN', $participantIDs)
    ->setValues(['status_id:name' => 'Cancelled'])
    ->execute();
}

/**
 * Find and cancel any pending memberships.
 *
 * @param int $contributionID
 *
 * @throws CRM_Core_Exception
 * @throws CRM_Core_Exception
 */
function contributioncancelactions_cancel_related_pending_memberships(int $contributionID): void {
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
    civicrm_api3('Membership', 'create', ['status_id' => 'Cancelled', 'id' => $membershipID, 'is_override' => 1, 'status_override_end_date' => 'null']);
  }
}
