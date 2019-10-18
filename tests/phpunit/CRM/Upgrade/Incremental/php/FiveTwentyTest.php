<?php
require_once 'CiviTest/CiviCaseTestCase.php';

/**
 * Class CRM_Upgrade_Incremental_php_FiveTwentyTest
 * @group headless
 */
class CRM_Upgrade_Incremental_php_FiveTwentyTest extends CiviCaseTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * Test that the upgrade task changes the direction but only
   * for bidirectional relationship types that are b_a.
   */
  public function testChangeCaseTypeAutoassignee() {

    // We don't know what the ids are for the relationship types since it
    // seems to depend what ran before us, so retrieve them first and go by
    // name.
    // Also spouse might not exist.

    $result = $this->callAPISuccess('RelationshipType', 'get', ['limit' => 0])['values'];
    // Get list of ids keyed on name.
    $relationshipTypeNames = array_column($result, 'id', 'name_b_a');

    // Create spouse if none.
    if (!isset($relationshipTypeNames['Spouse of'])) {
      $spouseId = $this->relationshipTypeCreate([
        'name_a_b' => 'Spouse of',
        'name_b_a' => 'Spouse of',
        'label_a_b' => 'Spouse of',
        'label_b_a' => 'Spouse of',
        'contact_type_a' => 'Individual',
        'contact_type_b' => 'Individual',
      ]);
      $relationshipTypeNames['Spouse of'] = $spouseId;
    }

    // Add a test case type that has all the different types of assignees
    // by relationship.
    $newCaseTypeParams = [
      'name' => 'test_type',
      'title' => 'Test Type',
      'is_active' => '1',
      'weight' => '1',
      'definition' => [
        'restrictActivityAsgmtToCmsUser' => '0',
        'activityAsgmtGrps' => [],
        'activityTypes' => [
          0 => [
            'name' => 'Open Case',
            'max_instances' => '1',
          ],
          1 => [
            'name' => 'Email',
          ],
          2 => [
            'name' => 'Follow up',
          ],
          3 => [
            'name' => 'Meeting',
          ],
          4 => [
            'name' => 'Phone Call',
          ],
        ],
        'activitySets' => [
          0 => [
            'name' => 'standard_timeline',
            'label' => 'Standard Timeline',
            'timeline' => '1',
            'activityTypes' => [
              0 => [
                'name' => 'Open Case',
                'status' => 'Completed',
                'label' => 'Open Case',
                'default_assignee_type' => '1',
              ],
            ],
          ],
          1 => [
            'name' => 'timeline_1',
            'label' => 'AnotherTimeline',
            'timeline' => '1',
            'activityTypes' => [
              // unidirectional: Homeless Services Coordinator is
              0 => [
                'name' => 'Follow up',
                'label' => 'Follow up',
                'status' => 'Scheduled',
                'reference_activity' => 'Open Case',
                'reference_offset' => '7',
                'reference_select' => 'newest',
                'default_assignee_type' => '2',
                'default_assignee_relationship' => "{$relationshipTypeNames['Homeless Services Coordinator']}_b_a",
                'default_assignee_contact' => '',
              ],
              // unidirectional in reverse: Benefits Specialist
              1 => [
                'name' => 'Follow up',
                'label' => 'Follow up',
                'status' => 'Scheduled',
                'reference_activity' => 'Open Case',
                'reference_offset' => '14',
                'reference_select' => 'newest',
                'default_assignee_type' => '2',
                'default_assignee_relationship' => "{$relationshipTypeNames['Benefits Specialist']}_a_b",
                'default_assignee_contact' => '',
              ],
              // bidirectional the way it's stored in 5.16+: Spouse of
              2 => [
                'name' => 'Follow up',
                'label' => 'Follow up',
                'status' => 'Scheduled',
                'reference_activity' => 'Open Case',
                'reference_offset' => '21',
                'reference_select' => 'newest',
                'default_assignee_type' => '2',
                'default_assignee_relationship' => "{$relationshipTypeNames['Spouse of']}_a_b",
                'default_assignee_contact' => '',
              ],
              // This simulates a config entry pre-5.16, where it's a bidirectional relationship stored as b_a
              3 => [
                'name' => 'Follow up',
                'label' => 'Follow up',
                'status' => 'Scheduled',
                'reference_activity' => 'Open Case',
                'reference_offset' => '28',
                'reference_select' => 'newest',
                'default_assignee_type' => '2',
                'default_assignee_relationship' => "{$relationshipTypeNames['Spouse of']}_b_a",
                'default_assignee_contact' => '',
              ],
            ],
          ],
        ],
        'caseRoles' => [
          0 => [
            'name' => 'Homeless Services Coordinator',
            'creator' => '1',
            'manager' => '1',
          ],
          1 => [
            'name' => 'Spouse of',
          ],
          2 => [
            'name' => 'Benefits Specialist is',
          ],
        ],
      ],
      'is_forkable' => '1',
      'is_forked' => '',
    ];

    $result = $this->callAPISuccess('CaseType', 'create', $newCaseTypeParams);
    $caseTypeId = $result['id'];

    // run the task
    $upgrader = new CRM_Upgrade_Incremental_php_FiveTwenty();
    $upgrader->changeCaseTypeAutoassignee();

    // Check if the case type is what we expect. It should be identical except
    // the b_a spouse one should get converted to the a_b direction.
    $expectedCaseTypeParams = $newCaseTypeParams;
    $expectedCaseTypeParams['definition']['activitySets'][1]['activityTypes'][3]['default_assignee_relationship'] = "{$relationshipTypeNames['Spouse of']}_a_b";
    $expectedCaseTypeParams['id'] = (string) $caseTypeId;

    // Get the updated case type and check.
    $result = $this->callAPISuccess('CaseType', 'get', ['id' => $caseTypeId])['values'];
    $updatedCaseType = $result[$caseTypeId];

    // arrrh
    $this->fudgeUpdatedCaseType($updatedCaseType);

    $this->assertEquals($expectedCaseTypeParams, $updatedCaseType);
  }

  /**
   * Unfortunately we have to fudge a few things since for one the api returns
   * an extra array that isn't actually part of the definition.
   * Also there's a problem when the nested innermost value is an empty
   * array, because that isn't a valid xml string, and if we had used that
   * as an input parameter to create, it gives a warning which during tests
   * is a fail.
   * i.e. in normal usage something like
   * <default_assignee_contact></default_assignee_contact> will return []
   * during api get, but if you use [] as input to create it doesn't like
   * it. So above in our definition we used ''. But now that comes back as []
   * so adjust so it matches.
   * If we leave it out of the definition completely, we're not testing that
   * the upgrade will do the right thing with it, because they do exist in
   * the wild.
   *
   * @param $updatedCaseType array
   */
  private function fudgeUpdatedCaseType(&$updatedCaseType) {
    unset($updatedCaseType['definition']['timelineActivityTypes']);
    if (!isset($updatedCaseType['definition']['activityAsgmtGrps'])) {
      $updatedCaseType['definition']['activityAsgmtGrps'] = [];
    }
    $updatedCaseType['definition']['activitySets'][1]['activityTypes'][0]['default_assignee_contact'] = '';
    $updatedCaseType['definition']['activitySets'][1]['activityTypes'][1]['default_assignee_contact'] = '';
    $updatedCaseType['definition']['activitySets'][1]['activityTypes'][2]['default_assignee_contact'] = '';
    $updatedCaseType['definition']['activitySets'][1]['activityTypes'][3]['default_assignee_contact'] = '';
  }

}
