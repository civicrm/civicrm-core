<?php
/**
 * @file
 * File for the TestContact class.
 *
 *  (PHP 5)
 *
 * @author Walt Haas <walt@dharmatech.org> (801) 534-1262
 * @copyright Copyright CiviCRM LLC (C) 2009
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU Affero General Public License version 3
 * @version   $Id: ContactTest.php 31254 2010-12-15 10:09:29Z eileen $
 * @package   CiviCRM
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
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 */
class api_v3_ContactTest extends CiviUnitTestCase {
  public $DBResetRequired = FALSE;
  protected $_apiversion;
  protected $_entity;
  protected $_params;

  protected $_contactID;
  protected $_financialTypeId = 1;

  /**
   * Test setup for every test.
   *
   * Connect to the database, truncate the tables that will be used
   * and redirect stdin to a temporary file
   */
  public function setUp() {
    // Connect to the database.
    parent::setUp();
    $this->_apiversion = 3;
    $this->_entity = 'contact';
    $this->_params = array(
      'first_name' => 'abc1',
      'contact_type' => 'Individual',
      'last_name' => 'xyz1',
    );
  }

  public function tearDown() {
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
   * Test civicrm_contact_create.
   *
   * Verify that attempt to create individual contact with only
   * first and last names succeeds
   */
  public function testAddCreateIndividual() {
    $oldCount = CRM_Core_DAO::singleValueQuery('select count(*) from civicrm_contact');
    $params = array(
      'first_name' => 'abc1',
      'contact_type' => 'Individual',
      'last_name' => 'xyz1',
    );

    $contact = $this->callAPISuccess('contact', 'create', $params);
    $this->assertTrue(is_numeric($contact['id']));
    $this->assertTrue($contact['id'] > 0);
    $newCount = CRM_Core_DAO::singleValueQuery('select count(*) from civicrm_contact');
    $this->assertEquals($oldCount + 1, $newCount);

    $this->assertDBState('CRM_Contact_DAO_Contact',
      $contact['id'],
      $params
    );
  }

  /**
   * Test civicrm_contact_create with sub-types.
   *
   * Verify that sub-types are created successfully and not deleted by subsequent updates.
   */
  public function testIndividualSubType() {
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

    $this->assertEquals(array('Student', 'Staff'), $contact['values'][$cid]['contact_sub_type']);
  }

  /**
   * Verify that attempt to create contact with empty params fails.
   */
  public function testCreateEmptyContact() {
    $this->callAPIFailure('contact', 'create', array());
  }

  /**
   * Verify that attempt to create contact with bad contact type fails.
   */
  public function testCreateBadTypeContact() {
    $params = array(
      'email' => 'man1@yahoo.com',
      'contact_type' => 'Does not Exist',
    );
    $this->callAPIFailure('contact', 'create', $params, "'Does not Exist' is not a valid option for field contact_type");
  }

  /**
   * Verify that attempt to create individual contact without required fields fails.
   */
  public function testCreateBadRequiredFieldsIndividual() {
    $params = array(
      'middle_name' => 'This field is not required',
      'contact_type' => 'Individual',
    );
    $this->callAPIFailure('contact', 'create', $params);
  }

  /**
   * Verify that attempt to create household contact without required fields fails.
   */
  public function testCreateBadRequiredFieldsHousehold() {
    $params = array(
      'middle_name' => 'This field is not required',
      'contact_type' => 'Household',
    );
    $this->callAPIFailure('contact', 'create', $params);
  }

  /**
   * Test required field check.
   *
   * Verify that attempt to create organization contact without required fields fails.
   */
  public function testCreateBadRequiredFieldsOrganization() {
    $params = array(
      'middle_name' => 'This field is not required',
      'contact_type' => 'Organization',
    );

    $this->callAPIFailure('contact', 'create', $params);
  }

  /**
   * Verify that attempt to create individual contact with only an email succeeds.
   */
  public function testCreateEmailIndividual() {

    $params = array(
      'email' => 'man3@yahoo.com',
      'contact_type' => 'Individual',
      'location_type_id' => 1,
    );

    $contact = $this->callAPISuccess('contact', 'create', $params);

    $this->assertEquals(1, $contact['id']);
    $email = $this->callAPISuccess('email', 'get', array('contact_id' => $contact['id']));
    $this->assertEquals(1, $email['count']);
    $this->assertEquals('man3@yahoo.com', $email['values'][$email['id']]['email']);

    $this->callAPISuccess('contact', 'delete', $contact);
  }

  /**
   * Test creating individual by name.
   *
   * Verify create individual contact with only first and last names succeeds.
   */
  public function testCreateNameIndividual() {
    $params = array(
      'first_name' => 'abc1',
      'contact_type' => 'Individual',
      'last_name' => 'xyz1',
    );

    $contact = $this->callAPISuccess('contact', 'create', $params);
    $this->assertEquals(1, $contact['id']);
  }

  /**
   * Test old keys still work.
   *
   * Verify that attempt to create individual contact with
   * first and last names and old key values works
   */
  public function testCreateNameIndividualOldKeys() {
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
   * Test preferred keys work.
   *
   * Verify that attempt to create individual contact with
   * first and last names and old key values works
   */
  public function testCreateNameIndividualRecommendedKeys2() {
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
   * Test household name is sufficient for create.
   *
   * Verify that attempt to create household contact with only
   * household name succeeds
   */
  public function testCreateNameHousehold() {
    $params = array(
      'household_name' => 'The abc Household',
      'contact_type' => 'Household',
    );
    $contact = $this->callAPISuccess('contact', 'create', $params);
    $this->assertEquals(1, $contact['id']);
  }

  /**
   * Test organization name is sufficient for create.
   *
   * Verify that attempt to create organization contact with only
   * organization name succeeds.
   */
  public function testCreateNameOrganization() {
    $params = array(
      'organization_name' => 'The abc Organization',
      'contact_type' => 'Organization',
    );
    $contact = $this->callAPISuccess('contact', 'create', $params);
    $this->assertEquals(1, $contact['id']);
  }

  /**
   * Verify that attempt to create organization contact without organization name fails.
   */
  public function testCreateNoNameOrganization() {
    $params = array(
      'first_name' => 'The abc Organization',
      'contact_type' => 'Organization',
    );
    $this->callAPIFailure('contact', 'create', $params);
  }

  /**
   * Check with complete array + custom field.
   *
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   */
  public function testCreateWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";
    $description = "This demonstrates setting a custom field through the API.";
    $result = $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__, $description);

    $check = $this->callAPISuccess($this->_entity, 'get', array(
      'return.custom_' . $ids['custom_field_id'] => 1,
      'id' => $result['id'],
    ));
    $this->assertEquals("custom string", $check['values'][$check['id']]['custom_' . $ids['custom_field_id']]);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   * CRM-12773 - expectation is that civicrm quietly ignores fields without values.
   */
  public function testCreateWithNULLCustomCRM12773() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = NULL;
    $this->callAPISuccess('contact', 'create', $params);
    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   * CRM-15792 - create/update datetime field for contact.
   */
  public function testCreateContactCustomFldDateTime() {
    $customGroup = $this->customGroupCreate(array('extends' => 'Individual', 'title' => 'datetime_test_group'));
    $dateTime = CRM_Utils_Date::currentDBDate();
    //check date custom field is saved along with time when time_format is set
    $params = array(
      'first_name' => 'abc3',
      'last_name' => 'xyz3',
      'contact_type' => 'Individual',
      'email' => 'man3@yahoo.com',
      'api.CustomField.create' => array(
        'custom_group_id' => $customGroup['id'],
        'name' => 'test_datetime',
        'label' => 'Demo Date',
        'html_type' => 'Select Date',
        'data_type' => 'Date',
        'time_format' => 2,
        'weight' => 4,
        'is_required' => 1,
        'is_searchable' => 0,
        'is_active' => 1,
      ),
    );

    $result = $this->callAPIAndDocument('Contact', 'create', $params, __FUNCTION__, __FILE__);
    $customFldId = $result['values'][$result['id']]['api.CustomField.create']['id'];
    $this->assertNotNull($result['id']);
    $this->assertNotNull($customFldId);

    $params = array(
      'id' => $result['id'],
      "custom_{$customFldId}" => $dateTime,
      'api.CustomValue.get' => 1,
    );

    $result = $this->callAPIAndDocument('Contact', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['id']);
    $customFldDate = date("YmdHis", strtotime($result['values'][$result['id']]['api.CustomValue.get']['values'][0]['latest']));
    $this->assertNotNull($customFldDate);
    $this->assertEquals($dateTime, $customFldDate);
    $customValueId = $result['values'][$result['id']]['api.CustomValue.get']['values'][0]['id'];
    $dateTime = date('Ymd');
    //date custom field should not contain time part when time_format is null
    $params = array(
      'id' => $result['id'],
      'api.CustomField.create' => array(
        'id' => $customFldId,
        'html_type' => 'Select Date',
        'data_type' => 'Date',
        'time_format' => '',
      ),
      'api.CustomValue.create' => array(
        'id' => $customValueId,
        'entity_id' => $result['id'],
        "custom_{$customFldId}" => $dateTime,
      ),
      'api.CustomValue.get' => 1,
    );
    $result = $this->callAPIAndDocument('Contact', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['id']);
    $customFldDate = date("Ymd", strtotime($result['values'][$result['id']]['api.CustomValue.get']['values'][0]['latest']));
    $customFldTime = date("His", strtotime($result['values'][$result['id']]['api.CustomValue.get']['values'][0]['latest']));
    $this->assertNotNull($customFldDate);
    $this->assertEquals($dateTime, $customFldDate);
    $this->assertEquals(000000, $customFldTime);
    $result = $this->callAPIAndDocument('Contact', 'create', $params, __FUNCTION__, __FILE__);
  }


  /**
   * Test creating a current employer through API.
   */
  public function testContactCreateCurrentEmployer() {
    // Here we will just do the get for set-up purposes.
    $count = $this->callAPISuccess('contact', 'getcount', array(
      'organization_name' => 'new employer org',
      'contact_type' => 'Organization',
    ));
    $this->assertEquals(0, $count);
    $employerResult = $this->callAPISuccess('contact', 'create', array_merge($this->_params, array(
        'current_employer' => 'new employer org',
      )
    ));
    // do it again as an update to check it doesn't cause an error
    $employerResult = $this->callAPISuccess('contact', 'create', array_merge($this->_params, array(
        'current_employer' => 'new employer org',
        'id' => $employerResult['id'],
      )
    ));
    $expectedCount = 1;
    $this->callAPISuccess('contact', 'getcount', array(
        'organization_name' => 'new employer org',
        'contact_type' => 'Organization',
      ),
      $expectedCount);

    $result = $this->callAPISuccess('contact', 'getsingle', array(
      'id' => $employerResult['id'],
    ));

    $this->assertEquals('new employer org', $result['current_employer']);

  }

  /**
   * Test creating a current employer through API.
   *
   * Check it will re-activate a de-activated employer
   */
  public function testContactCreateDuplicateCurrentEmployerEnables() {
    // Set up  - create employer relationship.
    $employerResult = $this->callAPISuccess('contact', 'create', array_merge($this->_params, array(
        'current_employer' => 'new employer org',
      )
    ));
    $relationship = $this->callAPISuccess('relationship', 'get', array(
      'contact_id_a' => $employerResult['id'],
    ));

    //disable & check it is disabled
    $this->callAPISuccess('relationship', 'create', array('id' => $relationship['id'], 'is_active' => 0));
    $this->callAPISuccess('relationship', 'getvalue', array(
      'id' => $relationship['id'],
      'return' => 'is_active',
    ), 0);

    // Re-set the current employer - thus enabling the relationship.
    $this->callAPISuccess('contact', 'create', array_merge($this->_params, array(
        'current_employer' => 'new employer org',
        'id' => $employerResult['id'],
      )
    ));
    //check is_active is now 1
    $relationship = $this->callAPISuccess('relationship', 'getsingle', array(
      'return' => 'is_active',
    ));
    $this->assertEquals(1, $relationship['is_active']);
  }

  /**
   * Check deceased contacts are not retrieved.
   *
   * Note at time of writing the default is to return default. This should possibly be changed & test added.
   */
  public function testGetDeceasedRetrieved() {
    $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $c2 = $this->callAPISuccess($this->_entity, 'create', array(
      'first_name' => 'bb',
      'last_name' => 'ccc',
      'contact_type' => 'Individual',
      'is_deceased' => 1,
    ));
    $result = $this->callAPISuccess($this->_entity, 'get', array('is_deceased' => 0));
    $this->assertFalse(array_key_exists($c2['id'], $result['values']));
  }

  /**
   * Test that sort works - old syntax.
   */
  public function testGetSort() {
    $c1 = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $c2 = $this->callAPISuccess($this->_entity, 'create', array(
      'first_name' => 'bb',
      'last_name' => 'ccc',
      'contact_type' => 'Individual',
    ));
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

  /**
   * Test that we can retrieve contacts using array syntax.
   *
   * I.e 'id' => array('IN' => array('3,4')).
   */
  public function testGetINIDArray() {
    $c1 = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $c2 = $this->callAPISuccess($this->_entity, 'create', array(
      'first_name' => 'bb',
      'last_name' => 'ccc',
      'contact_type' => 'Individual',
    ));
    $c3 = $this->callAPISuccess($this->_entity, 'create', array(
      'first_name' => 'hh',
      'last_name' => 'll',
      'contact_type' => 'Individual',
    ));
    $result = $this->callAPISuccess($this->_entity, 'get', array('id' => array('IN' => array($c1['id'], $c3['id']))));
    $this->assertEquals(2, $result['count']);
    $this->assertEquals(array($c1['id'], $c3['id']), array_keys($result['values']));
    $this->callAPISuccess($this->_entity, 'delete', array('id' => $c1['id']));
    $this->callAPISuccess($this->_entity, 'delete', array('id' => $c2['id']));
    $this->callAPISuccess($this->_entity, 'delete', array('id' => $c3['id']));
  }

  /**
   * Test variants on deleted behaviour.
   */
  public function testGetDeleted() {
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
    $this->assertEquals(1, $countActive);
    $this->assertEquals(1, $countTrash);
    $this->assertEquals(2, $countAll);
    $this->assertEquals(1, $countDeleted);
    $this->assertEquals(1, $countDefault, 'Only active by default in line ' . __LINE__);
  }

  /**
   * Test that sort works - new syntax.
   */
  public function testGetSortNewSyntax() {
    $c1 = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $c2 = $this->callAPISuccess($this->_entity, 'create', array(
      'first_name' => 'bb',
      'last_name' => 'ccc',
      'contact_type' => 'Individual',
    ));
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

  /**
   * Test sort and limit for chained relationship get.
   *
   * https://issues.civicrm.org/jira/browse/CRM-15983
   */
  public function testSortLimitChainedRelationshipGetCRM15983() {
    // Some contact
    $create_result_1 = $this->callAPISuccess('contact', 'create', array(
      'first_name' => 'Jules',
      'last_name' => 'Smos',
      'contact_type' => 'Individual',
    ));

    // Create another contact with two relationships.
    $create_params = array(
      'first_name' => 'Jos',
      'last_name' => 'Smos',
      'contact_type' => 'Individual',
      'api.relationship.create' => array(
        array(
          'contact_id_a' => '$value.id',
          'contact_id_b' => $create_result_1['id'],
          // spouse of:
          'relationship_type_id' => 2,
          'start_date' => '2005-01-12',
          'end_date' => '2006-01-11',
          'description' => 'old',
        ),
        array(
          'contact_id_a' => '$value.id',
          'contact_id_b' => $create_result_1['id'],
          // spouse of (was married twice :))
          'relationship_type_id' => 2,
          'start_date' => '2006-07-01',
          'end_date' => '2010-07-01',
          'description' => 'new',
        ),
      ),
    );
    $create_result = $this->callAPISuccess('contact', 'create', $create_params);

    // Try to retrieve the contact and the most recent relationship.
    $get_params = array(
      'sequential' => 1,
      'id' => $create_result['id'],
      'api.relationship.get' => array(
        'contact_id_a' => '$value.id',
        'options' => array(
          'limit' => '1',
          'sort' => 'start_date DESC',
        )),
    );
    $get_result = $this->callAPISuccess('contact', 'getsingle', $get_params);

    // Clean up.
    $this->callAPISuccess('contact', 'delete', array(
      'id' => $create_result['id'],
    ));

    // Assert.
    $this->assertEquals(1, $get_result['api.relationship.get']['count']);
    $this->assertEquals('new', $get_result['api.relationship.get']['values'][0]['description']);
  }

  /**
   * Test apostrophe works in get & create.
   */
  public function testGetApostropheCRM10857() {
    $params = array_merge($this->_params, array('last_name' => "O'Connor"));
    $this->callAPISuccess($this->_entity, 'create', $params);
    $result = $this->callAPISuccess($this->_entity, 'getsingle', array(
      'last_name' => "O'Connor",
      'sequential' => 1,
    ));
    $this->assertEquals("O'Connor", $result['last_name'], 'in line' . __LINE__);
  }

  /**
   * Check with complete array + custom field.
   *
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   */
  public function testGetWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";
    $description = "This demonstrates setting a custom field through the API.";
    $subfile = "CustomFieldGet";
    $result = $this->callAPISuccess($this->_entity, 'create', $params);

    $check = $this->callAPIAndDocument($this->_entity, 'get', array(
      'return.custom_' . $ids['custom_field_id'] => 1,
      'id' => $result['id'],
    ), __FUNCTION__, __FILE__, $description, $subfile);

    $this->assertEquals("custom string", $check['values'][$check['id']]['custom_' . $ids['custom_field_id']]);
    $fields = ($this->callAPISuccess('contact', 'getfields', $params));
    $this->assertTrue(is_array($fields['values']['custom_' . $ids['custom_field_id']]));
    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   * Check with complete array + custom field.
   *
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   */
  public function testGetWithCustomReturnSyntax() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";
    $description = "This demonstrates setting a custom field through the API.";
    $subfile = "CustomFieldGetReturnSyntaxVariation";
    $result = $this->callAPISuccess($this->_entity, 'create', $params);
    $params = array('return' => 'custom_' . $ids['custom_field_id'], 'id' => $result['id']);
    $check = $this->callAPIAndDocument($this->_entity, 'get', $params, __FUNCTION__, __FILE__, $description, $subfile);

    $this->assertEquals("custom string", $check['values'][$check['id']]['custom_' . $ids['custom_field_id']]);
    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   * Check that address name is returned if required.
   */
  public function testGetReturnAddressName() {
    $contactID = $this->individualCreate();
    $this->callAPISuccess('address', 'create', array(
      'contact_id' => $contactID,
      'address_name' => 'My house',
      'location_type_id' => 'Home',
      'street_address' => '1 my road',
    ));
    $result = $this->callAPISuccessGetSingle('contact', array(
      'return' => 'address_name, street_address',
      'id' => $contactID,
    ));
    $this->assertEquals('1 my road', $result['street_address']);
    $this->assertEquals('My house', $result['address_name']);

  }

  public function testGetGroupIDFromContact() {
    $groupId = $this->groupCreate();
    $description = "Get all from group and display contacts.";
    $subFile = "GroupFilterUsingContactAPI";
    $params = array(
      'email' => 'man2@yahoo.com',
      'contact_type' => 'Individual',
      'location_type_id' => 1,
      'api.group_contact.create' => array('group_id' => $groupId),
    );

    $this->callAPISuccess('contact', 'create', $params);
    // testing as integer
    $params = array(
      'filter.group_id' => $groupId,
      'contact_type' => 'Individual',
    );
    $result = $this->callAPIAndDocument('contact', 'get', $params, __FUNCTION__, __FILE__, $description, $subFile);
    $this->assertEquals(1, $result['count']);
    // group 26 doesn't exist, but we can still search contacts in it.
    $params = array(
      'filter.group_id' => 26,
      'contact_type' => 'Individual',
    );
    $this->callAPISuccess('contact', 'get', $params);
    // testing as string
    $params = array(
      'filter.group_id' => "$groupId, 26",
      'contact_type' => 'Individual',
    );
    $result = $this->callAPIAndDocument('contact', 'get', $params, __FUNCTION__, __FILE__, $description, $subFile);
    $this->assertEquals(1, $result['count']);
    $params = array(
      'filter.group_id' => "26,27",
      'contact_type' => 'Individual',
    );
    $this->callAPISuccess('contact', 'get', $params);

    // testing as string
    $params = array(
      'filter.group_id' => array($groupId, 26),
      'contact_type' => 'Individual',
    );
    $result = $this->callAPIAndDocument('contact', 'get', $params, __FUNCTION__, __FILE__, $description, $subFile);
    $this->assertEquals(1, $result['count']);

    //test in conjunction with other criteria
    $params = array(
      'filter.group_id' => array($groupId, 26),
      'contact_type' => 'Organization',
    );
    $this->callAPISuccess('contact', 'get', $params);
    $params = array(
      'filter.group_id' => array(26, 27),
      'contact_type' => 'Individual',
    );
    $result = $this->callAPISuccess('contact', 'get', $params);
    $this->assertEquals(0, $result['count']);
  }

  /**
   * Verify that attempt to create individual contact with two chained websites succeeds.
   */
  public function testCreateIndividualWithContributionDottedSyntax() {
    $description = "This demonstrates the syntax to create 2 chained entities.";
    $subFile = "ChainTwoWebsites";
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

    $result = $this->callAPIAndDocument('Contact', 'create', $params, __FUNCTION__, __FILE__, $description, $subFile);

    $this->assertEquals(1, $result['id']);
    // checking child function result not covered in callAPIAndDocument
    $this->assertAPISuccess($result['values'][$result['id']]['api.website.create']);
    $this->assertEquals("http://chained.org", $result['values'][$result['id']]['api.website.create.2']['values'][0]['url']);
    $this->assertEquals("http://civicrm.org", $result['values'][$result['id']]['api.website.create']['values'][0]['url']);

    // delete the contact
    $this->callAPISuccess('contact', 'delete', $result);
  }

  /**
   * Verify that attempt to create individual contact with chained contribution and website succeeds.
   */
  public function testCreateIndividualWithContributionChainedArrays() {
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

    $description = "Demonstrates creating two websites as an array.";
    $subfile = "ChainTwoWebsitesSyntax2";
    $result = $this->callAPIAndDocument('Contact', 'create', $params, __FUNCTION__, __FILE__, $description, $subfile);

    $this->assertEquals(1, $result['id']);
    // the callAndDocument doesn't check the chained call
    $this->assertEquals(0, $result['values'][$result['id']]['api.website.create'][0]['is_error']);
    $this->assertEquals("http://chained.org", $result['values'][$result['id']]['api.website.create'][1]['values'][0]['url']);
    $this->assertEquals("http://civicrm.org", $result['values'][$result['id']]['api.website.create'][0]['values'][0]['url']);

    $this->callAPISuccess('contact', 'delete', $result);
  }

  /**
   * Test for direction when chaining relationships.
   *
   * https://issues.civicrm.org/jira/browse/CRM-16084
   */
  public function testDirectionChainingRelationshipsCRM16084() {
    // Some contact, called Jules.
    $create_result_1 = $this->callAPISuccess('contact', 'create', array(
      'first_name' => 'Jules',
      'last_name' => 'Smos',
      'contact_type' => 'Individual',
    ));

    // Another contact: Jos, child of Jules.
    $create_params = array(
      'first_name' => 'Jos',
      'last_name' => 'Smos',
      'contact_type' => 'Individual',
      'api.relationship.create' => array(
        array(
          'contact_id_a' => '$value.id',
          'contact_id_b' => $create_result_1['id'],
          // child of
          'relationship_type_id' => 1,
        ),
      ),
    );
    $create_result_2 = $this->callAPISuccess('contact', 'create', $create_params);

    // Mia is the child of Jos.
    $create_params = array(
      'first_name' => 'Mia',
      'last_name' => 'Smos',
      'contact_type' => 'Individual',
      'api.relationship.create' => array(
        array(
          'contact_id_a' => '$value.id',
          'contact_id_b' => $create_result_2['id'],
          // child of
          'relationship_type_id' => 1,
        ),
      ),
    );
    $create_result_3 = $this->callAPISuccess('contact', 'create', $create_params);

    // Get Jos and his children.
    $get_params = array(
      'sequential' => 1,
      'id' => $create_result_2['id'],
      'api.relationship.get' => array(
        'contact_id_b' => '$value.id',
        'relationship_type_id' => 1,
      ),
    );
    $get_result = $this->callAPISuccess('contact', 'getsingle', $get_params);

    // Clean up first.
    $this->callAPISuccess('contact', 'delete', array(
      'id' => $create_result_1['id'],
      ));
    $this->callAPISuccess('contact', 'delete', array(
      'id' => $create_result_2['id'],
      ));
    $this->callAPISuccess('contact', 'delete', array(
      'id' => $create_result_2['id'],
    ));

    // Assert.
    $this->assertEquals(1, $get_result['api.relationship.get']['count']);
    $this->assertEquals($create_result_3['id'], $get_result['api.relationship.get']['values'][0]['contact_id_a']);
  }

  /**
   * Verify that attempt to create individual contact with first, and last names and email succeeds.
   */
  public function testCreateIndividualWithNameEmail() {
    $params = array(
      'first_name' => 'abc3',
      'last_name' => 'xyz3',
      'contact_type' => 'Individual',
      'email' => 'man3@yahoo.com',
    );

    $contact = $this->callAPISuccess('contact', 'create', $params);
    $this->assertEquals(1, $contact['id']);

    $this->callAPISuccess('contact', 'delete', $contact);
  }

  /**
   * Verify that attempt to create individual contact with no data fails.
   */
  public function testCreateIndividualWithOutNameEmail() {
    $params = array(
      'contact_type' => 'Individual',
    );
    $this->callAPIFailure('contact', 'create', $params);
  }

  /**
   * Test create individual contact with first &last names, email and location type succeeds.
   */
  public function testCreateIndividualWithNameEmailLocationType() {
    $params = array(
      'first_name' => 'abc4',
      'last_name' => 'xyz4',
      'email' => 'man4@yahoo.com',
      'contact_type' => 'Individual',
      'location_type_id' => 1,
    );
    $result = $this->callAPISuccess('contact', 'create', $params);

    $this->assertEquals(1, $result['id']);

    $this->callAPISuccess('contact', 'delete', array('id' => $result['id']));
  }

  /**
   * Verify that when changing employers the old employer relationship becomes inactive.
   */
  public function testCreateIndividualWithEmployer() {
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
    foreach (array('Employee of', 'Volunteer for') as $relationshipType) {
      $relTypeId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType', $relationshipType, 'id', 'name_a_b');
      $this->callAPISuccess('relationship', 'create', array(
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
   * Verify that attempt to create household contact with details succeeds.
   */
  public function testCreateHouseholdDetails() {
    $params = array(
      'household_name' => 'abc8\'s House',
      'nick_name' => 'x House',
      'email' => 'man8@yahoo.com',
      'contact_type' => 'Household',
    );

    $contact = $this->callAPISuccess('contact', 'create', $params);

    $this->assertEquals(1, $contact['id']);

    $this->callAPISuccess('contact', 'delete', $contact);
  }

  /**
   * Verify that attempt to create household contact with inadequate details fails.
   */
  public function testCreateHouseholdInadequateDetails() {
    $params = array(
      'nick_name' => 'x House',
      'email' => 'man8@yahoo.com',
      'contact_type' => 'Household',
    );
    $this->callAPIFailure('contact', 'create', $params);
  }

  /**
   * Verify successful update of individual contact.
   */
  public function testUpdateIndividualWithAll() {
    // Insert a row in civicrm_contact creating individual contact.
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createXMLDataSet(
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

    $this->callAPISuccess('Contact', 'Update', $params);
    $getResult = $this->callAPISuccess('Contact', 'Get', $params);
    unset($params['contact_id']);
    //Todo - neither API v2 or V3 are testing for home_url - not sure if it is being set.
    //reducing this test partially back to api v2 level to get it through
    unset($params['home_url']);
    foreach ($params as $key => $value) {
      $this->assertEquals($value, $getResult['values'][23][$key]);
    }
    // Check updated civicrm_contact against expected.
    $expected = $this->createXMLDataSet(
      dirname(__FILE__) . '/dataset/contact_ind_upd.xml'
    );
    $actual = new PHPUnit_Extensions_Database_DataSet_QueryDataSet(
      $this->_dbconn
    );
    $actual->addTable('civicrm_contact');
    $expected->matches($actual);
  }

  /**
   * Verify successful update of organization contact.
   */
  public function testUpdateOrganizationWithAll() {
    // Insert a row in civicrm_contact creating organization contact
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createXMLDataSet(
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

    $this->callAPISuccess('Contact', 'Update', $params);

    // Check updated civicrm_contact against expected.
    $expected = $this->createXMLDataSet(
      dirname(__FILE__) . '/dataset/contact_org_upd.xml'
    );
    $actual = new PHPUnit_Extensions_Database_DataSet_QueryDataSet(
      $this->_dbconn
    );
    $actual->addTable('civicrm_contact');
    $expected->matches($actual);
  }

  /**
   * Verify successful update of household contact.
   */
  public function testUpdateHouseholdWithAll() {
    // Insert a row in civicrm_contact creating household contact
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createXMLDataSet(
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
   * Test civicrm_update() without contact type.
   *
   * Deliberately exclude contact_type as it should still cope using civicrm_api.
   *
   * CRM-7645.
   */
  public function testUpdateCreateWithID() {
    // Insert a row in civicrm_contact creating individual contact.
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createXMLDataSet(
        dirname(__FILE__) . '/dataset/contact_ind.xml'
      )
    );

    $params = array(
      'id' => 23,
      'first_name' => 'abcd',
      'last_name' => 'wxyz',
    );
    $this->callAPISuccess('Contact', 'Update', $params);
  }

  /**
   * Test civicrm_contact_delete() with no contact ID.
   */
  public function testContactDeleteNoID() {
    $params = array(
      'foo' => 'bar',
    );
    $this->callAPIFailure('contact', 'delete', $params);
  }

  /**
   * Test civicrm_contact_delete() with error.
   */
  public function testContactDeleteError() {
    $params = array('contact_id' => 999);
    $this->callAPIFailure('contact', 'delete', $params);
  }

  /**
   * Test civicrm_contact_delete().
   */
  public function testContactDelete() {
    $contactID = $this->individualCreate();
    $params = array(
      'id' => $contactID,
    );
    $this->callAPIAndDocument('contact', 'delete', $params, __FUNCTION__, __FILE__);
  }

  /**
   * Test civicrm_contact_get() return only first name.
   */
  public function testContactGetRetFirst() {
    $contact = $this->callAPISuccess('contact', 'create', $this->_params);
    $params = array(
      'contact_id' => $contact['id'],
      'return_first_name' => TRUE,
      'sort' => 'first_name',
    );
    $result = $this->callAPISuccess('contact', 'get', $params);
    $this->assertEquals(1, $result['count']);
    $this->assertEquals($contact['id'], $result['id']);
    $this->assertEquals('abc1', $result['values'][$contact['id']]['first_name']);
  }

  /**
   * Test civicrm_contact_get() return only first name & last name.
   *
   * Use comma separated string return with a space.
   */
  public function testContactGetReturnFirstLast() {
    $contact = $this->callAPISuccess('contact', 'create', $this->_params);
    $params = array(
      'contact_id' => $contact['id'],
      'return' => 'first_name, last_name',
    );
    $result = $this->callAPISuccess('contact', 'getsingle', $params);
    $this->assertEquals('abc1', $result['first_name']);
    $this->assertEquals('xyz1', $result['last_name']);
    //check that other defaults not returns
    $this->assertArrayNotHasKey('sort_name', $result);
    $params = array(
      'contact_id' => $contact['id'],
      'return' => 'first_name,last_name',
    );
    $result = $this->callAPISuccess('contact', 'getsingle', $params);
    $this->assertEquals('abc1', $result['first_name']);
    $this->assertEquals('xyz1', $result['last_name']);
    //check that other defaults not returns
    $this->assertArrayNotHasKey('sort_name', $result);
  }

  /**
   * Test civicrm_contact_get() return only first name & last name.
   *
   * Use comma separated string return without a space
   */
  public function testContactGetReturnFirstLastNoComma() {
    $contact = $this->callAPISuccess('contact', 'create', $this->_params);
    $params = array(
      'contact_id' => $contact['id'],
      'return' => 'first_name,last_name',
    );
    $result = $this->callAPISuccess('contact', 'getsingle', $params);
    $this->assertEquals('abc1', $result['first_name']);
    $this->assertEquals('xyz1', $result['last_name']);
    //check that other defaults not returns
    $this->assertArrayNotHasKey('sort_name', $result);
  }

  /**
   * Test civicrm_contact_get() with default return properties.
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
   * Test civicrm_contact_getquick() with empty name param.
   */
  public function testContactGetQuick() {
    // Insert a row in civicrm_contact creating individual contact.
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createXMLDataSet(
        dirname(__FILE__) . '/dataset/contact_17.xml'
      )
    );
    $op->execute($this->_dbconn,
      $this->createXMLDataSet(
        dirname(__FILE__) . '/dataset/email_contact_17.xml'
      )
    );
    $params = array(
      'name' => "T",
    );

    $result = $this->callAPISuccess('contact', 'getquick', $params);
    $this->assertEquals(17, $result['values'][0]['id']);
  }

  /**
   * Test civicrm_contact_get) with empty params.
   */
  public function testContactGetEmptyParams() {
    $this->callAPISuccess('contact', 'get', array());
  }

  /**
   * Test civicrm_contact_get(,true) with no matches.
   */
  public function testContactGetOldParamsNoMatches() {
    // Insert a row in civicrm_contact creating contact 17.
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createXMLDataSet(
        dirname(__FILE__) . '/dataset/contact_17.xml'
      )
    );

    $params = array(
      'first_name' => 'Fred',
    );
    $result = $this->callAPISuccess('contact', 'get', $params);
    $this->assertEquals(0, $result['count']);
  }

  /**
   * Test civicrm_contact_get(,true) with one match.
   */
  public function testContactGetOldParamsOneMatch() {
    // Insert a row in civicrm_contact creating contact 17
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createXMLDataSet(dirname(__FILE__) . '/dataset/contact_17.xml'
      )
    );

    $params = array(
      'first_name' => 'Test',
    );
    $result = $this->callAPISuccess('contact', 'get', $params);
    $this->assertEquals(17, $result['values'][17]['contact_id']);
    $this->assertEquals(17, $result['id']);
  }

  /**
   * Test civicrm_contact_search_count().
   */
  public function testContactGetEmail() {
    $params = array(
      'email' => 'man2@yahoo.com',
      'contact_type' => 'Individual',
      'location_type_id' => 1,
    );

    $contact = $this->callAPISuccess('contact', 'create', $params);

    $this->assertEquals(1, $contact['id']);

    $params = array(
      'email' => 'man2@yahoo.com',
    );
    $result = $this->callAPIAndDocument('contact', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['values'][1]['contact_id']);
    $this->assertEquals('man2@yahoo.com', $result['values'][1]['email']);

    // delete the contact
    $this->callAPISuccess('contact', 'delete', $contact);
  }

  /**
   * Test birth date parameters.
   *
   * These include value, array & birth_date_high, birth_date_low
   * && deceased.
   */
  public function testContactGetBirthDate() {
    $contact1 = $this->callAPISuccess('contact', 'create', array_merge($this->_params, array('birth_date' => 'first day of next month - 2 years')));
    $contact2 = $this->callAPISuccess('contact', 'create', array_merge($this->_params, array('birth_date' => 'first day of  next month - 5 years')));
    $contact3 = $this->callAPISuccess('contact', 'create', array_merge($this->_params, array('birth_date' => 'first day of next month -20 years')));

    $result = $this->callAPISuccess('contact', 'get', array());
    $this->assertEquals(date('Y-m-d', strtotime('first day of next month -2 years')), $result['values'][$contact1['id']]['birth_date']);
    $result = $this->callAPISuccess('contact', 'get', array('birth_date' => 'first day of next month -5 years'));
    $this->assertEquals(1, $result['count']);
    $this->assertEquals(date('Y-m-d', strtotime('first day of next month -5 years')), $result['values'][$contact2['id']]['birth_date']);
    $result = $this->callAPISuccess('contact', 'get', array('birth_date_high' => date('Y-m-d', strtotime('-6 years'))));
    $this->assertEquals(1, $result['count']);
    $this->assertEquals(date('Y-m-d', strtotime('first day of next month -20 years')), $result['values'][$contact3['id']]['birth_date']);
    $result = $this->callAPISuccess('contact', 'get', array(
      'birth_date_low' => date('Y-m-d', strtotime('-6 years')),
      'birth_date_high' => date('Y-m-d', strtotime('- 3 years')),
    ));
    $this->assertEquals(1, $result['count']);
    $this->assertEquals(date('Y-m-d', strtotime('first day of next month -5 years')), $result['values'][$contact2['id']]['birth_date']);
    $result = $this->callAPISuccess('contact', 'get', array(
      'birth_date_low' => '-6 years',
      'birth_date_high' => '- 3 years',
    ));
    $this->assertEquals(1, $result['count']);
    $this->assertEquals(date('Y-m-d', strtotime('first day of next month -5 years')), $result['values'][$contact2['id']]['birth_date']);
  }

  /**
   * Test Deceased date parameters.
   *
   * These include value, array & Deceased_date_high, Deceased date_low
   * && deceased.
   */
  public function testContactGetDeceasedDate() {
    $contact1 = $this->callAPISuccess('contact', 'create', array_merge($this->_params, array('deceased_date' => 'first day of next month - 2 years')));
    $contact2 = $this->callAPISuccess('contact', 'create', array_merge($this->_params, array('deceased_date' => 'first day of  next month - 5 years')));
    $contact3 = $this->callAPISuccess('contact', 'create', array_merge($this->_params, array('deceased_date' => 'first day of next month -20 years')));

    $result = $this->callAPISuccess('contact', 'get', array());
    $this->assertEquals(date('Y-m-d', strtotime('first day of next month -2 years')), $result['values'][$contact1['id']]['deceased_date']);
    $result = $this->callAPISuccess('contact', 'get', array('deceased_date' => 'first day of next month -5 years'));
    $this->assertEquals(1, $result['count']);
    $this->assertEquals(date('Y-m-d', strtotime('first day of next month -5 years')), $result['values'][$contact2['id']]['deceased_date']);
    $result = $this->callAPISuccess('contact', 'get', array('deceased_date_high' => date('Y-m-d', strtotime('-6 years'))));
    $this->assertEquals(1, $result['count']);
    $this->assertEquals(date('Y-m-d', strtotime('first day of next month -20 years')), $result['values'][$contact3['id']]['deceased_date']);
    $result = $this->callAPISuccess('contact', 'get', array(
      'deceased_date_low' => '-6 years',
      'deceased_date_high' => date('Y-m-d', strtotime('- 3 years')),
    ));
    $this->assertEquals(1, $result['count']);
    $this->assertEquals(date('Y-m-d', strtotime('first day of next month -5 years')), $result['values'][$contact2['id']]['deceased_date']);
  }

  /**
   * Test for Contact.get id=@user:username.
   */
  public function testContactGetByUsername() {
    // Setup - create contact with a uf-match.
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
   * Test to check return works OK.
   */
  public function testContactGetReturnValues() {
    $extraParams = array('nick_name' => 'Bob', 'phone' => '456', 'email' => 'e@mail.com');
    $contactID = $this->individualCreate($extraParams);
    //actually it turns out the above doesn't create a phone
    $this->callAPISuccess('phone', 'create', array('contact_id' => $contactID, 'phone' => '456'));
    $result = $this->callAPISuccess('contact', 'getsingle', array('id' => $contactID));
    foreach ($extraParams as $key => $value) {
      $this->assertEquals($result[$key], $value);
    }
    //now we check they are still returned with 'return' key
    $result = $this->callAPISuccess('contact', 'getsingle', array(
      'id' => $contactID,
      'return' => array_keys($extraParams),
    ));
    foreach ($extraParams as $key => $value) {
      $this->assertEquals($result[$key], $value);
    }
  }

  public function testCRM13252MultipleChainedPhones() {
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
        ),
      ),
    );
    $this->callAPISuccess('contact', 'create', $params);
    $this->callAPISuccessGetCount('phone', array('contact_id' => $contactID), 2);

  }

  /**
   * Test for Contact.get id=@user:username (with an invalid username).
   */
  public function testContactGetByUnknownUsername() {
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
   * Verify attempt to create individual with chained arrays.
   */
  public function testGetIndividualWithChainedArrays() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $moreIDs = $this->CustomGroupMultipleCreateWithFields();
    $description = "This demonstrates the usage of chained api functions.\nIn this case no notes or custom fields have been created.";
    $subfile = "APIChainedArray";
    $params = array(
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
        'financial_type_id' => $this->_financialTypeId = 1,
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
      ),
      'api.CustomValue.get' => 1,
      'api.Note.get' => 1,
    );
    $result = $this->callAPIAndDocument('Contact', 'Get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    // delete the contact
    $this->callAPISuccess('contact', 'delete', $result);
    $this->customGroupDelete($ids['custom_group_id']);
    $this->customGroupDelete($moreIDs['custom_group_id']);
    $this->assertEquals(1, $result['id']);
    $this->assertEquals(0, $result['values'][$result['id']]['api.website.get']['is_error']);
    $this->assertEquals("http://civicrm.org", $result['values'][$result['id']]['api.website.get']['values'][0]['url']);
  }

  public function testGetIndividualWithChainedArraysFormats() {
    $description = "This demonstrates the usage of chained api functions.\nIn this case no notes or custom fields have been created.";
    $subfile = "APIChainedArrayFormats";
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $moreIDs = $this->CustomGroupMultipleCreateWithFields();
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
    $this->assertEquals(1, $result['id']);
    $this->assertEquals(2, $result['values'][$result['id']]['api.Contribution.getCount']);
    $this->assertEquals(0, $result['values'][$result['id']]['api.Note.get']['is_error']);
    $this->assertEquals("http://civicrm.org", $result['values'][$result['id']]['api.website.getValue']);

    $this->callAPISuccess('contact', 'delete', $result);
    $this->customGroupDelete($ids['custom_group_id']);
    $this->customGroupDelete($moreIDs['custom_group_id']);
  }

  /**
   * Test complex chaining.
   */
  public function testGetIndividualWithChainedArraysAndMultipleCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $params['custom_' . $ids['custom_field_id']] = "custom string";
    $moreIDs = $this->CustomGroupMultipleCreateWithFields();
    $andMoreIDs = $this->CustomGroupMultipleCreateWithFields(array(
      'title' => "another group",
      'name' => 'another name',
    ));
    $description = "This demonstrates the usage of chained api functions with multiple custom fields.";
    $subfile = "APIChainedArrayMultipleCustom";
    $params = array(
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
        'financial_type_id' => 1,
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
      'custom_' . $moreIDs['custom_field_id'][0] => "value 2",
      'custom_' . $moreIDs['custom_field_id'][1] => "warm beer",
      'custom_' . $andMoreIDs['custom_field_id'][1] => "vegemite",
    );

    $result = $this->callAPISuccess('Contact', 'create', $params);
    $result = $this->callAPISuccess('Contact', 'create', array(
      'contact_type' => 'Individual',
      'id' => $result['id'],
      'custom_' .
      $moreIDs['custom_field_id'][0] => "value 3",
      'custom_' .
      $ids['custom_field_id'] => "value 4",
    ));

    $params = array(
      'id' => $result['id'],
      'api.website.getValue' => array('return' => 'url'),
      'api.Contribution.getCount' => array(),
      'api.CustomValue.get' => 1,
    );
    $result = $this->callAPIAndDocument('Contact', 'Get', $params, __FUNCTION__, __FILE__, $description, $subfile);

    $this->customGroupDelete($ids['custom_group_id']);
    $this->customGroupDelete($moreIDs['custom_group_id']);
    $this->customGroupDelete($andMoreIDs['custom_group_id']);
    $this->assertEquals(1, $result['id']);
    $this->assertEquals(0, $result['values'][$result['id']]['api.CustomValue.get']['is_error']);
    $this->assertEquals('http://civicrm.org', $result['values'][$result['id']]['api.website.getValue']);
  }

  /**
   * Test checks usage of $values to pick & choose inputs.
   */
  public function testChainingValuesCreate() {
    $description = "This demonstrates the usage of chained api functions.  Specifically it has one 'parent function' &
      2 child functions - one receives values from the parent (Contact) and the other child (Tag).";
    $subfile = "APIChainedArrayValuesFromSiblingFunction";
    $params = array(
      'display_name' => 'batman',
      'contact_type' => 'Individual',
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

  /**
   * Test TrueFalse format - I couldn't come up with an easy way to get an error on Get.
   */
  public function testContactGetFormatIsSuccessTrue() {
    $this->createContactFromXML();
    $description = "This demonstrates use of the 'format.is_success' param.
    This param causes only the success or otherwise of the function to be returned as BOOLEAN";
    $subfile = "FormatIsSuccess_True";
    $params = array('id' => 17, 'format.is_success' => 1);
    $result = $this->callAPIAndDocument('Contact', 'Get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(1, $result);
    $this->callAPISuccess('Contact', 'Delete', $params);
  }

  /**
   * Test TrueFalse format.
   */
  public function testContactCreateFormatIsSuccessFalse() {

    $description = "This demonstrates use of the 'format.is_success' param.
    This param causes only the success or otherwise of the function to be returned as BOOLEAN";
    $subfile = "FormatIsSuccess_Fail";
    $params = array('id' => 500, 'format.is_success' => 1);
    $result = $this->callAPIAndDocument('Contact', 'Create', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(0, $result);
  }

  /**
   * Test Single Entity format.
   */
  public function testContactGetSingleEntityArray() {
    $this->createContactFromXML();
    $description = "This demonstrates use of the 'format.single_entity_array' param.
      This param causes the only contact to be returned as an array without the other levels.
      It will be ignored if there is not exactly 1 result";
    $subfile = "GetSingleContact";
    $params = array('id' => 17);
    $result = $this->callAPIAndDocument('Contact', 'GetSingle', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals('Test Contact', $result['display_name']);
    $this->callAPISuccess('Contact', 'Delete', $params);
  }

  /**
   * Test Single Entity format.
   */
  public function testContactGetFormatCountOnly() {
    $this->createContactFromXML();
    $description = "This demonstrates use of the 'getCount' action.
      This param causes the count of the only function to be returned as an integer.";
    $subfile = "GetCountContact";
    $params = array('id' => 17);
    $result = $this->callAPIAndDocument('Contact', 'GetCount', $params, __FUNCTION__, __FILE__, $description,
      $subfile, 'getcount');
    $this->assertEquals('1', $result);
    $this->callAPISuccess('Contact', 'Delete', $params);
  }

  /**
   * Test id only format.
   */
  public function testContactGetFormatIDOnly() {
    $this->createContactFromXML();
    $description = "This demonstrates use of the 'format.id_only' param.
      This param causes the id of the only entity to be returned as an integer.
      It will be ignored if there is not exactly 1 result";
    $subfile = "FormatOnlyID";
    $params = array('id' => 17, 'format.only_id' => 1);
    $result = $this->callAPIAndDocument('Contact', 'Get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals('17', $result);
    $this->callAPISuccess('Contact', 'Delete', $params);
  }

  /**
   * Test id only format.
   */
  public function testContactGetFormatSingleValue() {
    $this->createContactFromXML();
    $description = "This demonstrates use of the 'format.single_value' param.
      This param causes only a single value of the only entity to be returned as an string.
      It will be ignored if there is not exactly 1 result";
    $subFile = "FormatSingleValue";
    $params = array('id' => 17, 'return' => 'display_name');
    $result = $this->callAPIAndDocument('Contact', 'getvalue', $params, __FUNCTION__, __FILE__, $description, $subFile);
    $this->assertEquals('Test Contact', $result);
    $this->callAPISuccess('Contact', 'Delete', $params);
  }

  /**
   * Test that permissions are respected when creating contacts.
   */
  public function testContactCreationPermissions() {
    $params = array(
      'contact_type' => 'Individual',
      'first_name' => 'Foo',
      'last_name' => 'Bear',
      'check_permissions' => TRUE,
    );
    $config = CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = array('access CiviCRM');
    $result = $this->callAPIFailure('contact', 'create', $params);
    $this->assertEquals('API permission check failed for Contact/create call; insufficient permission: require access CiviCRM and add contacts', $result['error_message'], 'lacking permissions should not be enough to create a contact');

    $config->userPermissionClass->permissions = array('access CiviCRM', 'add contacts', 'import contacts');
    $this->callAPISuccess('contact', 'create', $params, NULL, 'overfluous permissions should be enough to create a contact');
  }

  /**
   * Test update with check permissions set.
   */
  public function testContactUpdatePermissions() {
    $params = array(
      'contact_type' => 'Individual',
      'first_name' => 'Foo',
      'last_name' => 'Bear',
      'check_permissions' => TRUE,
    );
    $result = $this->callAPISuccess('contact', 'create', $params);
    $config = CRM_Core_Config::singleton();
    $params = array(
      'id' => $result['id'],
      'contact_type' => 'Individual',
      'last_name' => 'Bar',
      'check_permissions' => TRUE,
    );

    $config->userPermissionClass->permissions = array('access CiviCRM');
    $result = $this->callAPIFailure('contact', 'update', $params);
    $this->assertEquals('API permission check failed for Contact/update call; insufficient permission: require access CiviCRM and edit all contacts', $result['error_message'], 'lacking permissions should not be enough to update a contact');

    $config->userPermissionClass->permissions = array(
      'access CiviCRM',
      'add contacts',
      'view all contacts',
      'edit all contacts',
      'import contacts',
    );
    $this->callAPISuccess('contact', 'update', $params, NULL, 'overfluous permissions should be enough to update a contact');
  }

  /**
   * Set up helper to create a contact.
   */
  public function createContactFromXML() {
    // Insert a row in civicrm_contact creating contact 17.
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createXMLDataSet(
        dirname(__FILE__) . '/dataset/contact_17.xml'
      )
    );
  }

  /**
   * Test contact proximity api.
   */
  public function testContactProximity() {
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
    $this->assertEquals(1, $result['count']);

    // now do a proximity search with a close enough geocode and hope to match
    // that specific contact only!
    $proxParams = array(
      'latitude' => 37.7,
      'longitude' => -122.3,
      'unit' => 'mile',
      'distance' => 10,
    );
    $result = $this->callAPISuccess('contact', 'proximity', $proxParams);
    $this->assertEquals(1, $result['count']);
  }

  /**
   * Test that Ajax API permission is sufficient to access getquick api.
   *
   * (note that getquick api is required for autocomplete & has ACL permissions applied)
   */
  public function testGetquickPermissionCRM13744() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviEvent');
    $this->callAPIFailure('contact', 'getquick', array('name' => 'b', 'check_permissions' => TRUE));
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM');
    $this->callAPISuccess('contact', 'getquick', array('name' => 'b', 'check_permissions' => TRUE));
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access AJAX API');
    $this->callAPISuccess('contact', 'getquick', array('name' => 'b', 'check_permissions' => TRUE));
  }

  /**
   * Test get ref api - gets a list of references to an entity.
   */
  public function testGetReferenceCounts() {
    $result = $this->callAPISuccess('Contact', 'create', array(
      'first_name' => 'Testily',
      'last_name' => 'McHaste',
      'contact_type' => 'Individual',
      'api.Address.replace' => array(
        'values' => array(),
      ),
      'api.Email.replace' => array(
        'values' => array(
          array(
            'email' => 'spam@dev.null',
            'is_primary' => 0,
            'location_type_id' => 1,
          ),
        ),
      ),
      'api.Phone.replace' => array(
        'values' => array(
          array(
            'phone' => '234-567-0001',
            'is_primary' => 1,
            'location_type_id' => 1,
          ),
          array(
            'phone' => '234-567-0002',
            'is_primary' => 0,
            'location_type_id' => 1,
          ),
        ),
      ),
    ));

    //$dao = new CRM_Contact_BAO_Contact();
    //$dao->id = $result['id'];
    //$this->assertTrue((bool) $dao->find(TRUE));
    //
    //$refCounts = $dao->getReferenceCounts();
    //$this->assertTrue(is_array($refCounts));
    //$refCountsIdx = CRM_Utils_Array::index(array('name'), $refCounts);

    $refCounts = $this->callAPISuccess('Contact', 'getrefcount', array(
      'id' => $result['id'],
    ));
    $refCountsIdx = CRM_Utils_Array::index(array('name'), $refCounts['values']);

    $this->assertEquals(1, $refCountsIdx['sql:civicrm_email:contact_id']['count']);
    $this->assertEquals('civicrm_email', $refCountsIdx['sql:civicrm_email:contact_id']['table']);
    $this->assertEquals(2, $refCountsIdx['sql:civicrm_phone:contact_id']['count']);
    $this->assertEquals('civicrm_phone', $refCountsIdx['sql:civicrm_phone:contact_id']['table']);
    $this->assertTrue(!isset($refCountsIdx['sql:civicrm_address:contact_id']));
  }

  public function testSQLOperatorsOnContactAPI() {
    $this->individualCreate();
    $this->organizationCreate();
    $this->householdCreate();
    $contacts = $this->callAPISuccess('contact', 'get', array('legal_name' => array('IS NOT NULL' => TRUE)));
    $this->assertEquals($contacts['count'], CRM_Core_DAO::singleValueQuery('select count(*) FROM civicrm_contact WHERE legal_name IS NOT NULL'));
    $contacts = $this->callAPISuccess('contact', 'get', array('legal_name' => array('IS NULL' => TRUE)));
    $this->assertEquals($contacts['count'], CRM_Core_DAO::singleValueQuery('select count(*) FROM civicrm_contact WHERE legal_name IS NULL'));
  }

  /**
   * CRM-14743 - test api respects search operators.
   */
  public function testGetModifiedDateByOperators() {
    $preExistingContactCount = CRM_Core_DAO::singleValueQuery('select count(*) FROM civicrm_contact');
    $contact1 = $this->individualCreate();
    $sql = "UPDATE civicrm_contact SET created_date = '2012-01-01', modified_date = '2013-01-01' WHERE id = " . $contact1;
    CRM_Core_DAO::executeQuery($sql);
    $contact2 = $this->individualCreate();
    $sql = "UPDATE civicrm_contact SET created_date = '2012-02-01', modified_date = '2013-02-01' WHERE id = " . $contact2;
    CRM_Core_DAO::executeQuery($sql);
    $contact3 = $this->householdCreate();
    $sql = "UPDATE civicrm_contact SET created_date = '2012-03-01', modified_date = '2013-03-01' WHERE id = " . $contact3;
    CRM_Core_DAO::executeQuery($sql);
    $contacts = $this->callAPISuccess('contact', 'get', array('modified_date' => array('<' => '2014-01-01')));
    $this->assertEquals($contacts['count'], 3);
    $contacts = $this->callAPISuccess('contact', 'get', array('modified_date' => array('>' => '2014-01-01')));
    $this->assertEquals($contacts['count'], $preExistingContactCount);
  }

  /**
   * CRM-14743 - test api respects search operators.
   */
  public function testGetCreatedDateByOperators() {
    $preExistingContactCount = CRM_Core_DAO::singleValueQuery('select count(*) FROM civicrm_contact');
    $contact1 = $this->individualCreate();
    $sql = "UPDATE civicrm_contact SET created_date = '2012-01-01' WHERE id = " . $contact1;
    CRM_Core_DAO::executeQuery($sql);
    $contact2 = $this->individualCreate();
    $sql = "UPDATE civicrm_contact SET created_date = '2012-02-01' WHERE id = " . $contact2;
    CRM_Core_DAO::executeQuery($sql);
    $contact3 = $this->householdCreate();
    $sql = "UPDATE civicrm_contact SET created_date = '2012-03-01' WHERE id = " . $contact3;
    CRM_Core_DAO::executeQuery($sql);
    $contacts = $this->callAPISuccess('contact', 'get', array('created_date' => array('<' => '2014-01-01')));
    $this->assertEquals($contacts['count'], 3);
    $contacts = $this->callAPISuccess('contact', 'get', array('created_date' => array('>' => '2014-01-01')));
    $this->assertEquals($contacts['count'], $preExistingContactCount);
  }

  /**
   * CRM-14263 check that API is not affected by search profile related bug.
   */
  public function testReturnCityProfile() {
    $contactID = $this->individualCreate();
    CRM_Core_Config::singleton()->defaultSearchProfileID = 1;
    $this->callAPISuccess('address', 'create', array(
      'contact_id' => $contactID,
      'city' => 'Cool City',
      'location_type_id' => 1,
    ));
    $result = $this->callAPISuccess('contact', 'get', array('city' => 'Cool City', 'return' => 'contact_type'));
    $this->assertEquals(1, $result['count']);
  }

  /**
   * CRM-15443 - ensure getlist api does not return deleted contacts
   */
  public function testGetlistExcludeConditions() {
    $name = md5(time());
    $contact = $this->individualCreate(array('last_name' => $name));
    $this->individualCreate(array('last_name' => $name, 'is_deceased' => 1));
    $this->individualCreate(array('last_name' => $name, 'is_deleted' => 1));
    // We should get all but the deleted contact.
    $result = $this->callAPISuccess('contact', 'getlist', array('input' => $name));
    $this->assertEquals(2, $result['count']);
    // Force-exclude the deceased contact.
    $result = $this->callAPISuccess('contact', 'getlist', array(
      'input' => $name,
      'params' => array('is_deceased' => 0),
    ));
    $this->assertEquals(1, $result['count']);
    $this->assertEquals($contact, $result['values'][0]['id']);
  }

  /**
   * Test contact getactions.
   */
  public function testGetActions() {
    $description = "Getting the available actions for an entity.";
    $result = $this->callAPIAndDocument($this->_entity, 'getactions', array(), __FUNCTION__, __FILE__, $description);
    $expected = array(
      'create',
      'delete',
      'get',
      'getactions',
      'getcount',
      'getfields',
      'getlist',
      'getoptions',
      'getquick',
      'getrefcount',
      'getsingle',
      'getvalue',
      'merge',
      'proximity',
      'replace',
      'setvalue',
      'update',
    );
    $deprecated = array(
      'update',
      'getquick',
    );
    foreach ($expected as $action) {
      $this->assertTrue(in_array($action, $result['values']), "Expected action $action");
    }
    foreach ($deprecated as $action) {
      $this->assertArrayKeyExists($action, $result['deprecated']);
    }
  }

}
