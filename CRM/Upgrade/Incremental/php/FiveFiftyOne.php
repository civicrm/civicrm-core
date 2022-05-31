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

use Civi\Api4\MappingField;

/**
 * Upgrade logic for the 5.51.x series.
 *
 * Each minor version in the series is handled by either a `5.51.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_51_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveFiftyOne extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_51_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask(ts('Convert import mappings to use names'), 'convertMappingFieldLabelsToNames', $rev);
    $this->addTask('Add column "civicrm_queue.status"', 'addColumn', 'civicrm_queue',
      'status', "varchar(16) NULL DEFAULT 'active' COMMENT 'Execution status'");
    $this->addTask('Add column "civicrm_queue.error"', 'addColumn', 'civicrm_queue',
      'error', "varchar(16) NULL COMMENT 'Fallback behavior for unhandled errors'");
    $this->addTask('Backfill "civicrm_queue.status" and "civicrm_queue.error")', 'fillQueueColumns');
  }

  public static function fillQueueColumns($ctx): bool {
    // Generally, anything we do here is nonsensical because there shouldn't be much real world data,
    // and the goal is to require something specific going forward (for anything that has an automatic runner).
    // But this ensures that satisfy the invariant.
    //
    // What default value of "error" should apply to pre-existing queues (if they somehow exist)?
    // Go back to our heuristic "short-term/finite queue <=> abort" vs "long-term/infinite queue <=> log".
    // We don't have adequate data to differentiate these, so some will be wrong/suboptimal.
    // What's the impact of getting it wrong?
    // - For a finite/short-term queue, work has finished already (or will finish soon), so there is
    //   very limited impact to wrongly setting `error=log`.
    // - For an infinite/long-term queue, work will continue indefinitely into the future. The impact
    //   of wrongly setting `error=abort` would continue indefinitely to the future.
    // Therefore, backfilling `error=log` is less-problematic than backfilling `error=abort`.
    CRM_Core_DAO::executeQuery('UPDATE civicrm_queue SET error = "delete" WHERE runner IS NOT NULL AND error IS NULL');
    CRM_Core_DAO::executeQuery('UPDATE civicrm_queue SET status = IF(runner IS NULL, NULL, "active")');
    return TRUE;
  }

  /**
   * Convert saved mapping fields for contribution imports to use name rather than
   * label.
   *
   * Currently the 'name' column in civicrm_mapping_field holds names like
   * 'First Name' or, more tragically 'Contact ID (match to contact)'.
   *
   * This updates them to hold the name - eg. 'total_amount'.
   *
   * @return bool
   * @throws \API_Exception
   */
  public static function convertMappingFieldLabelsToNames(): bool {
    $mappings = MappingField::get(FALSE)
      ->setSelect(['id', 'name'])
      ->addWhere('mapping_id.mapping_type_id:name', '=', 'Import Contribution')
      ->execute();
    $fields = CRM_Contribute_BAO_Contribution::importableFields('All', FALSE);
    $fieldMap = [];
    foreach ($fields as $fieldName => $field) {
      $fieldMap[$field['title']] = $fieldName;
    }
    $fieldMap[ts('Soft Credit')] = 'soft_credit';
    $fieldMap[ts('Pledge Payment')] = 'pledge_payment';
    $fieldMap[ts(ts('Pledge ID'))] = 'pledge_id';
    $fieldMap[ts(ts('Financial Type'))] = 'financial_type_id';
    $fieldMap[ts(ts('Payment Method'))] = 'payment_instrument_id';

    foreach ($mappings as $mapping) {
      if (!empty($fieldMap[$mapping['name']])) {
        MappingField::update(FALSE)
          ->addWhere('id', '=', $mapping['id'])
          ->addValue('name', $fieldMap[$mapping['name']])
          ->execute();
      }
    }
    return TRUE;
  }

}
