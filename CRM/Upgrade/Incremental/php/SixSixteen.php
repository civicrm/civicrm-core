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
 * Upgrade logic for the 6.16.x series.
 *
 * Each minor version in the series is handled by either a `6.16.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_16_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixSixteen extends CRM_Upgrade_Incremental_Base {

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    if ($rev === '6.16.alpha1') {
      if (Civi::settings()->get('search_mysql_fts')) {
        $settingUrl = (string) \Civi::url('civicrm/admin/setting/search')->addQuery(['reset' => 1]);
        $preUpgradeMessage .= '<p>' . ts('This upgrade will add a new Full Text Search index on `civicrm_contact` table. If you have lots of contacts, this may take a while and use a lot of space on your database server. If you don\'t want this, turn off Use Mysql Full Text Search in <a href="%1">Search Preferences</a> before running the upgrade.', [1 => $settingUrl]) . '</p>';
      }
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_16_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);

    $this->addTask('Add column "LineItem.created_date"', 'alterSchemaField', 'LineItem', 'created_date', [
      'title' => ts('Created Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'readonly' => TRUE,
      'description' => ts('When was the LineItem created.'),
      'add' => '6.16',
      'unique_name' => 'lineitem_created_date',
      'default' => 'CURRENT_TIMESTAMP',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'label' => ts('Created Date'),
      ],
    ]);
    $this->addTask('Add column "LineItem.modified_date"', 'alterSchemaField', 'LineItem', 'modified_date', [
      'title' => ts('Modified Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'readonly' => TRUE,
      'description' => ts('When was the LineItem modified.'),
      'add' => '6.16',
      'unique_name' => 'lineitem_modified_date',
      'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'label' => ts('Modified Date'),
      ],
    ]);
    $this->addTask('Change civicrm_tag.used_for to varchar(512)', 'alterSchemaField', 'Tag', 'used_for', [
      'title' => ts('Used For'),
      'sql_type' => 'varchar(512)',
      'input_type' => 'Select',
      'add' => '3.2',
      'default' => NULL,
      'serialize' => CRM_Core_DAO::SERIALIZE_COMMA,
      'pseudoconstant' => [
        'option_group_name' => 'tag_used_for',
      ],
    ]);

    $this->addTask(ts('Create Mysql Full Text Search indices if active'), 'createMissingFtsIndices');
  }

}
