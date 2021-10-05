<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace api\v4\Action;

use Civi\Api4\Contact;
use Civi\Api4\Email;

/**
 * @group headless
 */
class ContactApiKeyTest extends \api\v4\UnitTestCase {

  public function testGetApiKey() {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'add contacts', 'edit api keys', 'view all contacts', 'edit all contacts'];
    $key = \CRM_Utils_String::createRandom(16, \CRM_Utils_String::ALPHANUMERIC);
    $isSafe = function ($mixed) use ($key) {
      return strpos(json_encode($mixed), $key) === FALSE;
    };

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
      ->addSelect('IF((api_key IS NULL), "yes", "no") AS is_api_key_null')
      ->execute()
      ->first();
    $this->assertEquals($key, $result['api_key']);
    $this->assertEquals('no', $result['is_api_key_null']);
    $this->assertFalse($isSafe($result), "Should reveal secret details ($key): " . var_export($result, 1));

    // Can also be fetched via join
    $email = Email::get()
      ->addSelect('contact_id.api_key')
      ->addSelect('IF((contact_id.api_key IS NULL), "yes", "no") AS is_api_key_null')
      ->addWhere('id', '=', $contact['email']['id'])
      ->execute()->first();
    $this->assertEquals($key, $email['contact_id.api_key']);
    $this->assertEquals('no', $result['is_api_key_null']);
    $this->assertFalse($isSafe($email), "Should reveal secret details ($key): " . var_export($email, 1));

    // Remove permission and we should not see the key
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view debug output', 'view all contacts'];
    $result = Contact::get()
      ->addWhere('id', '=', $contact['id'])
      ->addSelect('api_key')
      ->addSelect('IF((api_key IS NULL), "yes", "no") AS is_api_key_null')
      ->setDebug(TRUE)
      ->execute();
    $this->assertContains('api_key', $result->debug['unauthorized_fields']);
    $this->assertArrayNotHasKey('api_key', $result[0]);
    $this->assertArrayNotHasKey('is_api_key_null', $result[0]);
    $this->assertTrue($isSafe($result[0]), "Should NOT reveal secret details ($key): " . var_export($result[0], 1));

    // Also not available via join
    $email = Email::get()
      ->addSelect('contact_id.api_key')
      ->addSelect('IF((contact_id.api_key IS NULL), "yes", "no") AS is_api_key_null')
      ->addWhere('id', '=', $contact['email']['id'])
      ->setDebug(TRUE)
      ->execute();
    $this->assertContains('contact_id.api_key', $email->debug['unauthorized_fields']);
    $this->assertArrayNotHasKey('contact_id.api_key', $email[0]);
    $this->assertArrayNotHasKey('is_api_key_null', $result[0]);
    $this->assertTrue($isSafe($email[0]), "Should NOT reveal secret details ($key): " . var_export($email[0], 1));

    $result = Contact::get()
      ->addWhere('id', '=', $contact['id'])
      ->execute()
      ->first();
    $this->assertArrayNotHasKey('api_key', $result);
    $this->assertTrue($isSafe($result), "Should NOT reveal secret details ($key): " . var_export($result, 1));
  }

  public function testApiKeyInWhereAndOrderBy() {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'add contacts', 'edit api keys', 'view all contacts', 'edit all contacts'];
    $keyA = 'a' . \CRM_Utils_String::createRandom(15, \CRM_Utils_String::ALPHANUMERIC);
    $keyB = 'b' . \CRM_Utils_String::createRandom(15, \CRM_Utils_String::ALPHANUMERIC);

    $firstName = uniqid('name');

    $contactA = Contact::create()
      ->addValue('first_name', $firstName)
      ->addValue('last_name', 'KeyA')
      ->addValue('api_key', $keyA)
      ->execute()
      ->first();

    $contactB = Contact::create()
      ->addValue('first_name', $firstName)
      ->addValue('last_name', 'KeyB')
      ->addValue('api_key', $keyB)
      ->execute()
      ->first();

    // With sufficient permission we can ORDER BY the key
    $result = Contact::get()
      ->addSelect('id')
      ->addWhere('first_name', '=', $firstName)
      ->addOrderBy('api_key', 'DESC')
      ->addOrderBy('id', 'ASC')
      ->execute();
    $this->assertEquals($contactB['id'], $result[0]['id']);

    // We can also use the key in WHERE clause
    $result = Contact::get()
      ->addSelect('id')
      ->addWhere('api_key', '=', $keyB)
      ->execute();
    $this->assertEquals($contactB['id'], $result->single()['id']);

    // We can also use the key in HAVING clause
    $result = Contact::get()
      ->addSelect('id', 'api_key')
      ->addHaving('api_key', '=', $keyA)
      ->execute();
    $this->assertEquals($contactA['id'], $result->single()['id']);

    // Remove permission
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view debug output', 'view all contacts'];

    // Assert we cannot ORDER BY the key
    $result = Contact::get()
      ->addSelect('id')
      ->addWhere('first_name', '=', $firstName)
      ->addOrderBy('api_key', 'DESC')
      ->addOrderBy('id', 'ASC')
      ->setDebug(TRUE)
      ->execute();
    $this->assertEquals($contactA['id'], $result[0]['id']);
    $this->assertContains('api_key', $result->debug['unauthorized_fields']);

    // Assert we cannot use the key in WHERE clause
    $result = Contact::get()
      ->addSelect('id')
      ->addWhere('api_key', '=', $keyB)
      ->setDebug(TRUE)
      ->execute();
    $this->assertGreaterThan(1, $result->count());
    $this->assertContains('api_key', $result->debug['unauthorized_fields']);

    // Assert we cannot use the key in HAVING clause
    $result = Contact::get()
      ->addSelect('id', 'api_key')
      ->addHaving('api_key', '=', $keyA)
      ->setDebug(TRUE)
      ->execute();
    $this->assertGreaterThan(1, $result->count());
    $this->assertContains('api_key', $result->debug['unauthorized_fields']);

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
    $this->assertStringContainsString('key', $error);
  }

  public function testGetApiKeyViaJoin() {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view all contacts'];
    $key = \CRM_Utils_String::createRandom(16, \CRM_Utils_String::ALPHANUMERIC);
    $isSafe = function ($mixed) use ($key) {
      if ($mixed instanceof Result) {
        $mixed = $mixed->getArrayCopy();
      }
      return strpos(json_encode($mixed), $key) === FALSE;
    };

    $contact = Contact::create(FALSE)
      ->addValue('first_name', 'Api')
      ->addValue('last_name', 'Key0')
      ->addValue('api_key', $key)
      ->execute()
      ->first();
    $this->assertFalse($isSafe($contact), "Should reveal secret details ($key): " . var_export($contact, 1));

    Email::create(FALSE)
      ->addValue('email', 'foo@example.org')
      ->addValue('contact_id', $contact['id'])
      ->execute();

    $result = Email::get(FALSE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->addSelect('email')
      ->addSelect('contact_id.api_key')
      ->execute()
      ->first();
    $this->assertFalse($isSafe($result), "Should reveal secret details ($key): " . var_export($result, 1));

    $result = Email::get(TRUE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->addSelect('contact_id.api_key')
      ->execute()
      ->first();
    $this->assertTrue($isSafe($result), "Should NOT reveal secret details ($key): " . var_export($result, 1));
  }

  public function testUpdateApiKey() {
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'edit all contacts'];
    $key = uniqid();

    $contact = Contact::create(FALSE)
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

    $result = Contact::get(FALSE)
      ->addWhere('id', '=', $contact['id'])
      ->addSelect('api_key')
      ->execute()
      ->first();

    $this->assertStringContainsString('key', $error);

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

    $contact = Contact::create(FALSE)
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

    $this->assertStringContainsString('key', $error);

    $result = Contact::get(FALSE)
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

    $result = Contact::get(FALSE)
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
