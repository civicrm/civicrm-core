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

use api\v4\UnitTestCase;
use Civi\Api4\Activity;
use Civi\Api4\Contact;

/**
 * @group headless
 */
class FkJoinTest extends UnitTestCase {

  public function setUpHeadless() {
    $relatedTables = [
      'civicrm_activity',
      'civicrm_phone',
      'civicrm_activity_contact',
    ];
    $this->cleanup(['tablesToTruncate' => $relatedTables]);
    $this->loadDataSet('DefaultDataSet');

    return parent::setUpHeadless();
  }

  /**
   * Fetch all phone call activities. Expects a single activity
   * loaded from the data set.
   */
  public function testThreeLevelJoin() {
    $results = Activity::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('activity_type.name', '=', 'Phone Call')
      ->execute();

    $this->assertCount(1, $results);
  }

  public function testActivityContactJoin() {
    $results = Activity::get()
      ->setCheckPermissions(FALSE)
      ->addSelect('assignees.id')
      ->addSelect('assignees.first_name')
      ->addSelect('assignees.display_name')
      ->addWhere('assignees.first_name', '=', 'Phoney')
      ->execute();

    $firstResult = $results->first();

    $this->assertCount(1, $results);
    $this->assertTrue(is_array($firstResult['assignees']));

    $firstAssignee = array_shift($firstResult['assignees']);
    $this->assertEquals($firstAssignee['first_name'], 'Phoney');
  }

  public function testContactPhonesJoin() {
    $testContact = $this->getReference('test_contact_1');
    $testPhone = $this->getReference('test_phone_1');

    $results = Contact::get()
      ->setCheckPermissions(FALSE)
      ->addSelect('phones.phone')
      ->addWhere('id', '=', $testContact['id'])
      ->addWhere('phones.location_type.name', '=', 'Home')
      ->execute()
      ->first();

    $this->assertArrayHasKey('phones', $results);
    $this->assertCount(1, $results['phones']);
    $firstPhone = array_shift($results['phones']);
    $this->assertEquals($testPhone['phone'], $firstPhone['phone']);
  }

  public function testJoinWithLimit() {
    $base = function() {
      return Contact::get()
        ->setCheckPermissions(FALSE)
        ->addWhere('id', 'IN', [$this->getReference('test_contact_1')['id'], $this->getReference('test_contact_2')['id']])
        ->addSelect('id', 'display_name', 'phones.phone')
        ->setDebug(TRUE)
        ->addOrderBy('id', 'DESC');
    };

    $result1 = $base()->setLimit(1)->execute();
    $result2 = $base()->setLimit(2)->execute();
    $this->assertCount(2, $result2);
    $this->assertCount(1, $result1);
    $this->assertNotContains('DISTINCT', $result1->debug['sql'][0]);
    $this->assertContains('LIMIT', $result1->debug['sql'][0]);
    // FIXME: secondary query should not contain LIMIT. Needs work.
    // $this->assertNotContains('LIMIT', $result1->debug['sql'][1]);
  }

}
