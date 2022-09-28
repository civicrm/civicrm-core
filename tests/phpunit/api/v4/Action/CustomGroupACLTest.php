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

use Civi\Api4\ACL;
use Civi\Api4\Contact;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\CustomValue;

/**
 * @group headless
 */
class CustomGroupACLTest extends BaseCustomValueTest {

  public function tearDown(): void {
    parent::tearDown();
    // Delete all ACLs
    ACL::delete(FALSE)->addWhere('id', '>', 0)->execute();
    unset(\Civi::$statics['CRM_Contact_BAO_Contact_Permission']);
    \CRM_Core_DAO::executeQuery('TRUNCATE civicrm_acl_contact_cache');
  }

  public function testViewEditCustomGroupACLs() {
    $groups = ['readWrite' => 'Edit', 'readOnly' => 'View', 'superSecret' => NULL];
    $v3 = [];

    foreach ($groups as $name => $access) {
      $singleGroup = CustomGroup::create(FALSE)
        ->addValue('name', 'My' . ucfirst($name) . 'Single')
        ->addValue('extends', 'Individual')
        ->addChain('field', CustomField::create()
          ->addValue('label', 'MyField')
          ->addValue('html_type', 'Text')
          ->addValue('custom_group_id', '$id'), 0)
        ->execute()->single();
      $v3['single'][$name] = 'custom_' . $singleGroup['field']['id'];
      $multiGroup = CustomGroup::create(FALSE)
        ->addValue('name', 'My' . ucfirst($name) . 'Multi')
        ->addValue('extends', 'Individual')
        ->addValue('is_multiple', TRUE)
        ->addChain('field', CustomField::create()
          ->addValue('label', 'MyField')
          ->addValue('html_type', 'Text')
          ->addValue('custom_group_id', '$id'), 0)
        ->execute()->single();
      $v3['multi'][$name] = 'custom_' . $multiGroup['field']['id'];
      if ($access) {
        ACL::create(FALSE)->setValues([
          'name' => $name . 'Single',
          'entity_id' => 0,
          'operation' => $access,
          'object_table' => 'civicrm_custom_group',
          'object_id' => $singleGroup['id'],
        ])->execute();
        ACL::create(FALSE)->setValues([
          'name' => $name . 'Multi',
          'entity_id' => 0,
          'operation' => $access,
          'object_table' => 'civicrm_custom_group',
          'object_id' => $multiGroup['id'],
        ])->execute();
      }
    }

    $this->createLoggedInUser();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access all custom data', 'access CiviCRM', 'add contacts'];

    $cid = Contact::create()->setValues([
      'contact_type' => 'Individual',
      'first_name' => 'test123',
      'MyReadWriteSingle.MyField' => '123',
      'MyReadOnlySingle.MyField' => '456',
      'MySuperSecretSingle.MyField' => '789',
    ])->execute()->first()['id'];

    // TEST SINGLE-VALUE CUSTOM GROUPS

    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view all contacts', 'edit all contacts'];

    // Ensure ACLs apply to APIv4 get
    $result = Contact::get()
      ->addWhere('id', '=', $cid)
      ->addSelect('custom.*')
      ->execute()->single();
    $this->assertEquals('123', $result['MyReadWriteSingle.MyField']);
    $this->assertEquals('456', $result['MyReadOnlySingle.MyField']);
    $this->assertArrayNotHasKey('MySuperSecretSingle.MyField', $result);

    // Ensure ACLs apply to APIv3 get
    $result = civicrm_api3('Contact', 'get', [
      'id' => $cid,
      'check_permissions' => 1,
      'return' => [$v3['single']['readWrite'], $v3['single']['readOnly'], $v3['single']['superSecret']],
    ])['values'][$cid];
    $this->assertEquals('123', $result[$v3['single']['readWrite']]);
    $this->assertArrayNotHasKey($v3['single']['superSecret'], $result);
    $this->assertEquals('456', $result[$v3['single']['readOnly']]);

    // Try to update all fields - ACLs will restrict based on write access
    Contact::update()->setValues([
      'id' => $cid,
      'first_name' => 'test1234',
      'MyReadWriteSingle.MyField' => '1234',
      'MyReadOnlySingle.MyField' => '4567',
      'MySuperSecretSingle.MyField' => '7890',
    ])->execute();

    // Verify only first name & readWrite field were altered by APIv4
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access all custom data', 'access CiviCRM', 'view all contacts', 'edit all contacts'];
    $result = Contact::get()
      ->addWhere('id', '=', $cid)
      ->addSelect('first_name', 'custom.*')
      ->execute()->single();
    $this->assertEquals('test1234', $result['first_name']);
    $this->assertEquals('1234', $result['MyReadWriteSingle.MyField']);
    $this->assertEquals('456', $result['MyReadOnlySingle.MyField']);
    $this->assertEquals('789', $result['MySuperSecretSingle.MyField']);

    // Try updating all fields with APIv3 - ACLs will restrict based on write access
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view all contacts', 'edit all contacts'];
    civicrm_api3('Contact', 'create', [
      'check_permissions' => 1,
      'id' => $cid,
      'first_name' => 'test12345',
      $v3['single']['readWrite'] => '12345',
      $v3['single']['readOnly'] => '45678',
      $v3['single']['superSecret'] => '7890!',
    ]);

    // Verify only first name & readWrite field were altered by APIv3
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access all custom data', 'access CiviCRM', 'view all contacts', 'edit all contacts'];
    $result = Contact::get()
      ->addWhere('id', '=', $cid)
      ->addSelect('first_name', 'custom.*')
      ->execute()->single();
    $this->assertEquals('test12345', $result['first_name']);
    $this->assertEquals('12345', $result['MyReadWriteSingle.MyField']);
    $this->assertEquals('456', $result['MyReadOnlySingle.MyField']);
    $this->assertEquals('789', $result['MySuperSecretSingle.MyField']);

    // TEST MULTI-VALUE CUSTOM GROUPS

    $multiValues = [
      'MyReadWriteMulti' => ['red', 'blue'],
      'MyReadOnlyMulti' => ['purple', 'orange'],
      'MySuperSecretMulti' => ['brown', 'black'],
    ];
    foreach ($multiValues as $groupName => $values) {
      \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access all custom data', 'access CiviCRM', 'view all contacts', 'edit all contacts'];
      foreach ($values as $value) {
        CustomValue::create($groupName)
          ->addValue('MyField', $value)
          ->addValue('entity_id', $cid)
          ->execute();
      }
      // Check that all but SuperSecret values can be read
      try {
        \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view all contacts', 'edit all contacts'];
        $result = CustomValue::get($groupName)
          ->addWhere('entity_id', '=', $cid)
          ->addOrderBy('id')
          ->execute();
        if ($groupName === 'MySuperSecretMulti') {
          $this->fail('API call should have failed');
        }
        $this->assertEquals($values, $result->column('MyField'));
      }
      catch (\API_Exception $e) {
        if ($groupName !== 'MySuperSecretMulti') {
          $this->fail('API get should have succeeded');
        }
      }
      // Check that it works via join also
      $result = Contact::get()
        ->addWhere('id', '=', $cid)
        ->addJoin("Custom_$groupName AS customGroup")
        ->addSelect("customGroup.MyField")
        ->execute();
      if ($groupName !== 'MySuperSecretMulti') {
        $this->assertEquals($values, $result->column("customGroup.MyField"));
      }
      else {
        foreach ($result as $row) {
          $this->assertArrayNotHasKey("customGroup.MyField", $row);
        }
      }
      try {
        CustomValue::create($groupName)
          ->addValue('MyField', 'new')
          ->addValue('entity_id', $cid)
          ->execute();
        if ($groupName !== 'MyReadWriteMulti') {
          $this->fail('API call should have failed');
        }
      }
      catch (\API_Exception $e) {
        if ($groupName === 'MyReadWriteMulti') {
          $this->fail('API create should have succeeded');
        }
      }
    }
    // Try updating with APIv3
    civicrm_api3('Contact', 'create', [
      'check_permissions' => 1,
      'id' => $cid,
      'first_name' => 'test12345',
      // Should update the first record in the "readWrite" group
      $v3['multi']['readWrite'] . '_1' => 'changed1',
      // These 2 updates should fail due to ACLs
      $v3['multi']['readOnly'] . '_1' => 'changed2',
      $v3['multi']['superSecret'] . '_1' => 'changed3',
    ]);

    // Ensure only readWrite group has been modified
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access all custom data', 'access CiviCRM', 'view all contacts', 'edit all contacts'];
    $expectedValues = [
      'MyReadWriteMulti' => ['changed1', 'blue', 'new'],
      'MyReadOnlyMulti' => ['purple', 'orange'],
      'MySuperSecretMulti' => ['brown', 'black'],
    ];
    foreach ($expectedValues as $groupName => $expected) {
      $result = CustomValue::get($groupName)
        ->addWhere('entity_id', '=', $cid)
        ->addOrderBy('id')
        ->execute();
      $this->assertEquals($expected, $result->column('MyField'));
    }

  }

}
