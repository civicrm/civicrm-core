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
 * Upgrade logic for the 6.12.x series.
 *
 * Each minor version in the series is handled by either a `6.12.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_12_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixTwelve extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_12_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $swaps = [
      'if !empty($selectPremium)' => 'if {contribution_product.id|boolean}',
      '$product_name' => 'contribution_product.product_id.name',
      'if $option' => 'if {contribution_product.product_option|boolean}',
      '$option' => 'contribution_product.product_option:label',
      'if $sku' => 'if {contribution_product.product_id.sku|boolean}',
      '$sku' => 'contribution_product.product_id.sku',
      'if $is_deductible AND !empty($price)' => 'if {contribution.non_deductible_amount|boolean} AND {contribution_product.product_id.price|boolean}',
      'ts 1=$price|crmMoney:$currency' => "ts 1='{contribution_product.product_id.price|crmMoney}'",
      'if !empty($receive_date)' => 'if {contribution.receive_date|boolean}',
      '$receive_date|crmDate' => 'contribution.receive_date',
      '$receive_date' => 'contribution.receive_date',
    ];
    foreach (['membership_online_receipt', 'contribution_online_receipt', 'contribution_offline_receipt'] as $type) {
      foreach ($swaps as $from => $to) {
        $this->addTask('Replace {' . $from . ' with ' . $to . 'in ' . $type,
          'updateMessageToken', $type, $from, $type, $rev
        );
      }
    }
  }

}
