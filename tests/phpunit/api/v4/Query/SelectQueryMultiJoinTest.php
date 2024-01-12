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


namespace api\v4\Query;

use Civi\Api4\Email;
use api\v4\Api4TestBase;

/**
 * Class SelectQueryMultiJoinTest
 * @package api\v4\Query
 * @group headless
 */
class SelectQueryMultiJoinTest extends Api4TestBase {

  public function testManyToOneSelect(): void {

    $contact1 = $this->createTestRecord('Contact', [
      'first_name' => 'First',
      'last_name' => 'Contact',
    ]);
    $firstEmail = $this->createTestRecord('Email', [
      'contact_id' => $contact1['id'],
      'location_type_id:name' => 'Home',
    ]);
    $secondEmail = $this->createTestRecord('Email', [
      'contact_id' => $contact1['id'],
      'location_type_id:name' => 'Work',
    ]);
    $contact2 = $this->createTestRecord('Contact', [
      'first_name' => 'Second',
      'last_name' => 'Contact',
    ]);
    $thirdEmail = $this->createTestRecord('Email', [
      'contact_id' => $contact2['id'],
      'location_type_id:name' => 'Home',
    ]);
    $fourthEmail = $this->createTestRecord('Email', [
      'contact_id' => $contact2['id'],
      'location_type_id:name' => 'Work',
    ]);

    $results = Email::get()
      ->addSelect('contact_id.display_name')
      ->execute()
      ->indexBy('id');

    $firstContactEmailIds = [$firstEmail['id'], $secondEmail['id']];
    $secondContactEmailIds = [$thirdEmail['id'], $fourthEmail['id']];

    foreach ($results as $id => $email) {
      $displayName = $email['contact_id.display_name'];
      if (in_array($id, $firstContactEmailIds)) {
        $this->assertEquals('First Contact', $displayName);
      }
      elseif (in_array($id, $secondContactEmailIds)) {
        $this->assertEquals('Second Contact', $displayName);
      }
    }
  }

}
