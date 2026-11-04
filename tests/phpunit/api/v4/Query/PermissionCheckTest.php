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


namespace api\v4\Query;

use api\v4\Api4TestBase;
use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Contact;
use Civi\Api4\Event;
use Civi\Api4\Participant;
use Civi\Test\Invasive;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class PermissionCheckTest extends Api4TestBase implements TransactionalInterface {

  /**
   * Clean up after test.
   *
   * @throws \Exception
   */
  public function tearDown(): void {
    \CRM_Utils_Hook::singleton()->reset();
    $config = \CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = NULL;
    parent::tearDown();
  }

  /**
   */
  public function testGatekeeperPermissions(): void {
    $config = \CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = [
      'access CiviCRM',
      'access CiviEvent',
      'view event info',
    ];
    // Above permissions should be sufficient to perform Event::get
    Event::get()->execute();

    $config->userPermissionClass->permissions = [];
    // Ensure error is thrown if permissions are not sufficient
    try {
      Event::get()->execute();
    }
    catch (UnauthorizedException $e) {
      $err = $e->getMessage();
    }
    $this->assertStringContainsString('Authorization failed', $err);
  }

  /**
   * Tests that gatekeeper permissions are enforced for implicit joins
   */
  public function testImplicitJoinPermissions(): void {
    $config = \CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = [
      'access CiviCRM',
      'access CiviEvent',
      'view all contacts',
      'view event info',
      'view event participants',
    ];
    $name = uniqid(__FUNCTION__);
    $event = Event::create(FALSE)
      ->addValue('title', 'ABC123 Event')
      ->addValue('event_type_id', 1)
      ->addValue('start_date', 'now')
      ->execute()->first();
    $contact = Contact::create(FALSE)
      ->addValue('first_name', $name)
      ->addChain('participant', Participant::create()
        ->addValue('contact_id', '$id')
        ->addValue('event_id', $event['id']),
      0)
      ->execute()->first();
    $participant = Participant::get()
      ->addSelect('contact_id.first_name', 'event_id.title')
      ->addWhere('event_id.id', '=', $event['id'])
      ->execute()
      ->first();

    $this->assertSame('ABC123 Event', $participant['event_id.title']);
    $this->assertEquals($name, $participant['contact_id.first_name']);

    // Remove access to view events
    $config->userPermissionClass->permissions = [
      'access CiviCRM',
      'access CiviEvent',
      'view all contacts',
      'view event participants',
    ];
    $participant = Participant::get()
      ->addSelect('contact_id.first_name')
      ->addSelect('event_id.title')
      ->addWhere('id', '=', $contact['participant']['id'])
      ->execute()
      ->first();

    $this->assertTrue(empty($participant['event_id.title']));
    $this->assertEquals($name, $participant['contact_id.first_name']);

  }

  /**
   * Tests that gatekeeper permissions are enforced for explicit joins
   */
  public function testExplicitJoinPermissions(): void {
    $config = \CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = [
      'access CiviCRM',
      'access CiviEvent',
      'view all contacts',
      'view event info',
      'view event participants',
    ];
    $name = uniqid(__FUNCTION__);
    $event = Event::create(FALSE)
      ->addValue('title', 'ABC321 Event')
      ->addValue('event_type_id', 1)
      ->addValue('start_date', 'now')
      ->execute()->first();
    $contact = Contact::create(FALSE)
      ->addValue('first_name', $name)
      ->addChain('participant', Participant::create()
        ->addValue('contact_id', '$id')
        ->addValue('event_id', $event['id']),
      0)
      ->execute()->first();
    $participant = Participant::get()
      ->addJoin('Contact AS contact1', 'INNER', ['contact1.id', '=', 'contact_id'])
      ->addJoin('Event AS event1', 'INNER')
      ->addSelect('contact1.first_name', 'event1.title')
      ->addWhere('event1.id', '=', $event['id'])
      ->execute()
      ->first();

    $this->assertSame('ABC321 Event', $participant['event1.title']);
    $this->assertEquals($name, $participant['contact1.first_name']);

    // Remove access to view events
    $config->userPermissionClass->permissions = [
      'access CiviCRM',
      'access CiviEvent',
      'view all contacts',
      'view event participants',
    ];
    $participant = Participant::get()
      ->addJoin('Contact AS contact1', 'INNER', ['contact1.id', '=', 'contact_id'])
      ->addJoin('Event AS event1', 'INNER')
      ->addSelect('contact1.first_name')
      ->addSelect('event1.title')
      ->addWhere('id', '=', $contact['participant']['id'])
      ->execute()
      ->first();

    $this->assertTrue(empty($participant['event1.title']));
    $this->assertEquals($name, $participant['contact1.first_name']);

  }

  /**
   * Tests that redundant ACL clauses on joined entities are deduped for implicit joins.
   */
  public function testAclClauseDeduping(): void {
    $config = \CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = ['access CiviCRM'];

    $query = new \Civi\Api4\Query\Api4SelectQuery(
      Contact::get()
        ->addSelect('id', 'sort_name', 'address_primary.street_address', 'email_primary.email', 'phone_primary.phone')
    );
    $sql = $query->getSql();

    // Verify joins are actually compiled
    $this->assertStringContainsString('civicrm_address `address_primary', $sql);
    $this->assertStringContainsString('civicrm_email `email_primary', $sql);
    $this->assertStringContainsString('civicrm_phone `phone_primary', $sql);

    // Redundant location ACL subqueries should be deduped from the ON clause
    [$fromAndJoins] = explode('WHERE', $sql);
    $this->assertStringNotContainsString('civicrm_loc_block', $fromAndJoins);
    $this->assertStringNotContainsString('civicrm_acl_contact_cache', $fromAndJoins);

    // Multi-hop implicit join: employer address_primary should also dedupe
    $multiHopQuery = new \Civi\Api4\Query\Api4SelectQuery(
      Contact::get()
        ->addSelect('id', 'employer_id.address_primary.street_address')
    );
    $multiHopSql = $multiHopQuery->getSql();
    $this->assertStringContainsString('civicrm_contact `employer_id', $multiHopSql);
    $this->assertStringContainsString('civicrm_address `address_primary', $multiHopSql);
    [$multiHopFromAndJoins] = explode('WHERE', $multiHopSql);
    $this->assertStringNotContainsString('civicrm_loc_block', $multiHopFromAndJoins);
    // ON clause of employer_id_address_primary should not have redundant contact ACL
    $this->assertStringNotContainsString('contact_id IN (SELECT contact_id FROM civicrm_acl_contact_cache', $multiHopFromAndJoins);

    // Querying Address directly still applies its location ACL check
    $addressQuery = new \Civi\Api4\Query\Api4SelectQuery(
      \Civi\Api4\Address::get()->addSelect('id', 'street_address')
    );
    $addressSql = $addressQuery->getSql();
    $this->assertStringContainsString('civicrm_acl_contact_cache', $addressSql);
  }

  /**
   * Tests that if a hook appends a restriction to Address $clauses['id'] (e.g. '= 0'),
   * deduping removes the redundant contact-ownership part while keeping the hook restriction.
   */
  public function testAclClauseDedupingRetainsHookIdRestriction(): void {
    $config = \CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = ['access CiviCRM'];

    \CRM_Utils_Hook::singleton()->setHook('civicrm_selectWhereClause', function ($entity, &$clauses) {
      if ($entity === 'Address') {
        $clauses['id'][] = '= 0';
      }
    });

    // 1. Direct Address get should enforce the hook restriction
    $addressQuery = new \Civi\Api4\Query\Api4SelectQuery(
      \Civi\Api4\Address::get()->addSelect('id', 'street_address')
    );
    $addressSql = $addressQuery->getSql();
    $this->assertStringContainsString('`a`.`id` = 0', $addressSql);

    // 2. Implicit join via contact should retain '= 0' on address_primary.id
    // but drop the redundant contact-ownership subquery
    $contactQuery = new \Civi\Api4\Query\Api4SelectQuery(
      Contact::get()->addSelect('id', 'address_primary.street_address')
    );
    $contactSql = $contactQuery->getSql();
    $this->assertMatchesRegularExpression('/`address_primary[^`]*`\.`id` = 0/', $contactSql);
    [$fromAndJoins] = explode('WHERE', $contactSql);
    $this->assertStringNotContainsString('civicrm_acl_contact_cache', $fromAndJoins);

    // 3. Explicit join should also retain '= 0' on address.id
    $explicitQuery = new \Civi\Api4\Query\Api4SelectQuery(
      Contact::get()
        ->addSelect('id', 'address.street_address')
        ->addJoin('Address AS address', 'LEFT', ['address.contact_id', '=', 'id'])
    );
    $explicitSql = $explicitQuery->getSql();
    $this->assertStringContainsString('`address`.`id` = 0', $explicitSql);
    [$fromAndJoinsExplicit] = explode('WHERE', $explicitSql);
    $this->assertStringNotContainsString('civicrm_acl_contact_cache', $fromAndJoinsExplicit);
  }

  /**
   * Tests that deduping works for custom fields which have a dot in their field name.
   */
  public function testAclClauseDedupingWithCustomField(): void {
    $config = \CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = ['access CiviCRM'];

    \CRM_Utils_Hook::singleton()->setHook('civicrm_selectWhereClause', function ($entity, &$clauses) {
      if ($entity === 'Contact') {
        $clauses['Custom_Group.my_field'] = ['= 123'];
      }
    });

    $query = new \Civi\Api4\Query\Api4SelectQuery(Contact::get());

    // When joining on a custom field that already has an ACL applied on the base entity,
    // the joined entity's clause on that custom field should be deduped.
    $dedupedClauses = $query->getAclClause('e', 'Contact', ['Custom_Group.my_field', 'e.Custom_Group.my_field']);
    $this->assertArrayNotHasKey('Custom_Group.my_field', $dedupedClauses);

    // If joined on a different field, the clause should NOT be deduped.
    $nonDedupedClauses = $query->getAclClause('e', 'Contact', ['other_field', 'e.id']);
    $this->assertArrayHasKey('Custom_Group.my_field', $nonDedupedClauses);

    // Check that aclFields tracked the full custom field name with the joined entity prefix
    $aclFields = Invasive::get([$query, 'aclFields']);
    $this->assertContains('e.Custom_Group.my_field', $aclFields);
  }

}
