<?php

// AUTO-GENERATED FILE -- Civix may overwrite any changes made to this file

/**
 * The ExtensionUtil class provides small stubs for accessing resources of this
 * extension.
 */
class CRM_CivicrmSearchUi_ExtensionUtil {
  const SHORT_NAME = 'civicrm_search_ui';
  const LONG_NAME = 'civicrm_search_ui';
  const CLASS_PREFIX = 'CRM_CivicrmSearchUi';

  /**
   * Translate a string using the extension's domain.
   *
   * If the extension doesn't have a specific translation
   * for the string, fallback to the default translations.
   *
   * @param string $text
   *   Canonical message text (generally en_US).
   * @param array $params
   * @return string
   *   Translated text.
   * @see ts
   */
  public static function ts($text, $params = []): string {
    if (!array_key_exists('domain', $params)) {
      $params['domain'] = [self::LONG_NAME, NULL];
    }
    return ts($text, $params);
  }

  /**
   * Get the URL of a resource file (in this extension).
   *
   * @param string|NULL $file
   *   Ex: NULL.
   *   Ex: 'css/foo.css'.
   * @return string
   *   Ex: 'http://example.org/sites/default/ext/org.example.foo'.
   *   Ex: 'http://example.org/sites/default/ext/org.example.foo/css/foo.css'.
   */
  public static function url($file = NULL): string {
    if ($file === NULL) {
      return rtrim(CRM_Core_Resources::singleton()->getUrl(self::LONG_NAME), '/');
    }
    return CRM_Core_Resources::singleton()->getUrl(self::LONG_NAME, $file);
  }

  /**
   * Get the path of a resource file (in this extension).
   *
   * @param string|NULL $file
   *   Ex: NULL.
   *   Ex: 'css/foo.css'.
   * @return string
   *   Ex: '/var/www/example.org/sites/default/ext/org.example.foo'.
   *   Ex: '/var/www/example.org/sites/default/ext/org.example.foo/css/foo.css'.
   */
  public static function path($file = NULL) {
    // return CRM_Core_Resources::singleton()->getPath(self::LONG_NAME, $file);
    return __DIR__ . ($file === NULL ? '' : (DIRECTORY_SEPARATOR . $file));
  }

  /**
   * Get the name of a class within this extension.
   *
   * @param string $suffix
   *   Ex: 'Page_HelloWorld' or 'Page\\HelloWorld'.
   * @return string
   *   Ex: 'CRM_Foo_Page_HelloWorld'.
   */
  public static function findClass($suffix) {
    return self::CLASS_PREFIX . '_' . str_replace('\\', '_', $suffix);
  }

  /**
   * @return \CiviMix\Schema\SchemaHelperInterface
   */
  public static function schema() {
    if (!isset($GLOBALS['CiviMixSchema'])) {
      pathload()->loadPackage('civimix-schema@5', TRUE);
    }
    return $GLOBALS['CiviMixSchema']->getHelper(static::LONG_NAME);
  }

}

use CRM_CivicrmSearchUi_ExtensionUtil as E;

spl_autoload_register('_civicrm_search_ui_civix_class_loader', TRUE, TRUE);

function _civicrm_search_ui_civix_class_loader($class) {
  if ($class === 'CRM_CivicrmSearchUi_DAO_Base') {
    if (version_compare(CRM_Utils_System::version(), '5.74.beta', '>=')) {
      class_alias('CRM_Core_DAO_Base', 'CRM_CivicrmSearchUi_DAO_Base');
      // ^^ Materialize concrete names -- encourage IDE's to pick up on this association.
    }
    else {
      $realClass = 'CiviMix\\Schema\\CivicrmSearchUi\\DAO';
      class_alias($realClass, $class);
      // ^^ Abstract names -- discourage IDE's from picking up on this association.
    }
    return;
  }

  // This allows us to tap-in to the installation process (without incurring real file-reads on typical requests).
  if (strpos($class, 'CiviMix\\Schema\\CivicrmSearchUi\\') === 0) {
    // civimix-schema@5 is designed for backported use in download/activation workflows,
    // where new revisions may become dynamically available.
    pathload()->loadPackage('civimix-schema@5', TRUE);
    CiviMix\Schema\loadClass($class);
  }
}

/**
 * (Delegated) Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config
 */
function _civicrm_search_ui_civix_civicrm_config($config = NULL) {
  static $configured = FALSE;
  if ($configured) {
    return;
  }
  $configured = TRUE;

  $extRoot = __DIR__ . DIRECTORY_SEPARATOR;
  $include_path = $extRoot . PATH_SEPARATOR . get_include_path();
  set_include_path($include_path);
  // Based on <compatibility>, this does not currently require mixin/polyfill.php.
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function _civicrm_search_ui_civix_civicrm_install() {
  _civicrm_search_ui_civix_civicrm_config();
  // Based on <compatibility>, this does not currently require mixin/polyfill.php.
}

/**
 * (Delegated) Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function _civicrm_search_ui_civix_civicrm_enable(): void {
  _civicrm_search_ui_civix_civicrm_config();
  // Based on <compatibility>, this does not currently require mixin/polyfill.php.
}

/**
 * Inserts a navigation menu item at a given place in the hierarchy.
 *
 * @param array $menu - menu hierarchy
 * @param string $path - path to parent of this item, e.g. 'my_extension/submenu'
 *    'Mailing', or 'Administer/System Settings'
 * @param array $item - the item to insert (parent/child attributes will be
 *    filled for you)
 *
 * @return bool
 */
function _civicrm_search_ui_civix_insert_navigation_menu(&$menu, $path, $item) {
  // If we are done going down the path, insert menu
  if (empty($path)) {
    $menu[] = [
      'attributes' => array_merge([
        'label' => $item['name'] ?? NULL,
        'active' => 1,
      ], $item),
    ];
    return TRUE;
  }
  else {
    // Find an recurse into the next level down
    $found = FALSE;
    $path = explode('/', $path);
    $first = array_shift($path);
    foreach ($menu as $key => &$entry) {
      if ($entry['attributes']['name'] == $first) {
        if (!isset($entry['child'])) {
          $entry['child'] = [];
        }
        $found = _civicrm_search_ui_civix_insert_navigation_menu($entry['child'], implode('/', $path), $item);
      }
    }
    return $found;
  }
}

/**
 * (Delegated) Implements hook_civicrm_navigationMenu().
 */
function _civicrm_search_ui_civix_navigationMenu(&$nodes) {
  if (!is_callable(['CRM_Core_BAO_Navigation', 'fixNavigationMenu'])) {
    _civicrm_search_ui_civix_fixNavigationMenu($nodes);
  }
}

/**
 * Given a navigation menu, generate navIDs for any items which are
 * missing them.
 */
function _civicrm_search_ui_civix_fixNavigationMenu(&$nodes) {
  $maxNavID = 1;
  array_walk_recursive($nodes, function($item, $key) use (&$maxNavID) {
    if ($key === 'navID') {
      $maxNavID = max($maxNavID, $item);
    }
  });
  _civicrm_search_ui_civix_fixNavigationMenuItems($nodes, $maxNavID, NULL);
}

function _civicrm_search_ui_civix_fixNavigationMenuItems(&$nodes, &$maxNavID, $parentID) {
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
      _civicrm_search_ui_civix_fixNavigationMenuItems($nodes[$origKey]['child'], $maxNavID, $nodes[$origKey]['attributes']['navID']);
    }
  }
}
