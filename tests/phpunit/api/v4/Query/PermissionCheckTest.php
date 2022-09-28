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

use api\v4\UnitTestCase;
use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Contact;
use Civi\Api4\Event;
use Civi\Api4\Participant;

/**
 * @group headless
 */
class PermissionCheckTest extends UnitTestCase {

  /**
   * Clean up after test.
   *
   * @throws \Exception
   */
  public function tearDown(): void {
    \CRM_Utils_Hook::singleton()->reset();
    $config = \CRM_Core_Config::singleton();
    unset($config->userPermissionClass->permissions);
    parent::tearDown();
  }

  /**
   */
  public function testGatekeeperPermissions() {
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
  public function testImplicitJoinPermissions() {
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

    $this->assertEquals('ABC123 Event', $participant['event_id.title']);
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
  public function testExplicitJoinPermissions() {
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

    $this->assertEquals('ABC321 Event', $participant['event1.title']);
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

}
