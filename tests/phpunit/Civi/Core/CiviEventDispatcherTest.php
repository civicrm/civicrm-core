<?php

namespace Civi\Core;

use Civi\Core\Event\GenericHookEvent;

/**
 * Class CiviEventDispatcherTest
 * @package Civi\Core
 * @group headless
 */
class CiviEventDispatcherTest extends \CiviUnitTestCase {

  public function testDispatchPolicy_run(): void {
    $d = new CiviEventDispatcher(\Civi::container());
    $d->setDispatchPolicy([
      'hook_civicrm_fakeRunnable' => 'run',
    ]);
    $calls = [];
    $d->addListener('hook_civicrm_fakeRunnable', function() use (&$calls) {
      $calls['hook_civicrm_fakeRunnable'] = 1;
    });
    $d->dispatch('hook_civicrm_fakeRunnable', GenericHookEvent::create([]));
    $this->assertEquals(1, $calls['hook_civicrm_fakeRunnable']);
  }

  public function testDispatchPolicy_drop(): void {
    $d = new CiviEventDispatcher(\Civi::container());
    $d->setDispatchPolicy([
      '/^hook_civicrm_fakeDr/' => 'drop',
    ]);
    $calls = [];
    $d->addListener('hook_civicrm_fakeDroppable', function() use (&$calls) {
      $calls['hook_civicrm_fakeDroppable'] = 1;
    });
    $d->dispatch('hook_civicrm_fakeDroppable', GenericHookEvent::create([]));
    $this->assertTrue(!isset($calls['hook_civicrm_fakeDroppable']));
  }

  public function testDispatchPolicy_fail(): void {
    $d = new CiviEventDispatcher(\Civi::container());
    $d->setDispatchPolicy([
      '/^hook_civicrm_fakeFa/' => 'fail',
    ]);
    try {
      $d->dispatch('hook_civicrm_fakeFailure', GenericHookEvent::create([]));
      $this->fail('Expected exception');
    }
    catch (\Exception $e) {
      $this->assertMatchesRegularExpression(';The dispatch policy prohibits event;', $e->getMessage());
    }
  }

  /**
   * This checks whether Civi's dispatcher can be used as a backend for
   * routing other event-objects (as in PSR-14).
   */
  public function testBasicEventObject(): void {
    $d = new CiviEventDispatcher(\Civi::container());
    $count = 0;
    $d->addListener('testBasicEventObject', function($e) use (&$count) {
      $this->assertTrue(is_object($e));
      $count += $e->number;
    });
    $d->dispatch('testBasicEventObject', (object) ['number' => 100]);
    $d->dispatch('testBasicEventObject', (object) ['number' => 200]);
    $this->assertEquals(300, $count, '100 + 200 = 300.');
  }

}
