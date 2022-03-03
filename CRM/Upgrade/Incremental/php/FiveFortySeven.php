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
 * Upgrade logic for the 5.47.x series.
 *
 * Each minor version in the series is handled by either a `5.47.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_47_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveFortySeven extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed before upgrade.
   *
   * Note: This function is called iteratively for each incremental upgrade step.
   * There must be a concrete step (eg 'X.Y.Z.mysql.tpl' or 'upgrade_X_Y_Z()').
   *
   * @param string $preUpgradeMessage
   * @param string $rev
   *   a version number, e.g. '4.4.alpha1', '4.4.beta3', '4.4.0'.
   * @param null $currentVer
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL): void {
    if ($rev === '5.47.alpha1') {
      $count = CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_contact WHERE preferred_mail_format != "Both"');
      if ($count) {
        $preUpgradeMessage .= '<p>' . ts('The contact field preferred mail format is being phased out. Modern email clients can handle receiving both formats so CiviCRM is moving towards always sending both and the field will be incrementally removed from the UI.')
        . ' <a href="https://lab.civicrm.org/dev/core/-/issues/2866">' . ts('See the issue for more detail') . '</a></p>';
      }
      // Check for custom groups with duplicate names
      $dupes = CRM_Core_DAO::singleValueQuery('SELECT COUNT(g1.id) FROM civicrm_custom_group g1, civicrm_custom_group g2 WHERE g1.name = g2.name AND g1.id > g2.id');
      if ($dupes) {
        $preUpgradeMessage .= '<p>' .
          ts('Your system has custom field groups with duplicate internal names. To ensure data integrity, the internal names will be automatically changed; user-facing titles will not be affected. Please review any custom code you may be using which relies on custom field group names.') .
          '</p>';
      }
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_47_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Migrate CiviGrant component to an extension', 'migrateCiviGrant');
    if (CRM_Core_Component::isEnabled('CiviGrant')) {
      $this->addExtensionTask('Re-enable CiviGrant', ['civigrant']);
    }
    $this->addTask('Add created_date to civicrm_relationship', 'addColumn', 'civicrm_relationship', 'created_date',
      "timestamp NOT NULL  DEFAULT CURRENT_TIMESTAMP COMMENT 'Relationship created date'"
    );
    $this->addTask('Add modified_date column to civicrm_relationship', 'addColumn',
      'civicrm_relationship', 'modified_date',
      "timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Relationship last modified.'"
    );
    $this->addTask('Set initial value for relationship created_date and modified_date to start_date', 'updateRelationshipDates');
    $this->addTask('core-issue#2122 - Add timezone column to Events', 'addColumn',
      'civicrm_event', 'event_tz', "text NULL DEFAULT NULL COMMENT 'Event\'s native time zone'"
    );
    $this->addTask('core-issue#2122 - Set the timezone to the default for existing Events', 'setEventTZDefault');
    $this->addTask('Drop CustomGroup UI_name_extends index', 'dropIndex', 'civicrm_custom_group', 'UI_name_extends');
    $this->addTask('Add CustomGroup UI_name index', 'addIndex', 'civicrm_custom_group', ['name'], 'UI');
  }

  /**
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function updateRelationshipDates(CRM_Queue_TaskContext $ctx): bool {
    CRM_Core_DAO::executeQuery('
      UPDATE civicrm_relationship SET created_date = start_date, modified_date = start_date
      WHERE start_date IS NOT NULL AND start_date > "1970-01-01"
    ');
    return TRUE;
  }

  /**
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   */
  public static function migrateCiviGrant(CRM_Queue_TaskContext $ctx): bool {
    $civiGrantEnabled = CRM_Core_Component::isEnabled('CiviGrant');
    if ($civiGrantEnabled) {
      CRM_Core_BAO_ConfigSetting::disableComponent('CiviGrant');
    }
    $civiGrantId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Component', 'CiviGrant', 'id', 'name');
    if ($civiGrantId) {
      foreach (['civicrm_menu', 'civicrm_option_value'] as $table) {
        CRM_Core_DAO::executeQuery("UPDATE $table SET component_id = NULL WHERE component_id = $civiGrantId", [], TRUE, NULL, FALSE, FALSE);
      }
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_component WHERE name = 'CiviGrant'", [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

  /**
   * Set the timezone to the default for existing Events.
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function setEventTZDefault(CRM_Queue_TaskContext $ctx) {
    // Set default for CiviCRM Events to user system timezone (most reasonable default);
    $defaultTZ = CRM_Core_Config::singleton()->userSystem->getTimeZoneString();
    CRM_Core_DAO::executeQuery('UPDATE `civicrm_event` SET `event_tz` = %1 WHERE `event_tz` IS NULL;', [1 => [$defaultTZ, 'String']]);
    return TRUE;
  }

}
