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
class api_v3_ContactTypeTest extends CiviUnitTestCase {
  protected $_apiversion;
  public $_eNoticeCompliant = TRUE;
  function setUp() {
    parent::setUp();
    $this->_apiversion = 3;
    $params = array(
      'label' => 'sub_individual',
      'name' => 'sub_individual',
      // Individual
      'parent_id' => 1,
      'is_active' => 1,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->subTypeIndividual = $params['name'];
    $this->_subTypeIndividualId = $result->id;

    $params = array(
      'label' => 'sub_organization',
      'name' => 'sub_organization',
      // Organization
      'parent_id' => 3,
      'is_active' => 1,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->subTypeOrganization = $params['name'];
    $this->_subTypeOrganizationId = $result->id;

    $params = array(
      'label' => 'sub_household',
      'name' => 'sub_household',
      // Household
      'parent_id' => 2,
      'is_active' => 1,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->subTypeHousehold = $params['name'];
    $this->_subTypeHouseholdId = $result->id;
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   */
  function tearDown() {
    $contactTypeIds = array(
      $this->_subTypeIndividualId,
      $this->_subTypeOrganizationId,
      $this->_subTypeHouseholdId,
    );
    foreach ($contactTypeIds as $typeId) {
      $this->contactTypeDelete($typeId);
    }
  }

  /*
     * test add methods with valid data
     * success expected
     */
  function testContactCreate() {

    // check for Type:Individual Subtype:sub_individual
    $contactParams = array(
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->subTypeIndividual,
      'version' => $this->_apiversion,
    );
    $contact = civicrm_api('contact', 'create', $contactParams);
    $this->assertEquals($contact['is_error'], 0, "In line " . __LINE__);
    $params = array(
      'contact_id' => $contact['id'],
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('contact', 'get', $params);
    $this->assertEquals($result['values'][$contact['id']]['first_name'], $contactParams['first_name'], "In line " . __LINE__);
    $this->assertEquals($result['values'][$contact['id']]['last_name'], $contactParams['last_name'], "In line " . __LINE__);
    $this->assertEquals($result['values'][$contact['id']]['contact_type'], $contactParams['contact_type'], "In line " . __LINE__);
    $this->assertEquals(end($result['values'][$contact['id']]['contact_sub_type']), $contactParams['contact_sub_type'], "In line " . __LINE__);
    civicrm_api('contact', 'delete', $params);

    // check for Type:Organization Subtype:sub_organization
    $contactParams = array(
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
      'contact_sub_type' => $this->subTypeOrganization,
      'version' => $this->_apiversion,
    );
    $contact = civicrm_api('contact', 'create', $contactParams);
    $this->assertEquals($contact['is_error'], 0, "In line " . __LINE__);

    $params = array(
      'contact_id' => $contact['id'],
      'version' => $this->_apiversion,
    );
    $getContacts = civicrm_api('contact', 'get', $params);
    $result = $getContacts['values'][$contact['id']];
    $this->assertEquals($result['organization_name'], $contactParams['organization_name'], "In line " . __LINE__);
    $this->assertEquals($result['contact_type'], $contactParams['contact_type'], "In line " . __LINE__);
    $this->assertEquals(end($result['contact_sub_type']), $contactParams['contact_sub_type'], "In line " . __LINE__);
    civicrm_api('contact', 'delete', $params);
  }


  /*
     * test add with invalid data
     */
  function testContactAddInvalidData() {

    // check for Type:Individual Subtype:sub_household
    $contactParams = array(
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->subTypeHousehold,
      'version' => $this->_apiversion,
    );
    $contact = $this->callAPIFailure('contact', 'create', $contactParams);

    // check for Type:Organization Subtype:sub_individual
    $contactParams = array(
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
      'contact_sub_type' => $this->subTypeIndividual,
      'version' => $this->_apiversion,
    );
    $contact = $this->callAPIFailure('contact', 'create', $contactParams);
  }


  /*
     * test update with no subtype to valid subtype
     * success expected
     */
  function testContactUpdateNoSubtypeValid() {

    // check for Type:Individual
    $contactParams = array(
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
      'version' => $this->_apiversion,
    );
    $contact = civicrm_api('contact', 'create', $contactParams);
    // subype:sub_individual
    $updateParams = array(
      'first_name' => 'John',
      'last_name' => 'Grant',
      'contact_id' => $contact['id'],
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->subTypeIndividual,
      'version' => $this->_apiversion,
    );
    $updateContact = civicrm_api('contact', 'create', $updateParams);
    $this->assertEquals($updateContact['is_error'], 0, "In line " . __LINE__);
    $this->assertEquals($updateContact['id'], $contact['id'], "In line " . __LINE__);

    $params = array(
      'contact_id' => $contact['id'],
      'version' => $this->_apiversion,
    );
    $getContacts = civicrm_api('contact', 'get', $params);
    $result = $getContacts['values'][$contact['id']];

    $this->assertEquals($result['first_name'], $updateParams['first_name'], "In line " . __LINE__);
    $this->assertEquals($result['last_name'], $updateParams['last_name'], "In line " . __LINE__);
    $this->assertEquals($result['contact_type'], $updateParams['contact_type'], "In line " . __LINE__);
    $this->assertEquals(end($result['contact_sub_type']), $updateParams['contact_sub_type'], "In line " . __LINE__);
    civicrm_api('contact', 'delete', $params);

    // check for Type:Organization
    $contactParams = array(
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
      'version' => $this->_apiversion,
    );
    $contact = civicrm_api('contact', 'create', $contactParams);

    // subype:sub_organization
    $updateParams = array(
      'organization_name' => 'Intel Arts',
      'contact_id' => $contact['id'],
      'contact_type' => 'Organization',
      'contact_sub_type' => $this->subTypeOrganization,
      'version' => $this->_apiversion,
    );
    $updateContact = civicrm_api('contact', 'create', $updateParams);

    $this->assertEquals($updateContact['is_error'], 0, "In line " . __LINE__);
    $this->assertEquals($updateContact['id'], $contact['id'], "In line " . __LINE__);

    $params = array(
      'contact_id' => $contact['id'],
      'version' => $this->_apiversion,
    );
    $getContacts = civicrm_api('contact', 'get', $params);
    $result = $getContacts['values'][$contact['id']];

    $this->assertEquals($result['organization_name'], $updateParams['organization_name'], "In line " . __LINE__);
    $this->assertEquals($result['contact_type'], $updateParams['contact_type'], "In line " . __LINE__);
    $this->assertEquals(end($result['contact_sub_type']), $updateParams['contact_sub_type'], "In line " . __LINE__);
    civicrm_api('contact', 'delete', $params);
  }


  /*
     * test update with no subtype to invalid subtype
     */
  function testContactUpdateNoSubtypeInvalid() {

    // check for Type:Individual
    $contactParams = array(
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
      'version' => $this->_apiversion,
    );
    $contact = civicrm_api('contact', 'create', $contactParams);

    // subype:sub_household
    $updateParams = array(
      'first_name' => 'John',
      'last_name' => 'Grant',
      'contact_id' => $contact['id'],
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->subTypeHousehold,
      'version' => $this->_apiversion,
    );
    $updateContact = $this->callAPIFailure('contact', 'create', $updateParams);
    $params = array(
      'contact_id' => $contact['id'],
      'version' => $this->_apiversion,
    );
    civicrm_api('contact', 'delete', $params);

    // check for Type:Organization
    $contactParams = array(
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
      'version' => $this->_apiversion,
    );
    $contact = civicrm_api('contact', 'create', $contactParams);

    $updateParams = array(
      'organization_name' => 'Intel Arts',
      'contact_id' => $contact['id'],
      'contact_type' => 'Organization',
      'contact_sub_type' => $this->subTypeIndividual,
      'version' => $this->_apiversion,
    );
    $updateContact = $this->callAPIFailure('contact', 'create', $updateParams);
    $params = array(
      'contact_id' => $contact['id'],
      'version' => $this->_apiversion,
    );
    civicrm_api('contact', 'delete', $params);
  }

  /*
     * test update with no subtype to valid subtype
     * success expected
     */
  function testContactUpdateSubtypeValid() {

    $params = array(
      'label' => 'sub2_individual',
      'name' => 'sub2_individual',
      // Individual
      'parent_id' => 1,
      'is_active' => 1,
    );
    $getSubtype = CRM_Contact_BAO_ContactType::add($params);
    $subtype = $params['name'];

    // check for Type:Individual subype:sub_individual
    $contactParams = array(
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->subTypeIndividual,
      'version' => $this->_apiversion,
    );
    $contact = civicrm_api('contact', 'create', $contactParams);
    // subype:sub2_individual
    $updateParams = array(
      'id' => $contact['id'],
      'first_name' => 'John',
      'last_name' => 'Grant',
      'contact_id' => $contact['id'],
      'contact_type' => 'Individual',
      'contact_sub_type' => $subtype,
      'version' => $this->_apiversion,
    );
    $updateContact = civicrm_api('contact', 'create', $updateParams);

    $this->assertEquals($updateContact['is_error'], 0, "In line " . __LINE__);
    $this->assertEquals($updateContact['id'], $contact['id'], "In line " . __LINE__);

    $params = array(
      'contact_id' => $contact['id'],
      'version' => $this->_apiversion,
    );
    $getContacts = civicrm_api('contact', 'get', $params);
    $result = $getContacts['values'][$contact['id']];

    $this->assertEquals($result['first_name'], $updateParams['first_name'], "In line " . __LINE__);
    $this->assertEquals($result['last_name'], $updateParams['last_name'], "In line " . __LINE__);
    $this->assertEquals($result['contact_type'], $updateParams['contact_type'], "In line " . __LINE__);
    $this->assertEquals(end($result['contact_sub_type']), $updateParams['contact_sub_type'], "In line " . __LINE__);
    civicrm_api('contact', 'delete', $params);


    $params = array(
      'label' => 'sub2_organization',
      'name' => 'sub2_organization',
      // Organization
      'parent_id' => 3,
      'is_active' => 1,
    );
    $getSubtype = CRM_Contact_BAO_ContactType::add($params);
    $subtype = $params['name'];

    // check for Type:Organization subype:sub_organization
    $contactParams = array(
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
      'contact_sub_type' => $this->subTypeOrganization,
      'version' => $this->_apiversion,
    );
    $contact = civicrm_api('contact', 'create', $contactParams);

    // subype:sub2_organization
    $updateParams = array(
      'organization_name' => 'Intel Arts',
      'contact_id' => $contact['id'],
      'contact_type' => 'Organization',
      'contact_sub_type' => $subtype,
      'version' => $this->_apiversion,
    );
    $updateContact = civicrm_api('contact', 'create', $updateParams);

    $this->assertEquals($updateContact['is_error'], 0, "In line " . __LINE__);
    $this->assertEquals($updateContact['id'], $contact['id'], "In line " . __LINE__);

    $params = array(
      'contact_id' => $contact['id'],
      'version' => $this->_apiversion,
    );
    $getContacts = civicrm_api('contact', 'get', $params);
    $result = $getContacts['values'][$contact['id']];

    $this->assertEquals($result['organization_name'], $updateParams['organization_name'], "In line " . __LINE__);
    $this->assertEquals($result['contact_type'], $updateParams['contact_type'], "In line " . __LINE__);
    $this->assertEquals(end($result['contact_sub_type']), $updateParams['contact_sub_type'], "In line " . __LINE__);
    civicrm_api('contact', 'delete', $params);
  }

  /*
     * test update with no subtype to invalid subtype
     */
  function testContactUpdateSubtypeInvalid() {

    // check for Type:Individual subtype:sub_individual
    $contactParams = array(
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->subTypeIndividual,
      'version' => $this->_apiversion,
    );
    $contact = civicrm_api('contact', 'create', $contactParams);

    // subype:sub_household
    $updateParams = array(
      'first_name' => 'John',
      'last_name' => 'Grant',
      'contact_id' => $contact['id'],
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->subTypeHousehold,
      'version' => $this->_apiversion,
    );
    $updateContact = $this->callAPIFailure('contact', 'create', $updateParams);
    $params = array(
      'contact_id' => $contact['id'],
      'version' => $this->_apiversion,
    );
    civicrm_api('contact', 'delete', $params);

    // check for Type:Organization subtype:
    $contactParams = array(
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
      'contact_sub_type' => $this->subTypeOrganization,
      'version' => $this->_apiversion,
    );
    $contact = civicrm_api('contact', 'create', $contactParams);

    $updateParams = array(
      'organization_name' => 'Intel Arts',
      'contact_id' => $contact['id'],
      'contact_sub_type' => $this->subTypeIndividual,
      'version' => $this->_apiversion,
    );
    $updateContact = $this->callAPIFailure('contact', 'create', $updateParams);
    $params = array(
      'contact_id' => $contact['id'],
      'version' => $this->_apiversion,
    );
    civicrm_api('contact', 'delete', $params);
  }
}

