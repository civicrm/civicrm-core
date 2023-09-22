<?php

/**
 * Class CRM_Utils_HookTest
 * @group headless
 */
class CRM_Utils_HookTest extends CiviUnitTestCase {

  /**
   * @var object|null
   */
  public static $activeTest = NULL;

  /**
   * @var array
   */
  public $fakeModules;

  /**
   * @var array
   */
  public $log;

  /**
   * @var CRM_Utils_Hook_UnitTests
   */
  public $hook;

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
    $this->fakeModules = [
      'hooktesta',
      'hooktestb',
      'hooktestc',
      'hooktestd',
      'hookteste',
    ];
    // our goal is to test a helper in CRM_Utils_Hook, but we need a concrete class
    $this->hook = new CRM_Utils_Hook_UnitTests();
    $this->log = [];
    self::$activeTest = $this;
  }

  public function tearDown(): void {
    self::$activeTest = $this;
    parent::tearDown();
  }

  /**
   * Verify that runHooks() is reentrant by invoking one hook which calls another hooks
   */
  public function testRunHooks_reentrancy(): void {
    $arg1 = 'whatever';
    $null = NULL;
    $this->hook->runHooks($this->fakeModules, 'civicrm_testRunHooks_outer', 1, $arg1, $null, $null, $null, $null, $null);
    $this->assertEquals(
      [
        'a-outer',
        'b-outer-1',
        'a-inner',
        'b-inner',
        'b-outer-2',
        'c-outer',
      ],
      $this->log
    );
  }

  /**
   * Verify that the results of runHooks() are correctly merged
   */
  public function testRunHooks_merge(): void {
    $null = NULL;
    $result = $this->hook->runHooks($this->fakeModules, 'civicrm_testRunHooks_merge', 0, $null, $null, $null, $null, $null, $null);
    $this->assertEquals(
      [
        'from-module-a1',
        'from-module-a2',
        'from-module-e',
      ],
      $result
    );
  }

}

/* --- Library of test hook implementations --- */

/**
 * Implements hook_civicrm_testRunHooks_outer().
 */
function hooktesta_civicrm_testRunHooks_outer() {
  $test = CRM_Utils_HookTest::$activeTest;
  $test->log[] = 'a-outer';
}

function hooktestb_civicrm_testRunHooks_outer() {
  $test = CRM_Utils_HookTest::$activeTest;
  $test->log[] = 'b-outer-1';
  $null = NULL;
  $test->hook->runHooks($test->fakeModules, 'civicrm_testRunHooks_inner', 0, $null, $null, $null, $null, $null, $null);
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

/**
 * @return array
 */
function hooktesta_civicrm_testRunHooks_merge() {
  return ['from-module-a1', 'from-module-a2'];
}

// OMIT: function hooktestb_civicrm_testRunHooks_merge

/**
 * Implements hook_civicrm_testRunHooks_merge().
 */
function hooktestc_civicrm_testRunHooks_merge() {
  return [];
}

/**
 * @return null
 */
function hooktestd_civicrm_testRunHooks_merge() {
  return NULL;
}

/**
 * @return array
 */
function hookteste_civicrm_testRunHooks_merge() {
  return ['from-module-e'];
}
