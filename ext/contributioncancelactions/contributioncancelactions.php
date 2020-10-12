<?php

require_once 'contributioncancelactions.civix.php';
// phpcs:disable
use CRM_Contributioncancelactions_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_post
 */
function contributioncancelactions_civicrm_post($op, $objectName, $objectId, $objectRef) {
  if ($op === 'edit' && $objectName === 'Contribution') {
    if ($objectRef->contribution_status_id === CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Cancelled')) {
      // Move all this code here from Contribution::cancel - reconcile with the other places in the
      // code that do the same.
      // Also move in & reconcile failed code.
      // This is pseudocode - will require more work!!
      $processContribution = FALSE;

      $participantStatuses = CRM_Event_PseudoConstant::participantStatus();
      if (is_array($memberships)) {
        foreach ($memberships as $membership) {
          $update = TRUE;
          //Update Membership status if there is no other completed contribution associated with the membership.
          $relatedContributions = CRM_Member_BAO_Membership::getMembershipContributionId($membership->id, TRUE);
          foreach ($relatedContributions as $contriId) {
            if ($contriId == $contributionId) {
              continue;
            }
            $statusId = CRM_Core_DAO::getFieldValue('CRM_Contribute_BAO_Contribution', $contriId, 'contribution_status_id');
            if (CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $statusId) === 'Completed') {
              $update = FALSE;
            }
          }
          if ($membership && $update) {
            $newStatus = array_search('Cancelled', $membershipStatuses);

            // Create activity
            $allStatus = CRM_Member_BAO_Membership::buildOptions('status_id', 'get');
            $activityParam = [
              'subject' => "Status changed from {$allStatus[$membership->status_id]} to {$allStatus[$newStatus]}",
              'source_contact_id' => CRM_Core_Session::singleton()->get('userID'),
              'target_contact_id' => $membership->contact_id,
              'source_record_id' => $membership->id,
              'activity_type_id' => 'Change Membership Status',
              'status_id' => 'Completed',
              'priority_id' => 'Normal',
              'activity_date_time' => 'now',
            ];

            $membership->status_id = $newStatus;
            $membership->is_override = TRUE;
            $membership->status_override_end_date = 'null';
            $membership->save();
            civicrm_api3('activity', 'create', $activityParam);

            $updateResult['updatedComponents']['CiviMember'] = $membership->status_id;
            if ($processContributionObject) {
              $processContribution = TRUE;
            }
          }
        }
      }

      if ($participant) {
        $updatedStatusId = array_search('Cancelled', $participantStatuses);
        CRM_Event_BAO_Participant::updateParticipantStatus($participant->id, $oldStatus, $updatedStatusId, TRUE);

        $updateResult['updatedComponents']['CiviEvent'] = $updatedStatusId;
        if ($processContributionObject) {
          $processContribution = TRUE;
        }
      }

      if ($pledgePayment) {
        CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($pledgeID, $pledgePaymentIDs, $contributionStatusId);

        $updateResult['updatedComponents']['CiviPledge'] = $contributionStatusId;
        if ($processContributionObject) {
          $processContribution = TRUE;
        }
      }
    }
  }
}
