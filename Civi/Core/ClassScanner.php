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

namespace Civi\Core;

/**
 * The ClassScanner is a helper for finding/loading classes based on their tagged interfaces.
 *
 * The implementation of scanning+caching are generally built on these assumptions:
 *
 * - Scanning the filesystem is expensive. One scan should serve many consumers.
 * - Consumers want to know about specific interfaces (`get(['interface' => 'CRM_Foo_BarInterface'])`.
 *
 * We reconcile these goals by performing a single scan and then storing an index.
 * (Indexes are stored per-interface. So `$cache->get(md5('CRM_Foo_BarInterface'))` is a list of matching classes.)
 */
class ClassScanner {

  /**
   * We cache information about classes that support each interface. Which interfaces should we track?
   */
  const CIVI_INTERFACE_REGEX = ';^(CRM_|Civi\\\);';

  /**
   * We load PHP files to find classes. Which files should we load?
   */
  const CIVI_CLASS_FILE_REGEX = '/^([A-Z][A-Za-z0-9]*)\.php$/';

  const TTL = 3 * 24 * 60 * 60;

  /**
   * @var array
   */
  private static $caches;

  /**
   * Get a list of classes which match the $criteria.
   *
   * @param array $criteria
   *   Ex: ['interface' => 'Civi\Core\HookInterface']
   * @return string[]
   *   List of matching classes.
   */
  public static function get(array $criteria): array {
    if (!isset($criteria['interface'])) {
      throw new \RuntimeException("Malformed request: ClassScanner::get() must specify an interface filter");
    }

    $cache = static::cache('index');
    $interface = $criteria['interface'];
    $interfaceId = md5($interface);

    $knownInterfaces = $cache->get('knownInterfaces');
    if ($knownInterfaces === NULL) {
      $knownInterfaces = static::buildIndex($cache);
      $cache->set('knownInterfaces', $knownInterfaces, static::TTL);
    }
    if (!in_array($interface, $knownInterfaces)) {
      return [];
    }

    $classes = $cache->get($interfaceId);
    if ($classes === NULL) {
      // Some cache backends don't guarantee the completeness of the set.
      //I suppose this one got purged early. We'll need to rebuild the whole set.
      $knownInterfaces = static::buildIndex($cache);
      $cache->set('knownInterfaces', $knownInterfaces, static::TTL);
      $classes = $cache->get($interfaceId);
    }

    return static::filterLiveClasses($classes ?: [], $criteria);
  }

  /**
   * Fill the 'index' cache with information about all available interfaces.
   *
   * Every extant interface will be stored as a separate cache-item.
   *
   * Example:
   *   assert $cache->get(md5(HookInterface::class)) == ['CRM_Foo_Bar', 'Civi\Whiz\Bang']
   *   assert $cache->get(md5(UserJob::class)) == ['CRM_Foo_Job1', 'Civi\Whiz\Job2']
   *
   * @return string[]
   *   List of PHP interfaces that were detected.
   *   Ex: ['\Civi\Core\HookInterface', '\Civi\UserJob\UserJobInterface']
   */
  private static function buildIndex(\CRM_Utils_Cache_Interface $cache): array {
    $allClasses = static::scanClasses();
    $byInterface = [];
    foreach ($allClasses as $class) {
      foreach (static::getRelevantInterfaces($class) as $interface) {
        $byInterface[$interface][] = $class;
      }
    }

    $cache->flush();
    foreach ($byInterface as $interface => $classes) {
      $cache->set(md5($interface), $classes, static::TTL);
    }

    return array_keys($byInterface);
  }

  /**
   * Build a list of Civi-related classes (including core and extensions).
   *
   * @return array
   *   Ex: ['CRM_Foo_Bar', 'Civi\Whiz\Bang']
   */
  private static function scanClasses(): array {
    $classes = static::scanCoreClasses();
    \CRM_Utils_Hook::scanClasses($classes);
    return $classes;
  }

  /**
   * Build a list of Civi-related classes (core-only).
   *
   * @return array
   *   Ex: ['CRM_Foo_Bar', 'Civi\Whiz\Bang']
   */
  private static function scanCoreClasses(): array {
    $cache = static::cache('structure');
    $cacheKey = 'ClassScanner_core';
    $classes = $cache->get($cacheKey);
    if ($classes !== NULL) {
      return $classes;
    }

    $civicrmRoot = \Civi::paths()->getPath('[civicrm.root]/');

    // Scan all core classes that might implement an interface we're looking for.
    // Excludes internal and legacy classes, upgraders, pages & other classes that don't need to be scanned.
    $classes = [];
    static::scanFolders($classes, $civicrmRoot, 'Civi/Test/ExampleData', '\\');
    // Most older CRM_ stuff doesn't implement event listeners & services so can be skipped.
    static::scanFolders($classes, $civicrmRoot, 'CRM', '_', ';(Upgrade|Utils|Exception|_DAO|_Page|_Form|_Controller|_StateMachine|_Selector|_CodeGen|_QuickForm);');
    static::scanFolders($classes, $civicrmRoot, 'Civi', '\\', ';\\\(Security|Test)\\\;');

    if (CIVICRM_UF === 'UnitTests') {
      static::scanFolders($classes, $civicrmRoot . 'tests/phpunit', 'Civi/Api4', '\\');
    }

    $cache->set($cacheKey, $classes, static::TTL);
    return $classes;
  }

  private static function filterLiveClasses(array $classes, array $criteria): array {
    return array_filter($classes, function($class) use ($criteria) {
      if (!class_exists($class)) {
        return FALSE;
      }
      $reflClass = new \ReflectionClass($class);
      return !$reflClass->isAbstract() && ($reflClass)->implementsInterface($criteria['interface']);
    });
  }

  /**
   * Does `$class` have any interfaces that we care about?
   *
   * @param string $class
   *   Concrete class that we are examining.
   *   Ex: 'Civi\Myextension\Foo'
   * @return array
   *   List of implemented interfaces that we care about.
   *   Ex: ['Civi\Core\HookInterface', 'Civi\Core\Service\AutoServiceInterface']
   * @throws \ReflectionException
   */
  private static function getRelevantInterfaces(string $class): array {
    $rawInterfaceNames = (new \ReflectionClass($class))->getInterfaceNames();
    return preg_grep(static::CIVI_INTERFACE_REGEX, $rawInterfaceNames);
  }

  /**
   * Search some $classRoot folder for a list of classes.
   *
   * Return any classes that implement a Civi-related interface, such as ExampleDataInterface
   * or HookInterface. (Specifically, interfaces matching CIVI_INTERFACE_REGEX.)
   *
   * @internal
   *   Currently reserved for use within civicrm-core. Signature may change.
   * @param string[] $classes
   *   Alterable list of extant classes.
   *   `scanFolders()` will add new classes to this list.
   * @param string $classRoot
   *   The base folder in which to search.
   *   Ex: The $civicrm_root or some extension's basedir.
   * @param string $classDir
   *   Folder to search (within the $classRoot).
   *   May use wildcards.
   *   Ex: "CRM" or "Civi"
   * @param string $classDelim
   *   Namespace separator, eg "_" (PEAR namespacing) or "\" (PHP namespacing).
   * @param string|null $excludeRegex
   *   A regular expression describing class-files that should be excluded.
   *   For example, if you have two versions of a class that are loaded in mutually-incompatible environments,
   *   then you may need to skip scanning.
   */
  public static function scanFolders(array &$classes, string $classRoot, string $classDir, string $classDelim, ?string $excludeRegex = NULL): void {
    $classRoot = \CRM_Utils_File::addTrailingSlash($classRoot, '/');

    $baseDirs = (array) glob($classRoot . $classDir);
    foreach ($baseDirs as $baseDir) {
      foreach (\CRM_Utils_File::findFiles($baseDir, '*.php') as $absFile) {
        if (!preg_match(static::CIVI_CLASS_FILE_REGEX, basename($absFile))) {
          continue;
        }
        $absFile = str_replace(DIRECTORY_SEPARATOR, '/', $absFile);
        $relFile = \CRM_Utils_File::relativize($absFile, $classRoot);
        $class = str_replace('/', $classDelim, substr($relFile, 0, -4));
        if ($excludeRegex !== NULL && preg_match($excludeRegex, $class)) {
          continue;
        }
        if (class_exists($class)) {
          $interfaces = static::getRelevantInterfaces($class);
          if ($interfaces) {
            $classes[] = $class;
          }
        }
        elseif (!interface_exists($class) && !trait_exists($class)) {
          // If you get this error, then perhaps (a) you need to fix the name of file/class/namespace or (b) you should disable class-scanning.
          // throw new \RuntimeException("Scanned file {$relFile} for class {$class}, but it was not found.");
          // We can't throw an exception since it breaks some test environments. We can't log because this happens too early and it leads to an infinite loop. error_log() works but is debatable if anyone will look there.
        }
      }
    }
  }

  /**
   * Lookup a cache-service used by ClassScanner.
   *
   * There are a couple of cache services (eg "index" and "structure") with different lifecycles.
   *
   * @param string $name
   *   - The 'index' cache describes the list of live classes that match an interface. It persists for the
   *     duration of the system-configuration (eg cleared by system-flush or enable/disable extension).
   *   - The 'structure' cache describes the class-structure within each extension. It persists for the
   *     duration of the current page-view and is essentially write-once. This minimizes extra scans during testing.
   *     (It could almost use Civi::$statics, except we want it to survive throughout testing.)
   *   - Note: Typical runtime usage should only hit the 'index' cache. The 'structure' cache should only
   *     be relevant following a system-flush.
   * @return \CRM_Utils_Cache_Interface
   * @internal
   */
  public static function cache(string $name): \CRM_Utils_Cache_Interface {
    // The class-scanner runs early (before the container is available), so we need to manage our own caches.

    if (!isset(static::$caches[$name])) {
      switch ($name) {
        case 'index':
          global $_DB_DATAOBJECT;
          if (empty($_DB_DATAOBJECT['CONFIG'])) {
            // Atypical example: You have a PHPUnit test with a @dataProvider that relies on ClassScanner. It runs before boot.
            return new \CRM_Utils_Cache_ArrayCache([]);
          }

          // The index-cache is similar to the extension-cache, except in the prefetch policy.
          // (We need the full list of extensions on every page-load, but we don't need the full list
          // of interfaces on every page-load.)
          static::$caches[$name] = \CRM_Utils_Cache::create([
            'name' => 'classes',
            'type' => ['*memory*', 'SqlGroup', 'ArrayCache'],
            'fastArray' => TRUE,
          ]);

        case 'structure':
          static::$caches[$name] = new \CRM_Utils_Cache_ArrayCache([]);
          break;

      }
    }

    return static::$caches[$name];
  }

}
