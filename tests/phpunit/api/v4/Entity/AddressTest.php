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

use api\v4\UnitTestCase;
use Civi\Api4\Address;
use Civi\Api4\Contact;
use Civi\Test\TransactionalInterface;

/**
 * Test Address functionality
 *
 * @group headless
 */
class AddressTest extends UnitTestCase implements TransactionalInterface {

  /**
   * Check that 2 addresses for the same contact can't both be primary
   */
  public function testPrimary() {
    $cid = Contact::create(FALSE)->addValue('first_name', uniqid())->execute()->single()['id'];

    $a1 = Address::create(FALSE)
      ->addValue('is_primary', TRUE)
      ->addValue('contact_id', $cid)
      ->addValue('location_type_id', 1)
      ->addValue('city', 'Somewhere')
      ->execute();

    $a2 = Address::create(FALSE)
      ->addValue('is_primary', TRUE)
      ->addValue('contact_id', $cid)
      ->addValue('location_type_id', 2)
      ->addValue('city', 'Elsewhere')
      ->execute();

    $addresses = Address::get(FALSE)
      ->addWhere('contact_id', '=', $cid)
      ->addOrderBy('id')
      ->execute();

    $this->assertFalse($addresses[0]['is_primary']);
    $this->assertTrue($addresses[1]['is_primary']);
  }

}
