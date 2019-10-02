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
use Civi\Api4\Relationship;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class DateTest extends UnitTestCase {

  public function testRelationshipDate() {
    $c1 = Contact::create()
      ->addValue('first_name', 'c')
      ->addValue('last_name', 'one')
      ->execute()
      ->first()['id'];
    $c2 = Contact::create()
      ->addValue('first_name', 'c')
      ->addValue('last_name', 'two')
      ->execute()
      ->first()['id'];
    $r = Relationship::create()
      ->addValue('contact_id_a', $c1)
      ->addValue('contact_id_b', $c2)
      ->addValue('relationship_type_id', 1)
      ->addValue('start_date', 'now')
      ->addValue('end_date', 'now + 1 week')
      ->execute()
      ->first()['id'];
    $result = Relationship::get()
      ->addWhere('start_date', '=', 'now')
      ->addWhere('end_date', '>', 'now + 1 day')
      ->execute()
      ->indexBy('id');
    $this->assertArrayHasKey($r, $result);
    $result = Relationship::get()
      ->addWhere('start_date', '<', 'now')
      ->execute()
      ->indexBy('id');
    $this->assertArrayNotHasKey($r, $result);
  }

}
