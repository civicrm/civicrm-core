<?php

namespace api\v4\Action;

use Civi\Api4\Contact;
use api\v4\UnitTestCase;

/**
 * Class UpdateContactTest
 * @package api\v4\Action
 * @group headless
 */
class UpdateContactTest extends UnitTestCase {

  public function testUpdateWithIdInWhere() {
    $contactId = Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Johann')
      ->addValue('last_name', 'Tester')
      ->addValue('contact_type', 'Individual')
      ->execute()
      ->first()['id'];

    $contact = Contact::update()
      ->setCheckPermissions(FALSE)
      ->addWhere('id', '=', $contactId)
      ->addValue('first_name', 'Testy')
      ->execute()
      ->first();
    $this->assertEquals('Testy', $contact['first_name']);
    $this->assertEquals('Tester', $contact['last_name']);
  }

  public function testUpdateWithIdInValues() {
    $contactId = Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Bobby')
      ->addValue('last_name', 'Tester')
      ->addValue('contact_type', 'Individual')
      ->execute()
      ->first()['id'];

    $contact = Contact::update()
      ->setCheckPermissions(FALSE)
      ->addValue('id', $contactId)
      ->addValue('first_name', 'Billy')
      ->execute();
    $this->assertCount(1, $contact);
    $this->assertEquals($contactId, $contact[0]['id']);
    $this->assertEquals('Billy', $contact[0]['first_name']);
    $this->assertEquals('Tester', $contact[0]['last_name']);
  }

}
