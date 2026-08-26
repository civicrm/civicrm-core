<?php

use CRM_AfformAdmin_ExtensionUtil as E;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;

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
class CiviAfformAdminAfformAdminMetaTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface {

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

  public function setUp():void {
    parent::setUp();
  }

  public function tearDown():void {
    parent::tearDown();
  }

  /**
   * Verify that getLocales works without php error.
   */
  public function testAdminSettings():void {
    $adminSettings = \Civi\AfformAdmin\AfformAdminMeta::getAdminSettings();
    $this->assertSame([], $adminSettings['locales']);
  }

  /**
   * A dynamic foreign key needs its table-to-entity map and the name of the field
   * that controls it, so the form builder can resolve which entity it points to.
   */
  public function testDynamicForeignKeyMetadata():void {
    $fields = \Civi\AfformAdmin\AfformAdminMeta::getFields('Note');

    $this->assertEquals('entity_table', $fields['entity_id']['input_attrs']['control_field']);
    $this->assertEquals('Contact', $fields['entity_id']['dfk_entities']['civicrm_contact']);
    // A plain foreign key has no map to resolve
    $this->assertEquals('Contact', $fields['contact_id']['fk_entity']);
    $this->assertArrayNotHasKey('dfk_entities', array_filter($fields['contact_id']));
  }

}
