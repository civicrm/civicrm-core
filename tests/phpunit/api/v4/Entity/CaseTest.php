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

use api\v4\Api4TestBase;
use Civi\Api4\Activity;
use Civi\Api4\CaseActivity;
use Civi\Api4\Relationship;

/**
 * @group headless
 */
class CaseTest extends Api4TestBase {

  public function setUp(): void {
    parent::setUp();
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
  }

  public function testCreateUsingLoggedInUser(): void {
    $uid = $this->createLoggedInUser();

    $contactID = $this->createTestRecord('Contact')['id'];

    $case = $this->createTestRecord('Case', [
      'creator_id' => 'user_contact_id',
      'contact_id' => $contactID,
    ]);

    $relationships = Relationship::get(FALSE)
      ->addWhere('case_id', '=', $case['id'])
      ->execute();

    $this->assertCount(1, $relationships);
    $this->assertEquals($uid, $relationships[0]['contact_id_b']);
    $this->assertEquals($contactID, $relationships[0]['contact_id_a']);
  }

  public function testCgExtendsObjects(): void {
    $this->createTestRecord('CaseType', [
      'title' => 'Test Case Type',
      'name' => 'test_case_type1',
    ]);

    $field = \Civi\Api4\CustomGroup::getFields(FALSE)
      ->setLoadOptions(TRUE)
      ->addValue('extends', 'Case')
      ->addWhere('name', '=', 'extends_entity_column_value')
      ->execute()
      ->first();

    $this->assertContains('Test Case Type', $field['options']);
  }

  public function testCaseActivity(): void {
    $case1 = $this->createTestRecord('Case');
    $case2 = $this->createTestRecord('Case');

    $activity1 = $this->createTestRecord('Activity', [
      'case_id' => $case1['id'],
    ]);

    $activity2 = $this->createTestRecord('Activity', [
      'case_id' => $case2['id'],
    ]);

    $get1 = Activity::get(FALSE)
      ->addWhere('case_id', '=', $case1['id'])
      ->execute()
      ->column('id');

    $this->assertContains($activity1['id'], $get1);
    $this->assertNotContains($activity2['id'], $get1);

    Activity::update(FALSE)
      ->addWhere('id', '=', $activity1['id'])
      ->addValue('case_id', $case2['id'])
      ->execute();

    // Both activities now belong to case 2
    $get2 = CaseActivity::get(FALSE)
      ->addWhere('case_id', '=', $case2['id'])
      ->execute()
      ->column('activity_id');
    $this->assertContains($activity1['id'], $get2);
    $this->assertContains($activity2['id'], $get2);

    // Ensure it's been moved out of case 1
    $get1 = CaseActivity::get(FALSE)
      ->addWhere('case_id', '=', $case1['id'])
      ->execute()
      ->column('activity_id');
    $this->assertNotContains($activity1['id'], $get1);

    Activity::update(FALSE)
      ->addWhere('id', '=', $activity1['id'])
      ->addValue('case_id', NULL)
      ->execute();

    // Activity 1 has been removed
    $get2 = CaseActivity::get(FALSE)
      ->addWhere('case_id', '=', $case2['id'])
      ->execute()
      ->column('activity_id');
    $this->assertNotContains($activity1['id'], $get2);
    $this->assertContains($activity2['id'], $get2);
  }

  public function testMultipleCaseActivity(): void {
    $case1 = $this->createTestRecord('Case');
    $case2 = $this->createTestRecord('Case');

    $activity = $this->createTestRecord('Activity', [
      'case_id' => [$case1['id'], $case2['id']],
    ]);

    $get1 = CaseActivity::get(FALSE)
      ->addWhere('activity_id', '=', $activity['id'])
      ->execute()
      ->column('case_id');
    $this->assertCount(2, $get1);
    $this->assertContains($case1['id'], $get1);
    $this->assertContains($case2['id'], $get1);

    // Ensure updating the activity doesn't change the case assoc
    Activity::update(FALSE)
      ->addValue('id', $activity['id'])
      ->execute();

    $get1 = CaseActivity::get(FALSE)
      ->addWhere('activity_id', '=', $activity['id'])
      ->execute()
      ->column('case_id');
    $this->assertCount(2, $get1);
    $this->assertContains($case1['id'], $get1);
    $this->assertContains($case2['id'], $get1);

    // Delete the case assoc
    Activity::update(FALSE)
      ->addValue('id', $activity['id'])
      ->addValue('case_id', [])
      ->execute();

    $get1 = CaseActivity::get(FALSE)
      ->addWhere('activity_id', '=', $activity['id'])
      ->execute();
    $this->assertCount(0, $get1);
  }

}
