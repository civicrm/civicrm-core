<?php
require_once 'CiviTest/CiviCaseTestCase.php';

/**
 * Class CRM_Case_PseudoConstantTest
 * @group headless
 */
class CRM_Case_XMLProcessor_ProcessTest extends CiviCaseTestCase {

  public function setUp(): void {
    parent::setUp();

    $this->defaultAssigneeOptionsValues = [];

    $this->setupContacts();
    $this->setupDefaultAssigneeOptions();
    $this->setupRelationships();
    $this->setupMoreRelationshipTypes();
    $this->setupActivityDefinitions();

    $this->process = new CRM_Case_XMLProcessor_Process();
  }

  public function tearDown(): void {
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
      // @todo This seems wrong, it just happens to work out because both caseId and caseTypeId equal 1 in the stock setup here.
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
   * Test the creation of activities where the default assignee should not
   * end up being a contact from another case where it has the same client
   * and relationship.
   */
  public function testCreateActivityWithDefaultContactByRelationshipTwoCases() {
    /*
    At this point the stock setup looks like this:
    Case 1: no roles assigned
    Non-case relationship with ana as pupil of beto
    Non-case relationship with ana as spouse of carlos

    So we want to:
    Make another case for the same client ana.
    Add a pupil role on that new case with some other person.
    Make an activity on the first case.

    Since there is a non-case relationship of that type for the
    right person we do want it to take that one even though there is no role
    on the first case, i.e. it SHOULD fall back to non-case relationships.
    So this is test 1.

    Then we want to get rid of the non-case relationship and try again. In
    this situation it should not make any assignment, i.e. it should not
    take the other person from the other case. The original bug was that it
    would assign the activity to that other person from the other case. This
    is test 2.
     */

    $relationship = $this->relationships['ana_is_pupil_of_beto'];

    // Make another case and add a case role with the same relationship we
    // want, but a different person.
    $caseObj = $this->createCase($this->contacts['ana'], $this->_loggedInUser);
    $this->callAPISuccess('Relationship', 'create', [
      'contact_id_a' => $this->contacts['ana'],
      'contact_id_b' => $this->contacts['carlos'],
      'relationship_type_id' => $relationship['type_id'],
      'case_id' => $caseObj->id,
    ]);

    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['BY_RELATIONSHIP'];
    $this->activityTypeXml->default_assignee_relationship = "{$relationship['type_id']}_b_a";

    $this->process->createActivity($this->activityTypeXml, $this->activityParams);

    // We can't use assertActivityAssignedToContactExists because it assumes
    // there's only one activity in the database, but we have several from the
    // second case. We want the one we just created on the first case.
    $result = $this->callAPISuccess('Activity', 'get', [
      'case_id' => $this->activityParams['caseID'],
      'return' => ['assignee_contact_id'],
    ])['values'];
    $this->assertCount(1, $result);
    foreach ($result as $activity) {
      // Note the first parameter is turned into an array to match the second.
      $this->assertEquals([$this->contacts['beto']], $activity['assignee_contact_id']);
    }

    // Now remove the non-case relationship.
    $result = $this->callAPISuccess('Relationship', 'get', [
      'case_id' => ['IS NULL' => 1],
      'relationship_type_id' => $relationship['type_id'],
      'contact_id_a' => $this->contacts['ana'],
      'contact_id_b' => $this->contacts['beto'],
    ])['values'];
    $this->assertCount(1, $result);
    foreach ($result as $activity) {
      $result = $this->callAPISuccess('Relationship', 'delete', ['id' => $activity['id']]);
    }

    // Create another activity on the first case. Make it a different activity
    // type so we can find it better.
    $activityXml = '<activity-type><name>Follow up</name></activity-type>';
    $activityXmlElement = new SimpleXMLElement($activityXml);
    $activityXmlElement->default_assignee_type = $this->defaultAssigneeOptionsValues['BY_RELATIONSHIP'];
    $activityXmlElement->default_assignee_relationship = "{$relationship['type_id']}_b_a";
    $this->process->createActivity($activityXmlElement, $this->activityParams);

    $result = $this->callAPISuccess('Activity', 'get', [
      'case_id' => $this->activityParams['caseID'],
      'activity_type_id' => 'Follow up',
      'return' => ['assignee_contact_id'],
    ])['values'];
    $this->assertCount(1, $result);
    foreach ($result as $activity) {
      // It should be empty, not the contact from the second case.
      $this->assertEmpty($activity['assignee_contact_id']);
    }
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
   * Test that caseRoles() doesn't have name and label mixed up.
   *
   * @param $key string The array key in the moreRelationshipTypes array that
   *   is the relationship type we're currently testing. So not necessarily
   *   unique for each entry in the dataprovider since want to test a given
   *   relationship type against multiple xml strings. It's not a test
   *   identifier, it's an array key to use to look up something.
   * @param $xmlString string
   * @param $expected array
   * @param $dontcare array We're re-using the data provider for two tests and
   *   we don't care about those expected values.
   *
   * @dataProvider xmlCaseRoleDataProvider
   */
  public function testCaseRoles($key, $xmlString, $expected, $dontcare) {
    $xmlObj = new SimpleXMLElement($xmlString);

    // element 0 is direction (a_b), 1 is the text we want
    $expectedArray = empty($expected) ? [] : ["{$this->moreRelationshipTypes[$key]['type_id']}_{$expected[0]}" => $expected[1]];

    $this->assertEquals($expectedArray, $this->process->caseRoles($xmlObj->CaseRoles, FALSE));
  }

  /**
   * Test that locateNameOrLabel doesn't have name and label mixed up.
   *
   * @param $key string The array key in the moreRelationshipTypes array that
   *   is the relationship type we're currently testing. So not necessarily
   *   unique for each entry in the dataprovider since want to test a given
   *   relationship type against multiple xml strings. It's not a test
   *   identifier, it's an array key to use to look up something.
   * @param $xmlString string
   * @param $dontcare array We're re-using the data provider for two tests and
   *   we don't care about those expected values.
   * @param $expected array
   *
   * @dataProvider xmlCaseRoleDataProvider
   */
  public function testLocateNameOrLabel($key, $xmlString, $dontcare, $expected) {
    $xmlObj = new SimpleXMLElement($xmlString);

    // element 0 is direction (a_b), 1 is the text we want.
    // In case of failure, the function is expected to return FALSE for the
    // direction and then for the text it just gives us back the string we
    // gave it.
    $expectedArray = empty($expected[0])
        ? [FALSE, $expected[1]]
        : ["{$this->moreRelationshipTypes[$key]['type_id']}_{$expected[0]}", $expected[1]];

    $this->assertEquals($expectedArray, $this->process->locateNameOrLabel($xmlObj->CaseRoles->RelationshipType));
  }

  /**
   * Data provider for testCaseRoles and testLocateNameOrLabel
   * @return array
   */
  public function xmlCaseRoleDataProvider() {
    return [
      // Simulate one that has been converted to the format it should be going
      // forward, where name is the actual name, i.e. same as machineName.
      [
        // this is the array key in the $this->moreRelationshipTypes array
        'unidirectional_name_label_different',
        // some xml
        '<CaseType><CaseRoles><RelationshipType><name>jm7ba</name><creator>1</creator><manager>1</manager></RelationshipType></CaseRoles></CaseType>',
        // this is the expected for testCaseRoles
        ['a_b', 'Jedi Master is'],
        // this is the expected for testLocateNameOrLabel
        ['a_b', 'jm7ba'],
      ],
      // Simulate one that is still in label format, i.e. one that is still in
      // xml files that haven't been updated, or in the db but upgrade script
      // not run yet.
      [
        'unidirectional_name_label_different',
        '<CaseType><CaseRoles><RelationshipType><name>Jedi Master for</name><creator>1</creator><manager>1</manager></RelationshipType></CaseRoles></CaseType>',
        ['a_b', 'Jedi Master is'],
        ['a_b', 'jm7ba'],
      ],
      // Ditto but where we know name and label are the same in the db.
      [
        'unidirectional_name_label_same',
        '<CaseType><CaseRoles><RelationshipType><name>Quilt Maker for</name><creator>1</creator><manager>1</manager></RelationshipType></CaseRoles></CaseType>',
        ['a_b', 'Quilt Maker is'],
        ['a_b', 'Quilt Maker for'],
      ],
      // Simulate one that is messed up and should fail, e.g. like a typo
      // in an xml file. Here we've made a typo on purpose.
      [
        'unidirectional_name_label_different',
        '<CaseType><CaseRoles><RelationshipType><name>Jedi Masterrrr for</name><creator>1</creator><manager>1</manager></RelationshipType></CaseRoles></CaseType>',
        NULL,
        [FALSE, 'Jedi Masterrrr for'],
      ],
      // Now some similar tests to above but for bidirectional relationships.
      // Bidirectional relationship, name and label different, using machine name.
      [
        'bidirectional_name_label_different',
        '<CaseType><CaseRoles><RelationshipType><name>f12</name><creator>1</creator><manager>1</manager></RelationshipType></CaseRoles></CaseType>',
        ['b_a', 'Friend of'],
        ['b_a', 'f12'],
      ],
      // Bidirectional relationship, name and label different, using display label.
      [
        'bidirectional_name_label_different',
        '<CaseType><CaseRoles><RelationshipType><name>Friend of</name><creator>1</creator><manager>1</manager></RelationshipType></CaseRoles></CaseType>',
        ['b_a', 'Friend of'],
        ['b_a', 'f12'],
      ],
      // Bidirectional relationship, name and label same.
      [
        'bidirectional_name_label_same',
        '<CaseType><CaseRoles><RelationshipType><name>Enemy of</name><creator>1</creator><manager>1</manager></RelationshipType></CaseRoles></CaseType>',
        ['b_a', 'Enemy of'],
        ['b_a', 'Enemy of'],
      ],
    ];
  }

  /**
   * Test XMLProcessor activityTypes()
   */
  public function testXmlProcessorActivityTypes() {
    // First change an activity's label since we also test getting the labels.
    // @todo Having a brain freeze or something - can't do this in one step?
    $activity_type_id = $this->callApiSuccess('OptionValue', 'get', [
      'option_group_id' => 'activity_type',
      'name' => 'Medical evaluation',
    ])['id'];
    $this->callApiSuccess('OptionValue', 'create', [
      'id' => $activity_type_id,
      'label' => 'Medical evaluation changed',
    ]);

    $p = new CRM_Case_XMLProcessor_Process();
    $xml = $p->retrieve('housing_support');

    // Test getting the `name`s
    $activityTypes = $p->activityTypes($xml->ActivityTypes, FALSE, FALSE, FALSE);
    $this->assertEquals(
      [
        13 => 'Open Case',
        55 => 'Medical evaluation',
        56 => 'Mental health evaluation',
        57 => 'Secure temporary housing',
        60 => 'Income and benefits stabilization',
        58 => 'Long-term housing plan',
        14 => 'Follow up',
        15 => 'Change Case Type',
        16 => 'Change Case Status',
        18 => 'Change Case Start Date',
        25 => 'Link Cases',
      ],
      $activityTypes
    );

    // While we're here and have the `name`s check the editable types in
    // Settings.xml which is something that gets called reasonably often
    // thru CRM_Case_XMLProcessor_Process::activityTypes().
    $activityTypeValues = array_flip($activityTypes);
    $xml = $p->retrieve('Settings');
    $settings = $p->activityTypes($xml->ActivityTypes, FALSE, FALSE, 'edit');
    $this->assertEquals(
      [
        'edit' => [
          0 => $activityTypeValues['Change Case Status'],
          1 => $activityTypeValues['Change Case Start Date'],
        ],
      ],
      $settings
    );

    // Now get `label`s
    $xml = $p->retrieve('housing_support');
    $activityTypes = $p->activityTypes($xml->ActivityTypes, FALSE, TRUE, FALSE);
    $this->assertEquals(
      [
        13 => 'Open Case',
        55 => 'Medical evaluation changed',
        56 => 'Mental health evaluation',
        57 => 'Secure temporary housing',
        60 => 'Income and benefits stabilization',
        58 => 'Long-term housing plan',
        14 => 'Follow up',
        15 => 'Change Case Type',
        16 => 'Change Case Status',
        18 => 'Change Case Start Date',
        25 => 'Link Cases',
      ],
      $activityTypes
    );
  }

}
