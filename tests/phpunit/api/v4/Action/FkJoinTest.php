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

}
