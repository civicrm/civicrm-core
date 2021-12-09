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
 * Upgrade logic for FiveNineteen
 */
class CRM_Upgrade_Incremental_php_FiveNineteen extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_19_alpha1($rev) {
    // Not used // $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
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
