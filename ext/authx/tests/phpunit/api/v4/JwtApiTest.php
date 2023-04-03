<?php

namespace api\v4\Authx;

use Civi\Api4\JWT;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test JWT API methods
 * @group headless
 */
class JwtApiTest extends TestCase implements HeadlessInterface, TransactionalInterface {

  use \Civi\Test\Api4TestTrait;
  use \Civi\Test\Api3TestTrait;
  use \Civi\Test\ContactTestTrait;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function testJWTGenrateToken(): void {
    $this->_apiversion = 4;
    $contactRecord = $this->createTestRecord('Contact', ['contact_type' => 'Individual']);
    $this->createLoggedInUser();
    $this->setPermissions([
      'access CiviCRM',
    ]);
    try {
      JWT::create()->setContactId($contactRecord['id'])->execute();
      $this->fail('JWT Should not be created as permission is not granted');
    }
    catch (\Exception $e) {
    }
    $this->setPermissions([
      'access CiviCRM',
      'generate JWT',
    ]);
    $jwt = JWT::create()->setContactId($contactRecord['id'])->execute();
    $this->assertNotEmpty($jwt[0]['token']);
  }

  public function testJWTValidation(): void {
    $this->_apiversion = 4;
    $contactRecord = $this->createTestRecord('Contact', ['contact_type' => 'Individual']);
    $this->createLoggedInUser();
    $this->setPermissions([
      'access CiviCRM',
      'generate JWT',
    ]);
    $jwt = JWT::create()->setContactId($contactRecord['id'])->execute();
    $validate = JWT::validate()->setToken($jwt[0]['token'])->execute();
    $this->assertEquals('jwt', $validate[0]['credType']);
    $this->assertEquals($contactRecord['id'], $validate[0]['contactId']);
    $this->assertEquals('cid:' . $contactRecord['id'], $validate[0]['jwt']['sub']);
  }

  /**
   * Test that the JWT does not validate if expired
   */
  public function testExpiredJWTValidation(): void {
    $this->expectException(\Civi\Authx\AuthxException::class);
    $this->expectExceptionMessage('Expired token');
    $this->_apiversion = 4;
    $contactRecord = $this->createTestRecord('Contact', ['contact_type' => 'Individual']);
    $this->createLoggedInUser();
    $this->setPermissions([
      'access CiviCRM',
      'generate JWT',
    ]);
    $jwt = JWT::create()->setContactId($contactRecord['id'])->setTtl(5)->execute();
    sleep(10);
    $validate = JWT::validate()->setToken($jwt[0]['token'])->execute();
  }

  /**
   * Set ACL permissions, overwriting any existing ones.
   *
   * @param array $permissions
   *   Array of permissions e.g ['access CiviCRM','access CiviContribute'],
   */
  protected function setPermissions(array $permissions): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = $permissions;
  }

}
