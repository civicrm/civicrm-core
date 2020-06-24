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
 * Upgrade logic for FiveNineteen */
class CRM_Upgrade_Incremental_php_FiveNineteen extends CRM_Upgrade_Incremental_Base {

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

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_19_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add api4 menu', 'api4Menu');
    $this->addTask('Add is_active field to civicrm_status_pref', 'addColumn', 'civicrm_status_pref', 'is_active',
      "tinyint(4) DEFAULT '1' COMMENT 'Is this status check active'", FALSE, '5.19.0');
  }

  /**
   * Add menu item for api4 explorer; rename v3 explorer menu item.
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function api4Menu(CRM_Queue_TaskContext $ctx) {
    try {
      $v3Item = civicrm_api3('Navigation', 'get', [
        'name' => 'API Explorer',
        'return' => ['id', 'parent_id', 'weight'],
        'sequential' => 1,
        'domain_id' => CRM_Core_Config::domainID(),
        'api.Navigation.create' => ['label' => ts("Api Explorer v3")],
      ]);
      $existing = civicrm_api3('Navigation', 'getcount', [
        'name' => "Api Explorer v4",
        'domain_id' => CRM_Core_Config::domainID(),
      ]);
      if (!$existing) {
        civicrm_api3('Navigation', 'create', [
          'parent_id' => $v3Item['values'][0]['parent_id'] ?? 'Developer',
          'label' => ts("Api Explorer v4"),
          'weight' => $v3Item['values'][0]['weight'] ?? 2,
          'name' => "Api Explorer v4",
          'permission' => "administer CiviCRM",
          'url' => "civicrm/api4#/explorer",
          'is_active' => 1,
        ]);
      }
    }
    catch (Exception $e) {
      // Couldn't create menu item.
    }
    return TRUE;
  }

}
