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

use Civi\WorkflowMessage\GenericWorkflowMessage;
use Civi\WorkflowMessage\Traits\CustomFieldTrait;

/**
 * Receipt sent when confirming a back office participation record.
 *
 * @support template-only
 *
 * @see CRM_Event_Form_Participant::submit()
 * @see CRM_Event_Form_ParticipantFeeSelection::emailReceipt
 */
class CRM_Event_WorkflowMessage_EventOfflineReceipt extends GenericWorkflowMessage {
  use CRM_Event_WorkflowMessage_ParticipantTrait;
  use CRM_Contribute_WorkflowMessage_ContributionTrait;
  use CustomFieldTrait;

  public const WORKFLOW = 'event_offline_receipt';

  /**
   * Viewable custom fields for the primary participant.
   *
   * This array is in the format
   *
   * ['customGroupLabel' => [['customFieldLabel' => 'customFieldValue'], ['customFieldLabel' => 'customFieldValue']]
   *
   * It is only added for the primary participant (which reflects historical
   * form behaviour) and only fields were the group has is_public = TRUE
   * and the field has is_view = FALSE. Fields are restricted to
   * those viewable by the logged in user (reflecting the fact this
   * is historically triggered by a back office user form submission
   * and also preventing using an email to see acl-blocked custom fields).
   *
   * @var array
   *
   * @scope tplParams as customGroup
   */
  public $customFields;

  /**
   * Get the custom fields for display for the participant, if not primary.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getCustomFields(): array {
    // Non-primary custom field info can't be gathered on the back office
    // form so historically it has not shown up. This keeps that behaviour
    // (although a future person could probably change it if they wanted
    // to think through any potential downsides.
    if (!$this->getIsPrimary()) {
      return [];
    }
    $participant = $this->getParticipant();
    // We re-filter the custom fields to eliminate any custom groups
    // not associated with the role, event_id etc. Realistically participants
    // should not have such data. But, out of caution we do this becasue
    // historical code did.
    $filters = [
      'ParticipantRole' => $participant['role_id'],
      'ParticipantEventName' => $participant['event_id'],
      'ParticipantEventType' => $participant['event_id.event_type_id'],
    ];
    return $this->getCustomFieldDisplay($participant, 'Participant', $filters);
  }

  /**
   * Get the participant fields we need to load.
   *
   * @throws \CRM_Core_Exception
   */
  protected function getFieldsToLoadForParticipant(): array {
    $fields = ['registered_by_id', 'role_id', 'event_id', 'event_id.event_type_id', 'contact_id'];
    // Request the relevant custom fields. This list is
    // restricted by view-ability but we don't have the information
    // at this point to filter by the finer tuned entity extends information
    // which relies on us knowing role etc.
    foreach ($this->getFilteredCustomFields('Participant') as $field) {
      $fields[] = $field['custom_group_id.name'] . '.' . $field['name'];
    }
    return $fields;
  }

}
