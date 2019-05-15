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
    // $this->quickCleanup(array('civicrm_acl_contact_cache'), TRUE);
    $this->useTransaction(TRUE);
    $this->allowedContactsACL = array();
  }

  /**
   * general test for the 'view all contacts' permission
   */
  public function testViewAllPermission() {
    // create test contacts
    $contacts = $this->createScenarioPlain();

    // test WITH all permissions
    // NULL means 'all permissions' in UnitTests environment
    CRM_Core_Config::singleton()->userPermissionClass->permissions = NULL;
    $result = CRM_Contact_BAO_Contact_Permission::allowList($contacts);
    sort($result);
    $this->assertEquals($result, $contacts, "Contacts should be viewable when 'view all contacts'");

    // test WITH explicit permission
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('view all contacts');
    $result = CRM_Contact_BAO_Contact_Permission::allowList($contacts, CRM_Core_Permission::VIEW);
    sort($result);
    $this->assertEquals($result, $contacts, "Contacts should be viewable when 'view all contacts'");

    // test WITH EDIT permissions (should imply VIEW)
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('edit all contacts');
    $result = CRM_Contact_BAO_Contact_Permission::allowList($contacts, CRM_Core_Permission::VIEW);
    sort($result);
    $this->assertEquals($result, $contacts, "Contacts should be viewable when 'edit all contacts'");

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
    $this->assertEquals(count($result), count($contacts) - 1, "Only deleted contacts should be excluded");
  }

  /**
   * Test access based on relations
   *
   * There should be the following permission-relationship
   * contact[0] -> contact[1] -> contact[2]
   */
  public function testPermissionByRelation() {
    // create test scenario
    $contacts = $this->createScenarioRelations();

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

      $this->assertNotContains($contacts[0], $result, "User[0] should NOT have $permission_label permission on contact[0].");
      $this->assertContains($contacts[1], $result, "User[0] should have $permission_label permission on contact[1].");
      $this->assertNotContains($contacts[2], $result, "User[0] should NOT have $permission_label permission on contact[2].");
      $this->assertNotContains($contacts[3], $result, "User[0] should NOT have $permission_label permission on contact[3].");
      $this->assertNotContains($contacts[4], $result, "User[0] should NOT have $permission_label permission on contact[4].");
      // view (b_a)
      if ($permission == CRM_Core_Permission::VIEW) {
        $this->assertContains($contacts[5], $result, "User[0] should have $permission_label permission on contact[5].");
      }
      else {
        $this->assertNotContains($contacts[5], $result, "User[0] should NOT have $permission_label permission on contact[5].");
      }
      $this->assertNotContains($contacts[6], $result, "User[0] should NOT have $permission_label permission on contact[6].");
      $this->assertNotContains($contacts[7], $result, "User[0] should NOT have $permission_label permission on contact[7].");
      // edit (a_b)
      $this->assertContains($contacts[8], $result, "User[0] should have $permission_label permission on contact[8].");
      $this->assertNotContains($contacts[9], $result, "User[0] should NOT have $permission_label permission on contact[9].");
    }

    // run this for SECOND DEGREE relations
    $config->secondDegRelPermissions = TRUE;
    $this->assertTrue($config->secondDegRelPermissions);
    foreach ($permissions_to_check as $permission => $permission_label) {
      $result = CRM_Contact_BAO_Contact_Permission::allowList($contacts, $permission);
      sort($result);

      $this->assertNotContains($contacts[0], $result, "User[0] should NOT have second degree $permission_label permission on contact[0].");
      $this->assertContains($contacts[1], $result, "User[0] should have second degree $permission_label permission on contact[1].");
      // Edit then edit -> edit
      $this->assertContains($contacts[2], $result, "User[0] should have second degree $permission_label permission on contact[2].");
      $this->assertNotContains($contacts[3], $result, "User[0] should NOT have second degree $permission_label permission on contact[3].");
      $this->assertNotContains($contacts[4], $result, "User[0] should NOT have second degree $permission_label permission on contact[4].");
      // View then Edit -> View
      if ($permission == CRM_Core_Permission::VIEW) {
        $this->assertContains($contacts[5], $result, "User[0] should have second degree $permission_label permission on contact[5].");
        $this->assertContains($contacts[6], $result, "User[0] should have second degree $permission_label permission on contact[6].");
      }
      else {
        $this->assertNotContains($contacts[5], $result, "User[0] should NOT have second degree $permission_label permission on contact[5].");
        $this->assertNotContains($contacts[6], $result, "User[0] should NOT have second degree $permission_label permission on contact[6].");
      }
      // View then Edit -> View
      if ($permission == CRM_Core_Permission::VIEW) {
        $this->assertContains($contacts[7], $result, "User[0] should have second degree $permission_label permission on contact[7].");
      }
      else {
        $this->assertNotContains($contacts[7], $result, "User[0] should NOT have second degree $permission_label permission on contact[7].");
      }
      // Edit then View -> View
      $this->assertContains($contacts[8], $result, "User[0] should have second degree $permission_label permission on contact[8].");
      if ($permission == CRM_Core_Permission::VIEW) {
        $this->assertContains($contacts[9], $result, "User[0] should have second degree $permission_label permission on contact[9].");
      }
      else {
        $this->assertNotContains($contacts[9], $result, "User[0] should NOT have second degree $permission_label permission on contact[9].");
      }
    }
  }

  /**
   * Test access based on ACL
   */
  public function testPermissionByACL() {
    $contacts = $this->createScenarioPlain();

    // set custom hook
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'hook_civicrm_aclWhereClause'));

    // run simple test
    $permissions_to_check = array(CRM_Core_Permission::VIEW => 'View', CRM_Core_Permission::EDIT => 'Edit');

    $this->allowedContactsACL = array($contacts[0], $contacts[1], $contacts[4]);
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array();
    $result = CRM_Contact_BAO_Contact_Permission::allowList($contacts);
    sort($result);

    $this->assertContains($contacts[0], $result, "User[0] should NOT have an ACL permission on contact[0].");
    $this->assertContains($contacts[1], $result, "User[0] should have an ACL permission on contact[1].");
    $this->assertNotContains($contacts[2], $result, "User[0] should NOT have an ACL permission on contact[2].");
    $this->assertNotContains($contacts[3], $result, "User[0] should NOT have an RELATION permission on contact[3].");
    $this->assertContains($contacts[4], $result, "User[0] should NOT have an ACL permission on contact[4].");
  }

  /**
   * Test access with a mix of ACL and relationship
   */
  public function testPermissionACLvsRelationship() {
    $contacts = $this->createScenarioRelations();

    // set custom hook
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'hook_civicrm_aclWhereClause'));

    $config = CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = array();
    $config->secondDegRelPermissions = TRUE;

    $this->allowedContactsACL = array($contacts[0], $contacts[1], $contacts[4]);
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array();
    $result = CRM_Contact_BAO_Contact_Permission::allowList($contacts);
    sort($result);

    $this->assertContains($contacts[0], $result, "User[0] should have an ACL permission on contact[0].");
    $this->assertContains($contacts[1], $result, "User[0] should have an ACL permission on contact[1].");
    $this->assertContains($contacts[2], $result, "User[0] should have second degree an relation permission on contact[2].");
    $this->assertNotContains($contacts[3], $result, "User[0] should NOT have an ACL permission on contact[3].");
    $this->assertContains($contacts[4], $result, "User[0] should have an ACL permission on contact[4].");
  }

  /**
   * Test access related to the 'access deleted contact' permission
   */
  public function testPermissionCompare() {
    $contacts = $this->createScenarioRelations();
    $contact_index = array_flip($contacts);

    // set custom hook
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'hook_civicrm_aclWhereClause'));

    $config = CRM_Core_Config::singleton();
    $this->allowedContactsACL = array($contacts[0], $contacts[1], $contacts[4]);
    $config->secondDegRelPermissions = TRUE;

    // test configurations
    $permissions_to_check    = array(CRM_Core_Permission::VIEW => 'View', CRM_Core_Permission::EDIT => 'Edit');
    $user_permission_options = array(/*ALL*/ NULL, /*NONE*/ array(), array('view all contacts'), array('edit all contacts'), array('view all contacts', 'edit all contacts'));

    // run all combinations of those
    foreach ($permissions_to_check as $permission_to_check => $permission_label) {
      foreach ($user_permission_options as $user_permissions) {
        // select the contact range
        $contact_range = $contacts;
        if (is_array($user_permissions) && count($user_permissions) == 0) {
          // slight (explainable) deviation on the own contact
          unset($contact_range[0]);
        }

        $config->userPermissionClass->permissions = $user_permissions;
        $user_permissions_label = json_encode($user_permissions);

        // get the list result
        $list_result = CRM_Contact_BAO_Contact_Permission::allowList($contact_range, $permission_to_check);
        $this->assertTrue(count($list_result) <= count($contact_range), "Permission::allowList should return a subset of the contats.");
        foreach ($list_result as $contact_id) {
          $this->assertContains($contact_id, $contact_range, "Permission::allowList should return a subset of the contats.");
        }

        // now compare the results
        foreach ($contact_range as $contact_id) {
          $individual_result = CRM_Contact_BAO_Contact_Permission::allow($contact_id, $permission_to_check);

          if (in_array($contact_id, $list_result)) {
            // listPermission reports PERMISSION GRANTED
            $this->assertTrue($individual_result, "Permission::allow denies {$permission_label} access for contact[{$contact_index[$contact_id]}], while Permission::allowList grants it. User permission: '{$user_permissions_label}'");

          }
          else {
            // listPermission reports PERMISSION DENIED
            $this->assertFalse($individual_result, "Permission::allow grantes {$permission_label} access for contact[{$contact_index[$contact_id]}], while Permission::allowList denies it. User permission: '{$user_permissions_label}'");

          }
        }
      }
    }
  }

  /*
   * Scenario Builders
   */

  /**
   * create plain test scenario, no relationships/ACLs
   */
  protected function createScenarioPlain() {
    // get logged in user
    $user_id = $this->createLoggedInUser();
    $this->assertNotEmpty($user_id);

    // create test contacts
    $bush_sr_id    = $this->individualCreate(array('first_name' => 'George', 'middle_name' => 'H. W.', 'last_name' => 'Bush'));
    $bush_jr_id    = $this->individualCreate(array('first_name' => 'George', 'middle_name' => 'W.', 'last_name' => 'Bush'));
    $bush_laura_id = $this->individualCreate(array('first_name' => 'Laura Lane', 'last_name' => 'Bush'));
    $bush_brbra_id = $this->individualCreate(array('first_name' => 'Barbara', 'last_name' => 'Bush'));
    $bush_brother_id = $this->individualCreate(array('first_name' => 'Brother', 'last_name' => 'Bush'));
    $bush_nephew_id = $this->individualCreate(array('first_name' => 'Nephew', 'last_name' => 'Bush'));
    $bush_nephew2_id = $this->individualCreate(array('first_name' => 'Nephew2', 'last_name' => 'Bush'));
    $bush_otherbro_id = $this->individualCreate(array('first_name' => 'Other Brother', 'last_name' => 'Bush'));
    $bush_otherneph_id = $this->individualCreate(array('first_name' => 'Other Nephew', 'last_name' => 'Bush'));

    $contacts = array($user_id, $bush_sr_id, $bush_jr_id, $bush_laura_id, $bush_brbra_id, $bush_brother_id, $bush_nephew_id, $bush_nephew2_id, $bush_otherbro_id, $bush_otherneph_id);
    sort($contacts);
    return $contacts;
  }

  /**
   * create plain test scenario, no relationships/ACLs
   */
  protected function createScenarioRelations() {
    $contacts = $this->createScenarioPlain();

    // create some relationships
    $this->callAPISuccess('Relationship', 'create', array(
      // CHILD OF
      'relationship_type_id' => 1,
      'contact_id_a'         => $contacts[1],
      'contact_id_b'         => $contacts[0],
      'is_permission_b_a'    => 1,
      'is_active'            => 1,
    ));

    $this->callAPISuccess('Relationship', 'create', array(
      // CHILD OF
      'relationship_type_id' => 1,
      'contact_id_a'         => $contacts[2],
      'contact_id_b'         => $contacts[1],
      'is_permission_b_a'    => 1,
      'is_active'            => 1,
    ));

    $this->callAPISuccess('Relationship', 'create', array(
      // CHILD OF
      'relationship_type_id' => 1,
      'contact_id_a'         => $contacts[4],
      'contact_id_b'         => $contacts[2],
      'is_permission_b_a'    => 1,
      'is_active'            => 1,
    ));

    $this->callAPISuccess('Relationship', 'create', array(
      // SIBLING OF
      'relationship_type_id' => 4,
      'contact_id_a'         => $contacts[5],
      'contact_id_b'         => $contacts[0],
      // View
      'is_permission_b_a'    => 2,
      'is_active'            => 1,
    ));

    $this->callAPISuccess('Relationship', 'create', array(
      // CHILD OF
      'relationship_type_id' => 1,
      'contact_id_a'         => $contacts[6],
      'contact_id_b'         => $contacts[5],
      // Edit
      'is_permission_b_a'    => 1,
      'is_active'            => 1,
    ));

    $this->callAPISuccess('Relationship', 'create', array(
      // CHILD OF
      'relationship_type_id' => 1,
      'contact_id_a'         => $contacts[7],
      'contact_id_b'         => $contacts[5],
      // View
      'is_permission_b_a'    => 2,
      'is_active'            => 1,
    ));

    $this->callAPISuccess('Relationship', 'create', array(
      // SIBLING OF
      'relationship_type_id' => 4,
      'contact_id_a'         => $contacts[0],
      'contact_id_b'         => $contacts[8],
      // edit  (as a_b)
      'is_permission_a_b'    => 1,
      'is_active'            => 1,
    ));

    $this->callAPISuccess('Relationship', 'create', array(
      // CHILD OF
      'relationship_type_id' => 1,
      'contact_id_a'         => $contacts[9],
      'contact_id_b'         => $contacts[8],
      // view
      'is_permission_b_a'    => 2,
      'is_active'            => 1,
    ));

    return $contacts;
  }

  /**
   * ACL HOOK implementation for various tests
   */
  public function hook_civicrm_aclWhereClause($type, &$tables, &$whereTables, &$contactID, &$where) {
    if (!empty($this->allowedContactsACL)) {
      $contact_id_list = implode(',', $this->allowedContactsACL);
      $where = " contact_a.id IN ($contact_id_list)";
    }
  }

}
