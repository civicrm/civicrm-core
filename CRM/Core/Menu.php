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
 * This file contains the various menus of the CiviCRM module
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Class CRM_Core_Menu.
 */
class CRM_Core_Menu {

  /**
   * The list of menu items.
   *
   * @var array
   */
  public static $_items = NULL;

  /**
   * The list of permissioned menu items.
   *
   * @var array
   */
  public static $_permissionedItems = NULL;

  public static $_serializedElements = [
    'access_arguments',
    'access_callback',
    'page_arguments',
    'page_callback',
    'breadcrumb',
  ];

  public static $_menuCache = NULL;
  const MENU_ITEM = 1;

  /**
   * This function fetches the menu items from xml and xmlMenu hooks.
   *
   * @param bool $fetchFromXML
   *   Fetch the menu items from xml and not from cache.
   *
   * @return array
   */
  public static function &xmlItems($fetchFromXML = FALSE) {
    if (!self::$_items || $fetchFromXML) {
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

      self::$_items = [];
      foreach ($files as $file) {
        self::read($file, self::$_items);
      }

      CRM_Utils_Hook::alterMenu(self::$_items);
    }

    return self::$_items;
  }

  /**
   * Read menu.
   *
   * @param string $name
   *   File name
   * @param array $menu
   *   An alterable list of menu items.
   *
   * @throws Exception
   */
  public static function read($name, &$menu) {
    $xml = simplexml_load_string(file_get_contents($name));
    self::readXML($xml, $menu);
  }

  /**
   * @param SimpleXMLElement $xml
   *   An XML document defining a list of menu items.
   * @param array $menu
   *   An alterable list of menu items.
   *
   * @throws CRM_Core_Exception
   */
  public static function readXML($xml, &$menu) {
    $config = CRM_Core_Config::singleton();
    foreach ($xml->item as $item) {
      if (!(string ) $item->path) {
        CRM_Core_Error::debug('i', $item);
        throw new CRM_Core_Exception('Unable to read XML file');
      }
      $path = (string ) $item->path;
      $menu[$path] = [];
      unset($item->path);

      if ($item->ids_arguments) {
        $ids = [];
        foreach (['json' => 'json', 'html' => 'html', 'exception' => 'exceptions'] as $tag => $attr) {
          $ids[$attr] = [];
          foreach ($item->ids_arguments->{$tag} as $value) {
            $ids[$attr][] = (string) $value;
          }
        }
        $menu[$path]['ids_arguments'] = $ids;
        unset($item->ids_arguments);
      }

      foreach ($item as $key => $value) {
        $key = (string ) $key;
        $value = (string ) $value;
        if (strpos($key, '_callback') &&
          strpos($value, '::')
        ) {
          // FIXME Remove the rewrite at this level. Instead, change downstream call_user_func*($value)
          // to call_user_func*(Civi\Core\Resolver::singleton()->get($value)).
          $value = explode('::', $value);
        }
        elseif ($key == 'access_arguments') {
          // FIXME Move the permission parser to its own class (or *maybe* CRM_Core_Permission).
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
            $items = [];
            foreach ($elements as $element) {
              $items[] = $element;
            }
            $value = [$items, $op];
          }
          else {
            $value = [[$value], 'and'];
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
   * This function defines information for various menu items.
   *
   * @param bool $fetchFromXML
   *   Fetch the menu items from xml and not from cache.
   *
   * @return array
   */
  public static function &items($fetchFromXML = FALSE) {
    return self::xmlItems($fetchFromXML);
  }

  /**
   * Is array true (whatever that means!).
   *
   * @param array $values
   *
   * @return bool
   */
  public static function isArrayTrue($values) {
    foreach ($values as $value) {
      if (!$value) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Fill menu values.
   *
   * @param array $menu
   * @param string $path
   *
   * @throws CRM_Core_Exception
   */
  public static function fillMenuValues(&$menu, $path) {
    $fieldsToPropagate = [
      'access_callback',
      'access_arguments',
      'page_callback',
      'page_arguments',
      'is_ssl',
    ];
    $fieldsPresent = [];
    foreach ($fieldsToPropagate as $field) {
      $fieldsPresent[$field] = isset($menu[$path][$field]);
    }

    $args = explode('/', $path);
    while (!self::isArrayTrue($fieldsPresent) && !empty($args)) {

      array_pop($args);
      $parentPath = implode('/', $args);

      foreach ($fieldsToPropagate as $field) {
        if (!$fieldsPresent[$field]) {
          $fieldInParentMenu = $menu[$parentPath][$field] ?? NULL;
          if ($fieldInParentMenu !== NULL) {
            $fieldsPresent[$field] = TRUE;
            $menu[$path][$field] = $fieldInParentMenu;
          }
        }
      }
    }

    if (self::isArrayTrue($fieldsPresent)) {
      return;
    }

    $messages = [];
    foreach ($fieldsToPropagate as $field) {
      if (!$fieldsPresent[$field]) {
        $messages[] = ts("Could not find %1 in path tree",
          [1 => $field]
        );
      }
    }
    throw new CRM_Core_Exception("'$path': " . implode(', ', $messages));
  }

  /**
   * We use this function to.
   *
   * 1. Compute the breadcrumb
   * 2. Compute local tasks value if any
   * 3. Propagate access argument, access callback, page callback to the menu item
   * 4. Build the global navigation block
   *
   * @param array $menu
   */
  public static function build(&$menu) {
    foreach ($menu as $path => $menuItems) {
      try {
        self::buildBreadcrumb($menu, $path);
        self::fillMenuValues($menu, $path);
        self::fillComponentIds($menu, $path);
        self::buildReturnUrl($menu, $path);

        // add add page_type if not present
        if (!isset($menu[$path]['page_type'])) {
          $menu[$path]['page_type'] = 0;
        }
      }
      catch (CRM_Core_Exception $e) {
        Civi::log()->error('Menu path skipped:' . $e->getMessage());
      }
    }

    self::buildAdminLinks($menu);
  }

  /**
   * Determine whether a route should canonically use a frontend or backend UI.
   *
   * @param string $path
   *   Ex: 'civicrm/contribute/transact'
   * @return bool
   *   TRUE if the route is marked with 'is_public=1'.
   * @internal
   *   We may wish to revise the metadata to allow more distinctions. In that case, `isPublicRoute()`
   *   would probably get replaced by something else.
   */
  public static function isPublicRoute(string $path): bool {
    // A page-view may include hundreds of links - so don't hit DB for every link. Use cache.
    // In default+demo builds, the list of public routes is much smaller than the list of
    // private routes (roughly 1:10; ~50 entries vs ~450 entries). Cache the smaller list.
    $cache = Civi::cache('long');
    $index = $cache->get('PublicRouteIndex');
    if ($index === NULL) {
      $routes = CRM_Core_DAO::executeQuery('SELECT id, path FROM civicrm_menu WHERE is_public = 1')
        ->fetchMap('id', 'path');
      if (empty($routes)) {
        Civi::log()->warning('isPublicRoute() should not be called before the menu has been built.');
        return FALSE;
      }
      $index = array_fill_keys(array_values($routes), TRUE);
      $cache->set('PublicRouteIndex', $index);
    }

    $parts = explode('/', $path);
    while (count($parts) > 1) {
      if (isset($index[implode('/', $parts)])) {
        return TRUE;
      }
      array_pop($parts);
    }
    return FALSE;
  }

  /**
   * This function recomputes menu from xml and populates civicrm_menu.
   *
   * @param bool $truncate
   */
  public static function store($truncate = TRUE) {
    // first clean up the db
    if ($truncate) {
      $query = 'TRUNCATE civicrm_menu';
      CRM_Core_DAO::executeQuery($query);
    }
    Civi::cache('long')->delete('PublicRouteIndex');
    $menuArray = self::items($truncate);

    self::build($menuArray);

    $daoFields = CRM_Core_DAO_Menu::fields();

    foreach ($menuArray as $path => $item) {
      $menu = new CRM_Core_DAO_Menu();
      $menu->path = $path;
      $menu->domain_id = CRM_Core_Config::domainID();

      $menu->find(TRUE);

      if (!CRM_Core_Config::isUpgradeMode() ||
        CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_menu', 'module_data', FALSE)
      ) {
        // Move unrecognized fields to $module_data.
        $module_data = [];
        foreach (array_keys($item) as $key) {
          if (!isset($daoFields[$key])) {
            $module_data[$key] = $item[$key];
            unset($item[$key]);
          }
        }

        $menu->module_data = serialize($module_data);
      }

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

  /**
   * Build admin links.
   *
   * @param array $menu
   */
  public static function buildAdminLinks(&$menu) {
    $values = [];

    foreach ($menu as $path => $item) {
      if (empty($item['adminGroup'])) {
        continue;
      }

      $query = !empty($item['path_arguments']) ? str_replace(',', '&', $item['path_arguments']) . '&reset=1' : 'reset=1';

      $value = [
        'title' => $item['title'],
        'desc' => $item['desc'] ?? NULL,
        'id' => strtr($item['title'], [
          '(' => '_',
          ')' => '',
          ' ' => '',
          ',' => '_',
          '/' => '_',
        ]),
        'url' => CRM_Utils_System::url($path, $query,
          FALSE,
          NULL,
          TRUE,
          FALSE,
          // forceBackend; CRM-14439 work-around; acceptable for now because we don't display breadcrumbs on frontend
          TRUE
        ),
        'icon' => $item['icon'] ?? NULL,
        'extra' => $item['extra'] ?? NULL,
      ];
      if (!array_key_exists($item['adminGroup'], $values)) {
        $values[$item['adminGroup']] = [];
        $values[$item['adminGroup']]['fields'] = [];
      }
      $values[$item['adminGroup']]['fields']["{weight}.{$item['title']}"] = $value;
      $values[$item['adminGroup']]['component_id'] = $item['component_id'];
    }

    foreach ($values as $group => $dontCare) {
      ksort($values[$group]);
    }

    $menu['admin'] = ['breadcrumb' => $values];
  }

  /**
   * Get admin links.
   *
   * @return array|null
   */
  public static function getAdminLinks() {
    $links = self::get('admin');
    return $links['breadcrumb'] ?? NULL;
  }

  /**
   * Get the breadcrumb for a given path.
   *
   * @param array $menu
   *   An array of all the menu items.
   * @param string $path
   *   Path for which breadcrumb is to be build.
   *
   * @return array
   *   The breadcrumb for this path
   */
  public static function buildBreadcrumb(&$menu, $path) {
    $crumbs = [];

    $pathElements = explode('/', $path);
    array_pop($pathElements);

    $currentPath = NULL;
    while ($newPath = array_shift($pathElements)) {
      $currentPath = $currentPath ? ($currentPath . '/' . $newPath) : $newPath;

      // when we come across breadcrumb which involves ids,
      // we should skip now and later on append dynamically.
      if (isset($menu[$currentPath]['skipBreadcrumb'])) {
        continue;
      }

      // add to crumb, if current-path exists in params.
      if (array_key_exists($currentPath, $menu) &&
        isset($menu[$currentPath]['title'])
      ) {
        $urlVar = !empty($menu[$currentPath]['path_arguments']) ? '&' . $menu[$currentPath]['path_arguments'] : '';
        $crumbs[] = [
          'title' => $menu[$currentPath]['title'],
          'url' => CRM_Utils_System::url($currentPath,
            'reset=1' . $urlVar,
            // absolute
            FALSE,
            // fragment
            NULL,
            // htmlize
            TRUE,
            // frontend
            FALSE,
            // forceBackend; CRM-14439 work-around; acceptable for now because we don't display breadcrumbs on frontend
            TRUE
          ),
        ];
      }
    }
    $menu[$path]['breadcrumb'] = $crumbs;

    return $crumbs;
  }

  /**
   * @param array $menu
   * @param string|int $path
   */
  public static function buildReturnUrl(&$menu, $path) {
    if (!isset($menu[$path]['return_url'])) {
      [$menu[$path]['return_url'], $menu[$path]['return_url_args']] = self::getReturnUrl($menu, $path);
    }
  }

  /**
   * @param $menu
   * @param $path
   *
   * @return array
   */
  public static function getReturnUrl(&$menu, $path) {
    if (!isset($menu[$path]['return_url'])) {
      $pathElements = explode('/', $path);
      array_pop($pathElements);

      if (empty($pathElements)) {
        return [NULL, NULL];
      }
      $newPath = implode('/', $pathElements);

      return self::getReturnUrl($menu, $newPath);
    }
    else {
      return [
        $menu[$path]['return_url'] ?? NULL,
        $menu[$path]['return_url_args'] ?? NULL,
      ];
    }
  }

  /**
   * @param $menu
   * @param $path
   *
   * @throws \CRM_Core_Exception
   */
  public static function fillComponentIds(&$menu, $path) {
    static $cache = [];

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
      if (!empty($menu[$compPath]['component'])) {
        $componentId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Component',
          $menu[$compPath]['component'],
          'id', 'name'
        );
      }
      $menu[$path]['component_id'] = $componentId ?: NULL;
      $cache[$compPath] = $menu[$path]['component_id'];
    }
  }

  /**
   * @param string $path
   *   Path of menu item to retrieve.
   *
   * @return array
   *   Menu entry array.
   */
  public static function get($path) {
    $args = explode('/', $path);

    $elements = [];
    while (!empty($args)) {
      $string = implode('/', $args);
      $string = CRM_Core_DAO::escapeString($string);
      $elements[] = "'{$string}'";
      array_pop($args);
    }

    $queryString = implode(', ', $elements);
    $domainID = CRM_Core_Config::domainID();

    $query = "
(
  SELECT *
  FROM     civicrm_menu
  WHERE    path in ( $queryString )
           AND domain_id = $domainID
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
         AND domain_id = $domainID
)
";
    }

    $menu = new CRM_Core_DAO_Menu();
    $menu->query($query);

    self::$_menuCache = [];
    $menuPath = NULL;
    while ($menu->fetch()) {
      self::$_menuCache[$menu->path] = [];
      CRM_Core_DAO::storeValues($menu, self::$_menuCache[$menu->path]);

      // Move module_data into main item.
      if (isset(self::$_menuCache[$menu->path]['module_data'])) {
        CRM_Utils_Array::extend(self::$_menuCache[$menu->path],
          CRM_Utils_String::unserialize(self::$_menuCache[$menu->path]['module_data']));
        unset(self::$_menuCache[$menu->path]['module_data']);
      }

      // Unserialize other elements.
      foreach (self::$_serializedElements as $element) {
        self::$_menuCache[$menu->path][$element] = CRM_Utils_String::unserialize($menu->$element);

        if (strpos($path, $menu->path) !== FALSE) {
          $menuPath = &self::$_menuCache[$menu->path];
        }
      }
    }

    if (str_contains($path, 'report/instance')) {
      $args = explode('/', $path);
      if (is_numeric(end($args))) {
        $menuPath['path'] .= '/' . end($args);
      }
    }

    if (preg_match('/^civicrm\/(upgrade\/)?queue\//', $path)) {
      CRM_Queue_Menu::alter($path, $menuPath);
    }

    if (!empty($menuPath)) {
      $i18n = CRM_Core_I18n::singleton();
      $i18n->localizeTitles($menuPath);
    }
    return $menuPath;
  }

  /**
   * @param $pathArgs
   *
   * @return mixed
   */
  public static function getArrayForPathArgs($pathArgs) {
    if (!is_string($pathArgs)) {
      return;
    }
    $arr = [];

    $elements = explode(',', $pathArgs);
    foreach ($elements as $keyVal) {
      [$key, $val] = explode('=', $keyVal, 2);
      $arr[$key] = $val;
    }

    if (array_key_exists('urlToSession', $arr)) {
      $urlToSession = [];

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
