<?php

namespace api\v4\Authx;

use Civi\Api4\AuthxCredential;
use Civi\Authx\AuthxException;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;

/**
 * Test AuthxCredential API methods
 * @group headless
 */
class AuthxCredentialTest extends TestCase implements HeadlessInterface, TransactionalInterface {

  use \Civi\Test\Api4TestTrait;
  use \Civi\Test\Api3TestTrait;
  use \Civi\Test\ContactTestTrait;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function testGenerateToken(): void {
    $this->_apiversion = 4;
    $contactRecord = $this->createTestRecord('Contact', ['contact_type' => 'Individual']);
    $this->createLoggedInUser();
    $this->setPermissions([
      'access CiviCRM',
    ]);
    try {
      AuthxCredential::create()->setContactId($contactRecord['id'])->execute();
      $this->fail('AuthxCredential Should not be created as permission is not granted');
    }
    catch (\Exception $e) {
    }
    $this->setPermissions([
      'access CiviCRM',
      'generate any authx credential',
    ]);
    $jwt = AuthxCredential::create()->setContactId($contactRecord['id'])->execute();
    $this->assertNotEmpty($jwt[0]['cred']);
  }

  public function testValidation(): void {
    $this->_apiversion = 4;
    $contactRecord = $this->createTestRecord('Contact', ['contact_type' => 'Individual']);
    $this->createLoggedInUser();
    $this->setPermissions([
      'access CiviCRM',
      'generate any authx credential',
    ]);
    $jwt = AuthxCredential::create()->setContactId($contactRecord['id'])->execute();

    $this->setPermissions([
      'access CiviCRM',
      'validate any authx credential',
    ]);
    $validate = AuthxCredential::validate()->setCred($jwt[0]['cred'])->execute();
    $this->assertEquals('jwt', $validate[0]['credType']);
    $this->assertEquals($contactRecord['id'], $validate[0]['contactId']);
    $this->assertEquals('cid:' . $contactRecord['id'], $validate[0]['jwt']['sub']);

    try {
      JWT::$timestamp = time() + 360;
      AuthxCredential::validate()->setCred($jwt[0]['cred'])->execute();
      $this->fail('Expected exception for expired token');
    }
    catch (AuthxException $e) {
      $this->assertEquals('Expired token', $e->getMessage());
    }
    finally {
      JWT::$timestamp = NULL;
    }
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
