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
    parent::alterActionScheduleQuery($e);
    $e->query
      ->select('mt.minimum_fee as ' . $this->getEntityAlias() . 'fee')
      ->join('mt', '!casMailingJoinType civicrm_membership_type mt ON e.membership_type_id = mt.id');
  }

  /**
   * @inheritDoc
   */
  public function evaluateToken(\Civi\Token\TokenRow $row, $entity, $field, $prefetch = NULL) {
    if ($field === 'fee') {
      $row->tokens($entity, $field, \CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($this->getFieldValue($row, $field)));
    }
    else {
      parent::evaluateToken($row, $entity, $field, $prefetch);
    }
  }

}
