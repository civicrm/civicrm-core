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
    try {
      $map->getLink('foo', 'bar');
    }
    catch (\API_Exception $e) {
      $exception = $e;
    }
    $this->assertStringContainsString('not found', $exception->getMessage());
  }

  public function testWillHavePathWithSingleJump() {
    $phoneTable = new Table('civicrm_phone');
    $locationTable = new Table('civicrm_location_type');
    $link = new Joinable('civicrm_location_type', 'id', 'location');
    $phoneTable->addTableLink('location_type_id', $link);

    $map = new SchemaMap();
    $map->addTables([$phoneTable, $locationTable]);

    $this->assertNotEmpty($map->getLink('civicrm_phone', 'location'));
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

    $this->assertEmpty($map->getLink('contact', 'foo'));
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

    $this->assertEmpty($map->getLink('first', 'fifth'));
  }

}
