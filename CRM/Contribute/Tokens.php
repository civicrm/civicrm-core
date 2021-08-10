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
 * Class CRM_Contribute_Tokens
 *
 * Generate "contribution.*" tokens.
 *
 * At time of writing, we don't have any particularly special tokens -- we just
 * do some basic formatting based on the corresponding DB field.
 */
class CRM_Contribute_Tokens extends CRM_Core_EntityTokens {

  /**
   * @return string
   */
  protected function getEntityName(): string {
    return 'contribution';
  }

  /**
   * Get the entity name for api v4 calls.
   *
   * In practice this IS just ucfirst($this->GetEntityName)
   * but declaring it seems more legible.
   *
   * @return string
   */
  protected function getApiEntityName(): string {
    return 'Contribution';
  }

  /**
   * Get a list of tokens which are loaded via APIv4.
   *
   * This list is historical and we need to question whether we
   * should filter out any fields (other than those fields, like api_key
   * on the contact entity) with permissions defined.
   *
   * @return array
   *   Ex: ['foo', 'bar_id', 'bar_id:name', 'bar_id:label']
   */
  protected function getApiTokens(): array {
    $fields = [
      'contribution_page_id',
      'source',
      'id',
      'receive_date',
      'total_amount',
      'fee_amount',
      'net_amount',
      'non_deductible_amount',
      'trxn_id',
      'invoice_id',
      'currency',
      'cancel_date',
      'receipt_date',
      'thankyou_date',
      'tax_amount',
      'contribution_status_id',
      'contribution_status_id:name',
      'contribution_status_id:label',
      'financial_type_id',
      'financial_type_id:name',
      'financial_type_id:label',
      'payment_instrument_id',
      'payment_instrument_id:name',
      'payment_instrument_id:label',
      'cancel_reason',
      'amount_level',
      'check_number',
    ];
    if (CRM_Campaign_BAO_Campaign::isCampaignEnable()) {
      $fields[] = 'campaign_id';
      $fields[] = 'campaign_id.name';
      $fields[] = 'campaign_id.title';
    }

    return $fields;
  }

  public function getAliasTokens(): array {
    $aliases = [];
    if (CRM_Campaign_BAO_Campaign::isCampaignEnable()) {
      // Unit-tests are written to use these funny tokens - but they're not valid in APIv4.
      $aliases['campaign'] = 'campaign_id.name';
      $aliases['campaign_id:name'] = 'campaign_id.name';
      $aliases['campaign_id:label'] = 'campaign_id.title';
    }
    return $aliases;
  }

  public function getPrefetchFields(\Civi\Token\Event\TokenValueEvent $e): array {
    $result = parent::getPrefetchFields($e);

    // Always prefetch 'civicrm_contribution.currency' in case we need to format other fields (fee_amount, total_amount, etc).
    $result[] = 'currency';

    return array_unique($result);
  }

  public function evaluateToken(\Civi\Token\TokenRow $row, $entity, $field, $prefetch = NULL) {
    $values = $prefetch[$row->context[$this->getEntityIDField()]];

    // Any monetary fields in a `Contribution` (`fee_amount`, `total_amount`, etc) should be formatted in matching `currency`.
    // This formatting rule would be nonsensical in any other entity.
    if ($this->isApiFieldType($field, 'Money')) {
      return $row->format('text/plain')->tokens($entity, $field,
        \CRM_Utils_Money::format($values[$field], $values['currency']));
    }

    return parent::evaluateToken($row, $entity, $field, $prefetch);
  }

}
