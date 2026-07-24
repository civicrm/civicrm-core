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
 * Upgrade logic for the 6.18.x series.
 *
 * Each minor version in the series is handled by either a `6.18.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_18_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixEighteen extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_18_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add column "RelationshipType.weight"', 'alterSchemaField', 'RelationshipType', 'weight', [
      'title' => ts('Order'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Ordering of the relationship types.'),
      'add' => '6.18',
      'default' => 0,
    ]);
    $this->addTask(ts('Initialize relationship type weights'), 'initializeRelationshipTypeWeights');

    $this->addTask('Add unique index to Currency.name', 'addIndex', 'civicrm_currency', 'name', 'UI');

    // Add FK to currency fields
    $entitiesWithCurrency = [
      'Contribution' => 'currency',
      'ContributionPage' => 'currency',
      'ContributionRecur' => 'currency',
      'ContributionSoft' => 'currency',
      'Product' => 'currency',
      'Event' => 'currency',
      'Participant' => 'fee_currency',
      'FinancialItem' => 'currency',
      'FinancialTrxn' => 'currency',
      'PCP' => 'currency',
      'Pledge' => 'currency',
      'PledgePayment' => 'currency',
    ];
    foreach ($entitiesWithCurrency as $entityName => $fieldName) {
      $this->addTask("Add foreign key to $entityName.$fieldName", 'addCurrencyFk', $entityName, $fieldName);
    }

    $this->addTask('Add CustomField.control_field column', 'alterSchemaField', 'CustomField', 'control_field', [
      'title' => ts('Depends on'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Select',
      'description' => ts('Name of the field that this field depends on.'),
      'add' => '6.18',
      'default' => NULL,
    ], 'AFTER in_selector');
  }

  /**
   * Initialize relationship type weights.
   *
   * @param CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function initializeRelationshipTypeWeights(CRM_Queue_TaskContext $ctx): bool {
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_relationship_type
      SET weight = id
      WHERE weight = 0
    ");

    return TRUE;
  }

  public static function addCurrencyFk($ctx, $entityName, $fieldName): bool {
    $tableName = Civi::entity($entityName)->getMeta('table');

    // Safety check, remove any invalid currency
    CRM_Core_DAO::executeQuery("UPDATE `$tableName` SET `$fieldName` = NULL WHERE `$fieldName` IS NOT NULL AND `$fieldName` NOT IN (SELECT `name` FROM `civicrm_currency`)", i18nRewrite: FALSE);

    Civi::schemaHelper()->createForeignKey($tableName, $fieldName, [
      'entity_reference' => [
        'entity' => 'Currency',
        'key' => 'name',
        'on_delete' => 'SET NULL',
      ],
    ]);

    return TRUE;
  }

}
