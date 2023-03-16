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
 * Class CRM_Pledge_Tokens
 *
 * Generate "pledge.*" tokens.
 *
 * @noinspection PhpUnused
 */
class CRM_Pledge_Tokens extends CRM_Core_EntityTokens {

  /**
   * Get the entity name for api v4 calls.
   *
   * @return string
   */
  protected function getApiEntityName(): string {
    return 'Pledge';
  }

  /**
   * Get entity fields that should be exposed as tokens.
   *
   * These are the fields that seem most likely to be useful as tokens.
   *
   * @todo - add more pseudo-fields like 'paid_amount', 'balance_amount'
   * to v4 api - see the ContributionGetSpecProvider for how.
   *
   * @return string[]
   *
   */
  protected function getExposedFields(): array {
    return [
      'amount',
      'currency',
      'frequency_unit',
      'frequency_interval',
      'frequency_day',
      'installments',
      'start_date',
      'create_date',
      'cancel_date',
      'end_date',
    ];
  }

}
