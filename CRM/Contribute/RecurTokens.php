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
 * Class CRM_Contribute_RecurTokens
 *
 * Generate "contribution_recur.*" tokens.
 */
class CRM_Contribute_RecurTokens extends CRM_Core_EntityTokens {

  /**
   * Get the entity name for api v4 calls.
   *
   * @return string
   */
  protected function getApiEntityName(): string {
    return 'ContributionRecur';
  }

  /**
   * @return array
   */
  public function getCurrencyFieldName(): array {
    return ['currency'];
  }

}
