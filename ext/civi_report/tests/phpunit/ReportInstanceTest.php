<?php

declare(strict_types = 1);

namespace Civi\ext\civi_report\tests\phpunit;

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\ReportInstance;
use Civi\Test;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use CRM_Core_Config;
use PHPUnit\Framework\TestCase;

/**
 * Test CiviReportInstance functionality.
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
class ReportInstanceTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

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
   * Test that configured permissions are applied when retrieving a report instance.
   *
   * @return void
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testPermissions(): void {
    $instance = ReportInstance::get(FALSE)
      ->addWhere('report_id', '=', 'contact/summary')
      ->execute()->first();

    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['view event info'];
    try {
      ReportInstance::get()
        ->addWhere('id', '=', $instance['id'])
        ->execute();
      $this->fail('Expected an exception as permissions do not permit access here');
    }
    catch (UnauthorizedException $e) {
    }
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $permittedRetrieval = ReportInstance::get()
      ->addWhere('id', '=', $instance['id'])
      ->execute();
    $this->assertCount(0, $permittedRetrieval);

    ReportInstance::update(FALSE)->addWhere('id', '=', $instance['id'])
      ->setValues(['permission' => 'access CiviCRM'])->execute();

    $permittedRetrieval = ReportInstance::get()
      ->addWhere('id', '=', $instance['id'])
      ->execute();
    $this->assertCount(1, $permittedRetrieval);
  }

}
