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
namespace Civi\Api4;

/**
 * EntityFinancialAccount. Joins financial accounts to financial types.
 *
 * @see https://docs.civicrm.org/dev/en/latest/financial/financialentities/#financial-accounts
 *
 * @ui_join_filters account_relationship
 *
 * @searchable secondary
 * @since 5.37
 * @package Civi\Api4
 */
class EntityFinancialAccount extends Generic\DAOEntity {
  use Generic\Traits\EntityBridge;

  /**
   * @return array
   */
  public static function getInfo() {
    $info = parent::getInfo();
    $info['bridge'] = [
      'entity_id' => ['to' => 'financial_account_id'],
      'financial_account_id' => ['to' => 'entity_id'],
    ];
    return $info;
  }

}
