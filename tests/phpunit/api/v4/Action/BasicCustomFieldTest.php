<?php

namespace api\v4\Action;

use Civi\Api4\Contact;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;

/**
 * @group headless
 */
class BasicCustomFieldTest extends BaseCustomValueTest {

  public function testWithSingleField() {

    $customGroup = CustomGroup::create()
      ->setCheckPermissions(FALSE)
      ->addValue('name', 'MyContactFields')
      ->addValue('extends', 'Contact')
      ->execute()
      ->first();

    CustomField::create()
      ->setCheckPermissions(FALSE)
      ->addValue('label', 'FavColor')
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'Text')
      ->addValue('data_type', 'String')
      ->execute();

    $contactId = Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Johann')
      ->addValue('last_name', 'Tester')
      ->addValue('contact_type', 'Individual')
      ->addValue('MyContactFields.FavColor', 'Red')
      ->execute()
      ->first()['id'];

    $contact = Contact::get()
      ->setCheckPermissions(FALSE)
      ->addSelect('first_name')
      ->addSelect('MyContactFields.FavColor')
      ->addWhere('id', '=', $contactId)
      ->addWhere('MyContactFields.FavColor', '=', 'Red')
      ->execute()
      ->first();

    $this->assertEquals('Red', $contact['MyContactFields.FavColor']);

    Contact::update()
      ->addWhere('id', '=', $contactId)
      ->addValue('MyContactFields.FavColor', 'Blue')
      ->execute();

    $contact = Contact::get()
      ->setCheckPermissions(FALSE)
      ->addSelect('MyContactFields.FavColor')
      ->addWhere('id', '=', $contactId)
      ->execute()
      ->first();

    $this->assertEquals('Blue', $contact['MyContactFields.FavColor']);
  }

  public function testWithTwoFields() {

    $customGroup = CustomGroup::create()
      ->setCheckPermissions(FALSE)
      ->addValue('name', 'MyContactFields')
      ->addValue('extends', 'Contact')
      ->execute()
      ->first();

    CustomField::create()
      ->setCheckPermissions(FALSE)
      ->addValue('label', 'FavColor')
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'Text')
      ->addValue('data_type', 'String')
      ->execute();

    CustomField::create()
      ->setCheckPermissions(FALSE)
      ->addValue('label', 'FavFood')
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'Text')
      ->addValue('data_type', 'String')
      ->execute();

    $contactId1 = Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Johann')
      ->addValue('last_name', 'Tester')
      ->addValue('MyContactFields.FavColor', 'Red')
      ->addValue('MyContactFields.FavFood', 'Cherry')
      ->execute()
      ->first()['id'];

    $contactId2 = Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'MaryLou')
      ->addValue('last_name', 'Tester')
      ->addValue('MyContactFields.FavColor', 'Purple')
      ->addValue('MyContactFields.FavFood', 'Grapes')
      ->execute()
      ->first()['id'];

    $contact = Contact::get()
      ->setCheckPermissions(FALSE)
      ->addSelect('first_name')
      ->addSelect('MyContactFields.FavColor')
      ->addSelect('MyContactFields.FavFood')
      ->addWhere('id', '=', $contactId1)
      ->addWhere('MyContactFields.FavColor', '=', 'Red')
      ->addWhere('MyContactFields.FavFood', '=', 'Cherry')
      ->execute()
      ->first();

    $this->assertArrayHasKey('MyContactFields.FavColor', $contact);
    $this->assertEquals('Red', $contact['MyContactFields.FavColor']);

    Contact::update()
      ->addWhere('id', '=', $contactId1)
      ->addValue('MyContactFields.FavColor', 'Blue')
      ->execute();

    $contact = Contact::get()
      ->setCheckPermissions(FALSE)
      ->addSelect('MyContactFields.FavColor')
      ->addWhere('id', '=', $contactId1)
      ->execute()
      ->first();

    $this->assertEquals('Blue', $contact['MyContactFields.FavColor']);

    $search = Contact::get()
      ->setCheckPermissions(FALSE)
      ->addClause('OR', ['MyContactFields.FavColor', '=', 'Blue'], ['MyContactFields.FavFood', '=', 'Grapes'])
      ->addSelect('id')
      ->addOrderBy('id')
      ->execute()
      ->indexBy('id');

    $this->assertEquals([$contactId1, $contactId2], array_keys((array) $search));

    $search = Contact::get()
      ->setCheckPermissions(FALSE)
      ->addClause('NOT', ['MyContactFields.FavColor', '=', 'Purple'], ['MyContactFields.FavFood', '=', 'Grapes'])
      ->addSelect('id')
      ->addOrderBy('id')
      ->execute()
      ->indexBy('id');

    $this->assertNotContains($contactId2, array_keys((array) $search));

    $search = Contact::get()
      ->setCheckPermissions(FALSE)
      ->addClause('NOT', ['MyContactFields.FavColor', '=', 'Purple'], ['MyContactFields.FavFood', '=', 'Grapes'])
      ->addSelect('id')
      ->addOrderBy('id')
      ->execute()
      ->indexBy('id');

    $this->assertContains($contactId1, array_keys((array) $search));
    $this->assertNotContains($contactId2, array_keys((array) $search));

    $search = Contact::get()
      ->setCheckPermissions(FALSE)
      ->setWhere([['NOT', ['OR', [['MyContactFields.FavColor', '=', 'Blue'], ['MyContactFields.FavFood', '=', 'Grapes']]]]])
      ->addSelect('id')
      ->addOrderBy('id')
      ->execute()
      ->indexBy('id');

    $this->assertNotContains($contactId1, array_keys((array) $search));
    $this->assertNotContains($contactId2, array_keys((array) $search));
  }

}
