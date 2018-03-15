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
    $this->assigneeContactId = $this->individualCreate();
    $this->targetContactId = $this->individualCreate();

    $this->setUpDefaultAssigneeOptions();
    $this->setUpRelationship();

    $activityTypeXml = '<activity-type><name>Open Case</name></activity-type>';
    $this->activityTypeXml = new SimpleXMLElement($activityTypeXml);
    $this->params = [
      'activity_date_time' => date('Ymd'),
      'caseID' => $this->caseTypeId,
      'clientID' => $this->targetContactId,
      'creatorID' => $this->_loggedInUser,
    ];

    $this->process = new CRM_Case_XMLProcessor_Process();
  }

  /**
   * Adds the default assignee group and options to the test database.
   * It also stores the IDs of the options in an index.
   */
  protected function setUpDefaultAssigneeOptions() {
    $options = [
      'NONE', 'BY_RELATIONSHIP', 'SPECIFIC_CONTACT', 'USER_CREATING_THE_CASE'
    ];

    CRM_Core_BAO_OptionGroup::ensureOptionGroupExists([
      'name' => 'activity_default_assignee'
    ]);

    foreach ($options as $option) {
      $optionValue = CRM_Core_BAO_OptionValue::ensureOptionValueExists([
        'option_group_id' => 'activity_default_assignee',
        'name' => $option,
        'label' => $option
      ]);

      $this->defaultAssigneeOptionsValues[$option] = $optionValue['value'];
    }
  }

  /**
   * Adds a relationship between the activity's target contact and default assignee.
   */
  protected function setUpRelationship() {
    $this->assignedRelationshipType = 'Instructor of';
    $this->unassignedRelationshipType = 'Employer of';

    $assignedRelationshipTypeId = $this->relationshipTypeCreate([
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
      'name_a_b' => 'Pupil of',
      'name_b_a' => $this->assignedRelationshipType,
    ]);
    $this->relationshipTypeCreate([
      'name_a_b' => 'Employee of',
      'name_b_a' => $this->unassignedRelationshipType,
    ]);
    $this->callAPISuccess('Relationship', 'create', [
      'contact_id_a' => $this->assigneeContactId,
      'contact_id_b' => $this->targetContactId,
      'relationship_type_id' => $assignedRelationshipTypeId
    ]);
  }

  /**
   * Tests the creation of activities with default assignee by relationship.
   */
  public function testCreateActivityWithDefaultContactByRelationship() {
    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['BY_RELATIONSHIP'];
    $this->activityTypeXml->default_assignee_relationship = $this->assignedRelationshipType;

    $this->process->createActivity($this->activityTypeXml, $this->params);
    $this->assertActivityAssignedToContactExists($this->assigneeContactId);
  }

  /**
   * Tests the creation of activities with default assignee by relationship,
   * but the target contact doesn't have any relationship of the selected type.
   */
  public function testCreateActivityWithDefaultContactByRelationButTheresNoRelationship() {
    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['BY_RELATIONSHIP'];
    $this->activityTypeXml->default_assignee_relationship = $this->unassignedRelationshipType;

    $this->process->createActivity($this->activityTypeXml, $this->params);
    $this->assertActivityAssignedToContactExists(NULL);
  }

  /**
   * Tests the creation of activities with default assignee set to a specific contact.
   */
  public function testCreateActivityAssignedToSpecificContact() {
    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['SPECIFIC_CONTACT'];
    $this->activityTypeXml->default_assignee_contact = $this->assigneeContactId;

    $this->process->createActivity($this->activityTypeXml, $this->params);
    $this->assertActivityAssignedToContactExists($this->assigneeContactId);
  }

  /**
   * Tests the creation of activities with default assignee set to a specific contact,
   * but the contact does not exist.
   */
  public function testCreateActivityAssignedToNonExistantSpecificContact() {
    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['SPECIFIC_CONTACT'];
    $this->activityTypeXml->default_assignee_contact = 987456321;

    $this->process->createActivity($this->activityTypeXml, $this->params);
    $this->assertActivityAssignedToContactExists(NULL);
  }

  /**
   * Tests the creation of activities with the default assignee being the one
   * creating the case's activity.
   */
  public function testCreateActivityAssignedToUserCreatingTheCase() {
    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['USER_CREATING_THE_CASE'];

    $this->process->createActivity($this->activityTypeXml, $this->params);
    $this->assertActivityAssignedToContactExists($this->_loggedInUser);
  }

  /**
   * Tests the creation of activities when the default assignee is set to NONE.
   */
  public function testCreateActivityAssignedNoUser() {
    $this->activityTypeXml->default_assignee_type = $this->defaultAssigneeOptionsValues['NONE'];

    $this->process->createActivity($this->activityTypeXml, $this->params);
    $this->assertActivityAssignedToContactExists(NULL);
  }

  /**
   * Tests the creation of activities when the default assignee is set to NONE.
   */
  public function testCreateActivityWithNoDefaultAssigneeOption() {
    $this->process->createActivity($this->activityTypeXml, $this->params);
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
      'target_contact_id' => $this->targetContactId,
      'return' => ['assignee_contact_id']
    ]);
    $activity = CRM_Utils_Array::first($result['values']);

    $this->assertNotNull($activity, 'Target contact has no activities assigned to them');
    $this->assertEquals($expectedContact, $activity['assignee_contact_id'], 'Activity is not assigned to expected contact');
  }

}
