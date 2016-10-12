<?php

/**
 * Class CRM_ACL_Test
 *
 * This test focuses on testing the (new) ID list-based functions:
 *   CRM_Contact_BAO_Contact_Permission::allowList()
 *   CRM_Contact_BAO_Contact_Permission::relationshipList()
 * @group headless
 */
class CRM_ACL_ListTest extends CiviUnitTestCase {

  /**
   * Set up function.
   */
  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  /**
   * general test for the 'view all contacts' permission
   */
  public function testViewAllPermission() {
    // create test contacts    
    $contacts = $this->createScenarioPlain();

    // test WITH all permissions
    CRM_Core_Config::singleton()->userPermissionClass->permissions = NULL;
    $result = CRM_Contact_BAO_Contact_Permission::allowList($contacts);
    sort($result);
    $this->assertEquals($result, $contacts, "Contacts should be viewable when 'view all contacts'");


    // test WITH explicit permission
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('view all contacts');
    $result = CRM_Contact_BAO_Contact_Permission::allowList($contacts, CRM_Core_Permission::VIEW);
    sort($result);
    $this->assertEquals($result, $contacts, "Contacts should be viewable when 'view all contacts'");


    // test WITHOUT permission
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array();
    $result = CRM_Contact_BAO_Contact_Permission::allowList($contacts);
    sort($result);
    $this->assertEmpty($result, "Contacts should NOT be viewable when 'view all contacts' is not set");
  }


  /**
   * general test for the 'view all contacts' permission
   */
  public function testEditAllPermission() {
    // create test contacts
    $contacts = $this->createScenarioPlain();

    // test WITH explicit permission
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('edit all contacts');
    $result = CRM_Contact_BAO_Contact_Permission::allowList($contacts, CRM_Core_Permission::EDIT);
    sort($result);
    $this->assertEquals($result, $contacts, "Contacts should be viewable when 'edit all contacts'");


    // test WITHOUT permission
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array();
    $result = CRM_Contact_BAO_Contact_Permission::allowList($contacts);
    sort($result);
    $this->assertEmpty($result, "Contacts should NOT be viewable when 'edit all contacts' is not set");
  }


  /**
   * Test access related to the 'access deleted contact' permission
   */
  public function testViewEditDeleted() {
    // create test contacts
    $contacts = $this->createScenarioPlain();

    // delete one contact
    $deleted_contact_id = $contacts[2];
    $this->callAPISuccess('Contact', 'create', array('id' => $deleted_contact_id, 'contact_is_deleted' => 1));
    $deleted_contact = $this->callAPISuccess('Contact', 'getsingle', array('id' => $deleted_contact_id));
    $this->assertEquals($deleted_contact['contact_is_deleted'], 1, "Contact should've been deleted");

    // test WITH explicit permission
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('edit all contacts', 'view all contacts');
    $result = CRM_Contact_BAO_Contact_Permission::allowList($contacts, CRM_Core_Permission::EDIT);
    sort($result);
    $this->assertNotContains($deleted_contact_id, $result, "Deleted contacts should be excluded");
    $this->assertEquals(count($result), count($contacts)-1, "Only deleted contacts should be excluded");

  }


  /**
   * Test access related to the 'access deleted contact' permission
   * 
   * There should be the following permission-relationship
   * contact[0] -> contact[1] -> contact[2]
   */
  public function testPermissionByRelation() {
    // create test scenario
    $contacts = $this->createScenarioRelation();

    // remove all permissions
    $config = CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = array();
    $permissions_to_check = array(CRM_Core_Permission::VIEW => 'View', CRM_Core_Permission::EDIT => 'Edit');

    // run this for SIMPLE relations
    $config->secondDegRelPermissions = FALSE;
    $this->assertFalse($config->secondDegRelPermissions);
    foreach ($permissions_to_check as $permission => $permission_label) {
      $result = CRM_Contact_BAO_Contact_Permission::allowList($contacts, $permission);
      sort($result);


      $this->assertNotContains($contacts[0], $result, "Contact[0] should NOT have $permission_label permission on contact[0].");
      $this->assertContains(   $contacts[1], $result, "Contact[0] should have $permission_label permission on contact[1].");
      $this->assertNotContains($contacts[2], $result, "Contact[0] should NOT have $permission_label permission on contact[2].");
      $this->assertNotContains($contacts[3], $result, "Contact[0] should NOT have $permission_label permission on contact[3].");
      $this->assertNotContains($contacts[4], $result, "Contact[0] should NOT have $permission_label permission on contact[4].");
    }
    
    // run this for SECOND DEGREE relations
    $config->secondDegRelPermissions = TRUE;
    $this->assertTrue($config->secondDegRelPermissions);
    foreach ($permissions_to_check as $permission => $permission_label) {
      $result = CRM_Contact_BAO_Contact_Permission::allowList($contacts, $permission);
      sort($result);

      $this->assertNotContains($contacts[0], $result, "Contact[0] should NOT have $permission_label permission on contact[0].");
      $this->assertContains(   $contacts[1], $result, "Contact[0] should have $permission_label permission on contact[1].");
      $this->assertContains(   $contacts[2], $result, "Contact[0] should have second degree $permission_label permission on contact[2].");
      $this->assertNotContains($contacts[3], $result, "Contact[0] should NOT have $permission_label permission on contact[3].");
      $this->assertNotContains($contacts[4], $result, "Contact[0] should NOT have $permission_label permission on contact[4].");
    }
  }


  /**
   * Test access related to the 'access deleted contact' permission
   */
  public function _testPermissionByACL() {
    // CRM_Core_Config::singleton()->userPermissionClass->permissions = array('edit all contacts', 'view all contacts');
    // $contacts = $this->createScenarioPlain();
  }

  /**
   * Test access related to the 'access deleted contact' permission
   */
  public function _testPermissionACLvsRelationship() {
    // CRM_Core_Config::singleton()->userPermissionClass->permissions = array('edit all contacts', 'view all contacts');
    // $contacts = $this->createScenarioPlain();
  }

  /**
   * Test access related to the 'access deleted contact' permission
   */
  public function _testPermissionCompare() {
    // CRM_Core_Config::singleton()->userPermissionClass->permissions = array('edit all contacts', 'view all contacts');
    // $contacts = $this->createScenarioPlain();
  }


  /****************************************************
   *             Scenario Builders                    *
   ***************************************************/

  /**
   * create plain test scenario, no relationships/ACLs
   */
  protected function createScenarioPlain() {
    // get logged in user
    $user_id = $this->createLoggedInUser();
    $this->assertNotEmpty($user_id);

    // create test contacts
    $bush_sr_id    = $this->individualCreate(array('first_name' => 'George', 'middle_name' => 'W.', 'last_name' => 'Bush'));
    $bush_jr_id    = $this->individualCreate(array('first_name' => 'George', 'middle_name' => 'H. W.', 'last_name' => 'Bush'));
    $bush_laura_id = $this->individualCreate(array('first_name' => 'Laura Lane', 'last_name' => 'Bush'));
    $bush_brbra_id = $this->individualCreate(array('first_name' => 'Barbara', 'last_name' => 'Bush'));

    $contacts = array($user_id, $bush_sr_id, $bush_jr_id, $bush_laura_id, $bush_brbra_id);
    sort($contacts);
    return $contacts;
  }

  /**
   * create plain test scenario, no relationships/ACLs
   */
  protected function createScenarioRelation() {
    $contacts = $this->createScenarioPlain();

    // create some relationships
    $this->callAPISuccess('Relationship', 'create', array(
      'relationship_type_id' => 1,  // CHILD OF
      'contact_id_a'         => $contacts[1],
      'contact_id_b'         => $contacts[0],
      'is_permission_b_a'    => 1,
      'is_active'            => 1,
      ));

    $this->callAPISuccess('Relationship', 'create', array(
      'relationship_type_id' => 1,  // CHILD OF
      'contact_id_a'         => $contacts[2],
      'contact_id_b'         => $contacts[1],
      'is_permission_b_a'    => 1,
      'is_active'            => 1,
      ));

    // create some relationships
    $this->callAPISuccess('Relationship', 'create', array(
      'relationship_type_id' => 1,  // CHILD OF
      'contact_id_a'         => $contacts[4],
      'contact_id_b'         => $contacts[2],
      'is_permission_b_a'    => 1,
      'is_active'            => 1,
      ));

    return $contacts;
  }
}
