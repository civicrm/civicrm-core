<?php
require_once 'CiviTest/CiviUnitTestCase.php';
class CRM_Utils_HookTest extends CiviUnitTestCase {

  static $activeTest = NULL;

  var $fakeModules;

  var $log;

  function setUp() {
    parent::setUp();
    $this->fakeModules = array(
      'hooktesta',
      'hooktestb',
      'hooktestc',
    );
    // our goal is to test a helper in CRM_Utils_Hook, but we need a concrete class
    $this->hook = new CRM_Utils_Hook_UnitTests();
    $this->log = array();
    self::$activeTest = $this;
  }

  function tearDown() {
    self::$activeTest = $this;
    parent::tearDown();
  }

  /**
   * Verify that runHooks() is reentrant by invoking one hook which calls another hooks
   */
  function testRunHooks_reentrancy() {
    $arg1 = 'whatever';
    $this->hook->runHooks($this->fakeModules, 'civicrm_testRunHooks_outer', 1, $arg1, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject);
    $this->assertEquals(
      array(
        'a-outer',
        'b-outer-1',
        'a-inner',
        'b-inner',
        'b-outer-2',
        'c-outer',
      ),
      $this->log
    );
  }
}

/* --- Library of test hook implementations ---- */

function hooktesta_civicrm_testRunHooks_outer() {
  $test = CRM_Utils_HookTest::$activeTest;
  $test->log[] = 'a-outer';
}

function hooktestb_civicrm_testRunHooks_outer() {
  $test = CRM_Utils_HookTest::$activeTest;
  $test->log[] = 'b-outer-1';
  $test->hook->runHooks($test->fakeModules, 'civicrm_testRunHooks_inner', 0, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject);
  $test->log[] = 'b-outer-2';
}

function hooktestc_civicrm_testRunHooks_outer() {
  $test = CRM_Utils_HookTest::$activeTest;
  $test->log[] = 'c-outer';
}

function hooktesta_civicrm_testRunHooks_inner() {
  $test = CRM_Utils_HookTest::$activeTest;
  $test->log[] = 'a-inner';
}

function hooktestb_civicrm_testRunHooks_inner() {
  $test = CRM_Utils_HookTest::$activeTest;
  $test->log[] = 'b-inner';
}
