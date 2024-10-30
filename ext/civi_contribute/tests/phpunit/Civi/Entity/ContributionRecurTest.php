<?php

namespace Civi\Entity;

use Civi\Api4\ContributionRecur;
use Civi\Test;
use Civi\Test\EntityTrait;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
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
class ContributionRecurTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use EntityTrait;

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
  public function setUpHeadless(): CiviEnvBuilder {
    return Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testDuplicateCheck(): void {
    $values = [
      'contact_id' => $this->createTestEntity('Contact', ['first_name' => 'Bob', 'contact_type' => 'Individual'])['id'],
      'trxn_id' => 'abc',
      'amount' => 20,
    ];
    ContributionRecur::create(FALSE)
      ->setValues($values)
      ->execute();
    try {
      ContributionRecur::create(FALSE)
        ->setValues($values)
        ->execute();
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertEquals('Found matching recurring contribution(s): 1', $e->getMessage());
      return;
    }
    $this->fail('We should have had an exception');
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testCurrencyFill(): void {
    $values = [
      'contact_id' => $this->createTestEntity('Contact', ['first_name' => 'Bob', 'contact_type' => 'Individual'])['id'],
      'trxn_id' => 'abc',
      'amount' => 20,
    ];
    $recur = ContributionRecur::create(FALSE)
      ->setValues($values)
      ->execute()->first();
    $this->assertEquals('USD', $recur['currency']);
  }

}
