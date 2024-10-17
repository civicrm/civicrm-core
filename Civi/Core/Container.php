<?php
namespace Civi\Core;

use Civi\Core\Compiler\AutoServiceScannerPass;
use Civi\Core\Compiler\EventScannerPass;
use Civi\Core\Compiler\SpecProviderPass;
use Civi\Core\Event\EventScanner;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Lock\LockManager;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Reference;
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
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   */
  public function loadContainer() {
    // Note: The container's raison d'etre is to manage construction of other
    // services. Consequently, we assume a minimal service available -- the classloader
    // has been setup, and civicrm.settings.php is loaded, but nothing else works.

    $cacheMode = defined('CIVICRM_CONTAINER_CACHE') ? CIVICRM_CONTAINER_CACHE : 'auto';

    // In pre-installation environments, don't bother with caching.
    if (!defined('CIVICRM_DSN') || defined('CIVICRM_TEST') || CIVICRM_UF === 'UnitTests' || $cacheMode === 'never' || \CRM_Utils_System::isInUpgradeMode()) {
      $containerBuilder = $this->createContainer();
      $containerBuilder->compile();
      return $containerBuilder;
    }

    $envId = md5(implode(',', array_merge(
      [\CRM_Core_Config_Runtime::getId()],
      array_column(\CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles(), 'prefix')
    )));
    $file = \Civi::paths()->getPath("[civicrm.compile]/CachedCiviContainer.{$envId}.php");
    $containerCacheClass = "CachedCiviContainer_{$envId}";
    $containerConfigCache = new ConfigCache($file, $cacheMode === 'auto');
    if (!$containerConfigCache->isFresh()) {
      $containerBuilder = $this->createContainer();
      $containerBuilder->compile();
      $dumper = new PhpDumper($containerBuilder);
      $containerConfigCache->write(
        $dumper->dump(['class' => $containerCacheClass]),
        $containerBuilder->getResources()
      );
    }

    require_once $file;
    $c = new $containerCacheClass();
    return $c;
  }

  /**
   * Construct a new container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   * @return \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  public function createContainer() {
    $civicrm_base_path = dirname(dirname(__DIR__));
    $container = new ContainerBuilder();
    $container->addCompilerPass(new AutoServiceScannerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1000);
    $container->addCompilerPass(new EventScannerPass());
    $container->addCompilerPass(new SpecProviderPass());
    $container->addCompilerPass(new RegisterListenersPass());
    $container->addObjectResource($this);
    $container->setParameter('civicrm_base_path', $civicrm_base_path);
    //$container->set(self::SELF, $this);

    $container->addResource(new \Symfony\Component\Config\Resource\FileResource(__FILE__));

    $container->setDefinition(self::SELF, new Definition(
      'Civi\Core\Container',
      []
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
      []
    ))
      ->setFactory([new Reference(self::SELF), 'createAngularManager'])->setPublic(TRUE);

    $container->setDefinition('angularjs.loader', new Definition('Civi\Angular\AngularLoader', []))
      ->setPublic(TRUE);

    $container->setDefinition('dispatcher', new Definition(
      'Civi\Core\CiviEventDispatcher',
      []
    ))
      ->setFactory([new Reference(self::SELF), 'createEventDispatcher'])->setPublic(TRUE);
    // In symfony 6 it only accepts event_dispatcher as the id, but there are
    // several places in civi and extensions that reference dispatcher.
    $container->setAlias('event_dispatcher', 'dispatcher')->setPublic(TRUE);

    $container->setDefinition('magic_function_provider', new Definition(
      'Civi\API\Provider\MagicFunctionProvider',
      []
    ))->setPublic(TRUE);

    $container->setDefinition('civi_api_kernel', new Definition(
      'Civi\API\Kernel',
      [new Reference('dispatcher'), new Reference('magic_function_provider')]
    ))
      ->setFactory([new Reference(self::SELF), 'createApiKernel'])->setPublic(TRUE);

    $container->setDefinition('cxn_reg_client', new Definition(
      'Civi\Cxn\Rpc\RegistrationClient',
      []
    ))
      ->setFactory('CRM_Cxn_BAO_Cxn::createRegistrationClient')->setPublic(TRUE);

    $container->setDefinition('psr_log', new Definition('CRM_Core_Error_Log', []))->setPublic(TRUE);
    $container->setDefinition('psr_log_manager', new Definition('Civi\Core\LogManager', []))->setPublic(TRUE);
    // With the default log-manager, you may overload a channel by defining a service, e.g.
    // $container->setDefinition('log.ipn', new Definition('CRM_Core_Error_Log', []))->setPublic(TRUE);

    $basicCaches = [
      'js_strings' => 'js_strings',
      'community_messages' => 'community_messages',
      'checks' => 'checks',
      'session' => 'CiviCRM Session',
      'long' => 'long',
      'groups' => 'contact groups',
      'navigation' => 'navigation',
      'customData' => 'custom data',
      'fields' => 'contact fields',
      'contactTypes' => 'contactTypes',
      'metadata' => 'metadata',
    ];
    $verSuffixCaches = ['metadata'];
    $verSuffix = '_' . preg_replace(';[^0-9a-z_];', '_', \CRM_Utils_System::version());
    foreach ($basicCaches as $cacheSvc => $cacheGrp) {
      $definitionParams = [
        'name' => $cacheGrp . (in_array($cacheGrp, $verSuffixCaches) ? $verSuffix : ''),
        'service' => $cacheSvc,
        'type' => ['*memory*', 'SqlGroup', 'ArrayCache'],
      ];
      // For Caches that we don't really care about the ttl for and/or maybe accessed
      // fairly often we use the fastArrayDecorator which improves reads and writes, these
      // caches should also not have concurrency risk.
      $fastArrayCaches = ['groups', 'navigation', 'customData', 'fields', 'contactTypes', 'metadata', 'js_strings'];
      if (in_array($cacheSvc, $fastArrayCaches)) {
        $definitionParams['withArray'] = 'fast';
      }
      $container->setDefinition("cache.{$cacheSvc}", new Definition(
        'CRM_Utils_Cache_Interface',
        [$definitionParams]
      ))->setFactory('CRM_Utils_Cache::create')->setPublic(TRUE);
    }

    // PrevNextCache cannot use memory or array cache at the moment because the
    // Code in CRM_Core_BAO_PrevNextCache assumes that this cache is sql backed.
    $container->setDefinition("cache.prevNextCache", new Definition(
      'CRM_Utils_Cache_Interface',
      [
        [
          'name' => 'CiviCRM Search PrevNextCache',
          'type' => ['SqlGroup'],
        ],
      ]
    ))->setFactory('CRM_Utils_Cache::create')->setPublic(TRUE);

    // Memcache is limited to 1 MB by default, and since this is not read often
    // it does not make much sense in Redis either.
    $container->setDefinition('cache.extension_browser', new Definition(
      'CRM_Utils_Cache_Interface',
      [
        [
          'name' => 'extension_browser',
          'type' => ['SqlGroup', 'ArrayCache'],
        ],
      ]
    ))->setFactory('CRM_Utils_Cache::create')->setPublic(TRUE);

    $container->setDefinition('bundle.bootstrap3', new Definition('CRM_Core_Resources_Bundle', ['bootstrap3']))
      ->setFactory('CRM_Core_Resources_Common::createBootstrap3Bundle')->setPublic(TRUE);

    $container->setDefinition('bundle.coreStyles', new Definition('CRM_Core_Resources_Bundle', ['coreStyles']))
      ->setFactory('CRM_Core_Resources_Common::createStyleBundle')->setPublic(TRUE);

    $container->setDefinition('bundle.coreResources', new Definition('CRM_Core_Resources_Bundle', ['coreResources']))
      ->setFactory('CRM_Core_Resources_Common::createFullBundle')->setPublic(TRUE);

    $container->setDefinition('pear_mail', new Definition('Mail'))
      ->setFactory('CRM_Utils_Mail::createMailer')->setPublic(TRUE);

    $bootServiceTypes = [
      'cache.settings' => \CRM_Utils_Cache_Interface::class,
      'dispatcher.boot' => CiviEventDispatcher::class,
      'lockManager' => LockManager::class,
      'paths' => Paths::class,
      'runtime' => \CRM_Core_Config_Runtime::class,
      'settings_manager' => SettingsManager::class,
      'userPermissionClass' => \CRM_Core_Permission_Base::class,
      'userSystem' => \CRM_Utils_System_Base::class,
    ];
    if (empty(\Civi::$statics[__CLASS__]['boot'])) {
      throw new \RuntimeException('Cannot initialize container. Boot services are undefined.');
    }
    foreach (\Civi::$statics[__CLASS__]['boot'] as $bootService => $def) {
      $container->setDefinition($bootService, new Definition($bootServiceTypes[$bootService] ?? NULL))
        ->setSynthetic(TRUE)
        ->setPublic(TRUE);
    }

    // Expose legacy singletons as services in the container.
    $singletons = [
      'httpClient' => 'CRM_Utils_HttpClient',
      'cache.default' => 'CRM_Utils_Cache',
      'i18n' => 'CRM_Core_I18n',
      // Maybe? 'config' => 'CRM_Core_Config',
      // Maybe? 'smarty' => 'CRM_Core_Smarty',
    ];
    foreach ($singletons as $name => $class) {
      $container->setDefinition($name, new Definition(
        $class
      ))
        ->setFactory([$class, 'singleton'])->setPublic(TRUE);
    }
    $container->setAlias('cache.short', 'cache.default')->setPublic(TRUE);

    $container->setDefinition('civi.pipe', new Definition(
      'Civi\Pipe\PipeSession',
      []
    ))->setPublic(TRUE)->setShared(FALSE);

    $container->setDefinition('resources', new Definition(
      'CRM_Core_Resources',
      [new Reference('service_container')]
    ))->setFactory([new Reference(self::SELF), 'createResources'])->setPublic(TRUE);

    $container->setDefinition('resources.js_strings', new Definition(
      'CRM_Core_Resources_Strings',
      [new Reference('cache.js_strings')]
    ))->setPublic(TRUE);

    $container->setDefinition('prevnext', new Definition(
      'CRM_Core_PrevNextCache_Interface',
      [new Reference('service_container')]
    ))->setFactory([new Reference(self::SELF), 'createPrevNextCache'])->setPublic(TRUE);

    $container->setDefinition('prevnext.driver.sql', new Definition(
      'CRM_Core_PrevNextCache_Sql',
      []
    ))->setPublic(TRUE);

    $container->setDefinition('prevnext.driver.redis', new Definition(
      'CRM_Core_PrevNextCache_Redis',
      [new Reference('cache_config')]
    ))->setPublic(TRUE);

    $container->setDefinition('cache_config', new Definition('ArrayObject'))
      ->setFactory([new Reference(self::SELF), 'createCacheConfig'])->setPublic(TRUE);

    $container->setDefinition('civi.activity.triggers', new Definition(
      'Civi\Core\SqlTrigger\TimestampTriggers',
      ['civicrm_activity', 'Activity']
    ))->addTag('kernel.event_listener', ['event' => 'hook_civicrm_triggerInfo', 'method' => 'onTriggerInfo'])->setPublic(TRUE);

    $container->setDefinition('civi.case.triggers', new Definition(
      'Civi\Core\SqlTrigger\TimestampTriggers',
      ['civicrm_case', 'Case']
    ))->addTag('kernel.event_listener', ['event' => 'hook_civicrm_triggerInfo', 'method' => 'onTriggerInfo'])->setPublic(TRUE);

    $container->setDefinition('civi.case.staticTriggers', new Definition(
      'Civi\Core\SqlTrigger\StaticTriggers',
      [
        [
          [
            'upgrade_check' => ['table' => 'civicrm_case', 'column' => 'modified_date'],
            'table' => 'civicrm_case_activity',
            'when' => 'AFTER',
            'event' => ['INSERT'],
            'sql' => "UPDATE civicrm_case SET modified_date = CURRENT_TIMESTAMP WHERE id = NEW.case_id;",
          ],
          [
            'upgrade_check' => ['table' => 'civicrm_case', 'column' => 'modified_date'],
            'table' => 'civicrm_activity',
            'when' => 'BEFORE',
            'event' => ['UPDATE', 'DELETE'],
            'sql' => "UPDATE civicrm_case SET modified_date = CURRENT_TIMESTAMP WHERE id IN (SELECT ca.case_id FROM civicrm_case_activity ca WHERE ca.activity_id = OLD.id);",
          ],
        ],
      ]
    ))
      ->addTag('kernel.event_listener', ['event' => 'hook_civicrm_triggerInfo', 'method' => 'onTriggerInfo'])->setPublic(TRUE);

    $container->setDefinition('civi_token_compat', new Definition(
      'Civi\Token\TokenCompatSubscriber',
      []
    ))->addTag('kernel.event_subscriber')->setPublic(TRUE);

    $container->setDefinition("crm_mailing_action_tokens", new Definition(
      'CRM_Mailing_ActionTokens',
      []
    ))->addTag('kernel.event_subscriber')->setPublic(TRUE);

    foreach (['Activity', 'Contact', 'Contribute', 'Event', 'Mailing', 'Member', 'Case', 'Pledge'] as $component) {
      $container->setDefinition('crm_' . strtolower($component) . '_tokens', new Definition(
        "CRM_{$component}_Tokens",
        []
      ))->addTag('kernel.event_subscriber')->setPublic(TRUE);
    }
    $container->setDefinition("crm_financial_trxn_tokens", new Definition(
      'CRM_Financial_FinancialTrxnTokens',
      []
    ))->addTag('kernel.event_subscriber')->setPublic(TRUE);

    $container->setDefinition('civi_token_impliedcontext', new Definition(
      'Civi\Token\ImpliedContextSubscriber',
      []
    ))->addTag('kernel.event_subscriber')->setPublic(TRUE);
    $container->setDefinition('crm_participant_tokens', new Definition(
      'CRM_Event_ParticipantTokens',
      []
    ))->addTag('kernel.event_subscriber')->setPublic(TRUE);
    $container->setDefinition('crm_contribution_recur_tokens', new Definition(
      'CRM_Contribute_RecurTokens',
      []
    ))->addTag('kernel.event_subscriber')->setPublic(TRUE);
    $container->setDefinition('crm_contribution_recur_tokens', new Definition(
      'CRM_Contribute_RecurTokens',
      []
    ))->addTag('kernel.event_subscriber')->setPublic(TRUE);
    $container->setDefinition('crm_survey_tokens', new Definition(
      'CRM_Campaign_SurveyTokens',
      []
    ))->addTag('kernel.event_subscriber')->setPublic(TRUE);
    $container->setDefinition('crm_group_tokens', new Definition(
      'CRM_Core_GroupTokens',
      []
    ))->addTag('kernel.event_subscriber')->setPublic(TRUE);
    $container->setDefinition('crm_domain_tokens', new Definition(
      'CRM_Core_DomainTokens',
      []
    ))->addTag('kernel.event_subscriber')->setPublic(TRUE);

    // For each DAO that supports tokens by declaring the token class...
    $entities = \CRM_Core_DAO_AllCoreTables::tokenClasses();
    foreach ($entities as $entity => $class) {
      $container->setDefinition('crm_entity_token_' . strtolower($entity), new Definition(
        $class,
        [$entity]
      ))->addTag('kernel.event_subscriber')->setPublic(TRUE);
    }

    $container->setDefinition('crm_token_tidy', new Definition(
      '\Civi\Token\TidySubscriber',
      []
    ))->addTag('kernel.event_subscriber')->setPublic(TRUE);

    $dispatcherDefn = $container->getDefinition('dispatcher');
    // Get all declared BAO classes
    $baoClasses = \CRM_Core_DAO_AllCoreTables::getBaoClasses();
    // "Extra" BAO classes that don't have a DAO but still need to listen to hooks
    $baoClasses[] = \CRM_Core_BAO_CustomValue::class;
    foreach ($baoClasses as $key => $baoClass) {
      // Key will be a valid entity name for all but the "extra" classes which don't have a DAO entity.
      $baoEntity = is_numeric($key) ? NULL : $key;
      $listenerMap = EventScanner::findListeners($baoClass, $baoEntity);
      if ($listenerMap) {
        $file = (new \ReflectionClass($baoClass))->getFileName();
        $container->addResource(new \Symfony\Component\Config\Resource\FileResource($file));
        $dispatcherDefn->addMethodCall('addListenerMap', [$baoClass, $listenerMap]);
      }
    }

    // FIXME: Automatically scan BasicServices for ProviderInterface.
    $container->getDefinition('civi_api_kernel')->addMethodCall(
      'registerApiProvider',
      [new Reference('action_object_provider')]
    );

    $container->setDefinition('esm.loader', new Definition(
      'object',
      [new Reference('service_container')]
    ))->setFactory([new Reference(self::SELF), 'createEsmLoader'])->setPublic(TRUE);

    \CRM_Utils_Hook::container($container);

    return $container;
  }

  /**
   * @return \Civi\Angular\Manager
   */
  public function createAngularManager() {
    $moduleEnvId = md5(\CRM_Core_Config_Runtime::getId());
    $angCache = \CRM_Utils_Cache::create([
      'name' => substr('angular_' . $moduleEnvId, 0, 32),
      'service' => 'angular_manager',
      'type' => ['*memory*', 'SqlGroup', 'ArrayCache'],
      'withArray' => 'fast',
      'prefetch' => TRUE,
    ]);
    return new \Civi\Angular\Manager(\CRM_Core_Resources::singleton(), $angCache);
  }

  /**
   * @return \Civi\Core\CiviEventDispatcherInterface
   */
  public function createEventDispatcher() {
    // Continue building on the original dispatcher created during bootstrap.
    /** @var CiviEventDispatcher $dispatcher */
    $dispatcher = static::getBootService('dispatcher.boot');

    // Sometimes, you have a generic event ('hook_pre') and wish to fire more targeted aliases ('hook_pre::MyEntity') to allow shorter subscriber lists.
    $aliasEvent = function($eventName, $fieldName) {
      return function($e) use ($eventName, $fieldName) {
        \Civi::dispatcher()->dispatch($eventName . "::" . $e->{$fieldName}, $e);
      };
    };
    $aliasMethodEvent = function($eventName, $methodName) {
      return function($e) use ($eventName, $methodName) {
        \Civi::dispatcher()->dispatch($eventName . "::" . $e->{$methodName}(), $e);
      };
    };

    $dispatcher->addListener('civi.api4.validate', $aliasMethodEvent('civi.api4.validate', 'getEntityName'), 100);
    $dispatcher->addListener('civi.api4.authorizeRecord', $aliasMethodEvent('civi.api4.authorizeRecord', 'getEntityName'), 100);

    $dispatcher->addListener('civi.core.install', ['\Civi\Core\InstallationCanary', 'check']);
    $dispatcher->addListener('civi.core.install', ['\Civi\Core\DatabaseInitializer', 'initialize']);
    $dispatcher->addListener('civi.core.install', ['\Civi\Core\LocalizationInitializer', 'initialize']);
    $dispatcher->addListener('hook_civicrm_post', ['\CRM_Core_Transaction', 'addPostCommit'], -1000);
    $dispatcher->addListener('hook_civicrm_pre', $aliasEvent('hook_civicrm_pre', 'entity'), 100);
    $dispatcher->addListener('civi.dao.preDelete', ['\CRM_Core_BAO_EntityTag', 'preDeleteOtherEntity']);
    $dispatcher->addListener('hook_civicrm_post', $aliasEvent('hook_civicrm_post', 'entity'), 100);
    $dispatcher->addListener('hook_civicrm_post::Activity', ['\Civi\CCase\Events', 'fireCaseChange']);
    $dispatcher->addListener('hook_civicrm_post::Case', ['\Civi\CCase\Events', 'fireCaseChange']);
    $dispatcher->addListener('hook_civicrm_caseChange', ['\Civi\CCase\Events', 'delegateToXmlListeners']);
    $dispatcher->addListener('hook_civicrm_caseChange', ['\Civi\CCase\SequenceListener', 'onCaseChange_static']);
    $dispatcher->addListener('hook_civicrm_cryptoRotateKey', ['\Civi\Crypto\RotateKeys', 'rotateSmtp']);
    $dispatcher->addListener('hook_civicrm_eventDefs', ['\Civi\Core\CiviEventInspector', 'findBuiltInEvents']);
    // TODO We need a better code-convention for metadata about non-hook events.
    $dispatcher->addListener('hook_civicrm_eventDefs', ['\Civi\API\Events', 'hookEventDefs']);
    $dispatcher->addListener('hook_civicrm_eventDefs', ['\Civi\Core\Event\SystemInstallEvent', 'hookEventDefs']);
    $dispatcher->addListener('hook_civicrm_buildAsset', ['\Civi\Angular\Page\Modules', 'buildAngularModules']);
    $dispatcher->addListenerService('civi.region.render', ['angularjs.loader', 'onRegionRender']);
    $dispatcher->addListener('hook_civicrm_buildAsset', ['\CRM_Core_Resources', 'renderMenubarStylesheet']);
    $dispatcher->addListener('hook_civicrm_buildAsset', ['\CRM_Core_Resources', 'renderL10nJs']);
    $dispatcher->addListener('hook_civicrm_coreResourceList', ['\CRM_Utils_System', 'appendCoreResources']);
    $dispatcher->addListener('hook_civicrm_getAssetUrl', ['\CRM_Utils_System', 'alterAssetUrl']);
    $dispatcher->addListener('hook_civicrm_alterExternUrl', ['\CRM_Utils_System', 'migrateExternUrl'], 1000);
    // Not a BAO class so it can't implement hookInterface
    $dispatcher->addListener('hook_civicrm_post', ['CRM_Utils_Recent', 'on_hook_civicrm_post']);
    $dispatcher->addListener('hook_civicrm_permissionList', ['CRM_Core_Permission_List', 'findConstPermissions'], 975);
    $dispatcher->addListener('hook_civicrm_permissionList', ['CRM_Core_Permission_List', 'findCiviPermissions'], 950);
    $dispatcher->addListener('hook_civicrm_permissionList', ['CRM_Core_Permission_List', 'findCmsPermissions'], 925);

    $dispatcher->addListener('hook_civicrm_postSave_civicrm_domain', ['\CRM_Core_BAO_Domain', 'onPostSave']);
    $dispatcher->addListener('hook_civicrm_unhandled_exception', [
      'CRM_Core_LegacyErrorHandler',
      'handleException',
    ], -200);

    return $dispatcher;
  }

  /**
   * @return \Civi\Core\Lock\LockManager
   */
  public static function createLockManager() {
    // Ideally, downstream implementers could override any definitions in
    // the container. For now, we'll make-do with some define()s.
    $lm = new LockManager();
    $lm
      ->register('/^cache\./', defined('CIVICRM_CACHE_LOCK') ? CIVICRM_CACHE_LOCK : ['CRM_Core_Lock', 'createScopedLock'])
      ->register('/^data\./', defined('CIVICRM_DATA_LOCK') ? CIVICRM_DATA_LOCK : ['CRM_Core_Lock', 'createScopedLock'])
      ->register('/^worker\.mailing\.send\./', defined('CIVICRM_WORK_LOCK') ? CIVICRM_WORK_LOCK : ['CRM_Core_Lock', 'createCivimailLock'])
      ->register('/^worker\./', defined('CIVICRM_WORK_LOCK') ? CIVICRM_WORK_LOCK : ['CRM_Core_Lock', 'createScopedLock']);

    // Registrations may use complex resolver expressions, but (as a micro-optimization)
    // the default factory is specified as an array.

    return $lm;
  }

  /**
   * @param \Civi\Core\CiviEventDispatcherInterface $dispatcher
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
    $dispatcher->addSubscriber(new \Civi\API\Subscriber\WrapperAdapter([
      \CRM_Utils_API_HTMLInputCoder::singleton(),
      \CRM_Utils_API_NullOutputCoder::singleton(),
      \CRM_Utils_API_ReloadOption::singleton(),
      \CRM_Utils_API_MatchOption::singleton(),
    ]));
    $dispatcher->addSubscriber(new \Civi\API\Subscriber\DebugSubscriber());
    $kernel = new \Civi\API\Kernel($dispatcher);

    $reflectionProvider = new \Civi\API\Provider\ReflectionProvider($kernel);
    $dispatcher->addSubscriber($reflectionProvider);

    $dispatcher->addSubscriber(new \Civi\API\Subscriber\DynamicFKAuthorization(
      $kernel,
      'Attachment',
      ['create', 'get', 'delete'],
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
      '
    ));

    $kernel->setApiProviders([
      $reflectionProvider,
      $magicFunctionProvider,
    ]);

    return $kernel;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return \CRM_Core_Resources
   */
  public static function createResources($container) {
    $sys = \CRM_Extension_System::singleton();
    return new \CRM_Core_Resources(
      $sys->getMapper(),
      new \CRM_Core_Resources_Strings($container->get('cache.js_strings')),
      \CRM_Core_Config::isUpgradeMode() ? NULL : 'resCacheCode'
    );
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return \CRM_Core_PrevNextCache_Interface
   */
  public static function createPrevNextCache($container) {
    $setting = \Civi::settings()->get('prevNextBackend');
    if (!$setting || $setting === 'default') {
      $cacheDriver = \CRM_Utils_Cache::getCacheDriver();
      $service = 'prevnext.driver.' . strtolower($cacheDriver);
      return $container->has($service)
        ? $container->get($service)
        : $container->get('prevnext.driver.sql');
    }
    return $container->get('prevnext.driver.' . $setting);
  }

  /**
   * Determine which component will load ECMAScript Modules.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return \object
   */
  public static function createEsmLoader($container): object {
    $name = \Civi::settings()->get('esm_loader');
    if ($name === 'auto') {
      $name = 'shim-fast';
      \Civi::dispatcher()->dispatch('civi.esm.loader.default', GenericHookEvent::create(['default' => &$name]));
    }
    if ($container->has("esm.loader.$name")) {
      return $container->get("esm.loader.$name");
    }
    else {
      \Civi::log()->warning('Invalid ESM loader: {name}', ['name' => $name]);
      return $container->get("esm.loader.browser");
    }
  }

  /**
   * @return \ArrayObject
   */
  public static function createCacheConfig() {
    $driver = \CRM_Utils_Cache::getCacheDriver();
    $settings = \CRM_Utils_Cache::getCacheSettings($driver);
    $settings['driver'] = $driver;
    return new \ArrayObject($settings);
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
    // Array(string $serviceId => object $serviceInstance).
    $bootServices = [];
    \Civi::$statics[__CLASS__]['boot'] = &$bootServices;

    $bootServices['runtime'] = $runtime = new \CRM_Core_Config_Runtime();
    $runtime->initialize($loadFromDB);

    $bootServices['paths'] = new \Civi\Core\Paths();

    $bootServices['dispatcher.boot'] = new CiviEventDispatcher();
    $bootServices['dispatcher.boot']->addListener('civi.queue.runTask.start', ['CRM_Upgrade_DispatchPolicy', 'onRunTask']);

    // Quality control: There should be no pre-boot hooks because they make it harder to understand/support/refactor.
    // If a pre-boot hook sneaks in, we'll raise an error.
    $bootDispatchPolicy = [
      '/^hook_/' => 'not-ready',
      '/^civi\./' => 'run',
    ];
    $bootServices['dispatcher.boot']->setDispatchPolicy($bootDispatchPolicy);

    $class = $runtime->userFrameworkClass;
    $bootServices['userSystem'] = $userSystem = new $class();
    $userSystem->initialize();

    $userPermissionClass = 'CRM_Core_Permission_' . $runtime->userFramework;
    $bootServices['userPermissionClass'] = new $userPermissionClass();

    $bootServices['cache.settings'] = \CRM_Utils_Cache::create([
      'name' => 'settings',
      'type' => ['*memory*', 'SqlGroup', 'ArrayCache'],
    ]);

    $bootServices['settings_manager'] = new \Civi\Core\SettingsManager($bootServices['cache.settings']);

    $bootServices['lockManager'] = self::createLockManager();

    if ($loadFromDB && $runtime->dsn) {
      \CRM_Core_DAO::init($runtime->dsn);
      $bootServices['settings_manager']->dbAvailable();
      \CRM_Utils_Hook::singleton(TRUE);
      \CRM_Extension_System::singleton(TRUE);
      \CRM_Extension_System::singleton()->getClassLoader()->register();
      \CRM_Extension_System::singleton()->getMixinLoader()->run();
      \CRM_Utils_Hook::singleton()->commonBuildModuleList('civicrm_boot');
      $bootServices['dispatcher.boot']->setDispatchPolicy(\CRM_Core_Config::isUpgradeMode() ? \CRM_Upgrade_DispatchPolicy::pick() : NULL);

      $runtime->includeCustomPath();

      $c = new self();
      static::useContainer($c->loadContainer());
    }
    else {
      $bootServices['dispatcher.boot']->setDispatchPolicy(\CRM_Core_Config::isUpgradeMode() ? \CRM_Upgrade_DispatchPolicy::pick() : NULL);
    }
  }

  /**
   * Set the active container (over-writing the current container, if defined).
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @internal Intended for bootstrap and unit-testing.
   */
  public static function useContainer($container): void {
    $bootServices = \Civi::$statics[__CLASS__]['boot'];
    foreach ($bootServices as $name => $obj) {
      $container->set($name, $obj);
    }
    \Civi::$statics[__CLASS__]['container'] = $container;
    // Ensure all container-based serivces have a chance to add their listeners.
    // Without this, it's a matter of happenstance (dependent upon particular page-request/configuration/etc).
    $container->get('dispatcher');
  }

  /**
   * @param string $name
   *
   * @return mixed
   */
  public static function getBootService($name) {
    return \Civi::$statics[__CLASS__]['boot'][$name];
  }

  /**
   * Determine whether the container services are available.
   *
   * @return bool
   */
  public static function isContainerBooted() {
    return isset(\Civi::$statics[__CLASS__]['container']);
  }

}
