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

use Civi\Core\Container;
use Civi\Test\Invasive;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The 'AutoDefinition' uses Civi-style annotations to construct the `Definition` of
 * a Symfony service. To test this, we need many different class/annotation combinations.
 * Each test follows this structure:
 *
 * 1. Define an example service with an anonymous class. (Include annotations to taste.)
 * 2. Ask the container for the service
 * 3. Assert that the service is well-configured.
 */
class AutoDefinitionTest extends \CiviUnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  /**
   * A property with the `@inject` annotation will receive a service with the matching name.
   */
  public function testInjectEponymousProperty(): void {
    $this->useExampleService(
      /**
       * @service TestEponymousProperty
       */
      new class() {

        /**
         * @var \Psr\Log\LoggerInterface
         * @inject
         */
        public $psr_log;

      }
    );

    $instance = \Civi::service('TestEponymousProperty');
    $this->assertInstanceOf(LoggerInterface::class, $instance->psr_log);
  }

  /**
   * A property with the `@inject` annotation can be private.
   */
  public function testInjectPrivateProperty(): void {
    $this->useExampleService(
      /**
       * @service TestInjectPrivateProperty
       */
      new class() {

        use AutoServiceTrait;

        /**
         * @var \Psr\Log\LoggerInterface
         * @inject
         */
        private $psr_log;

      }
    );

    $instance = \Civi::service('TestInjectPrivateProperty');
    $this->assertInstanceOf(LoggerInterface::class, Invasive::get([$instance, 'psr_log']));
  }

  /**
   * A property with `@inject <my.service.name>` will receive the named service.
   */
  public function testInjectNamedProperty(): void {
    $this->useExampleService(
      /**
       * @service TestNamedProperty
       */
      new class() {

        /**
         * @var \Psr\Log\LoggerInterface
         * @inject cache.extension_browser
         */
        public $cache;

      }
    );

    $instance = \Civi::service('TestNamedProperty');
    $this->assertInstanceOf(\CRM_Utils_Cache_CacheWrapper::class, $instance->cache);
    $cacheClass = Invasive::get([$instance->cache, 'delegate']);
    $this->assertInstanceOf(\CRM_Utils_Cache_SqlGroup::class, $cacheClass);
    $this->assertEquals('extension_browser', Invasive::get([$cacheClass, 'group']));
  }

  /**
   * A method `setFooBar()` with `@inject <my.service.name>` will be called during initialization
   * with the requested service.
   */
  public function testInjectSetter(): void {
    $this->useExampleService(
      /**
       * @service TestInjectSetter
       */
      new class() {

        /**
         * @var \Psr\Log\LoggerInterface
         */
        private $log;

        /**
         * @return \Psr\Log\LoggerInterface|null
         */
        public function getLog(): ?\Psr\Log\LoggerInterface {
          return $this->log;
        }

        /**
         * @param \Psr\Log\LoggerInterface|null $log
         * @inject psr_log
         */
        public function setLog(?\Psr\Log\LoggerInterface $log): void {
          $this->log = $log;
        }

      }
    );

    $instance = \Civi::service('TestInjectSetter');
    $this->assertInstanceOf(LoggerInterface::class, $instance->getLog());
  }

  /**
   * A constructor with `@inject <my.service.name>` will be called with the requested service.
   */
  public function testInjectConstructor(): void {
    $this->useExampleService(
      /**
       * @service TestInjectConstructor
       */
      new class() {

        /**
         * @var \Psr\Log\LoggerInterface
         */
        private $log;

        /**
         * @var \Psr\SimpleCache\CacheInterface
         */
        private $cache;

        /**
         * @param \Psr\Log\LoggerInterface|null $log
         * @param \Psr\SimpleCache\CacheInterface|null $cache
         * @inject psr_log, cache.extension_browser
         */
        public function __construct(?\Psr\Log\LoggerInterface $log = NULL, ?\Psr\SimpleCache\CacheInterface $cache = NULL) {
          $this->log = $log;
          $this->cache = $cache;
        }

      }
    );

    $instance = \Civi::service('TestInjectConstructor');
    $this->assertInstanceOf(LoggerInterface::class, Invasive::get([$instance, 'log']));
    $cacheWrapper = Invasive::get([$instance, 'cache']);
    $cacheClass = Invasive::get([$cacheWrapper, 'delegate']);
    $this->assertInstanceOf(\CRM_Utils_Cache_SqlGroup::class, $cacheClass);
    $this->assertEquals('extension_browser', Invasive::get([$cacheWrapper, 'serviceName']));
    $this->assertEquals('extension_browser', Invasive::get([$cacheClass, 'group']));
  }

  /**
   * If you use `@inject` on multiple items, the sequence of injections should be deterministic.
   *
   * Note, however, that upstream doesn't guarantee the sequence over the long-term.
   * If it changes, you may need to update the test.
   */
  public function testInjectionSequence(): void {
    $this->useExampleService(
      /**
       * @service TestInjectionSequence
       */
      new class() {

        use AutoServiceTrait;

        /**
         * A list of snapshots -- at each point in time, what fields have been defined?
         *
         * @var array
         */
        public $sequence = [];

        /**
         * @var \Psr\SimpleCache\CacheInterface
         */
        private $asConstructorArg;

        /**
         * @var \Psr\SimpleCache\CacheInterface
         * @inject cache.default
         */
        private $asPrivateProperty;

        /**
         * @var \Psr\SimpleCache\CacheInterface
         * @inject cache.metadata
         */
        public $asPublicProperty;

        /**
         * @var \Psr\SimpleCache\CacheInterface
         */
        private $asSetterMethod;

        /**
         * @param \Psr\SimpleCache\CacheInterface|null $asConstructorArg
         * @inject cache.long
         */
        public function __construct(?\Psr\SimpleCache\CacheInterface $asConstructorArg = NULL) {
          $this->asConstructorArg = $asConstructorArg;
          $this->sequence[] = ['@' . __FUNCTION__, $this->getFilledFields()];
        }

        /**
         * @param \Psr\SimpleCache\CacheInterface $asSetterMethod
         * @inject cache.js_strings
         */
        public function setAsSetterMethod($asSetterMethod): void {
          $this->asSetterMethod = $asSetterMethod;
          $this->sequence[] = ['@' . __FUNCTION__, $this->getFilledFields()];
        }

        public function getFilledFields(): array {
          $actualNames = [];
          foreach (['asConstructorArg', 'asPrivateProperty', 'asPublicProperty', 'asSetterMethod'] as $name) {
            if (!empty($this->{$name})) {
              $actualNames[] = $name;
            }
          }
          return $actualNames;
        }

      }
    );

    $instance = \Civi::service('TestInjectionSequence');
    $expectedSequence = [
      // ['@functionWhichTookSnapshot', ['list', 'of', 'filled', 'fields']]
      0 => ['@__construct', ['asConstructorArg']],
      1 => ['@__construct', ['asConstructorArg', 'asPrivateProperty', 'asPublicProperty']],
      // ^^ Ugh, when mixing injectors, Symfony calls the constructor twice...
      2 => ['@setAsSetterMethod', ['asConstructorArg', 'asPrivateProperty', 'asPublicProperty', 'asSetterMethod']],
    ];
    $this->assertEquals($expectedSequence, $instance->sequence);
  }

  /**
   * The `@service` annotation can be used to define factory methods.
   *
   * In this example, we create two services (each with a different factory method, and each
   * with a different kind of data).
   */
  public function testFactoryMethods(): void {
    $this->useExampleService(
      new class() {

        /**
         * @var \Psr\Log\LoggerInterface
         */
        private $log;

        /**
         * A factory that returns an instance of this class.
         *
         * @service TestInjectFactory.self
         * @inject psr_log
         * @param \Psr\Log\LoggerInterface|null $log
         */
        public static function selfFactory(?\Psr\Log\LoggerInterface $log = NULL) {
          $self = new static();
          $self->log = $log;
          return $self;
        }

        /**
         * A factory that returns picks a dynamic class.
         *
         * @service TestInjectFactory.dynamic
         * @inject psr_log
         * @return \Psr\SimpleCache\CacheInterface
         *   The concrete type will depend on configuration.
         */
        public static function dynamicFactory(?\Psr\Log\LoggerInterface $log = NULL) {
          if (!($log instanceof LoggerInterface)) {
            throw new \RuntimeException('Expected to get a log');
          }
          return \CRM_Utils_Cache::create([
            'type' => ['*memory*', 'ArrayCache'],
            'name' => 'yourFactory',
          ]);
        }

      }
    );

    $instance = \Civi::service('TestInjectFactory.self');
    $this->assertInstanceOf(LoggerInterface::class, Invasive::get([$instance, 'log']));
    $this->assertInstanceOf(CacheInterface::class, \Civi::service('TestInjectFactory.dynamic'));
  }

  /**
   * What happens if you have multiple `@service` definitions (one on the class, one on a factory-method)?
   * You get multiple services.
   */
  public function testClassAndFactoryMix(): void {
    $this->useExampleService(
      /**
       * @service TestClassAndFactoryMix.normal
       */
      new class() {

        /**
         * @var \Psr\Log\LoggerInterface
         */
        private $log;

        /**
         * A factory that returns an instance of this class.
         *
         * @inject psr_log
         * @param \Psr\Log\LoggerInterface|null $log
         */
        public function __construct(?\Psr\Log\LoggerInterface $log = NULL) {
          $this->log = $log;
        }

        /**
         * A factory that returns picks a dynamic class.
         *
         * @service TestClassAndFactoryMix.dynamic
         * @inject psr_log
         * @return \Psr\SimpleCache\CacheInterface
         *   The concrete type will depend on configuration.
         */
        public static function dynamicFactory(?\Psr\Log\LoggerInterface $log = NULL) {
          if (!($log instanceof LoggerInterface)) {
            throw new \RuntimeException('Expected to get a log');
          }
          return \CRM_Utils_Cache::create([
            'type' => ['*memory*', 'ArrayCache'],
            'name' => 'yourFactory',
          ]);
        }

      }
    );

    $instance = \Civi::service('TestClassAndFactoryMix.normal');
    $this->assertInstanceOf(LoggerInterface::class, Invasive::get([$instance, 'log']));
    $this->assertInstanceOf(CacheInterface::class, \Civi::service('TestClassAndFactoryMix.dynamic'));
  }

  /**
   * It is possible for third-party code to use `AutoDefinition` as the starting-point for
   * their own (slightly customized) service definitions.
   *
   * In this example, we make two instances. Each instance has a different value for `$myName`.
   */
  public function testTwoManualServices(): void {
    $this->useCustomContainer(function(ContainerBuilder $container) {
      $exemplar = new class() implements AutoServiceInterface {

        public static function buildContainer(ContainerBuilder $container): void {
          $container->setDefinition('TestTwoManualServices.1', AutoDefinition::create(static::class)
            ->setProperty('myName', 'first'));
          $container->setDefinition('TestTwoManualServices.2', AutoDefinition::create(static::class)
            ->setProperty('myName', 'second'));
        }

        /**
         * @var string
         */
        public $myName;

        /**
         * @var \Psr\Log\LoggerInterface
         * @inject psr_log
         */
        public $log;

      };
      $exemplar::buildContainer($container);
    });

    $first = \Civi::service('TestTwoManualServices.1');
    $this->assertEquals('first', $first->myName);
    $this->assertInstanceOf(LoggerInterface::class, $first->log);

    $second = \Civi::service('TestTwoManualServices.2');
    $this->assertEquals('second', $second->myName);
    $this->assertInstanceOf(LoggerInterface::class, $second->log);
  }

  protected function useExampleService($exemplar) {
    $this->useCustomContainer(function(ContainerBuilder $container) use ($exemplar) {
      $definitions = AutoDefinition::scan(get_class($exemplar));
      $container->addDefinitions($definitions);
    });
  }

  protected function useCustomContainer(callable $callback) {
    $container = (new Container())->createContainer();
    $callback($container);
    $container->compile();
    Container::useContainer($container);
  }

}
