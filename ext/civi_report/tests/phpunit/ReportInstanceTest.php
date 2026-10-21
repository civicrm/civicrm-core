<?php

declare(strict_types = 1);

namespace Civi\ext\civi_report\tests\phpunit;

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Dashboard;
use Civi\Api4\Navigation;
use Civi\Api4\Permission;
use Civi\Api4\ReportInstance;
use Civi\Core\HookInterface;
use Civi\Test;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use CRM_Core_Config;
use CRM_Core_Permission;
use CRM_Report_BAO_ReportInstance;
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

  protected function tearDown(): void {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = NULL;
    CRM_Core_Config::singleton()->userPermissionClass->groupRoles = NULL;
    parent::tearDown();
  }

  /**
   * Test that report navigation and dashlet items use synthetic permissions.
   */
  public function testSyntheticPermissionCreation(): void {
    $instance = CRM_Report_BAO_ReportInstance::create([
      'report_id' => 'contact/summary',
      'title' => 'Synthetic Perm Test Report',
      'permission' => 'access CiviReport',
      'grouprole' => ['admin', 'editor'],
      'is_navigation' => 1,
      'addToDashboard' => 1,
    ]);

    $this->assertNotEmpty($instance->id);

    // Verify Navigation item
    $nav = Navigation::get(FALSE)
      ->addWhere('permission', '=', "@report:{$instance->id}")
      ->execute()
      ->first();
    $this->assertEquals('AND', $nav['permission_operator']);

    // Verify Dashboard item
    $dashlet = Dashboard::get(FALSE)
      ->addWhere('name', '=', "report/{$instance->id}")
      ->execute()
      ->first();
    $this->assertEquals(["@report:{$instance->id}"], (array) $dashlet['permission']);
    $this->assertEquals('AND', $dashlet['permission_operator']);
  }

  /**
   * Test evaluation of synthetic permissions for report instances.
   */
  public function testSyntheticPermissionCheckEvaluation(): void {
    $instance = CRM_Report_BAO_ReportInstance::create([
      'report_id' => 'contact/summary',
      'title' => 'Permission Evaluation Report',
      'permission' => 'access CiviReport',
      'grouprole' => ['editor', 'manager'],
    ]);

    $perm = "@report:{$instance->id}";

    // Superuser (default permissions = NULL)
    CRM_Core_Config::singleton()->userPermissionClass->permissions = NULL;
    CRM_Core_Config::singleton()->userPermissionClass->groupRoles = NULL;
    $this->assertTrue(CRM_Core_Permission::check($perm));

    // User with administer Reports
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['administer Reports'];
    CRM_Core_Config::singleton()->userPermissionClass->groupRoles = [];
    $this->assertTrue(CRM_Core_Permission::check($perm));

    // Base permission only (lacks matching role)
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviReport'];
    CRM_Core_Config::singleton()->userPermissionClass->groupRoles = ['volunteer'];
    $this->assertFalse(CRM_Core_Permission::check($perm));

    // Matching role only (lacks base permission)
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['view all contacts'];
    CRM_Core_Config::singleton()->userPermissionClass->groupRoles = ['editor'];
    $this->assertFalse(CRM_Core_Permission::check($perm));

    // Both base permission and first role
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviReport'];
    CRM_Core_Config::singleton()->userPermissionClass->groupRoles = ['editor'];
    $this->assertTrue(CRM_Core_Permission::check($perm));

    // Both base permission and second role
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviReport'];
    CRM_Core_Config::singleton()->userPermissionClass->groupRoles = ['manager'];
    $this->assertTrue(CRM_Core_Permission::check($perm));

    // Report with only roles (no base permission)
    $instanceRolesOnly = CRM_Report_BAO_ReportInstance::create([
      'report_id' => 'contact/summary',
      'title' => 'Roles Only Report',
      'permission' => NULL,
      'grouprole' => ['editor'],
    ]);
    $permRolesOnly = "@report:{$instanceRolesOnly->id}";

    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    CRM_Core_Config::singleton()->userPermissionClass->groupRoles = ['editor'];
    $this->assertTrue(CRM_Core_Permission::check($permRolesOnly));

    CRM_Core_Config::singleton()->userPermissionClass->groupRoles = ['volunteer'];
    $this->assertFalse(CRM_Core_Permission::check($permRolesOnly));

    // Report with only base permission (no roles)
    $instancePermOnly = CRM_Report_BAO_ReportInstance::create([
      'report_id' => 'contact/summary',
      'title' => 'Permission Only Report',
      'permission' => 'access CiviReport',
      'grouprole' => NULL,
    ]);
    $permPermOnly = "@report:{$instancePermOnly->id}";

    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviReport'];
    CRM_Core_Config::singleton()->userPermissionClass->groupRoles = [];
    $this->assertTrue(CRM_Core_Permission::check($permPermOnly));

    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['view all contacts'];
    $this->assertFalse(CRM_Core_Permission::check($permPermOnly));

    // Non-existent report
    $this->assertFalse(CRM_Core_Permission::check('@report:999999'));
  }

  /**
   * Test that report permissions are listed via hook_civicrm_permissionList.
   */
  public function testSyntheticPermissionList(): void {
    $instance = CRM_Report_BAO_ReportInstance::create([
      'report_id' => 'contact/summary',
      'title' => 'Perm List Test Report',
    ]);

    $perm = Permission::get(FALSE)
      ->addWhere('name', '=', "@report:$instance->id")
      ->execute()
      ->first();

    $this->assertNotEmpty($perm);
    $this->assertEquals('report', $perm['group']);
    $this->assertTrue($perm['is_synthetic']);
    $this->assertEquals('CiviReport: Perm List Test Report', $perm['title']);
    $this->assertStringEndsWith("$instance->id", $perm['description']);
  }

}
