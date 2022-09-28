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
 * Upgrade logic for FiveFortyFour
 */
class CRM_Upgrade_Incremental_php_FiveFortyFour extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade function.
   * @param string $rev
   */
  public function upgrade_5_44_alpha1($rev) {
    // The runSql task populates the new column, so this addColumn task runs first
    $this->addTask('Add case_id column to civicrm_relationship_cache', 'addColumn',
      'civicrm_relationship_cache', 'case_id', "int unsigned DEFAULT NULL COMMENT 'FK to civicrm_case'"
    );
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add FK to civicrm_relationship_cache.case_id', 'addRelationshipCacheCaseFK');
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_44_beta1($rev) {
    $this->addTask('Repair option value label for nb_NO', 'repairNBNOOptionValue');
  }

  /**
   * @param \CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function addRelationshipCacheCaseFK(CRM_Queue_TaskContext $ctx): bool {
    if (!self::checkFKExists('civicrm_relationship_cache', 'FK_civicrm_relationship_cache_case_id')) {
      CRM_Core_DAO::executeQuery("
        ALTER TABLE `civicrm_relationship_cache`
          ADD CONSTRAINT `FK_civicrm_relationship_cache_case_id`
            FOREIGN KEY (`case_id`) REFERENCES `civicrm_case` (`id`)
            ON DELETE CASCADE;
      ", [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

  /**
   * Repair the option value label for nb_NO language.
   *
   * @param CRM_Queue_TaskContext $ctx
   */
  public static function repairNBNOOptionValue(CRM_Queue_TaskContext $ctx) {
    // Repair the existing nb_NO entry.
    $sql = CRM_Utils_SQL::interpolate('UPDATE civicrm_option_value SET label = @newLabel WHERE option_group_id = #group AND name = @name AND label IN (@oldLabels)', [
      'name' => 'nb_NO',
      'newLabel' => ts('Norwegian Bokmål'),
      // Adding check against old label in case they've customized it, in which
      // case we don't want to overwrite that. The ts() part is tricky since
      // it depends if they installed it in English first.
      'oldLabels' => ['Norwegian BokmÃ¥l', ts('Norwegian BokmÃ¥l')],
      'group' => CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_option_group WHERE name = "languages"'),
    ]);
    CRM_Core_DAO::executeQuery($sql);
    return TRUE;
  }

}
