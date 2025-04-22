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
class CRM_Core_BAO_Navigation extends CRM_Core_DAO_Navigation {

  // Number of characters in the menu js cache key
  const CACHE_KEY_STRLEN = 8;

  /**
   * Override parent method to flush caches after a write op.
   *
   * Note: this only applies to APIv4 because v3 uses the singular writeRecord.
   *
   * @param array[] $records
   * @return CRM_Core_DAO_Navigation[]
   * @throws CRM_Core_Exception
   */
  public static function writeRecords($records): array {
    $results = [];
    foreach ($records as $record) {
      $results[] = self::writeRecord($record);
    }
    self::resetNavigation();
    return $results;
  }

  /**
   * Override parent method to flush caches after delete.
   *
   * Note: this only applies to APIv4 because v3 uses the singular writeRecord.
   *
   * @param array[] $records
   * @return CRM_Core_DAO_Navigation[]
   * @throws CRM_Core_Exception
   */
  public static function deleteRecords(array $records) {
    $results = [];
    foreach ($records as $record) {
      $results[] = self::deleteRecord($record);
    }
    self::resetNavigation();
    return $results;
  }

  /**
   * @deprecated - this bypasses hooks.
   * @param int $id
   * @param bool $is_active
   * @return bool
   */
  public static function setIsActive($id, $is_active) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_Navigation', $id, 'is_active', $is_active);
  }

  /**
   * Deprecated in favor of APIv4
   *
   * @deprecated
   * @param array $params Submitted values
   * @return CRM_Core_DAO_Navigation
   */
  public static function add(&$params) {
    if (empty($params['id'])) {
      $params['is_active'] ??= FALSE;
      $params['has_separator'] ??= FALSE;
      $params['domain_id'] = $params['domain_id'] ?? CRM_Core_Config::domainID();
    }

    if (!isset($params['id']) ||
      (($params['parent_id'] ?? NULL) != ($params['current_parent_id'] ?? NULL))
    ) {
      /* re/calculate the weight, if the Parent ID changed OR create new menu */

      $navName = $params['name'] ?? NULL;
      $navLabel = $params['label'] ?? NULL;
      if ($navName) {
        $params['name'] = $navName;
      }
      elseif ($navLabel) {
        $params['name'] = $navLabel;
      }

      $params['weight'] = self::calculateWeight($params['parent_id'] ?? NULL);
    }

    return self::writeRecord($params);
  }

  /**
   * Retrieve DB object and copy to defaults array.
   *
   * @param array $params
   *   Array of criteria values.
   * @param array $defaults
   *   Array to be populated with found values.
   *
   * @return self|null
   *   The DAO object, if found.
   *
   * @deprecated
   */
  public static function retrieve($params, &$defaults) {
    $params['domain_id'] = CRM_Core_Config::domainID();
    return self::commonRetrieve(self::class, $params, $defaults);
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
    $cacheKeyString = "navigationList_" . CRM_Core_Config::domainID();
    $whereClause = '';

    $config = CRM_Core_Config::singleton();

    // check if we can retrieve from database cache
    $navigations = Civi::cache('navigation')->get($cacheKeyString);

    if (!$navigations) {
      $domainID = CRM_Core_Config::domainID();
      $query = "
SELECT id, label, parent_id, weight, is_active, name
FROM civicrm_navigation WHERE domain_id = $domainID
ORDER BY weight";
      $result = CRM_Core_DAO::executeQuery($query);

      $pidGroups = [];
      while ($result->fetch()) {
        $pidGroups[$result->parent_id][$result->label] = $result->id;
      }

      foreach ($pidGroups[''] as $label => $val) {
        $pidGroups[''][$label] = self::_getNavigationValue($val, $pidGroups);
      }

      $navigations = [];
      self::_getNavigationLabel($pidGroups[''], $navigations);

      Civi::cache('navigation')->set($cacheKeyString, $navigations);
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
      $translatedLabel = $i18n->crm_translate($label, ['context' => 'menu']);
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
      $list = ['navigation_id' => $val];
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
   * @return array
   *   nested array of menus
   */
  public static function buildNavigationTree() {
    $domainID = CRM_Core_Config::domainID();
    $navigationTree = [];

    $navigationMenu = new self();
    $navigationMenu->domain_id = $domainID;
    $navigationMenu->orderBy('parent_id, weight');
    $navigationMenu->find();

    while ($navigationMenu->fetch()) {
      $navigationTree[$navigationMenu->id] = [
        'attributes' => [
          'label' => $navigationMenu->label,
          'name' => $navigationMenu->name,
          'url' => $navigationMenu->url,
          'icon' => $navigationMenu->icon,
          'weight' => $navigationMenu->weight,
          'permission' => $navigationMenu->permission,
          'operator' => $navigationMenu->permission_operator,
          'separator' => $navigationMenu->has_separator,
          'parentID' => $navigationMenu->parent_id,
          'navID' => $navigationMenu->id,
          'active' => $navigationMenu->is_active,
        ],
      ];
    }

    return self::buildTree($navigationTree);
  }

  /**
   * Convert flat array to nested.
   *
   * @param array $elements
   * @param int|null $parentId
   *
   * @return array
   */
  private static function buildTree($elements, $parentId = NULL) {
    $branch = [];

    foreach ($elements as $id => $element) {
      if ($element['attributes']['parentID'] == $parentId) {
        $children = self::buildTree($elements, $id);
        if ($children) {
          $element['child'] = $children;
        }
        $branch[$id] = $element;
      }
    }

    return $branch;
  }

  /**
   * buildNavigationTree retreives items in order. We call this function to
   * ensure that any items added by the hook are also in the correct order.
   */
  public static function orderByWeight(&$navigations) {
    // sort each item in navigations by weight
    usort($navigations, function($a, $b) {

      // If no weight have been defined for an item put it at the end of the list
      if (!isset($a['attributes']['weight'])) {
        $a['attributes']['weight'] = 1000;
      }
      if (!isset($b['attributes']['weight'])) {
        $b['attributes']['weight'] = 1000;
      }
      return (int) $a['attributes']['weight'] - (int) $b['attributes']['weight'];
    });

    // If any of the $navigations have children, recurse
    foreach ($navigations as &$navigation) {
      if (isset($navigation['child'])) {
        self::orderByWeight($navigation['child']);
      }
    }
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
        if ($origKey != $newKey) {
          // If the keys are different, reset the array index to match.
          $nodes[$newKey] = $nodes[$origKey];
          unset($nodes[$origKey]);
          $origKey = $newKey;
        }
      }
      if (isset($nodes[$origKey]['child']) && is_array($nodes[$origKey]['child'])) {
        self::_fixNavigationMenu($nodes[$origKey]['child'], $maxNavID, $nodes[$origKey]['attributes']['navID']);
      }
    }
  }

  /**
   * Check if a menu item should be visible based on permissions and component.
   *
   * @param $item
   * @return bool
   */
  public static function checkPermission($item) {
    if (!empty($item['permission'])) {
      $permissions = explode(',', $item['permission']);
      $operator = $item['operator'] ?? NULL;
      $hasPermission = FALSE;
      foreach ($permissions as $key) {
        $key = trim($key);
        $showItem = TRUE;

        //get the component name from permission.
        $componentName = CRM_Core_Permission::getComponentName($key);

        if ($componentName) {
          if (!CRM_Core_Component::isEnabled($componentName) || !CRM_Core_Permission::check($key)) {
            $showItem = FALSE;
            if ($operator == 'AND') {
              return FALSE;
            }
          }
          else {
            $hasPermission = TRUE;
          }
        }
        elseif (!CRM_Core_Permission::check($key)) {
          $showItem = FALSE;
          if ($operator == 'AND') {
            return FALSE;
          }
        }
        else {
          $hasPermission = TRUE;
        }
      }

      if (empty($showItem) && !$hasPermission) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Turns relative URLs (like civicrm/foo/bar) into fully-formed
   * ones (i.e. example.com/wp-admin?q=civicrm/dashboard).
   *
   * If the URL is already fully-formed, nothing will be done.
   *
   * @param string $url
   *
   * @return string
   */
  public static function makeFullyFormedUrl($url) {
    if (self::isNotFullyFormedUrl($url)) {
      //CRM-7656 --make sure to separate out url path from url params,
      //as we'r going to validate url path across cross-site scripting.
      $path = parse_url($url, PHP_URL_PATH);
      $q = parse_url($url, PHP_URL_QUERY);
      $fragment = parse_url($url, PHP_URL_FRAGMENT);
      return CRM_Utils_System::url($path, $q, FALSE, $fragment);
    }

    if (!str_contains($url, '&amp;')) {
      return htmlspecialchars($url);
    }

    return $url;
  }

  /**
   * Checks if the given URL is not fully-formed
   *
   * @param string $url
   *
   * @return bool
   */
  private static function isNotFullyFormedUrl($url) {
    return substr($url, 0, 4) !== 'http' && $url[0] !== '/' && $url[0] !== '#';
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
      $ser = serialize($newKey);
      $query = "UPDATE civicrm_setting SET value = '$ser' WHERE name='navigation' AND contact_id IS NOT NULL";
      CRM_Core_DAO::executeQuery($query);
      Civi::cache('navigation')->flush();
      // reset ACL and System caches
      CRM_Core_BAO_Cache::resetCaches();
    }
    else {
      // before inserting check if contact id exists in db
      // this is to handle weird case when contact id is in session but not in db
      $contact = new CRM_Contact_DAO_Contact();
      $contact->id = $contactID;
      if ($contact->find(TRUE)) {
        Civi::contactSettings($contactID)->set('navigation', $newKey);
      }
    }

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
    $label = $params['data'] ?? NULL;

    switch ($type) {
      case "move":
        self::processMove($nodeID, $referenceID, $position);
        break;

      case "rename":
        self::writeRecord(['id' => $nodeID, 'label' => $label]);
        break;

      case "delete":
        self::deleteRecord(['id' => $nodeID]);
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
    $params = [1 => [$position, 'Positive']];
    $newWeight = CRM_Core_DAO::singleValueQuery($sql, $params);

    // this means node is moved to last position, so you need to get the weight of last element + 1
    if (!$newWeight) {
      // If this is not the first item being added to a parent
      if ($position) {
        $lastPosition = $position - 1;
        $sql = "SELECT weight from civicrm_navigation WHERE {$parentClause} ORDER BY weight LIMIT %1, 1";
        $params = [1 => [$lastPosition, 'Positive']];
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

    // now update the existing nodes to weight + 1, if required.
    if ($incrementOtherNodes) {
      $query = "UPDATE civicrm_navigation SET weight = weight + 1
                  WHERE {$parentClause} AND weight >= {$newWeight}";

      CRM_Core_DAO::executeQuery($query);
    }

    // finally set the weight and parent of current node
    self::writeRecord(['id' => $nodeID, 'weight' => $newWeight, 'parent_id' => $newParentID]);
  }

  /**
   * Function to process rename action for tree.
   *
   * @param int $nodeID
   * @param $label
   * @deprecated  - use API
   */
  public static function processRename($nodeID, $label) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    self::writeRecord(['id' => $nodeID, 'label' => $label]);
  }

  /**
   * Process delete action for tree.
   *
   * @param int $nodeID
   * @deprecated - use API
   */
  public static function processDelete($nodeID) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    self::deleteRecord(['id' => $nodeID]);
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
    $component_to_nav_name = [
      'CiviContact' => 'Contact Reports',
      'CiviContribute' => 'Contribution Reports',
      'CiviMember' => 'Membership Reports',
      'CiviEvent' => 'Event Reports',
      'CiviPledge' => 'Pledge Reports',
      'CiviGrant' => 'Grant Reports',
      'CiviMail' => 'Mailing Reports',
      'CiviCampaign' => 'Campaign Reports',
    ];

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
        $params = [
          1 => [$report_nav->id, 'Integer'],
          2 => [$report_id, 'Integer'],
        ];
        CRM_Core_DAO::executeQuery($query, $params);
      }
    }

    // Create or update the All Reports link.
    self::createOrUpdateReportNavItem('All Reports', 'civicrm/report/list', 'reset=1', $reports_nav->id, 'access CiviReport', $domain_id, TRUE);
    // Create or update the My Reports link.
    self::createOrUpdateReportNavItem('My Reports', 'civicrm/report/list', 'myreports=1&reset=1', $reports_nav->id, 'access CiviReport', $domain_id, TRUE);

  }

  /**
   * Create the top level 'Reports' item in the navigation tree.
   *
   * @param int $domain_id
   *
   * @return bool|\CRM_Core_DAO
   */
  public static function createOrUpdateTopLevelReportsNavItem($domain_id) {
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
   * @param string $url_params
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

    $dao = CRM_Core_DAO::executeQuery($sql, [
      1 => [$domain_id, 'Integer'],
    ]);
    $rows = [];
    while ($dao->fetch()) {
      $component_name = is_null($dao->name) ? 'CiviContact' : $dao->name;
      $component_id = is_null($dao->component_id) ? 99 : $dao->component_id;
      $rows[$component_id]['name'] = $component_name;
      $rows[$component_id]['reports'][$dao->id] = [
        'title' => $dao->title,
        'url' => "civicrm/report/instance/{$dao->id}",
        'permission' => $dao->permission,
      ];
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
   * @param bool $useWildcard
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
    $params = [
      'name' => $name,
      'label' => _ts($name),
      'url' => $url,
      'parent_id' => $parent_id,
      'is_active' => TRUE,
      'permission' => [
        $permission,
      ],
      'domain_id' => $domain_id,
    ];
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
    if (strlen($key ?? '') !== self::CACHE_KEY_STRLEN) {
      $key = self::resetNavigation($cid);
    }
    return $key;
  }

  /**
   * Unset menu items for disabled components and non-permissioned users
   *
   * @param $menu
   */
  public static function filterByPermission(&$menu) {
    foreach ($menu as $key => $item) {
      if (
        (array_key_exists('active', $item['attributes']) && !$item['attributes']['active']) ||
        !CRM_Core_BAO_Navigation::checkPermission($item['attributes'])
      ) {
        unset($menu[$key]);
        continue;
      }
      if (!empty($item['child'])) {
        self::filterByPermission($menu[$key]['child']);
      }
    }
  }

  /**
   * Count all nested child items (including sub-children and sub-sub-children, etc).
   *
   * @param int $id
   *   The ID of the parent item.
   *
   * @return int
   *   The total number of children.
   */
  public static function getChildCount(int $id): int {
    $childCount = 0;
    $parentIds = [$id];
    while ($parentIds) {
      $parentIds = \Civi\Api4\Navigation::get(FALSE)
        ->addWhere('parent_id', 'IN', $parentIds)
        ->addSelect('id')
        ->execute()->column('id');
      $childCount += count($parentIds);
    }
    return $childCount;
  }

  /**
   * @param array $menu
   */
  public static function buildHomeMenu(&$menu) {
    foreach ($menu as &$item) {
      if (($item['attributes']['name'] ?? NULL) === 'Home') {
        unset($item['attributes']['label'], $item['attributes']['url']);
        $item['attributes']['icon'] = 'crm-logo-sm';
        $item['attributes']['attr']['accesskey'] = 'm';
        $item['child'] = [];
        $item['child'][] = [
          'attributes' => [
            'label' => ts('CiviCRM Home'),
            'name' => 'CiviCRM Home',
            'url' => 'civicrm/dashboard?reset=1',
            'icon' => 'crm-i fa-house-user',
            'weight' => 1,
          ],
        ];
        if (CIVICRM_UF !== 'Standalone') {
          $item['child'][] = [
            'attributes' => [
              'label' => ts('Hide Menu'),
              'name' => 'Hide Menu',
              'url' => '#hidemenu',
              'icon' => 'crm-i fa-minus',
              'weight' => 2,
            ],
          ];
        }
        else {
          $item['child'][] = [
            'attributes' => [
              'label' => ts('Change Password'),
              'name' => 'Change Password',
              'url' => 'civicrm/admin/user/password',
              'icon' => 'crm-i fa-keyboard',
              'weight' => 2,
            ],
          ];
        }
        $item['child'][] = [
          'attributes' => [
            'label' => ts('Log out'),
            'name' => 'Log out',
            'url' => 'civicrm/logout?reset=1',
            'icon' => 'crm-i fa-person-walking-arrow-right',
            'weight' => 3,
          ],
        ];
        return;
      }
    }
  }

}
