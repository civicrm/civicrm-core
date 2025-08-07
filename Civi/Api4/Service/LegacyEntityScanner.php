<?php

namespace Civi\Api4\Service;

use Civi\Core\Service\AutoSubscriber;
use Civi\Core\Event\GenericHookEvent;
use Civi\Api4\Generic\EntityInterface;
use CRM_Extension_Info;

/**
 * This provides transitional support for extensions that provide Api4 Entities without
 * having the scan-classes mixin
 *
 * It could share a findClasses function with LegacySpecGatherer, except that seems
 * to do something with the container along the way
 *
 * @see LegacySpecGatherer
 */
class LegacyEntityScanner extends AutoSubscriber {

  public static function getSubscribedEvents(): array {
    return [
      'civi.api4.entityTypes' => 'addEntities',
    ];
  }

  public function addEntities(GenericHookEvent $e): void {
    foreach (self::getEntitiesFromClasses() as $info) {
      if (!isset($e->entities[$info['name']])) {
        $e->entities[$info['name']] = $info;
      }
    }
  }

  protected static function getEntitiesFromClasses(): array {
    $infos = [];

    $classNames = static::findClasses('Civi\Api4');
    foreach ($classNames as $className) {
      if (!class_exists($className)) {
        continue;
      }
      $class = new \ReflectionClass($className);
      if (!$class->implementsInterface(EntityInterface::class)) {
        // not an Api4 entity
        continue;
      }
      $infos[] = $className::getInfo();
    }

    return $infos;
  }

  /**
   * Scan all enabled extensions for files in a certain namespace.
   *
   * Note: respects dispatch policy for hook_civicrm_scanClasses, for consistency
   *
   * @param string $namespace
   * @return array
   */
  protected static function findClasses($namespace): array {
    // check for a dispatch policy - if in place then only run if hook
    // scanClasses is enabled, for consistency with AutoService SpecProviders
    if (\Civi::dispatcher()->getDispatchPolicy()) {
      $scanClassPolicy = \Civi::dispatcher()->checkDispatchPolicy('hook_civicrm_scanClasses');
      if ($scanClassPolicy !== 'run') {
        return [];
      }
    }

    $classes = [];

    $namespace = \CRM_Utils_File::addTrailingSlash($namespace, '\\');

    // get file paths to extensions WITHOUT scan classes
    $locations = self::getExtensionFoldersToScan();

    foreach ($locations as $location) {
      $path = \CRM_Utils_File::addTrailingSlash(dirname($location ?? '')) . str_replace('\\', DIRECTORY_SEPARATOR, $namespace);
      foreach (glob("$path*.php") as $file) {
        $matches = [];
        preg_match('/(\w*)\.php$/', $file, $matches);
        $classes[] = $namespace . array_pop($matches);
      }
    }
    return $classes;
  }

  /**
   * Get file paths for extensions WITHOUT scan classes
   * @return array
   */
  protected static function getExtensionFoldersToScan(): array {
    // get file paths to extensions WITHOUT scan classes
    $locations = [];

    $mapper = \CRM_Extension_System::singleton()->getMapper();
    $active = $mapper->getActiveModuleFiles();
    $infos = $mapper->getAllInfos();

    foreach ($active as $ext) {
      $info = $infos[$ext['fullName']];
      if (!self::hasScanClasses($info)) {
        $locations[] = $ext['filePath'];
      }
    }
    return $locations;
  }

  private static function hasScanClasses(CRM_Extension_Info $info): bool {
    foreach ($info->mixins as $mixin) {
      if (\str_starts_with($mixin, 'scan-classes@')) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
