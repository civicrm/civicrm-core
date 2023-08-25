<?php

namespace Civi\PhpStorm;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Generate metadata about dynamic CiviCRM services for use by PhpStorm.
 *
 * @link https://www.jetbrains.com/help/phpstorm/2021.3/ide-advanced-metadata.html
 */
class PhpStormCompilePass implements CompilerPassInterface {

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   * @return void
   */
  public function process(ContainerBuilder $container): void {
    if (defined('CIVICRM_TEST')) {
      return;
    }

    // Not 100% sure which is better. These trade-off in edge-cases of writability and readability.
    //  - '[civicrm.files]/.phpstorm.meta.php'
    //  - '[civicrm.compile]/.phpstorm.meta.php'
    //  - '[civicrm.root]/.phpstorm.meta.php'
    $file = \Civi::paths()->getPath('[civicrm.files]/.phpstorm.meta.php');

    $data = static::renderMetadata(static::findServices($container));
    file_put_contents($file, $data);
  }

  private static function findServices(ContainerBuilder $c): array {
    $aliases = $c->getAliases();
    $services = [];
    foreach ($c->getServiceIds() as $serviceId) {
      $definition = isset($aliases[$serviceId])
        ? $c->getDefinition($aliases[$serviceId]) : $c->getDefinition($serviceId);

      $class = $definition->getClass();
      if ($class) {
        $services[$serviceId] = $class;
      }
      else {
        // fprintf(STDERR, "INCOMPLETE: Service \"%s\" does not declare a type.\n", $serviceId);
        $services[$serviceId] = 'mixed';
      }
    }
    return $services;
  }

  private static function renderMetadata(array $services): string {
    ob_start();
    try {
      printf('<' . "?php\n");
      printf("namespace PHPSTORM_META {\n");
      printf("override(\Civi::service(), map(\n");
      echo str_replace('\\\\', '\\', var_export($services, 1));
      // PhpStorm 2022.3.1: 'Civi\\Foo' doesn't work, but 'Civi\Foo' does.
      printf(");\n");
      printf("}\n");
    }
    finally {
      return ob_get_clean();
    }
  }

}
