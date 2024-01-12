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

use Civi\ActionSchedule\Event\MailingQueryEvent;
use Civi\Token\Event\TokenValueEvent;
use Civi\Token\TokenRow;

/**
 * Generate "activity.*" tokens.
 */
class CRM_Activity_Tokens extends CRM_Core_EntityTokens {

  /**
   * Get the entity name for api v4 calls.
   *
   * @return string
   */
  protected function getApiEntityName(): string {
    return 'Activity';
  }

  /**
   * @inheritDoc
   */
  public function alterActionScheduleQuery(MailingQueryEvent $e): void {
    if ($e->mapping->getEntityTable($e->actionSchedule) !== $this->getExtendableTableName()) {
      return;
    }

    // The joint expression for activities needs some extra nuance to handle.
    // Multiple revisions of the activity.
    // Q: Could we simplify & move the extra AND clauses into `where(...)`?
    $e->query->param('casEntityJoinExpr', 'e.id = reminder.entity_id AND e.is_current_revision = 1 AND e.is_deleted = 0');
    parent::alterActionScheduleQuery($e);
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
    $activityId = $this->getFieldValue($row, 'id');

    if (!empty($this->getDeprecatedTokens()[$field])) {
      $realField = $this->getDeprecatedTokens()[$field];
      parent::evaluateToken($row, $entity, $realField, $prefetch);
      $row->format('text/plain')->tokens($entity, $field, $row->tokens['activity'][$realField]);
    }
    else {
      parent::evaluateToken($row, $entity, $field, $prefetch);
    }
  }

  /**
   * Get fields historically not advertised for tokens.
   *
   * @return string[]
   */
  protected function getSkippedFields(): array {
    return array_merge(parent::getSkippedFields(), [
      'source_record_id',
      'phone_id',
      'phone_number',
      'priority_id',
      'parent_id',
      'is_test',
      'medium_id',
      'is_auto',
      'relationship_id',
      'is_current_revision',
      'original_id',
      'result',
      'is_deleted',
      'engagement_level',
      'weight',
      'is_star',
    ]);
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
    foreach ($messageTokens[$this->entity] as $msgToken) {
      if (array_key_exists($msgToken, $this->getTokenMetadata())) {
        $activeTokens[] = $msgToken;
      }
      // case_id is probably set in metadata anyway.
      elseif ($msgToken === 'case_id' || isset($this->getDeprecatedTokens()[$msgToken])) {
        $activeTokens[] = $msgToken;
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
