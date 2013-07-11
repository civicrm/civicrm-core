<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 *  Test APIv3 civicrm_activity_* functions
 *
 *  @package CiviCRM_APIv3
 *  @subpackage API_Contact
 */

class api_v3_CustomValueContactTypeTest extends CiviUnitTestCase {
  protected $_contactID;
  protected $_apiversion;
  protected $CustomGroupIndividual;
  protected $individualStudent;
  public $_eNoticeCompliant = TRUE;

  function get_info() {
    return array(
      'name' => 'Custom Data For Contact Subtype',
      'description' => 'Test Custom Data for Contact subtype.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {

    parent::setUp();
    $this->_apiversion = 3;
    //  Create Group For Individual  Contact Type
    $groupIndividual = array('title' => 'TestGroup For Indivi' . substr(sha1(rand()), 0, 5),
      'extends' => array('Individual'),
      'style' => 'Inline',
      'is_active' => 1,
      'version' => $this->_apiversion,
    );

    $this->CustomGroupIndividual = $this->customGroupCreate($groupIndividual);

    $this->IndividualField = $this->customFieldCreate($this->CustomGroupIndividual['id'], "Custom Field" . substr(sha1(rand()), 0, 7));

    //  Create Group For Individual-Student  Contact Sub  Type
    $groupIndiStudent = array(
      'title' => 'Student Test' . substr(sha1(rand()), 0, 5),
      'extends' => array('Individual', array('Student')),
      'style' => 'Inline',
      'is_active' => 1,
      'version' => $this->_apiversion,
    );

    $this->CustomGroupIndiStudent = $this->customGroupCreate($groupIndiStudent);

    $this->IndiStudentField = $this->customFieldCreate($this->CustomGroupIndiStudent['id'], "Custom Field" . substr(sha1(rand()), 0, 7));

    $params = array(
      'first_name' => 'Mathev',
      'last_name' => 'Adison',
      'contact_type' => 'Individual',
      'version' => $this->_apiversion,
    );

    $this->individual = $this->individualCreate($params);

    $params = array(
      'first_name' => 'Steve',
      'last_name' => 'Tosun',
      'contact_type' => 'Individual',
      'contact_sub_type' => 'Student',
      'version' => $this->_apiversion,
    );
    $this->individualStudent = $this->individualCreate($params);

    $params = array(
      'first_name' => 'Mark',
      'last_name' => 'Dawson',
      'contact_type' => 'Individual',
      'contact_sub_type' => 'Parent',
      'version' => $this->_apiversion,
    );
    $this->individualParent = $this->individualCreate($params);

    $params = array(
      'organization_name' => 'Wellspring',
      'contact_type' => 'Organization',
      'version' => $this->_apiversion,
    );
    $this->organization = $this->organizationCreate($params);

    $params = array(
      'organization_name' => 'SubUrban',
      'contact_type' => 'Organization',
      'contact_sub_type' => 'Sponsor',
      'version' => $this->_apiversion,
    );
    $this->organizationSponsor = $this->organizationCreate($params);
    //refresh php cached variables
    CRM_Core_PseudoConstant::flush();
    CRM_Core_BAO_CustomField::getTableColumnGroup($this->IndividualField['id'], True);
    CRM_Core_BAO_CustomField::getTableColumnGroup($this->IndiStudentField['id'], True);
  }

  function tearDown() {
    $tablesToTruncate = array('civicrm_contact', 'civicrm_cache');
    $this->quickCleanup($tablesToTruncate, TRUE);
  }
  /*
   * Test that custom fields is returned for correct contact type only
   */
  function testGetFields() {
    $result = civicrm_api('Contact', 'getfields', array('version' => 3));
    $this->assertAPISuccess($result);
    $this->assertArrayHasKey("custom_{$this->IndividualField['id']}", $result['values'], 'If This fails there is probably a cachine issue - failure in line' . __LINE__ . print_r(array_keys($result['values']), TRUE));
    $result = civicrm_api('Contact', 'getfields', array('version' => 3, 'action' => 'create', 'contact_type' => 'Individual'), 'in line' . __LINE__);
    $this->assertArrayHasKey("custom_{$this->IndividualField['id']}", $result['values']);
    $result = civicrm_api('Contact', 'getfields', array('version' => 3, 'action' => 'create', 'contact_type' => 'Organization'));
    $this->assertArrayNotHasKey("custom_{$this->IndividualField['id']}", $result['values'], 'in line' . __LINE__ . print_r(array_keys($result['values']), TRUE));
    $result = civicrm_api('Relationship', 'getfields', array('version' => 3, 'action' => 'create'), 'in line' . __LINE__);
    $this->assertArrayNotHasKey("custom_{$this->IndividualField['id']}", $result['values']);
  }

  /**
   * Add  Custom data of Contact Type : Individual to a Contact type: Organization
   */
  function testAddIndividualCustomDataToOrganization() {

    $params = array(
      'id' => $this->organization,
      'contact_type' => 'Organization',
      "custom_{$this->IndividualField['id']}" => 'Test String',
      'version' => $this->_apiversion,
      'debug' => 1,// so that undefined_fields is returned
    );

    $contact = civicrm_api('contact', 'create', $params);
    $this->assertTrue(is_array($contact['undefined_fields']), __LINE__);
    $this->assertTrue(in_array("custom_{$this->IndividualField['id']}", $contact['undefined_fields']), __LINE__);
  }

  /**
   * Add valid  Empty params to a Contact Type : Individual
   */
  function testAddCustomDataEmptyToIndividual() {

    $params = array(
      'version' => 3,
    );
    $contact = $this->callAPIFailure('contact', 'create', $params);
    $this->assertEquals($contact['error_message'], 'Mandatory key(s) missing from params array: contact_type');
  }

  /**
   * Add valid custom data to a Contact Type : Individual
   */
  function testAddValidCustomDataToIndividual() {

    $params = array(
      'contact_id' => $this->individual,
      'contact_type' => 'Individual',
      "custom_{$this->IndividualField['id']}" => 'Test String',
      'version' => $this->_apiversion,
    );
    $contact = civicrm_api('contact', 'create', $params);

    $this->assertNotNull($contact['id'], 'In line ' . __LINE__);
    $entityValues = CRM_Core_BAO_CustomValueTable::getEntityValues($this->individual);
    $elements["custom_{$this->IndividualField['id']}"] = $entityValues["{$this->IndividualField['id']}"];

    // Check the Value in Database
    $this->assertEquals($elements["custom_{$this->IndividualField['id']}"], 'Test String', 'In line ' . __LINE__);
  }

  /**
   * Add  Custom data of Contact Type : Individual , SubType : Student to a Contact type: Organization  Subtype: Sponsor
   */
  function testAddIndividualStudentCustomDataToOrganizationSponsor() {

    $params = array(
      'contact_id' => $this->organizationSponsor,
      'contact_type' => 'Organization',
      "custom_{$this->IndiStudentField['id']}" => 'Test String',
      'version' => $this->_apiversion,
      'debug' => 1,// so that undefined_fields is returned
    );

    $contact = civicrm_api('contact', 'create', $params);
    $this->assertTrue(is_array($contact['undefined_fields']), __LINE__);
    $this->assertTrue(in_array("custom_{$this->IndiStudentField['id']}", $contact['undefined_fields']), __LINE__);
  }

  /**
   * Add valid custom data to a Contact Type : Individual Subtype: Student
   */
  function testCreateValidCustomDataToIndividualStudent() {

    $params = array(
      'contact_id' => $this->individualStudent,
      'contact_type' => 'Individual',
      "custom_{$this->IndiStudentField['id']}" => 'Test String',
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('contact', 'create', $params);

    $this->assertNotNull($result['id'], 'In line ' . __LINE__);
    $entityValues = CRM_Core_BAO_CustomValueTable::getEntityValues($this->individualStudent);
    $elements["custom_{$this->IndiStudentField['id']}"] = $entityValues["{$this->IndiStudentField['id']}"];

    // Check the Value in Database
    $this->assertEquals($elements["custom_{$this->IndiStudentField['id']}"], 'Test String', 'in line ' . __LINE__);
  }

  /**
   * Add custom data of Individual Student to a Contact Type : Individual - parent
   */
  function testAddIndividualStudentCustomDataToIndividualParent() {

    $params = array(
      'contact_id' => $this->individualParent,
      'contact_type' => 'Individual',
      "custom_{$this->IndiStudentField['id']}" => 'Test String',
      'version' => $this->_apiversion,
      'debug' => 1,// so that undefined_fields is returned
    );
    $contact = civicrm_api('contact', 'create', $params);
    $this->assertTrue(is_array($contact['undefined_fields']), __LINE__);
    $this->assertTrue(in_array("custom_{$this->IndiStudentField['id']}", $contact['undefined_fields']), __LINE__);
  }



  // Retrieve Methods

  /**
   * Retrieve Valid custom Data added to  Individual Contact Type
   */
  function testRetrieveValidCustomDataToIndividual() {

    $params = array(
      'contact_id' => $this->individual,
      'contact_type' => 'Individual',
      "custom_" . $this->IndividualField['id'] => 'Test String',
      'version' => $this->_apiversion,
    );

    $contact = civicrm_api('contact', 'create', $params);

    $this->assertAPISuccess($contact);
    $params = array(
      'contact_id' => $this->individual,
      'contact_type' => 'Individual',
      "return.custom_{$this->IndividualField['id']}" => 1,
      'version' => $this->_apiversion,
    );

    $getContact = civicrm_api('contact', 'get', $params);

    $this->assertEquals($getContact['values'][$this->individual]["custom_" . $this->IndividualField['id']], 'Test String', 'In line ' . __LINE__);
  }

  /**
   * Retrieve Valid custom Data added to  Individual Contact Type , Subtype : Student.
   */
  function testRetrieveValidCustomDataToIndividualStudent() {

    $params = array(
      'contact_id' => $this->individualStudent,
      'contact_type' => 'Individual',
      'contact_sub_type' => 'Student',
      "custom_{$this->IndiStudentField['id']}" => 'Test String',
      'version' => $this->_apiversion,
    );

    $contact = civicrm_api('contact', 'create', $params);
    $this->assertAPISuccess($contact);
    $params = array(
      'contact_id' => $this->individualStudent,
      'contact_type' => 'Individual',
      'contact_sub_type' => 'Student',
      'version' => $this->_apiversion,
      "return.custom_{$this->IndiStudentField['id']}" => 1,
    );

    $getContact = civicrm_api('contact', 'get', $params);


    $this->assertEquals($getContact['values'][$this->individualStudent]["custom_{$this->IndiStudentField['id']}"], 'Test String', 'In line ' . __LINE__);
  }
}

