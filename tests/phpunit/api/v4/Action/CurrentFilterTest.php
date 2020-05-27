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
 * $Id$
 *
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

    $getCurrent = (array) Relationship::get()->setCurrent(TRUE)->execute()->indexBy('id');
    $notCurrent = (array) Relationship::get()->setCurrent(FALSE)->execute()->indexBy('id');
    $getAll = (array) Relationship::get()->execute()->indexBy('id');

    $this->assertArrayHasKey($current['id'], $getAll);
    $this->assertArrayHasKey($indefinite['id'], $getAll);
    $this->assertArrayHasKey($expiring['id'], $getAll);
    $this->assertArrayHasKey($past['id'], $getAll);
    $this->assertArrayHasKey($inactive['id'], $getAll);

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
  }

}
