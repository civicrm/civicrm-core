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

use Civi\Api4\ContributionRecur;

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
    $hiddenTokens = ['modified_date', 'create_date', 'trxn_id', 'invoice_id', 'is_test', 'payment_token_id', 'payment_processor_id', 'payment_instrument_id', 'cycle_day', 'installments', 'processor_id', 'next_sched_contribution_date', 'failure_count', 'failure_retry_date', 'auto_renew', 'is_email_receipt', 'contribution_status_id'];
    $contributionRecurFields = ContributionRecur::getFields(FALSE)->setLoadOptions(TRUE)->execute();
    foreach ($contributionRecurFields as $contributionRecurField) {
      $tokens['contribution_recur_id.' . $contributionRecurField['name']] = [
        'title' => $contributionRecurField['title'],
        'name' => 'contribution_recur_id.' . $contributionRecurField['name'],
        'type' => 'mapped',
        'options' => $contributionRecurField['options'] ?? NULL,
        'data_type' => $contributionRecurField['data_type'],
        'audience' => in_array($contributionRecurField['name'], $hiddenTokens) ? 'hidden' : 'user',
      ];
    }
    return $tokens;
  }

}
