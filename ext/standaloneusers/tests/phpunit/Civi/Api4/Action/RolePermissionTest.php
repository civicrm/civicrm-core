<?php
namespace Civi\Api4\Action;

use Civi\Api4\Role;
use Civi\Api4\RolePermission;
use Civi\Api4\Utils\CoreUtil;
use Civi\Test\HeadlessInterface;

/**
 * @group headless
 */
class RolePermissionTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function testMetadata(): void {
    $this->assertEquals('parent', CoreUtil::getInfoItem('RolePermission', 'parent_field'));
  }

  /**
   * Test batch-updating role permissions
   */
  public function testRolePermissionUpdate() {
    $role1 = uniqid(1);
    $role2 = uniqid(2);
    Role::create(FALSE)
      ->addValue('name', $role1)
      ->addValue('label', $role1)
      ->addValue('permissions', [
        'access AJAX API',
        'access CiviCRM',
        'access Contact Dashboard',
        'access uploaded files',
        'add contacts',
        'view all contacts',
        'edit all contacts',
      ])
      ->execute();
    Role::create(FALSE)
      ->addValue('name', $role2)
      ->addValue('label', $role2)
      ->addValue('permissions', [
        'administer CiviCRM',
      ])
      ->execute();
    $rolePermissions = RolePermission::get(FALSE)
      ->execute()
      ->indexBy('name');

    $this->assertTrue($rolePermissions['view all contacts']["granted_$role1"]);
    $this->assertFalse($rolePermissions['view my contact']["granted_$role1"]);
    $this->assertTrue($rolePermissions['view my contact']["implied_$role1"]);
    $this->assertFalse($rolePermissions['administer CiviCRM']["granted_$role1"]);
    $this->assertFalse($rolePermissions['view all contacts']["granted_$role2"]);
    $this->assertFalse($rolePermissions['access CiviCRM']["granted_$role2"]);
    $this->assertTrue($rolePermissions['access CiviCRM']["implied_$role2"]);
    $this->assertTrue($rolePermissions['edit message templates']["implied_$role2"]);

    RolePermission::save(FALSE)
      ->addRecord(['name' => 'view all contacts', "granted_$role1" => FALSE, "granted_$role2" => TRUE])
      ->addRecord(['name' => 'administer CiviCRM', "granted_$role1" => FALSE])
      ->execute();

    $rolePermissions = RolePermission::get(FALSE)
      ->execute()
      ->indexBy('name');

    $this->assertFalse($rolePermissions['view all contacts']["granted_$role1"]);
    $this->assertFalse($rolePermissions['view my contact']["granted_$role1"]);
    $this->assertTrue($rolePermissions['view my contact']["implied_$role1"]);
    $this->assertFalse($rolePermissions['administer CiviCRM']["granted_$role1"]);
    $this->assertTrue($rolePermissions['view all contacts']["granted_$role2"]);
    $this->assertFalse($rolePermissions['view my contact']["granted_$role2"]);
    $this->assertTrue($rolePermissions['view my contact']["implied_$role2"]);
    $this->assertFalse($rolePermissions['access CiviCRM']["granted_$role2"]);
    $this->assertTrue($rolePermissions['access CiviCRM']["implied_$role2"]);
  }

}
