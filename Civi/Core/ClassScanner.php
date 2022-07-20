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
 * - Scanning the filesystem can be expensive. One scan should serve many consumers.
 * - Consumers want to know about specific interfaces (`get(['interface' => 'CRM_Foo_BarInterface'])`.
 *
 * We reconcile these goals by performing a single scan and then storing separate cache-items for each
 * known interface (eg `$cache->get(md5('CRM_Foo_BarInterface'))`).
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
   *
   * @return string[]
   *   List of PHP interfaces that were detected
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
   * @return array
   *   Ex: ['CRM_Foo_Bar', 'Civi\Whiz\Bang']
   */
  private static function scanClasses(): array {
    $classes = static::scanCoreClasses();
    \CRM_Utils_Hook::scanClasses($classes);
    return $classes;
  }

  /**
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

    // TODO: Consider expanding this search.
    $classes = [];
    static::scanFolders($classes, $civicrmRoot, 'Civi/Test/ExampleData', '\\');
    static::scanFolders($classes, $civicrmRoot, 'CRM/*/WorkflowMessage', '_');
    static::scanFolders($classes, $civicrmRoot, 'Civi/*/WorkflowMessage', '\\');
    static::scanFolders($classes, $civicrmRoot, 'Civi/WorkflowMessage', '\\');
    static::scanFolders($classes, $civicrmRoot, 'CRM/*/Import', '_');
    if (\CRM_Utils_Constant::value('CIVICRM_UF') === 'UnitTests') {
      static::scanFolders($classes, $civicrmRoot . 'tests/phpunit', 'CRM/*/WorkflowMessage', '_');
      static::scanFolders($classes, $civicrmRoot . 'tests/phpunit', 'Civi/*/WorkflowMessage', '\\');
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

  private static function getRelevantInterfaces(string $class): array {
    $rawInterfaceNames = (new \ReflectionClass($class))->getInterfaceNames();
    return preg_grep(static::CIVI_INTERFACE_REGEX, $rawInterfaceNames);
  }

  /**
   * Search some $classRoot folder for a list of classes.
   *
   * Return any classes that implement a Civi-related interface, such as ExampleDataInterface
   * or HookInterface. (Specifically, interfaces matchinv CIVI_INTERFACE_REGEX.)
   *
   * @internal
   *   Currently reserved for use within civicrm-core. Signature may change.
   * @param string[] $classes
   *   List of known/found classes.
   * @param string $classRoot
   *   The base folder in which to search.
   *   Ex: The $civicrm_root or some extension's basedir.
   * @param string $classDir
   *   Folder to search (within the $classRoot).
   *   May use wildcards.
   *   Ex: "CRM" or "Civi"
   * @param string $classDelim
   *   Namespace separator, eg underscore or backslash.
   */
  public static function scanFolders(array &$classes, string $classRoot, string $classDir, string $classDelim): void {
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
        if (class_exists($class)) {
          $interfaces = static::getRelevantInterfaces($class);
          if ($interfaces) {
            $classes[] = $class;
          }
        }
      }
    }
  }

  /**
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
    // Class-scanner runs before container is available. Manage our own cache. (Similar to extension-cache.)
    // However, unlike extension-cache, we do not want to prefetch all interface lists on all pageloads.

    if (!isset(static::$caches[$name])) {
      switch ($name) {
        case 'index':
          if (empty($_DB_DATAOBJECT['CONFIG'])) {
            // Atypical example: You have a test with a @dataProvider that relies on ClassScanner. Runs before bot.
            return new \CRM_Utils_Cache_ArrayCache([]);
          }
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
