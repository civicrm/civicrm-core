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
        'join_date' => ts('Member Since'),
        'start_date' => ts('Membership Start Date'),
        'end_date' => ts('Membership Expiration Date'),
        'status_id:label' => ts('Status'),
        'membership_type_id:label' => ts('Membership Type'),
      ],
      CRM_Utils_Token::getCustomFieldTokens('Membership')
    );
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

}
