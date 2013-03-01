<?php
require_once 'CiviTest/CiviUnitTestCase.php';
class CRM_Contact_BAO_ContactType_ContactTest extends CiviUnitTestCase {
  function get_info() {
    return array(
      'name' => 'Contact Subtype',
      'description' => 'Test Contact for subtype.',
      'group' => 'CiviCRM BAO Tests',
    );
  }

  function setUp() {
    parent::setUp();

    $params = array(
      'label' => 'indiv_student',
      'name' => 'indiv_student',
      // Individual
      'parent_id' => 1,
      'is_active' => 1,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->student = $params['name'];

    $params = array(
      'label' => 'indiv_parent',
      'name' => 'indiv_parent',
      // Individual
      'parent_id' => 1,
      'is_active' => 1,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->parent = $params['name'];


    $params = array(
      'label' => 'org_sponsor',
      'name' => 'org_sponsor',
      // Organization
      'parent_id' => 3,
      'is_active' => 1,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->sponsor = $params['name'];

    $params = array(
      'label' => 'org_team',
      'name' => 'org_team',
      // Organization
      'parent_id' => 3,
      'is_active' => 1,
    );
    $result = CRM_Contact_BAO_ContactType::add($params);
    $this->team = $params['name'];
  }

  function tearDown() {
    $this->quickCleanup(array('civicrm_contact'));
    $query = "
DELETE FROM civicrm_contact_type
      WHERE name IN ('{$this->student}','{$this->parent}','{$this->sponsor}', '{$this->team}');";
    CRM_Core_DAO::executeQuery($query);
  }

  /**
   * methods create Contact with valid data
   * success expected
   *
   */
  function testCreateContact() {
    //check for Type:Individual
    $params = array(
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
    );
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch(Exception$expected) {}
    $this->assertEquals($contact->first_name, 'Anne', 'In line ' . __LINE__);
    $this->assertEquals($contact->contact_type, 'Individual', 'In line ' . __LINE__);
    CRM_Contact_BAO_Contact::deleteContact($contact->id);

    //check for Type:Organization
    $params = array(
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
    );
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch(Exception$expected) {}
    $this->assertEquals($contact->organization_name, 'Compumentor', 'In line ' . __LINE__);
    $this->assertEquals($contact->contact_type, 'Organization', 'In line ' . __LINE__);
    CRM_Contact_BAO_Contact::deleteContact($contact->id);

    //check for Type:Household
    $params = array(
      'household_name' => 'John Does home',
      'contact_type' => 'Household',
    );
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch(Exception$expected) {}
    $this->assertEquals($contact->household_name, 'John Does home', 'In line ' . __LINE__);
    $this->assertEquals($contact->contact_type, 'Household', 'In line ' . __LINE__);
    CRM_Contact_BAO_Contact::deleteContact($contact->id);

    //check for Type:Individual, Subtype:Student
    $params = array(
      'first_name' => 'Bill',
      'last_name' => 'Adams',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->student,
    );
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch(Exception$expected) {}
    $this->assertEquals($contact->first_name, 'Bill', 'In line ' . __LINE__);
    $this->assertEquals($contact->contact_type, 'Individual', 'In line ' . __LINE__);
    $this->assertEquals(str_replace(CRM_Core_DAO::VALUE_SEPARATOR, '', $contact->contact_sub_type), $this->student, 'In line ' . __LINE__);
    CRM_Contact_BAO_Contact::deleteContact($contact->id);

    //check for Type:Organization, Subtype:Sponsor
    $params = array(
      'organization_name' => 'Conservation Corp',
      'contact_type' => 'Organization',
      'contact_sub_type' => $this->sponsor,
    );
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch(Exception$expected) {}
    $this->assertEquals($contact->organization_name, 'Conservation Corp', 'In line ' . __LINE__);
    $this->assertEquals($contact->contact_type, 'Organization', 'In line ' . __LINE__);
    $this->assertEquals(str_replace(CRM_Core_DAO::VALUE_SEPARATOR, '', $contact->contact_sub_type), $this->sponsor, 'In line ' . __LINE__);
    CRM_Contact_BAO_Contact::deleteContact($contact->id);
  }

  /**
   * update the contact with no subtype to a valid subtype
   * success expected
   */
  function testUpdateContactNosubtypeToValid() {
    $params = array(
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
    );
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch(Exception$expected) {}
    $updateParams = array(
      'contact_sub_type' => $this->student,
      'contact_type' => 'Individual',
      'contact_id' => $contact->id,
    );
    try {
      $updatedContact = CRM_Contact_BAO_Contact::add($updateParams);
    }
    catch(Exception$expected) {}
    $this->assertEquals($updatedContact->id, $contact->id, 'In line ' . __LINE__);
    $this->assertEquals($updatedContact->contact_type, 'Individual', 'In line ' . __LINE__);
    $this->assertEquals(str_replace(CRM_Core_DAO::VALUE_SEPARATOR, '', $updatedContact->contact_sub_type), $this->student, 'In line ' . __LINE__);
    CRM_Contact_BAO_Contact::deleteContact($contact->id);

    $params = array(
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
    );
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch(Exception$expected) {}

    $updateParams = array(
      'contact_sub_type' => $this->sponsor,
      'contact_type' => 'Organization',
      'contact_id' => $contact->id,
    );
    try {
      $updatedContact = CRM_Contact_BAO_Contact::add($updateParams);
    }
    catch(Exception$expected) {}
    $this->assertEquals($updatedContact->id, $contact->id, 'In line ' . __LINE__);
    $this->assertEquals($updatedContact->contact_type, 'Organization', 'In line ' . __LINE__);
    $this->assertEquals(str_replace(CRM_Core_DAO::VALUE_SEPARATOR, '', $updatedContact->contact_sub_type), $this->sponsor, 'In line ' . __LINE__);
    CRM_Contact_BAO_Contact::deleteContact($contact->id);
  }

  /**
   * update the contact with subtype to another valid subtype
   * success expected
   */
  function testUpdateContactSubtype() {
    $params = array(
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->student,
    );
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch(Exception$expected) {}

    $updateParams = array(
      'contact_sub_type' => $this->parent,
      'contact_type' => 'Individual',
      'contact_id' => $contact->id,
    );
    try {
      $updatedContact = CRM_Contact_BAO_Contact::add($updateParams);
    }
    catch(Exception$expected) {}
    $this->assertEquals($updatedContact->id, $contact->id, 'In line ' . __LINE__);
    $this->assertEquals($updatedContact->contact_type, 'Individual', 'In line ' . __LINE__);
    $this->assertEquals(str_replace(CRM_Core_DAO::VALUE_SEPARATOR, '', $updatedContact->contact_sub_type), $this->parent, 'In line ' . __LINE__);
    CRM_Contact_BAO_Contact::deleteContact($contact->id);


    $params = array(
      'organization_name' => 'Compumentor',
      'contact_type' => 'Organization',
      'contact_sub_type' => $this->sponsor,
    );
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch(Exception$expected) {}

    $updateParams = array(
      'contact_sub_type' => $this->team,
      'contact_type' => 'Organization',
      'contact_id' => $contact->id,
    );
    try {
      $updatedContact = CRM_Contact_BAO_Contact::add($updateParams);
    }
    catch(Exception$expected) {}

    $this->assertEquals($updatedContact->id, $contact->id, 'In line ' . __LINE__);
    $this->assertEquals($updatedContact->contact_type, 'Organization', 'In line ' . __LINE__);
    $this->assertEquals(str_replace(CRM_Core_DAO::VALUE_SEPARATOR, '', $updatedContact->contact_sub_type), $this->team, 'In line ' . __LINE__);
    CRM_Contact_BAO_Contact::deleteContact($contact->id);


    $params = array(
      'first_name' => 'Anne',
      'last_name' => 'Grant',
      'contact_type' => 'Individual',
      'contact_sub_type' => $this->student,
    );
    try {
      $contact = CRM_Contact_BAO_Contact::add($params);
    }
    catch(Exception$expected) {}

    $updateParams = array(
      'contact_sub_type' => NULL,
      'contact_type' => 'Individual',
      'contact_id' => $contact->id,
    );
    try {
      $updatedContact = CRM_Contact_BAO_Contact::add($updateParams);
    }
    catch(Exception$expected) {}

    $this->assertEquals($updatedContact->id, $contact->id, 'In line ' . __LINE__);
    $this->assertEquals($updatedContact->contact_type, 'Individual', 'In line ' . __LINE__);
    $this->assertEquals($updatedContact->contact_sub_type, 'null', 'In line ' . __LINE__);
    CRM_Contact_BAO_Contact::deleteContact($contact->id);
  }
}



