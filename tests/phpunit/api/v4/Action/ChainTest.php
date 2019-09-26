<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */


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
