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

use Civi\Api4\Address;
use Civi\Token\TokenRow;

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
  protected function getEntityAlias(): string {
    return 'contrib_';
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
   * @return array
   */
  public function getCurrencyFieldName() {
    return ['currency'];
  }

  /**
   * Get Related Entity tokens.
   *
   * @return array[]
   */
  protected function getRelatedTokens(): array {
    $tokens = [];
    // Check to make sure CiviContribute is enabled, just in case it remains registered. Eventually this will be moved to the CiviContribute extension
    // and this check can hopefully be removed (as long as caching on enable / disable doesn't explode our brains and / or crash the site).
    if (!array_key_exists('Contribution', \Civi::service('action_object_provider')->getEntities())) {
      return $tokens;
    }
    $tokens += $this->getRelatedTokensForEntity('Address', 'address_id', ['name', 'id']);

    $tokens['address_id.name']['title'] = ts('Billing Address Name');
    $tokens['address_id.display'] = [
      'title' => ts('Billing Address'),
      'name' => 'address_id.display',
      'type' => 'mapped',
      'input_type' => 'Text',
      'audience' => 'user',
      'data_type' => 'String',
    ];

    // Ideally we would derive this from 'usage' - but it looks like adding the usage data
    // was quite a bit of work & didn't leave the energy to implement - esp expose for
    // where clauses (also, it feels like 'hidden+token' would be a good usage.
    $contributionPageTokens = ['frontend_title', 'pay_later_text', 'pay_later_receipt', 'is_share', 'receipt_text'];
    $tokens += $this->getRelatedTokensForEntity('ContributionPage', 'contribution_page_id', $contributionPageTokens, ['is_share']);

    $hiddenTokens = ['modified_date', 'create_date', 'trxn_id', 'invoice_id', 'is_test', 'payment_token_id', 'payment_processor_id', 'payment_instrument_id', 'cycle_day', 'installments', 'processor_id', 'auto_renew', 'is_email_receipt', 'contribution_status_id'];
    $tokens += $this->getRelatedTokensForEntity('ContributionRecur', 'contribution_recur_id', ['*'], $hiddenTokens);
    return $tokens;
  }

  /**
   * @param \Civi\Token\TokenRow $row
   * @param string $field
   * @return string|int
   */
  protected function getFieldValue(TokenRow $row, string $field) {
    $entityName = $this->getEntityName();
    if (isset($row->context[$entityName][$field])) {
      return $row->context[$entityName][$field];
    }
    if ($field === 'address_id.display') {
      $addressID = $this->getFieldValue($row, 'address_id.id');
      // We possibly could figure out how to load in a cleverer way
      // or as part of apiv4 but this is tested so that can easily happen later...
      $address = Address::get(FALSE)
        ->addWhere('id', '=', $addressID)
        ->addSelect('*', 'state_province_id:label', 'country_id:label')
        ->execute()->first() ?? [];
      // We have name in the address_id.name token.
      unset($address['name']);
      return \CRM_Utils_Address::format($address);
    }
    return parent::getFieldValue($row, $field);
  }

  /**
   * Get fields which need to be returned to render another token.
   *
   * @return array
   */
  public function getDependencies(): array {
    return ['address_id.display' => 'address_id.id'];
  }

}
