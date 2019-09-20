<?php
require_once 'CiviTest/CiviCaseTestCase.php';

/**
 * Class CRM_Case_PseudoConstantTest
 * @group headless
 */
class CRM_Case_XMLProcessor_ProcessTest extends CiviCaseTestCase {

  public function setUp() {
    parent::setUp();

    $this->defaultAssigneeOptionsValues = [];

    $this->setupContacts();
    $this->setupDefaultAssigneeOptions();
    $this->setupRelationships();
    $this->setupMoreRelationshipTypes();
    $this->setupActivityDefinitions();

    $this->process = new CRM_Case_XMLProcessor_Process();
  }

  public function tearDown() {
    $this->deleteMoreRelationshipTypes();

    parent::tearDown();
  }

  /**
   * Creates sample contacts.
   */
  protected function setUpContacts() {
    $this->contacts = [
      'ana' => $this->individualCreate(),
      'beto' => $this->individualCreate(),
      'carlos' => $this->individualCreate(),
    ];
  }

  /**
   * Adds the default assignee group and options to the test database.
   * It also stores the IDs of the options in an index.
   */
  protected function setupDefaultAssigneeOptions() {
    $options = [
      'NONE', 'BY_RELATIONSHIP', 'SPECIFIC_CONTACT', 'USER_CREATING_THE_CASE',
    ];

    CRM_Core_BAO_OptionGroup::ensureOptionGroupExists([
      'name' => 'activity_default_assignee',
    ]);

    foreach ($options as $option) {
      $optionValue = CRM_Core_BAO_OptionValue::ensureOptionValueExists([
        'option_group_id' => 'activity_default_assignee',
        'name' => $option,
        'label' => $option,
      ]);

      $this->defaultAssigneeOptionsValues[$option] = $optionValue['value'];
    }
  }

  /**
   * Adds a relationship between the activity's target contact and default assignee.
   */
  protected function setupRelationships() {
    $this->relationships = [
      'ana_is_pupil_of_beto' => [
        'type_id' => NULL,
        'name_a_b' => 'Pupil of',
        'name_b_a' => 'Instructor',
        'contact_id_a' => $this->contacts['ana'],
        'contact_id_b' => $this->contacts['beto'],
      ],
      'ana_is_spouse_of_carlos' => [
        'type_id' => NULL,
        'name_a_b' => 'Spouse of',
        'name_b_a' => 'Spouse of',
        'contact_id_a' => $this->contacts['ana'],
        'contact_id_b' => $this->contacts['carlos'],
      ],
      'unassigned_employee' => [
        'type_id' => NULL,
        'name_a_b' => 'Employee of',
        'name_b_a' => 'Employer',
      ],
    ];

    foreach ($this->relationships as $name => &$relationship) {
      $relationship['type_id'] = $this->relationshipTypeCreate([
        'contact_type_a' => 'Individual',
        'contact_type_b' => 'Individual',
        'name_a_b' => $relationship['name_a_b'],
        'label_a_b' => $relationship['name_a_b'],
        'name_b_a' => $relationship['name_b_a'],
        'label_b_a' => $relationship['name_b_a'],
      ]);

      if (isset($relationship['contact_id_a'])) {
        $this->callAPISuccess('Relationship', 'create', [
          'contact_id_a' => $relationship['contact_id_a'],
          'contact_id_b' => $relationship['contact_id_b'],
          'relationship_type_id' => $relationship['type_id'],
        ]);
      }
    }
  }

  /**
   * Set up some additional relationship types for some specific tests.
   */
  protected function setupMoreRelationshipTypes() {
    $this->moreRelationshipTypes = [
      'unidirectional_name_label_different' => [
        'type_id' => NULL,
        'name_a_b' => 'jm7ab',
        'label_a_b' => 'Jedi Master is',
        'name_b_a' => 'jm7ba',
        'label_b_a' => 'Jedi Master for',
        'description' => 'Jedi Master',
      ],
      'unidirectional_name_label_same' => [
        'type_id' => NULL,
        'name_a_b' => 'Quilt Maker is',
        'label_a_b' => 'Quilt Maker is',
        'name_b_a' => 'Quilt Maker for',
        'label_b_a' => 'Quilt Maker for',
        'description' => 'Quilt Maker',
      ],
      'bidirectional_name_label_different' => [
        'type_id' => NULL,
        'name_a_b' => 'f12',
        'label_a_b' => 'Friend of',
        'name_b_a' => 'f12',
        'label_b_a' => 'Friend of',
        'description' => 'Friend',
      ],
      'bidirectional_name_label_same' => [
        'type_id' => NULL,
        'name_a_b' => 'Enemy of',
        'label_a_b' => 'Enemy of',
        'name_b_a' => 'Enemy of',
        'label_b_a' => 'Enemy of',
        'description' => 'Enemy',
      ],
    ];

    foreach ($this->moreRelationshipTypes as &$relationship) {
      $relationship['type_id'] = $this->relationshipTypeCreate([
        'contact_type_a' => 'Individual',
        'contact_type_b' => 'Individual',
        'name_a_b' => $relationship['name_a_b'],
        'label_a_b' => $relationship['label_a_b'],
        'name_b_a' => $relationship['name_b_a'],
        'label_b_a' => $relationship['label_b_a'],
        'description' => $relationship['description'],
      ]);
    }
  }

  /**
   * Clean up additional relationship types (tearDown).
   */
  protected function deleteMoreRelationshipTypes() {
    foreach ($this->moreRelationshipTypes as $relationship) {
      $this->callAPISuccess('relationship_type', 'delete', ['id' => $relationship['type_id']]);
    }
  }

  /**
   * Defines the the activity parameters and XML definitions. These can be used
   * to create the activity.
   */
  protected function setupActivityDefinitions() {
    $activityTypeXml = '<activity-type><name>Open Case</name></activity-type>';
    $this->activityTypeXml = new SimpleXMLElement($activityTypeXml);
    $this->activityParams = [
      'activity_date_time' => date('Ymd'),
      'caseID' => $this->caseTypeId,
      'clientID' => $this->contacts['ana'],
      'creatorID' => $this->_loggedInUser,
    ];
  }

  /**
   * Tests the creation of activities where the default assignee should be the
   * target contact's instructor. Beto is the instructor for Ana.
   */
  public function testCreateActivityWithDefaultContactByRelationship() {
    $relationship = $this->relationships['ana_is_pupil_of_beto'];
    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['BY_RELATIONSHIP'];
    $this->activityTypeXml->default_assignee_relationship = "{$relationship['type_id']}_b_a";

    $this->process->createActivity($this->activityTypeXml, $this->activityParams);
    $this->assertActivityAssignedToContactExists($this->contacts['beto']);
  }

  /**
   * Tests when the default assignee relationship exists, but in the other direction only.
   * Ana is a pupil, but has no pupils related to her.
   */
  public function testCreateActivityWithDefaultContactByRelationshipMissing() {
    $relationship = $this->relationships['ana_is_pupil_of_beto'];
    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['BY_RELATIONSHIP'];
    $this->activityTypeXml->default_assignee_relationship = "{$relationship['type_id']}_a_b";

    $this->process->createActivity($this->activityTypeXml, $this->activityParams);
    $this->assertActivityAssignedToContactExists(NULL);
  }

  /**
   * Tests when the the default assignee relationship exists and is a bidirectional
   * relationship. Ana and Carlos are spouses.
   */
  public function testCreateActivityWithDefaultContactByRelationshipBidirectional() {
    $relationship = $this->relationships['ana_is_spouse_of_carlos'];
    $this->activityParams['clientID'] = $this->contacts['carlos'];
    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['BY_RELATIONSHIP'];
    $this->activityTypeXml->default_assignee_relationship = "{$relationship['type_id']}_a_b";

    $this->process->createActivity($this->activityTypeXml, $this->activityParams);
    $this->assertActivityAssignedToContactExists($this->contacts['ana']);
  }

  /**
   * Tests when the default assignee relationship does not exist. Ana is not an
   * employee for anyone.
   */
  public function testCreateActivityWithDefaultContactByRelationButTheresNoRelationship() {
    $relationship = $this->relationships['unassigned_employee'];
    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['BY_RELATIONSHIP'];
    $this->activityTypeXml->default_assignee_relationship = "{$relationship['type_id']}_b_a";

    $this->process->createActivity($this->activityTypeXml, $this->activityParams);
    $this->assertActivityAssignedToContactExists(NULL);
  }

  /**
   * Tests the creation of activities with default assignee set to a specific contact.
   */
  public function testCreateActivityAssignedToSpecificContact() {
    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['SPECIFIC_CONTACT'];
    $this->activityTypeXml->default_assignee_contact = $this->contacts['carlos'];

    $this->process->createActivity($this->activityTypeXml, $this->activityParams);
    $this->assertActivityAssignedToContactExists($this->contacts['carlos']);
  }

  /**
   * Tests the creation of activities with default assignee set to a specific contact,
   * but the contact does not exist.
   */
  public function testCreateActivityAssignedToNonExistantSpecificContact() {
    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['SPECIFIC_CONTACT'];
    $this->activityTypeXml->default_assignee_contact = 987456321;

    $this->process->createActivity($this->activityTypeXml, $this->activityParams);
    $this->assertActivityAssignedToContactExists(NULL);
  }

  /**
   * Tests the creation of activities with the default assignee being the one
   * creating the case's activity.
   */
  public function testCreateActivityAssignedToUserCreatingTheCase() {
    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['USER_CREATING_THE_CASE'];

    $this->process->createActivity($this->activityTypeXml, $this->activityParams);
    $this->assertActivityAssignedToContactExists($this->_loggedInUser);
  }

  /**
   * Tests the creation of activities when the default assignee is set to NONE.
   */
  public function testCreateActivityAssignedNoUser() {
    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['NONE'];

    $this->process->createActivity($this->activityTypeXml, $this->activityParams);
    $this->assertActivityAssignedToContactExists(NULL);
  }

  /**
   * Tests the creation of activities when the default assignee is set to NONE.
   */
  public function testCreateActivityWithNoDefaultAssigneeOption() {
    $this->process->createActivity($this->activityTypeXml, $this->activityParams);
    $this->assertActivityAssignedToContactExists(NULL);
  }

  /**
   * Asserts that an activity was created where the assignee was the one related
   * to the target contact.
   *
   * @param int|null $assigneeContactId the ID of the expected assigned contact or NULL if expected to be empty.
   */
  protected function assertActivityAssignedToContactExists($assigneeContactId) {
    $expectedContact = $assigneeContactId === NULL ? [] : [$assigneeContactId];
    $result = $this->callAPISuccess('Activity', 'get', [
      'target_contact_id' => $this->activityParams['clientID'],
      'return' => ['assignee_contact_id'],
    ]);
    $activity = CRM_Utils_Array::first($result['values']);

    $this->assertNotNull($activity, 'Target contact has no activities assigned to them');
    $this->assertEquals($expectedContact, $activity['assignee_contact_id'], 'Activity is not assigned to expected contact');
  }

  /**
   * Test that locateNameOrLabel does the right things.
   *
   * @dataProvider xmlDataProvider
   */
  public function testLocateNameOrLabel($xmlString, $expected) {
    $xmlObj = new SimpleXMLElement($xmlString);
    $this->assertEquals($expected, $this->process->locateNameOrLabel($xmlObj));
  }

  /**
   * Data provider for testLocateNameOrLabel
   * @return array
   */
  public function xmlDataProvider() {
    return [
      ['<RelationshipType><name>Senior Services Coordinator</name><creator>1</creator><manager>1</manager></RelationshipType>', 'Senior Services Coordinator'],
      ['<RelationshipType><name>Senior Services Coordinator</name></RelationshipType>', 'Senior Services Coordinator'],
      ['<RelationshipType><name>Lion Tamer&#39;s Obituary Writer</name></RelationshipType>', "Lion Tamer's Obituary Writer"],
      ['<RelationshipType><machineName>BP1234</machineName><name>Banana Peeler</name></RelationshipType>', 'BP1234'],
      ['<RelationshipType><machineName>BP1234</machineName><name>Banana Peeler</name><creator>1</creator><manager>1</manager></RelationshipType>', 'BP1234'],
      ['<RelationshipType><machineName>0</machineName><name>Assistant Level 0</name></RelationshipType>', '0'],
      ['<RelationshipType><machineName></machineName><name>Banana Peeler</name></RelationshipType>', 'Banana Peeler'],
      // hopefully nobody would do this
      ['<RelationshipType><machineName>null</machineName><name>Annulled Relationship</name></RelationshipType>', 'null'],
    ];
  }

  /**
   * Test that caseRole() doesn't have name and label mixed up
   *
   * @dataProvider xmlCaseRoleDataProvider
   */
  public function testCaseRole($key, $xmlString, $expected) {
    $xmlObj = new SimpleXMLElement($xmlString);

    // element 0 is direction (a_b), 1 is the text we want
    $expectedArray = empty($expected) ? [] : ["{$this->moreRelationshipTypes[$key]['type_id']}_{$expected[0]}" => $expected[1]];

    $this->assertEquals($expectedArray, $this->process->caseRoles($xmlObj->CaseRoles, FALSE));
  }

  /**
   * Data provider for testCaseRole
   * @return array
   */
  public function xmlCaseRoleDataProvider() {
    return [
      // Simulate one that has been converted to the format it should be going
      // forward, where name is the actual name, i.e. same as machineName.
      [
        'unidirectional_name_label_different',
        '<CaseType><CaseRoles><RelationshipType><machineName>jm7ba</machineName><name>jm7ba</name><creator>1</creator><manager>1</manager></RelationshipType></CaseRoles></CaseType>',
        ['a_b', 'Jedi Master is'],
      ],
      // Simulate one that is in some intermediate format for some reason. It
      // should still work anyway, but the <name> should be fixed in the xml at
      // that site to be the actual 'name', i.e. same as 'machineName'.
      [
        'unidirectional_name_label_different',
        '<CaseType><CaseRoles><RelationshipType><machineName>jm7ba</machineName><name>Jedi Master of</name><creator>1</creator><manager>1</manager></RelationshipType></CaseRoles></CaseType>',
        ['a_b', 'Jedi Master is'],
      ],
      // Simulate one that is still in old format, i.e. one that is still in
      // xml files that haven't been updated, or in the db but upgrade script
      // not run yet.
      // In this set name and label are the same in the
      // civicrm_relationship_type table.
      [
        'unidirectional_name_label_same',
        '<CaseType><CaseRoles><RelationshipType><name>Quilt Maker for</name><creator>1</creator><manager>1</manager></RelationshipType></CaseRoles></CaseType>',
        ['a_b', 'Quilt Maker is'],
      ],
      // Simulate one that is still in old format, i.e. one that is still in
      // xml files that haven't been updated, or in the db but upgrade script
      // not run yet.
      // In this set name and label are different in the
      // civicrm_relationship_type table.
      // NOTE: If name and label are different in the civicrm_relationship_type
      // table, this SHOULD fail (empty array). The person needs to either run
      // the upgrade script or change their xml file.
      [
        'unidirectional_name_label_different',
        '<CaseType><CaseRoles><RelationshipType><name>Jedi Master of</name><creator>1</creator><manager>1</manager></RelationshipType></CaseRoles></CaseType>',
        NULL,
      ],
      // Bidirectional relationship.
      [
        'bidirectional_name_label_different',
        '<CaseType><CaseRoles><RelationshipType><machineName>f12</machineName><name>Friend of</name><creator>1</creator><manager>1</manager></RelationshipType></CaseRoles></CaseType>',
        ['b_a', 'Friend of'],
      ],
      // Bidirectional relationship, without machineName.
      // Name and label the same.
      [
        'bidirectional_name_label_same',
        '<CaseType><CaseRoles><RelationshipType><name>Enemy of</name><creator>1</creator><manager>1</manager></RelationshipType></CaseRoles></CaseType>',
        ['b_a', 'Enemy of'],
      ],
      // Bidirectional relationship, without machineName.
      // Name and label different. Should FAIL (empty array).
      [
        'bidirectional_name_label_different',
        '<CaseType><CaseRoles><RelationshipType><name>Friend of</name><creator>1</creator><manager>1</manager></RelationshipType></CaseRoles></CaseType>',
        NULL,
      ],
    ];
  }

}
