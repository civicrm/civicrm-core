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


namespace api\v4\Query;

use Civi\Api4\Contact;
use api\v4\UnitTestCase;

/**
 * Class OneToOneJoinTest
 * @package api\v4\Query
 * @group headless
 */
class OneToOneJoinTest extends UnitTestCase {

  public function testOneToOneJoin() {
    $armenianContact = Contact::create()
      ->addValue('first_name', 'Contact')
      ->addValue('last_name', 'One')
      ->addValue('contact_type', 'Individual')
      ->addValue('preferred_language', 'hy_AM')
      ->execute()
      ->first();

    $basqueContact = Contact::create()
      ->addValue('first_name', 'Contact')
      ->addValue('last_name', 'Two')
      ->addValue('contact_type', 'Individual')
      ->addValue('preferred_language', 'eu_ES')
      ->execute()
      ->first();

    $contacts = Contact::get()
      ->addWhere('id', 'IN', [$armenianContact['id'], $basqueContact['id']])
      ->addSelect('preferred_language.label')
      ->addSelect('last_name')
      ->execute()
      ->indexBy('last_name')
      ->getArrayCopy();

    $this->assertEquals($contacts['One']['preferred_language.label'], 'Armenian');
    $this->assertEquals($contacts['Two']['preferred_language.label'], 'Basque');
  }

}
