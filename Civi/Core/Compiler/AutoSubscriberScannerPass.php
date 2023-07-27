<?php

namespace Civi\Core\Compiler;

use Civi\Core\ClassScanner;
use Civi\Core\Service\AutoDefinition;
use Civi\Core\Service\AutoSubscriber;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Scan the source-tree for implementations of `AutoSubscriber` interface. Load them.
 *
 * Note: This will scan the core codebase as well as active extensions. For fully automatic
 * support in an extension, the extension must enable the mixin `scan-classes@1`.
 */
class AutoSubscriberScannerPass implements CompilerPassInterface {

  public function process(ContainerBuilder $container) {
    $autoSubscribers = ClassScanner::get(['interface' => AutoSubscriber::class]);
    foreach ($autoSubscribers as $autoSubscriber) {
      $reflection = new \ReflectionClass($autoSubscriber);
      $file = $reflection->getFileName();
      $container->addResource(new \Symfony\Component\Config\Resource\FileResource($file));
      $definition = new Definition($reflection->getName());
      $definition->setPublic(TRUE);
      AutoDefinition::applyTags($definition, $reflection, ['internal' => TRUE]);
      $container->setDefinition($reflection->getName(), $definition);
    }
  }

}
