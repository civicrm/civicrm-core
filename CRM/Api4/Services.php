<?php

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;

class CRM_Api4_Services {

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   */
  public static function hook_container($container) {
    $loader = new XmlFileLoader($container, new FileLocator(dirname(dirname(__DIR__))));
    $loader->load('Civi/Api4/services.xml');

    self::loadServices('Civi\Api4\Service\Spec\Provider', 'spec_provider', $container);
    self::loadServices('Civi\Api4\Event\Subscriber', 'event_subscriber', $container);

    $container->getDefinition('civi_api_kernel')->addMethodCall(
      'registerApiProvider',
      [new Reference('action_object_provider')]
    );

    // add event subscribers$container->get(
    $dispatcher = $container->getDefinition('dispatcher');
    $subscribers = $container->findTaggedServiceIds('event_subscriber');

    foreach (array_keys($subscribers) as $subscriber) {
      $dispatcher->addMethodCall(
        'addSubscriber',
        [new Reference($subscriber)]
      );
    }

    // add spec providers
    $providers = $container->findTaggedServiceIds('spec_provider');
    $gatherer = $container->getDefinition('spec_gatherer');

    foreach (array_keys($providers) as $provider) {
      $gatherer->addMethodCall(
        'addSpecProvider',
        [new Reference($provider)]
      );
    }

    if (defined('CIVICRM_UF') && CIVICRM_UF === 'UnitTests') {
      $loader->load('tests/phpunit/api/v4/services.xml');
    }
  }

  /**
   * Load all services in a given directory
   *
   * @param string $namespace
   * @param string $tag
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   */
  public static function loadServices($namespace, $tag, $container) {
    $namespace = \CRM_Utils_File::addTrailingSlash($namespace, '\\');
    $locations = array_merge([\Civi::paths()->getPath('[civicrm.root]/Civi.php')],
      array_column(\CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles(), 'filePath')
    );
    foreach ($locations as $location) {
      $path = \CRM_Utils_File::addTrailingSlash(dirname($location)) . str_replace('\\', DIRECTORY_SEPARATOR, $namespace);
      foreach (glob("$path*.php") as $file) {
        $matches = [];
        preg_match('/(\w*).php/', $file, $matches);
        $serviceName = $namespace . array_pop($matches);
        $serviceClass = new \ReflectionClass($serviceName);
        if ($serviceClass->isInstantiable()) {
          $definition = $container->register(str_replace('\\', '_', $serviceName), $serviceName);
          $definition->addTag($tag);
        }
      }
    }
  }

}
