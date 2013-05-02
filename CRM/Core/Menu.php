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
 * This file contains the various menus of the CiviCRM module
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

require_once 'CRM/Core/I18n.php';
class CRM_Core_Menu {

  /**
   * the list of menu items
   *
   * @var array
   * @static
   */
  static $_items = NULL;

  /**
   * the list of permissioned menu items
   *
   * @var array
   * @static
   */
  static $_permissionedItems = NULL;

  static $_serializedElements = array(
    'access_arguments',
    'access_callback',
    'page_arguments',
    'page_callback',
    'breadcrumb',
  );

  static $_menuCache = NULL;
  CONST MENU_ITEM = 1;

  static function &xmlItems() {
    if (!self::$_items) {
      $config = CRM_Core_Config::singleton();

      // We needs this until Core becomes a component
      $coreMenuFilesNamespace = 'CRM_Core_xml_Menu';
      $coreMenuFilesPath = str_replace('_', DIRECTORY_SEPARATOR, $coreMenuFilesNamespace);
      global $civicrm_root;
      $files = CRM_Utils_File::getFilesByExtension($civicrm_root . DIRECTORY_SEPARATOR . $coreMenuFilesPath, 'xml');

      // Grab component menu files
      $files = array_merge($files,
        CRM_Core_Component::xmlMenu()
      );

      // lets call a hook and get any additional files if needed
      CRM_Utils_Hook::xmlMenu($files);

      self::$_items = array();
      foreach ($files as $file) {
        self::read($file, self::$_items);
      }
    }

    return self::$_items;
  }

  static function read($name, &$menu) {

    $config = CRM_Core_Config::singleton();

    $xml = simplexml_load_file($name);
    foreach ($xml->item as $item) {
      if (!(string ) $item->path) {
        CRM_Core_Error::debug('i', $item);
        CRM_Core_Error::fatal();
      }
      $path = (string ) $item->path;
      $menu[$path] = array();
      unset($item->path);
      foreach ($item as $key => $value) {
        $key = (string ) $key;
        $value = (string ) $value;
        if (strpos($key, '_callback') &&
          strpos($value, '::')
        ) {
          $value = explode('::', $value);
        }
        elseif ($key == 'access_arguments') {
          if (strpos($value, ',') ||
            strpos($value, ';')
          ) {
            if (strpos($value, ',')) {
              $elements = explode(',', $value);
              $op = 'and';
            }
            else {
              $elements = explode(';', $value);
              $op = 'or';
            }
            $items = array();
            foreach ($elements as $element) {
              $items[] = $element;
            }
            $value = array($items, $op);
          }
          else {
            $value = array(array($value), 'and');
          }
        }
        elseif ($key == 'is_public' || $key == 'is_ssl') {
          $value = ($value == 'true' || $value == 1) ? 1 : 0;
        }
        $menu[$path][$key] = $value;
      }
    }
  }

  /**
   * This function defines information for various menu items
   *
   * @static
   * @access public
   */
  static function &items() {
    return self::xmlItems();
  }

  static function isArrayTrue(&$values) {
    foreach ($values as $name => $value) {
      if (!$value) {
        return FALSE;
      }
    }
    return TRUE;
  }

  static function fillMenuValues(&$menu, $path) {
    $fieldsToPropagate = array(
      'access_callback',
      'access_arguments',
      'page_callback',
      'page_arguments',
      'is_ssl',
    );
    $fieldsPresent = array();
    foreach ($fieldsToPropagate as $field) {
      $fieldsPresent[$field] = CRM_Utils_Array::value($field, $menu[$path]) !== NULL ? TRUE : FALSE;
    }

    $args = explode('/', $path);
    while (!self::isArrayTrue($fieldsPresent) && !empty($args)) {

      array_pop($args);
      $parentPath = implode('/', $args);

      foreach ($fieldsToPropagate as $field) {
        if (!$fieldsPresent[$field]) {
          if (CRM_Utils_Array::value($field, CRM_Utils_Array::value($parentPath, $menu)) !== NULL) {
            $fieldsPresent[$field] = TRUE;
            $menu[$path][$field] = $menu[$parentPath][$field];
          }
        }
      }
    }

    if (self::isArrayTrue($fieldsPresent)) {
      return;
    }

    $messages = array();
    foreach ($fieldsToPropagate as $field) {
      if (!$fieldsPresent[$field]) {
        $messages[] = ts("Could not find %1 in path tree",
          array(1 => $field)
        );
      }
    }
    CRM_Core_Error::fatal("'$path': " . implode(', ', $messages));
  }

  /**
   * We use this function to
   *
   * 1. Compute the breadcrumb
   * 2. Compute local tasks value if any
   * 3. Propagate access argument, access callback, page callback to the menu item
   * 4. Build the global navigation block
   *
   */
  static function build(&$menu) {
    foreach ($menu as $path => $menuItems) {
      self::buildBreadcrumb($menu, $path);
      self::fillMenuValues($menu, $path);
      self::fillComponentIds($menu, $path);
      self::buildReturnUrl($menu, $path);

      // add add page_type if not present
      if (!isset($menu[$path]['page_type'])) {
        $menu[$path]['page_type'] = 0;
      }
    }

    self::buildAdminLinks($menu);
  }

  static function store($truncate = TRUE) {
    // first clean up the db
    if ($truncate) {
      $query = 'TRUNCATE civicrm_menu';
      CRM_Core_DAO::executeQuery($query);
    }
    $menuArray = self::items();

    self::build($menuArray);


    $config = CRM_Core_Config::singleton();

    foreach ($menuArray as $path => $item) {
      $menu            = new CRM_Core_DAO_Menu();
      $menu->path      = $path;
      $menu->domain_id = CRM_Core_Config::domainID();

      $menu->find(TRUE);

      $menu->copyValues($item);

      foreach (self::$_serializedElements as $element) {
        if (!isset($item[$element]) ||
          $item[$element] == 'null'
        ) {
          $menu->$element = NULL;
        }
        else {
          $menu->$element = serialize($item[$element]);
        }
      }

      $menu->save();
    }
  }

  static function buildAdminLinks(&$menu) {
    $values = array();

    foreach ($menu as $path => $item) {
      if (!CRM_Utils_Array::value('adminGroup', $item)) {
        continue;
      }

      $query = CRM_Utils_Array::value('path_arguments', $item) ? str_replace(',', '&', $item['path_arguments']) . '&reset=1' : 'reset=1';

      $value = array(
        'title' => $item['title'],
        'desc' => CRM_Utils_Array::value('desc', $item),
        'id' => strtr($item['title'], array(
          '(' => '_', ')' => '', ' ' => '',
            ',' => '_', '/' => '_',
          )
        ),
        'url' => CRM_Utils_System::url($path, $query, FALSE),
        'icon' => CRM_Utils_Array::value('icon', $item),
        'extra' => CRM_Utils_Array::value('extra', $item),
      );
      if (!array_key_exists($item['adminGroup'], $values)) {
        $values[$item['adminGroup']] = array();
        $values[$item['adminGroup']]['fields'] = array();
      }
      $weight = CRM_Utils_Array::value('weight', $item, 0);
      $values[$item['adminGroup']]['fields']["{weight}.{$item['title']}"] = $value;
      $values[$item['adminGroup']]['component_id'] = $item['component_id'];
    }

    foreach ($values as $group => $dontCare) {
      $values[$group]['perColumn'] = round(count($values[$group]['fields']) / 2);
      ksort($values[$group]);
    }

    $menu['admin'] = array('breadcrumb' => $values);
  }

  static function &getNavigation($all = FALSE) {
    CRM_Core_Error::fatal();

    if (!self::$_menuCache) {
      self::get('navigation');
    }

    if (CRM_Core_Config::isUpgradeMode()) {
      return array();
    }

    if (!array_key_exists('navigation', self::$_menuCache)) {
      // problem could be due to menu table empty. Just do a
      // menu store and try again
      self::store();

      // here we goo
      self::get('navigation');
      if (!array_key_exists('navigation', self::$_menuCache)) {
        CRM_Core_Error::fatal();
      }
    }
    $nav = &self::$_menuCache['navigation'];

    if (!$nav ||
      !isset($nav['breadcrumb'])
    ) {
      return NULL;
    }

    $values = &$nav['breadcrumb'];
    $config = CRM_Core_Config::singleton();
    foreach ($values as $index => $item) {
      if (strpos(CRM_Utils_Array::value($config->userFrameworkURLVar, $_REQUEST),
          $item['path']
        ) === 0) {
        $values[$index]['active'] = 'class="active"';
      }
      else {
        $values[$index]['active'] = '';
      }

      if ($values[$index]['parent']) {
        $parent = $values[$index]['parent'];

        // only reset if still a leaf
        if ($values[$parent]['class'] == 'leaf') {
          $values[$parent]['class'] = 'collapsed';
        }

        // if a child or the parent is active, expand the menu
        if ($values[$index]['active'] ||
          $values[$parent]['active']
        ) {
          $values[$parent]['class'] = 'expanded';
        }

        // make the parent inactive if the child is active
        if ($values[$index]['active'] &&
          $values[$parent]['active']
        ) {
          $values[$parent]['active'] = '';
        }
      }
    }


    if (!$all) {
      // remove all collapsed menu items from the array
      foreach ($values as $weight => $v) {
        if ($v['parent'] &&
          $values[$v['parent']]['class'] == 'collapsed'
        ) {
          unset($values[$weight]);
        }
      }
    }

    // check permissions for the rest
    $activeChildren = array();

    foreach ($values as $weight => $v) {
      if (CRM_Core_Permission::checkMenuItem($v)) {
        if ($v['parent']) {
          $activeChildren[] = $weight;
        }
      }
      else {
        unset($values[$weight]);
      }
    }

    // add the start / end tags
    $len = count($activeChildren) - 1;
    if ($len >= 0) {
      $values[$activeChildren[0]]['start'] = TRUE;
      $values[$activeChildren[$len]]['end'] = TRUE;
    }

    ksort($values, SORT_NUMERIC);
    $i18n = CRM_Core_I18n::singleton();
    $i18n->localizeTitles($values);

    return $values;
  }

  static function &getAdminLinks() {
    $links = self::get('admin');

    if (!$links ||
      !isset($links['breadcrumb'])
    ) {
      return NULL;
    }

    $values = &$links['breadcrumb'];
    return $values;
  }

  /**
   * Get the breadcrumb for a given path.
   *
   * @param  array   $menu   An array of all the menu items.
   * @param  string  $path   Path for which breadcrumb is to be build.
   *
   * @return array  The breadcrumb for this path
   *
   * @static
   * @access public
   */
  static function buildBreadcrumb(&$menu, $path) {
    $crumbs = array();

    $pathElements = explode('/', $path);
    array_pop($pathElements);

    $currentPath = NULL;
    while ($newPath = array_shift($pathElements)) {
      $currentPath = $currentPath ? ($currentPath . '/' . $newPath) : $newPath;

      // when we come accross breadcrumb which involves ids,
      // we should skip now and later on append dynamically.
      if (isset($menu[$currentPath]['skipBreadcrumb'])) {
        continue;
      }

      // add to crumb, if current-path exists in params.
      if (array_key_exists($currentPath, $menu) &&
        isset($menu[$currentPath]['title'])
      ) {
        $urlVar = CRM_Utils_Array::value('path_arguments', $menu[$currentPath]) ? '&' . $menu[$currentPath]['path_arguments'] : '';
        $crumbs[] = array(
          'title' => $menu[$currentPath]['title'],
          'url' => CRM_Utils_System::url($currentPath,
            'reset=1' . $urlVar, FALSE
          ),
        );
      }
    }
    $menu[$path]['breadcrumb'] = $crumbs;

    return $crumbs;
  }

  static function buildReturnUrl(&$menu, $path) {
    if (!isset($menu[$path]['return_url'])) {
      list($menu[$path]['return_url'], $menu[$path]['return_url_args']) = self::getReturnUrl($menu, $path);
    }
  }

  static function getReturnUrl(&$menu, $path) {
    if (!isset($menu[$path]['return_url'])) {
      $pathElements = explode('/', $path);
      array_pop($pathElements);

      if (empty($pathElements)) {
        return array(NULL, NULL);
      }
      $newPath = implode('/', $pathElements);

      return self::getReturnUrl($menu, $newPath);
    }
    else {
      return array(
        CRM_Utils_Array::value('return_url',
          $menu[$path]
        ),
        CRM_Utils_Array::value('return_url_args',
          $menu[$path]
        ),
      );
    }
  }

  static function fillComponentIds(&$menu, $path) {
    static $cache = array();

    if (array_key_exists('component_id', $menu[$path])) {
      return;
    }

    $args = explode('/', $path);

    if (count($args) > 1) {
      $compPath = $args[0] . '/' . $args[1];
    }
    else {
      $compPath = $args[0];
    }

    $componentId = NULL;

    if (array_key_exists($compPath, $cache)) {
      $menu[$path]['component_id'] = $cache[$compPath];
    }
    else {
      if (CRM_Utils_Array::value('component', CRM_Utils_Array::value($compPath, $menu))) {
        $componentId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Component',
          $menu[$compPath]['component'],
          'id', 'name'
        );
      }
      $menu[$path]['component_id'] = $componentId ? $componentId : NULL;
      $cache[$compPath] = $menu[$path]['component_id'];
    }
  }

  static function get($path) {
    // return null if menu rebuild
    $config = CRM_Core_Config::singleton();

    $params = array();

    $args = explode('/', $path);

    $elements = array();
    while (!empty($args)) {
      $string     = implode('/', $args);
      $string     = CRM_Core_DAO::escapeString($string);
      $elements[] = "'{$string}'";
      array_pop($args);
    }

    $queryString       = implode(', ', $elements);
    $domainID          = CRM_Core_Config::domainID();
    $domainWhereClause = " AND domain_id = $domainID ";
    if ($config->isUpgradeMode() &&
      !CRM_Core_DAO::checkFieldExists('civicrm_menu', 'domain_id')
    ) {
      //domain_id wouldn't be available for earlier version of
      //3.0 and therefore can't be used as part of query for
      //upgrade case
      $domainWhereClause = "";
    }

    $query = "
(
  SELECT *
  FROM     civicrm_menu
  WHERE    path in ( $queryString )
           $domainWhereClause
  ORDER BY length(path) DESC
  LIMIT    1
)
";

    if ($path != 'navigation') {
      $query .= "
UNION (
  SELECT *
  FROM   civicrm_menu
  WHERE  path IN ( 'navigation' )
         $domainWhereClause
)
";
    }

    $menu = new CRM_Core_DAO_Menu();
    $menu->query($query);

    self::$_menuCache = array();
    $menuPath = NULL;
    while ($menu->fetch()) {
      self::$_menuCache[$menu->path] = array();
      CRM_Core_DAO::storeValues($menu, self::$_menuCache[$menu->path]);

      foreach (self::$_serializedElements as $element) {
        self::$_menuCache[$menu->path][$element] = unserialize($menu->$element);

        if (strpos($path, $menu->path) !== FALSE) {
          $menuPath = &self::$_menuCache[$menu->path];
        }
      }
    }

    if (strstr($path, 'report/instance')) {
      $args = explode('/', $path);
      if (is_numeric(end($args))) {
        $menuPath['path'] .= '/' . end($args);
      }
    }

    // *FIXME* : hack for 2.1 -> 2.2 upgrades.
    if ($path == 'civicrm/upgrade') {
      $menuPath['page_callback'] = 'CRM_Upgrade_Page_Upgrade';
      $menuPath['access_arguments'][0][] = 'administer CiviCRM';
      $menuPath['access_callback'] = array('CRM_Core_Permission', 'checkMenu');
    }
    // *FIXME* : hack for 4.1 -> 4.2 upgrades.
    if (preg_match('/^civicrm\/(upgrade\/)?queue\//', $path)) {
      CRM_Queue_Menu::alter($path, $menuPath);
    }

    // Part of upgrade framework but not run inside main upgrade because it deletes data
    // Once we have another example of a 'cleanup' we should generalize the clause below so it grabs string
    // which follows upgrade/ and checks for existence of a function in Cleanup class.
    if ($path == 'civicrm/upgrade/cleanup425') {
      $menuPath['page_callback'] = array('CRM_Upgrade_Page_Cleanup','cleanup425');
      $menuPath['access_arguments'][0][] = 'administer CiviCRM';
      $menuPath['access_callback'] = array('CRM_Core_Permission', 'checkMenu');
    }

    if (!empty($menuPath)) {
      $i18n = CRM_Core_I18n::singleton();
      $i18n->localizeTitles($menuPath);
    }
    return $menuPath;
  }

  static function getArrayForPathArgs($pathArgs) {
    if (!is_string($pathArgs)) {
      return;
    }
    $args = array();

    $elements = explode(',', $pathArgs);
    //CRM_Core_Error::debug( 'e', $elements );
    foreach ($elements as $keyVal) {
      list($key, $val) = explode('=', $keyVal);
      $arr[$key] = $val;
    }

    if (array_key_exists('urlToSession', $arr)) {
      $urlToSession = array();

      $params = explode(';', $arr['urlToSession']);
      $count = 0;
      foreach ($params as $keyVal) {
        list($urlToSession[$count]['urlVar'],
          $urlToSession[$count]['sessionVar'],
          $urlToSession[$count]['type'],
          $urlToSession[$count]['default']
        ) = explode(':', $keyVal);
        $count++;
      }
      $arr['urlToSession'] = $urlToSession;
    }
    return $arr;
  }
}

