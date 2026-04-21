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
 * Upgrade logic for the 6.14.x series.
 *
 * Each minor version in the series is handled by either a `6.14.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_14_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixFourteen extends CRM_Upgrade_Incremental_Base {

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    if ($rev === '6.14.alpha1') {
      if (CRM_Core_DAO::singleValueQuery('SELECT MIN(id) FROM civicrm_activity WHERE is_current_revision = 0')) {
        $docUrl = 'https://civicrm.org/redirect/activities-5.57';
        $docAnchor = 'target="_blank" href="' . htmlentities($docUrl) . '"';
        $preUpgradeMessage .= '<p>' . ts('Your database contains CiviCase activity revisions which have been deprecated since 5.54. As of 6.14 they will begin to appear as duplicates everywhere and you may experience issues editing or working with activities that have revisions. For further instructions see this <a %1>Lab Snippet</a>.', [1 => $docAnchor]) . '</p>';
      }
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_14_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add File.is_public', 'alterSchemaField', 'File', 'is_public', [
      'title' => ts('File Is Public'),
      'sql_type' => 'boolean',
      'input_type' => 'Toggle',
      'required' => TRUE,
      'description' => ts('Controls whether file is stored in public or private directory.'),
      'default' => FALSE,
    ], 'AFTER `description`');

    $this->addTask('Add CustomField.file_is_public', 'alterSchemaField', 'CustomField', 'file_is_public', [
      'title' => ts('File Is Public'),
      'sql_type' => 'boolean',
      'input_type' => 'Toggle',
      'required' => TRUE,
      'description' => ts('Controls whether file is stored in public or private directory.'),
      'default' => FALSE,
    ], 'AFTER `is_view`');

    $this->addTask('Add default to CustomGroup.created_date', 'alterSchemaField', 'CustomGroup', 'created_date', [
      'title' => ts('Custom Group Created Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      // This is what's being added
      'default' => 'CURRENT_TIMESTAMP',
      'readonly' => TRUE,
      'description' => ts('Date and time this custom group was created.'),
    ]);

    $this->addTask('Add PaymentProcessor.config', 'alterSchemaField', 'PaymentProcessor', 'config', [
      'title' => ts('Configuration'),
      'sql_type' => 'text',
      'input_type' => 'Textarea',
      'required' => FALSE,
      'description' => ts('JSON blob of config as appropriate for the specific integration'),
    ], 'AFTER `accepted_credit_cards`');
    $this->addTask('Drop civicrm_activity.is_current_revision index', 'dropIndex', 'civicrm_activity', 'index_is_current_revision');

    $bin_collation = strpos(CRM_Core_BAO_SchemaHandler::getInUseCollation(), 'utf8mb4') !== FALSE ? 'utf8mb4_bin' : 'utf8_bin';
    $this->addTask('Make WordReplacement "find_word" required.', 'alterSchemaField', 'WordReplacement', 'find_word', [
      'sql_type' => 'varchar(255)',
      'description' => ts('Word which need to be replaced'),
      'add' => '4.4',
      'collate' => $bin_collation,
      'required' => TRUE,
    ]);
    $this->addTask('Make WordReplacement "replace_word" required', 'alterSchemaField', 'WordReplacement', 'replace_word', [
      'sql_type' => 'varchar(255)',
      'description' => ts('Word which will replace the word in find'),
      'add' => '4.4',
      'collate' => $bin_collation,
      'required' => TRUE,
    ]);
    $this->addTask('Replace TranslationSource "index_source_key" with "UI_source_key"', 'replaceTranslationSourceIndex');
    $this->addTask('Ensure TranslationSource.source_key foreign key constraint exists', 'ensureTranslationSourceForeignKey');
  }

  /**
   * @see https://lab.civicrm.org/dev/core/-/issues/6143
   */
  public static function replaceTranslationSourceIndex() {
    \CRM_Core_BAO_SchemaHandler::createMissingIndices(CRM_Core_BAO_SchemaHandler::getMissingIndices(FALSE, ['civicrm_translation_source']));
    \CRM_Core_BAO_SchemaHandler::dropIndexIfExists('civicrm_translation_source', 'index_source_key');
    return TRUE;
  }

  /**
   * Constraint likely not have been created in 6.7 upgrader -
   * may also have collation issues
   */
  public static function ensureTranslationSourceForeignKey($ctx) {
    // drop any existing constraint so we can update collations
    CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_translation', 'FK_civicrm_translation_source_key');

    // ensure matching character sets + collations on the two fields
    // Q: why do we use ascii rather than standard utf8?
    $sqlType = 'char(22) CHARACTER SET ascii COLLATE ascii_general_ci';

    self::alterSchemaField($ctx, 'Translation', 'source_key', [
      'title' => ts('Source Key'),
      'input_type' => 'Text',
      'sql_type' => $sqlType,
      'required' => FALSE,
      'description' => ts('Alternate FK when using translation_source instead of entity_table / entity_id'),
    ]);

    self::alterSchemaField($ctx, 'TranslationSource', 'source_key', [
      'title' => ts('Source Key'),
      'sql_type' => $sqlType,
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('hash(source)'),
    ]);

    self::alterSchemaField($ctx, 'TranslationSource', 'context_key', [
      'title' => ts('Context Key'),
      'sql_type' => $sqlType,
      'required' => TRUE,
      'description' => ts('hash(entity_name,entity_id,entity_field,entity)'),
    ]);

    $sql = CRM_Core_BAO_SchemaHandler::buildForeignKeySQL([
      'fk_table_name' => 'civicrm_translation_source',
      'fk_field_name' => 'source_key',
      'name' => 'source_key',
      'fk_attributes' => ' ON DELETE CASCADE',
    ], "\n", " ADD ", 'civicrm_translation');
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_translation " . $sql, [], TRUE, NULL, FALSE, FALSE);

    return TRUE;
  }

}
