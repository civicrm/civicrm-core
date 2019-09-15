<?php

namespace api\v4\Action;

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

}
