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
 * Class api_v3_ContactTypeTest
 * @group headless
 */
class api_v3_ContactTypeTest extends CiviUnitTestCase {
  protected $_apiversion;

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
    $params = [
      'label' => 'sub_individual',
      'name' => 'sub_individual',
      // Individual
      'parent_id' => 1,
      'is_active' => 1,
    ];
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->subTypeIndividual = $params['name'];

    $params = [
      'label' => 'sub_organization',
      'name' => 'sub_organization',
      // Organization
      'parent_id' => 3,
      'is_active' => 1,
    ];
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->subTypeOrganization = $params['name'];

    $params = [
      'label' => 'sub_household',
      'name' => 'sub_household',
      // Household
      'parent_id' => 2,
      'is_active' => 1,
    ];
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->subTypeHousehold = $params['name'];
  }

  /**
   * Test add methods with valid data.
   * success expected
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testContactCreate($version) {
    $this->_apiversion = $version;

    // check for Type:Individual Subtype:sub_individual
    $contactParams = [
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->subTypeIndividual,
    ];
    $contact = $this->callAPISuccess('contact', 'create', $contactParams);
    $params = [
      'contact_id' => $contact['id'],
    ];
    $result = $this->callAPISuccess('contact', 'get', $params);
    $this->assertEquals($result['values'][$contact['id']]['first_name'], $contactParams['first_name'], "In line " . __LINE__);
    $this->assertEquals($result['values'][$contact['id']]['last_name'], $contactParams['last_name'], "In line " . __LINE__);
    $this->assertEquals($result['values'][$contact['id']]['contact_type'], $contactParams['contact_type'], "In line " . __LINE__);
    $this->assertEquals(end($result['values'][$contact['id']]['contact_sub_type']), $contactParams['contact_sub_type'], "In line " . __LINE__);
    $this->callAPISuccess('contact', 'delete', $params);

    // check for Type:Organization Subtype:sub_organization
    $contactParams = [
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
      'contact_sub_type' => $this->subTypeOrganization,
    ];
    $contact = $this->callAPISuccess('contact', 'create', $contactParams);

    $params = [
      'contact_id' => $contact['id'],
    ];
    $getContacts = $this->callAPISuccess('contact', 'get', $params);
    $result = $getContacts['values'][$contact['id']];
    $this->assertEquals($result['organization_name'], $contactParams['organization_name'], "In line " . __LINE__);
    $this->assertEquals($result['contact_type'], $contactParams['contact_type'], "In line " . __LINE__);
    $this->assertEquals(end($result['contact_sub_type']), $contactParams['contact_sub_type'], "In line " . __LINE__);
    $this->callAPISuccess('contact', 'delete', $params);
  }

  /**
   * Test add with invalid data.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testContactAddInvalidData($version) {
    $this->_apiversion = $version;

    // check for Type:Individual Subtype:sub_household
    $contactParams = [
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->subTypeHousehold,
    ];
    $contact = $this->callAPIFailure('contact', 'create', $contactParams);

    // check for Type:Organization Subtype:sub_individual
    $contactParams = [
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
      'contact_sub_type' => $this->subTypeIndividual,
    ];
    $contact = $this->callAPIFailure('contact', 'create', $contactParams);
  }

  /**
   * Test update with no subtype to valid subtype.
   * success expected
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testContactUpdateNoSubtypeValid($version) {
    $this->_apiversion = $version;

    // check for Type:Individual
    $contactParams = [
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
    ];
    $contact = $this->callAPISuccess('contact', 'create', $contactParams);
    // subype:sub_individual
    $updateParams = [
      'first_name' => 'John',
      'last_name' => 'Grant',
      'contact_id' => $contact['id'],
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->subTypeIndividual,
    ];
    $updateContact = $this->callAPISuccess('contact', 'create', $updateParams);
    $this->assertEquals($updateContact['id'], $contact['id'], "In line " . __LINE__);

    $params = [
      'contact_id' => $contact['id'],
    ];
    $getContacts = $this->callAPISuccess('contact', 'get', $params);
    $result = $getContacts['values'][$contact['id']];

    $this->assertEquals($result['first_name'], $updateParams['first_name'], "In line " . __LINE__);
    $this->assertEquals($result['last_name'], $updateParams['last_name'], "In line " . __LINE__);
    $this->assertEquals($result['contact_type'], $updateParams['contact_type'], "In line " . __LINE__);
    $this->assertEquals(end($result['contact_sub_type']), $updateParams['contact_sub_type'], "In line " . __LINE__);
    $this->callAPISuccess('contact', 'delete', $params);

    // check for Type:Organization
    $contactParams = [
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
    ];
    $contact = $this->callAPISuccess('contact', 'create', $contactParams);

    // subype:sub_organization
    $updateParams = [
      'organization_name' => 'Intel Arts',
      'contact_id' => $contact['id'],
      'contact_type' => 'Organization',
      'contact_sub_type' => $this->subTypeOrganization,
    ];
    $updateContact = $this->callAPISuccess('contact', 'create', $updateParams);
    $this->assertEquals($updateContact['id'], $contact['id'], "In line " . __LINE__);

    $params = [
      'contact_id' => $contact['id'],
    ];
    $getContacts = $this->callAPISuccess('contact', 'get', $params);
    $result = $getContacts['values'][$contact['id']];

    $this->assertEquals($result['organization_name'], $updateParams['organization_name'], "In line " . __LINE__);
    $this->assertEquals($result['contact_type'], $updateParams['contact_type'], "In line " . __LINE__);
    $this->assertEquals(end($result['contact_sub_type']), $updateParams['contact_sub_type'], "In line " . __LINE__);
    $this->callAPISuccess('contact', 'delete', $params);
  }

  /**
   * Test update with no subtype to invalid subtype.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testContactUpdateNoSubtypeInvalid($version) {
    $this->_apiversion = $version;

    // check for Type:Individual
    $contactParams = [
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
    ];
    $contact = $this->callAPISuccess('contact', 'create', $contactParams);

    // subype:sub_household
    $updateParams = [
      'first_name' => 'John',
      'last_name' => 'Grant',
      'contact_id' => $contact['id'],
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->subTypeHousehold,
    ];
    $updateContact = $this->callAPIFailure('contact', 'create', $updateParams);
    $params = [
      'contact_id' => $contact['id'],
    ];
    $this->callAPISuccess('contact', 'delete', $params);

    // check for Type:Organization
    $contactParams = [
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
    ];
    $contact = $this->callAPISuccess('contact', 'create', $contactParams);

    $updateParams = [
      'organization_name' => 'Intel Arts',
      'contact_id' => $contact['id'],
      'contact_type' => 'Organization',
      'contact_sub_type' => $this->subTypeIndividual,
    ];
    $updateContact = $this->callAPIFailure('contact', 'create', $updateParams);
    $params = [
      'contact_id' => $contact['id'],
    ];
    $this->callAPISuccess('contact', 'delete', $params);
  }

  /**
   * Test update with no subtype to valid subtype.
   * success expected
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testContactUpdateSubtypeValid($version) {
    $this->_apiversion = $version;

    $params = [
      'label' => 'sub2_individual',
      'name' => 'sub2_individual',
      // Individual
      'parent_id' => 1,
      'is_active' => 1,
    ];
    $getSubtype = CRM_Contact_BAO_ContactType::add($params);
    $subtype = $params['name'];

    // check for Type:Individual subype:sub_individual
    $contactParams = [
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->subTypeIndividual,
    ];
    $contact = $this->callAPISuccess('contact', 'create', $contactParams);
    // subype:sub2_individual
    $updateParams = [
      'id' => $contact['id'],
      'first_name' => 'John',
      'last_name' => 'Grant',
      'contact_id' => $contact['id'],
      'contact_type' => 'Individual',
      'contact_sub_type' => $subtype,
    ];
    $updateContact = $this->callAPISuccess('contact', 'create', $updateParams);

    $this->assertEquals($updateContact['id'], $contact['id'], "In line " . __LINE__);

    $params = [
      'contact_id' => $contact['id'],
    ];
    $getContacts = $this->callAPISuccess('contact', 'get', $params);
    $result = $getContacts['values'][$contact['id']];

    $this->assertEquals($result['first_name'], $updateParams['first_name'], "In line " . __LINE__);
    $this->assertEquals($result['last_name'], $updateParams['last_name'], "In line " . __LINE__);
    $this->assertEquals($result['contact_type'], $updateParams['contact_type'], "In line " . __LINE__);
    $this->assertEquals(end($result['contact_sub_type']), $updateParams['contact_sub_type'], "In line " . __LINE__);
    $this->callAPISuccess('contact', 'delete', $params);

    $params = [
      'label' => 'sub2_organization',
      'name' => 'sub2_organization',
      // Organization
      'parent_id' => 3,
      'is_active' => 1,
    ];
    $getSubtype = CRM_Contact_BAO_ContactType::add($params);
    $subtype = $params['name'];

    // check for Type:Organization subype:sub_organization
    $contactParams = [
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
      'contact_sub_type' => $this->subTypeOrganization,
    ];
    $contact = $this->callAPISuccess('contact', 'create', $contactParams);

    // subype:sub2_organization
    $updateParams = [
      'organization_name' => 'Intel Arts',
      'contact_id' => $contact['id'],
      'contact_type' => 'Organization',
      'contact_sub_type' => $subtype,
    ];
    $updateContact = $this->callAPISuccess('contact', 'create', $updateParams);
    $this->assertEquals($updateContact['id'], $contact['id'], "In line " . __LINE__);

    $params = [
      'contact_id' => $contact['id'],
    ];
    $getContacts = $this->callAPISuccess('contact', 'get', $params);
    $result = $getContacts['values'][$contact['id']];

    $this->assertEquals($result['organization_name'], $updateParams['organization_name'], "In line " . __LINE__);
    $this->assertEquals($result['contact_type'], $updateParams['contact_type'], "In line " . __LINE__);
    $this->assertEquals(end($result['contact_sub_type']), $updateParams['contact_sub_type'], "In line " . __LINE__);
    $this->callAPISuccess('contact', 'delete', $params);
  }

  /**
   * Test update with no subtype to invalid subtype.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testContactUpdateSubtypeInvalid($version) {
    $this->_apiversion = $version;

    // check for Type:Individual subtype:sub_individual
    $contactParams = [
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->subTypeIndividual,
    ];
    $contact = $this->callAPISuccess('contact', 'create', $contactParams);

    // subype:sub_household
    $updateParams = [
      'first_name' => 'John',
      'last_name' => 'Grant',
      'contact_id' => $contact['id'],
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->subTypeHousehold,
    ];
    $updateContact = $this->callAPIFailure('contact', 'create', $updateParams);
    $params = [
      'contact_id' => $contact['id'],
    ];
    $this->callAPISuccess('contact', 'delete', $params);

    // check for Type:Organization subtype:
    $contactParams = [
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
      'contact_sub_type' => $this->subTypeOrganization,
    ];
    $contact = $this->callAPISuccess('contact', 'create', $contactParams);

    $updateParams = [
      'organization_name' => 'Intel Arts',
      'contact_id' => $contact['id'],
      'contact_sub_type' => $this->subTypeIndividual,
    ];
    $updateContact = $this->callAPIFailure('contact', 'create', $updateParams);
    $params = [
      'contact_id' => $contact['id'],
    ];
    $this->callAPISuccess('contact', 'delete', $params);
  }

}
