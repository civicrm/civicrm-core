<?php
namespace Civi\Core;

use Civi\Core\Lock\LockManager;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\FileCacheReader;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\Setup;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

// TODO use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Class Container
 * @package Civi\Core
 */
class Container {

  const SELF = 'civi_container_factory';

  /**
   * @var ContainerBuilder
   */
  private static $singleton;

  /**
   * @param bool $reset whether to forcibly rebuild the entire container
   * @return \Symfony\Component\DependencyInjection\TaggedContainerInterface
   */
  public static function singleton($reset = FALSE) {
    if ($reset || self::$singleton === NULL) {
      $c = new self();
      self::$singleton = $c->createContainer();
    }
    return self::$singleton;
  }

  /**
   * @var ContainerBuilder
   * @return \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  public function createContainer() {
    $civicrm_base_path = dirname(dirname(__DIR__));
    $container = new ContainerBuilder();
    $container->setParameter('civicrm_base_path', $civicrm_base_path);
    $container->set(self::SELF, $this);

    // TODO Move configuration to an external file; define caching structure
    //    if (empty($configDirectories)) {
    //      throw new \Exception(__CLASS__ . ': Missing required properties (civicrmRoot, configDirectories)');
    //    }
    //    $locator = new FileLocator($configDirectories);
    //    $loaderResolver = new LoaderResolver(array(
    //      new YamlFileLoader($container, $locator)
    //    ));
    //    $delegatingLoader = new DelegatingLoader($loaderResolver);
    //    foreach (array('services.yml') as $file) {
    //      $yamlUserFiles = $locator->locate($file, NULL, FALSE);
    //      foreach ($yamlUserFiles as $file) {
    //        $delegatingLoader->load($file);
    //      }
    //    }

    $container->setDefinition('lockManager', new Definition(
      '\Civi\Core\Lock\LockManager',
      array()
    ))
      ->setFactoryService(self::SELF)->setFactoryMethod('createLockManager');

    $container->setDefinition('dispatcher', new Definition(
      '\Symfony\Component\EventDispatcher\EventDispatcher',
      array()
    ))
      ->setFactoryService(self::SELF)->setFactoryMethod('createEventDispatcher');

    return $container;
  }

  /**
   * @return \Symfony\Component\EventDispatcher\EventDispatcher
   */
  public function createEventDispatcher() {
    $dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
    return $dispatcher;
  }

  /**
   * @return LockManager
   */
  public function createLockManager() {
    // Ideally, downstream implementers could override any definitions in
    // the container. For now, we'll make-do with some define()s.
    $lm = new LockManager();
    $lm
      ->register('/^cache\./', defined('CIVICRM_CACHE_LOCK') ? CIVICRM_CACHE_LOCK : array('CRM_Core_Lock', 'createScopedLock'))
      ->register('/^data\./', defined('CIVICRM_DATA_LOCK') ? CIVICRM_DATA_LOCK : array('CRM_Core_Lock', 'createScopedLock'))
      ->register('/^worker\.mailing\.send\./', defined('CIVICRM_WORK_LOCK') ? CIVICRM_WORK_LOCK : array('CRM_Core_Lock', 'createCivimailLock'))
      ->register('/^worker\./', defined('CIVICRM_WORK_LOCK') ? CIVICRM_WORK_LOCK : array('CRM_Core_Lock', 'createScopedLock'));

    // Registrations may use complex resolver expressions, but (as a micro-optimization)
    // the default factory is specified as an array.

    return $lm;
  }

}
