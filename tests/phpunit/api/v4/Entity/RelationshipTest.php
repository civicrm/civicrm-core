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

namespace api\v4\Entity;

use Civi\Api4\Contact;
use api\v4\UnitTestCase;
use Civi\Api4\Relationship;
use Civi\Api4\RelationshipCache;
use Civi\Test\TransactionalInterface;

/**
 * Assert that interchanging data between APIv3 and APIv4 yields consistent
 * encodings.
 *
 * @group headless
 */
class RelationshipTest extends UnitTestCase implements TransactionalInterface {

  public function testRelCache() {
    $c1 = Contact::create(FALSE)->addValue('first_name', '1')->execute()->first()['id'];
    $c2 = Contact::create(FALSE)->addValue('first_name', '2')->execute()->first()['id'];
    Relationship::create(FALSE)
      ->setValues([
        'contact_id_a' => $c1,
        'contact_id_b' => $c2,
        'relationship_type_id' => 1,
      ])->execute();
    $cacheRecords = RelationshipCache::get(FALSE)
      ->addClause('OR', ['near_contact_id', '=', $c1], ['far_contact_id', '=', $c1])
      ->execute();
    $this->assertCount(2, $cacheRecords);
  }

}
