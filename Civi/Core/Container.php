<?php
namespace Civi\Core;

use Civi\Core\Event\SystemInstallEvent;
use Civi\Core\Lock\LockManager;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\FileCacheReader;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\Setup;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;

// TODO use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Class Container
 * @package Civi\Core
 */
class Container {

  const SELF = 'civi_container_factory';

  /**
   * @param bool $reset
   *   Whether to forcibly rebuild the entire container.
   * @return \Symfony\Component\DependencyInjection\TaggedContainerInterface
   */
  public static function singleton($reset = FALSE) {
    if ($reset || !isset(\Civi::$statics[__CLASS__]['container'])) {
      self::boot(TRUE);
    }
    return \Civi::$statics[__CLASS__]['container'];
  }

  /**
   * Find a cached container definition or construct a new one.
   *
   * There are many weird contexts in which Civi initializes (eg different
   * variations of multitenancy and different permutations of CMS/CRM bootstrap),
   * and hook_container may fire a bit differently in each context. To mitigate
   * risk of leaks between environments, we compute a unique envID
   * (md5(DB_NAME, HTTP_HOST, SCRIPT_FILENAME, etc)) and use separate caches for
   * each (eg "templates_c/CachedCiviContainer.$ENVID.php").
   *
   * Constants:
   *   - CIVICRM_CONTAINER_CACHE -- 'always' [default], 'never', 'auto'
   *   - CIVICRM_DSN
   *   - CIVICRM_DOMAIN_ID
   *   - CIVICRM_TEMPLATE_COMPILEDIR
   *
   * @return ContainerInterface
   */
  public function loadContainer() {
    // Note: The container's raison d'etre is to manage construction of other
    // services. Consequently, we assume a minimal service available -- the classloader
    // has been setup, and civicrm.settings.php is loaded, but nothing else works.

    $cacheMode = defined('CIVICRM_CONTAINER_CACHE') ? CIVICRM_CONTAINER_CACHE : 'always';

    // In pre-installation environments, don't bother with caching.
    if (!defined('CIVICRM_TEMPLATE_COMPILEDIR') || !defined('CIVICRM_DSN') || $cacheMode === 'never' || \CRM_Utils_System::isInUpgradeMode()) {
      return $this->createContainer();
    }

    $envId = \CRM_Core_Config_Runtime::getId();
    $file = CIVICRM_TEMPLATE_COMPILEDIR . "/CachedCiviContainer.{$envId}.php";
    $containerConfigCache = new ConfigCache($file, $cacheMode === 'auto');
    if (!$containerConfigCache->isFresh()) {
      $containerBuilder = $this->createContainer();
      $containerBuilder->compile();
      $dumper = new PhpDumper($containerBuilder);
      $containerConfigCache->write(
        $dumper->dump(array('class' => 'CachedCiviContainer')),
        $containerBuilder->getResources()
      );
    }

    require_once $file;
    $c = new \CachedCiviContainer();
    $c->set('service_container', $c);
    return $c;
  }

  /**
   * Construct a new container.
   *
   * @var ContainerBuilder
   * @return \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  public function createContainer() {
    $civicrm_base_path = dirname(dirname(__DIR__));
    $container = new ContainerBuilder();
    $container->addCompilerPass(new RegisterListenersPass('dispatcher'));
    $container->addObjectResource($this);
    $container->setParameter('civicrm_base_path', $civicrm_base_path);
    //$container->set(self::SELF, $this);
    $container->setDefinition(self::SELF, new Definition(
      'Civi\Core\Container',
      array()
    ));

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
      'Civi\Angular\Manager',
      array()
    ))
      ->setFactoryService(self::SELF)->setFactoryMethod('createAngularManager');

    $container->setDefinition('dispatcher', new Definition(
      'Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher',
      array(new Reference('service_container'))
    ))
      ->setFactoryService(self::SELF)->setFactoryMethod('createEventDispatcher');

    $container->setDefinition('magic_function_provider', new Definition(
      'Civi\API\Provider\MagicFunctionProvider',
      array()
    ));

    $container->setDefinition('civi_api_kernel', new Definition(
      'Civi\API\Kernel',
      array(new Reference('dispatcher'), new Reference('magic_function_provider'))
    ))
      ->setFactoryService(self::SELF)->setFactoryMethod('createApiKernel');

    $container->setDefinition('cxn_reg_client', new Definition(
      'Civi\Cxn\Rpc\RegistrationClient',
      array()
    ))
      ->setFactoryClass('CRM_Cxn_BAO_Cxn')->setFactoryMethod('createRegistrationClient');

    $container->setDefinition('psr_log', new Definition('CRM_Core_Error_Log', array()));

    foreach (array('js_strings', 'community_messages') as $cacheName) {
      $container->setDefinition("cache.{$cacheName}", new Definition(
        'CRM_Utils_Cache_Interface',
        array(
          array(
            'name' => $cacheName,
            'type' => array('*memory*', 'SqlGroup', 'ArrayCache'),
          ),
        )
      ))->setFactoryClass('CRM_Utils_Cache')->setFactoryMethod('create');
    }

    $container->setDefinition('pear_mail', new Definition('Mail'))
      ->setFactoryClass('CRM_Utils_Mail')->setFactoryMethod('createMailer');

    if (empty(\Civi::$statics[__CLASS__]['boot'])) {
      throw new \RuntimeException("Cannot initialize container. Boot services are undefined.");
    }
    foreach (\Civi::$statics[__CLASS__]['boot'] as $bootService => $def) {
      $container->setDefinition($bootService, new Definition($def['class'], array($bootService)))
        ->setFactoryClass(__CLASS__)
        ->setFactoryMethod('getBootService');
    }

    // Expose legacy singletons as services in the container.
    $singletons = array(
      'resources' => 'CRM_Core_Resources',
      'httpClient' => 'CRM_Utils_HttpClient',
      'cache.default' => 'CRM_Utils_Cache',
      'i18n' => 'CRM_Core_I18n',
      // Maybe? 'config' => 'CRM_Core_Config',
      // Maybe? 'smarty' => 'CRM_Core_Smarty',
    );
    foreach ($singletons as $name => $class) {
      $container->setDefinition($name, new Definition(
        $class
      ))
        ->setFactoryClass($class)->setFactoryMethod('singleton');
    }

    $container->setDefinition('civi_token_compat', new Definition(
      'Civi\Token\TokenCompatSubscriber',
      array()
    ))->addTag('kernel.event_subscriber');

    foreach (array('Activity', 'Contribute', 'Event', 'Member') as $comp) {
      $container->setDefinition("crm_" . strtolower($comp) . "_tokens", new Definition(
        "CRM_{$comp}_Tokens",
        array()
      ))->addTag('kernel.event_subscriber');
    }

    \CRM_Utils_Hook::container($container);

    return $container;
  }

  /**
   * @return \Civi\Angular\Manager
   */
  public function createAngularManager() {
    return new \Civi\Angular\Manager(\CRM_Core_Resources::singleton());
  }

  /**
   * @param ContainerInterface $container
   * @return \Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  public function createEventDispatcher($container) {
    $dispatcher = new ContainerAwareEventDispatcher($container);
    $dispatcher->addListener(SystemInstallEvent::EVENT_NAME, array('\Civi\Core\InstallationCanary', 'check'));
    $dispatcher->addListener(SystemInstallEvent::EVENT_NAME, array('\Civi\Core\DatabaseInitializer', 'initialize'));
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
    $dispatcher->addListener(\Civi\ActionSchedule\Events::MAPPINGS, array('CRM_Activity_ActionMapping', 'onRegisterActionMappings'));
    $dispatcher->addListener(\Civi\ActionSchedule\Events::MAPPINGS, array('CRM_Contact_ActionMapping', 'onRegisterActionMappings'));
    $dispatcher->addListener(\Civi\ActionSchedule\Events::MAPPINGS, array('CRM_Contribute_ActionMapping_ByPage', 'onRegisterActionMappings'));
    $dispatcher->addListener(\Civi\ActionSchedule\Events::MAPPINGS, array('CRM_Contribute_ActionMapping_ByType', 'onRegisterActionMappings'));
    $dispatcher->addListener(\Civi\ActionSchedule\Events::MAPPINGS, array('CRM_Event_ActionMapping', 'onRegisterActionMappings'));
    $dispatcher->addListener(\Civi\ActionSchedule\Events::MAPPINGS, array('CRM_Member_ActionMapping', 'onRegisterActionMappings'));

    return $dispatcher;
  }

  /**
   * @return LockManager
   */
  public static function createLockManager() {
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
      array('civicrm_activity', 'civicrm_mailing', 'civicrm_contact', 'civicrm_grant')
    ));

    $kernel->setApiProviders(array(
      $reflectionProvider,
      $magicFunctionProvider,
    ));

    return $kernel;
  }

  /**
   * Get a list of boot services.
   *
   * These are services which must be setup *before* the container can operate.
   *
   * @param bool $loadFromDB
   * @throws \CRM_Core_Exception
   */
  public static function boot($loadFromDB) {
    $bootServices = array();
    \Civi::$statics[__CLASS__]['boot'] = &$bootServices;

    $bootServices['runtime'] = array(
      'class' => 'CRM_Core_Config_Runtime',
      'obj' => ($runtime = new \CRM_Core_Config_Runtime()),
    );
    $runtime->initialize($loadFromDB);

    $bootServices['paths'] = array(
      'class' => 'Civi\Core\Paths',
      'obj' => new \Civi\Core\Paths(),
    );

    $class = $runtime->userFrameworkClass;
    $bootServices['userSystem'] = array(
      'class' => 'CRM_Utils_Cache_Interface',
      'obj' => ($userSystem = new $class()),
    );
    $userSystem->initialize();

    $userPermissionClass = 'CRM_Core_Permission_' . $runtime->userFramework;
    $bootServices['userPermissionClass'] = array(
      // Ugh, silly name.
      'class' => 'CRM_Core_Permission_Base',
      'obj' => new $userPermissionClass(),
    );

    $bootServices['cache.settings'] = array(
      'class' => 'CRM_Utils_Cache_Interface',
      'obj' => \CRM_Utils_Cache::create(array(
        'name' => 'settings',
        'type' => array('*memory*', 'SqlGroup', 'ArrayCache'),
      )),
    );

    $bootServices['settings_manager'] = array(
      'class' => 'Civi\Core\SettingsManager',
      'obj' => new \Civi\Core\SettingsManager($bootServices['cache.settings']['obj']),
    );

    $bootServices['lockManager'] = array(
      'class' => 'Civi\Core\Lock\LockManager',
      'obj' => self::createLockManager(),
    );

    if ($loadFromDB && $runtime->dsn) {
      \CRM_Core_DAO::init($runtime->dsn);
      \CRM_Utils_Hook::singleton(TRUE);
      \CRM_Extension_System::singleton(TRUE);

      $c = new self();
      \Civi::$statics[__CLASS__]['container'] = $c->loadContainer();
    }
  }

  public static function getBootService($name) {
    return \Civi::$statics[__CLASS__]['boot'][$name]['obj'];
  }

}
