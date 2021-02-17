<?php

/**
 *  Test class for Advanced Searches
 *
 * @group headless
 */
class CRM_Contact_BAO_QueryAdvancedSearchTest extends CiviCaseTestCase {
  protected $_params;
  protected $_entity;
  protected $_apiversion = 3;
  protected $followup_activity_type_value;
  /**
   * Activity ID of created case.
   *
   * @var int
   */
  protected $_caseActivityId;

  /**
   * @var \Civi\Core\SettingsStack
   */
  protected $settingsStack;

  /**
   * Test setup for every test.
   *
   * Connect to the database, truncate the tables that will be used
   * and redirect stdin to a temporary file.
   */
  public function setUp() {
    $this->_entity = 'case';

    parent::setUp();

    $this->settingsStack = new \Civi\Core\SettingsStack();
  }

  public function tearDown() {
    $this->settingsStack->popAll();
    parent::tearDown();
  }

  /**
   *  Checks that CRM_Contact_BAO_Query::getCaseTypeAndActivityTypeJoin()
   *  adds the join needed when both CaseType and ActivityType are specified
   *  https://lab.civicrm.org/dev/report/-/issues/53
   *
   * @throws \CRM_Core_Exception
   */
  public function testCaseTypeAndActivityTypeSearch() {
    // Collect the ids from the test database that are needed in the search
    $contact = $this-> callAPIsuccess('contact', 'get', ['display_name' => 'Test Contact']);
    $contact_id = $contact['id'];
    $medical = $this->callAPISuccess('OptionValue', 'Get', ['name' => 'Medical evaluation']);
    $option_value_id = $medical['id'];
    $medical_activity_type_id = $medical['values'][$option_value_id]['value'];
    $housing_case_type = $this->callAPISuccess('case_type', 'get', ['name' => 'housing_support']);
    $housing_case_type_id = $housing_case_type['id'];

    // Two new cases of different CaseTypes for the same contact
    // By default both are created with a Medical evaluation activity scheduled
    $params = [
      'contact_id' => $contact_id,
      'case_type' => "housing_support",
      'subject' => "HousingSupportTest"
    ];
    $this->callAPISuccess('case', 'create', $params);

    $params = [
      'contact_id' => $contact_id,
      'case_type' => "adult_day_care_referral",
      'subject' => "AdultDayCareTest"
    ];
    $this->callAPISuccess('case', 'create', $params);

    // Parameters for Contact + ActivityType + ActivityStatus + CaseType
    $params = [
      [
        0 => 'contact_id',
        1 => '=',
        2 => $contact_id,
        3 => 0,
        4 => 0,
      ],
      [
        0 => 'activity_type_id',
        1 => '=',
        2 => $medical_activity_type_id,
        3 => 0,
        4 => 0,
      ],
      [
        0 => 'activity_status_id',
        1 => '=',
        2 => 1, //scheduled
        3 => 0,
        4 => 0,
      ],
      [
        0 => 'case_type_id',
        1 => '=',
        2 => $housing_case_type_id,
        3 => 0,
        4 => 0,
      ],
    ];
    // Query to find the activities that meet the search criteria
    $query = new CRM_Contact_BAO_Query($params, NULL, NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_ACTIVITY);

    $dao = $query->searchQuery();
    $activity_array = [];
    while ($dao->fetch()) {
      $activity_array[] = $dao->activity_id;
    }
    // There are two activities that meet the ActivityType criterion
    // but only one also meets the CaseType criterion
    $this->assertEquals(1, count($activity_array));
  }

}
