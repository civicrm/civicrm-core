<?php

namespace Civi\Core\Event;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ServiceListenerTest extends \CiviUnitTestCase {

  public function tearDown(): void {
    ServiceListenerTestExample::$notes = [];
    parent::tearDown();
  }

  public function testDispatch() {
    $changeMe = $rand = rand(0, 16384);

    $container = new ContainerBuilder();
    $container->setDefinition('test.svlt', new Definition(ServiceListenerTestExample::class, [$rand]))
      ->setPublic(TRUE);

    $d = \Civi::dispatcher();
    $d->addListener('hook_civicrm_svlt', (new ServiceListener(['test.svlt', 'onSvlt']))->setContainer($container));

    // Baseline
    $this->assertEquals([], ServiceListenerTestExample::$notes);
    $this->assertEquals($changeMe, $rand);

    // First call - instantiate and run
    $d->dispatch('hook_civicrm_svlt', GenericHookEvent::create(['foo' => &$changeMe]));
    $this->assertEquals($changeMe, 1 + $rand);
    $this->assertEquals(["construct($rand)", "onSvlt($rand)"],
      ServiceListenerTestExample::$notes);

    // Second call - reuse and run
    $d->dispatch('hook_civicrm_svlt', GenericHookEvent::create(['foo' => &$changeMe]));
    $this->assertEquals($changeMe, 2 + $rand);
    $this->assertEquals(["construct($rand)", "onSvlt($rand)", "onSvlt(" . ($rand + 1) . ")"],
      ServiceListenerTestExample::$notes);
  }

}

class ServiceListenerTestExample {

  /**
   * Free-form list of strings.
   *
   * @var array
   */
  public static $notes = [];

  public function __construct($rand) {
    self::$notes[] = "construct($rand)";
  }

  public function onSvlt(GenericHookEvent $e) {
    self::$notes[] = "onSvlt({$e->foo})";
    $e->foo++;
  }

}
