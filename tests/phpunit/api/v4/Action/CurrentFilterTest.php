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
