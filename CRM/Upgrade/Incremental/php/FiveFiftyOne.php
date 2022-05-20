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
