<?php

namespace api\v4\Action;

use Civi\Api4\Contact;
use Civi\Api4\Email;

/**
 * @group headless
 */
class ContactApiKeyTest extends \api\v4\UnitTestCase {

  public function testGetApiKey() {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'add contacts', 'edit api keys', 'view all contacts', 'edit all contacts'];
    $key = uniqid();

    $contact = Contact::create()
      ->addValue('first_name', 'Api')
      ->addValue('last_name', 'Key0')
      ->addValue('api_key', $key)
      ->addChain('email', Email::create()
        ->addValue('contact_id', '$id')
        ->addValue('email', 'test@key.get'),
        0
      )
      ->execute()
      ->first();

    // With sufficient permission we should see the key
    $result = Contact::get()
      ->addWhere('id', '=', $contact['id'])
      ->addSelect('api_key')
      ->execute()
      ->first();
    $this->assertEquals($key, $result['api_key']);

    // Can also be fetched via join
    $email = Email::get()
      ->addSelect('contact.api_key')
      ->addWhere('id', '=', $contact['email']['id'])
      ->execute()->first();
    $this->assertEquals($key, $email['contact.api_key']);

    // Remove permission and we should not see the key
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $result = Contact::get()
      ->addWhere('id', '=', $contact['id'])
      ->addSelect('api_key')
      ->execute()
      ->first();
    $this->assertTrue(empty($result['api_key']));

    // Also not available via join
    $email = Email::get()
      ->addSelect('contact.api_key')
      ->addWhere('id', '=', $contact['email']['id'])
      ->execute()->first();
    $this->assertTrue(empty($email['contact.api_key']));

    $result = Contact::get()
      ->addWhere('id', '=', $contact['id'])
      ->execute()
      ->first();
    $this->assertTrue(empty($result['api_key']));
  }

  public function testCreateWithInsufficientPermissions() {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'add contacts'];
    $key = uniqid();

    $error = '';
    try {
      Contact::create()
        ->addValue('first_name', 'Api')
        ->addValue('last_name', 'Key1')
        ->addValue('api_key', $key)
        ->execute()
        ->first();
    }
    catch (\Exception $e) {
      $error = $e->getMessage();
    }
    $this->assertContains('key', $error);
  }

  public function testUpdateApiKey() {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'edit all contacts'];
    $key = uniqid();

    $contact = Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Api')
      ->addValue('last_name', 'Key2')
      ->addValue('api_key', $key)
      ->execute()
      ->first();

    $error = '';
    try {
      // Try to update the key without permissions; nothing should happen
      Contact::update()
        ->addWhere('id', '=', $contact['id'])
        ->addValue('api_key', "NotAllowed")
        ->execute();
    }
    catch (\Exception $e) {
      $error = $e->getMessage();
    }

    $result = Contact::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('id', '=', $contact['id'])
      ->addSelect('api_key')
      ->execute()
      ->first();

    $this->assertContains('key', $error);

    // Assert key is still the same
    $this->assertEquals($result['api_key'], $key);

    // Now we can update the key
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'administer CiviCRM', 'edit all contacts'];

    Contact::update()
      ->addWhere('id', '=', $contact['id'])
      ->addValue('api_key', "IGotThePower!")
      ->execute();

    $result = Contact::get()
      ->addWhere('id', '=', $contact['id'])
      ->addSelect('api_key')
      ->execute()
      ->first();

    // Assert key was updated
    $this->assertEquals($result['api_key'], "IGotThePower!");
  }

  public function testUpdateOwnApiKey() {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'edit own api keys', 'edit all contacts'];
    $key = uniqid();

    $contact = Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Api')
      ->addValue('last_name', 'Key3')
      ->addValue('api_key', $key)
      ->execute()
      ->first();

    $error = '';
    try {
      // Try to update the key without permissions; nothing should happen
      Contact::update()
        ->addWhere('id', '=', $contact['id'])
        ->addValue('api_key', "NotAllowed")
        ->execute();
    }
    catch (\Exception $e) {
      $error = $e->getMessage();
    }

    $this->assertContains('key', $error);

    $result = Contact::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('id', '=', $contact['id'])
      ->addSelect('api_key')
      ->execute()
      ->first();

    // Assert key is still the same
    $this->assertEquals($result['api_key'], $key);

    // Now we can update the key
    \CRM_Core_Session::singleton()->set('userID', $contact['id']);

    Contact::update()
      ->addWhere('id', '=', $contact['id'])
      ->addValue('api_key', "MyId!")
      ->execute();

    $result = Contact::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('id', '=', $contact['id'])
      ->addSelect('api_key')
      ->execute()
      ->first();

    // Assert key was updated
    $this->assertEquals($result['api_key'], "MyId!");
  }

  public function testApiKeyWithGetFields() {
    // With sufficient permissions the field should exist
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'edit api keys'];
    $this->assertArrayHasKey('api_key', \civicrm_api4('Contact', 'getFields', [], 'name'));
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'administer CiviCRM'];
    $this->assertArrayHasKey('api_key', \civicrm_api4('Contact', 'getFields', [], 'name'));

    // Field hidden from non-privileged users...
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'edit own api keys'];
    $this->assertArrayNotHasKey('api_key', \civicrm_api4('Contact', 'getFields', [], 'name'));

    // ...unless you disable 'checkPermissions'
    $this->assertArrayHasKey('api_key', \civicrm_api4('Contact', 'getFields', ['checkPermissions' => FALSE], 'name'));
  }

}
