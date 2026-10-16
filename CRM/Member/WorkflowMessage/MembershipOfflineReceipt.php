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
 * Receipt sent when confirming a back office membership.
 *
 * @support template-only
 *
 * @see CRM_Member_Form_MembershipRenewal::sendReceipt
 * @see CRM_Member_Form_Membership::emailReceipt
 * @see CRM_Batch_Form_Entry::emailReceipt
 */
class CRM_Member_WorkflowMessage_MembershipOfflineReceipt extends GenericWorkflowMessage {
  use CRM_Member_WorkflowMessage_MembershipTrait;
  use CRM_Contribute_WorkflowMessage_ContributionTrait;
  use CustomFieldTrait;

  public const WORKFLOW = 'membership_offline_receipt';

  /**
   * @var array
   *
   * @scope tplParams
   */
  protected $customValues;

  /**
   * Get custom values for display.
   *
   * The template renders this as a flat list of label => value, with no
   * grouping by custom group - so flatten getCustomFieldDisplay()'s
   * per-group arrays into one.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getCustomValues(): array {
    return array_merge([], ...array_values($this->getCustomFieldDisplay($this->getMembership(), 'Membership')));
  }

  /**
   * Get the membership fields we need to load.
   *
   * @throws \CRM_Core_Exception
   */
  protected function getAdditionalFieldsToLoadForMembership(): array {
    // Request the relevant custom fields. This list is
    // restricted by view-ability, but we don't have the information
    // at this point to filter by the finer tuned entity extends information
    // which relies on us knowing type
    $fields = [];
    foreach ($this->getFilteredCustomFields('Membership') as $field) {
      $fields[] = $field['custom_group_id.name'] . '.' . $field['name'];
    }
    return $fields;
  }

}
