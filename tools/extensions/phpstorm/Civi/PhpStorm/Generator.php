<?php

namespace Civi\PhpStorm;

use Civi\Api4\Provider\ActionObjectProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Generate metadata about dynamic CiviCRM services for use by PhpStorm.
 *
 * @link https://www.jetbrains.com/help/phpstorm/2021.3/ide-advanced-metadata.html
 */
class Generator {

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   * @return void
   */
  public static function generate(ContainerBuilder $container): void {
    if (defined('CIVICRM_TEST')) {
      return;
    }

    // Not 100% sure which is better. These trade-off in edge-cases of writability and readability.
    //  - '[civicrm.files]/.phpstorm.meta.php'
    //  - '[civicrm.compile]/.phpstorm.meta.php'
    //  - '[civicrm.root]/.phpstorm.meta.php'
    $file = \Civi::paths()->getPath('[civicrm.files]/.phpstorm.meta.php');

    $override = [
      '\Civi::service()' => static::findServices($container),
    ];
    $expectedArguments = [
      array_merge(['\civicrm_api4()', 0], static::getApi4Entities()),
      array_merge(['\civicrm_api4()', 1], static::getApi4Actions()),
    ];

    $data = static::renderMetadata($expectedArguments, $override);
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

  private static function getApi4Entities(): array {
    /*
     * FIXME: Getting all API entities requires the container to be built, at this stage it's not,
     * in fact the `action_object_provider` service doesn't even seem to exist at this stage,
     * as it's not included in the overrides.
     *
     * This file-scanning method doesn't include dynamic entities (e.g. from multi-record custom fields)
     * but it's better than nothing:
     */
    $provider = new ActionObjectProvider();
    $entityNames = [];
    foreach ($provider->getAllApiClasses() as $className) {
      $entityNames[] = "'" . $className::getEntityName() . "'";
    }
    natcasesort($entityNames);
    return $entityNames;
  }

  private static function getApi4Actions(): array {
    /*
     * FIXME: PHPSTORM_META doesn't seem to support compound dynamic arguments
     * so even if you give it separate lists like
     * ```
     * expectedArguments(\civicrm_api4('Contact'), 1, 'a', 'b');
     * expectedArguments(\civicrm_api4('Case'), 1, 'c', 'd');
     * ```
     * It doesn't differentiate them and always offers a,b,c,d for every entity.
     * If they ever fix that upstream we could fetch a different list of actions per entity,
     * but for now there's no point.
     */
    $hardcodedList = ['get', 'save', 'create', 'update', 'delete', 'replace', 'revert', 'export', 'autocomplete', 'getFields', 'getActions', 'checkAccess'];
    $actionNames = [];
    foreach ($hardcodedList as $actionName) {
      $actionNames[] = "'" . $actionName . "'";
    }
    return $actionNames;
  }

  private static function renderMetadata(array $expectedArguments, array $overrides): string {
    ob_start();
    try {
      printf('<' . "?php\n");
      printf("namespace PHPSTORM_META {\n\n");
      printf("exitPoint(\CRM_Utils_System::civiExit());\n\n");
      foreach ($expectedArguments as $functionArgs) {
        printf("expectedArguments(%s);\n", implode(', ', $functionArgs));
      }
      echo "\n";
      foreach ($overrides as $name => $map) {
        printf("override(%s, map(", $name);
        // PhpStorm 2022.3.1: 'Civi\\Foo' doesn't work, but 'Civi\Foo' does.
        echo str_replace('\\\\', '\\', var_export($map, 1));
        printf("));\n\n");
      }
      printf("}\n");
    }
    finally {
      return ob_get_clean();
    }
  }

}
