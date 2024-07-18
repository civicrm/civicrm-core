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

namespace Civi\Api4\Action\Membership;

use Civi\Api4\Generic\ValidateAction;
use Civi\Api4\Event\ValidateValuesEvent;

/**
 * Validate membership parameters before creating/updating Memberships.
 */
class Validate extends ValidateAction {

  protected function onValidateValues(ValidateValuesEvent $e) {
    foreach ($e->records as $recordKey => $record) {
      parent::onValidateValues($e);

      if (empty($record['status_id'])) {
        $e->addError($recordKey, 'status_id', 'empty_status_id', ts('There is no valid Membership Status available for selected membership dates.'));
      }

      if (!empty($record['join_date'])) {
        if (!empty($record['membership_type_id']) && !empty($record['start_date'])) {
          $membershipDetails = \CRM_Member_BAO_MembershipType::getMembershipType($record['membership_type_id']);
          if ($record['start_date'] && ($membershipDetails['period_type'] ?? NULL) === 'rolling') {
            if ($record['start_date'] < $record['join_date']) {
              $e->addError($recordKey, 'start_date', 'incorrect_start_date', 'Start date must be the same or later than Member since.');
            }
          }
        }
        if (!empty($record['end_date'])) {
          if ($membershipDetails['duration_unit'] === 'lifetime') {
            // Check if status is NOT cancelled or similar. For lifetime memberships, there is no automated
            // process to update status based on end-date. The user must change the status now.
            $result = civicrm_api3('MembershipStatus', 'get', [
              'sequential' => 1,
              'is_current_member' => 0,
            ]);
            $tmp_statuses = $result['values'];
            $status_ids = [];
            foreach ($tmp_statuses as $cur_stat) {
              $status_ids[] = $cur_stat['id'];
            }

            if (empty($record['status_id']) || in_array($record['status_id'], $status_ids) == FALSE) {
              $e->addError($recordKey, 'status_id', 'lifetime_membership_error', ts('A current lifetime membership cannot have an end date. You can either remove the end date or change the status to a non-current status like Cancelled, Expired, or Deceased.'));
            }
            if (!empty($record['is_override']) && !\CRM_Member_StatusOverrideTypes::isPermanent($record['is_override'])) {
              $e->addError($recordKey, 'is_override', 'lifetime_membership_error', ts('Because you set an End Date for a lifetime membership, This must be set to "Override Permanently"'));
            }
          }
          else {
            if (!$record['start_date']) {
              $e->addError($recordKey, 'start_date', 'empty_start_date', ts('Start date must be set if end date is set.'));
            }
            if ($record['end_date'] < $record['start_date']) {
              $e->addError($recordKey, 'end_date', 'incorrect_end_date', ts('End date must be the same or later than start date.'));
            }
          }
        }
      }

      if (!empty($record['is_override']) && \CRM_Member_StatusOverrideTypes::isUntilDate($record['is_override'])) {
        if (empty($record['status_override_end_date'])) {
          $e->addError($recordKey, 'status_override_end_date', 'empty_status_override_end_date', ts('Please enter the Membership override end date.'));
        }
      }
      if (empty($record['join_date'])) {
        $e->addError($recordKey, 'join_date', 'empty_join_date', ts('Please enter the Member Since.'));
      }
      if (!empty($record['is_override']) && CRM_Member_StatusOverrideTypes::isOverridden($record['is_override']) && empty($record['status_id'])) {
        $e->addError($recordKey, 'status_id', 'empty_status_id', ts('Please enter the Membership status.'));
      }
    }
  }

}
