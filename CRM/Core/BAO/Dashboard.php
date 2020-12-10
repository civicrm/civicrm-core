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
    CRM_Utils_Hook::pre($hook, 'Dashboard', CRM_Utils_Array::value('id', $params), $params);
    $dao = self::addDashlet($params);
    CRM_Utils_Hook::post($hook, 'Dashboard', $dao->id, $dao);
    return $dao;
  }

  /**
   * Get all available contact dashlets
   *
   * @return array
   *   array of dashlets
   */
  public static function getContactDashlets() {
    if (!isset(Civi::$statics[__CLASS__][__FUNCTION__])) {
      Civi::$statics[__CLASS__][__FUNCTION__] = [];
      $params = [
        'select' => ['*', 'dashboard_contact.*'],
        'join' => [
          ['DashboardContact AS dashboard_contact', FALSE, ['dashboard_contact.contact_id', '=', CRM_Core_Session::getLoggedInContactID()]],
        ],
        'where' => [
          ['domain_id', '=', 'current_domain'],
        ],
        'orderBy' => ['dashboard_contact.weight' => 'ASC'],
      ];

      // Get Dashboard + any joined DashboardContact records.
      $results = civicrm_api4('Dashboard', 'get', $params);

      // If empty, then initialize default dashlets for this user.
      if (!array_filter($results->column('dashboard_contact.id'))) {
        self::initializeDashlets();
      }
      $results = civicrm_api4('Dashboard', 'get', $params);

      foreach ($results as $item) {
        if ($item['is_active'] && self::checkPermission($item['permission'], $item['permission_operator'])) {
          Civi::$statics[__CLASS__][__FUNCTION__][] = $item;
        }
      }
    }
    return Civi::$statics[__CLASS__][__FUNCTION__];
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
      if (!empty($allDashlets[$name]) && !empty($allDashlets[$name]['id'])) {
        $defaultDashlets[$name] = [
          'dashboard_id' => $allDashlets[$name]['id'],
          'is_active' => 1,
          'column_no' => $column,
        ];
      }
    }
    CRM_Utils_Hook::dashboard_defaults($allDashlets, $defaultDashlets);
    if (is_array($defaultDashlets) && !empty($defaultDashlets)) {
      \Civi\Api4\DashboardContact::save(FALSE)
        ->setRecords($defaultDashlets)
        ->setDefaults(['contact_id' => CRM_Core_Session::getLoggedInContactID()])
        ->execute();
    }
  }

  /**
   * Check dashlet permission for current user.
   *
   * @param array $permissions
   * @param string $operator
   *
   * @return bool
   *   true if user has permission to view dashlet
   */
  public static function checkPermission($permissions, $operator) {
    if ($permissions) {
      $config = CRM_Core_Config::singleton();

      static $allComponents;
      if (!$allComponents) {
        $allComponents = CRM_Core_Component::getNames();
      }

      $hasPermission = FALSE;
      foreach ($permissions as $key) {
        $showDashlet = TRUE;

        $componentName = NULL;
        if (strpos($key, 'access') === 0) {
          $componentName = trim(substr($key, 6));
          if (!in_array($componentName, $allComponents)) {
            $componentName = NULL;
          }
        }

        // hack to handle case permissions
        if (!$componentName
          && in_array($key, ['access my cases and activities', 'access all cases and activities'])
        ) {
          $componentName = 'CiviCase';
        }

        //hack to determine if it's a component related permission
        if ($componentName) {
          if (!in_array($componentName, $config->enableComponents) ||
            !CRM_Core_Permission::check($key)
          ) {
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

      if (!$showDashlet && !$hasPermission) {
        return FALSE;
      }
      else {
        return TRUE;
      }
    }
    else {
      // if permission is not set consider everyone has permission to access it.
      return TRUE;
    }
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
                        WHERE url LIKE '" . CRM_Utils_Array::value('instanceURL', $params) . "&%'";
      $dashboardID = CRM_Core_DAO::singleValueQuery($query);
    }

    $dashlet = new CRM_Core_DAO_Dashboard();

    if (!$dashboardID) {
      // Assign domain before search to allow identical dashlets in different domains.
      $dashlet->domain_id = $params['domain_id'] ?? CRM_Core_Config::domainID();

      // Try and find an existing dashlet - it will be updated if found.
      if (!empty($params['name']) || !empty($params['url'])) {
        $dashlet->name = $params['name'] ?? NULL;
        $dashlet->url = $params['url'] ?? NULL;
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
