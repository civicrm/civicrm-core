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
 * Upgrade logic for the 6.15.x series.
 *
 * Each minor version in the series is handled by either a `6.15.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_15_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixFifteen extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_15_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
  }

  public static function removeSearchIndexForSerializedCustomFields($ctx): bool {
    $query = "
      SELECT f.column_name, g.table_name
      FROM civicrm_custom_field f
      INNER JOIN civicrm_custom_group g ON f.custom_group_id = g.id
      WHERE f.serialize IS NOT NULL AND f.serialize != '0' AND f.is_searchable = 1
    ";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $indexName = 'index_' . $dao->column_name;
      CRM_Core_BAO_SchemaHandler::dropIndexIfExists($dao->table_name, $indexName);
    }
    return TRUE;
  }

  public function upgrade_6_15_beta1($rev): void {
    $this->addTask('dev/core#6390 Remove indexes from serialied custom fields', 'removeSearchIndexForSerializedCustomFields');

    $swaps = [
      '{contribution_product.price|boolean}' => '{contribution_product.product_id.price|boolean}',
      '{contribution_product.price|crmMoney}' => '{contribution_product.product_id.price|crmMoney}',
      'ts 1=$price|crmMoney' => "ts 1=\\'{contribution_product.product_id.price|crmMoney}\\'",
    ];
    foreach (['membership_online_receipt', 'contribution_online_receipt', 'contribution_offline_receipt'] as $type) {
      foreach ($swaps as $from => $to) {
        $this->addTask('Replace . ' . $from . ' with ' . $to . $type,
          'updateMessageToken', $type, $from, $to, $rev
        );
      }
    }
  }

}
