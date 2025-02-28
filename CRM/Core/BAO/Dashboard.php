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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\Api4\DashboardContact;

/**
 * Class contains Contact dashboard related functions.
 */
class CRM_Core_BAO_Dashboard extends CRM_Core_DAO_Dashboard {

  /**
   * Create or update Dashboard.
   *
   * @param array $params
   *
   * @return CRM_Core_DAO_Dashboard
   */
  public static function create($params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'Dashboard', $params['id'] ?? NULL, $params);
    $dao = self::addDashlet($params);
    CRM_Utils_Hook::post($hook, 'Dashboard', $dao->id, $dao);
    return $dao;
  }

  /**
   * Get all available contact dashlets
   *
   * @return array
   *   array of dashlets
   * @throws \CRM_Core_Exception
   */
  public static function getContactDashlets(): array {
    $cid = CRM_Core_Session::getLoggedInContactID();
    if ($cid && !isset(Civi::$statics[__CLASS__][__FUNCTION__][$cid])) {
      Civi::$statics[__CLASS__][__FUNCTION__][$cid] = [];
      // If empty, then initialize default dashlets for this user.
      if (0 === DashboardContact::get(FALSE)->selectRowCount()->addWhere('contact_id', '=', $cid)->execute()->count()) {
        self::initializeDashlets();
      }
      $contactDashboards = (array) DashboardContact::get(FALSE)
        ->addSelect('column_no', 'is_active', 'dashboard_id', 'weight', 'contact_id')
        ->addWhere('contact_id', '=', $cid)
        ->addOrderBy('weight')
        ->execute()->indexBy('dashboard_id');

      $params = [
        'select' => ['*', 'dashboard_contact.*'],
        'where' => [
          ['domain_id', '=', 'current_domain'],
        ],
      ];

      // Get Dashboard + any joined DashboardContact records.
      $results = (array) civicrm_api4('Dashboard', 'get', $params);
      foreach ($results as $item) {
        $item['dashboard_contact.id'] = $contactDashboards[$item['id']]['id'] ?? NULL;
        $item['dashboard_contact.contact_id'] = $contactDashboards[$item['id']]['contact_id'] ?? NULL;
        $item['dashboard_contact.weight'] = $contactDashboards[$item['id']]['weight'] ?? NULL;
        $item['dashboard_contact.column_no'] = $contactDashboards[$item['id']]['column_no'] ?? NULL;
        $item['dashboard_contact.is_active'] = $contactDashboards[$item['id']]['is_active'] ?? NULL;
        if ($item['is_active'] && self::checkPermission($item['permission'], $item['permission_operator'])) {
          Civi::$statics[__CLASS__][__FUNCTION__][$cid][] = $item;
        }
      }
      usort(Civi::$statics[__CLASS__][__FUNCTION__][$cid], static function ($a, $b) {
        // Sort by dashboard contact weight, preferring not null to null.
        // I had hoped to do this in mysql either by
        // 1) making the dashboard contact part of the query NOT permissioned while
        // the parent query IS or
        // 2) using FIELD like
        // $params['orderBy'] = ['FIELD(id,' . implode(',', array_keys($contactDashboards)) . ')' => 'ASC'];
        // 3) or making the dashboard contact acl more inclusive such that 'view own contact'
        // is not required to view own contact's acl
        // but I couldn't see a way to make any of the above work. Perhaps improve in master?
        if (!isset($b['dashboard_contact.weight']) && !isset($a[$b['dashboard_contact.weight']])) {
          return 0;
        }
        if (!isset($b['dashboard_contact.weight'])) {
          return -1;
        }
        if (!isset($a['dashboard_contact.weight'])) {
          return 1;
        }
        return $a['dashboard_contact.weight'] <=> $b['dashboard_contact.weight'];
      });
    }
    return Civi::$statics[__CLASS__][__FUNCTION__][$cid] ?? [];
  }

  /**
   * Set default dashlets for new users.
   *
   * Called when a user accesses their dashboard for the first time.
   */
  public static function initializeDashlets() {
    $allDashlets = (array) civicrm_api4('Dashboard', 'get', [
      'where' => [['domain_id', '=', 'current_domain']],
    ], 'name');
    $defaultDashlets = [];
    $defaults = ['blog' => 1, 'getting-started' => '0'];
    foreach ($defaults as $name => $column) {
      if (!empty($allDashlets[$name]['id'])) {
        $defaultDashlets[$name] = [
          'dashboard_id' => $allDashlets[$name]['id'],
          'is_active' => 1,
          'column_no' => $column,
        ];
      }
    }
    CRM_Utils_Hook::dashboard_defaults($allDashlets, $defaultDashlets);
    if (is_array($defaultDashlets) && !empty($defaultDashlets)) {
      DashboardContact::save(FALSE)
        ->setRecords($defaultDashlets)
        ->setDefaults(['contact_id' => CRM_Core_Session::getLoggedInContactID()])
        ->execute();
    }
  }

  /**
   * Check dashlet permission for current user.
   *
   * @param array|null $permissions
   * @param string|null $operator
   *
   * @return bool
   *   true if user has permission to view dashlet
   */
  private static function checkPermission(?array $permissions, ?string $operator): bool {
    if ($permissions) {
      static $allComponents;
      if (!$allComponents) {
        $allComponents = CRM_Core_Component::getNames();
      }

      $hasPermission = FALSE;
      foreach ($permissions as $key) {
        $showDashlet = TRUE;

        $componentName = CRM_Core_Permission::getComponentName($key);

        // If the permission depends on a component, ensure it is enabled
        if ($componentName) {
          if (!CRM_Core_Component::isEnabled($componentName) || !CRM_Core_Permission::check($key)) {
            $showDashlet = FALSE;
            if ($operator == 'AND') {
              return $showDashlet;
            }
          }
          else {
            $hasPermission = TRUE;
          }
        }
        elseif (!CRM_Core_Permission::check($key)) {
          $showDashlet = FALSE;
          if ($operator == 'AND') {
            return $showDashlet;
          }
        }
        else {
          $hasPermission = TRUE;
        }
      }

      return $showDashlet || $hasPermission;
    }
    // If permission is not set consider everyone has access.
    return TRUE;
  }

  /**
   * Add dashlets.
   *
   * @param array $params
   *
   * @return object
   *   $dashlet returns dashlet object
   */
  public static function addDashlet(&$params) {

    // special case to handle duplicate entries for report instances
    $dashboardID = $params['id'] ?? NULL;

    if (!empty($params['instanceURL'])) {
      $query = "SELECT id
                        FROM `civicrm_dashboard`
                        WHERE url LIKE '" . ($params['instanceURL'] ?? '') . "&%'";
      $dashboardID = CRM_Core_DAO::singleValueQuery($query);
    }

    $dashlet = new CRM_Core_DAO_Dashboard();

    if (!$dashboardID) {
      // Assign domain before search to allow identical dashlets in different domains.
      $dashlet->domain_id = $params['domain_id'] ?? CRM_Core_Config::domainID();

      // Try and find an existing dashlet - it will be updated if found.
      if (!empty($params['name'])) {
        $dashlet->name = $params['name'] ?? NULL;
        $dashlet->find(TRUE);
      }
    }
    else {
      $dashlet->id = $dashboardID;
    }

    $dashlet->copyValues($params);
    $dashlet->save();

    // now we need to make dashlet entries for each contact
    self::addContactDashlet($dashlet);

    return $dashlet;
  }

  /**
   * Update contact dashboard with new dashlet.
   *
   * @param object $dashlet
   */
  public static function addContactDashlet($dashlet) {
    $admin = CRM_Core_Permission::check('administer CiviCRM');

    // if dashlet is created by admin then you need to add it all contacts.
    // else just add to contact who is creating this dashlet
    $contactIDs = [];
    if ($admin) {
      $query = "SELECT distinct( contact_id )
                        FROM civicrm_dashboard_contact";
      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {
        $contactIDs[$dao->contact_id] = NULL;
      }
    }
    else {
      //Get the id of Logged in User
      $contactID = CRM_Core_Session::getLoggedInContactID();
      if (!empty($contactID)) {
        $contactIDs[$contactID] = NULL;
      }
    }

    // Remove contact ids that already have this dashlet to avoid DB
    // constraint violation.
    $query = "SELECT distinct( contact_id )
              FROM civicrm_dashboard_contact WHERE dashboard_id = {$dashlet->id}";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      if (array_key_exists($dao->contact_id, $contactIDs)) {
        unset($contactIDs[$dao->contact_id]);
      }
    }
    if (!empty($contactIDs)) {
      foreach ($contactIDs as $contactID => $value) {
        $valuesArray[] = " ( {$dashlet->id}, {$contactID} )";
      }
      $valuesString = implode(',', $valuesArray);
      $query = "
                  INSERT INTO civicrm_dashboard_contact ( dashboard_id, contact_id )
                  VALUES {$valuesString}";

      CRM_Core_DAO::executeQuery($query);
    }
  }

}
