<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Upgrade logic for FiveTwentyOne */
class CRM_Upgrade_Incremental_php_FiveTwentyOne extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed beforeupgrade.
   *
   * Note: This function is called iteratively for each upcoming
   * revision to the database.
   *
   * @param string $preUpgradeMessage
   * @param string $rev
   *   a version number, e.g. '4.4.alpha1', '4.4.beta3', '4.4.0'.
   * @param null $currentVer
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    // Example: Generate a pre-upgrade message.
    // if ($rev == '5.12.34') {
    //   $preUpgradeMessage .= '<p>' . ts('A new permission, "%1", has been added. This permission is now used to control access to the Manage Tags screen.', array(1 => ts('manage tags'))) . '</p>';
    // }
  }

  /**
   * Compute any messages which should be displayed after upgrade.
   *
   * @param string $postUpgradeMessage
   *   alterable.
   * @param string $rev
   *   an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs.
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    // Example: Generate a post-upgrade message.
    // if ($rev == '5.12.34') {
    //   $postUpgradeMessage .= '<br /><br />' . ts("By default, CiviCRM now disables the ability to import directly from SQL. To use this feature, you must explicitly grant permission 'import SQL datasource'.");
    // }
  }

  /*
   * Important! All upgrade functions MUST add a 'runSql' task.
   * Uncomment and use the following template for a new upgrade version
   * (change the x in the function name):
   */

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_21_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Remove civicrm_word_replacement keys', 'removeWordReplacementsKeys');
    $this->addTask('Add civicrm_word_replacement language column', 'addWordReplacementsLanguageColumn');
    $this->addTask('Re-Create civicrm_word_replacement keys', 'recreateWordReplacementsLanguageKeys');
    $this->addTask('Drop domain locale_custom_strings column', 'dropDomainLocaleCustomStringsColumn');
  }

  public static function removeWordReplacementsKeys(CRM_Queue_TaskContext $ctx) {
    CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_word_replacement', 'FK_civicrm_word_replacement_domain_id');
    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_word_replacement DROP KEY UI_domain_find');
    return TRUE;
  }

  public static function addWordReplacementsLanguageColumn(CRM_Queue_TaskContext $ctx) {
    self::addColumn($ctx, 'civicrm_word_replacement', 'language', "varchar(5) default null COMMENT 'Word Replacement Language'");
    return TRUE;
  }

  public static function recreateWordReplacementsLanguageKeys(CRM_Queue_TaskContext $ctx) {
    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_word_replacement ADD unique key `UI_domain_find` (`domain_id`,`find_word`, `language`)');

    $sql = CRM_Core_BAO_SchemaHandler::buildForeignKeySQL([
      'fk_table_name' => 'civicrm_domain',
      'fk_field_name' => 'id',
      'name' => 'domain_id',
      'fk_attributes' => ' ON DELETE CASCADE',
    ], "\n", " ADD ", 'civicrm_word_replacement');
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_word_replacement " . $sql, [], TRUE, NULL, FALSE, FALSE);
    return TRUE;
  }

  public static function dropDomainLocaleCustomStringsColumn(CRM_Queue_TaskContext $ctx) {
    CRM_Core_BAO_SchemaHandler::dropColumn('civicrm_domain', 'locale_custom_strings', FALSE, TRUE);
    return TRUE;
  }

}
