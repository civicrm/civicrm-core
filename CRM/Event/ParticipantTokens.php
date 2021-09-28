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

use Civi\Token\TokenRow;

/**
 * Class CRM_Event_ParticipantTokens
 *
 * Generate "participant.*" tokens.
 */
class CRM_Event_ParticipantTokens extends CRM_Core_EntityTokens {

  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    // Set the weight so it runs before event tokens.
    $events['civi.token.eval'] = ['evaluateTokens', 400];
    return $events;
  }

  /**
   * Get the entity name for api v4 calls.
   *
   * @return string
   */
  protected function getApiEntityName(): string {
    return 'Participant';
  }

  /**
   * @return array
   */
  public function getCurrencyFieldName(): array {
    return ['fee_currency'];
  }

  /**
   * Get any tokens with custom calculation.
   */
  public function getBespokeTokens(): array {
    return ['balance' => ts('Event Balance')];
  }

  /**
   * @inheritDoc
   * @throws \CiviCRM_API3_Exception
   */
  public function evaluateToken(TokenRow $row, $entity, $field, $prefetch = NULL) {
    $this->prefetch = (array) $prefetch;
    if (empty($row->context['eventId'])) {
      $row->context['eventId'] = $this->getFieldValue($row, 'event_id');
    }
    if ($field === 'balance') {
      // @todo - is this really a good idea to call this & potentially get the
      // balance of the contribution attached to 'registered_by_id'
      $info = \CRM_Contribute_BAO_Contribution::getPaymentInfo($this->getFieldValue($row, 'id'), 'event');
      $balancePay = $info['balance'] ?? NULL;
      $balancePay = \CRM_Utils_Money::format($balancePay);
      $row->tokens($entity, $field, $balancePay);
      return;
    }
    parent::evaluateToken($row, $entity, $field, $prefetch);
  }

  /**
   * Do not show event id in the UI as event.id will also be available.
   *
   * Discount id is probably a bit esoteric.
   *
   * @return string[]
   */
  protected function getHiddenTokens(): array {
    return ['event_id', 'discount_id'];
  }

  /**
   * Get entity fields that should not be exposed as tokens.
   *
   * @return string[]
   */
  public function getSkippedFields(): array {
    $fields = parent::getSkippedFields();
    // Never add these 2 fields - may not be a stable part of the schema.
    // This field is on it's way out of core.
    $fields[] = 'cart_id';
    // this will probably get schema changed out of the table at some point.
    $fields[] = 'is_pay_later';
    return $fields;
  }

}
