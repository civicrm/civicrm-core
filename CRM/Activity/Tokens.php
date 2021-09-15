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

use Civi\Token\Event\TokenValueEvent;
use Civi\Token\TokenRow;

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
class CRM_Activity_Tokens extends CRM_Core_EntityTokens {

  use CRM_Core_TokenTrait;

  /**
   * Get the entity name for api v4 calls.
   *
   * @return string
   */
  protected function getApiEntityName(): string {
    return 'Activity';
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
   * Using arrays allows more complex tokens to be handled that require more than one API field.
   * For example, an address token might want ['street_address', 'city', 'postal_code']
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
  public function alterActionScheduleQuery(\Civi\ActionSchedule\Event\MailingQueryEvent $e): void {
    if ($e->mapping->getEntity() !== $this->getEntityTableName()) {
      return;
    }

    // The joint expression for activities needs some extra nuance to handle.
    // Multiple revisions of the activity.
    // Q: Could we simplify & move the extra AND clauses into `where(...)`?
    $e->query->param('casEntityJoinExpr', 'e.id = reminder.entity_id AND e.is_current_revision = 1 AND e.is_deleted = 0');
    $e->query->select('e.id AS tokenContext_' . $this->getEntityContextSchema());
  }

  /**
   * Evaluate the content of a single token.
   *
   * @param \Civi\Token\TokenRow $row
   *   The record for which we want token values.
   * @param string $entity
   *   The name of the token entity.
   * @param string $field
   *   The name of the token field.
   * @param mixed $prefetch
   *   Any data that was returned by the prefetch().
   *
   * @throws \CRM_Core_Exception
   */
  public function evaluateToken(TokenRow $row, $entity, $field, $prefetch = NULL) {
    $activityId = $row->context[$this->getEntityContextSchema()];

    if (!empty($this->getDeprecatedTokens()[$field])) {
      $realField = $this->getDeprecatedTokens()[$field];
      parent::evaluateToken($row, $entity, $realField, $prefetch);
      $row->format('text/plain')->tokens($entity, $field, $row->tokens['activity'][$realField]);
    }
    elseif (in_array($field, ['case_id'])) {
      // An activity can be linked to multiple cases so case_id is always an array.
      // We just return the first case ID for the token.
      // this weird hack might exist because apiv3 is weird &
      $caseID = CRM_Core_DAO::singleValueQuery('SELECT case_id FROM civicrm_case_activity WHERE activity_id = %1 LIMIT 1', [1 => [$activityId, 'Integer']]);
      $row->tokens($entity, $field, $caseID ?? '');
    }
    else {
      parent::evaluateToken($row, $entity, $field, $prefetch);
    }
  }

  /**
   * Get all the tokens supported by this processor.
   *
   * @return array|string[]
   * @throws \API_Exception
   */
  protected function getAllTokens(): array {
    $tokens = parent::getAllTokens();
    if (array_key_exists('CiviCase', CRM_Core_Component::getEnabledComponents())) {
      $tokens['case_id'] = ts('Activity Case ID');
    }
    return $tokens;
  }

  /**
   * Get the basic tokens provided.
   *
   * @return array token name => token label
   */
  public function getBasicTokens(): array {
    if (!isset($this->basicTokens)) {
      $this->basicTokens = [
        'id' => ts('Activity ID'),
        'subject' => ts('Activity Subject'),
        'details' => ts('Activity Details'),
        'activity_date_time' => ts('Activity Date-Time'),
        'created_date' => ts('Activity Created Date'),
        'modified_date' => ts('Activity Modified Date'),
        'activity_type_id' => ts('Activity Type ID'),
        'status_id' => ts('Activity Status ID'),
        'location' => ts('Activity Location'),
        'duration' => ts('Activity Duration'),
      ];
      if (CRM_Campaign_BAO_Campaign::isCampaignEnable()) {
        $this->basicTokens['campaign_id'] = ts('Campaign ID');
      }
    }
    return $this->basicTokens;
  }

  /**
   * @inheritDoc
   */
  public function getActiveTokens(TokenValueEvent $e) {
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
      elseif (in_array($msgToken, ['campaign', 'activity_id', 'status', 'activity_type', 'case_id'])) {
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

  public function getPrefetchFields(TokenValueEvent $e): array {
    $tokens = parent::getPrefetchFields($e);
    $active = $this->getActiveTokens($e);
    foreach ($this->getDeprecatedTokens() as $old => $new) {
      if (in_array($old, $active, TRUE) && !in_array($new, $active, TRUE)) {
        $tokens[] = $new;
      }
    }
    return $tokens;
  }

  /**
   * These tokens still work but we don't advertise them.
   *
   * We will actively remove from the following places
   * - scheduled reminders
   * - add to 'blocked' on pdf letter & email
   *
   * & then at some point start issuing warnings for them.
   *
   * @return string[]
   */
  protected function getDeprecatedTokens(): array {
    return [
      'activity_id' => 'id',
      'activity_type' => 'activity_type_id:label',
      'status' => 'status_id:label',
      'campaign' => 'campaign_id:label',
    ];
  }

}
