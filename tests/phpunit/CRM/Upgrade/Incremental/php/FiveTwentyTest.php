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

  /**
   * Test that the upgrade task converts case role <name>'s that
   * are labels to their name.
   */
  public function testConvertRoleLabelsToNames() {

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

    // Add one with changed labels
    $id = $this->relationshipTypeCreate([
      'name_a_b' => 'Wallet Inspector is',
      'name_b_a' => 'Wallet Inspector',
      'label_a_b' => 'has as Wallet Inspector',
      'label_b_a' => 'is Wallet Inspector of',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
    ]);
    $relationshipTypeNames['Wallet Inspector'] = $id;

    // Add one with non-ascii characters.
    $id = $this->relationshipTypeCreate([
      'name_a_b' => 'абвгде is',
      'name_b_a' => 'абвгде',
      'label_a_b' => 'абвгде is',
      'label_b_a' => 'абвгде',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
    ]);
    $relationshipTypeNames['Ascii'] = $id;

    // Add one with non-ascii characters changed labels.
    $id = $this->relationshipTypeCreate([
      'name_a_b' => 'αβγδ is',
      'name_b_a' => 'αβγδ',
      'label_a_b' => 'αβγδ is changed',
      'label_b_a' => 'αβγδ changed',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
    ]);
    $relationshipTypeNames['Ascii changed'] = $id;

    // Create some case types
    $caseTypes = $this->createCaseTypes($relationshipTypeNames, 1);

    // run the task
    $upgrader = new CRM_Upgrade_Incremental_php_FiveTwenty();
    // first, preupgrade messages should be blank here
    $preupgradeMessages = $upgrader->_changeCaseTypeLabelToName(TRUE);
    $this->assertEmpty($preupgradeMessages);

    // Now the actual run
    $upgrader->changeCaseTypeLabelToName();

    // Get the updated case types and check.
    $sqlParams = [
      1 => [implode(',', array_keys($caseTypes)), 'String'],
    ];
    $dao = CRM_Core_DAO::executeQuery("SELECT id, name, definition FROM civicrm_case_type WHERE id IN (%1)", $sqlParams);
    while ($dao->fetch()) {
      $this->assertEquals($caseTypes[$dao->id]['expected'], $dao->definition, "Case type {$dao->name}");
      // clean up
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_case_type WHERE id = {$dao->id}");
    }

    //
    // Second pass, where we have some edge cases.
    //

    // Add a relationship type that has the same labels as another.
    $id = $this->relationshipTypeCreate([
      'name_a_b' => 'mixedupab',
      'name_b_a' => 'mixedupba',
      'label_a_b' => 'Benefits Specialist',
      'label_b_a' => 'Benefits Specialist is',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
    ]);
    $relationshipTypeNames['mixedup'] = $id;

    // Add a relationship type that appears to be bidirectional but different
    // names.
    $id = $this->relationshipTypeCreate([
      'name_a_b' => 'diffnameab',
      'name_b_a' => 'diffnameba',
      'label_a_b' => 'Archenemy of',
      'label_b_a' => 'Archenemy of',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
    ]);
    $relationshipTypeNames['diffname'] = $id;

    // Second pass for case type creation.
    $caseTypes = $this->createCaseTypes($relationshipTypeNames, 2);

    // run the task
    $upgrader = new CRM_Upgrade_Incremental_php_FiveTwenty();
    // first, check preupgrade messages
    $preupgradeMessages = $upgrader->_changeCaseTypeLabelToName(TRUE);
    $this->assertEquals($this->getExpectedUpgradeMessages(), $preupgradeMessages);

    // Now the actual run
    $upgrader->changeCaseTypeLabelToName();

    // Get the updated case types and check.
    $sqlParams = [
      1 => [implode(',', array_keys($caseTypes)), 'String'],
    ];
    $dao = CRM_Core_DAO::executeQuery("SELECT id, name, definition FROM civicrm_case_type WHERE id IN (%1)", $sqlParams);
    while ($dao->fetch()) {
      $this->assertEquals($caseTypes[$dao->id]['expected'], $dao->definition, "Case type {$dao->name}");
      // clean up
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_case_type WHERE id = {$dao->id}");
    }
  }

  /**
   * Set up some original and expected xml pairs.
   *
   * @param $relationshipTypeNames array
   * @param $stage int
   *   We run it in a couple passes because we want to test with and without
   *   warning messages.
   * @return array
   */
  private function createCaseTypes($relationshipTypeNames, $stage) {
    $xmls = [];

    switch ($stage) {
      case 1:
        $newCaseTypeXml = <<<ENDXMLSIMPLE
<?xml version="1.0" encoding="utf-8" ?>

<CaseType>
<name>simple</name>
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
<RelationshipType>
<name>is Wallet Inspector of</name>
</RelationshipType>
<RelationshipType>
<name>has as Wallet Inspector</name>
</RelationshipType>
<RelationshipType>
<name>абвгде</name>
</RelationshipType>
<RelationshipType>
<name>αβγδ changed</name>
</RelationshipType>
</CaseRoles>
<RestrictActivityAsgmtToCmsUser>0</RestrictActivityAsgmtToCmsUser>
</CaseType>
ENDXMLSIMPLE;

        $expectedCaseTypeXml = <<<ENDXMLSIMPLEEXPECTED
<?xml version="1.0" encoding="utf-8" ?>

<CaseType>
<name>simple</name>
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
<RelationshipType>
<name>Wallet Inspector</name>
</RelationshipType>
<RelationshipType>
<name>Wallet Inspector is</name>
</RelationshipType>
<RelationshipType>
<name>абвгде</name>
</RelationshipType>
<RelationshipType>
<name>αβγδ</name>
</RelationshipType>
</CaseRoles>
<RestrictActivityAsgmtToCmsUser>0</RestrictActivityAsgmtToCmsUser>
</CaseType>
ENDXMLSIMPLEEXPECTED;

        $caseTypeId = $this->addCaseType('simple', $newCaseTypeXml);
        $xmls[$caseTypeId] = [
          'id' => $caseTypeId,
          'expected' => $expectedCaseTypeXml,
        ];
        break;

      case 2:
        // Note for these ones the roles that have warnings should remain
        // unchanged if they choose to continue with the upgrade.

        $newCaseTypeXml = <<<ENDXMLMIXEDUP
<?xml version="1.0" encoding="utf-8" ?>

<CaseType>
<name>mixedup</name>
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
<RelationshipType>
<name>is Wallet Inspector of</name>
</RelationshipType>
<RelationshipType>
<name>has as Wallet Inspector</name>
</RelationshipType>
<RelationshipType>
<name>абвгде</name>
</RelationshipType>
<RelationshipType>
<name>αβγδ changed</name>
</RelationshipType>
<RelationshipType>
<name>Benefits Specialist</name>
</RelationshipType>
<RelationshipType>
<name>Mythical Unicorn</name>
</RelationshipType>
</CaseRoles>
<RestrictActivityAsgmtToCmsUser>0</RestrictActivityAsgmtToCmsUser>
</CaseType>
ENDXMLMIXEDUP;

        $expectedCaseTypeXml = <<<ENDXMLMIXEDUPEXPECTED
<?xml version="1.0" encoding="utf-8" ?>

<CaseType>
<name>mixedup</name>
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
<RelationshipType>
<name>Wallet Inspector</name>
</RelationshipType>
<RelationshipType>
<name>Wallet Inspector is</name>
</RelationshipType>
<RelationshipType>
<name>абвгде</name>
</RelationshipType>
<RelationshipType>
<name>αβγδ</name>
</RelationshipType>
<RelationshipType>
<name>Benefits Specialist</name>
</RelationshipType>
<RelationshipType>
<name>Mythical Unicorn</name>
</RelationshipType>
</CaseRoles>
<RestrictActivityAsgmtToCmsUser>0</RestrictActivityAsgmtToCmsUser>
</CaseType>
ENDXMLMIXEDUPEXPECTED;

        $caseTypeId = $this->addCaseType('mixedup', $newCaseTypeXml);
        $xmls[$caseTypeId] = [
          'id' => $caseTypeId,
          'expected' => $expectedCaseTypeXml,
        ];

        $newCaseTypeXml = <<<ENDXMLDIFFNAME
<?xml version="1.0" encoding="utf-8" ?>

<CaseType>
<name>diffname</name>
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
<RelationshipType>
<name>is Wallet Inspector of</name>
</RelationshipType>
<RelationshipType>
<name>has as Wallet Inspector</name>
</RelationshipType>
<RelationshipType>
<name>абвгде</name>
</RelationshipType>
<RelationshipType>
<name>αβγδ changed</name>
</RelationshipType>
<RelationshipType>
<name>Archenemy of</name>
</RelationshipType>
</CaseRoles>
<RestrictActivityAsgmtToCmsUser>0</RestrictActivityAsgmtToCmsUser>
</CaseType>
ENDXMLDIFFNAME;

        $expectedCaseTypeXml = <<<ENDXMLDIFFNAMEEXPECTED
<?xml version="1.0" encoding="utf-8" ?>

<CaseType>
<name>diffname</name>
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
<RelationshipType>
<name>Wallet Inspector</name>
</RelationshipType>
<RelationshipType>
<name>Wallet Inspector is</name>
</RelationshipType>
<RelationshipType>
<name>абвгде</name>
</RelationshipType>
<RelationshipType>
<name>αβγδ</name>
</RelationshipType>
<RelationshipType>
<name>Archenemy of</name>
</RelationshipType>
</CaseRoles>
<RestrictActivityAsgmtToCmsUser>0</RestrictActivityAsgmtToCmsUser>
</CaseType>
ENDXMLDIFFNAMEEXPECTED;

        $caseTypeId = $this->addCaseType('diffname', $newCaseTypeXml);
        $xmls[$caseTypeId] = [
          'id' => $caseTypeId,
          'expected' => $expectedCaseTypeXml,
        ];

        break;

      default:
        break;
    }

    return $xmls;
  }

  /**
   * @return array
   */
  private function getExpectedUpgradeMessages() {
    return [
      "Case Type 'mixedup', role 'Benefits Specialist is' has an ambiguous configuration where the role matches multiple labels and so can't be automatically updated. See the administration console status messages for more info.",

      "Case Type 'mixedup', role 'Benefits Specialist' has an ambiguous configuration where the role matches multiple labels and so can't be automatically updated. See the administration console status messages for more info.",

      "Case Type 'mixedup', role 'Mythical Unicorn' doesn't seem to be a valid role. See the administration console status messages for more info.",

      "Case Type 'diffname', role 'Benefits Specialist is' has an ambiguous configuration where the role matches multiple labels and so can't be automatically updated. See the administration console status messages for more info.",

      "Case Type 'diffname', role 'Archenemy of' has an ambiguous configuration and can't be automatically updated. See the administration console status messages for more info.",
    ];
  }

  /**
   * Helper to add a case type to the database.
   *
   * @param $name string
   * @param $xml string
   *
   * @return int
   */
  private function addCaseType($name, $xml) {
    $dao = new CRM_Case_DAO_CaseType();
    $dao->name = $name;
    $dao->title = $name;
    $dao->is_active = 1;
    $dao->definition = $xml;
    $dao->insert();

    return $dao->id;
  }

}
