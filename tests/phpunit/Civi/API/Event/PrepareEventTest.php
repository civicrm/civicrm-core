<?php
namespace Civi\API\Event;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Civi\API\Events;
use Civi\API\Kernel;

/**
 */
class PrepareEventTest extends \CiviUnitTestCase {
  const MOCK_VERSION = 3;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  public $dispatcher;

  /**
   * @var \Civi\API\Kernel
   */
  public $kernel;

  protected function setUp() {
    parent::setUp();
    $this->dispatcher = new EventDispatcher();
    $this->kernel = new Kernel($this->dispatcher);
  }

  public function getPrepareExamples() {
    $apiCall = ['Widget', 'frobnicate', ['id' => 98, 'whimsy' => 'green', 'version' => self::MOCK_VERSION]];

    $exs = [];

    $exs[] = ['onPrepare_null', $apiCall, [98 => 'frob[green]']];
    $exs[] = ['onPrepare_wrapApi', $apiCall, [98 => 'frob[go green] and frob[who green]']];

    return $exs;
  }

  /**
   * @param string $onPrepare
   *   Name of a function (within this test class) to register for 'civi.api.prepare' event.
   * @param array $inputApiCall
   * @param array $expectResult
   * @dataProvider getPrepareExamples
   */
  public function testOnPrepare($onPrepare, $inputApiCall, $expectResult) {
    $this->dispatcher->addListener(Events::PREPARE, [$this, $onPrepare]);
    $this->kernel->registerApiProvider($this->createWidgetFrobnicateProvider());
    $result = call_user_func_array([$this->kernel, 'run'], $inputApiCall);
    $this->assertEquals($expectResult, $result['values']);
  }

  /**
   * Create an API provider for entity "Widget" with action "frobnicate".
   *
   * @return \Civi\API\Provider\ProviderInterface
   */
  public function createWidgetFrobnicateProvider() {
    $provider = new \Civi\API\Provider\AdhocProvider(self::MOCK_VERSION, 'Widget');
    $provider->addAction('frobnicate', 'access CiviCRM', function ($apiRequest) {
      return civicrm_api3_create_success([
        $apiRequest['params']['id'] => sprintf("frob[%s]", $apiRequest['params']['whimsy']),
      ]);
    });
    return $provider;
  }

  /**
   * Baseline - run API call without any manipulation of the result
   *
   * @param \Civi\API\Event\PrepareEvent $e
   */
  public function onPrepare_null(PrepareEvent $e) {
    // Nothing to do!
  }

  /**
   * Wrap the API call. The inputs are altered; the call is run twice; and
   * the results are combined.
   *
   * @param \Civi\API\Event\PrepareEvent $e
   */
  public function onPrepare_wrapApi(PrepareEvent $e) {
    if ($e->getApiRequestSig() === '3.widget.frobnicate') {
      $e->wrapApi(function($apiRequest, $continue) {
        $apiRequestA = $apiRequest;
        $apiRequestB = $apiRequest;
        $apiRequestA['params']['whimsy'] = 'go ' . $apiRequestA['params']['whimsy'];
        $apiRequestB['params']['whimsy'] = 'who ' . $apiRequestB['params']['whimsy'];
        $resultA = $continue($apiRequestA);
        $resultB = $continue($apiRequestB);
        $result = [];
        // Concatenate the separate results and form one result.
        foreach (array_keys($resultA['values']) as $id) {
          $result[$id] = $resultA['values'][$id] . ' and ' . $resultB['values'][$id];
        }
        return civicrm_api3_create_success($result);
      });
    }
  }

}
