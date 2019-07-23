<?php
namespace Civi\Core\Event;

class GenericHookEventTest extends \CiviUnitTestCase {

  public function tearDown() {
    \CRM_Utils_Hook::singleton()->reset();
    parent::tearDown();
  }

  public function testConstructParams() {
    $event = GenericHookEvent::create([
      'ab' => 123,
      'cd' => ['foo' => 'bar'],
      'nothingNull' => NULL,
      'nothingZero' => 0,
    ]);
    $this->assertEquals(123, $event->ab);
    $this->assertEquals('bar', $event->cd['foo']);
    $this->assertTrue($event->hasField('ab'));
    $this->assertTrue(isset($event->ab));
    $this->assertFalse($event->hasField('abc'));
    $this->assertFalse(isset($event->abc));
    $this->assertTrue(!isset($event->nothingNull) && empty($event->nothingNull));
    $this->assertTrue(isset($event->nothingZero) && empty($event->nothingZero));
  }

  public function testConstructOrdered() {
    $event = GenericHookEvent::createOrdered(
      ['alpha', 'beta', 'nothingNull', 'nothingZero'],
      [456, ['whiz' => 'bang'], NULL, 0, \CRM_Utils_Hook::$_nullObject]
    );
    $this->assertEquals(456, $event->alpha);
    $this->assertEquals('bang', $event->beta['whiz']);
    $this->assertTrue($event->hasField('alpha'));
    $this->assertTrue(isset($event->alpha));
    $this->assertFalse($event->hasField('ab'));
    $this->assertFalse(isset($event->ab));
    $this->assertTrue(!isset($event->nothingNull) && empty($event->nothingNull));
    $this->assertTrue(isset($event->nothingZero) && empty($event->nothingZero));
    $this->assertEquals(4, count($event->getHookValues()));
  }

  public function testDispatch() {
    \CRM_Utils_Hook::singleton()->setHook('civicrm_ghet',
      [$this, 'hook_civicrm_ghet']);
    \Civi::service('dispatcher')->addListener('hook_civicrm_ghet',
      [$this, 'onGhet']);

    $roString = 'readonly';
    $rwString = 'readwrite';
    $roArray = ['readonly'];
    $rwArray = ['readwrite'];
    $plainObj = new \stdClass();
    $refObj = new \stdClass();

    $returnValue = $this->hookStub($roString, $rwString, $roArray, $rwArray, $plainObj, $refObj);

    $this->assertEquals('readonly', $roString);
    $this->assertEquals('readwrite added-string-via-event added-string-via-hook', $rwString);
    $this->assertEquals(['readonly'], $roArray);
    $this->assertEquals(['readwrite', 'added-to-array-via-event', 'added-to-array-via-hook'], $rwArray);
    $this->assertEquals('added-to-object-via-hook', $plainObj->prop1);
    $this->assertEquals('added-to-object-via-hook', $refObj->prop2);
    $this->assertEquals(['early-running-result', 'late-running-result'], $returnValue);
  }

  /**
   * Fire a hook. This stub follows the same coding convention as
   * CRM_Utils_Hook::*(). This ensures that the coding convention is valid.
   *
   * @param $roString
   * @param $rwString
   * @param $roArray
   * @param $rwArray
   * @param $plainObj
   * @param $refObj
   * @return mixed
   */
  public function hookStub($roString, &$rwString, $roArray, &$rwArray, $plainObj, &$refObj) {
    return \CRM_Utils_Hook::singleton()->invoke(
      ['roString', 'rwString', 'roArray', 'rwArray', 'plainObj', 'refObj'],
      $roString, $rwString, $roArray, $rwArray, $plainObj, $refObj,
      'civicrm_ghet'
    );
  }

  public function hook_civicrm_ghet(&$roString, &$rwString, &$roArray, &$rwArray, $plainObj, &$refObj) {
    $roString .= 'changes should not propagate back';
    $rwString .= ' added-string-via-hook';
    $roArray[] = 'changes should not propagate back';
    $rwArray[] = 'added-to-array-via-hook';
    $plainObj->prop1 = 'added-to-object-via-hook';
    $refObj->prop2 = 'added-to-object-via-hook';
    return ['late-running-result'];
  }

  public function onGhet(GenericHookEvent $e) {
    $e->roString .= 'changes should not propagate back';
    $e->rwString .= ' added-string-via-event';
    $e->roArray[] = 'changes should not propagate back';
    $e->rwArray[] = 'added-to-array-via-event';
    $e->plainObj->prop1 = 'added-to-object-via-event';
    $e->refObj->prop2 = 'added-to-object-via-event';
    $e->addReturnValues(['early-running-result']);
  }

}
