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
use Civi\Api4\Website;
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
    $homeID = $this->getLocationTypeID('Home');
    $workID = $this->getLocationTypeID('Work');
    $mainID = $this->getLocationTypeID('Main');
    $cid = $this->createTestRecord('Individual')['id'];
    $this->saveTestRecords('Phone', [
      'records' => [
        ['phone' => '111-1111', 'location_type_id' => $homeID, 'contact_id' => $cid],
        ['phone' => '222-2222', 'location_type_id' => $workID, 'contact_id' => $cid],
        ['phone' => '333-3333', 'location_type_id' => $mainID, 'contact_id' => $cid],
      ],
    ]);

    $this->hookEntity = 'Phone';
    $this->hookCondition = [
      'location_type_id' => ["= $homeID"],
    ];
    $result = Phone::get()
      ->addWhere('contact_id', '=', $cid)
      ->execute();

    $this->assertCount(1, $result);
    $this->assertSame('111-1111', $result[0]['phone']);

    $result = Contact::get()
      ->addWhere('id', '=', $cid)
      ->addJoin('Phone AS phone', 'INNER', ['phone.contact_id', '=', 'id'])
      ->addSelect('phone.phone')
      ->execute();

    $this->assertCount(1, $result);
    $this->assertSame('111-1111', $result[0]['phone.phone']);
  }

  public function testDedupeACLClauses(): void {
    $homeID = $this->getLocationTypeID('Home');
    $workID = $this->getLocationTypeID('Work');
    $mainID = $this->getLocationTypeID('Main');
    $cidDisallowed = $this->createTestRecord('Individual')['id'];
    $cidAllowed = $this->createTestRecord('Individual', [
      'first_name' => 'Allowed',
    ])['id'];

    $this->saveTestRecords('Address', [
      'records' => [
        ['location_type_id' => $homeID, 'contact_id' => $cidDisallowed],
        ['location_type_id' => $homeID, 'contact_id' => $cidAllowed, 'street_address' => '111 Allowed'],
        ['location_type_id' => $workID, 'contact_id' => $cidAllowed, 'street_address' => '222 Allowed'],
        ['location_type_id' => $mainID, 'contact_id' => $cidAllowed, 'street_address' => '333 Allowed'],
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
      'location_type_id' => ["IN ($homeID,$workID)"],
    ];

    $result = Address::get()
      ->addSelect('id')
      ->setDebug(TRUE)
      ->execute();

    $this->assertCount(2, $result);

    // Ensure our selectWhereClause has been inserted exactly once
    $selectWhereCount = substr_count($result->debug['sql'][0], "`location_type_id` IN ($homeID,$workID)");
    $this->assertSame(1, $selectWhereCount);

    $result = Address::get()
      ->addWhere('location_type_id', '=', $homeID)
      ->addSelect('id', 'contact_id.first_name')
      ->setDebug(TRUE)
      ->execute();

    $this->assertCount(1, $result);
    $this->assertSame('Allowed', $result[0]['contact_id.first_name']);

    $selectWhereCount = substr_count($result->debug['sql'][0], "`location_type_id` IN ($homeID,$workID)");
    $this->assertSame(1, $selectWhereCount);

    // We only want to see one contact ACL where clause
    $aclCount = substr_count($result->debug['sql'][0], 'civicrm_acl_contact_cache');
    $this->assertSame(1, $aclCount);

    // Try with explicit join
    $result = Address::get()
      ->addWhere('location_type_id', '=', $homeID)
      ->addSelect('id', 'contact.first_name')
      ->addJoin('Contact AS contact', 'INNER', ['contact.id', '=', 'contact_id'])
      ->setDebug(TRUE)
      ->execute();

    $this->assertCount(1, $result);
    $this->assertSame('Allowed', $result[0]['contact.first_name']);

    $selectWhereCount = substr_count($result->debug['sql'][0], "`location_type_id` IN ($homeID,$workID)");
    $this->assertSame(1, $selectWhereCount);

    // We only want to see one contact ACL where clause
    $aclCount = substr_count($result->debug['sql'][0], 'civicrm_acl_contact_cache');
    $this->assertSame(1, $aclCount);

    // Try with implicit join via contact
    $result = Contact::get()
      ->addWhere('id', '=', $cidAllowed)
      ->addSelect('id', 'first_name', 'address_primary.street_address')
      ->setDebug(TRUE)
      ->execute();

    $this->assertCount(1, $result);
    $this->assertSame('111 Allowed', $result[0]['address_primary.street_address']);

    $selectWhereCount = substr_count($result->debug['sql'][0], "`location_type_id` IN ($homeID,$workID)");
    $this->assertSame(1, $selectWhereCount);

    $aclCount = substr_count($result->debug['sql'][0], 'civicrm_acl_contact_cache');
    $this->assertSame(1, $aclCount);

    // Try joining through another entity
    $phone = $this->createTestRecord('Phone', [
      'contact_id' => $cidAllowed,
    ]);

    $this->hookEntity = 'Address';
    $this->hookCondition = [
      'location_type_id' => ["IN ($workID)"],
    ];

    $result = Phone::get()
      ->addWhere('id', '=', $phone['id'])
      ->addSelect('id', 'contact.first_name', 'address.street_address')
      ->addJoin('Contact AS contact', 'INNER', ['contact.id', '=', 'contact_id'])
      ->addJoin('Address AS address', 'INNER', ['address.contact_id', '=', 'contact.id'])
      ->setDebug(TRUE)
      ->execute();

    $this->assertCount(1, $result);
    $this->assertSame('Allowed', $result[0]['contact.first_name']);
    $this->assertSame('222 Allowed', $result[0]['address.street_address']);

    $selectWhereCount = substr_count($result->debug['sql'][0], "`location_type_id` IN ($workID)");
    $this->assertSame(1, $selectWhereCount);

    // We only want to see one contact ACL where clause
    $aclCount = substr_count($result->debug['sql'][0], 'civicrm_acl_contact_cache');
    $this->assertSame(1, $aclCount);

    // Website has its own ACL on contact_id and joins Contact implicitly or explicitly
    $this->createTestRecord('Website', [
      'contact_id' => $cidAllowed,
      'url' => 'https://allowed.example.com',
      'website_type_id' => 1,
    ]);
    $this->createTestRecord('Website', [
      'contact_id' => $cidDisallowed,
      'url' => 'https://disallowed.example.com',
      'website_type_id' => 1,
    ]);
    $this->createTestRecord('Website', [
      'contact_id' => $cidAllowed,
      'url' => 'https://other.example.com',
      'website_type_id' => 2,
    ]);

    $this->hookEntity = 'Website';
    $this->hookCondition = [
      'website_type_id' => ['= 1'],
    ];

    // No joins
    $result = Website::get()
      ->addSelect('id', 'url')
      ->setDebug(TRUE)
      ->execute();

    $this->assertCount(1, $result);
    $this->assertSame('https://allowed.example.com', $result[0]['url']);

    // Implicit join
    $result = Website::get()
      ->addSelect('id', 'url', 'contact_id.first_name')
      ->setDebug(TRUE)
      ->execute();

    $this->assertCount(1, $result);
    $this->assertSame('https://allowed.example.com', $result[0]['url']);
    $this->assertSame('Allowed', $result[0]['contact_id.first_name']);

    $selectWhereCount = substr_count($result->debug['sql'][0], '`website_type_id` = 1');
    $this->assertSame(1, $selectWhereCount);

    // We only want to see one contact ACL where clause
    $aclCount = substr_count($result->debug['sql'][0], 'civicrm_acl_contact_cache');
    $this->assertSame(1, $aclCount);

    // Explicit join
    $result = Website::get()
      ->addSelect('id', 'url', 'contact.first_name')
      ->addJoin('Contact AS contact', 'INNER', ['contact.id', '=', 'contact_id'])
      ->setDebug(TRUE)
      ->execute();

    $this->assertCount(1, $result);
    $this->assertSame('https://allowed.example.com', $result[0]['url']);
    $this->assertSame('Allowed', $result[0]['contact.first_name']);

    $selectWhereCount = substr_count($result->debug['sql'][0], '`website_type_id` = 1');
    $this->assertSame(1, $selectWhereCount);

    $aclCount = substr_count($result->debug['sql'][0], 'civicrm_acl_contact_cache');
    $this->assertSame(1, $aclCount);
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
