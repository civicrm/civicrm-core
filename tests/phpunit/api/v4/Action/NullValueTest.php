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

use Civi\Api4\Activity;
use Civi\Api4\Contact;
use api\v4\Api4TestBase;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class NullValueTest extends Api4TestBase implements TransactionalInterface {

  public function setUp(): void {
    $format = '{contact.first_name}{ }{contact.last_name}';
    \Civi::settings()->set('display_name_format', $format);
    parent::setUp();
  }

  public function testStringNull(): void {
    $contact = Contact::create(FALSE)
      ->addValue('first_name', 'Joseph')
      ->addValue('last_name', 'null')
      ->addValue('contact_type', 'Individual')
      ->execute()
      ->first();

    $this->assertSame('Null', $contact['last_name']);
    $this->assertSame('Joseph Null', $contact['display_name']);
  }

  public function testSettingToNull(): void {
    $contact = Contact::create(FALSE)
      ->addValue('first_name', 'ILoveMy')
      ->addValue('last_name', 'LastName')
      ->addValue('contact_type', 'Individual')
      ->execute()
      ->first();

    $this->assertSame('ILoveMy LastName', $contact['display_name']);
    $contactId = $contact['id'];

    $contact = Contact::update(FALSE)
      ->addWhere('id', '=', $contactId)
      ->addValue('last_name', NULL)
      ->execute()
      ->first();

    $this->assertSame(NULL, $contact['last_name']);
    $this->assertSame('ILoveMy', $contact['display_name']);
  }

  public function testSaveWithReload(): void {
    $contact = Contact::create(FALSE)
      ->addValue('first_name', 'Firsty')
      ->addValue('last_name', 'Lasty')
      ->execute()
      ->first();

    $activity = Activity::create(FALSE)
      ->addValue('source_contact_id', $contact['id'])
      ->addValue('activity_type_id', 1)
      ->addValue('subject', 'hello')
      ->execute()
      ->first();

    $this->assertEquals('hello', $activity['subject']);

    $saved = Activity::save(FALSE)
      ->addRecord(['id' => $activity['id'], 'subject' => NULL])
      ->execute()
      ->first();

    $this->assertNull($saved['subject']);
    $this->assertArrayNotHasKey('activity_date_time', $saved);

    $saved = Activity::save(FALSE)
      ->addRecord(['id' => $activity['id'], 'subject' => NULL])
      ->setReload(TRUE)
      ->execute()
      ->first();

    $this->assertNull($saved['subject']);
    $this->assertArrayHasKey('activity_date_time', $saved);

  }

}
