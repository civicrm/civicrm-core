<?php

namespace api\v4\Action;

use api\v4\UnitTestCase;

/**
 * @group headless
 */
class ChainTest extends UnitTestCase {

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

    $contact = \Civi\Api4\Contact::create()
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

}
