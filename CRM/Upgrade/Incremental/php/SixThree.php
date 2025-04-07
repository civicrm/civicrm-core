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
    // Running this twice could end up matching on entity_file.id that belongs
    // to something else and then insert the wrong file_id. Also, the snapshot
    // will overwrite the old data with newly updated data so we lose the
    // backup we wanted. So leave a flag that we've already run this.
    if (!Civi::settings()->get('upgrade_6_3_entitytag_done')) {
      $this->addSnapshotTask('entity_tag_file', CRM_Utils_SQL_Select::from('civicrm_entity_tag')->where("entity_table = 'civicrm_file'"));
      $this->addTask('Harmonify entity tag file ids', 'harmonifyEntityTag');
      Civi::settings()->set('upgrade_6_3_entitytag_done', TRUE);
    }
  }

  /**
   * The UI is currently expecting entity_tag.entity_id to point to a file id.
   * It's currently pointing to entity_file's id.
   */
  public static function harmonifyEntityTag(CRM_Queue_TaskContext $ctx): bool {
    CRM_Core_DAO::executeQuery("UPDATE civicrm_entity_tag et
      INNER JOIN civicrm_entity_file ef ON (et.entity_id=ef.id AND et.entity_table='civicrm_file')
      SET et.entity_id=ef.file_id");
    return TRUE;
  }

}
