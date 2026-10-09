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
 * Upgrade logic for the 6.21.x series.
 *
 * Each minor version in the series is handled by either a `6.21.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_21_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixTwentyOne extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_21_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add column "MessageTemplate.usage"', 'alterSchemaField', 'MessageTemplate', 'usage', [
      'title' => ts('Usage'),
      'sql_type' => 'varchar(512)',
      'input_type' => 'Select',
      'description' => ts('Optional list of entities (e.g. Case, Activity) this template is relevant to. Used to filter the token picker. NULL/empty means no explicit restriction: for System Workflow templates the schema is derived from the workflow; for User Driven templates it defaults to contact tokens only.'),
      'add' => '6.21',
      'default' => NULL,
      'serialize' => CRM_Core_DAO::SERIALIZE_COMMA,
    ]);

    $this->addTask('Make the index UI_case_activity_id for civicrm_case_activity unique', 'makeCaseActivityIndexUnique');
  }

  /**
   * Make the index UI_case_activity_id on civicrm_case_activity unique to prevent duplicate entries
   *
   * @return bool
   */
  public static function makeCaseActivityIndexUnique(): bool {
    // Delete duplicate rows from civicrm_case_activity;
    CRM_Core_DAO::executeQuery("
      DELETE ca1 FROM civicrm_case_activity ca1
      INNER JOIN civicrm_case_activity ca2
      WHERE ca1.id > ca2.id
        AND ca1.case_id = ca2.case_id
        AND ca1.activity_id = ca2.activity_id
    ", i18nRewrite: FALSE);

    $caseActivityIndexes = CRM_Core_BAO_SchemaHandler::getIndexes(['civicrm_case_activity']);
    $caseActivityIdIndex = $caseActivityIndexes['civicrm_case_activity']['UI_case_activity_id'] ?? NULL;

    if (isset($caseActivityIdIndex) && $caseActivityIdIndex['unique']) {
      // Index is already unique => nothing more to do
      return TRUE;
    }

    if (Civi::schemaHelper()->indexExists('civicrm_case_activity', 'UI_case_activity_id')) {
      // Index is not unique => drop it and re-create it.
      // Note: Dropping and adding the index via Civi::schemaHelper() would result in a database
      // error due to a foreign key constraint whereas re-creating it in a single ALTER TABLE
      // statement works without errors.
      CRM_Core_DAO::executeQuery("
        ALTER TABLE civicrm_case_activity
          DROP INDEX UI_case_activity_id,
          ADD UNIQUE INDEX UI_case_activity_id (case_id, activity_id)
      ", i18nRewrite: FALSE);
    }
    else {
      // Index does not exist => create it
      Civi::schemaHelper()->createIndex('civicrm_case_activity', 'UI_case_activity_id', [
        'fields' => ['case_id', 'activity_id'],
        'unique' => TRUE,
      ]);
    }

    return TRUE;
  }

}
