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
 * Upgrade logic for the 5.67.x series.
 *
 * Each minor version in the series is handled by either a `5.67.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_67_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSixtySeven extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_67_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Make EntityFile.entity_table required', 'alterColumn', 'civicrm_entity_file', 'entity_table', "varchar(64) NOT NULL COMMENT 'physical tablename for entity being joined to file, e.g. civicrm_contact'");
    $this->addExtensionTask('Enable Authx extension', ['authx'], 1101);
    $this->addExtensionTask('Enable Afform extension', ['org.civicrm.afform'], 1102);
    $this->addTask('Add "civicrm_note" to "note_used_for" option group', 'addNoteNote');
  }

  public static function addNoteNote(CRM_Queue_TaskContext $ctx): bool {
    CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'option_group_id' => 'note_used_for',
      'label' => ts('Notes'),
      'name' => 'Note',
      'value' => 'civicrm_note',
    ]);
    return TRUE;
  }

}
