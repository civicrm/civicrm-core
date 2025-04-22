<?php
namespace Civi\API;

use Civi\Core\CiviEventDispatcher;

/**
 */
class KernelTest extends \CiviUnitTestCase {
  const MOCK_VERSION = 3;

  /**
   * @var array
   * (int => array('name' => string $eventName, 'type' => string $className))
   */
  public $actualEventSequence;

  /**
   * @var \Civi\Core\CiviEventDispatcher
   */
  public $dispatcher;

  /**
   * @var Kernel
   */
  public $kernel;

  protected function setUp(): void {
    parent::setUp();
    $this->actualEventSequence = [];
    $this->dispatcher = new CiviEventDispatcher();
    $this->monitorEvents(Events::allEvents());
    $this->kernel = new Kernel($this->dispatcher);
  }

  public function testNormalEvents(): void {
    $this->kernel->registerApiProvider($this->createWidgetFrobnicateProvider());
    $result = $this->kernel->runSafe('Widget', 'frobnicate', [
      'version' => self::MOCK_VERSION,
    ]);

    $expectedEventSequence = [
      ['name' => 'civi.api.resolve', 'class' => 'Civi\API\Event\ResolveEvent'],
      ['name' => 'civi.api.authorize', 'class' => 'Civi\API\Event\AuthorizeEvent'],
      ['name' => 'civi.api.prepare', 'class' => 'Civi\API\Event\PrepareEvent'],
      ['name' => 'civi.api.respond', 'class' => 'Civi\API\Event\RespondEvent'],
    ];
    $this->assertEquals($expectedEventSequence, $this->actualEventSequence);
    $this->assertEquals('frob', $result['values'][98]);
  }

  public function testResolveException(): void {
    $test = $this;
    $this->dispatcher->addListener('civi.api.resolve', function () {
      throw new \CRM_Core_Exception('Oh My God', 'omg', ['the' => 'badzes']);
    }, Events::W_EARLY);
    $this->dispatcher->addListener('civi.api.exception', function (\Civi\API\Event\ExceptionEvent $event) use ($test) {
      $test->assertEquals('Oh My God', $event->getException()->getMessage());
    });

    $this->kernel->registerApiProvider($this->createWidgetFrobnicateProvider());
    $result = $this->kernel->runSafe('Widget', 'frobnicate', [
      'version' => self::MOCK_VERSION,
    ]);

    $expectedEventSequence = [
      ['name' => 'civi.api.resolve', 'class' => 'Civi\API\Event\ResolveEvent'],
      ['name' => 'civi.api.exception', 'class' => 'Civi\API\Event\ExceptionEvent'],
    ];
    $this->assertEquals($expectedEventSequence, $this->actualEventSequence);
    $this->assertEquals('Oh My God', $result['error_message']);
    $this->assertEquals('omg', $result['error_code']);
    $this->assertEquals('badzes', $result['the']);
  }

  public function testExceptionException(): void {
    $test = $this;
    $this->dispatcher->addListener('civi.api.exception', function (\Civi\API\Event\ExceptionEvent $event) use ($test) {
      $test->assertEquals('Frobnication encountered an exception', $event->getException()->getMessage());
    });

    $this->kernel->registerApiProvider($this->createWidgetFrobnicateProvider());
    $result = $this->kernel->runSafe('Widget', 'frobnicate', [
      'version' => self::MOCK_VERSION,
      'exception' => 'Frobnication encountered an exception',
    ]);

    $expectedEventSequence = [
      ['name' => 'civi.api.resolve', 'class' => 'Civi\API\Event\ResolveEvent'],
      ['name' => 'civi.api.authorize', 'class' => 'Civi\API\Event\AuthorizeEvent'],
      ['name' => 'civi.api.prepare', 'class' => 'Civi\API\Event\PrepareEvent'],
      ['name' => 'civi.api.exception', 'class' => 'Civi\API\Event\ExceptionEvent'],
    ];
    $this->assertEquals($expectedEventSequence, $this->actualEventSequence);
    $this->assertEquals('Frobnication encountered an exception', $result['error_message']);
    $this->assertEquals(1, $result['is_error']);
  }

  // TODO testAuthorizeException, testPrepareException, testRespondException

  /**
   * Create an API provider for entity "Widget" with action "frobnicate".
   *
   * @return Provider\ProviderInterface
   */
  public function createWidgetFrobnicateProvider() {
    $provider = new \Civi\API\Provider\AdhocProvider(self::MOCK_VERSION, 'Widget');
    $provider->addAction('frobnicate', 'access CiviCRM', function ($apiRequest) {
      if (!empty($apiRequest['params']['exception'])) {
        throw new \Exception($apiRequest['params']['exception']);
      }
      return civicrm_api3_create_success([98 => 'frob']);
    });
    return $provider;
  }

  /**
   * Add listeners to $this->dispatcher which record each invocation of $monitoredEvents
   * in $this->actualEventSequence.
   *
   * @param array $monitoredEvents
   *   List of event names.
   *
   */
  public function monitorEvents($monitoredEvents) {
    foreach ($monitoredEvents as $monitoredEvent) {
      $test = $this;
      $this->dispatcher->addListener($monitoredEvent, function ($event) use ($monitoredEvent, &$test) {
        $test->actualEventSequence[] = [
          'name' => $monitoredEvent,
          'class' => get_class($event),
        ];
      }, 2 * Events::W_EARLY);
    }
  }

}
