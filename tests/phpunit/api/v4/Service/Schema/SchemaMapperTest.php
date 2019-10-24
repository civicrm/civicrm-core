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


namespace api\v4\Service\Schema;

use Civi\Api4\Service\Schema\Joinable\Joinable;
use Civi\Api4\Service\Schema\SchemaMap;
use Civi\Api4\Service\Schema\Table;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class SchemaMapperTest extends UnitTestCase {

  public function testWillHaveNoPathWithNoTables() {
    $map = new SchemaMap();
    $this->assertEmpty($map->getPath('foo', 'bar'));
  }

  public function testWillHavePathWithSingleJump() {
    $phoneTable = new Table('civicrm_phone');
    $locationTable = new Table('civicrm_location_type');
    $link = new Joinable('civicrm_location_type', 'id', 'location');
    $phoneTable->addTableLink('location_type_id', $link);

    $map = new SchemaMap();
    $map->addTables([$phoneTable, $locationTable]);

    $this->assertNotEmpty($map->getPath('civicrm_phone', 'location'));
  }

  public function testWillHavePathWithDoubleJump() {
    $activity = new Table('activity');
    $activityContact = new Table('activity_contact');
    $middleLink = new Joinable('activity_contact', 'activity_id');
    $contactLink = new Joinable('contact', 'id');
    $activity->addTableLink('id', $middleLink);
    $activityContact->addTableLink('contact_id', $contactLink);

    $map = new SchemaMap();
    $map->addTables([$activity, $activityContact]);

    $this->assertNotEmpty($map->getPath('activity', 'contact'));
  }

  public function testPathWithTripleJoin() {
    $first = new Table('first');
    $second = new Table('second');
    $third = new Table('third');
    $first->addTableLink('id', new Joinable('second', 'id'));
    $second->addTableLink('id', new Joinable('third', 'id'));
    $third->addTableLink('id', new Joinable('fourth', 'id'));

    $map = new SchemaMap();
    $map->addTables([$first, $second, $third]);

    $this->assertNotEmpty($map->getPath('first', 'fourth'));
  }

  public function testCircularReferenceWillNotBreakIt() {
    $contactTable = new Table('contact');
    $carTable = new Table('car');
    $carLink = new Joinable('car', 'id');
    $ownerLink = new Joinable('contact', 'id');
    $contactTable->addTableLink('car_id', $carLink);
    $carTable->addTableLink('owner_id', $ownerLink);

    $map = new SchemaMap();
    $map->addTables([$contactTable, $carTable]);

    $this->assertEmpty($map->getPath('contact', 'foo'));
  }

  public function testCannotGoOverJoinLimit() {
    $first = new Table('first');
    $second = new Table('second');
    $third = new Table('third');
    $fourth = new Table('fourth');
    $first->addTableLink('id', new Joinable('second', 'id'));
    $second->addTableLink('id', new Joinable('third', 'id'));
    $third->addTableLink('id', new Joinable('fourth', 'id'));
    $fourth->addTableLink('id', new Joinable('fifth', 'id'));

    $map = new SchemaMap();
    $map->addTables([$first, $second, $third, $fourth]);

    $this->assertEmpty($map->getPath('first', 'fifth'));
  }

}
