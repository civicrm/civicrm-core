<?php

namespace Civi\Oembed;

use Drupal\Core\DrupalKernel;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * We want to set the cookie-name so that `oembed.php` doesn't try to use or change cookies
 * issued by `index.php`. This will be... fun...
 */
class OembedDrupalKernel extends DrupalKernel {

  protected function getContainerCacheKey() {
    return parent::getContainerCacheKey() . ':oe1';
  }

  protected function getContainerBuilder() {
    $container = parent::getContainerBuilder();
    $container->addCompilerPass(new class implements CompilerPassInterface {

      public function process(ContainerBuilder $container) {
        $container->findDefinition('session_configuration')
          ->setClass(OembedSessionConfiguration::class);
      }

    }, PassConfig::TYPE_BEFORE_REMOVING);
    return $container;
  }

}
