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
 * Upgrade logic for FiveFifteen
 */
class CRM_Upgrade_Incremental_php_FiveFifteen extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_15_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Fix errant deferred revenue settings', 'updateContributeSettings');
    $this->addTask('Fix cache key column name in prev next cache', 'fixCacheKeyColumnNamePrevNext');
    $this->addTask('Update smart groups where jcalendar fields have been converted to datepicker', 'updateSmartGroups', [
      'datepickerConversion' => [
        'participant_register_date',
      ],
    ]);
  }

  public static function fixCacheKeyColumnNamePrevNext() {
    CRM_Core_BAO_SchemaHandler::dropIndexIfExists('civicrm_prevnext_cache', 'index_all');
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_prevnext_cache CHANGE COLUMN  cacheKey cachekey VARCHAR(255) COMMENT 'Unique path name for cache element of the searched item'");
    CRM_Core_DAO::executeQuery("CREATE INDEX index_all ON civicrm_prevnext_cache (cachekey, entity_id1, entity_id2, entity_table, is_selected)");
    return TRUE;
  }

}
