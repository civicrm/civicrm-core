<?php

namespace Civi\Api4\Service;

use Civi\Core\Service\AutoDefinition;
use Civi\Core\Service\AutoServiceInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This provides transitional support for extensions that implemented 'SpecProviderInterface'.
 * Compare these contracts:
 *
 * - For v5.19-v5.53, an extension could create a class in '$EXT/Civi/Api4/Service/Spec/Provider'
 *   which implements 'SpecProviderInterface'. This would be auto-registered as a Symfony service
 *   and tagged with 'spec_provider'.
 * - For v5.54+, an extension can enable `scan-classes@1`. Any classes in `$EXT/Civi` or `$EXT/CRM`
 *   will be scanned and registered, provided that they implement AutoServiceInterface and
 *   enable `scan-classes@1`.
 *
 * The 5.54+ scanner supports more interfaces and more options. However, it won't necessarily detect
 * spec-providers from 5.19-5.53 (because they don't have `scan-classes@1` and they don't
 * implement `AutoServiceInterface`).
 */
class LegacySpecScanner implements AutoServiceInterface {

  public static function buildContainer(ContainerBuilder $container): void {
    $classNames = static::findClasses('Civi\Api4\Service\Spec\Provider', $container);
    foreach ($classNames as $className) {
      if (!class_exists($className)) {
        continue;
      }
      $class = new \ReflectionClass($className);
      if ($class->implementsInterface(AutoServiceInterface::class)) {
        // This is already handled by the main scanner.
        continue;
      }
      $container->addResource(new \Symfony\Component\Config\Resource\FileResource($class->getFileName()));
      $name = $class->getName(); /* str_replace('\\', '_', $class->getName()); */
      $definition = AutoDefinition::create($className)->addTag('internal');
      $container->setDefinition($name, $definition);
    }
  }

  /**
   * Scan all enabled extensions for files in a certain namespace.
   *
   * Q: this seems to be adding resources to the container as it goes, rather
   * than just finding them as per function name? Otherwise could share an implementation
   * with LegacyEntityScanner
   *
   * Note: respects dispatch policy for hook_civicrm_scanClasses, for consistency
   *
   * @param string $namespace
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   * @return array
   */
  protected static function findClasses($namespace, $container): array {
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
    $locations = array_merge([\Civi::paths()->getPath('[civicrm.root]/Civi.php')],
      array_column(\CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles(), 'filePath')
    );
    foreach ($locations as $location) {
      $path = \CRM_Utils_File::addTrailingSlash(dirname($location ?? '')) . str_replace('\\', DIRECTORY_SEPARATOR, $namespace);
      if (!file_exists($path) || !is_dir($path)) {
        $resource = new \Symfony\Component\Config\Resource\FileExistenceResource($path);
        $container->addResource($resource);
      }
      else {
        $resource = new \Symfony\Component\Config\Resource\DirectoryResource($path, ';\.php$;');
        $container->addResource($resource);
        foreach (glob("$path*.php") as $file) {
          $matches = [];
          preg_match('/(\w*)\.php$/', $file, $matches);
          $classes[] = $namespace . array_pop($matches);
        }
      }
    }

    return $classes;
  }

}
