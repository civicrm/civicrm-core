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
 * Upgrade logic for FiveFortyTwo
 */
class CRM_Upgrade_Incremental_php_FiveFortyTwo extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_42_alpha1(string $rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $contributeComponentID = CRM_Core_DAO::singleValueQuery('SELECT id from civicrm_component WHERE name = "CiviContribute"');
    $this->addTask(
      'Add option group for entity batch table (if you are using gift-aid you will need an extension update)',
      'addOptionGroup',
      [
        'name' => 'entity_batch_extends',
        'title' => ts('Entity Batch Extends'),
        'is_reserved' => 1,
        'is_active' => 1,
        'is_locked' => 1,
      ],
      [
        [
          'name' => 'civicrm_financial_trxn',
          'value' => 'civicrm_financial_trxn',
          'label' => ts('Financial Transactions'),
          'component_id' => $contributeComponentID,
        ],
      ]
    );
  }

}
