<?php

use Civi\Test\Api3TestTrait;
use CRM_PrimaryKeys_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use Civi\Test\CiviEnvBuilder;
use PHPUnit\Framework\TestCase;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class BeepTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {
  use Api3TestTrait;

  /**
   * Setup used when HeadlessInterface is implemented.
   *
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   *
   * @link https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   *
   * @return \Civi\Test\CiviEnvBuilder
   *
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless():CiviEnvBuilder {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp():void {
    parent::setUp();
  }

  public function tearDown():void {
    parent::tearDown();
  }

  /**
   * Example: Test our extension can create & merge
   */
  public function testUpdate():void {
    $contact1 = $this->callAPISuccess('Contact', 'create', ['first_name' => 'Anthony', 'last_name' => 'Collins', 'contact_type' => 'Individual']);
    $contact2 = $this->callAPISuccess('Contact', 'create', ['first_name' => 'Anthony', 'last_name' => 'Collins', 'contact_type' => 'Individual']);
    $this->callAPISuccess('BeepTimes', 'create', [
      'contact_id' => $contact2,
      'recording_device_id' => 8,
      'start_time' => 'now',
      'laps' => 6,
    ]);
    $this->callAPISuccess('Contact', 'merge', ['to_keep_id' => $contact1, 'to_remove_id' => $contact2]);
  }

}
