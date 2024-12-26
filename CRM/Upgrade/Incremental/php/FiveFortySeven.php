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
      $this->addExtensionTask('Enable CiviGrant extension', ['civigrant']);
    }
    $this->addTask('Add created_date to civicrm_relationship', 'addColumn', 'civicrm_relationship', 'created_date',
      "timestamp NOT NULL  DEFAULT CURRENT_TIMESTAMP COMMENT 'Relationship created date'"
    );
    $this->addTask('Add modified_date column to civicrm_relationship', 'addColumn',
      'civicrm_relationship', 'modified_date',
      "timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Relationship last modified.'"
    );
    $this->addTask('Set initial value for relationship created_date and modified_date to start_date', 'updateRelationshipDates');
    $this->addTask('Drop CustomGroup UI_name_extends index', 'dropIndex', 'civicrm_custom_group', 'UI_name_extends');
    $this->addTask('Add CustomGroup UI_name index', 'addIndex', 'civicrm_custom_group', ['name'], 'UI');
    if (CRM_Core_DAO::checkTableExists('civicrm_search_display')) {
      $this->addTask('Add SearchDisplay.acl_bypass', 'addColumn',
        'civicrm_search_display', 'acl_bypass',
        "tinyint DEFAULT 0 COMMENT 'Skip permission checks and ACLs when running this display.'"
      );
    }
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
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\NotImplementedException
   */
  public static function migrateCiviGrant(CRM_Queue_TaskContext $ctx): bool {
    $civiGrantEnabled = CRM_Core_Component::isEnabled('CiviGrant');
    // This was failing on multi-domain setups. See  https://github.com/civicrm/civicrm-core/pull/26043
    // Instead, we'll handle it in FiveSixtyTwo::consolidateComponents.
    //  if ($civiGrantEnabled) {
    //    CRM_Core_BAO_ConfigSetting::disableComponent('CiviGrant');
    //  }
    $civiGrantId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Component', 'CiviGrant', 'id', 'name');
    if ($civiGrantId) {
      foreach (['civicrm_menu', 'civicrm_option_value'] as $table) {
        CRM_Core_DAO::executeQuery("UPDATE $table SET component_id = NULL WHERE component_id = $civiGrantId", [], TRUE, NULL, FALSE, FALSE);
      }
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_component WHERE name = 'CiviGrant'", [], TRUE, NULL, FALSE, FALSE);
    }
    // Reload the civi cache here as 'table_name' may not be in the cached entities
    // array generated in an earlier version retrieved via $cache->get('api4.entities.info', []);
    Civi::cache('metadata')->clear();

    // There are existing records which should be managed by `civigrant`. To assign ownership, we need
    // placeholders in `civicrm_extension` and `civicrm_managed`.
    $ext = new CRM_Core_DAO_Extension();
    $ext->full_name = 'civigrant';
    if (!$ext->find(TRUE)) {
      $ext->type = 'module';
      $ext->name = 'CiviGrant';
      $ext->label = ts('CiviGrant');
      $ext->file = 'civigrant';
      $ext->is_active = 0; /* Not active _yet_. If site uses CiviGrant, we will re-activate once the core-schema has been revised. */
      $ext->save();
      CRM_Extension_System::singleton()->getManager()->refresh();

      $managedItems = [
        'Navigation_Grants' => [
          'entity' => 'Navigation',
          'values' => [
            'name' => 'Grants',
            'domain_id' => 'current_domain',
          ],
        ],
        'Navigation_Grants_Navigation_Dashboard' => [
          'entity' => 'Navigation',
          'values' => [
            'name' => 'Dashboard',
            'url' => 'civicrm/grant?reset=1',
            'domain_id' => 'current_domain',
          ],
        ],
        'Navigation_Grants_Navigation_New_Grant' => [
          'entity' => 'Navigation',
          'values' => [
            'name' => 'New Grant',
            'url' => 'civicrm/grant/add?reset=1&action=add&context=standalone',
            'domain_id' => 'current_domain',
          ],
        ],
        'Navigation_Grants_Navigation_Find_Grants' => [
          'entity' => 'Navigation',
          'values' => [
            'name' => 'Find Grants',
            'url' => 'civicrm/grant/search?reset=1',
            'domain_id' => 'current_domain',
          ],
        ],
        'Navigation_CiviGrant' => [
          'entity' => 'Navigation',
          'values' => [
            'name' => 'CiviGrant',
            'domain_id' => 'current_domain',
          ],
        ],
        'Navigation_CiviGrant_Navigation_Grant_Types' => [
          'entity' => 'Navigation',
          'values' => [
            'name' => 'Grant Types',
            'domain_id' => 'current_domain',
          ],
        ],
        'Navigation_CiviGrant_Navigation_Grant_Status' => [
          'entity' => 'Navigation',
          'values' => [
            'name' => 'Grant Status',
            'domain_id' => 'current_domain',
          ],
        ],
      ];
      // Create an entry in civicrm_managed for each existing record that will be managed by the extension
      foreach ($managedItems as $name => $item) {
        $params = ['checkPermissions' => FALSE];
        foreach ($item['values'] as $k => $v) {
          $params['where'][] = [$k, '=', $v];
        }
        $record = civicrm_api4($item['entity'], 'get', $params)->first();
        if ($record) {
          $mgd = new CRM_Core_DAO_Managed();
          $mgd->name = $name;
          $mgd->module = 'civigrant';
          $mgd->entity_type = $item['entity'];
          if (!$mgd->find(TRUE)) {
            $mgd->entity_id = $record['id'];
            $mgd->cleanup = 'unused';
            $mgd->save();
          }
          // Disable record if CiviGrant disabled
          if (!$civiGrantEnabled && !empty($record['is_active'])) {
            civicrm_api4($item['entity'], 'update', [
              'checkPermissions' => FALSE,
              'values' => ['id' => $record['id'], 'is_active' => FALSE],
            ]);
          }
        }
      }
    }
    return TRUE;
  }

}
