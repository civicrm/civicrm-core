<?php

namespace Civi\Core\Compiler;

use Civi\Core\ClassScanner;
use Civi\Core\Service\AutoServiceInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Scan the source-tree for implementations of `AutoServiceInterface`. Load them.
 *
 * Note: This will scan the core codebase as well as active extensions. For fully automatic
 * support in an extension, the extension must enable the mixin `scan-classes@1`.
 */
class AutoServiceScannerPass implements CompilerPassInterface {

  public function process(ContainerBuilder $container) {
    $autoServices = ClassScanner::get(['interface' => AutoServiceInterface::class]);
    foreach ($autoServices as $autoService) {
      $autoService::buildContainer($container);
    }
  }

}
