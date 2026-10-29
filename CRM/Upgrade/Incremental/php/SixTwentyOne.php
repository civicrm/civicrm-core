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

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    if ($rev === '6.21.alpha1') {
      $preUpgradeMessage .= $this->createDeepExtMessage();
    }
  }

  /**
   * In 6.21, the depth limit of the extension search also applies to the
   * custom extensions directory. The default depth should catch most/all
   * extensions on most/all sites -- but some sites may need a deeper search.
   *
   * @return string
   */
  protected function createDeepExtMessage(): string {
    $infinityAndBeyond = 1000;
    $liveSystem = CRM_Extension_System::singleton();
    $deepSystem = new CRM_Extension_System(['maxDepth' => $infinityAndBeyond]);
    $problemKeys = array_diff($deepSystem->getFullContainer()->getKeys(), $liveSystem->getFullContainer()->getKeys());
    if (empty($problemKeys)) {
      return '';
    }

    $message = ts('When loading extensions, CiviCRM searches the filesystem. In v6.21+, the search of the custom extensions directory is also constrained. This improves performance, but some extensions (%1) will become invisible. To make these visible, you should either move the source-code or edit <code>civicrm.settings.php</code>. For example, you may add this line:', [
      1 => implode(', ', array_map(
        function($key) {
          return '"<code>' . htmlentities($key) . '</code>"';
        },
        $problemKeys
      )),
    ]);
    $example = htmlentities("\$civicrm_setting['domain']['ext_max_depth'] = $infinityAndBeyond;");

    return "<p><strong>" . ts('WARNING') . "</strong>: {$message}</p><pre>$example</pre>";
  }

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
    $this->addTask('Make "StatusPreference.ignore_severity" required', 'alterSchemaField', 'StatusPreference', 'ignore_severity', [
      'title' => ts('Ignore Severity'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Hush messages up to and including this severity.'),
      'add' => '4.7',
      'required' => TRUE,
      'default' => 0,
      'pseudoconstant' => [
        'callback' => ['CRM_Utils_Check', 'getSeverityOptions'],
      ],
    ]);
    $this->addTask('Make StatusPreference name unique per domain', 'makeStatusPreferenceNameUnique');
    $this->addTask('Increase length of "Batch.name"', 'alterSchemaField', 'Batch', 'name', [
      'title' => ts('Batch Name'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Variable name/programmatic handle for this batch.'),
      'add' => '3.3',
    ]);
  }

  /**
   * Keep one StatusPreference per name and domain (the oldest, which is the one
   * the BAO has been updating) and replace the name index with a unique one.
   *
   * @return bool
   */
  public static function makeStatusPreferenceNameUnique(): bool {
    CRM_Core_DAO::executeQuery("
      DELETE sp1 FROM civicrm_status_pref sp1
      INNER JOIN civicrm_status_pref sp2
      WHERE sp1.id > sp2.id
        AND sp1.domain_id = sp2.domain_id
        AND sp1.name = sp2.name
    ", i18nRewrite: FALSE);
    Civi::schemaHelper()->dropIndex('civicrm_status_pref', 'UI_status_pref_name');
    Civi::schemaHelper()->createIndex('civicrm_status_pref', 'UI_name_domain_id', [
      'fields' => ['name' => TRUE, 'domain_id' => TRUE],
      'unique' => TRUE,
    ]);
    return TRUE;
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
