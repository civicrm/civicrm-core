<?php
namespace Civi\Core\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Scan the container for services tagged as 'spec_provider'.
 * Register each with the `spec_gatherer`.
 */
class SpecProviderPass implements CompilerPassInterface {

  public function process(ContainerBuilder $container) {
    $providers = $container->findTaggedServiceIds('spec_provider');
    $gatherer = $container->getDefinition('spec_gatherer');

    foreach (array_keys($providers) as $provider) {
      $gatherer->addMethodCall(
        'addSpecProvider',
        [new Reference($provider)]
      );
    }
  }

}
