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
   * Get a list of simple, passthrough tokens which are loaded via APIv4.
   *
   * This list is historical and we need to question whether we
   * should filter out any fields (other than those fields, like api_key
   * on the contact entity) with permissions defined.
   *
   * @return array
   *   Ex: ['foo' => 'Foo Stuff', 'bar_id' => 'Barber ID#']
   */
  protected function getApiTokens(): array {
    $result = parent::getApiTokens();

    // When enabling joins/etc, there is a potential for qty to explode or maybe reveal extra info. For now, curate the list.
    $result['contribution_status_id:name'] = ts('Contribution Status (Name)');
    $result['contribution_status_id:label'] = ts('Contribution Status (Label)');
    $result['financial_type_id:name'] = ts('Financial Type (Name)');
    $result['financial_type_id:label'] = ts('Financial Type (Label)');
    $result['payment_instrument_id:name'] = ts('Payment Instrument (Name)');
    $result['payment_instrument_id:label'] = ts('Payment Instrument (Label)');
    if (CRM_Campaign_BAO_Campaign::isCampaignEnable()) {
      $result['campaign_id.name'] = ts('Campaign (Name)');
      $result['campaign_id.title'] = ts('Campaign (Title)');
    }

    asort($result);
    return $result;
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
