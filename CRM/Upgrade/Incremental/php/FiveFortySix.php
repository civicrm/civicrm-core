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
 * Upgrade logic for FiveFortySix
 */
class CRM_Upgrade_Incremental_php_FiveFortySix extends CRM_Upgrade_Incremental_Base {

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
  }

  /**
   * Compute any messages which should be displayed after upgrade.
   *
   * Note: This function is called iteratively for each incremental upgrade step.
   * There must be a concrete step (eg 'X.Y.Z.mysql.tpl' or 'upgrade_X_Y_Z()').
   *
   * @param string $postUpgradeMessage
   *   alterable.
   * @param string $rev
   *   an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs.
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev): void {
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_46_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add "Import Custom Data" menu', 'addImportCustomMenu');
  }

  /**
   * Add menu item "Import MultiValued Custom" below "Import Activities"
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function addImportCustomMenu(CRM_Queue_TaskContext $ctx) {
    try {
      // Get "Import Activities" and remove separator
      $importActivities = civicrm_api3('Navigation', 'get', [
        'name' => 'Import Activities',
        'return' => ['id', 'parent_id', 'weight'],
        'sequential' => 1,
        'domain_id' => CRM_Core_Config::domainID(),
        'api.Navigation.create' => ['has_separator' => ''],
      ])['values'][0] ?? NULL;
      $existing = civicrm_api3('Navigation', 'getcount', [
        'name' => "Import MultiValued Custom",
        'domain_id' => CRM_Core_Config::domainID(),
      ]);
      // Insert "Import MultiValued Custom" below "Import Activities"
      if ($importActivities && !$existing) {
        // Use APIv4 because it will auto-adjust weights unlike v3
        civicrm_api4('Navigation', 'create', [
          'checkPermissions' => FALSE,
          'values' => [
            'parent_id' => $importActivities['parent_id'],
            'label' => ts('Import Custom Data'),
            'weight' => $importActivities['weight'] + 1,
            'name' => 'Import MultiValued Custom',
            'permission' => "import contacts",
            'url' => "civicrm/import/custom?reset=1",
            'has_separator' => 1,
            'is_active' => 1,
          ],
        ]);
      }
    }
    catch (Exception $e) {
      // Couldn't create menu item.
    }
    return TRUE;
  }

}
