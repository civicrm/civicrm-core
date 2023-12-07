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
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 * @group headless
 */
class api_v3_CustomValueContactTypeTest extends CiviUnitTestCase {

  /**
   * @var int
   */
  private $individualFieldID;

  /**
   * @var int
   */
  private $individualStudentFieldID;

  /**
   * @var int
   */
  private $individualContactID;

  /**
   * @var int
   */
  private $individualStudentContactID;

  /**
   * @var int
   */
  private $individualParentContactID;

  /**
   * @var int
   */
  private $organizationContactID;

  /**
   * @var int
   */
  private $organizationSponsorContactID;

  public function setUp(): void {
    parent::setUp();
    //  Create Group For Individual  Contact Type
    $groupIndividual = [
      'title' => 'TestGroup For Individual' . substr(sha1(rand()), 0, 5),
      'extends' => ['Individual'],
      'style' => 'Inline',
      'is_active' => 1,
    ];

    $customGroupIndividual = $this->customGroupCreate($groupIndividual);

    $individualField = $this->customFieldCreate(['custom_group_id' => $customGroupIndividual['id']]);
    $this->individualFieldID = $individualField['id'];

    //  Create Group For Individual-Student  Contact Sub  Type
    $groupIndividualStudent = [
      'title' => 'Student Test' . substr(sha1(rand()), 0, 5),
      'extends' => ['Individual', ['Student']],
      'style' => 'Inline',
      'is_active' => 1,
    ];

    $customGroupIndividualStudent = $this->customGroupCreate($groupIndividualStudent);

    $individualStudentField = $this->customFieldCreate(['custom_group_id' => $customGroupIndividualStudent['id']]);
    $this->individualStudentFieldID = $individualStudentField['id'];

    $params = [
      'first_name' => 'Mathev',
      'last_name' => 'Adison',
      'contact_type' => 'Individual',
    ];

    $this->individualContactID = $this->individualCreate($params);

    $params = [
      'first_name' => 'Steve',
      'last_name' => 'Tosun',
      'contact_type' => 'Individual',
      'contact_sub_type' => 'Student',
    ];
    $this->individualStudentContactID = $this->individualCreate($params);

    $params = [
      'first_name' => 'Mark',
      'last_name' => 'Dawson',
      'contact_type' => 'Individual',
      'contact_sub_type' => 'Parent',
    ];
    $this->individualParentContactID = $this->individualCreate($params);

    $params = [
      'organization_name' => 'Wellspring',
      'contact_type' => 'Organization',
    ];
    $this->organizationContactID = $this->organizationCreate($params);

    $params = [
      'organization_name' => 'SubUrban',
      'contact_type' => 'Organization',
      'contact_sub_type' => 'Sponsor',
    ];
    $this->organizationSponsorContactID = $this->organizationCreate($params);

    //refresh php cached variables
    CRM_Core_PseudoConstant::flush();
    CRM_Core_BAO_CustomField::getTableColumnGroup($this->individualFieldID);
    CRM_Core_BAO_CustomField::getTableColumnGroup($this->individualStudentFieldID);
  }

  public function tearDown(): void {
    $tablesToTruncate = ['civicrm_contact', 'civicrm_cache'];
    $this->quickCleanup($tablesToTruncate, TRUE);
  }

  /**
   * Test that custom fields is returned for correct contact type only.
   */
  public function testGetFields(): void {
    $result = $this->callAPISuccess('Contact', 'getfields', []);
    $this->assertArrayHasKey("custom_{$this->individualFieldID}", $result['values'], 'If This fails there is probably a caching issue - failure in line' . __LINE__ . print_r(array_keys($result['values']), TRUE));
    $result = $this->callAPISuccess('Contact', 'getfields', [
      'action' => 'create',
      'contact_type' => 'Individual',
    ], 'in line' . __LINE__);
    $this->assertArrayHasKey("custom_{$this->individualFieldID}", $result['values']);
    $result = $this->callAPISuccess('Contact', 'getfields', [
      'action' => 'create',
      'contact_type' => 'Organization',
    ]);
    $this->assertArrayNotHasKey("custom_{$this->individualFieldID}", $result['values'], 'in line' . __LINE__ . print_r(array_keys($result['values']), TRUE));
    $result = $this->callAPISuccess('Relationship', 'getfields', ['action' => 'create'], 'in line' . __LINE__);
    $this->assertArrayNotHasKey("custom_{$this->individualFieldID}", $result['values']);
  }

  /**
   * Add  Custom data of Contact Type : Individual to a Contact type: Organization
   */
  public function testAddIndividualCustomDataToOrganization(): void {

    $params = [
      'id' => $this->organizationContactID,
      'contact_type' => 'Organization',
      "custom_{$this->individualFieldID}" => 'Test String',
      // so that undefined_fields is returned
      'debug' => 1,
    ];

    $contact = $this->callAPISuccess('contact', 'create', $params);
    $this->assertTrue(is_array($contact['undefined_fields']), __LINE__);
    $this->assertTrue(in_array("custom_{$this->individualFieldID}", $contact['undefined_fields']), __LINE__);
  }

  /**
   * Add valid  Empty params to a Contact Type : Individual
   * note - don't copy & paste this - is of marginal value
   */
  public function testAddCustomDataEmptyToIndividual(): void {
    $contact = $this->callAPIFailure('contact', 'create', [],
      'Mandatory key(s) missing from params array: contact_type'
    );
  }

  /**
   * Add valid custom data to a Contact Type : Individual
   */
  public function testAddValidCustomDataToIndividual(): void {

    $params = [
      'contact_id' => $this->individualContactID,
      'contact_type' => 'Individual',
      "custom_{$this->individualFieldID}" => 'Test String',
    ];
    $contact = $this->callAPISuccess('contact', 'create', $params);

    $this->assertNotNull($contact['id']);
    $entityValues = CRM_Core_BAO_CustomValueTable::getEntityValues($this->individualContactID);
    $elements["custom_{$this->individualFieldID}"] = $entityValues["{$this->individualFieldID}"];

    // Check the Value in Database
    $this->assertEquals($elements["custom_{$this->individualFieldID}"], 'Test String');
  }

  /**
   * Add  Custom data of Contact Type : Individual , SubType : Student to a Contact type: Organization  Subtype: Sponsor
   */
  public function testAddIndividualStudentCustomDataToOrganizationSponsor(): void {

    $params = [
      'contact_id' => $this->organizationSponsorContactID,
      'contact_type' => 'Organization',
      "custom_{$this->individualStudentFieldID}" => 'Test String',
      // so that undefined_fields is returned
      'debug' => 1,
    ];

    $contact = $this->callAPISuccess('contact', 'create', $params);
    $this->assertTrue(is_array($contact['undefined_fields']), __LINE__);
    $this->assertTrue(in_array("custom_{$this->individualStudentFieldID}", $contact['undefined_fields']), __LINE__);
  }

  /**
   * Add valid custom data to a Contact Type : Individual Subtype: Student
   */
  public function testCreateValidCustomDataToIndividualStudent(): void {

    $params = [
      'contact_id' => $this->individualStudentContactID,
      'contact_type' => 'Individual',
      "custom_{$this->individualStudentFieldID}" => 'Test String',
    ];

    $result = $this->callAPISuccess('contact', 'create', $params);

    $this->assertNotNull($result['id']);
    $entityValues = CRM_Core_BAO_CustomValueTable::getEntityValues($this->individualStudentContactID);
    $elements["custom_{$this->individualStudentFieldID}"] = $entityValues["{$this->individualStudentFieldID}"];

    // Check the Value in Database
    $this->assertEquals($elements["custom_{$this->individualStudentFieldID}"], 'Test String');
  }

  /**
   * Add custom data of Individual Student to a Contact Type : Individual - parent
   */
  public function testAddIndividualStudentCustomDataToIndividualParent(): void {

    $params = [
      'contact_id' => $this->individualParentContactID,
      'contact_type' => 'Individual',
      "custom_{$this->individualStudentFieldID}" => 'Test String',
      // so that undefined_fields is returned
      'debug' => 1,
    ];
    $contact = $this->callAPISuccess('contact', 'create', $params);
    $this->assertTrue(is_array($contact['undefined_fields']), __LINE__);
    $this->assertTrue(in_array("custom_{$this->individualStudentFieldID}", $contact['undefined_fields']), __LINE__);
  }

  // Retrieve Methods

  /**
   * Retrieve Valid custom Data added to  Individual Contact Type.
   */
  public function testRetrieveValidCustomDataToIndividual(): void {

    $params = [
      'contact_id' => $this->individualContactID,
      'contact_type' => 'Individual',
      "custom_" . $this->individualFieldID => 'Test String',
    ];

    $contact = $this->callAPISuccess('contact', 'create', $params);

    $this->assertAPISuccess($contact);
    $params = [
      'contact_id' => $this->individualContactID,
      'contact_type' => 'Individual',
      "return.custom_{$this->individualFieldID}" => 1,
    ];

    $getContact = $this->callAPISuccess('contact', 'get', $params);

    $this->assertEquals($getContact['values'][$this->individualContactID]["custom_" . $this->individualFieldID], 'Test String');
  }

  /**
   * Retrieve Valid custom Data added to  Individual Contact Type , Subtype : Student.
   */
  public function testRetrieveValidCustomDataToIndividualStudent(): void {

    $params = [
      'contact_id' => $this->individualStudentContactID,
      'contact_type' => 'Individual',
      'contact_sub_type' => 'Student',
      "custom_{$this->individualStudentFieldID}" => 'Test String',
    ];

    $contact = $this->callAPISuccess('contact', 'create', $params);
    $this->assertAPISuccess($contact);
    $params = [
      'contact_id' => $this->individualStudentContactID,
      'contact_type' => 'Individual',
      'contact_sub_type' => 'Student',
      "return.custom_{$this->individualStudentFieldID}" => 1,
    ];

    $getContact = $this->callAPISuccess('contact', 'get', $params);

    $this->assertEquals($getContact['values'][$this->individualStudentContactID]["custom_{$this->individualStudentFieldID}"], 'Test String');
  }

}
