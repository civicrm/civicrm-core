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
 * Upgrade logic for the 6.3.x series.
 *
 * Each minor version in the series is handled by either a `6.3.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_3_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixThree extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_3_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);

    // Delete non-attachment rows in batches of 5000
    $fileCount = CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_entity_file WHERE entity_table LIKE "civicrm_value_%"');
    $iterations = ceil($fileCount / self::BATCH_SIZE);
    for ($i = 1; $i <= $iterations; $i++) {
      $this->addTask('Delete non-attachment rows from civicrm_entity_file', 'deleteNonAttachmentFiles', $i);
    }
  }

  public function upgrade_6_3_beta1($rev): void {
    $this->addTask('Add column "UserJob.search_display_id"', 'alterSchemaField', 'UserJob', 'search_display_id', [
      'title' => ts('SearchDisplay ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('Batch import search display'),
      'input_attrs' => [
        'label' => ts('Search Display'),
      ],
      'entity_reference' => [
        'entity' => 'SearchDisplay',
        'key' => 'id',
        'fk' => FALSE,
      ],
    ], 'queue_id');
  }

  /**
   * @param \CRM_Queue_TaskContext|null $context
   * @param string $entity
   *
   * @return true
   * @throws \CRM_Core_Exception
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public static function upgradeImportMappingFields($context, string $entity): bool {
    CRM_Upgrade_Incremental_php_SixTwo::upgradeImportMappingFields($context, $entity);
    return TRUE;
  }

  public function upgrade_6_3_beta2($rev): void {
    $this->addTask('Update Activity mappings', 'upgradeImportMappingFields', 'Activity');
    $this->addTask('Update Membership mappings', 'upgradeImportMappingFields', 'Membership');
    $this->addTask('Update Contribution mappings', 'upgradeImportMappingFields', 'Contribution');
    $this->addTask('Update Participant mappings', 'upgradeImportMappingFields', 'Participant');
  }

  public static function deleteNonAttachmentFiles(): bool {
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_entity_file WHERE entity_table LIKE "civicrm_value_%" LIMIT ' . self::BATCH_SIZE);
    return TRUE;
  }

}
