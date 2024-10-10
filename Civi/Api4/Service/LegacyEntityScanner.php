<?php

namespace Civi\Api4\Service;

use Civi\Core\Service\AutoSubscriber;
use Civi\Core\Event\GenericHookEvent;
use Civi\Api4\Generic\EntityInterface;

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
      'civi.api4.entityTypes' => 'getEntitiesFromClasses',
    ];
  }

  public function getEntitiesFromClasses(GenericHookEvent $e): void {
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
      $info = $className::getInfo();
      if (!isset($e->entities[$info['name']])) {
        $e->entities[$info['name']] = $info;
      }
    }
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

    // can we exclude extensions with scan classes enabled?
    $locations = array_column(\CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles(), 'filePath');

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

}
