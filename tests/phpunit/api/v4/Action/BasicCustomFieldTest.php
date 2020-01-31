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
      ->addValue('name', 'MyIndividualFields')
      ->addValue('extends', 'Individual')
      ->execute()
      ->first();

    CustomField::create()
      ->setCheckPermissions(FALSE)
      ->addValue('label', 'FavColor')
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'Text')
      ->addValue('data_type', 'String')
      ->execute();

    // Individual fields should show up when contact_type = null|Individual but not other contact types
    $getFields = Contact::getFields()->setCheckPermissions(FALSE);
    $this->assertContains('MyIndividualFields.FavColor', $getFields->execute()->column('name'));
    $this->assertContains('MyIndividualFields.FavColor', $getFields->setValues(['contact_type' => 'Individual'])->execute()->column('name'));
    $this->assertNotContains('MyIndividualFields.FavColor', $getFields->setValues(['contact_type' => 'Household'])->execute()->column('name'));

    $contactId = Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Johann')
      ->addValue('last_name', 'Tester')
      ->addValue('contact_type', 'Individual')
      ->addValue('MyIndividualFields.FavColor', 'Red')
      ->execute()
      ->first()['id'];

    $contact = Contact::get()
      ->setCheckPermissions(FALSE)
      ->addSelect('first_name')
      ->addSelect('MyIndividualFields.FavColor')
      ->addWhere('id', '=', $contactId)
      ->addWhere('MyIndividualFields.FavColor', '=', 'Red')
      ->execute()
      ->first();

    $this->assertEquals('Red', $contact['MyIndividualFields.FavColor']);

    Contact::update()
      ->addWhere('id', '=', $contactId)
      ->addValue('MyIndividualFields.FavColor', 'Blue')
      ->execute();

    $contact = Contact::get()
      ->setCheckPermissions(FALSE)
      ->addSelect('MyIndividualFields.FavColor')
      ->addWhere('id', '=', $contactId)
      ->execute()
      ->first();

    $this->assertEquals('Blue', $contact['MyIndividualFields.FavColor']);
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
