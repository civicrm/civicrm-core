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

use Civi\Api4\Relationship;
use api\v4\UnitTestCase;
use Civi\Api4\Contact;

/**
 * @group headless
 */
class CurrentFilterTest extends UnitTestCase {

  public function testCurrentRelationship() {
    $cid1 = Contact::create()->addValue('first_name', 'Bob1')->execute()->first()['id'];
    $cid2 = Contact::create()->addValue('first_name', 'Bob2')->execute()->first()['id'];

    $current = Relationship::create()->setValues([
      'relationship_type_id' => 1,
      'contact_id_a' => $cid1,
      'contact_id_b' => $cid2,
      'end_date' => 'now + 1 week',
    ])->execute()->first();
    $indefinite = Relationship::create()->setValues([
      'relationship_type_id' => 2,
      'contact_id_a' => $cid1,
      'contact_id_b' => $cid2,
    ])->execute()->first();
    $expiring = Relationship::create()->setValues([
      'relationship_type_id' => 3,
      'contact_id_a' => $cid1,
      'contact_id_b' => $cid2,
      'end_date' => 'now',
    ])->execute()->first();
    $past = Relationship::create()->setValues([
      'relationship_type_id' => 3,
      'contact_id_a' => $cid1,
      'contact_id_b' => $cid2,
      'end_date' => 'now - 1 week',
    ])->execute()->first();
    $inactive = Relationship::create()->setValues([
      'relationship_type_id' => 4,
      'contact_id_a' => $cid1,
      'contact_id_b' => $cid2,
      'is_active' => 0,
    ])->execute()->first();

    $getCurrent = Relationship::get()->addWhere('is_current', '=', TRUE)->execute()->indexBy('id');
    $notCurrent = Relationship::get()->addWhere('is_current', '=', FALSE)->execute()->indexBy('id');
    $getAll = Relationship::get()->addSelect('is_current')->execute()->indexBy('id');

    $this->assertTrue($getAll[$current['id']]['is_current']);
    $this->assertTrue($getAll[$indefinite['id']]['is_current']);
    $this->assertTrue($getAll[$expiring['id']]['is_current']);
    $this->assertFalse($getAll[$past['id']]['is_current']);
    $this->assertFalse($getAll[$inactive['id']]['is_current']);

    $this->assertArrayHasKey($current['id'], $getCurrent);
    $this->assertArrayHasKey($indefinite['id'], $getCurrent);
    $this->assertArrayHasKey($expiring['id'], $getCurrent);
    $this->assertArrayNotHasKey($past['id'], $getCurrent);
    $this->assertArrayNotHasKey($inactive['id'], $getCurrent);

    $this->assertArrayNotHasKey($current['id'], $notCurrent);
    $this->assertArrayNotHasKey($indefinite['id'], $notCurrent);
    $this->assertArrayNotHasKey($expiring['id'], $notCurrent);
    $this->assertArrayHasKey($past['id'], $notCurrent);
    $this->assertArrayHasKey($inactive['id'], $notCurrent);

    // Assert that "Extra" fields like is_current are not returned with select *
    $defaultGet = Relationship::get()->setLimit(1)->execute()->single();
    $this->assertArrayNotHasKey('is_current', $defaultGet);
    $starGet = Relationship::get()->addSelect('*')->setLimit(1)->execute()->single();
    $this->assertArrayNotHasKey('is_current', $starGet);
  }

}
