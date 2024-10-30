<?php

namespace Civi\PhpStorm;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Generate metadata about dynamic CiviCRM services for use by PhpStorm.
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

    $services = $this->findServices($container);
    $caches = [];
    foreach ($services as $serviceId => $type) {
      if (preg_match('/^cache\./', $serviceId)) {
        $caches[substr($serviceId, 6)] = $type;
      }
    }

    $builder = new PhpStormMetadata('services', __CLASS__);
    $builder->addOverrideMap('\Civi::service()', $services);
    $builder->addOverrideMap('\Civi::cache()', $caches);
    $builder->write();
  }

  private function findServices(ContainerBuilder $c): array {
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

}
