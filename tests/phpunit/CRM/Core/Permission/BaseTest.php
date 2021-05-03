<?php

/**
 * Class CRM_Core_Permission_BaseTest
 * @group headless
 */
class CRM_Core_Permission_BaseTest extends CiviUnitTestCase {

  use Civi\Test\ACLPermissionTrait;

  /**
   * @return array
   *   (0 => input to translatePermission, 1 => expected output from translatePermission)
   */
  public function translateData(): array {
    $cases = [];

    $cases[] = ['administer CiviCRM', 'administer CiviCRM'];
    $cases[] = ['cms:universal name', 'local name'];
    $cases[] = ['cms:universal name2', 'local name2'];
    $cases[] = ['cms:unknown universal name', CRM_Core_Permission::ALWAYS_DENY_PERMISSION];
    $cases[] = ['myruntime:foo', 'foo'];
    $cases[] = ['otherruntime:foo', CRM_Core_Permission::ALWAYS_DENY_PERMISSION];
    $cases[] = ['otherruntime:foo:bar', CRM_Core_Permission::ALWAYS_DENY_PERMISSION];
    $cases[] = [CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION, CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION];

    return $cases;
  }

  /**
   * @dataProvider translateData
   *
   * @param string $input
   *   The name of a permission which should be translated.
   * @param string $expected
   *   The name of an actual permission (based on translation matrix for "runtime").
   */
  public function testTranslate(string $input, string $expected): void {
    $perm = new CRM_Core_Permission_Base();
    $actual = $perm->translatePermission($input, 'myruntime', [
      'universal name' => 'local name',
      'universal name2' => 'local name2',
      'gunk' => 'gunky',
    ]);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Test that the user has the implied permission of administer CiviCRM data by virtue of having administer CiviCRM.
   */
  public function testImpliedPermission(): void {
    $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'administer CiviCRM',
    ];
    $this->assertTrue(CRM_Core_Permission::check('administer CiviCRM data'));
  }

  /**
   * Test that the super permission gives the implied permission of the whole shebang.
   */
  public function testImpliedPermissionSuperDuper(): void {
    $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'all CiviCRM permissions and ACLs',
    ];
    $this->assertTrue(CRM_Core_Permission::check('view all contacts'));
  }

}
