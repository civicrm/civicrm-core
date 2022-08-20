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

/**
 * When an activity is created in a case, the "case_activity" email is sent.
 * Generally, the email is sent to the assignee, although (depending on
 * the configuration/add-ons) additional copies may be sent.
 *
 * @see CRM_Case_BAO_Case::sendActivityCopy
 *
 * @support template-only
 */
class CRM_Case_WorkflowMessage_CaseActivity extends GenericWorkflowMessage {

  public const WORKFLOW = 'case_activity';

  /**
   * The activity.
   *
   * @var array|null
   *
   * @scope tokenContext as activity
   */
  public $activity;

  /**
   * @var int
   *
   * @scope tokenContext as activityId
   */
  public $activityID;

  /**
   * @param array $activity
   *
   * @return CRM_Case_WorkflowMessage_CaseActivity
   */
  public function setActivity(array $activity): CRM_Case_WorkflowMessage_CaseActivity {
    $this->activity = $activity;
    if (!empty($activity['id'])) {
      $this->activityID = $activity['id'];
    }
    return $this;
  }

}
