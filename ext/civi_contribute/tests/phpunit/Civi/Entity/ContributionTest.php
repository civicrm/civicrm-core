<?php

namespace Civi\Entity;

use Civi\Api4\Contribution;
use Civi\Test;
use Civi\Test\EntityTrait;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Core\HookInterface;
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
class ContributionTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

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
   * Test duplicates fail with an appropriate message.
   *
   * @dataProvider duplicateCheckProvider
   */
  public function testDuplicateCheck(array $duplicateValues, string $expectedMessage): void {
    $values = [
      'contact_id' => $this->createTestEntity('Contact', ['first_name' => 'Bob', 'contact_type' => 'Individual'])['id'],
      'trxn_id' => 'abc',
      'invoice_id' => 'xyz',
      'total_amount' => 20,
      'financial_type_id:name' => 'Donation',
    ];
    $this->createTestEntity('Contribution', $values, 'original');

    try {
      Contribution::create(FALSE)
        ->setValues(array_merge($values, $duplicateValues))
        ->execute();
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertEquals(
        sprintf($expectedMessage, $this->ids['Contribution']['original']),
        $e->getMessage()
      );
      return;
    }
    $this->fail('We should have had an exception');
  }

  public static function duplicateCheckProvider(): array {
    return [
      'trxn_id match' => [
        ['invoice_id' => 'different_invoice'],
        'Duplicate error - existing contribution record(s) have a matching Transaction ID or Invoice Reference. Contribution record ID(s) are: [id: %s, trxn_id: abc]',
      ],
      'invoice_id match' => [
        ['trxn_id' => 'different_trxn'],
        'Duplicate error - existing contribution record(s) have a matching Transaction ID or Invoice Reference. Contribution record ID(s) are: [id: %s, invoice_id: xyz]',
      ],
      'trxn_id and invoice_id match' => [
        [],
        'Duplicate error - existing contribution record(s) have a matching Transaction ID or Invoice Reference. Contribution record ID(s) are: [id: %s, trxn_id: abc, invoice_id: xyz]',
      ],
    ];
  }

}
