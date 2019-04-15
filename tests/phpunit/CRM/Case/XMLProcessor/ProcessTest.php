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
    $this->setupActivityDefinitions();

    $this->process = new CRM_Case_XMLProcessor_Process();
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

}
