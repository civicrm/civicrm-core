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

use Civi\Api4\Email;
use api\v4\UnitTestCase;
use Civi\Api4\Contact;

/**
 * @group headless
 */
class CopyTest extends UnitTestCase {

  public function testEmailCopy() {
    $cid1 = Contact::create()
      ->addValue('first_name', 'Lotsa')
      ->addValue('last_name', 'Emails')
      ->execute()
      ->first()['id'];
    $e1 = Email::create()
      ->setValues(['contact_id' => $cid1, 'email' => 'first@example.com', 'location_type_id' => 1])
      ->execute()
      ->first()['id'];
    $e2 = Email::create()
      ->setValues(['contact_id' => $cid1, 'email' => 'second@example.com', 'location_type_id' => 1])
      ->execute()
      ->first()['id'];

    $copyResult = Email::copy()
      ->addValue('location_type_id', 2)
      ->addWhere('contact_id', '=', $cid1)
      ->execute()
      ->indexBy('id');
    // Should have saved 2 records
    $this->assertCount(2, $copyResult);
    $this->assertArrayNotHasKey($e1, (array) $copyResult);
    $this->assertArrayNotHasKey($e2, (array) $copyResult);

    // Verify contact now has the new email records
    $results = Email::get()
      ->addWhere('contact_id', '=', $cid1)
      ->addOrderBy('id')
      ->execute()
      ->indexBy('id');
    $this->assertCount(4, $results);
    $this->assertEquals(1, $results->first()['location_type_id']);
    $this->assertEquals(2, $results->last()['location_type_id']);
  }

}
