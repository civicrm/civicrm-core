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
