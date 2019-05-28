<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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

/**
 * Test class for CRM_Contact_BAO_GroupContact BAO
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Contact_BAO_GroupContactTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   *
   * This method is called after a test is executed.
   */
  protected function tearDown() {
  }

  /**
   * Test case for add( ).
   */
  public function testAdd() {

    //creates a test group contact by recursively creation
    //lets create 10 groupContacts for fun
    $groupContacts = CRM_Core_DAO::createTestObject('CRM_Contact_DAO_GroupContact', NULL, 10);

    //check the group contact id is not null for each of them
    foreach ($groupContacts as $gc) {
      $this->assertNotNull($gc->id);
    }

    //cleanup
    foreach ($groupContacts as $gc) {
      $gc->deleteTestObjects('CRM_Contact_DAO_GroupContact');
    }
  }

  /**
   * Test case for getGroupId( )
   */
  public function testGetGroupId() {

    //creates a test groupContact object
    //force group_id to 1 so we can compare
    $groupContact = CRM_Core_DAO::createTestObject('CRM_Contact_DAO_GroupContact');

    //check the group contact id is not null
    $this->assertNotNull($groupContact->id);

    $groupId = CRM_Core_DAO::singleValueQuery('select max(id) from civicrm_group');

    $this->assertEquals($groupContact->group_id, $groupId, 'Check for group_id');

    //cleanup
    $groupContact->deleteTestObjects('CRM_Contact_DAO_GroupContact');
  }

  /**
   *  Test case for contact search: CRM-6706, CRM-6586 Parent Group search should return contacts from child groups too.
   */
  public function testContactSearchByParentGroup() {
    // create a parent group
    $parentGroup = $this->callAPISuccess('Group', 'create', array(
      'title' => 'Parent Group',
      'description' => 'Parent Group',
      'visibility' => 'User and User Admin Only',
      'is_active' => 1,
    ));

    // create a child group
    $childGroup = $this->callAPISuccess('Group', 'create', array(
      'title' => 'Child Group',
      'description' => 'Child Group',
      'visibility' => 'User and User Admin Only',
      'parents' => $parentGroup['id'],
      'is_active' => 1,
    ));

    // create smart group based on saved criteria Gender = Male
    $batch = $this->callAPISuccess('SavedSearch', 'create', array(
      'form_values' => 'a:1:{i:0;a:5:{i:0;s:9:"gender_id";i:1;s:1:"=";i:2;i:2;i:3;i:0;i:4;i:0;}}',
    ));
    // Create contact with Gender - Male
    $childSmartGroupContact = $this->individualCreate(array(
      'gender_id' => "Male",
      'first_name' => 'C',
    ), 1);
    // then create smart group
    $childSmartGroup = $this->callAPISuccess('Group', 'create', array(
      'title' => 'Child Smart Group',
      'description' => 'Child Smart Group',
      'visibility' => 'User and User Admin Only',
      'saved_search_id' => $batch['id'],
      'is_active' => 1,
      'parents' => $parentGroup['id'],
    ));

    $this->callAPISuccess('Group', 'create', array(
      'id' => $parentGroup['id'],
      'children' => implode(',', array($childGroup['id'], $childSmartGroup['id'])),
    ));

    // Create a contact within parent group
    $parentContactParams = array(
      'first_name' => 'Parent1 Fname',
      'last_name' => 'Parent1 Lname',
      'group' => array($parentGroup['id'] => 1),
    );
    $parentContact = $this->individualCreate($parentContactParams);

    // create a contact within child dgroup
    $childContactParams = array(
      'first_name' => 'Child1 Fname',
      'last_name' => 'Child2 Lname',
      'group' => array($childGroup['id'] => 1),
    );
    $childContact = $this->individualCreate($childContactParams);

    // Check if searching by parent group  returns both parent and child group contacts
    $result = $this->callAPISuccess('contact', 'get', array(
      'group' => $parentGroup['id'],
    ));
    $validContactIds = array($parentContact, $childContact);
    $resultContactIds = array();
    foreach ($result['values'] as $k => $v) {
      $resultContactIds[] = $v['contact_id'];
    }
    $this->assertEquals(3, count($resultContactIds), 'Check the count of returned values');
    $this->assertEquals(array(), array_diff($validContactIds, $resultContactIds), 'Check that the difference between two arrays should be blank array');

    // Check if searching by child group returns just child group contacts
    $result = $this->callAPISuccess('contact', 'get', array(
      'group' => $childGroup['id'],
    ));
    $validChildContactIds = array($childContact);
    $resultChildContactIds = array();
    foreach ($result['values'] as $k => $v) {
      $resultChildContactIds[] = $v['contact_id'];
    }
    $this->assertEquals(1, count($resultChildContactIds), 'Check the count of returned values');
    $this->assertEquals(array(), array_diff($validChildContactIds, $resultChildContactIds), 'Check that the difference between two arrays should be blank array');

    // Check if searching by smart child group returns just smart child group contacts
    $result = $this->callAPISuccess('contact', 'get', array(
      'group' => $childSmartGroup['id'],
    ));
    $validChildContactIds = array($childSmartGroupContact);
    $resultChildContactIds = array();
    foreach ($result['values'] as $k => $v) {
      $resultChildContactIds[] = $v['contact_id'];
    }
    $this->assertEquals(1, count($resultChildContactIds), 'Check the count of returned values');
    $this->assertEquals(array(), array_diff($validChildContactIds, $resultChildContactIds), 'Check that the difference between two arrays should be blank array');

    //cleanup
    $this->callAPISuccess('Contact', 'delete', array('id' => $parentContact));
    $this->callAPISuccess('Contact', 'delete', array('id' => $childContact));
    $this->callAPISuccess('Contact', 'delete', array('id' => $childSmartGroupContact));
  }

  /**
   *  CRM-19698: Test case for combine contact search in regular and smart group
   */
  public function testContactCombineGroupSearch() {
    // create regular group based
    $regularGroup = $this->callAPISuccess('Group', 'create', array(
      'title' => 'Regular Group',
      'description' => 'Regular Group',
      'visibility' => 'User and User Admin Only',
      'is_active' => 1,
    ));

    // Create contact with Gender - Male
    $contact1 = $this->individualCreate(array(
      'gender_id' => "Male",
      'first_name' => 'A',
    ));

    // Create contact with Gender - Male and in regular group
    $contact2 = $this->individualCreate(array(
      'group' => array($regularGroup['id'] => 1),
      'gender_id' => "Male",
      'first_name' => 'B',
    ), 1);

    // Create contact with Gender - Female and in regular group
    $contact3 = $this->individualCreate(array(
      'group' => array($regularGroup['id'] => 1),
      'gender_id' => "Female",
      'first_name' => 'C',
    ), 1);

    // create smart group based on saved criteria Gender = Male
    $batch = $this->callAPISuccess('SavedSearch', 'create', array(
      'form_values' => 'a:1:{i:0;a:5:{i:0;s:9:"gender_id";i:1;s:1:"=";i:2;i:2;i:3;i:0;i:4;i:0;}}',
    ));
    $smartGroup = $this->callAPISuccess('Group', 'create', array(
      'title' => 'Smart Group',
      'description' => 'Smart Group',
      'visibility' => 'User and User Admin Only',
      'saved_search_id' => $batch['id'],
      'is_active' => 1,
    ));

    $useCases = array(
      //Case 1: Find all contacts in regular group
      array(
        'form_value' => array('group' => $regularGroup['id']),
        'expected_count' => 2,
        'expected_contact' => array($contact2, $contact3),
      ),
      //Case 2: Find all contacts in smart group
      array(
        'form_value' => array('group' => $smartGroup['id']),
        'expected_count' => 2,
        'expected_contact' => array($contact1, $contact2),
      ),
      //Case 3: Find all contacts in regular group and smart group
      array(
        'form_value' => array('group' => array('IN' => array($regularGroup['id'], $smartGroup['id']))),
        'expected_count' => 3,
        'expected_contact' => array($contact1, $contact2, $contact3),
      ),
    );
    foreach ($useCases as $case) {
      $query = new CRM_Contact_BAO_Query(CRM_Contact_BAO_Query::convertFormValues($case['form_value']));
      list($select, $from, $where, $having) = $query->query();
      $groupContacts = CRM_Core_DAO::executeQuery("SELECT DISTINCT contact_a.* $from $where ORDER BY contact_a.first_name")->fetchAll();
      foreach ($groupContacts as $key => $value) {
        $groupContacts[$key] = $value['id'];
      }
      $this->assertEquals($case['expected_count'], count($groupContacts));
      $this->checkArrayEquals($case['expected_contact'], $groupContacts);
    }
  }

  /**
   *  CRM-19333: Test case for contact search on basis of group type
   */
  public function testbyGroupType() {
    $groupTypes = CRM_Core_BAO_OptionValue::getOptionValuesAssocArrayFromName('group_type');
    $mailingListGT = array_search('Mailing List', $groupTypes);
    $accessControlGT = array_search('Access Control', $groupTypes);

    // create group with group type - Mailing list
    $group1 = $this->callAPISuccess('Group', 'create', array(
      'title' => 'Group 1',
      'visibility' => 'User and User Admin Only',
      'is_active' => 1,
      'group_type' => $mailingListGT,
    ));

    // create group with group type - Access Control
    $group2 = $this->callAPISuccess('Group', 'create', array(
      'title' => 'Group 2',
      'visibility' => 'User and User Admin Only',
      'is_active' => 1,
      'group_type' => $accessControlGT,
    ));

    // create contact in 'Group 1'
    $contact1 = $this->individualCreate(array(
      'group' => array($group1['id'] => 1),
      'first_name' => 'A',
    ));

    // create contact in 'Group 2'
    $contact2 = $this->individualCreate(array(
      'group' => array($group2['id'] => 1),
      'first_name' => 'B',
    ), 1);

    $useCases = array(
      //Case 1: Find contacts in group type - Mailing List
      array(
        'form_value' => array('group_type' => array($mailingListGT)),
        'expected_count' => 1,
        'expected_contact' => array($contact1),
      ),
      //Case 2: Find contacts in group type - Access Control
      array(
        'form_value' => array('group_type' => array($accessControlGT)),
        'expected_count' => 1,
        'expected_contact' => array($contact2),
      ),
      //Case 3: Find contacts in group type - Mailing List or Access List
      array(
        'form_value' => array('group_type' => array($mailingListGT, $accessControlGT)),
        'expected_count' => 2,
        'expected_contact' => array($contact1, $contact2),
      ),
    );

    foreach ($useCases as $case) {
      $query = new CRM_Contact_BAO_Query(CRM_Contact_BAO_Query::convertFormValues($case['form_value']));
      list($select, $from, $where, $having) = $query->query();
      $groupContacts = CRM_Core_DAO::executeQuery("SELECT DISTINCT contact_a.id, contact_a.first_name $from $where ORDER BY contact_a.first_name")->fetchAll();
      foreach ($groupContacts as $key => $value) {
        $groupContacts[$key] = $value['id'];
      }
      $this->assertEquals($case['expected_count'], count($groupContacts));
      $this->checkArrayEquals($case['expected_contact'], $groupContacts);
    }
  }

}
