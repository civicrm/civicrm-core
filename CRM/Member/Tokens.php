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
      'source',
      'status_override_end_date',
    ];
  }

  /**
   * @inheritDoc
   * @throws \CRM_Core_Exception
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
   * Get any overrides for token metadata.
   *
   * This is most obviously used for setting the audience, which
   * will affect widget-presence.
   *
   * Changing the audience is done in order to simplify the
   * UI for more general users.
   *
   * @return \string[][]
   */
  protected function getTokenMetadataOverrides(): array {
    return [
      'owner_membership_id' => ['audience' => 'sysadmin'],
      'max_related' => ['audience' => 'sysadmin'],
      'contribution_recur_id' => ['audience' => 'sysadmin'],
      'is_override' => ['audience' => 'sysadmin'],
      'is_test' => ['audience' => 'sysadmin'],
      // Pay later is considered to be unreliable in the schema
      // and will eventually be removed.
      'is_pay_later' => ['audience' => 'deprecated'],
    ];
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
