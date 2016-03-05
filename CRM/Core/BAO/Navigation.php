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
class CRM_Core_BAO_Navigation extends CRM_Core_DAO_Navigation {

  // Number of characters in the menu js cache key
  const CACHE_KEY_STRLEN = 8;

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return CRM_Core_DAO_Navigation|NULL
   *   DAO object on success, NULL otherwise
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_Navigation', $id, 'is_active', $is_active);
  }

  /**
   * Get existing / build navigation for CiviCRM Admin Menu.
   *
   * @return array
   *   associated array
   */
  public static function getMenus() {
    $menus = array();

    $menu = new CRM_Core_DAO_Menu();
    $menu->domain_id = CRM_Core_Config::domainID();
    $menu->find();

    while ($menu->fetch()) {
      if ($menu->title) {
        $menus[$menu->path] = $menu->title;
      }
    }
    return $menus;
  }

  /**
   * Add/update navigation record.
   *
   * @param array $params Submitted values
   *
   * @return CRM_Core_DAO_Navigation
   *   navigation object
   */
  public static function add(&$params) {
    $navigation = new CRM_Core_DAO_Navigation();
    if (empty($params['id'])) {
      $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
      $params['has_separator'] = CRM_Utils_Array::value('has_separator', $params, FALSE);
    }

    if (!isset($params['id']) ||
      (CRM_Utils_Array::value('parent_id', $params) != CRM_Utils_Array::value('current_parent_id', $params))
    ) {
      /* re/calculate the weight, if the Parent ID changed OR create new menu */

      if ($navName = CRM_Utils_Array::value('name', $params)) {
        $params['name'] = $navName;
      }
      elseif ($navLabel = CRM_Utils_Array::value('label', $params)) {
        $params['name'] = $navLabel;
      }

      $params['weight'] = self::calculateWeight(CRM_Utils_Array::value('parent_id', $params));
    }

    if (array_key_exists('permission', $params) && is_array($params['permission'])) {
      $params['permission'] = implode(',', $params['permission']);
    }

    $navigation->copyValues($params);

    $navigation->domain_id = CRM_Core_Config::domainID();

    $navigation->save();
    return $navigation;
  }

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Core_BAO_Navigation|null
   *   object on success, NULL otherwise
   */
  public static function retrieve(&$params, &$defaults) {
    $navigation = new CRM_Core_DAO_Navigation();
    $navigation->copyValues($params);

    $navigation->domain_id = CRM_Core_Config::domainID();

    if ($navigation->find(TRUE)) {
      CRM_Core_DAO::storeValues($navigation, $defaults);
      return $navigation;
    }
    return NULL;
  }

  /**
   * Calculate navigation weight.
   *
   * @param int $parentID
   *   Parent_id of a menu.
   * @param int $menuID
   *   Menu id.
   *
   * @return int
   *   $weight string
   */
  public static function calculateWeight($parentID = NULL, $menuID = NULL) {
    $domainID = CRM_Core_Config::domainID();

    $weight = 1;
    // we reset weight for each parent, i.e we start from 1 to n
    // calculate max weight for top level menus, if parent id is absent
    if (!$parentID) {
      $query = "SELECT max(weight) as weight FROM civicrm_navigation WHERE parent_id IS NULL AND domain_id = $domainID";
    }
    else {
      // if parent is passed, we need to get max weight for that particular parent
      $query = "SELECT max(weight) as weight FROM civicrm_navigation WHERE parent_id = {$parentID} AND domain_id = $domainID";
    }

    $dao = CRM_Core_DAO::executeQuery($query);
    $dao->fetch();
    return $weight = $weight + $dao->weight;
  }

  /**
   * Get formatted menu list.
   *
   * @return array
   *   returns associated array
   */
  public static function getNavigationList() {
    $cacheKeyString = "navigationList";
    $whereClause = '';

    $config = CRM_Core_Config::singleton();

    // check if we can retrieve from database cache
    $navigations = CRM_Core_BAO_Cache::getItem('navigation', $cacheKeyString);

    if (!$navigations) {
      $domainID = CRM_Core_Config::domainID();
      $query = "
SELECT id, label, parent_id, weight, is_active, name
FROM civicrm_navigation WHERE domain_id = $domainID {$whereClause} ORDER BY parent_id, weight ASC";
      $result = CRM_Core_DAO::executeQuery($query);

      $pidGroups = array();
      while ($result->fetch()) {
        $pidGroups[$result->parent_id][$result->label] = $result->id;
      }

      foreach ($pidGroups[''] as $label => $val) {
        $pidGroups[''][$label] = self::_getNavigationValue($val, $pidGroups);
      }

      $navigations = array();
      self::_getNavigationLabel($pidGroups[''], $navigations);

      CRM_Core_BAO_Cache::setItem($navigations, 'navigation', $cacheKeyString);
    }
    return $navigations;
  }

  /**
   * Helper function for getNavigationList().
   *
   * @param array $list
   *   Menu info.
   * @param array $navigations
   *   Navigation menus.
   * @param string $separator
   *   Menu separator.
   */
  public static function _getNavigationLabel($list, &$navigations, $separator = '') {
    $i18n = CRM_Core_I18n::singleton();
    foreach ($list as $label => $val) {
      if ($label == 'navigation_id') {
        continue;
      }
      $translatedLabel = $i18n->crm_translate($label, array('context' => 'menu'));
      $navigations[is_array($val) ? $val['navigation_id'] : $val] = "{$separator}{$translatedLabel}";
      if (is_array($val)) {
        self::_getNavigationLabel($val, $navigations, $separator . '&nbsp;&nbsp;&nbsp;&nbsp;');
      }
    }
  }

  /**
   * Helper function for getNavigationList().
   *
   * @param string $val
   *   Menu name.
   * @param array $pidGroups
   *   Parent menus.
   *
   * @return array
   */
  public static function _getNavigationValue($val, &$pidGroups) {
    if (array_key_exists($val, $pidGroups)) {
      $list = array('navigation_id' => $val);
      foreach ($pidGroups[$val] as $label => $id) {
        $list[$label] = self::_getNavigationValue($id, $pidGroups);
      }
      unset($pidGroups[$val]);
      return $list;
    }
    else {
      return $val;
    }
  }

  /**
   * Build navigation tree.
   *
   * @param array $navigationTree
   *   Nested array of menus.
   * @param int $parentID
   *   Parent id.
   * @param bool $navigationMenu
   *   True when called for building top navigation menu.
   *
   * @return array
   *   nested array of menus
   */
  public static function buildNavigationTree(&$navigationTree, $parentID, $navigationMenu = TRUE) {
    $whereClause = " parent_id IS NULL";

    if ($parentID) {
      $whereClause = " parent_id = {$parentID}";
    }

    $domainID = CRM_Core_Config::domainID();

    // get the list of menus
    $query = "
SELECT id, label, url, permission, permission_operator, has_separator, parent_id, is_active, name
FROM civicrm_navigation
WHERE {$whereClause}
AND domain_id = $domainID
ORDER BY parent_id, weight";

    $navigation = CRM_Core_DAO::executeQuery($query);
    $config = CRM_Core_Config::singleton();
    while ($navigation->fetch()) {
      $label = $navigation->label;
      if (!$navigationMenu) {
        $label = addcslashes($label, '"');
      }

      // for each menu get their children
      $navigationTree[$navigation->id] = array(
        'attributes' => array(
          'label' => $label,
          'name' => $navigation->name,
          'url' => $navigation->url,
          'permission' => $navigation->permission,
          'operator' => $navigation->permission_operator,
          'separator' => $navigation->has_separator,
          'parentID' => $navigation->parent_id,
          'navID' => $navigation->id,
          'active' => $navigation->is_active,
        ),
      );
      self::buildNavigationTree($navigationTree[$navigation->id]['child'], $navigation->id, $navigationMenu);
    }

    return $navigationTree;
  }

  /**
   * Build menu.
   *
   * @param bool $json
   *   By default output is html.
   * @param bool $navigationMenu
   *   True when called for building top navigation menu.
   *
   * @return string
   *   html or json string
   */
  public static function buildNavigation($json = FALSE, $navigationMenu = TRUE) {
    $navigations = array();
    self::buildNavigationTree($navigations, $parent = NULL, $navigationMenu);
    $navigationString = NULL;

    // run the Navigation  through a hook so users can modify it
    CRM_Utils_Hook::navigationMenu($navigations);
    self::fixNavigationMenu($navigations);

    $i18n = CRM_Core_I18n::singleton();

    //skip children menu item if user don't have access to parent menu item
    $skipMenuItems = array();
    foreach ($navigations as $key => $value) {
      if ($json) {
        if ($navigationString) {
          $navigationString .= '},';
        }
        $data = $value['attributes']['label'];
        $class = '';
        if (!$value['attributes']['active']) {
          $class = ', "attr": { "class" : "disabled"} ';
        }
        $l10nName = $i18n->crm_translate($data, array('context' => 'menu'));
        $navigationString .= ' { "attr": { "id" : "node_' . $key . '"}, "data": { "title":"' . $l10nName . '"' . $class . '}';
      }
      else {
        // Home is a special case
        if ($value['attributes']['name'] != 'Home') {
          $name = self::getMenuName($value, $skipMenuItems);
          if ($name) {
            //separator before
            if (isset($value['attributes']['separator']) && $value['attributes']['separator'] == 2) {
              $navigationString .= '<li class="menu-separator"></li>';
            }
            $removeCharacters = array('/', '!', '&', '*', ' ', '(', ')', '.');
            $navigationString .= '<li class="menumain crm-' . str_replace($removeCharacters, '_', $value['attributes']['label']) . '">' . $name;
          }
        }
      }

      self::recurseNavigation($value, $navigationString, $json, $skipMenuItems);
    }

    if ($json) {
      $navigationString = '[' . $navigationString . '}]';
    }
    else {
      // clean up - Need to remove empty <ul>'s, this happens when user don't have
      // permission to access parent
      $navigationString = str_replace('<ul></ul></li>', '', $navigationString);
    }

    return $navigationString;
  }

  /**
   * Recursively check child menus.
   *
   * @param array $value
   * @param string $navigationString
   * @param bool $json
   * @param bool $skipMenuItems
   *
   * @return string
   */
  public static function recurseNavigation(&$value, &$navigationString, $json, $skipMenuItems) {
    if ($json) {
      if (!empty($value['child'])) {
        $navigationString .= ', "children": [ ';
      }
      else {
        return $navigationString;
      }

      if (!empty($value['child'])) {
        $appendComma = TRUE;
        $count = 1;
        foreach ($value['child'] as $k => $val) {
          if ($count == count($value['child'])) {
            $appendComma = FALSE;
          }
          $data = $val['attributes']['label'];
          $class = '';
          if (!$val['attributes']['active']) {
            $class = ', "attr": { "class" : "disabled"} ';
          }
          $navigationString .= ' { "attr": { "id" : "node_' . $k . '"}, "data": { "title":"' . $data . '"' . $class . '}';
          self::recurseNavigation($val, $navigationString, $json, $skipMenuItems);
          $navigationString .= $appendComma ? ' },' : ' }';
          $count++;
        }
      }

      if (!empty($value['child'])) {
        $navigationString .= ' ]';
      }
    }
    else {
      if (!empty($value['child'])) {
        $navigationString .= '<ul>';
      }
      else {
        $navigationString .= '</li>';
        //locate separator after
        if (isset($value['attributes']['separator']) && $value['attributes']['separator'] == 1) {
          $navigationString .= '<li class="menu-separator"></li>';
        }
      }

      if (!empty($value['child'])) {
        foreach ($value['child'] as $val) {
          $name = self::getMenuName($val, $skipMenuItems);
          if ($name) {
            //locate separator before
            if (isset($val['attributes']['separator']) && $val['attributes']['separator'] == 2) {
              $navigationString .= '<li class="menu-separator"></li>';
            }
            $removeCharacters = array('/', '!', '&', '*', ' ', '(', ')', '.');
            $navigationString .= '<li class="crm-' . str_replace($removeCharacters, '_', $val['attributes']['label']) . '">' . $name;
            self::recurseNavigation($val, $navigationString, $json, $skipMenuItems);
          }
        }
      }
      if (!empty($value['child'])) {
        $navigationString .= '</ul></li>';
        if (isset($value['attributes']['separator']) && $value['attributes']['separator'] == 1) {
          $navigationString .= '<li class="menu-separator"></li>';
        }
      }
    }
    return $navigationString;
  }

  /**
   * Given a navigation menu, generate navIDs for any items which are
   * missing them.
   *
   * @param array $nodes
   *   Each key is a numeral; each value is a node in
   *   the menu tree (with keys "child" and "attributes").
   */
  public static function fixNavigationMenu(&$nodes) {
    $maxNavID = 1;
    array_walk_recursive($nodes, function($item, $key) use (&$maxNavID) {
      if ($key === 'navID') {
        $maxNavID = max($maxNavID, $item);
      }
    });
    self::_fixNavigationMenu($nodes, $maxNavID, NULL);
  }

  /**
   * @param array $nodes
   *   Each key is a numeral; each value is a node in
   *   the menu tree (with keys "child" and "attributes").
   * @param int $maxNavID
   * @param int $parentID
   */
  private static function _fixNavigationMenu(&$nodes, &$maxNavID, $parentID) {
    $origKeys = array_keys($nodes);
    foreach ($origKeys as $origKey) {
      if (!isset($nodes[$origKey]['attributes']['parentID']) && $parentID !== NULL) {
        $nodes[$origKey]['attributes']['parentID'] = $parentID;
      }
      // If no navID, then assign navID and fix key.
      if (!isset($nodes[$origKey]['attributes']['navID'])) {
        $newKey = ++$maxNavID;
        $nodes[$origKey]['attributes']['navID'] = $newKey;
        $nodes[$newKey] = $nodes[$origKey];
        unset($nodes[$origKey]);
        $origKey = $newKey;
      }
      if (isset($nodes[$origKey]['child']) && is_array($nodes[$origKey]['child'])) {
        self::_fixNavigationMenu($nodes[$origKey]['child'], $maxNavID, $nodes[$origKey]['attributes']['navID']);
      }
    }
  }

  /**
   * Get Menu name.
   *
   * @param $value
   * @param array $skipMenuItems
   *
   * @return bool|string
   */
  public static function getMenuName(&$value, &$skipMenuItems) {
    // we need to localise the menu labels (CRM-5456) and don’t
    // want to use ts() as it would throw the ts-extractor off
    $i18n = CRM_Core_I18n::singleton();

    $name = $i18n->crm_translate($value['attributes']['label'], array('context' => 'menu'));
    $url = CRM_Utils_Array::value('url', $value['attributes']);
    $permission = CRM_Utils_Array::value('permission', $value['attributes']);
    $operator = CRM_Utils_Array::value('operator', $value['attributes']);
    $parentID = CRM_Utils_Array::value('parentID', $value['attributes']);
    $navID = CRM_Utils_Array::value('navID', $value['attributes']);
    $active = CRM_Utils_Array::value('active', $value['attributes']);
    $menuName = CRM_Utils_Array::value('name', $value['attributes']);
    $target = CRM_Utils_Array::value('target', $value['attributes']);

    if (in_array($parentID, $skipMenuItems) || !$active) {
      $skipMenuItems[] = $navID;
      return FALSE;
    }

    //we need to check core view/edit or supported acls.
    if (in_array($menuName, array(
      'Search...',
      'Contacts',
    ))) {
      if (!CRM_Core_Permission::giveMeAllACLs()) {
        $skipMenuItems[] = $navID;
        return FALSE;
      }
    }

    $config = CRM_Core_Config::singleton();

    $makeLink = FALSE;
    if (isset($url) && $url) {
      if (substr($url, 0, 4) !== 'http') {
        //CRM-7656 --make sure to separate out url path from url params,
        //as we'r going to validate url path across cross-site scripting.
        $urlParam = explode('?', $url);
        if (empty($urlParam[1])) {
          $urlParam[1] = NULL;
        }
        $url = CRM_Utils_System::url($urlParam[0], $urlParam[1], FALSE, NULL, TRUE);
      }
      elseif (strpos($url, '&amp;') === FALSE) {
        $url = htmlspecialchars($url);
      }
      $makeLink = TRUE;
    }

    static $allComponents;
    if (!$allComponents) {
      $allComponents = CRM_Core_Component::getNames();
    }

    if (isset($permission) && $permission) {
      $permissions = explode(',', $permission);

      $hasPermission = FALSE;
      foreach ($permissions as $key) {
        $key = trim($key);
        $showItem = TRUE;

        //get the component name from permission.
        $componentName = CRM_Core_Permission::getComponentName($key);

        if ($componentName) {
          if (!in_array($componentName, $config->enableComponents) ||
            !CRM_Core_Permission::check($key)
          ) {
            $showItem = FALSE;
            if ($operator == 'AND') {
              $skipMenuItems[] = $navID;
              return $showItem;
            }
          }
          else {
            $hasPermission = TRUE;
          }
        }
        elseif (!CRM_Core_Permission::check($key)) {
          $showItem = FALSE;
          if ($operator == 'AND') {
            $skipMenuItems[] = $navID;
            return $showItem;
          }
        }
        else {
          $hasPermission = TRUE;
        }
      }

      if (!$showItem && !$hasPermission) {
        $skipMenuItems[] = $navID;
        return FALSE;
      }
    }

    if ($makeLink) {
      $url = CRM_Utils_System::evalUrl($url);
      if ($target) {
        $name = "<a href=\"{$url}\" target=\"{$target}\">{$name}</a>";
      }
      else {
        $name = "<a href=\"{$url}\">{$name}</a>";
      }
    }

    return $name;
  }

  /**
   * Create navigation for CiviCRM Admin Menu.
   *
   * @param int $contactID
   *   Contact id.
   *
   * @return string
   *   returns navigation html
   */
  public static function createNavigation($contactID) {
    $config = CRM_Core_Config::singleton();

    $navigation = self::buildNavigation();

    if ($navigation) {

      //add additional navigation items
      $logoutURL = CRM_Utils_System::url('civicrm/logout', 'reset=1');

      // get home menu from db
      $homeParams = array('name' => 'Home');
      $homeNav = array();
      $homeIcon = '<span class="crm-logo-sm" ></span>';
      self::retrieve($homeParams, $homeNav);
      if ($homeNav) {
        list($path, $q) = explode('?', $homeNav['url']);
        $homeURL = CRM_Utils_System::url($path, $q);
        $homeLabel = $homeNav['label'];
        // CRM-6804 (we need to special-case this as we don’t ts()-tag variables)
        if ($homeLabel == 'Home') {
          $homeLabel = ts('CiviCRM Home');
        }
      }
      else {
        $homeURL = CRM_Utils_System::url('civicrm/dashboard', 'reset=1');
        $homeLabel = ts('CiviCRM Home');
      }
      // Link to hide the menubar
      $hideLabel = ts('Hide Menu');

      $prepandString = "
        <li class='menumain crm-link-home'>$homeIcon
          <ul id='civicrm-home'>
            <li><a href='$homeURL'>$homeLabel</a></li>
            <li><a href='#' class='crm-hidemenu'>$hideLabel</a></li>
            <li><a href='$logoutURL' class='crm-logout-link'>" . ts('Log out') . "</a></li>
          </ul>";
      // <li> tag doesn't need to be closed
    }
    return $prepandString . $navigation;
  }

  /**
   * Reset navigation for all contacts or a specified contact.
   *
   * @param int $contactID
   *   Reset only entries belonging to that contact ID.
   *
   * @return string
   */
  public static function resetNavigation($contactID = NULL) {
    $newKey = CRM_Utils_String::createRandom(self::CACHE_KEY_STRLEN, CRM_Utils_String::ALPHANUMERIC);
    if (!$contactID) {
      $query = "UPDATE civicrm_setting SET value = '$newKey' WHERE name='navigation' AND contact_id IS NOT NULL";
      CRM_Core_DAO::executeQuery($query);
      CRM_Core_BAO_Cache::deleteGroup('navigation');
    }
    else {
      // before inserting check if contact id exists in db
      // this is to handle weird case when contact id is in session but not in db
      $contact = new CRM_Contact_DAO_Contact();
      $contact->id = $contactID;
      if ($contact->find(TRUE)) {
        CRM_Core_BAO_Setting::setItem(
          $newKey,
          CRM_Core_BAO_Setting::PERSONAL_PREFERENCES_NAME,
          'navigation',
          NULL,
          $contactID,
          $contactID
        );
      }
    }
    // also reset the dashlet cache in case permissions have changed etc
    // FIXME: decouple this
    CRM_Core_BAO_Dashboard::resetDashletCache($contactID);

    return $newKey;
  }

  /**
   * Process navigation.
   *
   * @param array $params
   *   Associated array, $_GET.
   */
  public static function processNavigation(&$params) {
    $nodeID = (int) str_replace("node_", "", $params['id']);
    $referenceID = (int) str_replace("node_", "", $params['ref_id']);
    $position = $params['ps'];
    $type = $params['type'];
    $label = CRM_Utils_Array::value('data', $params);

    switch ($type) {
      case "move":
        self::processMove($nodeID, $referenceID, $position);
        break;

      case "rename":
        self::processRename($nodeID, $label);
        break;

      case "delete":
        self::processDelete($nodeID);
        break;
    }

    //reset navigation menus
    self::resetNavigation();
    CRM_Utils_System::civiExit();
  }

  /**
   * Process move action.
   *
   * @param $nodeID
   *   Node that is being moved.
   * @param $referenceID
   *   Parent id where node is moved. 0 mean no parent.
   * @param $position
   *   New position of the nod, it starts with 0 - n.
   */
  public static function processMove($nodeID, $referenceID, $position) {
    // based on the new position we need to get the weight of the node after moved node
    // 1. update the weight of $position + 1 nodes to weight + 1
    // 2. weight of the ( $position -1 ) node - 1 is the new weight of the node being moved

    // check if there is parent id, which means node is moved inside existing parent container, so use parent id
    // to find the correct position else use NULL to get the weights of parent ( $position - 1 )
    // accordingly set the new parent_id
    if ($referenceID) {
      $newParentID = $referenceID;
      $parentClause = "parent_id = {$referenceID} ";
    }
    else {
      $newParentID = 'NULL';
      $parentClause = 'parent_id IS NULL';
    }

    $incrementOtherNodes = TRUE;
    $sql = "SELECT weight from civicrm_navigation WHERE {$parentClause} ORDER BY weight LIMIT %1, 1";
    $params = array(1 => array($position, 'Positive'));
    $newWeight = CRM_Core_DAO::singleValueQuery($sql, $params);

    // this means node is moved to last position, so you need to get the weight of last element + 1
    if (!$newWeight) {
      // If this is not the first item being added to a parent
      if ($position) {
        $lastPosition = $position - 1;
        $sql = "SELECT weight from civicrm_navigation WHERE {$parentClause} ORDER BY weight LIMIT %1, 1";
        $params = array(1 => array($lastPosition, 'Positive'));
        $newWeight = CRM_Core_DAO::singleValueQuery($sql, $params);

        // since last node increment + 1
        $newWeight = $newWeight + 1;
      }
      else {
        $newWeight = '0';
      }

      // since this is a last node we don't need to increment other nodes
      $incrementOtherNodes = FALSE;
    }

    $transaction = new CRM_Core_Transaction();

    // now update the existing nodes to weight + 1, if required.
    if ($incrementOtherNodes) {
      $query = "UPDATE civicrm_navigation SET weight = weight + 1
                  WHERE {$parentClause} AND weight >= {$newWeight}";

      CRM_Core_DAO::executeQuery($query);
    }

    // finally set the weight of current node
    $query = "UPDATE civicrm_navigation SET weight = {$newWeight}, parent_id = {$newParentID} WHERE id = {$nodeID}";
    CRM_Core_DAO::executeQuery($query);

    $transaction->commit();
  }

  /**
   * Function to process rename action for tree.
   *
   * @param int $nodeID
   * @param $label
   */
  public static function processRename($nodeID, $label) {
    CRM_Core_DAO::setFieldValue('CRM_Core_DAO_Navigation', $nodeID, 'label', $label);
  }

  /**
   * Process delete action for tree.
   *
   * @param int $nodeID
   */
  public static function processDelete($nodeID) {
    $query = "DELETE FROM civicrm_navigation WHERE id = {$nodeID}";
    CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Get the info on navigation item.
   *
   * @param int $navigationID
   *   Navigation id.
   *
   * @return array
   *   associated array
   */
  public static function getNavigationInfo($navigationID) {
    $query = "SELECT parent_id, weight FROM civicrm_navigation WHERE id = %1";
    $params = array($navigationID, 'Integer');
    $dao = CRM_Core_DAO::executeQuery($query, array(1 => $params));
    $dao->fetch();
    return array(
      'parent_id' => $dao->parent_id,
      'weight' => $dao->weight,
    );
  }

  /**
   * Update menu.
   *
   * @param array $params
   * @param array $newParams
   *   New value of params.
   */
  public static function processUpdate($params, $newParams) {
    $dao = new CRM_Core_DAO_Navigation();
    $dao->copyValues($params);
    if ($dao->find(TRUE)) {
      $dao->copyValues($newParams);
      $dao->save();
    }
  }

  /**
   * Rebuild reports menu.
   *
   * All Contact reports will become sub-items of 'Contact Reports' and so on.
   *
   * @param int $domain_id
   */
  public static function rebuildReportsNavigation($domain_id) {
    $component_to_nav_name = array(
      'CiviContact' => 'Contact Reports',
      'CiviContribute' => 'Contribution Reports',
      'CiviMember' => 'Membership Reports',
      'CiviEvent' => 'Event Reports',
      'CiviPledge' => 'Pledge Reports',
      'CiviGrant' => 'Grant Reports',
      'CiviMail' => 'Mailing Reports',
      'CiviCampaign' => 'Campaign Reports',
    );

    // Create or update the top level Reports link.
    $reports_nav = self::createOrUpdateTopLevelReportsNavItem($domain_id);

    // Get all active report instances grouped by component.
    $components = self::getAllActiveReportsByComponent($domain_id);
    foreach ($components as $component_id => $component) {
      // Create or update the per component reports links.
      $component_nav_name = $component['name'];
      if (isset($component_to_nav_name[$component_nav_name])) {
        $component_nav_name = $component_to_nav_name[$component_nav_name];
      }
      $permission = "access {$component['name']}";
      if ($component['name'] === 'CiviContact') {
        $permission = "administer CiviCRM";
      }
      elseif ($component['name'] === 'CiviCampaign') {
        $permission = "access CiviReport";
      }
      $component_nav = self::createOrUpdateReportNavItem($component_nav_name, 'civicrm/report/list',
        "compid={$component_id}&reset=1", $reports_nav->id, $permission, $domain_id, TRUE);
      foreach ($component['reports'] as $report_id => $report) {
        // Create or update the report instance links.
        $report_nav = self::createOrUpdateReportNavItem($report['title'], $report['url'], 'reset=1', $component_nav->id, $report['permission'], $domain_id, FALSE, TRUE);
        // Update the report instance to include the navigation id.
        $query = "UPDATE civicrm_report_instance SET navigation_id = %1 WHERE id = %2";
        $params = array(
          1 => array($report_nav->id, 'Integer'),
          2 => array($report_id, 'Integer'),
        );
        CRM_Core_DAO::executeQuery($query, $params);
      }
    }

    // Create or update the All Reports link.
    self::createOrUpdateReportNavItem('All Reports', 'civicrm/report/list', 'reset=1', $reports_nav->id, 'access CiviReport', $domain_id, TRUE);
  }

  /**
   * Create the top level 'Reports' item in the navigation tree.
   *
   * @param int $domain_id
   *
   * @return bool|\CRM_Core_DAO
   */
  static public function createOrUpdateTopLevelReportsNavItem($domain_id) {
    $id = NULL;

    $dao = new CRM_Core_BAO_Navigation();
    $dao->name = 'Reports';
    $dao->domain_id = $domain_id;
    // The first selectAdd clears it - so that we only retrieve the one field.
    $dao->selectAdd();
    $dao->selectAdd('id');
    if ($dao->find(TRUE)) {
      $id = $dao->id;
    }

    $nav = self::createReportNavItem('Reports', NULL, NULL, NULL, 'access CiviReport', $id, $domain_id);
    return $nav;
  }

  /**
   * Retrieve a navigation item using it's url.
   *
   * Note that we use LIKE to permit a wildcard as the calling code likely doesn't
   * care about output params appended.
   *
   * @param string $url
   * @param array $url_params
   *
   * @param int|null $parent_id
   *   Optionally restrict to one parent.
   *
   * @return bool|\CRM_Core_BAO_Navigation
   */
  public static function getNavItemByUrl($url, $url_params, $parent_id = NULL) {
    $nav = new CRM_Core_BAO_Navigation();
    $nav->parent_id = $parent_id;
    $nav->whereAdd("url LIKE '{$url}?{$url_params}'");

    if ($nav->find(TRUE)) {
      return $nav;
    }
    return FALSE;
  }

  /**
   * Get all active reports, organised by component.
   *
   * @param int $domain_id
   *
   * @return array
   */
  public static function getAllActiveReportsByComponent($domain_id) {
    $sql = "
      SELECT
        civicrm_report_instance.id, civicrm_report_instance.title, civicrm_report_instance.permission, civicrm_component.name, civicrm_component.id AS component_id
      FROM
        civicrm_option_group
      LEFT JOIN
        civicrm_option_value ON civicrm_option_value.option_group_id = civicrm_option_group.id AND civicrm_option_group.name = 'report_template'
      LEFT JOIN
        civicrm_report_instance ON civicrm_option_value.value = civicrm_report_instance.report_id
      LEFT JOIN
        civicrm_component ON civicrm_option_value.component_id = civicrm_component.id
      WHERE
        civicrm_option_value.is_active = 1
      AND
        civicrm_report_instance.domain_id = %1
      ORDER BY civicrm_option_value.weight";

    $dao = CRM_Core_DAO::executeQuery($sql, array(
      1 => array($domain_id, 'Integer'),
    ));
    $rows = array();
    while ($dao->fetch()) {
      $component_name = is_null($dao->name) ? 'CiviContact' : $dao->name;
      $component_id = is_null($dao->component_id) ? 99 : $dao->component_id;
      $rows[$component_id]['name'] = $component_name;
      $rows[$component_id]['reports'][$dao->id] = array(
        'title' => $dao->title,
        'url' => "civicrm/report/instance/{$dao->id}",
        'permission' => $dao->permission,
      );
    }
    return $rows;
  }

  /**
   * Create or update a navigation item for a report instance.
   *
   * The function will check whether create or update is required.
   *
   * @param string $name
   * @param string $url
   * @param string $url_params
   * @param int $parent_id
   * @param string $permission
   * @param int $domain_id
   *
   * @param bool $onlyMatchParentID
   *   If True then do not match with a url that has a different parent
   *   (This is because for top level items there is a risk of 'stealing' rows that normally
   *   live under 'Contact' and intentionally duplicate the report examples.)
   *
   * @return \CRM_Core_DAO_Navigation
   */
  protected static function createOrUpdateReportNavItem($name, $url, $url_params, $parent_id, $permission,
                                                        $domain_id, $onlyMatchParentID = FALSE, $useWildcard = TRUE) {
    $id = NULL;
    $existing_url_params = $useWildcard ? $url_params . '%' : $url_params;
    $existing_nav = CRM_Core_BAO_Navigation::getNavItemByUrl($url, $existing_url_params, ($onlyMatchParentID ? $parent_id : NULL));
    if ($existing_nav) {
      $id = $existing_nav->id;
    }

    $nav = self::createReportNavItem($name, $url, $url_params, $parent_id, $permission, $id, $domain_id);
    return $nav;
  }

  /**
   * Create a navigation item for a report instance.
   *
   * @param string $name
   * @param string $url
   * @param string $url_params
   * @param int $parent_id
   * @param string $permission
   * @param int $id
   * @param int $domain_id
   *   ID of domain to create item in.
   *
   * @return \CRM_Core_DAO_Navigation
   */
  public static function createReportNavItem($name, $url, $url_params, $parent_id, $permission, $id, $domain_id) {
    if ($url !== NULL) {
      $url = "{$url}?{$url_params}";
    }
    $params = array(
      'name' => $name,
      'label' => ts($name),
      'url' => $url,
      'parent_id' => $parent_id,
      'is_active' => TRUE,
      'permission' => array(
        $permission,
      ),
      'domain_id' => $domain_id,
    );
    if ($id) {
      $params['id'] = $id;
    }
    return CRM_Core_BAO_Navigation::add($params);
  }

  /**
   * Get cache key.
   *
   * @param int $cid
   *
   * @return object|string
   */
  public static function getCacheKey($cid) {
    $key = Civi::service('settings_manager')
      ->getBagByContact(NULL, $cid)
      ->get('navigation');
    if (strlen($key) !== self::CACHE_KEY_STRLEN) {
      $key = self::resetNavigation($cid);
    }
    return $key;
  }

}
