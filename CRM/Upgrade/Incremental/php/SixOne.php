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
 * Upgrade logic for the 6.1.x series.
 *
 * Each minor version in the series is handled by either a `6.1.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_1_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixOne extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_1_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);

    $this->addTask(ts('Replace Clear Caches & Reset Paths with Clear Caches in Nav Menu'), 'updateUpdateConfigBackendNavItem');
  }

  /**
   * The updateConfigBackend page has been removed - so remove any nav items linking to it
   *
   * Add a new menu item to Clear Caches directly
   *
   * @return bool
   */
  public static function updateUpdateConfigBackendNavItem() {
    // delete any entries to the path that no longer exists
    \Civi\Api4\Navigation::delete(FALSE)
      ->addWhere('url', '=', 'civicrm/admin/setting/updateConfigBackend?reset=1')
      ->execute();

    $systemSettingsNavItem = \Civi\Api4\Navigation::get(FALSE)
      ->addWhere('name', '=', 'System Settings')
      ->execute()
      ->first()['id'] ?? NULL;

    if (!$systemSettingsNavItem) {
      \Civi::log()->debug('Couldn\'t find System Settings Nav Menu Item to create new Clear Caches entry');
      return TRUE;
    }

    // Q: how to handle multi domain?
    \Civi\Api4\Navigation::create(FALSE)
      ->addValue('url', 'civicrm/menu/rebuild?reset=1')
      ->addValue('label', ts('Clear Caches'))
      ->addValue('name', 'cache_clear')
      ->addValue('has_separator', TRUE)
      ->addValue('parent_id', $systemSettingsNavItem)
      ->addValue('weight', 0)
      ->execute();

    return TRUE;
  }

}
