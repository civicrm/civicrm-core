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
 *  Test APIv3 civicrm_activity_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 * @group headless
 */
class api_v3_CustomValueContactTypeTest extends CiviUnitTestCase {
  protected $_contactID;
  protected $_apiversion = 3;
  protected $CustomGroupIndividual;
  protected $individualStudent;

  public function setUp() {
    parent::setUp();
    //  Create Group For Individual  Contact Type
    $groupIndividual = [
      'title' => 'TestGroup For Indivi' . substr(sha1(rand()), 0, 5),
      'extends' => ['Individual'],
      'style' => 'Inline',
      'is_active' => 1,
    ];

    $this->CustomGroupIndividual = $this->customGroupCreate($groupIndividual);

    $this->IndividualField = $this->customFieldCreate(['custom_group_id' => $this->CustomGroupIndividual['id']]);

    //  Create Group For Individual-Student  Contact Sub  Type
    $groupIndiStudent = [
      'title' => 'Student Test' . substr(sha1(rand()), 0, 5),
      'extends' => ['Individual', ['Student']],
      'style' => 'Inline',
      'is_active' => 1,
    ];

    $this->CustomGroupIndiStudent = $this->customGroupCreate($groupIndiStudent);

    $this->IndiStudentField = $this->customFieldCreate(['custom_group_id' => $this->CustomGroupIndiStudent['id']]);

    $params = [
      'first_name' => 'Mathev',
      'last_name' => 'Adison',
      'contact_type' => 'Individual',
    ];

    $this->individual = $this->individualCreate($params);

    $params = [
      'first_name' => 'Steve',
      'last_name' => 'Tosun',
      'contact_type' => 'Individual',
      'contact_sub_type' => 'Student',
    ];
    $this->individualStudent = $this->individualCreate($params);

    $params = [
      'first_name' => 'Mark',
      'last_name' => 'Dawson',
      'contact_type' => 'Individual',
      'contact_sub_type' => 'Parent',
    ];
    $this->individualParent = $this->individualCreate($params);

    $params = [
      'organization_name' => 'Wellspring',
      'contact_type' => 'Organization',
    ];
    $this->organization = $this->organizationCreate($params);

    $params = [
      'organization_name' => 'SubUrban',
      'contact_type' => 'Organization',
      'contact_sub_type' => 'Sponsor',
    ];
    $this->organizationSponsor = $this->organizationCreate($params);
    //refresh php cached variables
    CRM_Core_PseudoConstant::flush();
    CRM_Core_BAO_CustomField::getTableColumnGroup($this->IndividualField['id'], TRUE);
    CRM_Core_BAO_CustomField::getTableColumnGroup($this->IndiStudentField['id'], TRUE);
  }

  public function tearDown() {
    $tablesToTruncate = ['civicrm_contact', 'civicrm_cache'];
    $this->quickCleanup($tablesToTruncate, TRUE);
  }

  /**
   * Test that custom fields is returned for correct contact type only.
   */
  public function testGetFields() {
    $result = $this->callAPISuccess('Contact', 'getfields', []);
    $this->assertArrayHasKey("custom_{$this->IndividualField['id']}", $result['values'], 'If This fails there is probably a caching issue - failure in line' . __LINE__ . print_r(array_keys($result['values']), TRUE));
    $result = $this->callAPISuccess('Contact', 'getfields', [
      'action' => 'create',
      'contact_type' => 'Individual',
    ], 'in line' . __LINE__);
    $this->assertArrayHasKey("custom_{$this->IndividualField['id']}", $result['values']);
    $result = $this->callAPISuccess('Contact', 'getfields', [
      'action' => 'create',
      'contact_type' => 'Organization',
    ]);
    $this->assertArrayNotHasKey("custom_{$this->IndividualField['id']}", $result['values'], 'in line' . __LINE__ . print_r(array_keys($result['values']), TRUE));
    $result = $this->callAPISuccess('Relationship', 'getfields', ['action' => 'create'], 'in line' . __LINE__);
    $this->assertArrayNotHasKey("custom_{$this->IndividualField['id']}", $result['values']);
  }

  /**
   * Add  Custom data of Contact Type : Individual to a Contact type: Organization
   */
  public function testAddIndividualCustomDataToOrganization() {

    $params = [
      'id' => $this->organization,
      'contact_type' => 'Organization',
      "custom_{$this->IndividualField['id']}" => 'Test String',
      // so that undefined_fields is returned
      'debug' => 1,
    ];

    $contact = $this->callAPISuccess('contact', 'create', $params);
    $this->assertTrue(is_array($contact['undefined_fields']), __LINE__);
    $this->assertTrue(in_array("custom_{$this->IndividualField['id']}", $contact['undefined_fields']), __LINE__);
  }

  /**
   * Add valid  Empty params to a Contact Type : Individual
   * note - don't copy & paste this - is of marginal value
   */
  public function testAddCustomDataEmptyToIndividual() {
    $contact = $this->callAPIFailure('contact', 'create', [],
      'Mandatory key(s) missing from params array: contact_type'
    );
  }

  /**
   * Add valid custom data to a Contact Type : Individual
   */
  public function testAddValidCustomDataToIndividual() {

    $params = [
      'contact_id' => $this->individual,
      'contact_type' => 'Individual',
      "custom_{$this->IndividualField['id']}" => 'Test String',
    ];
    $contact = $this->callAPISuccess('contact', 'create', $params);

    $this->assertNotNull($contact['id']);
    $entityValues = CRM_Core_BAO_CustomValueTable::getEntityValues($this->individual);
    $elements["custom_{$this->IndividualField['id']}"] = $entityValues["{$this->IndividualField['id']}"];

    // Check the Value in Database
    $this->assertEquals($elements["custom_{$this->IndividualField['id']}"], 'Test String');
  }

  /**
   * Add  Custom data of Contact Type : Individual , SubType : Student to a Contact type: Organization  Subtype: Sponsor
   */
  public function testAddIndividualStudentCustomDataToOrganizationSponsor() {

    $params = [
      'contact_id' => $this->organizationSponsor,
      'contact_type' => 'Organization',
      "custom_{$this->IndiStudentField['id']}" => 'Test String',
      // so that undefined_fields is returned
      'debug' => 1,
    ];

    $contact = $this->callAPISuccess('contact', 'create', $params);
    $this->assertTrue(is_array($contact['undefined_fields']), __LINE__);
    $this->assertTrue(in_array("custom_{$this->IndiStudentField['id']}", $contact['undefined_fields']), __LINE__);
  }

  /**
   * Add valid custom data to a Contact Type : Individual Subtype: Student
   */
  public function testCreateValidCustomDataToIndividualStudent() {

    $params = [
      'contact_id' => $this->individualStudent,
      'contact_type' => 'Individual',
      "custom_{$this->IndiStudentField['id']}" => 'Test String',
    ];

    $result = $this->callAPISuccess('contact', 'create', $params);

    $this->assertNotNull($result['id']);
    $entityValues = CRM_Core_BAO_CustomValueTable::getEntityValues($this->individualStudent);
    $elements["custom_{$this->IndiStudentField['id']}"] = $entityValues["{$this->IndiStudentField['id']}"];

    // Check the Value in Database
    $this->assertEquals($elements["custom_{$this->IndiStudentField['id']}"], 'Test String');
  }

  /**
   * Add custom data of Individual Student to a Contact Type : Individual - parent
   */
  public function testAddIndividualStudentCustomDataToIndividualParent() {

    $params = [
      'contact_id' => $this->individualParent,
      'contact_type' => 'Individual',
      "custom_{$this->IndiStudentField['id']}" => 'Test String',
      // so that undefined_fields is returned
      'debug' => 1,
    ];
    $contact = $this->callAPISuccess('contact', 'create', $params);
    $this->assertTrue(is_array($contact['undefined_fields']), __LINE__);
    $this->assertTrue(in_array("custom_{$this->IndiStudentField['id']}", $contact['undefined_fields']), __LINE__);
  }

  // Retrieve Methods

  /**
   * Retrieve Valid custom Data added to  Individual Contact Type.
   */
  public function testRetrieveValidCustomDataToIndividual() {

    $params = [
      'contact_id' => $this->individual,
      'contact_type' => 'Individual',
      "custom_" . $this->IndividualField['id'] => 'Test String',
    ];

    $contact = $this->callAPISuccess('contact', 'create', $params);

    $this->assertAPISuccess($contact);
    $params = [
      'contact_id' => $this->individual,
      'contact_type' => 'Individual',
      "return.custom_{$this->IndividualField['id']}" => 1,
    ];

    $getContact = $this->callAPISuccess('contact', 'get', $params);

    $this->assertEquals($getContact['values'][$this->individual]["custom_" . $this->IndividualField['id']], 'Test String');
  }

  /**
   * Retrieve Valid custom Data added to  Individual Contact Type , Subtype : Student.
   */
  public function testRetrieveValidCustomDataToIndividualStudent() {

    $params = [
      'contact_id' => $this->individualStudent,
      'contact_type' => 'Individual',
      'contact_sub_type' => 'Student',
      "custom_{$this->IndiStudentField['id']}" => 'Test String',
    ];

    $contact = $this->callAPISuccess('contact', 'create', $params);
    $this->assertAPISuccess($contact);
    $params = [
      'contact_id' => $this->individualStudent,
      'contact_type' => 'Individual',
      'contact_sub_type' => 'Student',
      "return.custom_{$this->IndiStudentField['id']}" => 1,
    ];

    $getContact = $this->callAPISuccess('contact', 'get', $params);

    $this->assertEquals($getContact['values'][$this->individualStudent]["custom_{$this->IndiStudentField['id']}"], 'Test String');
  }

}
