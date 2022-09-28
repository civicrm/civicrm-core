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

use api\v4\UnitTestCase;
use Civi\Api4\Activity;
use Civi\Api4\Contact;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;

/**
 * @group headless
 */
class ChainTest extends UnitTestCase {

  public function tearDown(): void {
    CustomField::delete()
      ->setCheckPermissions(FALSE)
      ->addWhere('name', '=', 'FavPerson')
      ->addChain('group', CustomGroup::delete()->addWhere('name', '=', 'TestActCus'))
      ->execute();
    parent::tearDown();
  }

  public function testGetActionsWithFields() {
    $actions = \Civi\Api4\Activity::getActions()
      ->addChain('fields', \Civi\Api4\Activity::getFields()->setAction('$name'), 'name')
      ->execute()
      ->indexBy('name');

    $this->assertEquals('Array', $actions['getActions']['fields']['params']['data_type']);
  }

  public function testGetEntityWithActions() {
    $entities = \Civi\Api4\Entity::get()
      ->addSelect('name')
      ->setChain([
        'actions' => ['$name', 'getActions', ['select' => ['name']], 'name'],
      ])
      ->execute()
      ->indexBy('name');

    $this->assertArrayHasKey('replace', $entities['Contact']['actions']);
    $this->assertArrayHasKey('getLinks', $entities['Entity']['actions']);
    $this->assertArrayNotHasKey('replace', $entities['Entity']['actions']);
  }

  public function testContactCreateWithGroup() {
    $firstName = uniqid('cwtf');
    $lastName = uniqid('cwtl');

    $contact = Contact::create()
      ->addValue('first_name', $firstName)
      ->addValue('last_name', $lastName)
      ->addChain('group', \Civi\Api4\Group::create()->addValue('title', '$display_name'), 0)
      ->addChain('add_to_group', \Civi\Api4\GroupContact::create()->addValue('contact_id', '$id')->addValue('group_id', '$group.id'), 0)
      ->addChain('check_group', \Civi\Api4\GroupContact::get()->addWhere('group_id', '=', '$group.id'))
      ->execute()
      ->first();

    $this->assertCount(1, $contact['check_group']);
    $this->assertEquals($contact['id'], $contact['check_group'][0]['contact_id']);
    $this->assertEquals($contact['group']['id'], $contact['check_group'][0]['group_id']);
  }

  public function testWithContactRef() {
    CustomGroup::create()
      ->setCheckPermissions(FALSE)
      ->addValue('name', 'TestActCus')
      ->addValue('extends', 'Activity')
      ->addChain('field1', CustomField::create()
        ->addValue('label', 'FavPerson')
        ->addValue('custom_group_id', '$id')
        ->addValue('html_type', 'Autocomplete-Select')
        ->addValue('data_type', 'ContactReference')
      )
      ->execute();

    $sourceId = Contact::create()->addValue('first_name', 'Source')->execute()->first()['id'];

    $created = Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Fav')
      ->addChain('activity', Activity::create()
        ->addValue('activity_type_id:name', 'Meeting')
        ->addValue('source_contact_id', $sourceId)
        ->addValue('TestActCus.FavPerson', '$id'),
      0)
      ->execute()->first();

    $found = Activity::get()
      ->addSelect('TestActCus.*')
      ->addWhere('id', '=', $created['activity']['id'])
      ->addChain('contact', Contact::get()
        // Test that we can access an array key with a dot in it (and it won't be confused with dot notation)
        ->addWhere('id', '=', '$TestActCus.FavPerson'),
      0)
      ->addChain('contact2', Contact::get()
        // Test that we can access a value within an array using dot notation
        ->addWhere('id', '=', '$contact.id'),
      0)
      ->execute()->first();

    $this->assertEquals('Fav', $found['contact']['first_name']);
    $this->assertEquals('Fav', $found['contact2']['first_name']);
  }

}
