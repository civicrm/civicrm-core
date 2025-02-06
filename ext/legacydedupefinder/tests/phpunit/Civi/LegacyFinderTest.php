<?php

namespace Civi\ext\legacydedupefinder\tests\phpunit\Civi;

use Civi\Api4\Contact;
use Civi\Api4\Group;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\EntityTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;

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
class LegacyFinderTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface {

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
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp(): void {
    parent::setUp();
  }

  /**
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    Contact::delete(FALSE)->addWhere('id', 'IN', $this->ids['Contact'])
      ->setUseTrash(FALSE)
      ->execute();
    if (!empty($this->ids['Group'])) {
      Group::delete(FALSE)->addWhere('id', 'IN', $this->ids['Group'])
        ->execute();
    }
    parent::tearDown();
  }

  /**
   * Test that passing in a group ID filter allows selected merging.
   *
   * @throws \CRM_Core_Exception
   */
  public function testFinderMergeInGroup(): void {
    $this->createTestEntity('Group', ['name' => 'merge_group', 'title' => 'Group']);
    $contactValues = [
      'contact_type' => 'Individual',
      'first_name' => 'Mary',
      'last_name' => 'Contrary',
      'email_primary.email' => 'mary@example.org',
    ];
    $this->createTestEntity('Contact', $contactValues, 'mary');
    $this->createTestEntity('Contact', $contactValues, 'mary_2');
    $contactValues['email_primary.email'] = 'contrary@example.org';
    $this->createTestEntity('Contact', $contactValues, 'contrary');
    $this->createTestEntity('Contact', $contactValues, 'contrary_2');
    $this->createTestEntity('GroupContact', [
      'group_id' => $this->ids['Group']['default'],
      'contact_id' => $this->ids['Contact']['contrary_2'],
    ]);
    civicrm_api3('Job', 'process_batch_merge', ['gid' => $this->ids['Group']['default']]);
    $contacts = Contact::get(FALSE)
      ->addWhere('id', 'IN', $this->ids['Contact'])
      ->addWhere('is_deleted', '=', FALSE)
      ->execute()
      ->indexBy('id');
    $this->assertCount(3, $contacts);
    $this->assertArrayNotHasKey($this->ids['Contact']['contrary_2'], $contacts);

    // Now remove the restriction - Mary 2 should merge into Mary.
    civicrm_api3('Job', 'process_batch_merge');
    $contacts = Contact::get(FALSE)
      ->addWhere('id', 'IN', $this->ids['Contact'])
      ->addWhere('is_deleted', '=', FALSE)
      ->execute()
      ->indexBy('id');
    $this->assertCount(2, $contacts);
    $this->assertArrayNotHasKey($this->ids['Contact']['mary_2'], $contacts);
  }

  public function testFindDuplicate(): void {
    $this->createTestEntity('Contact', [
      'email_primary.email' => 'bob@example.org',
      'first_name' => 'Bob',
    ]);
    $matches = Contact::getDuplicates(FALSE)
      ->setDedupeRule('Individual.Unsupervised')
      ->setValues([
        'email_primary.email' => 'bob@example.org',
        'first_name' => 'Bob',
      ])
      ->execute();
    $this->assertCount(1, $matches);
  }

}
