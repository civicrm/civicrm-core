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
    $contacts = $this->createScenarioA();
    // CRM_Core_Error::debug_log_message(json_encode($contacts));

    // test WITH permission
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('view all contacts');
    $result = CRM_Contact_BAO_Contact_Permission::allowList($contacts);
    // CRM_Core_Error::debug_log_message(json_encode($result));
    $this->assertEqual($result, $contacts, "Contacts should be viewable when 'view all contacts'");


    // test WITH explicit permission
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('view all contacts');
    $result = CRM_Contact_BAO_Contact_Permission::allowList($contacts, CRM_Core_Permission::VIEW);
    // CRM_Core_Error::debug_log_message(json_encode($result));
    $this->assertEqual($result, $contacts, "Contacts should be viewable when 'view all contacts'");


    // test WITHOUT permission
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array();
    $result = CRM_Contact_BAO_Contact_Permission::allowList($contacts);
    $this->assertEmpty($result, "Contacts should NOT be viewable when 'view all contacts' is not set");
  }


  /**
   * general test for the 'view all contacts' permission
   */
  public function testEditAllPermission() {
    // create test contacts

    $contacts = $this->createScenarioA();

    // test WITH explicit permission
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('edit all contacts');
    $result = CRM_Contact_BAO_Contact_Permission::allowList($contacts, CRM_Core_Permission::EDIT);
    $this->assertEqual($result, $contacts, "Contacts should be viewable when 'edit all contacts'");


    // test WITHOUT permission
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array();
    $result = CRM_Contact_BAO_Contact_Permission::allowList($contacts);
    $this->assertEmpty($result, "Contacts should NOT be viewable when 'edit all contacts' is not set");
  }


  /**
   * general test for the 'view all contacts' permission
   */
  public function testViewEditDeleted() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('edit all contacts', 'view all contacts');
    $contacts = $this->createScenarioA();

    
  }







  /**
   * create test scenario A
   */
  protected function createScenarioA() {
    // get logged in user
    $user_id = $this->createLoggedInUser();
    $this->assertNotEmpty($user_id);

    // create test contacts
    $bush_sr_id    = $this->individualCreate(array('first_name' => 'George', 'middle_name' => 'W.', 'last_name' => 'Bush'));
    $bush_jr_id    = $this->individualCreate(array('first_name' => 'George', 'middle_name' => 'H. W.', 'last_name' => 'Bush'));
    $bush_laura_id = $this->individualCreate(array('first_name' => 'Laura Lane', 'last_name' => 'Bush'));
    $bush_brbra_id = $this->individualCreate(array('first_name' => 'Barbara', 'last_name' => 'Bush'));

    // create some relationships
    $this->callAPISuccess('Relationship', 'create', array(
      'relationship_type_id' => 1,  // CHILD OF
      'contact_id_a'         => $bush_sr_id,
      'contact_id_b'         => $user_id,
      'is_permission_a_b'    => 1,
      ));

    $this->callAPISuccess('Relationship', 'create', array(
      'relationship_type_id' => 1,  // CHILD OF
      'contact_id_a'         => $bush_jr_id,
      'contact_id_b'         => $bush_sr_id,
      'is_permission_a_b'    => 1,
      ));

    // create some relationships
    $this->callAPISuccess('Relationship', 'create', array(
      'relationship_type_id' => 1,  // CHILD OF
      'contact_id_a'         => $bush_brbra_id,
      'contact_id_b'         => $bush_jr_id,
      'is_permission_a_b'    => 1,
      ));

    return array($user_id, $bush_sr_id, $bush_jr_id, $bush_laura_id, $bush_brbra_id);
  }

}
