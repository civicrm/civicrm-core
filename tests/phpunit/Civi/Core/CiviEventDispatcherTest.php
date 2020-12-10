<?php

namespace Civi\Core;

use Civi\Core\Event\GenericHookEvent;

/**
 * Class CiviEventDispatcherTest
 * @package Civi\Core
 * @group headless
 */
class CiviEventDispatcherTest extends \CiviUnitTestCase {

  public function testDispatchPolicy_run() {
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

  public function testDispatchPolicy_drop() {
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

  public function testDispatchPolicy_fail() {
    $d = new CiviEventDispatcher(\Civi::container());
    $d->setDispatchPolicy([
      '/^hook_civicrm_fakeFa/' => 'fail',
    ]);
    try {
      $d->dispatch('hook_civicrm_fakeFailure', GenericHookEvent::create([]));
      $this->fail('Expected exception');
    }
    catch (\Exception $e) {
      $this->assertRegExp(';The dispatch policy prohibits event;', $e->getMessage());
    }
  }

}
