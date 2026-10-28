<?php
namespace Civi\Afform;

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Afform;
use Civi\Test\Api4TestTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * Tests for AfformAccessSubscriber.
 *
 * @group headless
 */
class AfformAccessTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {
  use Api4TestTrait;

  private string $formName = 'test_afform_access';

  public function setUpHeadless() {
    return \Civi\Test::headless()->installMe(__DIR__)->install('org.civicrm.search_kit')->apply();
  }

  public function setUp(): void {
    parent::setUp();
    Afform::revert(FALSE)->addWhere('name', '=', $this->formName)->execute();
  }

  public function tearDown(): void {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = NULL;
    \CRM_Core_Config::singleton()->userPermissionTemp = NULL;
    \CRM_Core_Session::singleton()->reset();
    \Civi::$statics = [];
    Afform::revert(FALSE)->addWhere('name', '=', $this->formName)->execute();
    parent::tearDown();
  }

  /**
   * Helper to create the test form owned by a given contact ID.
   */
  private function createFormWithOwner(int $ownerId): void {
    \CRM_Core_Session::singleton()->set('userID', $ownerId);
    Afform::create(FALSE)
      ->addValue('name', $this->formName)
      ->addValue('title', 'Original Form')
      ->execute();

    $form = Afform::get(FALSE)->addWhere('name', '=', $this->formName)->execute()->single();
    $this->assertSame($ownerId, $form['created_id']);
  }

  /**
   * Test that a user with 'manage own afform' cannot take ownership of another user's afform via update.
   */
  public function testCannotTakeOwnershipViaUpdate(): void {
    $owner = $this->createTestRecord('Individual', ['first_name' => 'Owner']);
    $otherUser = $this->createTestRecord('Individual', ['first_name' => 'Other']);

    $this->createFormWithOwner($owner['id']);

    // Log in as otherUser with 'manage own afform'
    \CRM_Core_Session::singleton()->set('userID', $otherUser['id']);
    \CRM_Core_Config::singleton()->userPermissionTemp = NULL;
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'manage own afform',
    ];

    $this->expectException(UnauthorizedException::class);
    Afform::update()
      ->setCheckPermissions(TRUE)
      ->addWhere('name', '=', $this->formName)
      ->addValue('title', 'Hijacked Form')
      ->addValue('created_id', $otherUser['id'])
      ->execute();
  }

  /**
   * Test that a user with 'manage own afform' cannot overwrite another user's afform via create.
   */
  public function testCannotTakeOwnershipViaCreate(): void {
    $owner = $this->createTestRecord('Individual', ['first_name' => 'Owner']);
    $otherUser = $this->createTestRecord('Individual', ['first_name' => 'Other']);

    $this->createFormWithOwner($owner['id']);

    // Log in as otherUser with 'manage own afform'
    \CRM_Core_Session::singleton()->set('userID', $otherUser['id']);
    \CRM_Core_Config::singleton()->userPermissionTemp = NULL;
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'manage own afform',
    ];

    $this->expectException(UnauthorizedException::class);
    Afform::create()
      ->setCheckPermissions(TRUE)
      ->addValue('name', $this->formName)
      ->addValue('title', 'Overwritten Form')
      ->addValue('created_id', $otherUser['id'])
      ->execute();
  }

  /**
   * Test that a user with 'manage own afform' cannot take ownership of another user's afform via save.
   */
  public function testCannotTakeOwnershipViaSave(): void {
    $owner = $this->createTestRecord('Individual', ['first_name' => 'Owner']);
    $otherUser = $this->createTestRecord('Individual', ['first_name' => 'Other']);

    $this->createFormWithOwner($owner['id']);

    // Log in as otherUser with 'manage own afform'
    \CRM_Core_Session::singleton()->set('userID', $otherUser['id']);
    \CRM_Core_Config::singleton()->userPermissionTemp = NULL;
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'manage own afform',
    ];

    $this->expectException(UnauthorizedException::class);
    Afform::save()
      ->setCheckPermissions(TRUE)
      ->addRecord([
        'name' => $this->formName,
        'title' => 'Hijacked via Save',
        'created_id' => $otherUser['id'],
      ])
      ->execute();
  }

  /**
   * Test that the owner of an afform with 'manage own afform' cannot transfer ownership to someone else.
   */
  public function testCannotTransferOwnership(): void {
    $owner = $this->createTestRecord('Individual', ['first_name' => 'Owner']);
    $otherUser = $this->createTestRecord('Individual', ['first_name' => 'Other']);

    $this->createFormWithOwner($owner['id']);

    // Log in as owner with 'manage own afform'
    \CRM_Core_Session::singleton()->set('userID', $owner['id']);
    \CRM_Core_Config::singleton()->userPermissionTemp = NULL;
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'manage own afform',
    ];

    $this->expectException(UnauthorizedException::class);
    Afform::update()
      ->setCheckPermissions(TRUE)
      ->addWhere('name', '=', $this->formName)
      ->addValue('created_id', $otherUser['id'])
      ->execute();
  }

  /**
   * Test that the owner with 'manage own afform' can update their own afform.
   */
  public function testOwnerCanUpdateOwnAfform(): void {
    $owner = $this->createTestRecord('Individual', ['first_name' => 'Owner']);

    $this->createFormWithOwner($owner['id']);

    // Log in as owner with 'manage own afform'
    \CRM_Core_Session::singleton()->set('userID', $owner['id']);
    \CRM_Core_Config::singleton()->userPermissionTemp = NULL;
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'manage own afform',
    ];

    Afform::update()
      ->setCheckPermissions(TRUE)
      ->addWhere('name', '=', $this->formName)
      ->addValue('title', 'Updated by Owner')
      ->execute();

    $form = Afform::get(FALSE)->addWhere('name', '=', $this->formName)->execute()->single();
    $this->assertSame('Updated by Owner', $form['title']);
    $this->assertSame($owner['id'], $form['created_id']);
  }

}
