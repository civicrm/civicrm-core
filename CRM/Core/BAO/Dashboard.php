<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
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
    $dashlets = array();
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

      $values = array();
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
   * @param bool $flatFormat
   *   This is true if you want simple associated.
   *   array of all the contact's dashlets whether or not they are enabled.
   *
   * @param int $contactID
   *   Provide the dashlets for the contact id.
   *   passed rather than the current user.
   *
   * @return array
   *   array of dashlets
   */
  public static function getContactDashlets($flatFormat = FALSE, $contactID = NULL) {
    $dashlets = array();

    if (!$contactID) {
      $contactID = CRM_Core_Session::singleton()->get('userID');
    }

    // Get contact dashboard dashlets.
    $hasDashlets = FALSE;
    $dao = new CRM_Contact_DAO_DashboardContact();
    $dao->contact_id = $contactID;
    $dao->orderBy('column_no asc, weight asc');
    $dao->find();
    while ($dao->fetch()) {
      // When a dashlet is removed, it stays in the table with status disabled,
      // so even if a user decides not to have any dashlets show, they will still
      // have records in the table to indicate that we are not newly initializing.
      $hasDashlets = TRUE;
      if (!$flatFormat) {
        if ($dao->is_active) {
          // append weight so that order is preserved.
          $dashlets[$dao->column_no]["{$dao->weight}-{$dao->dashboard_id}"] = $dao->is_minimized;
        }
      }
      else {
        $dashlets[$dao->dashboard_id] = $dao->dashboard_id;
      }
    }

    if ($flatFormat) {
      return $dashlets;
    }

    // If empty, then initialize contact dashboard for this user.
    if (!$hasDashlets) {
      return self::initializeDashlets($flatFormat);
    }
    return $dashlets;
  }

  /**
   * Setup default dashlets for new users.
   *
   * When a user accesses their dashboard for the first time, set up
   * the default dashlets.
   *
   * @param bool $flatFormat
   *
   * @return array
   *    Array of dashboard_id's
   * @throws \CiviCRM_API3_Exception
   */
  public static function initializeDashlets($flatFormat = FALSE) {
    $dashlets = array();
    $getDashlets = civicrm_api3("Dashboard", "get", array(
        'domain_id' => CRM_Core_Config::domainID(),
        'option.limit' => 0,
      ));
    $contactID = CRM_Core_Session::singleton()->get('userID');
    $allDashlets = CRM_Utils_Array::index(array('name'), $getDashlets['values']);
    $defaultDashlets = array();
    $defaults = array('blog' => 1, 'getting-started' => '0');
    foreach ($defaults as $name => $column) {
      if (!empty($allDashlets[$name])) {
        $defaultDashlets[$name] = array(
          'dashboard_id' => $allDashlets[$name]['id'],
          'is_active' => 1,
          'column_no' => $column,
          'contact_id' => $contactID,
        );
      }
    }
    CRM_Utils_Hook::dashboard_defaults($allDashlets, $defaultDashlets);
    if (is_array($defaultDashlets) && !empty($defaultDashlets)) {
      foreach ($defaultDashlets as $id => $defaultDashlet) {
        $dashboard_id = $defaultDashlet['dashboard_id'];
        if (!self::checkPermission($getDashlets['values'][$dashboard_id]['permission'],
          CRM_Utils_Array::value('permission_operator', $getDashlets['values'][$dashboard_id]))
        ) {
          continue;
        }
        else {
          $assignDashlets = civicrm_api3("dashboard_contact", "create", $defaultDashlet);
          if (!$flatFormat) {
            $values = $assignDashlets['values'][$assignDashlets['id']];
            $dashlets[$values['column_no']][$values['weight'] - $values['dashboard_id']] = $values['is_minimized'];
          }
          else {
            $dashlets[$dashboard_id] = $defaultDashlet['dashboard_id'];
          }
        }
      }
    }
    return $dashlets;
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
        if (!$componentName && in_array($key, array(
            'access my cases and activities',
            'access all cases and activities',
          ))
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
   * Get details of each dashlets.
   *
   * @param int $dashletID
   *   Widget ID.
   *
   * @return array
   *   associted array title and content
   */
  public static function getDashletInfo($dashletID) {
    $dashletInfo = array();

    $params = array(1 => array($dashletID, 'Integer'));
    $query = "SELECT name, label, url, fullscreen_url, is_fullscreen FROM civicrm_dashboard WHERE id = %1";
    $dashboadDAO = CRM_Core_DAO::executeQuery($query, $params);
    $dashboadDAO->fetch();

    // build the content
    $dao = new CRM_Contact_DAO_DashboardContact();

    $session = CRM_Core_Session::singleton();
    $dao->contact_id = $session->get('userID');
    $dao->dashboard_id = $dashletID;
    $dao->find(TRUE);

    //reset content based on the cache time set in config
    $createdDate = strtotime($dao->created_date);
    $dateDiff = round(abs(time() - $createdDate) / 60);

    $config = CRM_Core_Config::singleton();
    if ($config->dashboardCacheTimeout <= $dateDiff) {
      $dao->content = NULL;
    }

    // if content is empty and url is set, retrieve it from url
    if (!$dao->content && $dashboadDAO->url) {
      $url = $dashboadDAO->url;

      // CRM-7087
      // -lets use relative url for internal use.
      // -make sure relative url should not be htmlize.
      if (substr($dashboadDAO->url, 0, 4) != 'http') {
        $urlParam = explode('?', $dashboadDAO->url);
        $url = CRM_Utils_System::url($urlParam[0], $urlParam[1], TRUE, NULL, FALSE);
      }

      //get content from url
      $dao->content = CRM_Utils_System::getServerResponse($url);
      $dao->created_date = date("YmdHis");
      $dao->save();
    }

    $dashletInfo = array(
      'title' => $dashboadDAO->label,
      'name' => $dashboadDAO->name,
      'content' => $dao->content,
    );

    if ($dashboadDAO->is_fullscreen) {
      $fullscreenUrl = $dashboadDAO->fullscreen_url;
      if (substr($fullscreenUrl, 0, 4) != 'http') {
        $urlParam = explode('?', $dashboadDAO->fullscreen_url);
        $fullscreenUrl = CRM_Utils_System::url($urlParam[0], $urlParam[1], TRUE, NULL, FALSE);
      }
      $dashletInfo['fullscreenUrl'] = $fullscreenUrl;
    }
    return $dashletInfo;
  }

  /**
   * Save changes made by use to the Dashlet.
   *
   * @param array $columns
   *
   * @param int $contactID
   *
   * @throws RuntimeException
   */
  public static function saveDashletChanges($columns, $contactID = NULL) {
    $session = CRM_Core_Session::singleton();
    if (!$contactID) {
      $contactID = $session->get('userID');
    }

    if (empty($contactID)) {
      throw new RuntimeException("Failed to determine contact ID");
    }

    //we need to get existing dashlets, so we know when to update or insert
    $contactDashlets = self::getContactDashlets(TRUE, $contactID);

    $dashletIDs = array();
    if (is_array($columns)) {
      foreach ($columns as $colNo => $dashlets) {
        if (!is_int($colNo)) {
          continue;
        }
        $weight = 1;
        foreach ($dashlets as $dashletID => $isMinimized) {
          $isMinimized = (int) $isMinimized;
          if (in_array($dashletID, $contactDashlets)) {
            $query = " UPDATE civicrm_dashboard_contact
                                        SET weight = {$weight}, is_minimized = {$isMinimized}, column_no = {$colNo}, is_active = 1
                                      WHERE dashboard_id = {$dashletID} AND contact_id = {$contactID} ";
          }
          else {
            $query = " INSERT INTO civicrm_dashboard_contact
                                        ( weight, is_minimized, column_no, is_active, dashboard_id, contact_id )
                                     VALUES( {$weight},  {$isMinimized},  {$colNo}, 1, {$dashletID}, {$contactID} )";
          }
          // fire update query for each column
          $dao = CRM_Core_DAO::executeQuery($query);

          $dashletIDs[] = $dashletID;
          $weight++;
        }
      }
    }

    if (!empty($dashletIDs)) {
      // we need to disable widget that removed
      $updateQuery = " UPDATE civicrm_dashboard_contact
                               SET is_active = 0
                               WHERE dashboard_id NOT IN  ( " . implode(',', $dashletIDs) . ") AND contact_id = {$contactID}";
    }
    else {
      // this means all widgets are disabled
      $updateQuery = " UPDATE civicrm_dashboard_contact
                               SET is_active = 0
                               WHERE contact_id = {$contactID}";
    }

    CRM_Core_DAO::executeQuery($updateQuery);
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
    $dashboardID = CRM_Utils_Array::value('id', $params);

    if (!empty($params['instanceURL'])) {
      $query = "SELECT id
                        FROM `civicrm_dashboard`
                        WHERE url LIKE '" . CRM_Utils_Array::value('instanceURL', $params) . "&%'";
      $dashboardID = CRM_Core_DAO::singleValueQuery($query);
    }

    $dashlet = new CRM_Core_DAO_Dashboard();

    if (!$dashboardID) {
      // check url is same as exiting entries, if yes just update existing
      if (!empty($params['name'])) {
        $dashlet->name = CRM_Utils_Array::value('name', $params);
        $dashlet->find(TRUE);
      }
      else {
        $dashlet->url = CRM_Utils_Array::value('url', $params);
        $dashlet->find(TRUE);
      }
      if (empty($params['domain_id'])) {
        $dashlet->domain_id = CRM_Core_Config::domainID();
      }
    }
    else {
      $dashlet->id = $dashboardID;
    }

    if (is_array(CRM_Utils_Array::value('permission', $params))) {
      $params['permission'] = implode(',', $params['permission']);
    }
    $dashlet->copyValues($params);
    $dashlet->save();

    // now we need to make dashlet entries for each contact
    self::addContactDashlet($dashlet);

    return $dashlet;
  }

  /**
   * @param $url
   *
   * @return string
   */
  public static function getDashletName($url) {
    $urlElements = explode('/', $url);
    if ($urlElements[1] == 'dashlet') {
      return $urlElements[2];
    }
    elseif ($urlElements[1] == 'report') {
      return 'report/' . $urlElements[3];
    }
    return $url;
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
    $contactIDs = array();
    if ($admin) {
      $query = "SELECT distinct( contact_id )
                        FROM civicrm_dashboard_contact
                        WHERE contact_id NOT IN (
                            SELECT distinct( contact_id )
                            FROM civicrm_dashboard_contact WHERE dashboard_id = {$dashlet->id}
                            )";

      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {
        $contactIDs[] = $dao->contact_id;
      }
    }
    else {
      //Get the id of Logged in User
      $session = CRM_Core_Session::singleton();
      $contactID = $session->get('userID');
      if (!empty($contactID)) {
        $contactIDs[] = $session->get('userID');
      }
    }

    if (!empty($contactIDs)) {
      foreach ($contactIDs as $contactID) {
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
    $columns = array();
    foreach ($params as $dashboardIDs) {
      $contactID = CRM_Utils_Array::value('contact_id', $dashboardIDs);
      $dashboardID = CRM_Utils_Array::value('dashboard_id', $dashboardIDs);
      $column = CRM_Utils_Array::value('column_no', $dashboardIDs, 0);
      $columns[$column][$dashboardID] = 0;
    }
    self::saveDashletChanges($columns, $contactID);
    return TRUE;
  }

  /**
   * Reset dashlet cache.
   *
   * @param int $contactID
   *   Reset cache only for specific contact.
   */
  public static function resetDashletCache($contactID = NULL) {
    $whereClause = NULL;
    $params = array();
    if ($contactID) {
      $whereClause = "WHERE contact_id = %1";
      $params[1] = array($contactID, 'Integer');
    }
    $query = "UPDATE civicrm_dashboard_contact SET content = NULL $whereClause";
    $dao = CRM_Core_DAO::executeQuery($query, $params);
  }

  /**
   * Delete Dashlet.
   *
   * @param int $dashletID
   *
   * @return bool
   */
  public static function deleteDashlet($dashletID) {
    $dashlet = new CRM_Core_DAO_Dashboard();
    $dashlet->id = $dashletID;
    $dashlet->delete();
    return TRUE;
  }

}
