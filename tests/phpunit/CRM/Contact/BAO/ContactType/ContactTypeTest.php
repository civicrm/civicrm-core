<?php

/**
 * Class CRM_Contact_BAO_ContactType_ContactTypeTest
 */
class CRM_Contact_BAO_ContactType_ContactTypeTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    $labelsub1 = 'sub1_individual' . substr(sha1(rand()), 0, 7);
    $params = array(
      'label' => $labelsub1,
      'name' => $labelsub1,
      // Individual
      'parent_id' => 1,
      'is_active' => 1,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->subTypesIndividual[] = $params['name'];

    $labelsub2 = 'sub2_individual' . substr(sha1(rand()), 0, 7);
    $params = array(
      'label' => $labelsub2,
      'name' => $labelsub2,
      // Individual
      'parent_id' => 1,
      'is_active' => 1,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->subTypesIndividual[] = $params['name'];

    $labelsub = 'sub_organization' . substr(sha1(rand()), 0, 7);
    $params = array(
      'label' => $labelsub,
      'name' => $labelsub,
      // Organization
      'parent_id' => 3,
      'is_active' => 1,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->subTypesOrganization[] = $params['name'];

    $labelhousehold = 'sub_household' . substr(sha1(rand()), 0, 7);
    $params = array(
      'label' => $labelhousehold,
      'name' => $labelhousehold,
      // Household
      'parent_id' => 2,
      'is_active' => 1,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->subTypesHousehold[] = $params['name'];
  }

  /**
   * Test contactTypes() and subTypes() methods with valid data
   * success expected
   */
  public function testGetMethods() {

    // check all contact types
    $contactTypes = array('Individual', 'Organization', 'Household');
    $result = CRM_Contact_BAO_ContactType::contactTypes('Individual');
    foreach ($contactTypes as $type) {
      $this->assertEquals(in_array($type, $result), TRUE);
    }

    // check for type:Individual
    $result = CRM_Contact_BAO_ContactType::subTypes('Individual');
    foreach ($result as $subtype) {
      $subTypeName = in_array($subtype, $this->subTypesIndividual);
      if (!empty($subTypeName)) {
        $this->assertEquals($subTypeName, TRUE);
      }
      $this->assertEquals(in_array($subtype, $this->subTypesOrganization), FALSE);
      $this->assertEquals(in_array($subtype, $this->subTypesHousehold), FALSE);
    }

    // check for type:Organization
    $result = CRM_Contact_BAO_ContactType::subTypes('Organization');
    foreach ($result as $subtype) {
      $this->assertEquals(in_array($subtype, $this->subTypesIndividual), FALSE);
      $subTypeName = in_array($subtype, $this->subTypesOrganization);
      if (!empty($subTypeName)) {
        $this->assertEquals($subTypeName, TRUE);
      }
      $subTypeName = in_array($subTypeName, $this->subTypesHousehold);
      if (empty($subTypeName)) {
        $this->assertEquals($subTypeName, FALSE);
      }
    }

    // check for type:Household
    $result = CRM_Contact_BAO_ContactType::subTypes('Household');
    foreach ($result as $subtype) {
      $this->assertEquals(in_array($subtype, $this->subTypesIndividual), FALSE);
      $this->assertEquals(in_array($subtype, $this->subTypesOrganization), FALSE);
      $this->assertEquals(in_array($subtype, $this->subTypesHousehold), TRUE);
    }

    // check for all conatct types
    $result = CRM_Contact_BAO_ContactType::subTypes();
    foreach ($this->subTypesIndividual as $subtype) {
      $this->assertEquals(in_array($subtype, $result), TRUE);
    }
    foreach ($this->subTypesOrganization as $subtype) {
      $this->assertEquals(in_array($subtype, $result), TRUE);
    }
    foreach ($this->subTypesHousehold as $subtype) {
      $this->assertEquals(in_array($subtype, $result), TRUE);
    }
  }

  /**
   * Test subTypes() methods with invalid data
   */
  public function testGetMethodsInvalid() {

    $params = 'invalid';
    $result = CRM_Contact_BAO_ContactType::subTypes($params);
    $this->assertEquals(empty($result), TRUE);

    $params = array('invalid');
    $result = CRM_Contact_BAO_ContactType::subTypes($params);
    $this->assertEquals(empty($result), TRUE);
  }

  /**
   * Test add() methods with valid data
   * success expected
   */
  public function testAdd() {

    $params = array(
      'label' => 'indiviSubType',
      'name' => 'indiviSubType',
      'parent_id' => 1,
      'is_active' => 1,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->assertEquals($result->label, $params['label']);
    $this->assertEquals($result->name, $params['name']);
    $this->assertEquals($result->parent_id, $params['parent_id']);
    $this->assertEquals($result->is_active, $params['is_active']);
    CRM_Contact_BAO_ContactType::del($result->id);

    $params = array(
      'label' => 'householdSubType',
      'name' => 'householdSubType',
      'parent_id' => 2,
      'is_active' => 0,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->assertEquals($result->label, $params['label']);
    $this->assertEquals($result->name, $params['name']);
    $this->assertEquals($result->parent_id, $params['parent_id']);
    $this->assertEquals($result->is_active, $params['is_active']);
    CRM_Contact_BAO_ContactType::del($result->id);
  }

  /**
   * Test add() with invalid data
   */
  public function testAddInvalid1() {

    // parent id does not exist in db
    $params = array(
      'label' => 'subType',
      'name' => 'subType',
      // non existent
      'parent_id' => 100,
      'is_active' => 1,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->assertEquals($result, NULL);
  }

  public function testAddInvalid2() {

    // params does not have name and label keys
    $params = array(
      'parent_id' => 1,
      'is_active' => 1,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->assertEquals($result, NULL);
  }

  public function testAddInvalid3() {

    // params does not have parent_id
    $params = array(
      'label' => 'subType',
      'name' => 'subType',
      'is_active' => 1,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->assertEquals($result, NULL, 'In line' . __LINE__);
  }

  /**
   * Test del() with valid data
   * success expected
   */
  public function testDel() {

    $params = array(
      'label' => 'indiviSubType',
      'name' => 'indiviSubType',
      'parent_id' => 1,
      'is_active' => 1,
    );
    $subtype = CRM_Contact_BAO_ContactType::add($params);

    $del = CRM_Contact_BAO_ContactType::del($subtype->id);
    $result = CRM_Contact_BAO_ContactType::subTypes();
    $this->assertEquals($del, TRUE);
    $this->assertEquals(in_array($subtype->name, $result), TRUE);
  }

  /**
   * Test del() with invalid data
   */
  public function testDelInvalid() {

    $del = CRM_Contact_BAO_ContactType::del(NULL);
    $this->assertEquals($del, FALSE);
  }

}
