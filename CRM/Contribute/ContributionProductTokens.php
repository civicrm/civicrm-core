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
 * Class CRM_Contribute_ContributionProductTokens
 *
 * Generate "contribution_product.*" tokens.
 */
class CRM_Contribute_ContributionProductTokens extends CRM_Core_EntityTokens {

  /**
   * Get the entity name for api v4 calls.
   *
   * @return string
   */
  protected function getApiEntityName(): string {
    return 'ContributionProduct';
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
    $tokens += $this->getRelatedTokensForEntity('Product', 'product_id', ['name', 'sku']);
    return $tokens;
  }

}
