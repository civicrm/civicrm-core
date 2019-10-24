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
    // Maybe unnecessary but why not. Slightly different than an undefined
    // index later when it doesn't exist at all.
    $this->assertGreaterThan(0, $relationshipTypeNames['Spouse of']);

    /**
     * Set up xml.
     * In this sample case type for autoassignees we have:
     * - a_b unidirectional: Case Coordinator is
     * - b_a unidirectional: Benefits Specialist
     * - Bidirectional the way it's stored in 5.16+: Spouse of
     * - config entry pre-5.16, where it's a bidirectional relationship stored as b_a: Spouse of
     *
     * Also just for extra some non-ascii chars in here to show they
     * don't get borked.
     */
    $newCaseTypeXml = <<<ENDXML
<?xml version="1.0" encoding="utf-8" ?>

<CaseType>
<name>test_type</name>
<ActivityTypes>
<ActivityType>
<name>Open Case</name>
<max_instances>1</max_instances>
</ActivityType>
<ActivityType>
<name>Email</name>
</ActivityType>
<ActivityType>
<name>Follow up</name>
</ActivityType>
<ActivityType>
<name>Meeting</name>
</ActivityType>
<ActivityType>
<name>Phone Call</name>
</ActivityType>
<ActivityType>
<name>давид</name>
</ActivityTypes>
<ActivitySets>
<ActivitySet>
<name>standard_timeline</name>
<label>Standard Timeline</label>
<timeline>true</timeline>
<ActivityTypes>
<ActivityType>
<name>Open Case</name>
<status>Completed</status>
<label>Open Case</label>
<default_assignee_type>1</default_assignee_type>
</ActivityType>
</ActivityTypes>
</ActivitySet>
<ActivitySet>
<name>timeline_1</name>
<label>AnotherTimeline</label>
<timeline>true</timeline>
<ActivityTypes>
<ActivityType>
<name>Follow up</name>
<label>Follow up</label>
<status>Scheduled</status>
<reference_activity>Open Case</reference_activity>
<reference_offset>7</reference_offset>
<reference_select>newest</reference_select>
<default_assignee_type>2</default_assignee_type>
<default_assignee_relationship>{$relationshipTypeNames['Senior Services Coordinator']}_b_a</default_assignee_relationship>
<default_assignee_contact></default_assignee_contact>
</ActivityType>
<ActivityType>
<name>Follow up</name>
<label>Follow up</label>
<status>Scheduled</status>
<reference_activity>Open Case</reference_activity>
<reference_offset>14</reference_offset>
<reference_select>newest</reference_select>
<default_assignee_type>2</default_assignee_type>
<default_assignee_relationship>{$relationshipTypeNames['Benefits Specialist']}_a_b</default_assignee_relationship>
<default_assignee_contact></default_assignee_contact>
</ActivityType>
<ActivityType>
<name>Follow up</name>
<label>Follow up</label>
<status>Scheduled</status>
<reference_activity>Open Case</reference_activity>
<reference_offset>21</reference_offset>
<reference_select>newest</reference_select>
<default_assignee_type>2</default_assignee_type>
<default_assignee_relationship>{$relationshipTypeNames['Spouse of']}_a_b</default_assignee_relationship>
<default_assignee_contact></default_assignee_contact>
</ActivityType>
<ActivityType>
<name>Follow up</name>
<label>Follow up</label>
<status>Scheduled</status>
<reference_activity>Open Case</reference_activity>
<reference_offset>28</reference_offset>
<reference_select>newest</reference_select>
<default_assignee_type>2</default_assignee_type>
<default_assignee_relationship>{$relationshipTypeNames['Spouse of']}_b_a</default_assignee_relationship>
<default_assignee_contact></default_assignee_contact>
</ActivityType>
</ActivityTypes>
</ActivitySet>
</ActivitySets>
<CaseRoles>
<RelationshipType>
<name>Senior Services Coordinator</name>
<creator>1</creator>
<manager>1</manager>
</RelationshipType>
<RelationshipType>
<name>Spouse of</name>
</RelationshipType>
<RelationshipType>
<name>Benefits Specialist is</name>
</RelationshipType>
</CaseRoles>
<RestrictActivityAsgmtToCmsUser>0</RestrictActivityAsgmtToCmsUser>
</CaseType>
ENDXML;

    $dao = new CRM_Case_DAO_CaseType();
    $dao->name = 'test_type';
    $dao->title = 'Test Type';
    $dao->is_active = 1;
    $dao->definition = $newCaseTypeXml;
    $dao->insert();

    $caseTypeId = $dao->id;

    // run the task
    $upgrader = new CRM_Upgrade_Incremental_php_FiveTwenty();
    $upgrader->changeCaseTypeAutoassignee();

    // Check if the case type is what we expect. It should be identical except
    // the b_a spouse one should get converted to the a_b direction.
    $expectedCaseTypeXml = str_replace(
      "<default_assignee_relationship>{$relationshipTypeNames['Spouse of']}_b_a",
      "<default_assignee_relationship>{$relationshipTypeNames['Spouse of']}_a_b",
      $newCaseTypeXml
    );

    //echo $expectedCaseTypeXml;

    // Get the updated case type and check.
    $dao = CRM_Core_DAO::executeQuery("SELECT definition FROM civicrm_case_type WHERE id = {$caseTypeId}");
    $dao->fetch();

    $this->assertEquals($expectedCaseTypeXml, $dao->definition);
  }

}
