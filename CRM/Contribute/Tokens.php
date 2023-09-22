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

use Civi\Api4\ContributionPage;
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
    // Check to make sure CiviContribute is enabled, just in case it remains registered. Eventually this will be moved to the CiviContribute extension
    // and this check can hopefully be removed (as long as caching on enable / disable doesn't explode our brains and / or crash the site).
    if (!array_key_exists('Contribution', \Civi::service('action_object_provider')->getEntities())) {
      return $tokens;
    }
    // Ideally we would derive this from 'usage' - but it looks like adding the usage data
    // was quite a bit of work & didn't leave the energy to implement - esp expose for
    // where clauses (also, it feels like 'hidden+token' would be a good usage.
    $tokenList = ['frontend_title', 'pay_later_text', 'pay_later_receipt', 'is_share', 'receipt_text'];
    $contributionPageTokens = ContributionPage::getFields(FALSE)->addWhere('name', 'IN', $tokenList)->execute();
    foreach ($contributionPageTokens as $contributionPageToken) {
      $tokens['contribution_page_id.' . $contributionPageToken['name']] = [
        'title' => $contributionPageToken['title'],
        'name' => 'contribution_page_id.' . $contributionPageToken['name'],
        'type' => 'mapped',
        'data_type' => $contributionPageToken['data_type'],
        'input_type' => $contributionPageToken['input_type'],
        'audience' => $contributionPageToken['name'] === 'is_share' ? 'hidden' : 'user',
      ];
    }
    $hiddenTokens = ['modified_date', 'create_date', 'trxn_id', 'invoice_id', 'is_test', 'payment_token_id', 'payment_processor_id', 'payment_instrument_id', 'cycle_day', 'installments', 'processor_id', 'next_sched_contribution_date', 'failure_count', 'failure_retry_date', 'auto_renew', 'is_email_receipt', 'contribution_status_id'];
    $contributionRecurFields = ContributionRecur::getFields(FALSE)->setLoadOptions(TRUE)->execute();
    foreach ($contributionRecurFields as $contributionRecurField) {
      $tokens['contribution_recur_id.' . $contributionRecurField['name']] = [
        'title' => $contributionRecurField['title'],
        'name' => 'contribution_recur_id.' . $contributionRecurField['name'],
        'type' => 'mapped',
        'options' => $contributionRecurField['options'] ?? NULL,
        'data_type' => $contributionRecurField['data_type'],
        'input_type' => $contributionRecurField['input_type'],
        'audience' => in_array($contributionRecurField['name'], $hiddenTokens) ? 'hidden' : 'user',
      ];
    }
    return $tokens;
  }

}
