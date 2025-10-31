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
 * Upgrade logic for the 6.0.x series.
 *
 * Each minor version in the series is handled by either a `6.0.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_0_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixZero extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_0_alpha1($rev): void {
    $this->addSnapshotTask('from_addresses', CRM_Utils_SQL_Select::from('civicrm_option_value')
      ->where('option_group_id = (select id from civicrm_option_group where name = "from_email_address")')
    );
    $this->addTask('Install SiteEmailAddress entity', 'createEntityTable', '6.0.alpha1.SiteEmailAddress.entityType.php');
    $this->addTask('Migrate from_email_address option group to SiteEmailAddress entity', 'migrateFromEmailAddressValues');
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask(
      'Convert MembershipLog.modified_date to timestamp',
      'alterColumn',
      'civicrm_membership_log',
      'modified_date',
      "timestamp NULL DEFAULT NULL COMMENT 'Date this membership modification action was logged.'",
      FALSE
    );
    $this->addTask('Set a default activity priority', 'addActivityPriorityDefault');
    $this->addSimpleExtensionTask('Enable dedupe backward compatibility', ['legacydedupefinder']);
    $this->addTask('Increase field length of civicrm_action_schedule.entity_status', 'alterSchemaField', 'ActionSchedule', 'entity_status', [
      'title' => ts('Entity Status'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Select',
      'description' => ts('Entity status'),
      'add' => '3.4',
      'serialize' => CRM_Core_DAO::SERIALIZE_SEPARATOR_TRIMMED,
      'input_attrs' => [
        'label' => ts('Entity Status'),
        'multiple' => '1',
        'control_field' => 'entity_value',
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_ActionSchedule', 'getEntityStatusOptions'],
      ],
    ]);
    $this->addTask('Increase field length of civicrm_action_schedule.start_action_date', 'alterSchemaField', 'ActionSchedule', 'start_action_date', [
      'title' => ts('Start Action Date'),
      'sql_type' => 'varchar(2048)',
      'input_type' => 'Select',
      'description' => ts('Entity date'),
      'add' => '3.4',
      'input_attrs' => [
        'label' => ts('Start Date'),
        'control_field' => 'entity_value',
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_ActionSchedule', 'getActionDateOptions'],
      ],
    ]);
  }

  public static function migrateFromEmailAddressValues($rev): bool {
    $select = <<<SQL
SELECT
    value AS id,
    TRIM(BOTH '"' FROM SUBSTRING_INDEX(SUBSTRING_INDEX(TRIM(label), '"', 2), '"', -1)) AS display_name, -- Extract quoted part as the display_name
    TRIM(BOTH '<>' FROM SUBSTRING_INDEX(SUBSTRING_INDEX(TRIM(label), '<', -1), '>', 1)) AS email, -- Extract part in brackets as email
    description,
    is_active,
    is_default,
    domain_id
FROM
    civicrm_option_value
WHERE
    option_group_id IN (SELECT id FROM civicrm_option_group WHERE name = 'from_email_address')
SQL;
    $values = CRM_Core_DAO::executeQuery($select)->fetchAll();

    if (!$values) {
      // Upgrade step has already run. Skip.
      return TRUE;
    }

    $usedIds = [];
    foreach ($values as &$value) {
      // Ensure unique 'id' keys by incrementing duplicates
      while (in_array($value['id'], $usedIds)) {
        $value['id']++;
      }
      $usedIds[] = $value['id'];
    }

    CRM_Utils_SQL_Insert::into('civicrm_site_email_address')
      ->rows($values)
      ->execute();
    return TRUE;
  }

  /**
   * This task sets the Normal option as the default activity status.
   * It was previously hardcoded in Form and BAO files.
   *
   * @return bool
   */
  public static function addActivityPriorityDefault() {
    // Check if a default option is already set (could be other than Normal)
    $oid = CRM_Core_DAO::singleValueQuery('SELECT ov.id
      FROM civicrm_option_value ov
      LEFT JOIN civicrm_option_group og ON (og.id = ov.option_group_id)
      WHERE og.name = %1 and ov.is_default = 1', [
        1 => ['priority', 'String'],
      ]);

    if ($oid) {
      return TRUE;
    }

    // Set 'Normal' as the default
    $sql = CRM_Utils_SQL::interpolate('UPDATE civicrm_option_value SET is_default = 1 WHERE option_group_id = #group AND name = @name', [
      'name' => 'Normal',
      'group' => CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_option_group WHERE name = "priority"'),
    ]);
    CRM_Core_DAO::executeQuery($sql);

    return TRUE;
  }

}
