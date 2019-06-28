<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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

  private $basicTokens;
  private $customFieldTokens;

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
   * CRM_Activity_Tokens constructor.
   */
  public function __construct() {
    parent::__construct('activity', array_merge(
      $this->getBasicTokens(),
      $this->getCustomFieldTokens()
    ));
  }

  /**
   * @inheritDoc
   */
  public function checkActive(\Civi\Token\TokenProcessor $processor) {
    return in_array('activityId', $processor->context['schema']) ||
      (!empty($processor->context['actionMapping'])
      && $processor->context['actionMapping']->getEntity() === 'civicrm_activity');
  }

  /**
   * @inheritDoc
   */
  public function getActiveTokens(\Civi\Token\Event\TokenValueEvent $e) {
    $messageTokens = $e->getTokenProcessor()->getMessageTokens();
    if (!isset($messageTokens[$this->entity])) {
      return NULL;
    }

    $activeTokens = [];
    // if message token contains '_\d+_', then treat as '_N_'
    foreach ($messageTokens[$this->entity] as $msgToken) {
      if (array_key_exists($msgToken, $this->tokenNames)) {
        $activeTokens[] = $msgToken;
      }
      else {
        $altToken = preg_replace('/_\d+_/', '_N_', $msgToken);
        if (array_key_exists($altToken, $this->tokenNames)) {
          $activeTokens[] = $msgToken;
        }
      }
    }
    return array_unique($activeTokens);
  }

  /**
   * @inheritDoc
   */
  public function alterActionScheduleQuery(\Civi\ActionSchedule\Event\MailingQueryEvent $e) {
    if ($e->mapping->getEntity() !== 'civicrm_activity') {
      return;
    }

    // The joint expression for activities needs some extra nuance to handle.
    // Multiple revisions of the activity.
    // Q: Could we simplify & move the extra AND clauses into `where(...)`?
    $e->query->param('casEntityJoinExpr', 'e.id = reminder.entity_id AND e.is_current_revision = 1 AND e.is_deleted = 0');
  }

  /**
   * Find the fields that we need to get to construct the tokens requested.
   * @param  array $tokens list of tokens
   * @return array         list of fields needed to generate those tokens
   */
  public function getReturnFields($tokens) {
    // Make sure we always return something
    $fields = ['id'];

    foreach (array_intersect($tokens,
      array_merge(array_keys(self::getBasicTokens()), array_keys(self::getCustomFieldTokens()))
      ) as $token) {
      if (isset(self::$fieldMapping[$token])) {
        $fields = array_merge($fields, self::$fieldMapping[$token]);
      }
      else {
        $fields[] = $token;
      }
    }
    return array_unique($fields);
  }

  /**
   * @inheritDoc
   */
  public function prefetch(\Civi\Token\Event\TokenValueEvent $e) {
    // Find all the activity IDs
    $activityIds
      = $e->getTokenProcessor()->getContextValues('actionSearchResult', 'entityID')
      + $e->getTokenProcessor()->getContextValues('activityId');

    if (!$activityIds) {
      return;
    }

    // Get data on all activities for basic and customfield tokens
    $activities = civicrm_api3('Activity', 'get', [
      'id' => ['IN' => $activityIds],
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
    $activityId = isset($row->context['actionSearchResult']->entityID)
      ? $row->context['actionSearchResult']->entityID
      : $row->context['activityId'];

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

  /**
   * Get the tokens for custom fields
   * @return array token name => token label
   */
  protected function getCustomFieldTokens() {
    if (!isset($this->customFieldTokens)) {
      $this->customFieldTokens = \CRM_Utils_Token::getCustomFieldTokens('Activity');
    }
    return $this->customFieldTokens;
  }

}
