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
 * Test class for CRM_Activity_BAO_ActivityTarget BAO
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Activity_BAO_ActivityTargetTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   *
   * This method is called after a test is executed.
   */
  protected function tearDown() {
  }

  public function testRetrieveTargetIdsByActivityIdZeroID() {
    $this->activityCreate();
    $target = CRM_Activity_BAO_ActivityTarget::retrieveTargetIdsByActivityId(0);
    $this->assertSame($target, [], 'No targets returned');
  }

  public function testRetrieveTargetIdsByActivityIdOneID() {
    $activity = $this->activityCreate();

    $targetIDs = CRM_Activity_BAO_ActivityTarget::retrieveTargetIdsByActivityId($activity['id']);

    // assert that we have at least one targetID
    $this->assertEquals(count($targetIDs), 1, 'One target ID match for activity');
    $this->assertEquals($targetIDs[0], $activity['target_contact_id'], 'The returned target contacts ids match');
  }

}
