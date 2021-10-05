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
   * List out the fields that are exposed.
   *
   * For historical reasons these are the only exposed fields.
   *
   * It is also possible to list 'skippedFields'
   *
   * @return string[]
   */
  protected function getExposedFields(): array {
    return [
      'id',
      'join_date',
      'start_date',
      'end_date',
      'status_id',
      'membership_type_id',
    ];
  }

  /**
   * @inheritDoc
   * @throws \CiviCRM_API3_Exception
   */
  public function evaluateToken(\Civi\Token\TokenRow $row, $entity, $field, $prefetch = NULL) {
    if ($field === 'fee') {
      $membershipType = CRM_Member_BAO_MembershipType::getMembershipType($this->getFieldValue($row, 'membership_type_id'));
      $row->tokens($entity, $field, \CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($membershipType['minimum_fee']));
    }
    else {
      parent::evaluateToken($row, $entity, $field, $prefetch);
    }
  }

  /**
   * Get fields which need to be returned to render another token.
   *
   * @return array
   */
  public function getDependencies(): array {
    return ['fee' => 'membership_type_id'];
  }

  /**
   * Get any tokens with custom calculation.
   *
   * In this case 'fee' should be converted to{membership.membership_type_id.fee}
   * but we don't have the formatting support to do that with no
   * custom intervention yet.
   */
  protected function getBespokeTokens(): array {
    return [
      'fee' => [
        'title' => ts('Membership Fee'),
        'name' => 'fee',
        'type' => 'calculated',
        'options' => NULL,
        'data_type' => 'integer',
        'audience' => 'user',
      ],
    ];
  }

}
