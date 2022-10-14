<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */
namespace Civi\Core\Service;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * By combining AutoServiceInterface and AutoServiceTrait, you can make any class
 * behave like an AutoService (auto-registered in the CiviCRM container).
 */
trait AutoServiceTrait {

  /**
   * Register the service in the container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   * @internal
   */
  final public static function buildContainer(ContainerBuilder $container): void {
    // "final": AutoServices should avoid coupling to Symfony DI. However, if you really
    // need to customize this, then omit AutoServiceTrait and write your own variant.

    $file = (new \ReflectionClass(static::class))->getFileName();
    $container->addResource(new \Symfony\Component\Config\Resource\FileResource($file));
    foreach (AutoDefinition::scan(static::class) as $id => $definition) {
      $container->setDefinition($id, $definition);
    }
  }

  /**
   * (Internal) Utility method used to `@inject` data into private properties.
   *
   * @param string $key
   * @param mixed $value
   * @internal
   */
  final public function injectPrivateProperty(string $key, $value): void {
    // "final": There is no need to override. If you want a custom assignment logic, then put `@inject` on your setter method.

    $this->{$key} = $value;
  }

}
