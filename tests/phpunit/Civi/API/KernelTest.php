<?php
namespace Civi\API;

use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 */
class KernelTest extends \CiviUnitTestCase {
  const MOCK_VERSION = 3;

  /**
   * @var array(int => array('name' => string $eventName, 'type' => string $className))
   */
  public $actualEventSequence;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  public $dispatcher;

  /**
   * @var Kernel
   */
  public $kernel;

  protected function setUp() {
    parent::setUp();
    $this->actualEventSequence = array();
    $this->dispatcher = new EventDispatcher();
    $this->monitorEvents(Events::allEvents());
    $this->kernel = new Kernel($this->dispatcher);
  }

  public function testNormalEvents() {
    $this->kernel->registerApiProvider($this->createWidgetFrobnicateProvider());
    $result = $this->kernel->run('Widget', 'frobnicate', array(
      'version' => self::MOCK_VERSION,
    ));

    $expectedEventSequence = array(
      array('name' => Events::RESOLVE, 'class' => 'Civi\API\Event\ResolveEvent'),
      array('name' => Events::AUTHORIZE, 'class' => 'Civi\API\Event\AuthorizeEvent'),
      array('name' => Events::PREPARE, 'class' => 'Civi\API\Event\PrepareEvent'),
      array('name' => Events::RESPOND, 'class' => 'Civi\API\Event\RespondEvent'),
    );
    $this->assertEquals($expectedEventSequence, $this->actualEventSequence);
    $this->assertEquals('frob', $result['values'][98]);
  }

  public function testResolveException() {
    $test = $this;
    $this->dispatcher->addListener(Events::RESOLVE, function () {
      throw new \API_Exception('Oh My God', 'omg', array('the' => 'badzes'));
    }, Events::W_EARLY);
    $this->dispatcher->addListener(Events::EXCEPTION, function (\Civi\API\Event\ExceptionEvent $event) use ($test) {
      $test->assertEquals('Oh My God', $event->getException()->getMessage());
    });

    $this->kernel->registerApiProvider($this->createWidgetFrobnicateProvider());
    $result = $this->kernel->run('Widget', 'frobnicate', array(
      'version' => self::MOCK_VERSION,
    ));

    $expectedEventSequence = array(
      array('name' => Events::RESOLVE, 'class' => 'Civi\API\Event\ResolveEvent'),
      array('name' => Events::EXCEPTION, 'class' => 'Civi\API\Event\ExceptionEvent'),
    );
    $this->assertEquals($expectedEventSequence, $this->actualEventSequence);
    $this->assertEquals('Oh My God', $result['error_message']);
    $this->assertEquals('omg', $result['error_code']);
    $this->assertEquals('badzes', $result['the']);
  }

  // TODO testAuthorizeException, testPrepareException, testRespondException, testExceptionException

  /**
   * Create an API provider for entity "Widget" with action "frobnicate".
   *
   * @return Provider\ProviderInterface
   */
  public function createWidgetFrobnicateProvider() {
    $provider = new \Civi\API\Provider\AdhocProvider(self::MOCK_VERSION, 'Widget');
    $provider->addAction('frobnicate', 'access CiviCRM', function ($apiRequest) {
      return civicrm_api3_create_success(array(98 => 'frob'));
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
        $test->actualEventSequence[] = array(
          'name' => $monitoredEvent,
          'class' => get_class($event),
        );
      }, 2 * Events::W_EARLY);
    }
  }

}
