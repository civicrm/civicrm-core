<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Class contains Contact dashboard related functions
 */
class CRM_Core_BAO_Dashboard extends CRM_Core_DAO_Dashboard {

  /**
   * Get the list of dashlets enabled by admin
   *
   * @param boolean $all all or only active
   *
   * @return array $widgets  array of dashlets
   * @access public
   * @static
   */
  static function getDashlets($all = TRUE) {
    $dashlets = array();
    $dao = new CRM_Core_DAO_Dashboard();

    if (!$all) {
      $dao->is_active = 1;
    }

    $dao->domain_id = CRM_Core_Config::domainID();

    $dao->find();
    while ($dao->fetch()) {
      if (!self::checkPermission($dao->permission, $dao->permission_operator)) {
        continue;
      }

      $values = array();
      CRM_Core_DAO::storeValues($dao, $values);
      $dashlets[$dao->id] = $values;
    }

    return $dashlets;
  }

  /**
   * Function to get the list of dashlets for a contact
   *
   * Initializes the dashboard with defaults if this is the user's first visit to their dashboard
   *
   * @param boolean $flatFormat this is true if you want simple associated array of contact dashlets
   *
   * @return array $dashlets array of dashlets
   * @access public
   * @static
   */
  static function getContactDashlets($flatFormat = FALSE) {
    $dashlets = array();

    $contactID = CRM_Core_Session::singleton()->get('userID');

    // get contact dashboard dashlets
    $hasDashlets = FALSE;
    $dao = new CRM_Contact_DAO_DashboardContact();
    $dao->contact_id = $contactID;
    $dao->orderBy('column_no asc, weight asc');
    $dao->find();
    while ($dao->fetch()) {
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

    // If empty then initialize contact dashboard for this user
    if (!$hasDashlets) {
      $defaultDashlets = self::getDashlets();
      if ($defaultDashlets) {
        // Add dashlet entries for logged in contact
        // TODO: need to optimize this sql
        $items = '';
        foreach ($defaultDashlets as $key => $values) {
          // Set civicrm blog as default enabled
          $default = $values['url'] == 'civicrm/dashlet/blog&reset=1&snippet=5' ? 1 : 0;
          $items .= ($items ? ', ' : '') . "($key, $contactID, $default, $default)";
        }
        $query = "INSERT INTO civicrm_dashboard_contact (dashboard_id, contact_id, column_no, is_active) VALUES $items";
        CRM_Core_DAO::executeQuery($query);
      }
    }

    return $dashlets;
  }

  /**
   * Function to check dashlet permission for current user
   *
   * @param string permission string
   *
   * @return boolean true if use has permission else false
   */
  static function checkPermission($permission, $operator) {
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
          'access my cases and activities', 'access all cases and activities'))) {
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
   * Function to get details of each dashlets
   *
   * @param int $dashletID widget ID
   *
   * @return array associted array title and content
   * @access public
   * @static
   */
  static function getDashletInfo($dashletID) {
    $dashletInfo = array();

    $params = array(1 => array($dashletID, 'Integer'));
    $query = "SELECT label, url, fullscreen_url, is_fullscreen FROM civicrm_dashboard WHERE id = %1";
    $dashboadDAO = CRM_Core_DAO::executeQuery($query, $params);
    $dashboadDAO->fetch();

    // build the content
    $dao = new CRM_Contact_DAO_DashboardContact();

    $session           = CRM_Core_Session::singleton();
    $dao->contact_id   = $session->get('userID');
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
        $urlParam = CRM_Utils_System::explode('&', $dashboadDAO->url, 2);
        $url = CRM_Utils_System::url($urlParam[0], $urlParam[1], TRUE, NULL, FALSE);
      }

      //get content from url
      $dao->content = CRM_Utils_System::getServerResponse($url);
      $dao->created_date = date("YmdHis");
      $dao->save();
    }

    $dashletInfo = array(
      'title' => $dashboadDAO->label,
      'content' => $dao->content,
    );

    if ($dashboadDAO->is_fullscreen) {
      $fullscreenUrl = $dashboadDAO->fullscreen_url;
      if (substr($fullscreenUrl, 0, 4) != 'http') {
        $urlParam = CRM_Utils_System::explode('&', $dashboadDAO->fullscreen_url, 2);
        $fullscreenUrl = CRM_Utils_System::url($urlParam[0], $urlParam[1], TRUE, NULL, FALSE);
      }
      $dashletInfo['fullscreenUrl'] = $fullscreenUrl;
    }
    return $dashletInfo;
  }

  /**
   * Function to save changes made by use to the Dashlet
   *
   * @param array $columns associated array
   *
   * @return void
   * @access public
   * @static
   */
  static function saveDashletChanges($columns) {
    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');

    //we need to get existing dashletes, so we know when to update or insert
    $contactDashlets = self::getContactDashlets(TRUE);

    $dashletIDs = array();
    if (is_array($columns)) {
      foreach ($columns as $colNo => $dashlets) {
        if (!is_integer($colNo)) {
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
   * Function to add dashlets
   *
   * @param array $params associated array
   *
   * @return object $dashlet returns dashlet object
   * @access public
   * @static
   */
  static function addDashlet(&$params) {

    // special case to handle duplicate entires for report instances
    $dashboardID = NULL;
    if (CRM_Utils_Array::value('instanceURL', $params)) {
      $query = "SELECT id
                        FROM `civicrm_dashboard`
                        WHERE url LIKE '" . CRM_Utils_Array::value('instanceURL', $params) . "&%'";
      $dashboardID = CRM_Core_DAO::singleValueQuery($query);
    }

    $dashlet = new CRM_Core_DAO_Dashboard();

    if (!$dashboardID) {
      // check url is same as exiting entries, if yes just update existing
      $dashlet->url = CRM_Utils_Array::value('url', $params);
      $dashlet->find(TRUE);
    }
    else {
      $dashlet->id = $dashboardID;
    }

    if (is_array(CRM_Utils_Array::value('permission', $params))) {
      $params['permission'] = implode(',', $params['permission']);
    }
    $dashlet->copyValues($params);

    $dashlet->domain_id = CRM_Core_Config::domainID();

    $dashlet->save();

    // now we need to make dashlet entries for each contact
    self::addContactDashlet($dashlet);

    return $dashlet;
  }

  /**
   * Update contact dashboard with new dashlet
   *
   * @param object: $dashlet
   *
   * @return void
   * @static
   */
  static function addContactDashlet($dashlet) {
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
      $contactIDs[] = $session->get('userID');
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
   * Function to reset dashlet cache
   *
   * @param int $contactID reset cache only for specific contact
   *
   * @return void
   * @static
   */
  static function resetDashletCache($contactID = null) {
    $whereClause = null;
    $params = array();
    if ($contactID) {
      $whereClause = "WHERE contact_id = %1";
      $params[1] = array($contactID, 'Integer');
    }
    $query = "UPDATE civicrm_dashboard_contact SET content = NULL $whereClause";
    $dao = CRM_Core_DAO::executeQuery($query, $params);
  }

  /**
   * Delete Dashlet
   *
   * @return void
   * @static
   */
  static function deleteDashlet($dashletID) {
    $dashlet = new CRM_Core_DAO_Dashboard();
    $dashlet->id = $dashletID;
    $dashlet->delete();
  }
}

