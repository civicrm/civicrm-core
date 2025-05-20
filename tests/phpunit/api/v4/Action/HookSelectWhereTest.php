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


namespace Civi\tests\phpunit\api\v4\Action;

use api\v4\Api4TestBase;
use Civi\Api4\Address;
use Civi\Api4\Contact;
use Civi\Api4\Phone;
use Civi\Core\HookInterface;
use Civi\Test\ACLPermissionTrait;

/**
 * @group headless
 */
class HookSelectWhereTest extends Api4TestBase implements HookInterface {

  use ACLPermissionTrait;

  private $hookEntity;
  private $hookCondition = [];

  public function testHookPhoneClause(): void {
    $cid = $this->createTestRecord('Individual')['id'];
    $this->saveTestRecords('Phone', [
      'records' => [
        ['phone' => '111-1111', 'location_type_id' => 1, 'contact_id' => $cid],
        ['phone' => '222-2222', 'location_type_id' => 2, 'contact_id' => $cid],
        ['phone' => '333-3333', 'location_type_id' => 3, 'contact_id' => $cid],
      ],
    ]);

    $this->hookEntity = 'Phone';
    $this->hookCondition = [
      'location_type_id' => ['= 1'],
    ];
    $result = Phone::get()
      ->addWhere('contact_id', '=', $cid)
      ->execute();

    $this->assertCount(1, $result);
    $this->assertEquals('111-1111', $result[0]['phone']);

    $result = Contact::get()
      ->addWhere('id', '=', $cid)
      ->addJoin('Phone AS phone', 'INNER', ['phone.contact_id', '=', 'id'])
      ->addSelect('phone.phone')
      ->execute();

    $this->assertCount(1, $result);
    $this->assertEquals('111-1111', $result[0]['phone.phone']);
  }

  public function testDedupeACLClauses(): void {
    $cidDisallowed = $this->createTestRecord('Individual')['id'];
    $cidAllowed = $this->createTestRecord('Individual', [
      'first_name' => 'Allowed',
    ])['id'];

    $this->saveTestRecords('Address', [
      'records' => [
        ['location_type_id' => 1, 'contact_id' => $cidDisallowed],
        ['location_type_id' => 1, 'contact_id' => $cidAllowed, 'street_address' => '111 Allowed'],
        ['location_type_id' => 2, 'contact_id' => $cidAllowed, 'street_address' => '222 Allowed'],
        ['location_type_id' => 3, 'contact_id' => $cidAllowed, 'street_address' => '333 Allowed'],
      ],
    ]);

    $this->createLoggedInUser();

    $this->allowedContactId = $cidAllowed;
    \CRM_Utils_Hook_UnitTests::singleton()->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereOnlyOne',
    ]);
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'view debug output'];

    $this->hookEntity = 'Address';
    $this->hookCondition = [
      'location_type_id' => ['IN (1,2)'],
    ];

    $result = Address::get()
      ->addSelect('id')
      ->setDebug(TRUE)
      ->execute();

    $this->assertCount(2, $result);

    // Ensure our selectWhereClause has been inserted exactly once
    $selectWhereCount = substr_count($result->debug['sql'][0], '`location_type_id` IN (1,2)');
    $this->assertEquals(1, $selectWhereCount);

    $result = Address::get()
      ->addWhere('location_type_id', '=', 1)
      ->addSelect('id', 'contact_id.first_name')
      ->setDebug(TRUE)
      ->execute();

    $this->assertCount(1, $result);
    $this->assertEquals('Allowed', $result[0]['contact_id.first_name']);

    $selectWhereCount = substr_count($result->debug['sql'][0], '`location_type_id` IN (1,2)');
    $this->assertEquals(1, $selectWhereCount);

    // We only want to see one contact ACL where clause
    $aclCount = substr_count($result->debug['sql'][0], 'civicrm_acl_contact_cache');
    $this->assertEquals(1, $aclCount);

    // Try with explicit join
    $result = Address::get()
      ->addWhere('location_type_id', '=', 1)
      ->addSelect('id', 'contact.first_name')
      ->addJoin('Contact AS contact', 'INNER', ['contact.id', '=', 'contact_id'])
      ->setDebug(TRUE)
      ->execute();

    $this->assertCount(1, $result);
    $this->assertEquals('Allowed', $result[0]['contact.first_name']);

    $selectWhereCount = substr_count($result->debug['sql'][0], '`location_type_id` IN (1,2)');
    $this->assertEquals(1, $selectWhereCount);

    // We only want to see one contact ACL where clause
    $aclCount = substr_count($result->debug['sql'][0], 'civicrm_acl_contact_cache');
    $this->assertEquals(1, $aclCount);

    // Try joining through another entity
    $phone = $this->createTestRecord('Phone', [
      'contact_id' => $cidAllowed,
    ]);

    $this->hookEntity = 'Address';
    $this->hookCondition = [
      'location_type_id' => ['IN (2)'],
    ];

    $result = Phone::get()
      ->addWhere('id', '=', $phone['id'])
      ->addSelect('id', 'contact.first_name', 'address.street_address')
      ->addJoin('Contact AS contact', 'INNER', ['contact.id', '=', 'contact_id'])
      ->addJoin('Address AS address', 'INNER', ['address.contact_id', '=', 'contact.id'])
      ->setDebug(TRUE)
      ->execute();

    $this->assertCount(1, $result);
    $this->assertEquals('Allowed', $result[0]['contact.first_name']);
    $this->assertEquals('222 Allowed', $result[0]['address.street_address']);

    $selectWhereCount = substr_count($result->debug['sql'][0], '`location_type_id` IN (2)');
    $this->assertEquals(1, $selectWhereCount);

    // We only want to see one contact ACL where clause
    $aclCount = substr_count($result->debug['sql'][0], 'civicrm_acl_contact_cache');
    $this->assertEquals(1, $aclCount);
  }

  /**
   * Implements hook_civicrm_selectWhereClause().
   */
  public function hook_civicrm_selectWhereClause($entity, &$clauses) {
    if ($entity == $this->hookEntity) {
      foreach ($this->hookCondition as $field => $clause) {
        $clauses[$field] = array_merge(($clauses[$field] ?? []), $clause);
      }
    }
  }

}
