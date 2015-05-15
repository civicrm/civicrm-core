<?php
namespace Civi\Core;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\FileCacheReader;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\Setup;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
   * @param bool $reset
   *   Whether to forcibly rebuild the entire container.
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

    $container->setDefinition('angular', new Definition(
      '\Civi\Angular\Manager',
      array()
    ))
      ->setFactoryService(self::SELF)->setFactoryMethod('createAngularManager');

    $container->setDefinition('dispatcher', new Definition(
      '\Symfony\Component\EventDispatcher\EventDispatcher',
      array()
    ))
      ->setFactoryService(self::SELF)->setFactoryMethod('createEventDispatcher');

    $container->setDefinition('magic_function_provider', new Definition(
      '\Civi\API\Provider\MagicFunctionProvider',
      array()
    ));

    $container->setDefinition('civi_api_kernel', new Definition(
      '\Civi\API\Kernel',
      array(new Reference('dispatcher'), new Reference('magic_function_provider'))
    ))
      ->setFactoryService(self::SELF)->setFactoryMethod('createApiKernel');

    // Expose legacy singletons as services in the container.
    $singletons = array(
      'resources' => 'CRM_Core_Resources',
      'httpClient' => 'CRM_Utils_HttpClient',
      // Maybe? 'config' => 'CRM_Core_Config',
      // Maybe? 'smarty' => 'CRM_Core_Smarty',
    );
    foreach ($singletons as $name => $class) {
      $container->setDefinition($name, new Definition(
        $class
      ))
        ->setFactoryClass($class)->setFactoryMethod('singleton');
    }

    return $container;
  }

  /**
   * @return \Civi\Angular\Manager
   */
  public function createAngularManager() {
    return new \Civi\Angular\Manager(\CRM_Core_Resources::singleton());
  }

  /**
   * @return \Symfony\Component\EventDispatcher\EventDispatcher
   */
  public function createEventDispatcher() {
    $dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
    $dispatcher->addListener('hook_civicrm_post::Activity', array('\Civi\CCase\Events', 'fireCaseChange'));
    $dispatcher->addListener('hook_civicrm_post::Case', array('\Civi\CCase\Events', 'fireCaseChange'));
    $dispatcher->addListener('hook_civicrm_caseChange', array('\Civi\CCase\Events', 'delegateToXmlListeners'));
    $dispatcher->addListener('hook_civicrm_caseChange', array('\Civi\CCase\SequenceListener', 'onCaseChange_static'));
    $dispatcher->addListener('DAO::post-insert', array('\CRM_Core_BAO_RecurringEntity', 'triggerInsert'));
    $dispatcher->addListener('DAO::post-update', array('\CRM_Core_BAO_RecurringEntity', 'triggerUpdate'));
    $dispatcher->addListener('DAO::post-delete', array('\CRM_Core_BAO_RecurringEntity', 'triggerDelete'));
    $dispatcher->addListener('hook_civicrm_unhandled_exception', array(
        'CRM_Core_LegacyErrorHandler',
        'handleException',
      ));
    return $dispatcher;
  }

  /**
   * @param \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher
   * @param $magicFunctionProvider
   *
   * @return \Civi\API\Kernel
   */
  public function createApiKernel($dispatcher, $magicFunctionProvider) {
    $dispatcher->addSubscriber(new \Civi\API\Subscriber\ChainSubscriber());
    $dispatcher->addSubscriber(new \Civi\API\Subscriber\TransactionSubscriber());
    $dispatcher->addSubscriber(new \Civi\API\Subscriber\I18nSubscriber());
    $dispatcher->addSubscriber($magicFunctionProvider);
    $dispatcher->addSubscriber(new \Civi\API\Subscriber\PermissionCheck());
    $dispatcher->addSubscriber(new \Civi\API\Subscriber\APIv3SchemaAdapter());
    $dispatcher->addSubscriber(new \Civi\API\Subscriber\WrapperAdapter(array(
      \CRM_Utils_API_HTMLInputCoder::singleton(),
      \CRM_Utils_API_NullOutputCoder::singleton(),
      \CRM_Utils_API_ReloadOption::singleton(),
      \CRM_Utils_API_MatchOption::singleton(),
    )));
    $dispatcher->addSubscriber(new \Civi\API\Subscriber\XDebugSubscriber());
    $kernel = new \Civi\API\Kernel($dispatcher);

    $reflectionProvider = new \Civi\API\Provider\ReflectionProvider($kernel);
    $dispatcher->addSubscriber($reflectionProvider);

    $dispatcher->addSubscriber(new \Civi\API\Subscriber\DynamicFKAuthorization(
      $kernel,
      'Attachment',
      array('create', 'get', 'delete'),
      // Given a file ID, determine the entity+table it's attached to.
      'SELECT if(cf.id,1,0) as is_valid, cef.entity_table, cef.entity_id
         FROM civicrm_file cf
         LEFT JOIN civicrm_entity_file cef ON cf.id = cef.file_id
         WHERE cf.id = %1',
      // Get a list of custom fields (field_name,table_name,extends)
      'SELECT concat("custom_",fld.id) as field_name,
        grp.table_name as table_name,
        grp.extends as extends
       FROM civicrm_custom_field fld
       INNER JOIN civicrm_custom_group grp ON fld.custom_group_id = grp.id
       WHERE fld.data_type = "File"
      ',
      array('civicrm_activity', 'civicrm_mailing', 'civicrm_contact')
    ));

    $kernel->setApiProviders(array(
      $reflectionProvider,
      $magicFunctionProvider,
    ));

    return $kernel;
  }

}
