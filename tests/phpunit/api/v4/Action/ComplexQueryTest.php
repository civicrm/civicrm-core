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

use api\v4\UnitTestCase;
use Civi\Api4\Activity;
use Civi\Api4\Contact;
use Civi\Test\CiviEnvBuilder;

/**
 * @group headless
 *
 * This class tests a series of complex query situations described in the
 * initial APIv4 specification
 */
class ComplexQueryTest extends UnitTestCase {

  public function setUpHeadless(): CiviEnvBuilder {
    $this->loadDataSet('DefaultDataSet');
    return parent::setUpHeadless();
  }

  public function tearDown(): void {
    $relatedTables = [
      'civicrm_activity',
      'civicrm_activity_contact',
    ];
    $this->cleanup(['tablesToTruncate' => $relatedTables]);
    parent::tearDown();
  }

  /**
   * Fetch all phone call activities
   * Expects at least one activity loaded from the data set.
   *
   * @throws \API_Exception
   */
  public function testGetAllHousingSupportActivities(): void {
    $results = Activity::get(FALSE)
      ->addWhere('activity_type_id:name', '=', 'Phone Call')
      ->execute();

    $this->assertGreaterThan(0, count($results));
  }

  /**
   *
   */
  public function testGetWithCount() {
    $myName = uniqid('count');
    for ($i = 1; $i <= 20; ++$i) {
      Contact::create()
        ->addValue('first_name', "Contact $i")
        ->addValue('last_name', $myName)
        ->setCheckPermissions(FALSE)->execute();
    }

    $get1 = Contact::get()
      ->addWhere('last_name', '=', $myName)
      ->selectRowCount()
      ->addSelect('first_name')
      ->setLimit(10)
      ->setDebug(TRUE)
      ->setCheckPermissions(FALSE)->execute();

    $this->assertEquals(20, $get1->count());
    $this->assertCount(10, (array) $get1);

  }

  /**
   * Fetch contacts named 'Bob' and all of their blue activities
   */
  public function testGetAllBlueActivitiesForBobs() {

  }

  /**
   * Get all contacts in a zipcode and return their Home or Work email addresses
   */
  public function testGetHomeOrWorkEmailsForContactsWithZipcode() {

  }

  /**
   * Fetch all activities where Bob is the assignee or source
   */
  public function testGetActivitiesWithBobAsAssigneeOrSource() {

  }

  /**
   * Get all contacts which
   * (a) have address in zipcode 94117 or 94118 or in city "San Francisco","LA"
   * and
   * (b) are not deceased and
   * (c) have a custom-field "most_important_issue=Environment".
   */
  public function testAWholeLotOfConditions() {

  }

  /**
   * Get participants who attended CiviCon 2012 but not CiviCon 2013.
   * Return their name and email.
   */
  public function testGettingNameAndEmailOfAttendeesOfCiviCon2012Only() {

  }

}
