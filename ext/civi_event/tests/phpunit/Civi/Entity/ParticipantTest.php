<?php

namespace Civi\Entity;

use Civi\Api4\Participant;
use Civi\Test;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\EventTestTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use CRM_Event_ExtensionUtil as E;
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
class ParticipantTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use EventTestTrait;

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
  public function testGetDuplicates(): void {
    $fields = Participant::getFields()
      ->setAction('getduplicates')
      ->execute()
      ->indexBy('name');
    $this->assertTrue($fields['contact_id']['required']);
    $this->assertTrue($fields['event_id']['required']);

    $this->eventCreateUnpaid();
    $this->createTestEntity('Contact', ['first_name' => 'Donald', 'last_name' => 'Duck', 'contact_type' => 'Individual']);
    $duplicate = Participant::getDuplicates(FALSE)
      ->setValues([
        'event_id' => $this->ids['Event']['event'],
        'contact_id' => $this->ids['Contact']['default'],
      ])->execute();
    $this->assertCount(0, $duplicate);

    $this->createTestEntity('Participant', [
      'event_id' => $this->ids['Event']['event'],
      'contact_id' => $this->ids['Contact']['default'],
    ]);

    $duplicate = Participant::getDuplicates(FALSE)
      ->setValues([
        'event_id' => $this->ids['Event']['event'],
        'contact_id' => $this->ids['Contact']['default'],
      ])->execute();
    $this->assertCount(1, $duplicate);
  }

}
