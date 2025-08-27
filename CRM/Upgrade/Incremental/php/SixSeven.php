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
 * Upgrade logic for the 6.7.x series.
 *
 * Each minor version in the series is handled by either a `6.7.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_7_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixSeven extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_7_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Create TranslationSource table', 'createEntityTable', '6.7.alpha1.TranslationSource.entityType.php');
    $this->addTask('Create Translation.source_key column', 'alterSchemaField', 'Translation', 'source_key', [
      'title' => ts('Source Key'),
      'input_type' => 'Text',
      'sql_type' => 'char(22) CHARACTER SET ascii',
      'required' => FALSE,
      'description' => ts('Alternate FK when using translation_source instead of entity_table / entity_id'),
      'add' => '6.7.alpha1',
      'entity_reference' => [
        'entity' => 'TranslationSource',
        'key' => 'source_key',
        'on_delete' => 'CASCADE',
      ],
    ]);
    $this->addTask(ts('Create index %1', [1 => 'civicrm_translation.index_source_key']), 'addIndex', 'civicrm_translation', 'source_key');
    $this->addTask('Update localization menu item', 'updateLocalizationMenuItem');
    $this->addTask('Add unsubscribe mode column to civicrm mailing', 'alterSchemaField', 'Mailing', 'unsubscribe_mode', [
      'title' => ts('One Click Unsubscribe Mode'),
      'sql_type' => 'varchar(70)',
      'input_type' => 'select',
      'description' => ts('One Click Unsubscribe mode either unsubscribe or opt-out'),
      'add' => '6.7',
      'input_attrs' => [
        'label' => ts('One Click Unsubscribe Mode'),
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Mailing_Service_ListUnsubscribe', 'unsubscribeModes'],
      ],
      'default' => 'unsubscribe',
      'required' => TRUE,
    ]);
    $this->addTask('Populate Unsubscribe mode column on civicrm_mailing', 'populateUnsubscribeMode');
  }

  public static function updateLocalizationMenuItem(): bool {
    CRM_Core_DAO::executeQuery("UPDATE civicrm_navigation SET has_separator = 1 WHERE name = 'Preferred Language Options'");
    return TRUE;
  }

  public static function populateUnsubscribeMode(): bool {
    CRM_Core_DAO::executeQuery("UPDATE civicrm_mailing SET unsubscribe_mode = 'unsubscribe' WHERE unsubscribe_mode IS NULL");
    return TRUE;
  }

}
