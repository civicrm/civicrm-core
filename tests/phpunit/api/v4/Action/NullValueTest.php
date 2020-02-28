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
 * $Id$
 *
 */


namespace api\v4\Action;

use Civi\Api4\Activity;
use Civi\Api4\Contact;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class NullValueTest extends UnitTestCase {

  public function setUpHeadless() {
    $format = '{contact.first_name}{ }{contact.last_name}';
    \Civi::settings()->set('display_name_format', $format);
    return parent::setUpHeadless();
  }

  public function testStringNull() {
    $contact = Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Joseph')
      ->addValue('last_name', 'null')
      ->addValue('contact_type', 'Individual')
      ->execute()
      ->first();

    $this->assertSame('Null', $contact['last_name']);
    $this->assertSame('Joseph Null', $contact['display_name']);
  }

  public function testSettingToNull() {
    $contact = Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'ILoveMy')
      ->addValue('last_name', 'LastName')
      ->addValue('contact_type', 'Individual')
      ->execute()
      ->first();

    $this->assertSame('ILoveMy LastName', $contact['display_name']);
    $contactId = $contact['id'];

    $contact = Contact::update()
      ->setCheckPermissions(FALSE)
      ->addWhere('id', '=', $contactId)
      ->addValue('last_name', NULL)
      ->execute()
      ->first();

    $this->assertSame(NULL, $contact['last_name']);
    $this->assertSame('ILoveMy', $contact['display_name']);
  }

  public function testSaveWithReload() {
    $contact = Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Firsty')
      ->addValue('last_name', 'Lasty')
      ->execute()
      ->first();

    $activity = Activity::create()
      ->setCheckPermissions(FALSE)
      ->addValue('source_contact_id', $contact['id'])
      ->addValue('activity_type_id', 1)
      ->addValue('subject', 'hello')
      ->execute()
      ->first();

    $this->assertEquals('hello', $activity['subject']);

    $saved = Activity::save()
      ->setCheckPermissions(FALSE)
      ->addRecord(['id' => $activity['id'], 'subject' => NULL])
      ->execute()
      ->first();

    $this->assertNull($saved['subject']);
    $this->assertArrayNotHasKey('activity_date_time', $saved);

    $saved = Activity::save()
      ->setCheckPermissions(FALSE)
      ->addRecord(['id' => $activity['id'], 'subject' => NULL])
      ->setReload(TRUE)
      ->execute()
      ->first();

    $this->assertNull($saved['subject']);
    $this->assertArrayHasKey('activity_date_time', $saved);

  }

}
