<?php
namespace Civi\Afform;

use Civi\Api4\Afform;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class AfformAuthxPermissionsTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  private $formName = 'authx_perm_test_form';

  public function setUpHeadless() {
    return \Civi\Test::headless()->installMe(__DIR__)->install('org.civicrm.search_kit')->apply();
  }

  public function tearDown(): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = NULL;
    Afform::revert(FALSE)->addWhere('name', '=', $this->formName)->execute();
    parent::tearDown();
  }

  /**
   * Test that a user with 'administer afform' permission but without
   * 'all CiviCRM permissions and ACLs' cannot set or change the value of 'authx_timeout' or 'authx_redirect'.
   */
  public function testAuthxSettingsPermissions(): void {
    // 1. Create a form as superuser with checkPermissions = FALSE
    Afform::create(FALSE)
      ->addValue('name', $this->formName)
      ->addValue('title', 'Authx Perm Test Form')
      ->addValue('authx_timeout', 10)
      ->addValue('authx_redirect', 'civicrm/custom-redirect')
      ->execute();

    // 2. Set user permissions to 'administer afform' (without 'all CiviCRM permissions and ACLs')
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'administer afform',
      'manage own afform',
    ];

    // 3. User with 'administer afform' tries to change authx_timeout and authx_redirect
    Afform::save(TRUE)
      ->setRecords([
        [
          'name' => $this->formName,
          'title' => 'Authx Perm Test Form Updated',
          'authx_timeout' => 20,
          'authx_redirect' => 'civicrm/unauthorized-redirect',
        ],
      ])
      ->execute();

    // 4. Restore superuser permissions and verify authx_timeout & authx_redirect were NOT changed
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = NULL;

    $saved = Afform::get(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->execute()->single();

    $this->assertEquals('Authx Perm Test Form Updated', $saved['title']);
    $this->assertEquals(10, $saved['authx_timeout']);
    $this->assertEquals('civicrm/custom-redirect', $saved['authx_redirect']);
  }

}
