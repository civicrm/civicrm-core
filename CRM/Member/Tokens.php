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
 * Class CRM_Member_Tokens
 *
 * Generate "member.*" tokens.
 *
 * This TokenSubscriber was produced by refactoring the code from the
 * scheduled-reminder system with the goal of making that system
 * more flexible. The current implementation is still coupled to
 * scheduled-reminders. It would be good to figure out a more generic
 * implementation which is not tied to scheduled reminders, although
 * that is outside the current scope.
 */
class CRM_Member_Tokens extends CRM_Core_EntityTokens {

  /**
   * Get the entity name for api v4 calls.
   *
   * @return string
   */
  protected function getApiEntityName(): string {
    return 'Membership';
  }

  /**
   * Get all tokens.
   *
   * This function will be removed once the parent class can determine it.
   */
  public function getAllTokens(): array {
    return array_merge(
      [
        'fee' => ts('Membership Fee'),
        'id' => ts('Membership ID'),
        'join_date' => ts('Membership Join Date'),
        'start_date' => ts('Membership Start Date'),
        'end_date' => ts('Membership End Date'),
        'status_id:label' => ts('Membership Status'),
        'membership_type_id:label' => ts('Membership Type'),
      ],
      CRM_Utils_Token::getCustomFieldTokens('Membership')
    );
  }

  /**
   * @inheritDoc
   */
  public function checkActive(\Civi\Token\TokenProcessor $processor) {
    // Extracted from scheduled-reminders code. See the class description.
    return !empty($processor->context['actionMapping'])
      && $processor->context['actionMapping']->getEntity() === 'civicrm_membership';
  }

  /**
   * Alter action schedule query.
   *
   * @param \Civi\ActionSchedule\Event\MailingQueryEvent $e
   */
  public function alterActionScheduleQuery(\Civi\ActionSchedule\Event\MailingQueryEvent $e): void {
    if ($e->mapping->getEntity() !== 'civicrm_membership') {
      return;
    }

    // FIXME: `select('e.*')` seems too broad.
    $e->query
      ->select('e.*')
      ->select('mt.minimum_fee as fee, e.id as id , e.join_date, e.start_date, e.end_date, membership_type_id as Membership__membership_type_id, status_id as Membership__status_id')
      ->join('mt', '!casMailingJoinType civicrm_membership_type mt ON e.membership_type_id = mt.id');
  }

  /**
   * @inheritDoc
   */
  public function evaluateToken(\Civi\Token\TokenRow $row, $entity, $field, $prefetch = NULL) {
    $actionSearchResult = $row->context['actionSearchResult'];

    if (in_array($field, ['start_date', 'end_date', 'join_date'])) {
      $row->tokens($entity, $field, \CRM_Utils_Date::customFormat($actionSearchResult->$field));
    }
    elseif ($field == 'fee') {
      $row->tokens($entity, $field, \CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($actionSearchResult->$field));
    }
    elseif (isset($actionSearchResult->$field)) {
      $row->tokens($entity, $field, $actionSearchResult->$field);
    }
    elseif ($cfID = \CRM_Core_BAO_CustomField::getKeyID($field)) {
      $row->customToken($entity, $cfID, $actionSearchResult->entity_id);
    }
    else {
      parent::evaluateToken($row, $entity, $field, $prefetch);
    }
  }

}
