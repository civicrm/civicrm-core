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

use Civi\Api4\Contact;

/**
 * @group headless
 */
class ContactGetTest extends \api\v4\UnitTestCase {

  public function testGetDeletedContacts() {
    $last_name = uniqid('deleteContactTest');

    $bob = Contact::create()
      ->setValues(['first_name' => 'Bob', 'last_name' => $last_name])
      ->execute()->first();

    $jan = Contact::create()
      ->setValues(['first_name' => 'Jan', 'last_name' => $last_name])
      ->execute()->first();

    $del = Contact::create()
      ->setValues(['first_name' => 'Del', 'last_name' => $last_name, 'is_deleted' => 1])
      ->execute()->first();

    // Deleted contacts are not fetched by default
    $this->assertCount(2, Contact::get()->addWhere('last_name', '=', $last_name)->selectRowCount()->execute());

    // You can search for them specifically
    $contacts = Contact::get()->addWhere('last_name', '=', $last_name)->addWhere('is_deleted', '=', 1)->addSelect('id')->execute();
    $this->assertEquals($del['id'], $contacts->first()['id']);

    // Or by id
    $this->assertCount(3, Contact::get()->addWhere('id', 'IN', [$bob['id'], $jan['id'], $del['id']])->selectRowCount()->execute());

    // Putting is_deleted anywhere in the where clause will disable the default
    $contacts = Contact::get()->addClause('OR', ['last_name', '=', $last_name], ['is_deleted', '=', 0])->addSelect('id')->execute();
    $this->assertContains($del['id'], $contacts->column('id'));
  }

}
