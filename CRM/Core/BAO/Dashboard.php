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
   * Add Dashboard.
   *
   * @param array $params
   *   Values.
   *
   *
   * @return object
   */
  public static function create($params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'Dashboard', CRM_Utils_Array::value('id', $params), $params);
    $dao = self::addDashlet($params);
    CRM_Utils_Hook::post($hook, 'Dashboard', $dao->id, $dao);
    return $dao;
  }

  /**
   * Get the list of dashlets enabled by admin.
   *
   * @param bool $all
   *   All or only active.
   * @param bool $checkPermission
   *   All or only authorized for the current user.
   *
   * @return array
   *   array of dashlets
   */
  public static function getDashlets($all = TRUE, $checkPermission = TRUE) {
    $dashlets = [];
    $dao = new CRM_Core_DAO_Dashboard();

    if (!$all) {
      $dao->is_active = 1;
    }

    $dao->domain_id = CRM_Core_Config::domainID();

    $dao->find();
    while ($dao->fetch()) {
      if ($checkPermission && !self::checkPermission($dao->permission, $dao->permission_operator)) {
        continue;
      }

      $values = [];
      CRM_Core_DAO::storeValues($dao, $values);
      $dashlets[$dao->id] = $values;
    }

    return $dashlets;
  }

  /**
   * Get the list of dashlets for the current user or the specified user.
   *
   * Additionlly, initializes the dashboard with defaults if this is the
   * user's first visit to their dashboard.
   *
   * @param int $contactID
   *   Defaults to the current user.
   *
   * @return array
   *   array of dashlets
   */
  public static function getContactDashlets($contactID = NULL) {
    $contactID = $contactID ? $contactID : CRM_Core_Session::getLoggedInContactID();
    $dashlets = [];

    // Get contact dashboard dashlets.
    $results = civicrm_api3('DashboardContact', 'get', [
      'contact_id' => $contactID,
      'is_active' => 1,
      'dashboard_id.is_active' => 1,
      'dashboard_id.domain_id' => CRM_Core_Config::domainID(),
      'options' => ['sort' => 'weight', 'limit' => 0],
      'return' => [
        'id',
        'weight',
        'column_no',
        'dashboard_id',
        'dashboard_id.name',
        'dashboard_id.label',
        'dashboard_id.url',
        'dashboard_id.fullscreen_url',
        'dashboard_id.cache_minutes',
        'dashboard_id.permission',
        'dashboard_id.permission_operator',
      ],
    ]);

    foreach ($results['values'] as $item) {
      if (self::checkPermission(CRM_Utils_Array::value('dashboard_id.permission', $item), CRM_Utils_Array::value('dashboard_id.permission_operator', $item))) {
        $dashlets[$item['id']] = [
          'dashboard_id' => $item['dashboard_id'],
          'weight' => $item['weight'],
          'column_no' => $item['column_no'],
          'name' => $item['dashboard_id.name'],
          'label' => $item['dashboard_id.label'],
          'url' => $item['dashboard_id.url'],
          'cache_minutes' => $item['dashboard_id.cache_minutes'],
          'fullscreen_url' => $item['dashboard_id.fullscreen_url'] ?? NULL,
        ];
      }
    }

    // If empty, then initialize default dashlets for this user.
    if (!$results['count']) {
      // They may just have disabled all their dashlets. Check if any records exist for this contact.
      if (!civicrm_api3('DashboardContact', 'getcount', ['contact_id' => $contactID, 'dashboard_id.domain_id' => CRM_Core_Config::domainID()])) {
        $dashlets = self::initializeDashlets();
      }
    }

    return $dashlets;
  }

  /**
   * @return array
   */
  public static function getContactDashletsForJS() {
    $data = [[], []];
    foreach (self::getContactDashlets() as $item) {
      $data[$item['column_no']][] = [
        'id' => (int) $item['dashboard_id'],
        'name' => $item['name'],
        'title' => $item['label'],
        'url' => self::parseUrl($item['url']),
        'cacheMinutes' => $item['cache_minutes'],
        'fullscreenUrl' => self::parseUrl($item['fullscreen_url']),
      ];
    }
    return $data;
  }

  /**
   * Setup default dashlets for new users.
   *
   * When a user accesses their dashboard for the first time, set up
   * the default dashlets.
   *
   * @return array
   *   Array of dashboard_id's
   * @throws \CiviCRM_API3_Exception
   */
  public static function initializeDashlets() {
    $dashlets = [];
    $getDashlets = civicrm_api3("Dashboard", "get", [
      'domain_id' => CRM_Core_Config::domainID(),
      'option.limit' => 0,
    ]);
    $contactID = CRM_Core_Session::getLoggedInContactID();
    $allDashlets = CRM_Utils_Array::index(['name'], $getDashlets['values']);
    $defaultDashlets = [];
    $defaults = ['blog' => 1, 'getting-started' => '0'];
    foreach ($defaults as $name => $column) {
      if (!empty($allDashlets[$name]) && !empty($allDashlets[$name]['id'])) {
        $defaultDashlets[$name] = [
          'dashboard_id' => $allDashlets[$name]['id'],
          'is_active' => 1,
          'column_no' => $column,
          'contact_id' => $contactID,
        ];
      }
    }
    CRM_Utils_Hook::dashboard_defaults($allDashlets, $defaultDashlets);
    if (is_array($defaultDashlets) && !empty($defaultDashlets)) {
      foreach ($defaultDashlets as $id => $defaultDashlet) {
        $dashboard_id = $defaultDashlet['dashboard_id'];
        $dashlet = $getDashlets['values'][$dashboard_id];
        if (!self::checkPermission(CRM_Utils_Array::value('permission', $dashlet), CRM_Utils_Array::value('permission_operator', $dashlet))) {
          continue;
        }
        else {
          $assignDashlets = civicrm_api3("dashboard_contact", "create", $defaultDashlet);
          $values = $assignDashlets['values'][$assignDashlets['id']];
          $dashlets[$assignDashlets['id']] = [
            'dashboard_id' => $values['dashboard_id'],
            'weight' => $values['weight'],
            'column_no' => $values['column_no'],
            'name' => $dashlet['name'],
            'label' => $dashlet['label'],
            'cache_minutes' => $dashlet['cache_minutes'],
            'url' => $dashlet['url'],
            'fullscreen_url' => $dashlet['fullscreen_url'] ?? NULL,
          ];
        }
      }
    }
    return $dashlets;
  }

  /**
   * @param $url
   * @return string
   */
  public static function parseUrl($url) {
    // Check if it is already a fully-formed url
    if ($url && substr($url, 0, 4) != 'http' && $url[0] != '/') {
      $urlParam = explode('?', $url);
      $url = CRM_Utils_System::url($urlParam[0], CRM_Utils_Array::value(1, $urlParam), FALSE, NULL, FALSE);
    }
    return $url;
  }

  /**
   * Check dashlet permission for current user.
   *
   * @param string $permission
   *   Comma separated list.
   * @param string $operator
   *
   * @return bool
   *   true if use has permission else false
   */
  public static function checkPermission($permission, $operator) {
    if ($permission) {
      $permissions = explode(',', $permission);
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
   * Save changes made by user to the Dashlet.
   *
   * @param array $columns
   *
   * @param int $contactID
   *
   * @throws RuntimeException
   */
  public static function saveDashletChanges($columns, $contactID = NULL) {
    if (!$contactID) {
      $contactID = CRM_Core_Session::getLoggedInContactID();
    }

    if (empty($contactID)) {
      throw new RuntimeException("Failed to determine contact ID");
    }

    $dashletIDs = [];
    if (is_array($columns)) {
      foreach ($columns as $colNo => $dashlets) {
        if (!is_int($colNo)) {
          continue;
        }
        $weight = 1;
        foreach ($dashlets as $dashletID => $isMinimized) {
          $dashletID = (int) $dashletID;
          $query = "INSERT INTO civicrm_dashboard_contact
                    (weight, column_no, is_active, dashboard_id, contact_id)
                    VALUES({$weight}, {$colNo}, 1, {$dashletID}, {$contactID})
                    ON DUPLICATE KEY UPDATE weight = {$weight}, column_no = {$colNo}, is_active = 1";
          // fire update query for each column
          CRM_Core_DAO::executeQuery($query);

          $dashletIDs[] = $dashletID;
          $weight++;
        }
      }
    }

    // Find dashlets in this domain.
    $domainDashlets = civicrm_api3('Dashboard', 'get', [
      'return' => array('id'),
      'domain_id' => CRM_Core_Config::domainID(),
      'options' => ['limit' => 0],
    ]);

    // Get the array of IDs.
    $domainDashletIDs = [];
    if ($domainDashlets['is_error'] == 0) {
      $domainDashletIDs = CRM_Utils_Array::collect('id', $domainDashlets['values']);
    }

    // Restrict query to Dashlets in this domain.
    $domainDashletClause = !empty($domainDashletIDs) ? "dashboard_id IN (" . implode(',', $domainDashletIDs) . ")" : '(1)';

    // Target only those Dashlets which are inactive.
    $dashletClause = $dashletIDs ? "dashboard_id NOT IN (" . implode(',', $dashletIDs) . ")" : '(1)';

    // Build params.
    $params = [
      1 => [$contactID, 'Integer'],
    ];

    // Build query.
    $updateQuery = "UPDATE civicrm_dashboard_contact
                    SET is_active = 0
                    WHERE $domainDashletClause
                    AND $dashletClause
                    AND contact_id = %1";

    // Disable inactive widgets.
    CRM_Core_DAO::executeQuery($updateQuery, $params);
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

  /**
   * @param array $params
   *   Each item is a spec for a dashlet on the contact's dashboard.
   * @return bool
   */
  public static function addContactDashletToDashboard(&$params) {
    $valuesString = NULL;
    $columns = [];
    foreach ($params as $dashboardIDs) {
      $contactID = $dashboardIDs['contact_id'] ?? NULL;
      $dashboardID = $dashboardIDs['dashboard_id'] ?? NULL;
      $column = $dashboardIDs['column_no'] ?? 0;
      $columns[$column][$dashboardID] = 0;
    }
    self::saveDashletChanges($columns, $contactID);
    return TRUE;
  }

  /**
   * @deprecated
   * @param int $dashletID
   * @return bool
   */
  public static function deleteDashlet($dashletID) {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Core_DAO_Dashboard::deleteRecord');
    try {
      CRM_Core_DAO_Dashboard::deleteRecord(['id' => $dashletID]);
    }
    catch (CRM_Core_Exception $e) {
      return FALSE;
    }
    return TRUE;
  }

}
