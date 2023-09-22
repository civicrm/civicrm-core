<?php

namespace Civi\Core\Event;

use Civi\Core\Container;
use Civi\Core\HookInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Register a service (eg 'test.hssvlt') with a hook method (eg `function hook_civicrm_hssvlt()`).
 * Ensure that the hook method can alter data.
 */
class HookStyleServiceListenerTest extends \CiviUnitTestCase {

  public function tearDown(): void {
    HookStyleServiceListenerTestExample::$notes = [];
    parent::tearDown();
  }

  public function testDispatch(): void {
    $changeMe = $rand = rand(0, 16384);

    $this->useCustomContainer(function (ContainerBuilder $container) use ($rand) {
      $container->register('test.hssvlt', HookStyleServiceListenerTestExample::class)
        ->addArgument($rand)
        ->addTag('event_subscriber')
        ->setPublic(TRUE);
    });

    $d = \Civi::dispatcher();

    // Baseline
    $this->assertEquals([], HookStyleServiceListenerTestExample::$notes);
    $this->assertEquals($changeMe, $rand);

    // First call - instantiate and run
    $d->dispatch('hook_civicrm_hssvlt', GenericHookEvent::create(['foo' => &$changeMe]));
    $this->assertEquals($changeMe, 1 + $rand);
    $this->assertEquals(["construct($rand)", "fired($rand)"], HookStyleServiceListenerTestExample::$notes);

    // Second call - reuse and run
    $d->dispatch('hook_civicrm_hssvlt', GenericHookEvent::create(['foo' => &$changeMe]));
    $this->assertEquals($changeMe, 2 + $rand);
    $this->assertEquals(["construct($rand)", "fired($rand)", "fired(" . ($rand + 1) . ")"], HookStyleServiceListenerTestExample::$notes);
  }

  /**
   * Create and activate a custom service-container.
   *
   * @param callable $callback
   *   Callback function which will modify the container.
   *   function(ContainerBuilder $container)
   */
  protected function useCustomContainer(callable $callback) {
    $container = (new Container())->createContainer();
    $callback($container);
    $container->compile();
    Container::useContainer($container);
  }

}

class HookStyleServiceListenerTestExample implements HookInterface {

  /**
   * Free-form list of strings.
   *
   * @var array
   */
  public static $notes = [];

  public function __construct($rand) {
    self::$notes[] = "construct($rand)";
  }

  public function hook_civicrm_hssvlt(&$foo) {
    self::$notes[] = "fired({$foo})";
    $foo++;
  }

}
