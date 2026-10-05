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
use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Activity;
use Civi\Api4\CaseActivity;
use Civi\Api4\CiviCase;
use Civi\Api4\Relationship;
use Civi\Api4\RelationshipType;

/**
 * @group headless
 */
class CaseTest extends Api4TestBase {

  public function setUp(): void {
    parent::setUp();
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
  }

  public function testGetFields(): void {
    $fields = CiviCase::getFields(FALSE)
      ->setAction('create')
      ->setLoadOptions(['id', 'name', 'label'])
      ->execute()->indexBy('name');

    $encounterMediums = array_column($fields['medium_id']['options'], NULL, 'name');
    $this->assertArrayHasKey('in_person', $encounterMediums);
    $this->assertEquals(['name', 'label', 'description'], $fields['medium_id']['suffixes']);

    $this->assertSame('Number', $fields['duration']['input_type']);
    $this->assertSame('Text', $fields['location']['input_type']);
    $this->assertSame('EntityRef', $fields['creator_id']['input_type']);
    $this->assertSame('user_contact_id', $fields['creator_id']['default_value']);
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

  public function testCaseManagerId(): void {
    $uid = $this->createLoggedInUser();
    $contactID = $this->createTestRecord('Contact')['id'];

    // housing_support's "Homeless Services Coordinator" role has both
    // creator=1 and manager=1, so opening the case as the logged-in user
    // makes them both the creator and the case manager.
    $case = $this->createTestRecord('Case', [
      'creator_id' => 'user_contact_id',
      'contact_id' => $contactID,
    ]);

    $result = CiviCase::get(FALSE)
      ->addWhere('id', '=', $case['id'])
      ->addSelect('case_manager_id')
      ->execute()
      ->single();

    $this->assertEquals($uid, $result['case_manager_id']);
  }

  public function testCaseManagerIdNullWhenCaseTypeHasNoManagerRole(): void {
    $uid = $this->createLoggedInUser();
    $contactID = $this->createTestRecord('Contact')['id'];

    // Same shape as the default test CaseType definition, except the
    // "Parent of" role is only flagged as creator, not manager - so no
    // relationship type/direction is configured as the case manager role
    // for this case type at all.
    $caseType = $this->createTestRecord('CaseType', [
      'title' => 'Test Case Type No Manager',
      'name' => 'test_case_type_no_manager',
      'definition' => [
        'activityTypes' => [
          ['name' => 'Open Case', 'max_instances' => 1],
          ['name' => 'Follow up'],
        ],
        'activitySets' => [
          [
            'name' => 'standard_timeline',
            'label' => 'Standard Timeline',
            'timeline' => 1,
            'activityTypes' => [
              ['name' => 'Open Case', 'status' => 'Completed'],
            ],
          ],
        ],
        'caseRoles' => [
          ['name' => 'Parent of', 'creator' => 1],
        ],
      ],
    ]);

    $case = $this->createTestRecord('Case', [
      'case_type_id' => $caseType['id'],
      'creator_id' => 'user_contact_id',
      'contact_id' => $contactID,
    ]);

    // The creator relationship still exists (Parent of, creator=1)...
    $relationships = Relationship::get(FALSE)
      ->addWhere('case_id', '=', $case['id'])
      ->execute();
    $this->assertCount(1, $relationships);
    $this->assertEquals($uid, $relationships[0]['contact_id_b']);

    // ...but since no role on this case type is flagged as manager,
    // case_manager_id has nothing to resolve to.
    $result = CiviCase::get(FALSE)
      ->addWhere('id', '=', $case['id'])
      ->addSelect('case_manager_id')
      ->execute()
      ->single();

    $this->assertNull($result['case_manager_id']);
  }

  public function testCaseRoleFieldsForCaseManager(): void {
    $uid = $this->createLoggedInUser();
    $contactID = $this->createTestRecord('Contact')['id'];

    // housing_support's "Homeless Services Coordinator" role has both
    // creator=1 and manager=1, so opening the case as the logged-in user
    // makes them both the creator and the case manager.
    $case = $this->createTestRecord('Case', [
      'creator_id' => 'user_contact_id',
      'contact_id' => $contactID,
    ]);

    $expectedRole = $this->getExpectedCaseRole($case['id'], $uid);

    $result = CiviCase::get(FALSE)
      ->addWhere('id', '=', $case['id'])
      ->addSelect('my_case_role', 'is_my_case', 'is_my_managed_case')
      ->execute()
      ->single();

    $this->assertEquals($expectedRole, $result['my_case_role']);
    $this->assertTrue($result['is_my_case']);
    $this->assertTrue($result['is_my_managed_case']);
  }

  public function testCaseRoleFieldsForUninvolvedUser(): void {
    $this->createLoggedInUser();
    $contactID = $this->createTestRecord('Contact')['id'];
    $otherContactID = $this->createTestRecord('Contact')['id'];

    // The case is opened by a different contact, so the logged-in user has
    // no relationship to it at all.
    $case = $this->createTestRecord('Case', [
      'creator_id' => $otherContactID,
      'contact_id' => $contactID,
    ]);

    $result = CiviCase::get(FALSE)
      ->addWhere('id', '=', $case['id'])
      ->addSelect('my_case_role', 'is_my_case', 'is_my_managed_case')
      ->execute()
      ->single();

    $this->assertNull($result['my_case_role']);
    $this->assertFalse($result['is_my_case']);
    $this->assertFalse($result['is_my_managed_case']);
  }

  public function testCaseRoleFieldsForInvolvedNonManager(): void {
    $this->createLoggedInUser();
    $contactID = $this->createTestRecord('Contact')['id'];

    $case = $this->createTestRecord('Case', [
      'creator_id' => 'user_contact_id',
      'contact_id' => $contactID,
    ]);

    // Switch the logged-in user to a second contact who holds housing_support's
    // "Health Services Coordinator" role on the same case - a role that's
    // neither creator nor manager, so they're involved without managing it.
    $coordinatorID = $this->createLoggedInUser();
    $relationshipTypeID = RelationshipType::get(FALSE)
      ->addWhere('label_b_a', '=', 'Health Services Coordinator')
      ->addSelect('id')
      ->execute()->single()['id'];
    $relationship = $this->createTestRecord('Relationship', [
      'relationship_type_id' => $relationshipTypeID,
      'contact_id_a' => $contactID,
      'contact_id_b' => $coordinatorID,
      'case_id' => $case['id'],
      'is_active' => TRUE,
    ]);

    $expectedRole = $this->getExpectedCaseRole($case['id'], $coordinatorID, $relationship['id']);

    $result = CiviCase::get(FALSE)
      ->addWhere('id', '=', $case['id'])
      ->addSelect('my_case_role', 'is_my_case', 'is_my_managed_case')
      ->execute()
      ->single();

    $this->assertEquals($expectedRole, $result['my_case_role']);
    $this->assertTrue($result['is_my_case']);
    $this->assertFalse($result['is_my_managed_case']);
  }

  /**
   * Mirrors CaseRoleSpecProvider::renderMyCaseRoleSql()'s direction logic:
   * the role label is read from whichever side of the relationship type
   * the given user occupies, rather than assuming a fixed label column.
   */
  private function getExpectedCaseRole(int $caseID, int $userID, ?int $relationshipID = NULL): ?string {
    $query = Relationship::get(FALSE)
      ->addWhere('case_id', '=', $caseID)
      ->addSelect('contact_id_b', 'relationship_type_id.label_a_b', 'relationship_type_id.label_b_a');
    if ($relationshipID) {
      $query->addWhere('id', '=', $relationshipID);
    }
    $relationship = $query->execute()->single();

    return $relationship['contact_id_b'] == $userID
      ? $relationship['relationship_type_id.label_a_b']
      : $relationship['relationship_type_id.label_b_a'];
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

  public function testCaseActivityPermission(): void {
    $case1 = $this->createTestRecord('Case')['id'];
    $userId = $this->createLoggedInUser();
    $case2 = $this->createTestRecord('Case', [
      'creator_id' => $userId,
    ])['id'];

    $act1 = $this->createTestRecord('Activity', [
      'case_id' => $case1,
    ])['id'];
    $act2 = $this->createTestRecord('Activity', [
      'case_id' => $case2,
    ])['id'];
    $act3 = $this->createTestRecord('Activity')['id'];

    // No CiviCase permission
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'view all contacts',
    ];

    $result = Activity::get()
      ->addWhere('id', 'IN', [$act1, $act2, $act3])
      ->execute()->column('id');
    $this->assertCount(1, $result);
    $this->assertEquals($act3, $result[0]);
    try {
      CiviCase::get()->execute();
      $this->fail('Expected UnauthorizedException');
    }
    catch (UnauthorizedException $e) {
    }

    // Without any CiviCase permission, ensure `case_id` in the where clause doesn't cause errors
    $result = Activity::get()
      ->addWhere('id', 'IN', [$act1, $act2, $act3])
      ->addWhere('case_id', 'IS EMPTY')
      ->execute()->column('id');
    $this->assertCount(1, $result);
    $this->assertEquals($act3, $result[0]);

    // CiviCase permission limited to "my cases"
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'view all contacts',
      'access my cases and activities',
    ];

    $result = Activity::get()
      ->addWhere('id', 'IN', [$act1, $act2])
      ->execute()->column('id');
    $this->assertCount(1, $result);
    $this->assertEquals($act2, $result[0]);
    $result = CiviCase::get()
      ->addWhere('id', 'IN', [$case1, $case2])
      ->execute()->column('id');
    $this->assertCount(1, $result);
    $this->assertEquals($case2, $result[0]);

    // CiviCase permission for all non-deleted cases
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'view all contacts',
      'access all cases and activities',
    ];

    $result = Activity::get()
      ->addWhere('id', 'IN', [$act1, $act2])
      ->execute()->column('id');
    $this->assertCount(2, $result);
    $result = CiviCase::get()
      ->addWhere('id', 'IN', [$case1, $case2])
      ->execute()->column('id');
    $this->assertCount(2, $result);

    CiviCase::update(FALSE)
      ->addWhere('id', '=', $case2)
      ->addValue('is_deleted', TRUE)
      ->execute();

    $result = Activity::get()
      ->addWhere('id', 'IN', [$act1, $act2])
      ->execute()->column('id');
    $this->assertCount(1, $result);
    $this->assertEquals($act1, $result[0]);
    $result = CiviCase::get()
      ->addWhere('id', 'IN', [$case1, $case2])
      ->execute()->column('id');
    $this->assertCount(1, $result);
    $this->assertEquals($case1, $result[0]);

    // CiviCase permission for all contacts and cases
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'view all contacts',
      'access deleted contacts',
      'access all cases and activities',
      'administer CiviCase',
    ];

    $result = Activity::get()
      ->addWhere('id', 'IN', [$act1, $act2])
      ->execute()->column('id');
    $this->assertCount(2, $result);
    $result = CiviCase::get()
      ->addWhere('id', 'IN', [$case1, $case2])
      ->execute()->column('id');
    $this->assertCount(2, $result);

    $result = Activity::get()
      ->addWhere('id', 'IN', [$act1, $act2, $act3])
      ->addWhere('case_id', 'IS EMPTY')
      ->execute()->column('id');
    $this->assertCount(1, $result);
    $this->assertEquals($act3, $result[0]);
  }

  public function testCaseActivityJoin(): void {
    $case1 = $this->createTestRecord('Case')['id'];
    $case2 = $this->createTestRecord('Case')['id'];
    $acts = $this->saveTestRecords('Activity', [
      'records' => [
        ['subject' => 'A'],
        ['subject' => 'B'],
        ['subject' => 'C'],
        ['subject' => 'D'],
        ['subject' => 'F', 'case_id' => $case2],
      ],
      'defaults' => ['case_id' => $case1],
    ])->column('id');

    $result = Activity::get(FALSE)
      ->addWhere('id', 'IN', $acts)
      ->addJoin('Case AS Activity_CaseActivity_Case_01', 'LEFT', 'CaseActivity', ['Activity_CaseActivity_Case_01.activity_id', '=', 'id'])
      ->addGroupBy('id')
      ->addGroupBy('Activity_CaseActivity_Case_01.id')
      ->addSelect('subject', 'Activity_CaseActivity_Case_01.id')
      ->execute()->column('Activity_CaseActivity_Case_01.id', 'subject');

    $this->assertEquals(['A' => $case1, 'B' => $case1, 'C' => $case1, 'D' => $case1, 'F' => $case2], $result);

    $result = Activity::get(FALSE)
      ->addWhere('id', 'IN', $acts)
      ->addWhere('Activity_CaseActivity_Case_01.id', '=', $case1)
      ->addJoin('Case AS Activity_CaseActivity_Case_01', 'LEFT', 'CaseActivity', ['Activity_CaseActivity_Case_01.activity_id', '=', 'id'])
      ->addGroupBy('id')
      ->addGroupBy('Activity_CaseActivity_Case_01.id')
      ->addSelect('subject', 'Activity_CaseActivity_Case_01.id')
      ->execute()->column('Activity_CaseActivity_Case_01.id', 'subject');

    $this->assertEquals(['A' => $case1, 'B' => $case1, 'C' => $case1, 'D' => $case1], $result);
  }

  public function testCaseSoftDelete(): void {
    // Create a case with a relationship and activity
    $case = $this->createTestRecord('Case');

    $activity = $this->createTestRecord('Activity', [
      'case_id' => $case['id'],
      'subject' => 'Test Activity',
    ]);

    // Get the relationship created with the case
    $relationships = Relationship::get(FALSE)
      ->addWhere('case_id', '=', $case['id'])
      ->execute();
    $relationshipId = $relationships[0]['id'];

    // Delete the case with useTrash = TRUE
    CiviCase::delete(FALSE)
      ->addWhere('id', '=', $case['id'])
      ->setUseTrash(TRUE)
      ->execute();

    // Assert the case still exists but is_deleted = TRUE
    $deletedCase = CiviCase::get(FALSE)
      ->addWhere('id', '=', $case['id'])
      ->execute()->single();
    $this->assertTrue($deletedCase['is_deleted']);

    // Assert the activity still exists but is_deleted = TRUE
    $deletedActivity = Activity::get(FALSE)
      ->addWhere('id', '=', $activity['id'])
      ->execute()->single();
    $this->assertTrue($deletedActivity['is_deleted']);

    // Assert the relationship still exists but is_active = FALSE
    $deletedRelationship = Relationship::get(FALSE)
      ->addWhere('id', '=', $relationshipId)
      ->execute()->single();
    $this->assertFalse($deletedRelationship['is_active']);
  }

}
