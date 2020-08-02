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

use Civi\Api4\Contact;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;

/**
 * @group headless
 */
class BasicCustomFieldTest extends BaseCustomValueTest {

  public function testWithSingleField() {

    $customGroup = CustomGroup::create(FALSE)
      ->addValue('name', 'MyIndividualFields')
      ->addValue('extends', 'Individual')
      ->execute()
      ->first();

    CustomField::create(FALSE)
      ->addValue('label', 'FavColor')
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'Text')
      ->addValue('data_type', 'String')
      ->execute();

    // Individual fields should show up when contact_type = null|Individual but not other contact types
    $getFields = Contact::getFields(FALSE);
    $this->assertContains('MyIndividualFields.FavColor', $getFields->execute()->column('name'));
    $this->assertContains('MyIndividualFields.FavColor', $getFields->setValues(['contact_type' => 'Individual'])->execute()->column('name'));
    $this->assertNotContains('MyIndividualFields.FavColor', $getFields->setValues(['contact_type' => 'Household'])->execute()->column('name'));

    $contactId = Contact::create(FALSE)
      ->addValue('first_name', 'Johann')
      ->addValue('last_name', 'Tester')
      ->addValue('contact_type', 'Individual')
      ->addValue('MyIndividualFields.FavColor', 'Red')
      ->execute()
      ->first()['id'];

    $contact = Contact::get(FALSE)
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

    $contact = Contact::get(FALSE)
      ->addSelect('MyIndividualFields.FavColor')
      ->addWhere('id', '=', $contactId)
      ->execute()
      ->first();

    $this->assertEquals('Blue', $contact['MyIndividualFields.FavColor']);
  }

  public function testWithTwoFields() {

    // First custom set
    CustomGroup::create(FALSE)
      ->addValue('name', 'MyContactFields')
      ->addValue('extends', 'Contact')
      ->addChain('field1', CustomField::create()
        ->addValue('label', 'FavColor')
        ->addValue('custom_group_id', '$id')
        ->addValue('html_type', 'Text')
        ->addValue('data_type', 'String'))
      ->addChain('field2', CustomField::create()
        ->addValue('label', 'FavFood')
        ->addValue('custom_group_id', '$id')
        ->addValue('html_type', 'Text')
        ->addValue('data_type', 'String'))
      ->execute();

    // Second custom set
    CustomGroup::create(FALSE)
      ->addValue('name', 'MyContactFields2')
      ->addValue('extends', 'Contact')
      ->addChain('field1', CustomField::create()
        ->addValue('label', 'FavColor')
        ->addValue('custom_group_id', '$id')
        ->addValue('html_type', 'Text')
        ->addValue('data_type', 'String'))
      ->addChain('field2', CustomField::create()
        ->addValue('label', 'FavFood')
        ->addValue('custom_group_id', '$id')
        ->addValue('html_type', 'Text')
        ->addValue('data_type', 'String'))
      ->execute();

    $contactId1 = Contact::create(FALSE)
      ->addValue('first_name', 'Johann')
      ->addValue('last_name', 'Tester')
      ->addValue('MyContactFields.FavColor', 'Red')
      ->addValue('MyContactFields.FavFood', 'Cherry')
      ->execute()
      ->first()['id'];

    $contactId2 = Contact::create(FALSE)
      ->addValue('first_name', 'MaryLou')
      ->addValue('last_name', 'Tester')
      ->addValue('MyContactFields.FavColor', 'Purple')
      ->addValue('MyContactFields.FavFood', 'Grapes')
      ->execute()
      ->first()['id'];

    $contact = Contact::get(FALSE)
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

    // By default custom fields are not returned
    $contact = Contact::get(FALSE)
      ->addWhere('id', '=', $contactId1)
      ->addWhere('MyContactFields.FavColor', '=', 'Red')
      ->addWhere('MyContactFields.FavFood', '=', 'Cherry')
      ->execute()
      ->first();
    $this->assertArrayNotHasKey('MyContactFields.FavColor', $contact);

    // Update 2nd set and ensure 1st hasn't changed
    Contact::update()
      ->addWhere('id', '=', $contactId1)
      ->addValue('MyContactFields2.FavColor', 'Orange')
      ->addValue('MyContactFields2.FavFood', 'Tangerine')
      ->execute();
    $contact = Contact::get(FALSE)
      ->addSelect('MyContactFields.FavColor', 'MyContactFields2.FavColor', 'MyContactFields.FavFood', 'MyContactFields2.FavFood')
      ->addWhere('id', '=', $contactId1)
      ->execute()
      ->first();
    $this->assertEquals('Red', $contact['MyContactFields.FavColor']);
    $this->assertEquals('Orange', $contact['MyContactFields2.FavColor']);
    $this->assertEquals('Cherry', $contact['MyContactFields.FavFood']);
    $this->assertEquals('Tangerine', $contact['MyContactFields2.FavFood']);

    // Update 1st set and ensure 2st hasn't changed
    Contact::update()
      ->addWhere('id', '=', $contactId1)
      ->addValue('MyContactFields.FavColor', 'Blue')
      ->execute();
    $contact = Contact::get(FALSE)
      ->addSelect('custom.*')
      ->addWhere('id', '=', $contactId1)
      ->execute()
      ->first();
    $this->assertEquals('Blue', $contact['MyContactFields.FavColor']);
    $this->assertEquals('Orange', $contact['MyContactFields2.FavColor']);
    $this->assertEquals('Cherry', $contact['MyContactFields.FavFood']);
    $this->assertEquals('Tangerine', $contact['MyContactFields2.FavFood']);

    $search = Contact::get(FALSE)
      ->addClause('OR', ['MyContactFields.FavColor', '=', 'Blue'], ['MyContactFields.FavFood', '=', 'Grapes'])
      ->addSelect('id')
      ->addOrderBy('id')
      ->execute()
      ->indexBy('id');

    $this->assertEquals([$contactId1, $contactId2], array_keys((array) $search));

    $search = Contact::get(FALSE)
      ->addClause('NOT', ['MyContactFields.FavColor', '=', 'Purple'], ['MyContactFields.FavFood', '=', 'Grapes'])
      ->addSelect('id')
      ->addOrderBy('id')
      ->execute()
      ->indexBy('id');

    $this->assertNotContains($contactId2, array_keys((array) $search));

    $search = Contact::get(FALSE)
      ->addClause('NOT', ['MyContactFields.FavColor', '=', 'Purple'], ['MyContactFields.FavFood', '=', 'Grapes'])
      ->addSelect('id')
      ->addOrderBy('id')
      ->execute()
      ->indexBy('id');

    $this->assertContains($contactId1, array_keys((array) $search));
    $this->assertNotContains($contactId2, array_keys((array) $search));

    $search = Contact::get(FALSE)
      ->setWhere([['NOT', ['OR', [['MyContactFields.FavColor', '=', 'Blue'], ['MyContactFields.FavFood', '=', 'Grapes']]]]])
      ->addSelect('id')
      ->addOrderBy('id')
      ->execute()
      ->indexBy('id');

    $this->assertNotContains($contactId1, array_keys((array) $search));
    $this->assertNotContains($contactId2, array_keys((array) $search));
  }

}
