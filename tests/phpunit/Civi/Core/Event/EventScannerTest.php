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

namespace Civi\Core\Event;

use Civi\Core\Attribute\CiviEventListener;
use Civi\Core\ClassScanner;
use Civi\Core\Container;
use Civi\Core\HookInterface;
use Civi\Schema\EntityRepository;

/**
 * Tests for EventScanner and EventScannerPass listener discovery.
 *
 * @group headless
 */
class EventScannerTest extends \CiviUnitTestCase {

  /**
   * @var string[]
   */
  private array $scannedFixtures = [];

  protected function tearDown(): void {
    \Civi::dispatcher()->removeListener('hook_civicrm_scanClasses', [$this, 'addScannedFixtures']);
    ClassScanner::cache('index')->flush();
    EntityRepository::flush();
    parent::tearDown();
  }

  /**
   * Each entity listener method in core is registered once per entity.
   */
  public function testCoreEntityListenersAreRegisteredOnce(): void {
    $entityEvents = array_filter(array_keys(\Civi::dispatcher()->getListeners()), fn($event) => str_contains($event, '::'));
    $this->assertNotEmpty($entityEvents);
    foreach ($entityEvents as $event) {
      $this->assertListenerMethodsUnique($event);
    }
  }

  /**
   * A scanned subclass of a core BAO does not register the parent's listeners a second time.
   */
  public function testBaoSubclassDoesNotDuplicateParentListeners(): void {
    $listeners = $this->compileListenerMaps([EventScannerTest_BAO_EmailSubclass::class]);
    $this->assertNotEmpty($listeners['hook_civicrm_pre::Email']);
    foreach (['hook_civicrm_pre::Email', 'hook_civicrm_post::Email'] as $event) {
      $duplicates = array_keys(array_filter(array_count_values($listeners[$event] ?? []), fn($count) => $count > 1));
      $this->assertEquals([], $duplicates, "Listeners registered more than once for $event");
    }
  }

  /**
   * Listeners from abstract parent classes and traits are inherited by concrete classes,
   * but not duplicated onto further concrete subclasses.
   */
  public function testAbstractParentAndTraitListeners(): void {
    $concreteMap = EventScanner::findListeners(EventScannerTestConcreteListener::class);
    $this->assertEquals([
      'civi.test.abstract_event' => [['onAbstractEvent', 0]],
      'civi.test.trait_event' => [['onTraitEvent', 0]],
      'civi.test.concrete_event' => [['onConcreteEvent', 0]],
    ], $concreteMap);

    $childMap = EventScanner::findListeners(EventScannerTestChildOfConcreteListener::class);
    $this->assertEquals([], $childMap);
  }

  /**
   * Legacy entity BAOs implementing HookInterface are registered without class scanning,
   * whereas #[CiviEventListener] requires class scanning.
   */
  public function testUnscannedEntityBaoListeners(): void {
    $this->hookClass->setHook('civicrm_entityTypes', function (array &$entityTypes): void {
      $entityTypes['EventScannerLegacyThing'] = [
        'name' => 'EventScannerLegacyThing',
        'table' => 'civicrm_event_scanner_legacy_thing',
        'class' => EventScannerTest_DAO_LegacyThing::class,
        'module' => 'civicrm',
        'getInfo' => fn() => ['title' => 'Event Scanner Legacy Thing'],
        'getFields' => fn() => [
          'id' => [
            'sql_type' => 'int unsigned',
            'input_type' => 'Number',
            'required' => TRUE,
            'primary_key' => TRUE,
            'auto_increment' => TRUE,
          ],
        ],
      ];
      $entityTypes['EventScannerAttributeThing'] = [
        'name' => 'EventScannerAttributeThing',
        'table' => 'civicrm_event_scanner_attribute_thing',
        'class' => EventScannerTest_DAO_AttributeThing::class,
        'module' => 'civicrm',
        'getInfo' => fn() => ['title' => 'Event Scanner Attribute Thing'],
        'getFields' => fn() => [
          'id' => [
            'sql_type' => 'int unsigned',
            'input_type' => 'Number',
            'required' => TRUE,
            'primary_key' => TRUE,
            'auto_increment' => TRUE,
          ],
        ],
      ];
    });
    EntityRepository::flush();

    // Without scan-classes, LegacyThing (HookInterface) is registered, AttributeThing (#[CiviEventListener]) is not.
    $listeners = $this->compileListenerMaps([]);
    $this->assertEquals(
      [EventScannerTest_BAO_LegacyThing::class . '::self_hook_civicrm_pre'],
      $listeners['hook_civicrm_pre::EventScannerLegacyThing'] ?? []
    );
    $this->assertArrayNotHasKey('hook_civicrm_pre::EventScannerAttributeThing', $listeners);

    // With scan-classes, AttributeThing is also registered.
    $scannedListeners = $this->compileListenerMaps([EventScannerTest_BAO_AttributeThing::class]);
    $this->assertEquals(
      [EventScannerTest_BAO_AttributeThing::class . '::onPre'],
      $scannedListeners['hook_civicrm_pre::EventScannerAttributeThing'] ?? []
    );
  }

  /**
   * A scanned DAO subclass without an entity of its own does not break the container
   * or register empty-entity listeners.
   */
  public function testDaoSubclassWithoutEntityDoesNotBreakContainer(): void {
    $listeners = $this->compileListenerMaps([EventScannerTest_BAO_NoEntity::class]);
    $this->assertNotEmpty($listeners);
    $this->assertArrayNotHasKey('hook_civicrm_pre::', $listeners);
    $this->assertArrayNotHasKey('hook_civicrm_post::', $listeners);
  }

  public function addScannedFixtures(GenericHookEvent $event): void {
    $event->classes = array_merge($event->classes, $this->scannedFixtures);
  }

  /**
   * Compile a container whose class scan includes the given classes.
   *
   * @param string[] $classes
   * @return array
   *   Methods registered on the dispatcher via addListenerMap, as
   *   [eventName => ['DeclaringClass::method', ...]].
   */
  private function compileListenerMaps(array $classes): array {
    $this->scannedFixtures = $classes;
    \Civi::dispatcher()->addListener('hook_civicrm_scanClasses', [$this, 'addScannedFixtures']);
    ClassScanner::cache('index')->flush();
    $container = (new Container())->createContainer();
    $container->compile();

    $listeners = [];
    foreach ($container->getDefinition('dispatcher')->getMethodCalls() as [$method, $args]) {
      if ($method === 'addListenerMap') {
        [$target, $listenerMap] = $args;
        foreach ($listenerMap as $event => $callbacks) {
          foreach ($callbacks as $callback) {
            $reflection = new \ReflectionMethod($target, $callback[0]);
            $listeners[$event][] = $reflection->class . '::' . $reflection->name;
          }
        }
      }
    }
    return $listeners;
  }

  private function assertListenerMethodsUnique(string $event): void {
    $methods = [];
    foreach (\Civi::dispatcher()->getListeners($event) as $listener) {
      if (is_array($listener) && is_string($listener[0])) {
        $method = new \ReflectionMethod($listener[0], $listener[1]);
        $methods[] = $method->class . '::' . $method->name;
      }
    }
    $duplicates = array_keys(array_filter(array_count_values($methods), fn($count) => $count > 1));
    $this->assertEquals([], $duplicates, "Listeners registered more than once for $event");
  }

}

class EventScannerTest_BAO_EmailSubclass extends \CRM_Core_BAO_Email {
}

class EventScannerTest_DAO_LegacyThing extends \CRM_Core_DAO_Base {
}

class EventScannerTest_BAO_LegacyThing extends EventScannerTest_DAO_LegacyThing implements HookInterface {

  public static function self_hook_civicrm_pre(PreEvent $event): void {
  }

}

class EventScannerTest_DAO_AttributeThing extends \CRM_Core_DAO_Base {
}

class EventScannerTest_BAO_AttributeThing extends EventScannerTest_DAO_AttributeThing {

  #[CiviEventListener(event: 'hook_civicrm_pre::{self}')]
  public static function onPre(PreEvent $event): void {
  }

}

class EventScannerTest_BAO_NoEntity extends \CRM_Core_DAO implements HookInterface {

  public static function self_hook_civicrm_pre(PreEvent $event): void {
  }

  #[CiviEventListener(event: 'hook_civicrm_post::{self}')]
  public static function onPost(PostEvent $event): void {
  }

}

trait EventScannerTestListenerTrait {

  #[CiviEventListener(event: 'civi.test.trait_event')]
  public static function onTraitEvent(GenericHookEvent $event): void {
  }

}

abstract class EventScannerTestAbstractBaseListener {

  #[CiviEventListener(event: 'civi.test.abstract_event')]
  public static function onAbstractEvent(GenericHookEvent $event): void {
  }

}

class EventScannerTestConcreteListener extends EventScannerTestAbstractBaseListener {
  use EventScannerTestListenerTrait;

  #[CiviEventListener(event: 'civi.test.concrete_event')]
  public static function onConcreteEvent(GenericHookEvent $event): void {
  }

}

class EventScannerTestChildOfConcreteListener extends EventScannerTestConcreteListener {
}
