<?php
/**
 *  File for the TestContact class
 *
 *  (PHP 5)
 *
 *   @author Walt Haas <walt@dharmatech.org> (801) 534-1262
 *   @copyright Copyright CiviCRM LLC (C) 2009
 *   @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU Affero General Public License version 3
 *   @version   $Id: ContactTest.php 31254 2010-12-15 10:09:29Z eileen $
 *   @package   CiviCRM
 *
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */

/**
 *  Include class definitions
 */
require_once 'CiviTest/CiviUnitTestCase.php';


/**
 *  Test APIv3 civicrm_contact* functions
 *
 *  @package CiviCRM_APIv3
 *  @subpackage API_Contact
 */

class api_v3_ContactTest extends CiviUnitTestCase {
  public $DBResetRequired = FALSE;
  protected $_apiversion;
  protected $_entity;
  protected $_params;

  protected $_contactID;
  protected $_financialTypeId =1;

  /**
   *  Constructor
   *
   *  Initialize configuration
   */
  function __construct() {
    parent::__construct();
  }

  /**
   *  Test setup for every test
   *
   *  Connect to the database, truncate the tables that will be used
   *  and redirect stdin to a temporary file
   */
  public function setUp() {
    //  Connect to the database
    parent::setUp();
    $this->_apiversion = 3;
    $this->_entity = 'contact';
    $this->_params = array(
      'first_name' => 'abc1',
      'contact_type' => 'Individual',
      'last_name' => 'xyz1',
    );
  }

  function tearDown() {
    // truncate a few tables
    $tablesToTruncate = array(
      'civicrm_contact',
      'civicrm_email',
      'civicrm_contribution',
      'civicrm_line_item',
      'civicrm_website',
      'civicrm_relationship',
      'civicrm_uf_match',
      'civicrm_phone',
    );

    $this->quickCleanup($tablesToTruncate, TRUE);
  }

  /**
   *  Test civicrm_contact_create
   *
   *  Verify that attempt to create individual contact with only
   *  first and last names succeeds
   */
  function testAddCreateIndividual() {
    $oldCount = CRM_Core_DAO::singleValueQuery('select count(*) from civicrm_contact');
    $params = array(
      'first_name' => 'abc1',
      'contact_type' => 'Individual',
      'last_name' => 'xyz1',
    );

    $contact = $this->callAPISuccess('contact', 'create', $params);
    $this->assertTrue(is_numeric($contact['id']), "In line " . __LINE__);
    $this->assertTrue($contact['id'] > 0, "In line " . __LINE__);
    $newCount = CRM_Core_DAO::singleValueQuery('select count(*) from civicrm_contact');
    $this->assertEquals($oldCount+1, $newCount);

    $this->assertDBState('CRM_Contact_DAO_Contact',
      $contact['id'],
      $params
    );
  }

  /**
   *  Test civicrm_contact_create with sub-types
   *
   *  Verify that sub-types are created successfully and not deleted by subsequent updates
   */
  function testIndividualSubType() {
    $params = array(
      'first_name' => 'test abc',
      'contact_type' => 'Individual',
      'last_name' => 'test xyz',
      'contact_sub_type' => array('Student', 'Staff'),
     );
    $contact = $this->callAPISuccess('contact', 'create', $params);
    $cid = $contact['id'];

    $params = array(
      'id' => $cid,
      'middle_name' => 'foo',
    );
    $this->callAPISuccess('contact', 'create', $params);
    unset($params['middle_name']);

    $contact = $this->callAPISuccess('contact', 'get', $params);

    $this->assertEquals(array('Student', 'Staff'), $contact['values'][$cid]['contact_sub_type'], "In line " . __LINE__);
  }

  /**
   *  Verify that attempt to create contact with empty params fails
   */
  function testCreateEmptyContact() {
    $this->callAPIFailure('contact', 'create', array());
  }

  /**
   *  Verify that attempt to create contact with bad contact type fails
   */
  function testCreateBadTypeContact() {
    $params = array(
      'email' => 'man1@yahoo.com',
      'contact_type' => 'Does not Exist',
    );
    $result = $this->callAPIFailure('contact', 'create', $params,"'Does not Exist' is not a valid option for field contact_type");
  }

  /**
   *  Verify that attempt to create individual contact with required
   *  fields missing fails
   */
  function testCreateBadRequiredFieldsIndividual() {
    $params = array(
      'middle_name' => 'This field is not required',
      'contact_type' => 'Individual',
    );

    $contact = $this->callAPIFailure('contact', 'create', $params);
  }

  /**
   *  Verify that attempt to create household contact with required
   *  fields missing fails
   */
  function testCreateBadRequiredFieldsHousehold() {
    $params = array(
      'middle_name' => 'This field is not required',
      'contact_type' => 'Household',
    );
    $contact = $this->callAPIFailure('contact', 'create', $params);
  }

  /**
   *  Verify that attempt to create organization contact with
   *  required fields missing fails
   */
  function testCreateBadRequiredFieldsOrganization() {
    $params = array(
      'middle_name' => 'This field is not required',
      'contact_type' => 'Organization',
    );

    $contact = $this->callAPIFailure('contact', 'create', $params);
  }

  /**
   *  Verify that attempt to create individual contact with only an
   *  email succeeds
   */
  function testCreateEmailIndividual() {

    $params = array(
      'email' => 'man3@yahoo.com',
      'contact_type' => 'Individual',
      'location_type_id' => 1,
    );

    $contact = $this->callAPISuccess('contact', 'create', $params);

    $this->assertEquals(1, $contact['id'], "In line " . __LINE__);
    $email = $this->callAPISuccess('email', 'get', array('contact_id' => $contact['id']));
    $this->assertEquals(1, $email['count']);
    $this->assertEquals('man3@yahoo.com', $email['values'][$email['id']]['email']);

    $this->callAPISuccess('contact', 'delete', $contact);
  }

  /**
   *  Verify that attempt to create individual contact with only
   *  first and last names succeeds
   */
  function testCreateNameIndividual() {
    $params = array(
      'first_name' => 'abc1',
      'contact_type' => 'Individual',
      'last_name' => 'xyz1',
    );

    $contact = $this->callAPISuccess('contact', 'create', $params);
    $this->assertEquals(1, $contact['id']);
  }

  /**
   *  Verify that attempt to create individual contact with
   *  first and last names and old key values works
   */
  function testCreateNameIndividualOldKeys() {
    $params = array(
      'individual_prefix' => 'Dr.',
      'first_name' => 'abc1',
      'contact_type' => 'Individual',
      'last_name' => 'xyz1',
      'individual_suffix' => 'Jr.',
    );

    $contact = $this->callAPISuccess('contact', 'create', $params);
    $result = $this->callAPISuccess('contact', 'getsingle', array('id' => $contact['id']));

    $this->assertArrayKeyExists('prefix_id', $result);
    $this->assertArrayKeyExists('suffix_id', $result);
    $this->assertArrayKeyExists('gender_id', $result);
    $this->assertEquals(4, $result['prefix_id']);
    $this->assertEquals(1, $result['suffix_id']);
  }

  /**
   *  Verify that attempt to create individual contact with
   *  first and last names and old key values works
   */
  function testCreateNameIndividualrecommendedKeys2() {
    $params = array(
      'prefix_id' => 'Dr.',
      'first_name' => 'abc1',
      'contact_type' => 'Individual',
      'last_name' => 'xyz1',
      'suffix_id' => 'Jr.',
      'gender_id' => 'Male',
    );

    $contact = $this->callAPISuccess('contact', 'create', $params);
    $result = $this->callAPISuccess('contact', 'getsingle', array('id' => $contact['id']));

    $this->assertArrayKeyExists('prefix_id', $result);
    $this->assertArrayKeyExists('suffix_id', $result);
    $this->assertArrayKeyExists('gender_id', $result);
    $this->assertEquals(4, $result['prefix_id']);
    $this->assertEquals(1, $result['suffix_id']);
  }

  /**
   *  Verify that attempt to create household contact with only
   *  household name succeeds
   */
  function testCreateNameHousehold() {
    $params = array(
      'household_name' => 'The abc Household',
      'contact_type' => 'Household',
    );
    $contact = $this->callAPISuccess('contact', 'create', $params);
    $this->assertEquals(1, $contact['id'], "In line " . __LINE__);
  }

  /**
   *  Verify that attempt to create organization contact with only
   *  organization name succeeds
   */
  function testCreateNameOrganization() {
    $params = array(
      'organization_name' => 'The abc Organization',
      'contact_type' => 'Organization',
    );
    $contact = $this->callAPISuccess('contact', 'create', $params);
    $this->assertEquals(1, $contact['id']);
  }
  /**
   *  Verify that attempt to create organization contact without organization name fails
   */
  function testCreateNoNameOrganization() {
    $params = array(
      'first_name' => 'The abc Organization',
      'contact_type' => 'Organization',
    );
    $result = $this->callAPIFailure('contact', 'create', $params);
  }
  /**
   * check with complete array + custom field
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   */
  function testCreateWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";
    $description = "/*this demonstrates setting a custom field through the API ";
    $subfile = "CustomFieldCreate";
    $result = $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__, $description);

    $check = $this->callAPISuccess($this->_entity, 'get', array('return.custom_' . $ids['custom_field_id'] => 1, 'id' => $result['id']));
    $this->assertEquals("custom string", $check['values'][$check['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   * CRM-12773 - expectation is that civicrm quietly ignores
   * fields without values
   */
  function testCreateWithNULLCustomCRM12773() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = NULL;
    $result = $this->callAPISuccess('contact', 'create', $params);
    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }


  /*
   * Test creating a current employer through API
   */
  function testContactCreateCurrentEmployer(){
    //here we will just do the get for set-up purposes
    $count = $this->callAPISuccess('contact', 'getcount', array(
      'organization_name' => 'new employer org',
      'contact_type' => 'Organization'
    ));
    $this->assertEquals(0, $count);
    $employerResult = $this->callAPISuccess('contact', 'create', array_merge($this->_params, array(
      'current_employer' => 'new employer org',)
    ));
    // do it again as an update to check it doesn't cause an error
    $employerResult = $this->callAPISuccess('contact', 'create', array_merge($this->_params, array(
        'current_employer' => 'new employer org', 'id' => $employerResult['id'])
    ));
    $expectedCount = 1;
    $count = $this->callAPISuccess('contact', 'getcount', array(
      'organization_name' => 'new employer org',
      'contact_type' => 'Organization'
    ),
    $expectedCount);

    $result = $this->callAPISuccess('contact', 'getsingle', array(
      'id' => $employerResult['id'],
    ));

    $this->assertEquals('new employer org', $result['current_employer']);

  }
  /*
   * Test creating a current employer through API
   * - check it will re-activate a de-activated employer
  */
  function testContactCreateDuplicateCurrentEmployerEnables(){
    //set up  - create employer relationship
    $employerResult = $this->callAPISuccess('contact', 'create', array_merge($this->_params, array(
        'current_employer' => 'new employer org',)
    ));
    $relationship = $this->callAPISuccess('relationship','get', array(
      'contact_id_a' => $employerResult['id'],
   ));

    //disable & check it is disabled
    $this->callAPISuccess('relationship', 'create', array('id' => $relationship['id'], 'is_active' => 0));
    $relationship = $this->callAPISuccess('relationship','getvalue', array(
      'id' => $relationship['id'],
      'return' => 'is_active'
    ), 0);

    //re-set the current employer - thus enabling the relationshp
    $employerResult = $this->callAPISuccess('contact', 'create', array_merge($this->_params, array(
        'current_employer' => 'new employer org', 'id' => $employerResult['id'])
    ));
    //check is_active is now 1
   $relationship = $this->callAPISuccess('relationship','getsingle', array(
     'return' => 'is_active',));
   $this->assertEquals(1, $relationship['is_active']);
  }

  /**
   * Check deceased contacts are not retrieved
   * Note at time of writing the default is to return default. This should possibly be changed & test added
   *
   */
  function testGetDeceasedRetrieved() {
    $c1 = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $c2 = $this->callAPISuccess($this->_entity, 'create', array('first_name' => 'bb', 'last_name' => 'ccc', 'contact_type' => 'Individual', 'is_deceased' => 1));
    $result = $this->callAPISuccess($this->_entity, 'get', array('is_deceased' => 0));
    $this->assertFalse(array_key_exists($c2['id'], $result['values']));
  }

  /*
     * Test that sort works - old syntax
     */
  function testGetSort() {
    $c1 = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $c2 = $this->callAPISuccess($this->_entity, 'create', array('first_name' => 'bb', 'last_name' => 'ccc', 'contact_type' => 'Individual'));
    $result = $this->callAPISuccess($this->_entity, 'get', array(
        'sort' => 'first_name ASC',
        'return.first_name' => 1,
        'sequential' => 1,
        'rowCount' => 1,
      ));

    $this->assertEquals('abc1', $result['values'][0]['first_name']);
    $result = $this->callAPISuccess($this->_entity, 'get', array(
      'sort' => 'first_name DESC',
      'return.first_name' => 1,
      'sequential' => 1,
      'rowCount' => 1,
    ));
    $this->assertEquals('bb', $result['values'][0]['first_name']);

    $this->callAPISuccess($this->_entity, 'delete', array('id' => $c1['id']));
    $this->callAPISuccess($this->_entity, 'delete', array('id' => $c2['id']));
  }
  /*
   * Test that we can retrieve contacts using
   * 'id' => array('IN' => array('3,4')) syntax
  */
  function testGetINIDArray() {
    $c1 = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $c2 = $this->callAPISuccess($this->_entity, 'create', array('first_name' => 'bb', 'last_name' => 'ccc', 'contact_type' => 'Individual'));
    $c3 = $this->callAPISuccess($this->_entity, 'create', array('first_name' => 'hh', 'last_name' => 'll', 'contact_type' => 'Individual'));
    $result = $this->callAPISuccess($this->_entity, 'get', array('id' => array('IN' => array($c1['id'], $c3['id']))));
    $this->assertEquals(2, $result['count']);
    $this->assertEquals(array($c1['id'], $c3['id']), array_keys($result['values']));
    $this->callAPISuccess($this->_entity, 'delete', array('id' => $c1['id']));
    $this->callAPISuccess($this->_entity, 'delete', array('id' => $c2['id']));
    $this->callAPISuccess($this->_entity, 'delete', array('id' => $c3['id']));
  }
  /*
   * Test variants on deleted behaviour
   */
  function testGetDeleted() {
    $params = $this->_params;
    $contact1 = $this->callAPISuccess('contact', 'create', $params);
    $params['is_deleted'] = 1;
    $params['last_name'] = 'bcd';
    $contact2 = $this->callAPISuccess('contact', 'create', $params);
    $countActive = $this->callAPISuccess('contact', 'getcount', array('showAll' => 'active'));
    $countAll = $this->callAPISuccess('contact', 'getcount', array('showAll' => 'all'));
    $countTrash = $this->callAPISuccess('contact', 'getcount', array('showAll' => 'trash'));
    $countDefault = $this->callAPISuccess('contact', 'getcount', array());
    $countDeleted = $this->callAPISuccess('contact', 'getcount', array(
      'contact_is_deleted' => 1,
      ));
    $countNotDeleted = $this->callAPISuccess('contact', 'getcount', array(
      'contact_is_deleted' => 0,
      ));
    $this->callAPISuccess('contact', 'delete', array('id' => $contact1['id']));
    $this->callAPISuccess('contact', 'delete', array('id' => $contact2['id']));
    $this->assertEquals(1, $countNotDeleted, 'contact_is_deleted => 0 is respected in line ' . __LINE__);
    $this->assertEquals(1, $countActive, 'in line ' . __LINE__);
    $this->assertEquals(1, $countTrash, 'in line ' . __LINE__);
    $this->assertEquals(2, $countAll, 'in line ' . __LINE__);
    $this->assertEquals(1, $countDeleted, 'in line ' . __LINE__);
    $this->assertEquals(1, $countDefault, 'Only active by default in line ' . __LINE__);
  }
  /*
     * Test that sort works - new syntax
     */
  function testGetSortNewSYntax() {
    $c1     = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $c2     = $this->callAPISuccess($this->_entity, 'create', array('first_name' => 'bb', 'last_name' => 'ccc', 'contact_type' => 'Individual'));
    $result = $this->callAPISuccess($this->_entity, 'getvalue', array(
      'return' => 'first_name',
        'options' => array(
          'limit' => 1,
          'sort' => 'first_name',
        ),
      ));
    $this->assertEquals('abc1', $result, 'in line' . __LINE__);

    $result = $this->callAPISuccess($this->_entity, 'getvalue', array(
        'return' => 'first_name',
        'options' => array(
          'limit' => 1,
          'sort' => 'first_name DESC',
        ),
      ));
    $this->assertEquals('bb', $result);

    $this->callAPISuccess($this->_entity, 'delete', array('id' => $c1['id']));
    $this->callAPISuccess($this->_entity, 'delete', array('id' => $c2['id']));
  }
  /*
   * Test appostrophe works in get & create
   */
  function testGetAppostropheCRM10857() {
    $params = array_merge($this->_params, array('last_name' => "O'Connor"));
    $contact = $this->callAPISuccess($this->_entity, 'create', $params);
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array(
      'last_name' => "O'Connor",
      'sequential' => 1,
    ));
    $this->assertEquals("O'Connor", $result['last_name'], 'in line' . __LINE__);
  }

  /**
   * check with complete array + custom field
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   */
  function testGetWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";
    $description = "/*this demonstrates setting a custom field through the API ";
    $subfile = "CustomFieldGet";
    $result = $this->callAPISuccess($this->_entity, 'create', $params);

    $check = $this->callAPIAndDocument($this->_entity, 'get', array('return.custom_' . $ids['custom_field_id'] => 1, 'id' => $result['id']), __FUNCTION__, __FILE__, $description, $subfile);

    $this->assertEquals("custom string", $check['values'][$check['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);
    $fields = ($this->callAPISuccess('contact', 'getfields', $params));
    $this->assertTrue(is_array($fields['values']['custom_' . $ids['custom_field_id']]));
    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }
  /*
     * check with complete array + custom field
     * Note that the test is written on purpose without any
     * variables specific to participant so it can be replicated into other entities
     * and / or moved to the automated test suite
     */
  function testGetWithCustomReturnSyntax() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";
    $description = "/*this demonstrates setting a custom field through the API ";
    $subfile = "CustomFieldGetReturnSyntaxVariation";
    $result = $this->callAPISuccess($this->_entity, 'create', $params);
    $params = array('return' => 'custom_' . $ids['custom_field_id'], 'id' => $result['id']);
    $check = $this->callAPIAndDocument($this->_entity, 'get', $params, __FUNCTION__, __FILE__, $description, $subfile);

    $this->assertEquals("custom string", $check['values'][$check['id']]['custom_' . $ids['custom_field_id']]);
    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   * Check that address name is returned if required
   */
  function testGetReturnAddressName () {
    $contactID = $this->individualCreate();
    $this->callAPISuccess('address', 'create', array('contact_id' => $contactID, 'address_name' => 'My house', 'location_type_id' => 'Home', 'street_address' => '1 my road'));
    $result = $this->callAPISuccessGetSingle('contact', array('return' => 'address_name, street_address', 'id' => $contactID));
    $this->assertEquals('1 my road', $result['street_address']);
    $this->assertEquals('My house', $result['address_name']);

  }

  function testGetGroupIDFromContact() {
    $groupId     = $this->groupCreate();
    $description = "Get all from group and display contacts";
    $subfile     = "GroupFilterUsingContactAPI";
    $params      = array(
      'email' => 'man2@yahoo.com',
      'contact_type' => 'Individual',
      'location_type_id' => 1,
      'api.group_contact.create' => array('group_id' => $groupId),
    );

    $contact = $this->callAPISuccess('contact', 'create', $params);
    // testing as integer
    $params = array(
      'filter.group_id' => $groupId,
      'contact_type' => 'Individual',
    );
    $result = $this->callAPIAndDocument('contact', 'get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(1, $result['count']);
    // group 26 doesn't exist, but we can still search contacts in it.
    $params = array(
      'filter.group_id' => 26,
      'contact_type' => 'Individual',
    );
    $result = $this->callAPISuccess('contact', 'get', $params);
    // testing as string
    $params = array(
      'filter.group_id' => "$groupId, 26",
      'contact_type' => 'Individual',
    );
    $result = $this->callAPIAndDocument('contact', 'get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(1, $result['count']);
    $params = array(
      'filter.group_id' => "26,27",
      'contact_type' => 'Individual',
    );
    $result = $this->callAPISuccess('contact', 'get', $params);

    // testing as string
    $params = array('filter.group_id' => array($groupId, 26),
      'contact_type' => 'Individual',
    );
    $result = $this->callAPIAndDocument('contact', 'get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(1, $result['count']);

    //test in conjunction with other criteria
      $params = array('filter.group_id' => array($groupId, 26),
      'contact_type' => 'Organization',
    );
    $result = $this->callAPISuccess('contact', 'get', $params);
    $params = array('filter.group_id' => array(26, 27),
      'contact_type' => 'Individual',
    );
    $result = $this->callAPISuccess('contact', 'get', $params);
    $this->assertEquals(0, $result['count'], " in line " . __LINE__);
  }

  /**
   *  Verify that attempt to create individual contact with two chained websites succeeds
   */
  function testCreateIndividualWithContributionDottedSyntax() {
    $description = "test demonstrates the syntax to create 2 chained entities";
    $subfile     = "ChainTwoWebsites";
    $params      = array(
      'first_name' => 'abc3',
      'last_name' => 'xyz3',
      'contact_type' => 'Individual',
      'email' => 'man3@yahoo.com',
      'api.contribution.create' => array(
        'receive_date' => '2010-01-01',
        'total_amount' => 100.00,
        'financial_type_id'   => $this->_financialTypeId,
        'payment_instrument_id' => 1,
        'non_deductible_amount' => 10.00,
        'fee_amount' => 50.00,
        'net_amount' => 90.00,
        'trxn_id' => 15345,
        'invoice_id' => 67990,
        'source' => 'SSF',
        'contribution_status_id' => 1,
      ),
      'api.website.create' => array(
        'url' => "http://civicrm.org",
      ),
      'api.website.create.2' => array(
        'url' => "http://chained.org",
      ),
    );

    $result = $this->callAPIAndDocument('Contact', 'create', $params, __FUNCTION__, __FILE__, $description, $subfile);

    $this->assertEquals(1, $result['id'], "In line " . __LINE__);
    // checking child function result not covered in callAPIAndDocument
    $this->assertAPISuccess($result['values'][$result['id']]['api.website.create']);
    $this->assertEquals("http://chained.org", $result['values'][$result['id']]['api.website.create.2']['values'][0]['url'], "In line " . __LINE__);
    $this->assertEquals("http://civicrm.org", $result['values'][$result['id']]['api.website.create']['values'][0]['url'], "In line " . __LINE__);

    // delete the contact
    $this->callAPISuccess('contact', 'delete', $result);
  }

  /**
   *  Verify that attempt to create individual contact with chained contribution and website succeeds
   */
  function testCreateIndividualWithContributionChainedArrays() {
    $params = array(
      'first_name' => 'abc3',
      'last_name' => 'xyz3',
      'contact_type' => 'Individual',
      'email' => 'man3@yahoo.com',
      'api.contribution.create' => array(
        'receive_date' => '2010-01-01',
        'total_amount' => 100.00,
        'financial_type_id'   => $this->_financialTypeId,
        'payment_instrument_id' => 1,
        'non_deductible_amount' => 10.00,
        'fee_amount' => 50.00,
        'net_amount' => 90.00,
        'trxn_id' => 12345,
        'invoice_id' => 67890,
        'source' => 'SSF',
        'contribution_status_id' => 1,
      ),
      'api.website.create' => array(
        array(
          'url' => "http://civicrm.org",
        ),
        array(
          'url' => "http://chained.org",
          'website_type_id' => 2,
        ),
      ),
    );

    $description = "demonstrates creating two websites as an array";
    $subfile     = "ChainTwoWebsitesSyntax2";
    $result      = $this->callAPIAndDocument('Contact', 'create', $params, __FUNCTION__, __FILE__, $description, $subfile);

    $this->assertEquals(1, $result['id']);
    // the callAndDocument doesn't check the chained call
    $this->assertEquals(0, $result['values'][$result['id']]['api.website.create'][0]['is_error'], "In line " . __LINE__);
    $this->assertEquals("http://chained.org", $result['values'][$result['id']]['api.website.create'][1]['values'][0]['url'], "In line " . __LINE__);
    $this->assertEquals("http://civicrm.org", $result['values'][$result['id']]['api.website.create'][0]['values'][0]['url'], "In line " . __LINE__);

    $this->callAPISuccess('contact', 'delete', $result);
  }

  /**
   *  Verify that attempt to create individual contact with first
   *  and last names and email succeeds
   */
  function testCreateIndividualWithNameEmail() {
    $params = array(
      'first_name' => 'abc3',
      'last_name' => 'xyz3',
      'contact_type' => 'Individual',
      'email' => 'man3@yahoo.com',
    );

    $contact = $this->callAPISuccess('contact', 'create', $params);
    $this->assertEquals(1, $contact['id'], "In line " . __LINE__);

    // delete the contact
    $this->callAPISuccess('contact', 'delete', $contact);
  }
  /**
   *  Verify that attempt to create individual contact with no data fails
   */
  function testCreateIndividualWithOutNameEmail() {
    $params = array(
      'contact_type' => 'Individual',
    );

    $result = $this->callAPIFailure('contact', 'create', $params);
  }
  /**
   *  Verify that attempt to create individual contact with first
   *  and last names, email and location type succeeds
   */
  function testCreateIndividualWithNameEmailLocationType() {
    $params = array(
      'first_name' => 'abc4',
      'last_name' => 'xyz4',
      'email' => 'man4@yahoo.com',
      'contact_type' => 'Individual',
      'location_type_id' => 1,
    );
    $result = $this->callAPISuccess('contact', 'create', $params);

    $this->assertEquals(1, $result['id'], "In line " . __LINE__);

    $this->callAPISuccess('contact', 'delete', array('id' => $result['id']));
  }

  /**
   * Verify that when changing employers
   * the old employer relationship becomes inactive
   */
  function testCreateIndividualWithEmployer() {
    $employer = $this->organizationCreate();
    $employer2 = $this->organizationCreate();

    $params = array(
        'email' => 'man4@yahoo.com',
        'contact_type' => 'Individual',
        'employer_id' => $employer,
    );

    $result = $this->callAPISuccess('contact', 'create', $params);
    $relationships = $this->callAPISuccess('relationship', 'get', array(
      'contact_id_a' => $result['id'],
      'sequential' => 1,
    ));

    $this->assertEquals($employer, $relationships['values'][0]['contact_id_b']);

    // Add more random relationships to make the test more realistic
    foreach (array('Employee of', 'Volunteer for') as $rtype) {
      $relTypeId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType', $rtype, 'id', 'name_a_b');
      $random_rel = $this->callAPISuccess('relationship', 'create', array(
        'contact_id_a' => $result['id'],
        'contact_id_b' => $this->organizationCreate(),
        'is_active' => 1,
        'relationship_type_id' => $relTypeId,
      ));
   }

    // Add second employer
    $params['employer_id'] = $employer2;
    $params['id'] = $result['id'];
    $result = $this->callAPISuccess('contact', 'create', $params);

    $relationships = $this->callAPISuccess('relationship', 'get', array(
      'contact_id_a' => $result['id'],
      'sequential' => 1,
      'is_active' => 0,
    ));

    $this->assertEquals($employer, $relationships['values'][0]['contact_id_b']);
  }

  /**
   *  Verify that attempt to create household contact with details
   *  succeeds
   */
  function testCreateHouseholdDetails() {
    $params = array(
      'household_name' => 'abc8\'s House',
      'nick_name' => 'x House',
      'email' => 'man8@yahoo.com',
      'contact_type' => 'Household',
    );

    $contact = $this->callAPISuccess('contact', 'create', $params);

    $this->assertEquals(1, $contact['id'], "In line " . __LINE__);

    $this->callAPISuccess('contact', 'delete', $contact);
  }
  /**
   *  Verify that attempt to create household contact with inadequate details
   *  fails
   */
  function testCreateHouseholdInadequateDetails() {
    $params = array(
      'nick_name' => 'x House',
      'email' => 'man8@yahoo.com',
      'contact_type' => 'Household',
    );

    $result = $this->callAPIFailure('contact', 'create', $params);
  }

  /**
   *  Verify successful update of individual contact
   */
  function testUpdateIndividualWithAll() {
    //  Insert a row in civicrm_contact creating individual contact
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/contact_ind.xml'
      )
    );

    $params = array(
      'id' => 23,
      'first_name' => 'abcd',
      'contact_type' => 'Individual',
      'nick_name' => 'This is nickname first',
      'do_not_email' => '1',
      'do_not_phone' => '1',
      'do_not_mail' => '1',
      'do_not_trade' => '1',
      'legal_identifier' => 'ABC23853ZZ2235',
      'external_identifier' => '1928837465',
      'image_URL' => 'http://some.url.com/image.jpg',
      'home_url' => 'http://www.example.org',

    );

    $result    = $this->callAPISuccess('Contact', 'Update', $params);
    $getResult = $this->callAPISuccess('Contact', 'Get', $params);
    unset($params['contact_id']);
    //Todo - neither API v2 or V3 are testing for home_url - not sure if it is being set.
    //reducing this test partially back to apiv2 level to get it through
    unset($params['home_url']);
    foreach ($params as $key => $value) {
      $this->assertEquals($value, $result['values'][23][$key], "In line " . __LINE__);
    }
    //  Check updated civicrm_contact against expected
    $expected = new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
      dirname(__FILE__) . '/dataset/contact_ind_upd.xml'
    );
    $actual = new PHPUnit_Extensions_Database_DataSet_QueryDataset(
      $this->_dbconn
    );
    $actual->addTable('civicrm_contact');
    $expected->matches($actual);
  }

  /**
   *  Verify successful update of organization contact
   */
  function testUpdateOrganizationWithAll() {
    //  Insert a row in civicrm_contact creating organization contact
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/contact_org.xml'
      )
    );

    $params = array(
      'id' => 24,
      'organization_name' => 'WebAccess India Pvt Ltd',
      'legal_name' => 'WebAccess',
      'sic_code' => 'ABC12DEF',
      'contact_type' => 'Organization',
    );

    $result = $this->callAPISuccess('Contact', 'Update', $params);

    //  Check updated civicrm_contact against expected
    $expected = new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
      dirname(__FILE__) . '/dataset/contact_org_upd.xml'
    );
    $actual = new PHPUnit_Extensions_Database_DataSet_QueryDataset(
      $this->_dbconn
    );
    $actual->addTable('civicrm_contact');
    $expected->matches($actual);
  }

  /**
   *  Verify successful update of household contact
   */
  function testUpdateHouseholdwithAll() {
    //  Insert a row in civicrm_contact creating household contact
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/contact_hld.xml'
      )
    );

    $params = array(
      'id' => 25,
      'household_name' => 'ABC household',
      'nick_name' => 'ABC House',
      'contact_type' => 'Household',
    );

    $result = $this->callAPISuccess('Contact', 'Update', $params);

    $expected = array(
      'contact_type' => 'Household',
      'is_opt_out' => 0,
      'sort_name' => 'ABC household',
      'display_name' => 'ABC household',
      'nick_name' => 'ABC House',
    );
    $this->getAndCheck($expected, $result['id'], 'contact');
  }

  /**
   *  Test civicrm_update() Deliberately exclude contact_type as it should still
   *  cope using civicrm_api CRM-7645
   */

  public function testUpdateCreateWithID() {
    //  Insert a row in civicrm_contact creating individual contact
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/contact_ind.xml'
      )
    );



    $params = array(
      'id' => 23,
      'first_name' => 'abcd',
      'last_name' => 'wxyz',
    );

    $result = $this->callAPISuccess('Contact', 'Update', $params);
  }

  /**
   *  Test civicrm_contact_delete() with no contact ID
   */
  function testContactDeleteNoID() {
    $params = array(
      'foo' => 'bar',
    );
    $result = $this->callAPIFailure('contact', 'delete', $params);
  }

  /**
   *  Test civicrm_contact_delete() with error
   */
  function testContactDeleteError() {
    $params = array('contact_id' => 999);
    $result = $this->callAPIFailure('contact', 'delete', $params);
  }

  /**
   *  Test civicrm_contact_delete()
   */
  function testContactDelete() {
    $contactID = $this->individualCreate();
    $params = array(
      'id' => $contactID ,
    );
    $result = $this->callAPIAndDocument('contact', 'delete', $params, __FUNCTION__, __FILE__);
  }

  /**
   *  Test civicrm_contact_get() return only first name
   */
  public function testContactGetRetFirst() {
    $contact = $this->callAPISuccess('contact', 'create', $this->_params);
    $params = array(
      'contact_id' => $contact['id'],
      'return_first_name' => TRUE,
      'sort' => 'first_name',
    );
    $result = $this->callAPISuccess('contact', 'get', $params);
    $this->assertEquals(1, $result['count'], "In line " . __LINE__);
    $this->assertEquals($contact['id'], $result['id'], "In line " . __LINE__);
    $this->assertEquals('abc1', $result['values'][$contact['id']]['first_name'], "In line " . __LINE__);
  }

  /**
   *  Test civicrm_contact_get() return only first name & last name
   *  Use comma separated string return with a space
   */
  public function testContactGetRetFirstLast() {
    $contact = $this->callAPISuccess('contact', 'create', $this->_params);
    $params = array(
      'contact_id' => $contact['id'],
      'return' => 'first_name, last_name',
    );
    $result = $this->callAPISuccess('contact', 'getsingle', $params);
    $this->assertEquals('abc1', $result['first_name'], "In line " . __LINE__);
    $this->assertEquals('xyz1', $result['last_name'], "In line " . __LINE__);
    //check that other defaults not returns
    $this->assertArrayNotHasKey('sort_name', $result);
    $params = array(
      'contact_id' => $contact['id'],
      'return' => 'first_name,last_name',
    );
    $result = $this->callAPISuccess('contact', 'getsingle', $params);
    $this->assertEquals('abc1', $result['first_name'], "In line " . __LINE__);
    $this->assertEquals('xyz1', $result['last_name'], "In line " . __LINE__);
    //check that other defaults not returns
    $this->assertArrayNotHasKey('sort_name', $result);
  }

  /**
   *  Test civicrm_contact_get() return only first name & last name
   *  Use comma separated string return without a space
   */
  public function testContactGetRetFirstLastNoComma() {
    $contact = $this->callAPISuccess('contact', 'create', $this->_params);
    $params = array(
      'contact_id' => $contact['id'],
      'return' => 'first_name,last_name',
    );
    $result = $this->callAPISuccess('contact', 'getsingle', $params);
    $this->assertEquals('abc1', $result['first_name'], "In line " . __LINE__);
    $this->assertEquals('xyz1', $result['last_name'], "In line " . __LINE__);
    //check that other defaults not returns
    $this->assertArrayNotHasKey('sort_name', $result);
  }

  /**
   *  Test civicrm_contact_get() with default return properties
   */
  public function testContactGetRetDefault() {
    $contactID = $this->individualCreate();
    $params = array(
      'contact_id' => $contactID,
      'sort' => 'first_name',
    );
    $result = $this->callAPISuccess('contact', 'get', $params);
    $this->assertEquals($contactID, $result['values'][$contactID]['contact_id']);
    $this->assertEquals('Anthony', $result['values'][$contactID]['first_name']);
  }

  /**
   *  Test civicrm_contact_getquick() with empty name param
   */
  public function testContactGetQuick() {
    //  Insert a row in civicrm_contact creating individual contact
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/contact_17.xml'
      )
    );
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/email_contact_17.xml'
      )
    );
    $params = array(
      'name' => "T",
    );

    $result = $this->callAPISuccess('contact', 'getquick', $params);
    $this->assertEquals(17, $result['values'][0]['id'], 'in line ' . __LINE__);
  }

  /**
   *  Test civicrm_contact_get) with empty params
   */
  public function testContactGetEmptyParams() {
    $result = $this->callAPISuccess('contact', 'get', array());
  }

  /**
   *  Test civicrm_contact_get(,true) with no matches
   */
  public function testContactGetOldParamsNoMatches() {
    //  Insert a row in civicrm_contact creating contact 17
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/contact_17.xml'
      )
    );

    $params = array(
      'first_name' => 'Fred',
    );
    $result = $this->callAPISuccess('contact', 'get', $params);
    $this->assertEquals(0, $result['count'], 'in line ' . __LINE__);
  }

  /**
   *  Test civicrm_contact_get(,true) with one match
   */
  public function testContactGetOldParamsOneMatch() {
    //  Insert a row in civicrm_contact creating contact 17
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(dirname(__FILE__) . '/dataset/contact_17.xml'
      )
    );

    $params = array(
      'first_name' => 'Test',
    );
    $result = $this->callAPISuccess('contact', 'get', $params);
    $this->assertEquals(17, $result['values'][17]['contact_id'], 'in line ' . __LINE__);
    $this->assertEquals(17, $result['id'], 'in line ' . __LINE__);
  }

  /**
   *  Test civicrm_contact_search_count()
   */
  public function testContactGetEmail() {
    $params = array(
      'email' => 'man2@yahoo.com',
      'contact_type' => 'Individual',
      'location_type_id' => 1,
    );

    $contact = $this->callAPISuccess('contact', 'create', $params);

    $this->assertEquals(1, $contact['id'], "In line " . __LINE__);

    $params = array(
      'email' => 'man2@yahoo.com',
    );
    $result = $this->callAPIAndDocument('contact', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['values'][1]['contact_id'], "In line " . __LINE__);
    $this->assertEquals('man2@yahoo.com', $result['values'][1]['email'], "In line " . __LINE__);

    // delete the contact
    $this->callAPISuccess('contact', 'delete', $contact);
  }

  /**
   * Test for Contact.get id=@user:username
   */
  function testContactGetByUsername() {
    // setup - create contact with a uf-match
    $cid = $this->individualCreate(array(
      'contact_type' => 'Individual',
      'first_name' => 'testGetByUsername',
      'last_name' => 'testGetByUsername',
    ));

    $ufMatchParams = array(
      'domain_id' => CRM_Core_Config::domainID(),
      'uf_id' => 99,
      'uf_name' => 'the-email-matching-key-is-not-really-the-username',
      'contact_id' => $cid,
    );
    $ufMatch = CRM_Core_BAO_UFMatch::create($ufMatchParams);
    $this->assertTrue(is_numeric($ufMatch->id));

    // setup - mock the calls to CRM_Utils_System_*::getUfId
    $userSystem = $this->getMock('CRM_Utils_System_UnitTests', array('getUfId'));
    $userSystem->expects($this->once())
      ->method('getUfId')
      ->with($this->equalTo('exampleUser'))
      ->will($this->returnValue(99));
    CRM_Core_Config::singleton()->userSystem = $userSystem;

    // perform a lookup
    $result = $this->callAPISuccess('Contact', 'get', array(
      'id' => '@user:exampleUser',
    ));
    $this->assertEquals('testGetByUsername', $result['values'][$cid]['first_name']);
  }

  /**
   * Test to check return works OK
   */
  function testContactGetReturnValues() {
    $extraParams = array('nick_name' => 'Bob', 'phone' => '456', 'email' => 'e@mail.com');
    $contactID = $this->individualCreate($extraParams);
    //actually it turns out the above doesn't create a phone
    $phones = $this->callAPISuccess('phone', 'create', array('contact_id' => $contactID, 'phone' => '456',));
    $result = $this->callAPISuccess('contact', 'getsingle', array('id' => $contactID));
    foreach ($extraParams as $key => $value) {
      $this->assertEquals($result[$key], $value);
    }
    //now we check they are still returned with 'return' key
    $result = $this->callAPISuccess('contact', 'getsingle', array('id' => $contactID, 'return' => array_keys($extraParams)));
    foreach ($extraParams as $key => $value) {
      $this->assertEquals($result[$key], $value);
    }
  }

  function testCRM13252MultipleChainedPhones() {
    $contactID = $this->householdCreate();
    $this->callAPISuccessGetCount('phone', array('contact_id' => $contactID), 0);
    $params = array(
     'contact_id' => $contactID,
     'household_name' => 'Household 1',
     'contact_type' => 'Household',
     'api.phone.create' => array(
        0 => array(
          'phone' => '111-111-1111',
          'location_type_id' => 1,
          'phone_type_id' => 1,
        ),
        1 => array(
          'phone' => '222-222-2222',
          'location_type_id' => 1,
          'phone_type_id' => 2,
        )
      )
    );
    $result = $this->callAPISuccess('contact', 'create', $params);
    $this->callAPISuccessGetCount('phone', array('contact_id' => $contactID), 2);

  }
  /**
   * Test for Contact.get id=@user:username (with an invalid username)
   */
  function testContactGetByUnknownUsername() {
    // setup - mock the calls to CRM_Utils_System_*::getUfId
    $userSystem = $this->getMock('CRM_Utils_System_UnitTests', array('getUfId'));
    $userSystem->expects($this->once())
      ->method('getUfId')
      ->with($this->equalTo('exampleUser'))
      ->will($this->returnValue(NULL));
    CRM_Core_Config::singleton()->userSystem = $userSystem;

    // perform a lookup
    $result = $this->callAPIFailure('Contact', 'get', array(
      'id' => '@user:exampleUser',
    ));
    $this->assertRegExp('/cannot be resolved to a contact ID/', $result['error_message']);
  }

  /**
   *  Verify attempt to create individual with chained arrays
   */
  function testGetIndividualWithChainedArrays() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $moreids     = $this->CustomGroupMultipleCreateWithFields();
    $description = "/*this demonstrates the usage of chained api functions. In this case no notes or custom fields have been created ";
    $subfile     = "APIChainedArray";
    $params      = array(
      'first_name' => 'abc3',
      'last_name' => 'xyz3',
      'contact_type' => 'Individual',
      'email' => 'man3@yahoo.com',
      'api.contribution.create' => array(
        'receive_date' => '2010-01-01',
        'total_amount' => 100.00,
        'financial_type_id' => 1,
        'payment_instrument_id' => 1,
        'non_deductible_amount' => 10.00,
        'fee_amount' => 50.00,
        'net_amount' => 90.00,
        'trxn_id' => 12345,
        'invoice_id' => 67890,
        'source' => 'SSF',
        'contribution_status_id' => 1,
      ),
      'api.contribution.create.1' => array(
        'receive_date' => '2011-01-01',
        'total_amount' => 120.00,
        'financial_type_id' => $this->_financialTypeId =1,
        'payment_instrument_id' => 1,
        'non_deductible_amount' => 10.00,
        'fee_amount' => 50.00,
        'net_amount' => 90.00,
        'trxn_id' => 12335,
        'invoice_id' => 67830,
        'source' => 'SSF',
        'contribution_status_id' => 1,
      ),
      'api.website.create' => array(
        array(
          'url' => "http://civicrm.org",
        ),
      ),
    );

    $result = $this->callAPISuccess('Contact', 'create', $params);
    $params = array(
      'id' => $result['id'],
      'api.website.get' => array(),
      'api.Contribution.get' => array(
        'total_amount' => '120.00',
      ), 'api.CustomValue.get' => 1,
      'api.Note.get' => 1,
    );
    $result = $this->callAPIAndDocument('Contact', 'Get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    // delete the contact
    $this->callAPISuccess('contact', 'delete', $result);
    $this->customGroupDelete($ids['custom_group_id']);
    $this->customGroupDelete($moreids['custom_group_id']);
    $this->assertEquals(1, $result['id'], "In line " . __LINE__);
    $this->assertEquals(0, $result['values'][$result['id']]['api.website.get']['is_error'], "In line " . __LINE__);
    $this->assertEquals("http://civicrm.org", $result['values'][$result['id']]['api.website.get']['values'][0]['url'], "In line " . __LINE__);
  }

  function testGetIndividualWithChainedArraysFormats() {
    $description = "/*this demonstrates the usage of chained api functions. A variety of return formats are used. Note that no notes
    *custom fields or memberships exist";
    $subfile = "APIChainedArrayFormats";
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $moreids = $this->CustomGroupMultipleCreateWithFields();
    $params = array(
      'first_name' => 'abc3',
      'last_name' => 'xyz3',
      'contact_type' => 'Individual',
      'email' => 'man3@yahoo.com',
      'api.contribution.create' => array(
        'receive_date' => '2010-01-01',
        'total_amount' => 100.00,
        'financial_type_id' => $this->_financialTypeId,
        'payment_instrument_id' => 1,
        'non_deductible_amount' => 10.00,
        'fee_amount' => 50.00,
        'net_amount' => 90.00,
        'source' => 'SSF',
        'contribution_status_id' => 1,
      ),
      'api.contribution.create.1' => array(
        'receive_date' => '2011-01-01',
        'total_amount' => 120.00,
        'financial_type_id' => $this->_financialTypeId,
        'payment_instrument_id' => 1,
        'non_deductible_amount' => 10.00,
        'fee_amount' => 50.00,
        'net_amount' => 90.00,
        'source' => 'SSF',
        'contribution_status_id' => 1,
      ),
      'api.website.create' => array(
        array(
          'url' => "http://civicrm.org",
        ),
      ),
    );


    $result = $this->callAPISuccess('Contact', 'create', $params);
    $params = array(
      'id' => $result['id'],
      'api.website.getValue' => array('return' => 'url'),
      'api.Contribution.getCount' => array(),
      'api.CustomValue.get' => 1,
      'api.Note.get' => 1,
      'api.Membership.getCount' => array(),
    );
    $result = $this->callAPIAndDocument('Contact', 'Get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(1, $result['id'], "In line " . __LINE__);
    $this->assertEquals(2, $result['values'][$result['id']]['api.Contribution.getCount'], "In line " . __LINE__);
    $this->assertEquals(0, $result['values'][$result['id']]['api.Note.get']['is_error'], "In line " . __LINE__);
    $this->assertEquals("http://civicrm.org", $result['values'][$result['id']]['api.website.getValue'], "In line " . __LINE__);

    $this->callAPISuccess('contact', 'delete', $result);
    $this->customGroupDelete($ids['custom_group_id']);
    $this->customGroupDelete($moreids['custom_group_id']);
  }

  function testGetIndividualWithChainedArraysAndMultipleCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $params['custom_' . $ids['custom_field_id']] = "custom string";
    $moreids = $this->CustomGroupMultipleCreateWithFields();
    $andmoreids = $this->CustomGroupMultipleCreateWithFields(array('title' => "another group", 'name' => 'another name'));
    $description = "/*this demonstrates the usage of chained api functions. A variety of techniques are used";
    $subfile = "APIChainedArrayMultipleCustom";
    $params = array(
      'first_name' => 'abc3',
      'last_name' => 'xyz3',
      'contact_type' => 'Individual',
      'email' => 'man3@yahoo.com',
      'api.contribution.create' => array(
        'receive_date' => '2010-01-01',
        'total_amount' => 100.00,
        'financial_type_id'   => 1,
        'payment_instrument_id' => 1,
        'non_deductible_amount' => 10.00,
        'fee_amount' => 50.00,
        'net_amount' => 90.00,
        'trxn_id' => 12345,
        'invoice_id' => 67890,
        'source' => 'SSF',
        'contribution_status_id' => 1,
      ),
      'api.contribution.create.1' => array(
        'receive_date' => '2011-01-01',
        'total_amount' => 120.00,
        'financial_type_id'   => 1,
        'payment_instrument_id' => 1,
        'non_deductible_amount' => 10.00,
        'fee_amount' => 50.00,
        'net_amount' => 90.00,
        'trxn_id' => 12335,
        'invoice_id' => 67830,
        'source' => 'SSF',
        'contribution_status_id' => 1,
      ),
      'api.website.create' => array(
        array(
          'url' => "http://civicrm.org",
        ),
      ),
      'custom_' . $ids['custom_field_id'] => "value 1",
      'custom_' . $moreids['custom_field_id'][0] => "value 2",
      'custom_' . $moreids['custom_field_id'][1] => "warm beer",
      'custom_' . $andmoreids['custom_field_id'][1] => "vegemite",
    );


    $result = $this->callAPISuccess('Contact', 'create', $params);
    $result = $this->callAPISuccess('Contact', 'create', array(
      'contact_type' => 'Individual', 'id' => $result['id'], 'custom_' . $moreids['custom_field_id'][0] => "value 3", 'custom_' . $ids['custom_field_id'] => "value 4",
      ));

    $params = array(
      'id' => $result['id'],
      'api.website.getValue' => array('return' => 'url'),
      'api.Contribution.getCount' => array(),
      'api.CustomValue.get' => 1,
    );
    $result = $this->callAPIAndDocument('Contact', 'Get', $params, __FUNCTION__, __FILE__, $description, $subfile);

    $this->customGroupDelete($ids['custom_group_id']);
    $this->customGroupDelete($moreids['custom_group_id']);
    $this->customGroupDelete($andmoreids['custom_group_id']);
    $this->assertEquals(1, $result['id'], "In line " . __LINE__);
    $this->assertEquals(0, $result['values'][$result['id']]['api.CustomValue.get']['is_error'], "In line " . __LINE__);
    $this->assertEquals('http://civicrm.org', $result['values'][$result['id']]['api.website.getValue'], "In line " . __LINE__);
  }
  /*
   * Test checks siusage of $values to pick & choose inputs
   */
  function testChainingValuesCreate() {
    $description = "/*this demonstrates the usage of chained api functions.  Specifically it has one 'parent function' &
    2 child functions - one receives values from the parent (Contact) and the other child (Tag). ";
    $subfile = "APIChainedArrayValuesFromSiblingFunction";
    $params = array(
      'display_name' => 'batman', 'contact_type' => 'Individual',
      'api.tag.create' => array('name' => '$value.id', 'description' => '$value.display_name', 'format.only_id' => 1),
      'api.entity_tag.create' => array('tag_id' => '$value.api.tag.create'),
    );
    $result = $this->callAPIAndDocument('Contact', 'Create', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(0, $result['values'][$result['id']]['api.entity_tag.create']['is_error']);

    $tablesToTruncate = array(
      'civicrm_contact',
      'civicrm_activity',
      'civicrm_entity_tag',
      'civicrm_tag',
    );
    $this->quickCleanup($tablesToTruncate, TRUE);
  }

  /*
   * test TrueFalse format - I couldn't come up with an easy way to get an error on Get
   */
  function testContactGetFormatIsSuccessTrue() {
    $this->createContactFromXML();
    $description = "This demonstrates use of the 'format.is_success' param.
    This param causes only the success or otherwise of the function to be returned as BOOLEAN";
    $subfile = "FormatIsSuccess_True";
    $params  = array('id' => 17, 'format.is_success' => 1);
    $result  = $this->callAPIAndDocument('Contact', 'Get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(1, $result);
    $this->callAPISuccess('Contact', 'Delete', $params);
  }
  /*
   * test TrueFalse format
   */
  function testContactCreateFormatIsSuccessFalse() {

    $description = "This demonstrates use of the 'format.is_success' param.
    This param causes only the success or otherwise of the function to be returned as BOOLEAN";
    $subfile = "FormatIsSuccess_Fail";
    $params  = array('id' => 500, 'format.is_success' => 1);
    $result  = $this->callAPIAndDocument('Contact', 'Create', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(0, $result);
  }
  /*
   * test Single Entity format
   */
  function testContactGetSingle_entity_array() {
    $this->createContactFromXML();
    $description = "This demonstrates use of the 'format.single_entity_array' param.
    /* This param causes the only contact to be returned as an array without the other levels.
    /* it will be ignored if there is not exactly 1 result";
    $subfile = "GetSingleContact";
    $params  = array('id' => 17);
    $result  = $this->callAPIAndDocument('Contact', 'GetSingle', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals('Test Contact', $result['display_name'], "in line " . __LINE__);
    $this->callAPISuccess('Contact', 'Delete', $params);
  }

  /*
   * test Single Entity format
   */
  function testContactGetFormatcount_only() {
    $this->createContactFromXML();
    $description = "/*This demonstrates use of the 'getCount' action
    /*  This param causes the count of the only function to be returned as an integer";
    $subfile = "GetCountContact";
    $params  = array('id' => 17);
    $result  = $this->callAPIAndDocument('Contact', 'GetCount', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals('1', $result, "in line " . __LINE__);
    $this->callAPISuccess('Contact', 'Delete', $params);
  }
  /*
    * Test id only format
    */
  function testContactGetFormatID_only() {
    $this->createContactFromXML();
    $description = "This demonstrates use of the 'format.id_only' param.
    /* This param causes the id of the only entity to be returned as an integer.
    /* it will be ignored if there is not exactly 1 result";
    $subfile = "FormatOnlyID";
    $params  = array('id' => 17, 'format.only_id' => 1);
    $result  = $this->callAPIAndDocument('Contact', 'Get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals('17', $result, "in line " . __LINE__);
    $this->callAPISuccess('Contact', 'Delete', $params);
  }

  /*
    * Test id only format
    */
  function testContactGetFormatSingleValue() {
    $this->createContactFromXML();
    $description = "This demonstrates use of the 'format.single_value' param.
    /* This param causes only a single value of the only entity to be returned as an string.
    /* it will be ignored if there is not exactly 1 result";
    $subfile = "FormatSingleValue";
    $params  = array('id' => 17, 'return' => 'display_name');
    $result  = $this->callAPIAndDocument('Contact', 'getvalue', $params, __FUNCTION__, __FILE__, $description, $subfile,'getvalue');
    $this->assertEquals('Test Contact', $result, "in line " . __LINE__);
    $this->callAPISuccess('Contact', 'Delete', $params);
  }

  function testContactCreationPermissions() {
    $params = array(
      'contact_type' => 'Individual', 'first_name' => 'Foo',
      'last_name' => 'Bear',
      'check_permissions' => TRUE,
    );
    $config = CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = array('access CiviCRM');
    $result = $this->callAPIFailure('contact', 'create', $params);
    $this->assertEquals('API permission check failed for contact/create call; insufficient permission: require access CiviCRM and add contacts', $result['error_message'], 'lacking permissions should not be enough to create a contact');

    $config->userPermissionClass->permissions = array('access CiviCRM', 'add contacts', 'import contacts');
    $result = $this->callAPISuccess('contact', 'create', $params, NULL, 'overfluous permissions should be enough to create a contact');
  }

  function testContactUpdatePermissions() {
    $params = array('contact_type' => 'Individual', 'first_name' => 'Foo', 'last_name' => 'Bear', 'check_permissions' => TRUE,);
    $result = $this->callAPISuccess('contact', 'create', $params);
    $config = CRM_Core_Config::singleton();
    $params = array('id' => $result['id'], 'contact_type' => 'Individual', 'last_name' => 'Bar', 'check_permissions' => TRUE,);

    $config->userPermissionClass->permissions = array('access CiviCRM');
    $result = $this->callAPIFailure('contact', 'update', $params);
    $this->assertEquals('API permission check failed for contact/update call; insufficient permission: require access CiviCRM and edit all contacts', $result['error_message'], 'lacking permissions should not be enough to update a contact');

    $config->userPermissionClass->permissions = array('access CiviCRM', 'add contacts', 'view all contacts', 'edit all contacts', 'import contacts');
    $result = $this->callAPISuccess('contact', 'update', $params, NULL, 'overfluous permissions should be enough to update a contact');
  }

  function createContactFromXML() {
    //  Insert a row in civicrm_contact creating contact 17
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_XMLDataSet(
        dirname(__FILE__) . '/dataset/contact_17.xml'
      )
    );
  }

  function testContactProximity() {
    // first create a contact with a SF location with a specific
    // geocode
    $contactID = $this->organizationCreate();

    // now create the address
    $params = array(
      'street_address' => '123 Main Street',
      'city' => 'San Francisco',
      'is_primary' => 1,
      'country_id' => 1228,
      'state_province_id' => 1004,
      'geo_code_1' => '37.79',
      'geo_code_2' => '-122.40',
      'location_type_id' => 1,
      'contact_id' => $contactID,
    );

    $result = $this->callAPISuccess('address', 'create', $params);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);

    // now do a proximity search with a close enough geocode and hope to match
    // that specific contact only!
    $proxParams = array(
      'latitude' => 37.7,
      'longitude' => -122.3,
      'unit' => 'mile',
      'distance' => 10,
    );
    $result = $this->callAPISuccess('contact', 'proximity', $proxParams);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
  }

  /**
   * Test that Ajax API permission is suffient to access getquick api
   * (note that getquick api is required for autocomplete & has ACL permissions applied)
   */
  function testgetquickPermission_CRM_13744() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviEvent');
    $result = $this->callAPIFailure('contact', 'getquick', array('name' => 'b', 'check_permissions' => TRUE));
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM');
    $result = $this->callAPISuccess('contact', 'getquick', array('name' => 'b', 'check_permissions' => TRUE));
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access AJAX API');
    $result = $this->callAPISuccess('contact', 'getquick', array('name' => 'b', 'check_permissions' => TRUE));
  }
}
