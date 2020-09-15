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

/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Class CRM_Member_Tokens
 *
 * Generate "activity.*" tokens.
 *
 * This TokenSubscriber was originally produced by refactoring the code from the
 * scheduled-reminder system with the goal of making that system
 * more flexible. The current implementation is still coupled to
 * scheduled-reminders. It would be good to figure out a more generic
 * implementation which is not tied to scheduled reminders, although
 * that is outside the current scope.
 *
 * This has been enhanced to work with PDF/letter merge
 */
class CRM_Activity_Tokens extends \Civi\Token\AbstractTokenSubscriber {

  use CRM_Core_TokenTrait;

  /**
   * @return string
   */
  private function getEntityName(): string {
    return 'activity';
  }

  /**
   * @return string
   */
  private function getEntityTableName(): string {
    return 'civicrm_activity';
  }

  /**
   * @return string
   */
  private function getEntityContextSchema(): string {
    return 'activityId';
  }

  /**
   * Mapping from tokenName to api return field
   * Use lists since we might need multiple fields
   *
   * @var array
   */
  private static $fieldMapping = [
    'activity_id' => ['id'],
    'activity_type' => ['activity_type_id'],
    'status' => ['status_id'],
    'campaign' => ['campaign_id'],
  ];

  /**
   * @inheritDoc
   */
  public function alterActionScheduleQuery(\Civi\ActionSchedule\Event\MailingQueryEvent $e) {
    if ($e->mapping->getEntity() !== $this->getEntityTableName()) {
      return;
    }

    // The joint expression for activities needs some extra nuance to handle.
    // Multiple revisions of the activity.
    // Q: Could we simplify & move the extra AND clauses into `where(...)`?
    $e->query->param('casEntityJoinExpr', 'e.id = reminder.entity_id AND e.is_current_revision = 1 AND e.is_deleted = 0');
  }

  /**
   * @inheritDoc
   */
  public function prefetch(\Civi\Token\Event\TokenValueEvent $e) {
    // Find all the entity IDs
    $entityIds
      = $e->getTokenProcessor()->getContextValues('actionSearchResult', 'entityID')
      + $e->getTokenProcessor()->getContextValues($this->getEntityContextSchema());

    if (!$entityIds) {
      return NULL;
    }

    // Get data on all activities for basic and customfield tokens
    $activities = civicrm_api3('Activity', 'get', [
      'id' => ['IN' => $entityIds],
      'options' => ['limit' => 0],
      'return' => self::getReturnFields($this->activeTokens),
    ]);
    $prefetch['activity'] = $activities['values'];

    // Store the activity types if needed
    if (in_array('activity_type', $this->activeTokens)) {
      $this->activityTypes = \CRM_Core_OptionGroup::values('activity_type');
    }

    // Store the activity statuses if needed
    if (in_array('status', $this->activeTokens)) {
      $this->activityStatuses = \CRM_Core_OptionGroup::values('activity_status');
    }

    // Store the campaigns if needed
    if (in_array('campaign', $this->activeTokens)) {
      $this->campaigns = \CRM_Campaign_BAO_Campaign::getCampaigns();
    }

    return $prefetch;
  }

  /**
   * @inheritDoc
   */
  public function evaluateToken(\Civi\Token\TokenRow $row, $entity, $field, $prefetch = NULL) {
    // maps token name to api field
    $mapping = [
      'activity_id' => 'id',
    ];

    // Get ActivityID either from actionSearchResult (for scheduled reminders) if exists
    $activityId = $row->context['actionSearchResult']->entityID ?? $row->context[$this->getEntityContextSchema()];

    $activity = (object) $prefetch['activity'][$activityId];

    if (in_array($field, ['activity_date_time', 'created_date'])) {
      $row->tokens($entity, $field, \CRM_Utils_Date::customFormat($activity->$field));
    }
    elseif (isset($mapping[$field]) and (isset($activity->{$mapping[$field]}))) {
      $row->tokens($entity, $field, $activity->{$mapping[$field]});
    }
    elseif (in_array($field, ['activity_type'])) {
      $row->tokens($entity, $field, $this->activityTypes[$activity->activity_type_id]);
    }
    elseif (in_array($field, ['status'])) {
      $row->tokens($entity, $field, $this->activityStatuses[$activity->status_id]);
    }
    elseif (in_array($field, ['campaign'])) {
      $row->tokens($entity, $field, $this->campaigns[$activity->campaign_id]);
    }
    elseif (in_array($field, ['case_id'])) {
      // An activity can be linked to multiple cases so case_id is always an array.
      // We just return the first case ID for the token.
      $row->tokens($entity, $field, is_array($activity->case_id) ? reset($activity->case_id) : $activity->case_id);
    }
    elseif (array_key_exists($field, $this->customFieldTokens)) {
      $row->tokens($entity, $field,
        isset($activity->$field)
          ? \CRM_Core_BAO_CustomField::displayValue($activity->$field, $field)
          : ''
      );
    }
    elseif (isset($activity->$field)) {
      $row->tokens($entity, $field, $activity->$field);
    }
  }

  /**
   * Get the basic tokens provided.
   *
   * @return array token name => token label
   */
  protected function getBasicTokens() {
    if (!isset($this->basicTokens)) {
      $this->basicTokens = [
        'activity_id' => ts('Activity ID'),
        'activity_type' => ts('Activity Type'),
        'subject' => ts('Activity Subject'),
        'details' => ts('Activity Details'),
        'activity_date_time' => ts('Activity Date-Time'),
        'activity_type_id' => ts('Activity Type ID'),
        'status' => ts('Activity Status'),
        'status_id' => ts('Activity Status ID'),
        'location' => ts('Activity Location'),
        'created_date' => ts('Activity Creation Date'),
        'duration' => ts('Activity Duration'),
        'campaign' => ts('Activity Campaign'),
        'campaign_id' => ts('Activity Campaign ID'),
      ];
      if (array_key_exists('CiviCase', CRM_Core_Component::getEnabledComponents())) {
        $this->basicTokens['case_id'] = ts('Activity Case ID');
      }
    }
    return $this->basicTokens;
  }

}
