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

    $this->addTask('Update afform tab names', 'updateAfformTabs');
    $this->addTask('Replace Clear Caches & Reset Paths with Clear Caches in Nav Menu', 'updateUpdateConfigBackendNavItem');
  }

  /**
   * The updateConfigBackend page has been removed - so remove any nav items linking to it
   *
   * Add a new menu item to Clear Caches directly
   *
   * @return bool
   */
  public static function updateUpdateConfigBackendNavItem() {
    $domainID = CRM_Core_Config::domainID();

    // delete any entries to the path that no longer exists
    // doesn't seem necessary to restrict by domain?
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_navigation WHERE url = "civicrm/admin/setting/updateConfigBackend?reset=1"');

    $systemSettingsNavItem = CRM_Core_DAO::singleValueQuery("SELECT id
      FROM civicrm_navigation
      WHERE name = 'System Settings' AND domain_id = {$domainID}
    ");

    if (!$systemSettingsNavItem) {
      \Civi::log()->debug('Couldn\'t find System Settings Nav Menu Item to create new Clear Caches entry');
      return TRUE;
    }

    $exists = CRM_Core_DAO::singleValueQuery("SELECT id
      FROM civicrm_navigation
      WHERE name = 'cache_clear' AND domain_id = {$domainID}
    ");

    if ($exists) {
      // already exists, we can finish early
      return TRUE;
    }

    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_navigation
        (
          url, label, name,
          has_separator, parent_id, weight,
          permission, domain_id
        )
      VALUES
        (
          'civicrm/menu/rebuild?reset=1', 'Clear Caches', 'cache_clear',
          1, {$systemSettingsNavItem}, 0,
          'administer CiviCRM', {$domainID}
        )
    ");

    return TRUE;
  }

  /**
   * If the ContactLayout extension is installed, update its stored tab names to keep up
   * with core changes to Afform tabs.
   *
   * @see https://github.com/civicrm/civicrm-core/pull/27196
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function updateAfformTabs(CRM_Queue_TaskContext $ctx) {
    $convert = function($id) {
      if ($id === 'afsearchGrants') {
        return 'grant';
      }
      if (preg_match('#^(afform|afsearch)#i', $id)) {
        $tabId = CRM_Utils_String::convertStringToSnakeCase(preg_replace('#^(afformtab|afsearchtab|afform|afsearch)#i', '', $id));
        // @see afform_civicrm_tabset()
        if (str_starts_with($tabId, 'custom_')) {
          // custom group tab forms use name, but need to replace tabs using ID
          // remove 'afsearchTabCustom_' from the form name to get the group name
          $groupName = substr($id, 18);
          $groupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $groupName, 'id', 'name');
          if ($groupId) {
            $tabId = 'custom_' . $groupId;
          }
        }
        return $tabId;
      }
      return $id;
    };

    $setting = \Civi::settings()->get('contactlayout_default_tabs');
    if ($setting && is_array($setting)) {
      foreach ($setting as $index => $tab) {
        $setting[$index]['id'] = $convert($tab['id']);
      }
      \Civi::settings()->set('contactlayout_default_tabs', $setting);
    }
    if (CRM_Core_DAO::checkTableExists('civicrm_contact_layout')) {
      // Can't use the api due to extension loading issues
      $dao = CRM_Core_DAO::executeQuery('SELECT * FROM civicrm_contact_layout');
      while ($dao->fetch()) {
        if (!empty($dao->tabs)) {
          $tabs = CRM_Core_DAO::unSerializeField($dao->tabs, CRM_Core_DAO::SERIALIZE_JSON);
          foreach ($tabs as $index => $tab) {
            $tabs[$index]['id'] = $convert($tab['id']);
          }
          CRM_Core_DAO::executeQuery('UPDATE civicrm_contact_layout SET tabs = %1 WHERE id = %2', [
            1 => [CRM_Core_DAO::serializeField($tabs, CRM_Core_DAO::SERIALIZE_JSON), 'String'],
            2 => [$dao->id, 'Integer'],
          ]);
        }
      }
    }
    return TRUE;
  }

}
