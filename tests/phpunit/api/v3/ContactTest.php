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
 *  Test APIv3 civicrm_contact* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 * @group headless
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

  /**
   * Restore the DB for the next test.
   *
   * @throws \Exception
   */
  public function tearDown() {
    $this->callAPISuccess('Setting', 'create', array('includeOrderByClause' => TRUE));
    // truncate a few tables
    $tablesToTruncate = array(
      'civicrm_email',
      'civicrm_contribution',
      'civicrm_line_item',
      'civicrm_website',
      'civicrm_relationship',
      'civicrm_uf_match',
      'civicrm_phone',
      'civicrm_address',
      'civicrm_acl_contact_cache',
      'civicrm_activity_contact',
      'civicrm_activity',
      'civicrm_group',
      'civicrm_group_contact',
      'civicrm_saved_search',
      'civicrm_group_contact_cache',
    );

    $this->quickCleanup($tablesToTruncate, TRUE);
    parent::tearDown();
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
   * Test that it is possible to prevent cache clearing via option.
   *
   * Cache clearing is bypassed if 'options' => array('do_not_reset_cache' => 1 is used.
   */
  public function testCreateIndividualNoCacheClear() {

    $contact = $this->callAPISuccess('contact', 'create', $this->_params);
    $groupID = $this->groupCreate();

    $this->putGroupContactCacheInClearableState($groupID, $contact);

    $this->callAPISuccess('contact', 'create', array('id' => $contact['id']));
    $this->assertEquals(0, CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_group_contact_cache"));

    // Rinse & repeat, but with the option.
    $this->putGroupContactCacheInClearableState($groupID, $contact);
    CRM_Core_Config::setPermitCacheFlushMode(FALSE);
    $this->callAPISuccess('contact', 'create', array('id' => $contact['id']));
    $this->assertEquals(1, CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_group_contact_cache"));
    CRM_Core_Config::setPermitCacheFlushMode(TRUE);
  }

  /**
   * Test for international string acceptance (CRM-10210).
   * Requires the databsase to be in utf8.
   *
   * @dataProvider getInternationalStrings
   *
   * @param string $string
   *   String to be tested.
   *
   *   Bool to see if we should check charset.
   *
   * @throws \Exception
   */
  public function testInternationalStrings($string) {
    $this->callAPISuccess('Contact', 'create', array_merge(
      $this->_params,
      array('first_name' => $string)
    ));

    $result = $this->callAPISuccessGetSingle('Contact', array('first_name' => $string));
    $this->assertEquals($string, $result['first_name']);

    $organizationParams = array(
      'organization_name' => $string,
      'contact_type' => 'Organization',
    );

    $this->callAPISuccess('Contact', 'create', $organizationParams);
    $result = $this->callAPISuccessGetSingle('Contact', $organizationParams);
    $this->assertEquals($string, $result['organization_name']);
  }

  /**
   * Get international string data for testing against api calls.
   */
  public function getInternationalStrings() {
    $invocations = array();
    $invocations[] = array('Scarabée');
    $invocations[] = array('Iñtërnâtiônàlizætiøn');
    $invocations[] = array('これは日本語のテキストです。読めますか');
    $invocations[] = array('देखें हिन्दी कैसी नजर आती है। अरे वाह ये तो नजर आती है।');
    return $invocations;
  }

  /**
   * Test civicrm_contact_create.
   *
   * Verify that preferred language can be set.
   */
  public function testAddCreateIndividualWithPreferredLanguage() {
    $params = array(
      'first_name' => 'abc1',
      'contact_type' => 'Individual',
      'last_name' => 'xyz1',
      'preferred_language' => 'es_ES',
    );

    $contact = $this->callAPISuccess('contact', 'create', $params);
    $this->getAndCheck($params, $contact['id'], 'Contact');
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
   * Verify that we can retreive contacts of different sub types
   */
  public function testGetMultipleContactSubTypes() {

    // This test presumes that there are no parents or students in the dataset

    // create a student
    $student = $this->callAPISuccess('contact', 'create', array(
      'email' => 'student@example.com',
      'contact_type' => 'Individual',
      'contact_sub_type' => 'Student',
    ));

    // create a parent
    $parent = $this->callAPISuccess('contact', 'create', array(
      'email' => 'parent@example.com',
      'contact_type' => 'Individual',
      'contact_sub_type' => 'Parent',
    ));

    // create a parent
    $this->callAPISuccess('contact', 'create', array(
      'email' => 'parent@example.com',
      'contact_type' => 'Individual',
    ));

    // get all students and parents
    $getParams = array('contact_sub_type' => array('IN' => array('Parent', 'Student')));
    $result = civicrm_api3('contact', 'get', $getParams);

    // check that we retrieved the student and the parent
    $this->assertArrayHasKey($student['id'], $result['values']);
    $this->assertArrayHasKey($parent['id'], $result['values']);
    $this->assertEquals(2, $result['count']);

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
    $primaryEmail = 'man3@yahoo.com';
    $notPrimaryEmail = 'man4@yahoo.com';
    $params = array(
      'email' => $primaryEmail,
      'contact_type' => 'Individual',
      'location_type_id' => 1,
    );

    $contact1 = $this->callAPISuccess('contact', 'create', $params);

    $this->assertEquals(3, $contact1['id']);
    $email1 = $this->callAPISuccess('email', 'get', array('contact_id' => $contact1['id']));
    $this->assertEquals(1, $email1['count']);
    $this->assertEquals($primaryEmail, $email1['values'][$email1['id']]['email']);

    $email2 = $this->callAPISuccess('email', 'create', array('contact_id' => $contact1['id'], 'is_primary' => 0, 'email' => $notPrimaryEmail));

    // Case 1: Check with criteria primary 'email' => array('IS NOT NULL' => 1)
    $result = $this->callAPISuccess('contact', 'get', array('email' => array('IS NOT NULL' => 1)));
    $primaryEmailContactIds = array_keys($result['values']);
    $this->assertEquals($primaryEmail, $email1['values'][$email1['id']]['email']);

    // Case 2: Check with criteria primary 'email' => array('<>' => '')
    $result = $this->callAPISuccess('contact', 'get', array('email' => array('<>' => '')));
    $primaryEmailContactIds = array_keys($result['values']);
    $this->assertEquals($primaryEmail, $email1['values'][$email1['id']]['email']);

    // Case 3: Check with email_id='primary email id'
    $result = $this->callAPISuccess('contact', 'get', array('email_id' => $email1['id']));
    $this->assertEquals(1, $result['count']);
    $this->assertEquals($contact1['id'], $result['id']);

    $this->callAPISuccess('contact', 'delete', $contact1);
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

    $this->callAPISuccess('contact', 'create', $params);
  }

  /**
   * Test creating individual by display_name.
   *
   * Display name & sort name should be set.
   */
  public function testCreateDisplayNameIndividual() {
    $params = array(
      'display_name' => 'abc1',
      'contact_type' => 'Individual',
    );

    $contact = $this->callAPISuccess('contact', 'create', $params);
    $params['sort_name'] = 'abc1';
    $this->getAndCheck($params, $contact['id'], 'contact');
  }

  /**
   * Test that name searches are case insensitive.
   */
  public function testGetNameVariantsCaseInsensitive() {
    $this->callAPISuccess('contact', 'create', [
      'display_name' => 'Abc1',
      'contact_type' => 'Individual',
    ]);
    $this->callAPISuccessGetSingle('Contact', ['display_name' => 'aBc1']);
    $this->callAPISuccessGetSingle('Contact', ['sort_name' => 'aBc1']);
    Civi::settings()->set('includeNickNameInName', TRUE);
    $this->callAPISuccessGetSingle('Contact', ['display_name' => 'aBc1']);
    $this->callAPISuccessGetSingle('Contact', ['sort_name' => 'aBc1']);
    Civi::settings()->set('includeNickNameInName', FALSE);
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
    $this->callAPISuccess('contact', 'create', $params);
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
    $this->callAPISuccess('contact', 'create', $params);
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
   * Check that permissions on API key are restricted (CRM-18112).
   */
  public function testCreateApiKey() {
    $config = CRM_Core_Config::singleton();
    $contactId = $this->individualCreate(array(
      'first_name' => 'A',
      'last_name' => 'B',
    ));

    // Allow edit -- because permissions aren't being checked
    $config->userPermissionClass->permissions = array();
    $result = $this->callAPISuccess('Contact', 'create', array(
      'id' => $contactId,
      'api_key' => 'original',
    ));
    $this->assertEquals('original', $result['values'][$contactId]['api_key']);

    // Allow edit -- because we have adequate permission
    $config->userPermissionClass->permissions = array('access CiviCRM', 'edit all contacts', 'edit api keys');
    $result = $this->callAPISuccess('Contact', 'create', array(
      'check_permissions' => 1,
      'id' => $contactId,
      'api_key' => 'abcd1234',
    ));
    $this->assertEquals('abcd1234', $result['values'][$contactId]['api_key']);

    // Disallow edit -- because we don't have permission
    $config->userPermissionClass->permissions = array('access CiviCRM', 'edit all contacts');
    $result = $this->callAPIFailure('Contact', 'create', array(
      'check_permissions' => 1,
      'id' => $contactId,
      'api_key' => 'defg4321',
    ));
    $this->assertRegExp(';Permission denied to modify api key;', $result['error_message']);

    // Return everything -- because permissions are not being checked
    $config->userPermissionClass->permissions = array();
    $result = $this->callAPISuccess('Contact', 'create', array(
      'id' => $contactId,
      'first_name' => 'A2',
    ));
    $this->assertEquals('A2', $result['values'][$contactId]['first_name']);
    $this->assertEquals('B', $result['values'][$contactId]['last_name']);
    $this->assertEquals('abcd1234', $result['values'][$contactId]['api_key']);

    // Return everything -- because we have adequate permission
    $config->userPermissionClass->permissions = array('access CiviCRM', 'edit all contacts', 'edit api keys');
    $result = $this->callAPISuccess('Contact', 'create', array(
      'check_permissions' => 1,
      'id' => $contactId,
      'first_name' => 'A3',
    ));
    $this->assertEquals('A3', $result['values'][$contactId]['first_name']);
    $this->assertEquals('B', $result['values'][$contactId]['last_name']);
    $this->assertEquals('abcd1234', $result['values'][$contactId]['api_key']);

    // Restricted return -- because we don't have permission
    $config->userPermissionClass->permissions = array('access CiviCRM', 'edit all contacts');
    $result = $this->callAPISuccess('Contact', 'create', array(
      'check_permissions' => 1,
      'id' => $contactId,
      'first_name' => 'A4',
    ));
    $this->assertEquals('A4', $result['values'][$contactId]['first_name']);
    $this->assertEquals('B', $result['values'][$contactId]['last_name']);
    $this->assertTrue(empty($result['values'][$contactId]['api_key']));
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
   * CRM-14232 test preferred language set to site default if not passed.
   */
  public function testCreatePreferredLanguageUnset() {
    $this->callAPISuccess('Contact', 'create', array(
      'first_name' => 'Snoop',
      'last_name' => 'Dog',
      'contact_type' => 'Individual')
    );
    $result = $this->callAPISuccessGetSingle('Contact', array('last_name' => 'Dog'));
    $this->assertEquals('en_US', $result['preferred_language']);
  }

  /**
   * CRM-14232 test preferred language returns setting if not passed.
   */
  public function testCreatePreferredLanguageSet() {
    $this->callAPISuccess('Setting', 'create', array('contact_default_language' => 'fr_FR'));
    $this->callAPISuccess('Contact', 'create', array(
      'first_name' => 'Snoop',
      'last_name' => 'Dog',
      'contact_type' => 'Individual',
    ));
    $result = $this->callAPISuccessGetSingle('Contact', array('last_name' => 'Dog'));
    $this->assertEquals('fr_FR', $result['preferred_language']);
  }

  /**
   * CRM-14232 test preferred language returns setting if not passed where setting is NULL.
   */
  public function testCreatePreferredLanguageNull() {
    $this->callAPISuccess('Setting', 'create', array('contact_default_language' => 'null'));
    $this->callAPISuccess('Contact', 'create', array(
        'first_name' => 'Snoop',
        'last_name' => 'Dog',
        'contact_type' => 'Individual',
        )
    );
    $result = $this->callAPISuccessGetSingle('Contact', array('last_name' => 'Dog'));
    $this->assertEquals(NULL, $result['preferred_language']);
  }

  /**
   * CRM-14232 test preferred language returns setting if not passed where setting is NULL.
   */
  public function testCreatePreferredLanguagePassed() {
    $this->callAPISuccess('Setting', 'create', array('contact_default_language' => 'null'));
    $this->callAPISuccess('Contact', 'create', array(
      'first_name' => 'Snoop',
      'last_name' => 'Dog',
      'contact_type' => 'Individual',
      'preferred_language' => 'en_AU',
    ));
    $result = $this->callAPISuccessGetSingle('Contact', array('last_name' => 'Dog'));
    $this->assertEquals('en_AU', $result['preferred_language']);
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

    $result = $this->callAPISuccess('Contact', 'create', $params);
    $customFldId = $result['values'][$result['id']]['api.CustomField.create']['id'];
    $this->assertNotNull($result['id']);
    $this->assertNotNull($customFldId);

    $params = array(
      'id' => $result['id'],
      "custom_{$customFldId}" => $dateTime,
      'api.CustomValue.get' => 1,
    );

    $result = $this->callAPISuccess('Contact', 'create', $params);
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
    $result = $this->callAPISuccess('Contact', 'create', $params);
    $this->assertNotNull($result['id']);
    $customFldDate = date("Ymd", strtotime($result['values'][$result['id']]['api.CustomValue.get']['values'][0]['latest']));
    $customFldTime = date("His", strtotime($result['values'][$result['id']]['api.CustomValue.get']['values'][0]['latest']));
    $this->assertNotNull($customFldDate);
    $this->assertEquals($dateTime, $customFldDate);
    $this->assertEquals(000000, $customFldTime);
    $this->callAPISuccess('Contact', 'create', $params);
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
      'contact_type' => 'Individual',
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
    $countActive = $this->callAPISuccess('contact', 'getcount', array(
      'showAll' => 'active',
      'contact_type' => 'Individual',
    ));
    $countAll = $this->callAPISuccess('contact', 'getcount', array('showAll' => 'all', 'contact_type' => 'Individual'));
    $countTrash = $this->callAPISuccess('contact', 'getcount', array('showAll' => 'trash', 'contact_type' => 'Individual'));
    $countDefault = $this->callAPISuccess('contact', 'getcount', array('contact_type' => 'Individual'));
    $countDeleted = $this->callAPISuccess('contact', 'getcount', array(
      'contact_type' => 'Individual',
      'contact_is_deleted' => 1,
    ));
    $countNotDeleted = $this->callAPISuccess('contact', 'getcount', array(
      'contact_is_deleted' => 0,
      'contact_type' => 'Individual',
    ));
    $this->callAPISuccess('contact', 'delete', array('id' => $contact1['id']));
    $this->callAPISuccess('contact', 'delete', array('id' => $contact2['id']));
    $this->assertEquals(1, $countNotDeleted, 'contact_is_deleted => 0 is respected');
    $this->assertEquals(1, $countActive);
    $this->assertEquals(1, $countTrash);
    $this->assertEquals(2, $countAll);
    $this->assertEquals(1, $countDeleted);
    $this->assertEquals(1, $countDefault, 'Only active by default in line');
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
      'contact_type' => 'Individual',
      'options' => array(
        'limit' => 1,
        'sort' => 'first_name',
      ),
    ));
    $this->assertEquals('abc1', $result);

    $result = $this->callAPISuccess($this->_entity, 'getvalue', array(
      'return' => 'first_name',
      'contact_type' => 'Individual',
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
    $this->assertEquals("O'Connor", $result['last_name']);
  }

  /**
   * Test between accepts zero.
   *
   * In the past it incorrectly required !empty.
   */
  public function testGetBetweenZeroWorks() {
    $this->callAPISuccess($this->_entity, 'get', [
      'contact_id' => ['BETWEEN' => [0, 9]],
    ]);
    $this->callAPISuccess($this->_entity, 'get', [
      'contact_id' => [
        'BETWEEN' => [
          (0 - 9),
          0,
        ],
      ],
    ]);
  }

  /**
   * Test retrieval by addressee id.
   */
  public function testGetByAddresseeID() {
    $individual1ID = $this->individualCreate([
      'skip_greeting_processing' => 1,
      'addressee_id' => 'null',
      'email_greeting_id' => 'null',
      'postal_greeting_id' => 'null'
    ]);
    $individual2ID = $this->individualCreate();

    $this->assertEquals($individual1ID,
      $this->callAPISuccessGetValue('Contact', ['contact_type' => 'Individual', 'addressee_id' => ['IS NULL' => 1], 'return' => 'id'])
    );
    $this->assertEquals($individual1ID,
      $this->callAPISuccessGetValue('Contact', ['contact_type' => 'Individual', 'email_greeting_id' => ['IS NULL' => 1], 'return' => 'id'])
    );
    $this->assertEquals($individual1ID,
      $this->callAPISuccessGetValue('Contact', ['contact_type' => 'Individual', 'postal_greeting_id' => ['IS NULL' => 1], 'return' => 'id'])
    );

    $this->assertEquals($individual2ID,
      $this->callAPISuccessGetValue('Contact', ['contact_type' => 'Individual', 'addressee_id' => ['NOT NULL' => 1], 'return' => 'id'])
    );
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
   * Check that address name, ID is returned if required.
   */
  public function testGetReturnAddress() {
    $contactID = $this->individualCreate();
    $result = $this->callAPISuccess('address', 'create', array(
      'contact_id' => $contactID,
      'address_name' => 'My house',
      'location_type_id' => 'Home',
      'street_address' => '1 my road',
    ));
    $addressID = $result['id'];

    $result = $this->callAPISuccessGetSingle('contact', array(
      'return' => 'address_name, street_address, address_id',
      'id' => $contactID,
    ));
    $this->assertEquals($addressID, $result['address_id']);
    $this->assertEquals('1 my road', $result['street_address']);
    $this->assertEquals('My house', $result['address_name']);

  }

  /**
   * Test group filter syntaxes.
   */
  public function testGetGroupIDFromContact() {
    $groupId = $this->groupCreate();
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
    $result = $this->callAPISuccess('contact', 'get', $params);
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
    $result = $this->callAPISuccess('contact', 'get', $params);
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
    $result = $this->callAPISuccess('contact', 'get', $params);
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
        'skipCleanMoney' => 1,
      ),
      'api.website.create' => array(
        'url' => "http://civicrm.org",
      ),
      'api.website.create.2' => array(
        'url' => "http://chained.org",
      ),
    );

    $result = $this->callAPIAndDocument('Contact', 'create', $params, __FUNCTION__, __FILE__, $description, $subFile);

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
        'skipCleanMoney' => 1,
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
   * Test merging 2 organizations.
   *
   * CRM-20421: This test make sure that inherited memberships are deleted upon merging organization.
   */
  public function testMergeOrganizations() {
    $organizationID1 = $this->organizationCreate(array(), 0);
    $organizationID2 = $this->organizationCreate(array(), 1);
    $contact = $this->callAPISuccess('contact', 'create', array_merge($this->_params, array(
        'employer_id' => $organizationID1,
      )
    ));
    $contact = $contact["values"][$contact["id"]];

    $membershipType = $this->createEmployerOfMembership();
    $membershipParams = array(
      'membership_type_id' => $membershipType["id"],
      'contact_id' => $organizationID1,
      'start_date' => "01/01/2015",
      'join_date' => "01/01/2010",
      'end_date' => "12/31/2015",
    );
    $ownermembershipid = $this->contactMembershipCreate($membershipParams);

    $contactmembership = $this->callAPISuccess("membership", "getsingle", array(
      "contact_id" => $contact["id"],
    ));

    $this->assertEquals($ownermembershipid, $contactmembership["owner_membership_id"], "Contact membership must be inherited from Organization");

    CRM_Dedupe_Merger::moveAllBelongings($organizationID2, $organizationID1, array(
      "move_rel_table_memberships"   => "0",
      "move_rel_table_relationships" => "1",
      "main_details" => array(
        "contact_id"   => $organizationID2,
        "contact_type" => "Organization",
      ),
      "other_details" => array(
        "contact_id"   => $organizationID1,
        "contact_type" => "Organization",
      ),
    ));

    $contactmembership = $this->callAPISuccess("membership", "get", array(
      "contact_id" => $contact["id"],
    ));

    $this->assertEquals(0, $contactmembership["count"], "Contact membership must be deleted after merging organization without memberships.");
  }

  private function createEmployerOfMembership() {
    $params = array(
      'domain_id' => CRM_Core_Config::domainID(),
      'name' => 'Organization Membership',
      'description' => NULL,
      'member_of_contact_id' => 1,
      'financial_type_id' => 1,
      'minimum_fee' => 10,
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'relationship_type_id' => 5,
      'relationship_direction' => 'b_a',
      'visibility' => 'Public',
      'is_active' => 1,
    );
    $membershipType = $this->callAPISuccess('membership_type', 'create', $params);
    return $membershipType["values"][$membershipType["id"]];
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
    $params = array(
      'name' => "TestContact@example.com",
      'field_name' => 'sort_name',
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

    $params = array(
      'email' => 'man2@yahoo.com',
    );
    $result = $this->callAPIAndDocument('contact', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals('man2@yahoo.com', $result['values'][$result['id']]['email']);

    $this->callAPISuccess('contact', 'delete', $contact);
  }

  /**
   * Ensure consistent return format for option group fields.
   */
  public function testSetPreferredCommunicationNull() {
    $contact = $this->callAPISuccess('contact', 'create', array_merge($this->_params, array(
      'preferred_communication_method' => array('Phone', 'SMS'),
    )));
    $preferredCommunicationMethod = $this->callAPISuccessGetValue('Contact', array(
      'id' => $contact['id'],
      'return' => 'preferred_communication_method',
    ));
    $this->assertNotEmpty($preferredCommunicationMethod);
    $contact = $this->callAPISuccess('contact', 'create', array_merge($this->_params, array(
      'preferred_communication_method' => 'null',
      'id' => $contact['id'],
    )));
    $preferredCommunicationMethod = $this->callAPISuccessGetValue('Contact', array(
      'id' => $contact['id'],
      'return' => 'preferred_communication_method',
    ));
    $this->assertEmpty($preferredCommunicationMethod);
  }

  /**
   * Ensure consistent return format for option group fields.
   */
  public function testPseudoFields() {
    $params = array(
      'preferred_communication_method' => array('Phone', 'SMS'),
      'preferred_language' => 'en_US',
      'gender_id' => 'Female',
      'prefix_id' => 'Mrs.',
      'suffix_id' => 'II',
      'communication_style_id' => 'Formal',
    );

    $contact = $this->callAPISuccess('contact', 'create', array_merge($this->_params, $params));

    $result = $this->callAPISuccess('contact', 'getsingle', array('id' => $contact['id']));
    $this->assertEquals('Both', $result['preferred_mail_format']);

    $this->assertEquals('en_US', $result['preferred_language']);
    $this->assertEquals(1, $result['communication_style_id']);
    $this->assertEquals(1, $result['gender_id']);
    $this->assertEquals('Female', $result['gender']);
    $this->assertEquals('Mrs.', $result['individual_prefix']);
    $this->assertEquals(1, $result['prefix_id']);
    $this->assertEquals('II', $result['individual_suffix']);
    $this->assertEquals(CRM_Core_PseudoConstant::getKey("CRM_Contact_BAO_Contact", 'suffix_id', 'II'), $result['suffix_id']);
    $this->callAPISuccess('contact', 'delete', $contact);
    $this->assertEquals(array(
      CRM_Core_PseudoConstant::getKey("CRM_Contact_BAO_Contact", 'preferred_communication_method', 'Phone'),
      CRM_Core_PseudoConstant::getKey("CRM_Contact_BAO_Contact", 'preferred_communication_method', 'SMS'),
    ), $result['preferred_communication_method']);
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
   * Test Address parameters
   *
   * This include state_province, state_province_name, country
   */
  public function testContactGetWithAddressFields() {
    $individuals = array(
      array(
        'first_name' => 'abc1',
        'contact_type' => 'Individual',
        'last_name' => 'xyz1',
        'api.address.create' => array(
          'country' => 'United States',
          'state_province_id' => 'Michigan',
          'location_type_id' => 1,
        ),
      ),
      array(
        'first_name' => 'abc2',
        'contact_type' => 'Individual',
        'last_name' => 'xyz2',
        'api.address.create' => array(
          'country' => 'United States',
          'state_province_id' => 'Alabama',
          'location_type_id' => 1,
        ),
      ),
    );
    foreach ($individuals as $params) {
      $contact = $this->callAPISuccess('contact', 'create', $params);
    }

    // Check whether Contact get API return successfully with below Address params.
    $fieldsToTest = array(
      'state_province_name' => 'Michigan',
      'state_province' => 'Michigan',
      'country' => 'United States',
      'state_province_name' => array('IN' => array('Michigan', 'Alabama')),
      'state_province' => array('IN' => array('Michigan', 'Alabama')),
    );
    foreach ($fieldsToTest as $field => $value) {
      $getParams = array(
        'id' => $contact['id'],
        $field => $value,
      );
      $result = $this->callAPISuccess('Contact', 'get', $getParams);
      $this->assertEquals(1, $result['count']);
    }
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
    $mockFunction = $this->mockMethod;
    $userSystem = $this->$mockFunction('CRM_Utils_System_UnitTests', array('getUfId'));
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

    // Check search of contacts with & without uf records
    $result = $this->callAPISuccess('Contact', 'get', ['uf_user' => 1]);
    $this->assertArrayHasKey($cid, $result['values']);

    $result = $this->callAPISuccess('Contact', 'get', ['uf_user' => 0]);
    $this->assertArrayNotHasKey($cid, $result['values']);
  }

  /**
   * Test to check return works OK.
   */
  public function testContactGetReturnValues() {
    $extraParams = array(
      'nick_name' => 'Bob',
      'phone' => '456',
      'email' => 'e@mail.com',
    );
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

  /**
   * Test creating multiple phones using chaining.
   *
   * @throws \Exception
   */
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
    $mockFunction = $this->mockMethod;
    $userSystem = $this->$mockFunction('CRM_Utils_System_UnitTests', array('getUfId'));
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
   * Verify attempt to create individual with chained arrays and sequential.
   */
  public function testGetIndividualWithChainedArraysAndSequential() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $moreIDs = $this->CustomGroupMultipleCreateWithFields();
    $params = array(
      'sequential' => 1,
      'first_name' => 'abc3',
      'last_name' => 'xyz3',
      'contact_type' => 'Individual',
      'email' => 'man3@yahoo.com',
      'api.website.create' => array(
        array(
          'url' => "http://civicrm.org",
        ),
        array(
          'url' => "https://civicrm.org",
        ),
      ),
    );

    $result = $this->callAPISuccess('Contact', 'create', $params);

    // delete the contact and custom groups
    $this->callAPISuccess('contact', 'delete', array('id' => $result['id']));
    $this->customGroupDelete($ids['custom_group_id']);
    $this->customGroupDelete($moreIDs['custom_group_id']);

    $this->assertEquals($result['id'], $result['values'][0]['id']);
    $this->assertArrayKeyExists('api.website.create', $result['values'][0]);
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
    $this->assertEquals(0, $result['values'][$result['id']]['api.website.get']['is_error']);
    $this->assertEquals("http://civicrm.org", $result['values'][$result['id']]['api.website.get']['values'][0]['url']);
  }

  /**
   * Verify attempt to create individual with chained arrays and sequential.
   *
   *  See https://issues.civicrm.org/jira/browse/CRM-15815
   */
  public function testCreateIndividualWithChainedArrayAndSequential() {
    $params = array(
      'sequential' => 1,
      'first_name' => 'abc5',
      'last_name' => 'xyz5',
      'contact_type' => 'Individual',
      'email' => 'woman5@yahoo.com',
      'api.phone.create' => array(
        array('phone' => '03-231 07 95'),
        array('phone' => '03-232 51 62'),
      ),
      'api.website.create' => array(
        'url' => 'http://civicrm.org',
      ),
    );
    $result = $this->callAPISuccess('Contact', 'create', $params);

    // I could try to parse the result to see whether the two phone numbers
    // and the website are there, but I am not sure about the correct format.
    // So I will just fetch it again before checking.
    // See also http://forum.civicrm.org/index.php/topic,35393.0.html
    $params = array(
      'sequential' => 1,
      'id' => $result['id'],
      'api.website.get' => array(),
      'api.phone.get' => array(),
    );
    $result = $this->callAPISuccess('Contact', 'get', $params);

    // delete the contact
    $this->callAPISuccess('contact', 'delete', $result);

    $this->assertEquals(2, $result['values'][0]['api.phone.get']['count']);
    $this->assertEquals(1, $result['values'][0]['api.website.get']['count']);
  }

  /**
   * Test retrieving an individual with chained array syntax.
   */
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
        'skipCleanMoney' => 1,
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
        'skipCleanMoney' => 1,
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
        'skipCleanMoney' => 1,
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
        'skipCleanMoney' => 1,
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
      'api.tag.create' => array(
        'name' => '$value.id',
        'description' => '$value.display_name',
        'format.only_id' => 1,
      ),
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
   * Test long display names.
   *
   * CRM-21258
   */
  public function testContactCreateLongDisplayName() {
    $result = $this->callAPISuccess('Contact', 'Create', array(
      'first_name' => str_pad('a', 64, 'a'),
      'last_name' => str_pad('a', 64, 'a'),
      'contact_type' => 'Individual',
    ));
    $this->assertEquals('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', $result['values'][$result['id']]['display_name']);
    $this->assertEquals('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa, aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', $result['values'][$result['id']]['sort_name']);
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
    $params = array('id' => 17);
    $result = $this->callAPIAndDocument('Contact', 'GetCount', $params, __FUNCTION__, __FILE__, $description,
      'GetCountContact');
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
    $this->callAPISuccess('contact', 'create', $params);
  }

  /**
   * Test that delete with skip undelete respects permissions.
   */
  public function testContactDeletePermissions() {
    $contactID = $this->individualCreate();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM');
    $this->callAPIFailure('Contact', 'delete', array(
      'id' => $contactID,
      'check_permissions' => 1,
      'skip_undelete' => 1,
    ));
    $this->callAPISuccess('Contact', 'delete', array(
      'id' => $contactID,
      'check_permissions' => 0,
      'skip_undelete' => 1,
    ));
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
    $this->assertEquals('Permission denied to modify contact record', $result['error_message']);

    $config->userPermissionClass->permissions = array(
      'access CiviCRM',
      'add contacts',
      'view all contacts',
      'edit all contacts',
      'import contacts',
    );
    $this->callAPISuccess('contact', 'update', $params);
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
   * Test that getquick returns contacts with an exact first name match first.
   *
   * The search string 'b' & 'bob' both return ordered by sort_name if includeOrderByClause
   * is true (default) but if it is false then matches are returned in ID order.
   *
   * @dataProvider getSearchSortOptions
   */
  public function testGetQuickExactFirst($searchParameters, $settings, $firstContact, $secondContact = NULL) {
    $this->getQuickSearchSampleData();
    $this->callAPISuccess('Setting', 'create', $settings);
    $result = $this->callAPISuccess('contact', 'getquick', $searchParameters);
    $this->assertEquals($firstContact, $result['values'][0]['sort_name']);
    $this->assertEquals($secondContact, $result['values'][1]['sort_name']);
    $this->callAPISuccess('Setting', 'create', array('includeWildCardInName' => TRUE, 'includeOrderByClause' => TRUE));
  }

  public function getSearchSortOptions() {
    $firstAlphabeticalContactBySortName = 'A Bobby, Bobby';
    $secondAlphabeticalContactBySortName = 'Aadvark, Bob';
    $secondAlphabeticalContactWithEmailBySortName = 'Bob, Bob';
    $firstAlphabeticalContactFirstNameBob = 'Aadvark, Bob';
    $secondAlphabeticalContactFirstNameBob  = 'Bob, Bob';
    $firstByIDContactFirstNameBob = 'Bob, Bob';
    $secondByIDContactFirstNameBob  = 'K Bobby, Bob';
    $firstContactByID = 'Bob, Bob';
    $secondContactByID = 'E Bobby, Bobby';
    $bobLikeEmail = 'A Bobby, Bobby';

    return array(
      'empty_search_basic' => array(
        'search_parameters' => array('name' => '%'),
        'settings' => array('includeWildCardInName' => TRUE, 'includeOrderByClause' => TRUE),
        'first_contact' => $firstAlphabeticalContactBySortName,
        'second_contact' => $secondAlphabeticalContactBySortName,
      ),
      'empty_search_basic_no_wildcard' => array(
        'search_parameters' => array('name' => '%'),
        'settings' => array('includeWildCardInName' => FALSE, 'includeOrderByClause' => TRUE),
        'first_contact' => $firstAlphabeticalContactBySortName,
        'second_contact' => $secondAlphabeticalContactBySortName,
      ),
      'single_letter_search_basic' => array(
        'search_parameters' => array('name' => 'b'),
        'settings' => array('includeWildCardInName' => TRUE, 'includeOrderByClause' => TRUE),
        'first_contact' => $firstAlphabeticalContactBySortName,
        'second_contact' => $secondAlphabeticalContactBySortName,
      ),
      'bob_search_basic' => array(
        'search_parameters' => array('name' => 'bob'),
        'settings' => array('includeWildCardInName' => TRUE, 'includeOrderByClause' => TRUE),
        'first_contact' => $firstAlphabeticalContactBySortName,
        'second_contact' => $secondAlphabeticalContactBySortName,
      ),
      // This test has been disabled as is proving to be problematic to reproduce due to MySQL sorting issues between different versions
      // 'bob_search_no_orderby' => array(
      //  'search_parameters' => array('name' => 'bob'),
      //  'settings' => array('includeWildCardInName' => TRUE, 'includeOrderByClause' => FALSE),
      //  'first_contact' => $firstContactByID,
      //  'second_contact' => $secondContactByID,
      //),
      'bob_search_no_wildcard' => array(
        'search_parameters' => array('name' => 'bob'),
        'settings' => array('includeWildCardInName' => FALSE, 'includeOrderByClause' => TRUE),
        'second_contact' => $bobLikeEmail,
        'first_contact' => $secondAlphabeticalContactFirstNameBob,
      ),
      // This should be the same as just no wildcard as if we had an exactMatch while searching by
      // sort name it would rise to the top CRM-19547
      'bob_search_no_wildcard_no_orderby' => array(
        'search_parameters' => array('name' => 'bob'),
        'settings' => array('includeWildCardInName' => FALSE, 'includeOrderByClause' => TRUE),
        'second_contact' => $bobLikeEmail,
        'first_contact' => $secondAlphabeticalContactFirstNameBob,
      ),
      'first_name_search_basic' => array(
        'search_parameters' => array('name' => 'bob', 'field_name' => 'first_name'),
        'settings' => array('includeWildCardInName' => TRUE, 'includeOrderByClause' => TRUE),
        'first_contact' => $firstAlphabeticalContactFirstNameBob,
        'second_contact' => $secondAlphabeticalContactFirstNameBob,
      ),
      'first_name_search_no_wildcard' => array(
        'search_parameters' => array('name' => 'bob', 'field_name' => 'first_name'),
        'settings' => array('includeWildCardInName' => FALSE, 'includeOrderByClause' => TRUE),
        'first_contact' => $firstAlphabeticalContactFirstNameBob,
        'second_contact' => $secondAlphabeticalContactFirstNameBob,
      ),
      // This test has been disabled as is proving to be problematic to reproduce due to MySQL sorting issues between different versions
      //'first_name_search_no_orderby' => array(
      //  'search_parameters' => array('name' => 'bob', 'field_name' => 'first_name'),
      //  'settings' => array('includeWildCardInName' => TRUE, 'includeOrderByClause' => FALSE),
      //  'first_contact' => $firstByIDContactFirstNameBob,
      //  'second_contact' => $secondByIDContactFirstNameBob,
      //),
      'email_search_basic' => array(
        'search_parameters' => array('name' => 'bob', 'field_name' => 'email', 'table_name' => 'eml'),
        'settings' => array('includeWildCardInName' => FALSE, 'includeOrderByClause' => TRUE),
        'first_contact' => $firstAlphabeticalContactBySortName,
        'second_contact' => $secondAlphabeticalContactWithEmailBySortName,
      ),
    );
  }

  /**
   * Test that getquick returns contacts with an exact first name match first.
   */
  public function testGetQuickEmail() {
    $this->getQuickSearchSampleData();
    $loggedInContactID = $this->createLoggedInUser();
    $result = $this->callAPISuccess('contact', 'getquick', array(
      'name' => 'c',
    ));
    $expectedData = array(
      'A Bobby, Bobby :: bob@bobby.com',
      'Bob, Bob :: bob@bob.com',
      'C Bobby, Bobby',
      'H Bobby, Bobby :: bob@h.com',
      'Second Domain',
      $this->callAPISuccessGetValue('Contact', array('id' => $loggedInContactID, 'return' => 'last_name')) . ', Logged In :: anthony_anderson@civicrm.org',
    );
    $this->assertEquals(6, $result['count']);
    foreach ($expectedData as $index => $value) {
      $this->assertEquals($value, $result['values'][$index]['data']);
    }
    $result = $this->callAPISuccess('contact', 'getquick', array(
      'name' => 'h.',
    ));
    $expectedData = array(
      'H Bobby, Bobby :: bob@h.com',
    );
    foreach ($expectedData as $index => $value) {
      $this->assertEquals($value, $result['values'][$index]['data']);
    }
    $this->callAPISuccess('Setting', 'create', array('includeWildCardInName' => FALSE));
    $result = $this->callAPISuccess('contact', 'getquick', array(
      'name' => 'h.',
    ));
    $this->callAPISuccess('Setting', 'create', array('includeWildCardInName' => TRUE));
    $this->assertEquals(0, $result['count']);
  }

  /**
   * Test that getquick returns contacts with an exact first name match first.
   */
  public function testGetQuickEmailACL() {
    $this->getQuickSearchSampleData();
    $loggedInContactID = $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array();
    $result = $this->callAPISuccess('contact', 'getquick', array(
      'name' => 'c',
    ));
    $this->assertEquals(0, $result['count']);

    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereNoBobH'));
    CRM_Contact_BAO_Contact_Permission::cache($loggedInContactID, CRM_Core_Permission::VIEW, TRUE);
    $result = $this->callAPISuccess('contact', 'getquick', array(
      'name' => 'c',
    ));

    // Without the acl it would be 6 like the previous email getquick test.
    $this->assertEquals(5, $result['count']);
    $expectedData = array(
      'A Bobby, Bobby :: bob@bobby.com',
      'Bob, Bob :: bob@bob.com',
      'C Bobby, Bobby',
      'Second Domain',
      $this->callAPISuccessGetValue('Contact', array('id' => $loggedInContactID, 'return' => 'last_name')) . ', Logged In :: anthony_anderson@civicrm.org',
    );
    foreach ($expectedData as $index => $value) {
      $this->assertEquals($value, $result['values'][$index]['data']);
    }
  }

  /**
   * Test that getquick returns contacts with an exact first name match first.
   */
  public function testGetQuickExternalID() {
    $this->getQuickSearchSampleData();
    $result = $this->callAPISuccess('contact', 'getquick', array(
      'name' => 'b',
      'field_name' => 'external_identifier',
      'table_name' => 'cc',
    ));
    $this->assertEquals(0, $result['count']);
    $result = $this->callAPISuccess('contact', 'getquick', array(
      'name' => 'abc',
      'field_name' => 'external_identifier',
      'table_name' => 'cc',
    ));
    $this->assertEquals(1, $result['count']);
    $this->assertEquals('Bob, Bob', $result['values'][0]['sort_name']);
  }

  /**
   * Test that getquick returns contacts with an exact first name match first.
   */
  public function testGetQuickID() {
    $max = CRM_Core_DAO::singleValueQuery("SELECT max(id) FROM civicrm_contact");
    $this->getQuickSearchSampleData();
    $result = $this->callAPISuccess('contact', 'getquick', array(
      'name' => $max + 2,
      'field_name' => 'id',
      'table_name' => 'cc',
    ));
    $this->assertEquals(1, $result['count']);
    $this->assertEquals('E Bobby, Bobby', $result['values'][0]['sort_name']);
    $result = $this->callAPISuccess('contact', 'getquick', array(
      'name' => $max + 2,
      'field_name' => 'contact_id',
      'table_name' => 'cc',
    ));
    $this->assertEquals(1, $result['count']);
    $this->assertEquals('E Bobby, Bobby', $result['values'][0]['sort_name']);
  }

  /**
   * Test that getquick returns contacts with an exact first name match first.
   *
   * Depending on the setting the sort name sort might click in next or not - test!
   */
  public function testGetQuickFirstName() {
    $this->getQuickSearchSampleData();
    $this->callAPISuccess('Setting', 'create', array('includeOrderByClause' => TRUE));
    $result = $this->callAPISuccess('contact', 'getquick', array(
      'name' => 'Bob',
      'field_name' => 'first_name',
      'table_name' => 'cc',
    ));
    $expected = array(
      'Aadvark, Bob',
      'Bob, Bob',
      'K Bobby, Bob',
      'A Bobby, Bobby',
    );

    foreach ($expected as $index => $value) {
      $this->assertEquals($value, $result['values'][$index]['sort_name']);
    }
    $this->callAPISuccess('Setting', 'create', array('includeOrderByClause' => FALSE));
    $result = $this->callAPISuccess('contact', 'getquick', array('name' => 'bob'));
    $this->assertEquals('Bob, Bob', $result['values'][0]['sort_name']);
    // This test has been disabled as is proving to be problematic to reproduce due to MySQL sorting issues between different versions
    //$this->assertEquals('E Bobby, Bobby', $result['values'][1]['sort_name']);
  }

  /**
   * Test that getquick applies ACLs.
   */
  public function testGetQuickFirstNameACLs() {
    $this->getQuickSearchSampleData();
    $userID = $this->createLoggedInUser();
    $this->callAPISuccess('Setting', 'create', array('includeOrderByClause' => TRUE, 'search_autocomplete_count' => 15));
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array();
    $result = $this->callAPISuccess('contact', 'getquick', array(
      'name' => 'Bob',
      'field_name' => 'first_name',
      'table_name' => 'cc',
    ));
    $this->assertEquals(0, $result['count']);

    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereNoBobH'));
    CRM_Contact_BAO_Contact_Permission::cache($userID, CRM_Core_Permission::VIEW, TRUE);
    $result = $this->callAPISuccess('contact', 'getquick', array(
      'name' => 'Bob',
      'field_name' => 'first_name',
      'table_name' => 'cc',
    ));
    $this->assertEquals('K Bobby, Bob', $result['values'][2]['sort_name']);
    // Without the ACL 9 would be bob@h.com.
    $this->assertEquals('I Bobby, Bobby', $result['values'][10]['sort_name']);
  }

  /**
   * Full results returned.
   * @implements CRM_Utils_Hook::aclWhereClause
   *
   * @param string $type
   * @param array $tables
   * @param array $whereTables
   * @param int $contactID
   * @param string $where
   */
  public function aclWhereNoBobH($type, &$tables, &$whereTables, &$contactID, &$where) {
    $where = " (email <> 'bob@h.com' OR email IS NULL) ";
    $whereTables['civicrm_email'] = "LEFT JOIN civicrm_email e ON contact_a.id = e.contact_id";
  }

  /**
   * Test that getquick returns contacts with an exact last name match first.
   */
  public function testGetQuickLastName() {
    $this->getQuickSearchSampleData();
    $this->callAPISuccess('Setting', 'create', array('includeOrderByClause' => TRUE));
    $result = $this->callAPISuccess('contact', 'getquick', array(
      'name' => 'Bob',
      'field_name' => 'last_name',
      'table_name' => 'cc',
    ));
    $expected = array(
      'Bob, Bob',
      'A Bobby, Bobby',
      'B Bobby, Bobby',
    );

    foreach ($expected as $index => $value) {
      $this->assertEquals($value, $result['values'][$index]['sort_name']);
    }
    $this->callAPISuccess('Setting', 'create', array('includeOrderByClause' => FALSE));
    $result = $this->callAPISuccess('contact', 'getquick', array('name' => 'bob'));
    $this->assertEquals('Bob, Bob :: bob@bob.com', $result['values'][0]['data']);
  }

  /**
   * Test that getquick returns contacts by city.
   */
  public function testGetQuickCity() {
    $this->getQuickSearchSampleData();
    $result = $this->callAPISuccess('contact', 'getquick', array(
      'name' => 'o',
      'field_name' => 'city',
      'table_name' => 'sts',
    ));
    $this->assertEquals('B Bobby, Bobby :: Toronto', $result['values'][0]['data']);
    $result = $this->callAPISuccess('contact', 'getquick', array(
      'name' => 'n',
      'field_name' => 'city',
      'table_name' => 'sts',
    ));
    $this->assertEquals('B Bobby, Bobby :: Toronto', $result['values'][0]['data']);
    $this->assertEquals('C Bobby, Bobby :: Whanganui', $result['values'][1]['data']);
  }

  /**
   * Set up some sample data for testing quicksearch.
   */
  public function getQuickSearchSampleData() {
    $contacts = array(
      array('first_name' => 'Bob', 'last_name' => 'Bob', 'external_identifier' => 'abc', 'email' => 'bob@bob.com'),
      array('first_name' => 'Bobby', 'last_name' => 'E Bobby', 'external_identifier' => 'abcd'),
      array(
        'first_name' => 'Bobby',
        'last_name' => 'B Bobby',
        'external_identifier' => 'bcd',
        'api.address.create' => array(
          'street_address' => 'Sesame Street',
          'city' => 'Toronto',
          'location_type_id' => 1,
        ),
      ),
      array(
        'first_name' => 'Bobby',
        'last_name' => 'C Bobby',
        'external_identifier' => 'bcde',
        'api.address.create' => array(
          'street_address' => 'Te huarahi',
          'city' => 'Whanganui',
          'location_type_id' => 1,
        ),
      ),
      array('first_name' => 'Bobby', 'last_name' => 'D Bobby', 'external_identifier' => 'efg'),
      array('first_name' => 'Bobby', 'last_name' => 'A Bobby', 'external_identifier' => 'hij', 'email' => 'bob@bobby.com'),
      array('first_name' => 'Bobby', 'last_name' => 'F Bobby', 'external_identifier' => 'klm'),
      array('first_name' => 'Bobby', 'last_name' => 'G Bobby', 'external_identifier' => 'nop'),
      array('first_name' => 'Bobby', 'last_name' => 'H Bobby', 'external_identifier' => 'qrs', 'email' => 'bob@h.com'),
      array('first_name' => 'Bobby', 'last_name' => 'I Bobby'),
      array('first_name' => 'Bobby', 'last_name' => 'J Bobby'),
      array('first_name' => 'Bob', 'last_name' => 'K Bobby', 'external_identifier' => 'bcdef'),
      array('first_name' => 'Bob', 'last_name' => 'Aadvark'),
    );
    foreach ($contacts as $type => $contact) {
      $contact['contact_type'] = 'Individual';
      $this->callAPISuccess('Contact', 'create', $contact);
    }
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

  /**
   * Test the use of sql operators.
   */
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
   * CRM-15443 - ensure getlist api does not return deleted contacts.
   */
  public function testGetlistExcludeConditions() {
    $name = 'Scarabée';
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

  /**
   * Test the duplicate check function.
   */
  public function testDuplicateCheck() {
    $harry = array(
      'first_name' => 'Harry',
      'last_name' => 'Potter',
      'email' => 'harry@hogwarts.edu',
      'contact_type' => 'Individual',
    );
    $this->callAPISuccess('Contact', 'create', $harry);
    $result = $this->callAPISuccess('Contact', 'duplicatecheck', array(
      'match' => $harry,
    ));

    $this->assertEquals(1, $result['count']);
    $result = $this->callAPISuccess('Contact', 'duplicatecheck', array(
      'match' => array(
        'first_name' => 'Harry',
        'last_name' => 'Potter',
        'email' => 'no5@privet.drive',
        'contact_type' => 'Individual',
      ),
    ));
    $this->assertEquals(0, $result['count']);
    $this->callAPIFailure('Contact', 'create', array_merge($harry, array('dupe_check' => 1)));
  }

  /**
   * Test the duplicate check function.
   */
  public function testDuplicateCheckRuleNotReserved() {
    $harry = array(
      'first_name' => 'Harry',
      'last_name' => 'Potter',
      'email' => 'harry@hogwarts.edu',
      'contact_type' => 'Individual',
    );
    $defaultRule = $this->callAPISuccess('RuleGroup', 'getsingle', array('used' => 'Unsupervised', 'is_reserved' => 1));
    $this->callAPISuccess('RuleGroup', 'create', array('id' => $defaultRule['id'], 'is_reserved' => 0));
    $this->callAPISuccess('Contact', 'create', $harry);
    $result = $this->callAPISuccess('Contact', 'duplicatecheck', array(
      'match' => $harry,
    ));

    $this->assertEquals(1, $result['count']);
    $this->callAPISuccess('RuleGroup', 'create', array('id' => $defaultRule['id'], 'is_reserved' => 1));
  }

  /**
   * Test variants on retrieving contact by type.
   */
  public function testGetByContactType() {
    $individual = $this->callAPISuccess('Contact', 'create', array(
      'email' => 'individual@test.com',
      'contact_type' => 'Individual',
    ));
    $household = $this->callAPISuccess('Contact', 'create', array(
      'household_name' => 'household@test.com',
      'contact_type' => 'Household',
    ));
    $organization = $this->callAPISuccess('Contact', 'create', array(
      'organization_name' => 'organization@test.com',
      'contact_type' => 'Organization',
    ));
    // Test with id - getsingle will throw an exception if not found
    $this->callAPISuccess('Contact', 'getsingle', array(
      'id' => $individual['id'],
      'contact_type' => 'Individual',
    ));
    $this->callAPISuccess('Contact', 'getsingle', array(
      'id' => $individual['id'],
      'contact_type' => array('IN' => array('Individual')),
      'return' => 'id',
    ));
    $this->callAPISuccess('Contact', 'getsingle', array(
      'id' => $organization['id'],
      'contact_type' => array('IN' => array('Individual', 'Organization')),
    ));
    // Test as array
    $result = $this->callAPISuccess('Contact', 'get', array(
      'contact_type' => array('IN' => array('Individual', 'Organization')),
      'options' => array('limit' => 0),
      'return' => 'id',
    ));
    $this->assertContains($organization['id'], array_keys($result['values']));
    $this->assertContains($individual['id'], array_keys($result['values']));
    $this->assertNotContains($household['id'], array_keys($result['values']));
    // Test as string
    $result = $this->callAPISuccess('Contact', 'get', array(
      'contact_type' => 'Household',
      'options' => array('limit' => 0),
      'return' => 'id',
    ));
    $this->assertNotContains($organization['id'], array_keys($result['values']));
    $this->assertNotContains($individual['id'], array_keys($result['values']));
    $this->assertContains($household['id'], array_keys($result['values']));
  }

  /**
   * Test merging 2 contacts.
   *
   * Someone kindly bequethed us the legacy of mixed up use of main_id & other_id
   * in the params for contact.merge api.
   *
   * This test protects that legacy.
   */
  public function testMergeBizzareOldParams() {
    $this->createLoggedInUser();
    $otherContact = $this->callAPISuccess('contact', 'create', $this->_params);
    $mainContact = $this->callAPISuccess('contact', 'create', $this->_params);
    $this->callAPISuccess('contact', 'merge', array(
      'main_id' => $mainContact['id'],
      'other_id' => $otherContact['id'],
    ));
    $contacts = $this->callAPISuccess('contact', 'get', $this->_params);
    $this->assertEquals($otherContact['id'], $contacts['id']);
  }

  /**
   * Test merging 2 contacts.
   */
  public function testMerge() {
    $this->createLoggedInUser();
    $otherContact = $this->callAPISuccess('contact', 'create', $this->_params);
    $retainedContact = $this->callAPISuccess('contact', 'create', $this->_params);
    $this->callAPISuccess('contact', 'merge', array(
      'to_keep_id' => $retainedContact['id'],
      'to_remove_id' => $otherContact['id'],
      'auto_flip' => FALSE,
    ));

    $contacts = $this->callAPISuccess('contact', 'get', $this->_params);
    $this->assertEquals($retainedContact['id'], $contacts['id']);
    $activity = $this->callAPISuccess('Activity', 'getsingle', array(
      'target_contact_id' => $retainedContact['id'],
      'activity_type_id' => 'Contact Merged',
    ));
    $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($activity['activity_date_time'])));
    $activity2 = $this->callAPISuccess('Activity', 'getsingle', array(
      'target_contact_id' => $otherContact['id'],
      'activity_type_id' => 'Contact Deleted by Merge',
    ));
    $this->assertEquals($activity['id'], $activity2['parent_id']);
    $this->assertEquals('Normal', civicrm_api3('option_value', 'getvalue', array(
      'value' => $activity['priority_id'],
      'return' => 'label',
      'option_group_id' => 'priority',
    )));

  }

  /**
   * Test retrieving merged contacts.
   *
   * The goal here is to start with a contact deleted by merged and find out the contact that is the current version of them.
   */
  public function testMergedGet() {
    $this->contactIDs[] = $this->individualCreate();
    $this->contactIDs[] = $this->individualCreate();
    $this->contactIDs[] = $this->individualCreate();
    $this->contactIDs[] = $this->individualCreate();

    // First do an 'unnatural merge' - they 'like to merge into the lowest but this will mean that contact 0 merged to contact [3].
    // When the batch merge runs.... the new lowest contact is contact[1]. All contacts will merge into that contact,
    // including contact[3], resulting in only 3 existing at the end. For each contact the correct answer to 'who did I eventually
    // wind up being should be [1]
    $this->callAPISuccess('Contact', 'merge', ['to_remove_id' => $this->contactIDs[0], 'to_keep_id' => $this->contactIDs[3]]);

    $this->callAPISuccess('Job', 'process_batch_merge', []);
    foreach ($this->contactIDs as $contactID) {
      if ($contactID === $this->contactIDs[1]) {
        continue;
      }
      $result = $this->callAPIAndDocument('Contact', 'getmergedto', ['sequential' => 1, 'contact_id' => $contactID], __FUNCTION__, __FILE__);
      $this->assertEquals(1, $result['count']);
      $this->assertEquals($this->contactIDs[1], $result['values'][0]['id']);
    }

    $result = $this->callAPIAndDocument('Contact', 'getmergedfrom', ['contact_id' => $this->contactIDs[1]], __FUNCTION__, __FILE__)['values'];
    $mergedContactIds = array_merge(array_diff($this->contactIDs, [$this->contactIDs[1]]));
    $this->assertEquals($mergedContactIds, array_keys($result));
  }

  /**
   * Test merging 2 contacts with delete to trash off.
   *
   * We are checking that there is no error due to attempting to add an activity for the
   * deleted contact.
   *
   * CRM-18307
   */
  public function testMergeNoTrash() {
    $this->createLoggedInUser();
    $this->callAPISuccess('Setting', 'create', array('contact_undelete' => FALSE));
    $otherContact = $this->callAPISuccess('contact', 'create', $this->_params);
    $retainedContact = $this->callAPISuccess('contact', 'create', $this->_params);
    $this->callAPISuccess('contact', 'merge', array(
      'to_keep_id' => $retainedContact['id'],
      'to_remove_id' => $otherContact['id'],
      'auto_flip' => FALSE,
    ));
    $this->callAPISuccess('Setting', 'create', array('contact_undelete' => TRUE));
  }

  /**
   * Ensure format with return=group shows comma-separated group IDs.
   *
   * CRM-19426
   */
  public function testContactGetReturnGroup() {
    // Set up a contact, asser that they were created.
    $contact_params = array(
      'contact_type' => 'Individual',
      'first_name' => 'Test',
      'last_name' => 'Groupmember',
      'email' => 'test@example.org',
    );
    $create_contact = $this->callApiSuccess('Contact', 'create', $contact_params);
    $this->assertEquals(0, $create_contact['is_error']);
    $this->assertInternalType('int', $create_contact['id']);

    $created_contact_id = $create_contact['id'];

    // Set up multiple groups, add the contact to the groups.
    $test_groups = array('Test group A', 'Test group B');
    foreach ($test_groups as $title) {
      // Use this contact as group owner, since we know they exist.
      $group_params = array(
        'title' => $title,
        'created_id' => $created_contact_id,
      );
      $create_group = $this->callApiSuccess('Group', 'create', $group_params);
      $this->assertEquals(0, $create_group['is_error']);
      $this->assertInternalType('int', $create_group['id']);

      $created_group_ids[] = $create_group['id'];

      // Add contact to the new group.
      $group_contact_params = array(
        'contact_id' => $created_contact_id,
        'group_id' => $create_group['id'],
      );
      $create_group_contact = $this->callApiSuccess('GroupContact', 'create', $group_contact_params);
      $this->assertEquals(0, $create_group_contact['is_error']);
      $this->assertInternalType('int', $create_group_contact['added']);
    }

    // Use the Contact,get API to retrieve the contact
    $contact_get_params = array(
      'id' => $created_contact_id,
      'return' => 'group',
    );
    $contact_get = $this->callApiSuccess('Contact', 'get', $contact_get_params);
    $this->assertInternalType('array', $contact_get['values'][$created_contact_id]);
    $this->assertInternalType('string', $contact_get['values'][$created_contact_id]['groups']);

    // Ensure they are shown as being in each created group.
    $contact_group_ids = explode(',', $contact_get['values'][$created_contact_id]['groups']);
    foreach ($created_group_ids as $created_group_id) {
      $this->assertContains($created_group_id, $contact_group_ids);
    }
  }

  /**
   * CRM-20144 Verify that passing title of group works as well as id
   * Tests the following formats
   * contact.get group='title1'
   * contact.get group=id1
   */
  public function testContactGetWithGroupTitle() {
    // Set up a contact, asser that they were created.
    $contact_params = array(
      'contact_type' => 'Individual',
      'first_name' => 'Test2',
      'last_name' => 'Groupmember',
      'email' => 'test@example.org',
    );
    $create_contact = $this->callApiSuccess('Contact', 'create', $contact_params);
    $created_contact_id = $create_contact['id'];
    // Set up multiple groups, add the contact to the groups.
    $test_groups = array('Test group C', 'Test group D');
    foreach ($test_groups as $title) {
      $group_params = array(
        'title' => $title,
        'created_id' => $created_contact_id,
      );
      $create_group = $this->callApiSuccess('Group', 'create', $group_params);
      $created_group_id = $create_group['id'];

      // Add contact to the new group.
      $group_contact_params = array(
        'contact_id' => $created_contact_id,
        'group_id' => $create_group['id'],
      );
      $this->callApiSuccess('GroupContact', 'create', $group_contact_params);
      $contact_get = $this->callAPISuccess('contact', 'get', array('group' => $title, 'return' => 'group'));
      $this->assertEquals(1, $contact_get['count']);
      $this->assertEquals($created_contact_id, $contact_get['id']);
      $contact_groups = explode(',', $contact_get['values'][$created_contact_id]['groups']);
      $this->assertContains((string) $create_group['id'], $contact_groups);
      $contact_get2 = $this->callAPISuccess('contact', 'get', array('group' => $created_group_id, 'return' => 'group'));
      $this->assertEquals($created_contact_id, $contact_get2['id']);
      $contact_groups2 = explode(',', $contact_get2['values'][$created_contact_id]['groups']);
      $this->assertContains((string) $create_group['id'], $contact_groups2);
      $this->callAPISuccess('group', 'delete', array('id' => $created_group_id));
    }
    $this->callAPISuccess('contact', 'delete', array('id' => $created_contact_id, 'skip_undelete' => TRUE));
  }

  /**
   * CRM-20144 Verify that passing title of group works as well as id
   * Tests the following formats
   * contact.get group=array('title1', title1)
   * contact.get group=array('IN' => array('title1', 'title2)
   */
  public function testContactGetWithGroupTitleMultipleGroups() {
    $description = "Get all from group and display contacts.";
    $subFile = "GroupFilterUsingContactAPI";
    // Set up a contact, asser that they were created.
    $contact_params = array(
      'contact_type' => 'Individual',
      'first_name' => 'Test2',
      'last_name' => 'Groupmember',
      'email' => 'test@example.org',
    );
    $create_contact = $this->callApiSuccess('Contact', 'create', $contact_params);
    $created_contact_id = $create_contact['id'];
    $createdGroupsTitles = $createdGroupsIds = array();
    // Set up multiple groups, add the contact to the groups.
    $test_groups = array('Test group C', 'Test group D');
    foreach ($test_groups as $title) {
      $group_params = array(
        'title' => $title,
        'created_id' => $created_contact_id,
      );
      $create_group = $this->callApiSuccess('Group', 'create', $group_params);
      $created_group_id = $create_group['id'];
      $createdGroupsIds[] = $create_group['id'];
      $createdGroupTitles[] = $title;
      // Add contact to the new group.
      $group_contact_params = array(
        'contact_id' => $created_contact_id,
        'group_id' => $create_group['id'],
      );
      $create_group_contact = $this->callApiSuccess('GroupContact', 'create', $group_contact_params);
    }
    $contact_get = $this->callAPISuccess('contact', 'get', array('group' => $createdGroupTitles, 'return' => 'group'));
    $this->assertEquals(1, $contact_get['count']);
    $this->assertEquals($created_contact_id, $contact_get['id']);
    $contact_groups = explode(',', $contact_get['values'][$created_contact_id]['groups']);
    foreach ($createdGroupsIds as $id) {
      $this->assertContains((string) $id, $contact_groups);
    }
    $contact_get2 = $this->callAPIAndDocument('contact', 'get', array('group' => array('IN' => $createdGroupTitles)), __FUNCTION__, __FILE__, $description, $subFile);
    $contact_get2 = $this->callAPISuccess('contact', 'get', array('group' => array('IN' => $createdGroupTitles), 'return' => 'group'));
    $this->assertEquals($created_contact_id, $contact_get2['id']);
    $contact_groups2 = explode(',', $contact_get2['values'][$created_contact_id]['groups']);
    foreach ($createdGroupsIds as $id) {
      $this->assertContains((string) $id, $contact_groups2);
    }
    foreach ($createdGroupsIds as $id) {
      $this->callAPISuccess('group', 'delete', array('id' => $id));
    }
    $this->callAPISuccess('contact', 'delete', array('id' => $created_contact_id, 'skip_undelete' => TRUE));
  }

  /**
   * CRM-20144 Verify that passing title of group works as well as id
   * Tests the following formats
   * contact.get group=array('title1' => 1)
   * contact.get group=array('titke1' => 1, 'title2' => 1)
   * contact.get group=array('id1' => 1)
   * contact.get group=array('id1' => 1, id2 => 1)
   */
  public function testContactGetWithGroupTitleMultipleGroupsLegacyFormat() {
    // Set up a contact, asser that they were created.
    $contact_params = array(
      'contact_type' => 'Individual',
      'first_name' => 'Test2',
      'last_name' => 'Groupmember',
      'email' => 'test@example.org',
    );
    $create_contact = $this->callApiSuccess('Contact', 'create', $contact_params);
    $created_contact_id = $create_contact['id'];
    $createdGroupsTitles = $createdGroupsIds = array();
    // Set up multiple groups, add the contact to the groups.
    $test_groups = array('Test group C', 'Test group D');
    foreach ($test_groups as $title) {
      $group_params = array(
        'title' => $title,
        'created_id' => $created_contact_id,
      );
      $create_group = $this->callApiSuccess('Group', 'create', $group_params);
      $created_group_id = $create_group['id'];
      $createdGroupsIds[] = $create_group['id'];
      $createdGroupTitles[] = $title;
      // Add contact to the new group.
      $group_contact_params = array(
        'contact_id' => $created_contact_id,
        'group_id' => $create_group['id'],
      );
      $create_group_contact = $this->callApiSuccess('GroupContact', 'create', $group_contact_params);
    }
    $contact_get = $this->callAPISuccess('contact', 'get', array('group' => array($createdGroupTitles[0] => 1), 'return' => 'group'));
    $this->assertEquals(1, $contact_get['count']);
    $this->assertEquals($created_contact_id, $contact_get['id']);
    $contact_groups = explode(',', $contact_get['values'][$created_contact_id]['groups']);
    foreach ($createdGroupsIds as $id) {
      $this->assertContains((string) $id, $contact_groups);
    }
    $contact_get2 = $this->callAPISuccess('contact', 'get', array('group' => array($createdGroupTitles[0] => 1, $createdGroupTitles[1] => 1), 'return' => 'group'));
    $this->assertEquals(1, $contact_get2['count']);
    $this->assertEquals($created_contact_id, $contact_get2['id']);
    $contact_groups2 = explode(',', $contact_get2['values'][$created_contact_id]['groups']);
    foreach ($createdGroupsIds as $id) {
      $this->assertContains((string) $id, $contact_groups2);
    }
    $contact_get3 = $this->callAPISuccess('contact', 'get', array('group' => array($createdGroupsIds[0] => 1), 'return' => 'group'));
    $this->assertEquals($created_contact_id, $contact_get3['id']);
    $contact_groups3 = explode(',', $contact_get3['values'][$created_contact_id]['groups']);
    foreach ($createdGroupsIds as $id) {
      $this->assertContains((string) $id, $contact_groups3);
    }
    $contact_get4 = $this->callAPISuccess('contact', 'get', array('group' => array($createdGroupsIds[0] => 1, $createdGroupsIds[1] => 1), 'return' => 'group'));
    $this->assertEquals($created_contact_id, $contact_get4['id']);
    $contact_groups4 = explode(',', $contact_get4['values'][$created_contact_id]['groups']);
    foreach ($createdGroupsIds as $id) {
      $this->assertContains((string) $id, $contact_groups4);
    }
    foreach ($createdGroupsIds as $id) {
      $this->callAPISuccess('group', 'delete', array('id' => $id));
    }
    $this->callAPISuccess('contact', 'delete', array('id' => $created_contact_id, 'skip_undelete' => TRUE));
  }

  /**
   * Test the prox_distance functionality works.
   *
   * This is primarily testing functionality in the BAO_Query object that 'happens to be'
   * accessible via the api.
   */
  public function testContactGetProximity() {
    CRM_Core_Config::singleton()->geocodeMethod = 'CRM_Utils_MockGeocoder';
    $this->individualCreate();
    $contactID = $this->individualCreate();
    $this->callAPISuccess('Address', 'create', [
      'contact_id' => $contactID,
      'is_primary' => 1,
      'city' => 'Whangarei',
      'street_address' => 'Dent St',
      'geo_code_1' => '-35.8743325',
      'geo_code_2' => '174.4567136',
      'location_type_id' => 'Home',
    ]);
    $contact = $this->callAPISuccess('Contact', 'get', [
      'prox_distance' => 100,
      'prox_geo_code_1' => '-35.72192',
      'prox_geo_code_2' => '174.32034',
    ]);
    $this->assertEquals(1, $contact['count']);
    $this->assertEquals($contactID, $contact['id']);
  }

  public function testLoggedInUserAPISupportToken() {
    $description = "Get contact id of the current logged in user";
    $subFile = "ContactIDOfLoggedInUserContactAPI";
    $cid = $this->createLoggedInUser();
    $contact = $this->callAPIAndDocument('contact', 'get', array('id' => 'user_contact_id'), __FUNCTION__, __FILE__, $description, $subFile);
    $this->assertEquals($cid, $contact['id']);
  }

  /**
   * @param $groupID
   * @param $contact
   */
  protected function putGroupContactCacheInClearableState($groupID, $contact) {
    // We need to force the situation where there is invalid data in the cache and it
    // is due to be cleared.
    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_group_contact_cache (group_id, contact_id)
      VALUES ({$groupID}, {$contact['id']})
    ");
    CRM_Core_DAO::executeQuery("UPDATE civicrm_group SET cache_date = '2017-01-01'");
    // Reset so it does not skip.
    Civi::$statics['CRM_Contact_BAO_GroupContactCache']['is_refresh_init'] = FALSE;
  }

  /**
   * CRM-21041 Test if 'communication style' is set to site default if not passed.
   */
  public function testCreateCommunicationStyleUnset() {
    $this->callAPISuccess('Contact', 'create', array(
      'first_name' => 'John',
      'last_name' => 'Doe',
      'contact_type' => 'Individual')
    );
    $result = $this->callAPISuccessGetSingle('Contact', array('last_name' => 'Doe'));
    $this->assertEquals(1, $result['communication_style_id']);
  }

  /**
   * CRM-21041 Test if 'communication style' is set if value is passed.
   */
  public function testCreateCommunicationStylePassed() {
    $this->callAPISuccess('Contact', 'create', array(
      'first_name' => 'John',
      'last_name' => 'Doe',
      'contact_type' => 'Individual',
      'communication_style_id' => 'Familiar',
    ));
    $result = $this->callAPISuccessGetSingle('Contact', array('last_name' => 'Doe'));
    $params = array(
      'option_group_id' => 'communication_style',
      'label' => 'Familiar',
      'return' => 'value',
    );
    $optionResult = civicrm_api3('OptionValue', 'get', $params);
    $communicationStyle = reset($optionResult['values']);
    $this->assertEquals($communicationStyle['value'], $result['communication_style_id']);
  }

  /**
   * Test that creating a contact with various contact greetings works.
   */
  public function testContactGreetingsCreate() {
    $contact = $this->callAPISuccess('Contact', 'create', array('first_name' => 'Alan', 'last_name' => 'MouseMouse', 'contact_type' => 'Individual'));
    $contact = $this->callAPISuccessGetSingle('Contact', array('id' => $contact['id'], 'return' => 'postal_greeting'));
    $this->assertEquals('Dear Alan', $contact['postal_greeting_display']);

    $contact = $this->callAPISuccess('Contact', 'create', array('id' => $contact['id'], 'postal_greeting_id' => 2));
    $contact = $this->callAPISuccessGetSingle('Contact', array('id' => $contact['id'], 'return' => 'postal_greeting'));
    $this->assertEquals('Dear Alan MouseMouse', $contact['postal_greeting_display']);

    $contact = $this->callAPISuccess('Contact', 'create', array('organization_name' => 'Alan\'s Show', 'contact_type' => 'Organization'));
    $contact = $this->callAPISuccessGetSingle('Contact', array('id' => $contact['id'], 'return' => 'postal_greeting, addressee, email_greeting'));
    $this->assertEquals('', $contact['postal_greeting_display']);
    $this->assertEquals('', $contact['email_greeting_display']);
    $this->assertEquals('Alan\'s Show', $contact['addressee_display']);
  }

  /**
   * Test that creating a contact with various contact greetings works.
   */
  public function testContactGreetingsCreateWithCustomField() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $contact = $this->callAPISuccess('Contact', 'create', array('first_name' => 'Alan', 'contact_type' => 'Individual', 'custom_' . $ids['custom_field_id'] => 'Mice'));

    // Change postal greeting to involve a custom field.
    $postalOption = $this->callAPISuccessGetSingle('OptionValue', array('option_group_id' => 'postal_greeting', 'filter' => 1, 'is_default' => 1));
    $this->callAPISuccess('OptionValue', 'create', array(
      'id' => $postalOption['id'],
      'name' => 'Dear {contact.first_name} {contact.custom_' . $ids['custom_field_id'] . '}',
      'label' => 'Dear {contact.first_name} {contact.custom_' . $ids['custom_field_id'] . '}',
    ));

    // Update contact & see if postal greeting now reflects the new string.
    $this->callAPISuccess('Contact', 'create', array('id' => $contact['id'], 'last_name' => 'MouseyMousey'));
    $contact = $this->callAPISuccessGetSingle('Contact', array('id' => $contact['id'], 'return' => 'postal_greeting'));
    $this->assertEquals('Dear Alan Mice', $contact['postal_greeting_display']);

    // Set contact to have no postal greeting & check it is correct.
    $this->callAPISuccess('Contact', 'create', array('id' => $contact['id'], 'postal_greeting_id' => 'null'));
    $contact = $this->callAPISuccessGetSingle('Contact', array('id' => $contact['id'], 'return' => 'postal_greeting'));
    $this->assertEquals('', $contact['postal_greeting_display']);

    //Cleanup
    $this->callAPISuccess('OptionValue', 'create', array('id' => $postalOption['id'], 'name' => 'Dear {contact.first_name}'));
    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   * Test getunique api call for Contact entity
   */
  public function testContactGetUnique() {
    $result = $this->callAPIAndDocument($this->_entity, 'getunique', array(), __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertEquals(array('external_identifier'), $result['values']['UI_external_identifier']);
  }

  /**
   * API test to retrieve contact from group having different group title and name.
   */
  public function testContactGetFromGroup() {
    $groupId = $this->groupCreate([
      'name' => 'Test_Group',
      'domain_id' => 1,
      'title' => 'New Test Group Created',
      'description' => 'New Test Group Created',
      'is_active' => 1,
      'visibility' => 'User and User Admin Only',
    ]);
    $contact = $this->callAPISuccess('contact', 'create', $this->_params);
    $groupContactCreateParams = array(
      'contact_id' => $contact['id'],
      'group_id' => $groupId,
      'status' => 'Pending',
    );
    $groupContact = $this->callAPISuccess('groupContact', 'create', $groupContactCreateParams);
    $groupGetContact = $this->CallAPISuccess('groupContact', 'get', $groupContactCreateParams);
    $this->CallAPISuccess('Contact', 'getcount', [
      'group' => "Test_Group",
    ]);
  }

  public function testSmartGroupsForRelatedContacts() {
    $rtype1 = $this->callAPISuccess('relationship_type', 'create', array(
      "name_a_b" => uniqid() . " Child of",
      "name_b_a" => uniqid() . " Parent of",
    ));
    $rtype2 = $this->callAPISuccess('relationship_type', 'create', array(
      "name_a_b" => uniqid() . " Household Member of",
      "name_b_a" => uniqid() . " Household Member is",
    ));
    $h1 = $this->householdCreate();
    $c1 = $this->individualCreate(array('last_name' => 'Adams'));
    $c2 = $this->individualCreate(array('last_name' => 'Adams'));
    $this->callAPISuccess('relationship', 'create', array(
      'contact_id_a' => $c1,
      'contact_id_b' => $c2,
      'is_active' => 1,
      'relationship_type_id' => $rtype1['id'], // Child of
    ));
    $this->callAPISuccess('relationship', 'create', array(
      'contact_id_a' => $c1,
      'contact_id_b' => $h1,
      'is_active' => 1,
      'relationship_type_id' => $rtype2['id'], // Household Member of
    ));
    $this->callAPISuccess('relationship', 'create', array(
      'contact_id_a' => $c2,
      'contact_id_b' => $h1,
      'is_active' => 1,
      'relationship_type_id' => $rtype2['id'], // Household Member of
    ));

    $ssParams = array(
      'formValues' => array(
        'display_relationship_type' => $rtype1['id'] . '_a_b', // Child of
        'sort_name' => 'Adams',
      ),
    );
    $g1ID = $this->smartGroupCreate($ssParams, array('name' => uniqid(), 'title' => uniqid()));
    $ssParams = array(
      'formValues' => array(
        'display_relationship_type' => $rtype2['id'] . '_a_b', // Household Member of
      ),
    );
    $g2ID = $this->smartGroupCreate($ssParams, array('name' => uniqid(), 'title' => uniqid()));
    $ssParams = array(
      'formValues' => array(
        'display_relationship_type' => $rtype2['id'] . '_b_a', // Household Member is
      ),
    );
    // the reverse of g2 which adds another layer for overlap at related contact filter
    $g3ID = $this->smartGroupCreate($ssParams, array('name' => uniqid(), 'title' => uniqid()));
    CRM_Contact_BAO_GroupContactCache::loadAll();
    $g1Contacts = $this->callAPISuccess('contact', 'get', array('group' => $g1ID));
    $g2Contacts = $this->callAPISuccess('contact', 'get', array('group' => $g2ID));
    $g3Contacts = $this->callAPISuccess('contact', 'get', array('group' => $g3ID));
    $this->assertTrue($g1Contacts['count'] == 1);
    $this->assertTrue($g2Contacts['count'] == 2);
    $this->assertTrue($g3Contacts['count'] == 1);
  }

  /**
   * Test creating a note from the contact.create API call when only passing the note as a string.
   */
  public function testCreateNoteinCreate() {
    $loggedInContactID = $this->createLoggedInUser();
    $this->_params['note'] = "Test note created by API Call as a String";
    $contact = $this->callAPISuccess('Contact', 'create', $this->_params);
    $note = $this->callAPISuccess('Note', 'get', ['contact_id' => $loggedInContactID]);
    $this->assertEquals($note['values'][$note['id']]['note'], "Test note created by API Call as a String");
    $note = $this->callAPISuccess('Note', 'get', ['entity_id' => $contact['id']]);
    $this->assertEquals($note['values'][$note['id']]['note'], "Test note created by API Call as a String");
    $this->callAPISuccess('Contact', 'delete', ['id' => $contact['id'], 'skip_undelete' => TRUE]);
  }

  /**
   * Test Creating a note from the contact.create api call when passing the note params as an array.
   */
  public function testCreateNoteinCreateArrayFormat() {
    $contact1 = $this->callAPISuccess('Contact', 'create', array('first_name' => 'Alan', 'last_name' => 'MouseMouse', 'contact_type' => 'Individual'));
    $this->_params['note'] = [['note' => "Test note created by API Call as array", 'contact_id' => $contact1['id']]];
    $contact2 = $this->callAPISuccess('Contact', 'create', $this->_params);
    $note = $this->callAPISuccess('Note', 'get', ['contact_id' => $contact1['id']]);
    $this->assertEquals($note['values'][$note['id']]['note'], "Test note created by API Call as array");
    $note = $this->callAPISuccess('Note', 'get', ['entity_id' => $contact2['id']]);
    $this->assertEquals($note['values'][$note['id']]['note'], "Test note created by API Call as array");
  }

  /**
   * Verify that passing tag IDs to Contact.get works
   *
   * Tests the following formats
   * - Contact.get tag='id1'
   * - Contact.get tag='id1,id2'
   * - Contact.get tag='id1, id2'
   */
  public function testContactGetWithTag() {
    $contact = $this->callApiSuccess('Contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => 'Test',
      'last_name' => 'Tagged',
      'email' => 'test@example.org',
    ]);
    $tags = [];
    foreach (['Tag A', 'Tag B'] as $name) {
      $tags[] = $this->callApiSuccess('Tag', 'create', [
        'name' => $name
      ]);
    }

    // assign contact to "Tag B"
    $this->callApiSuccess('EntityTag', 'create', [
      'entity_table' => 'civicrm_contact',
      'entity_id' => $contact['id'],
      'tag_id' => $tags[1]['id'],
    ]);

    // test format Contact.get tag='id1'
    $contact_get = $this->callAPISuccess('Contact', 'get', [
      'tag' => $tags[1]['id'],
      'return' => 'tag',
    ]);
    $this->assertEquals(1, $contact_get['count']);
    $this->assertEquals($contact['id'], $contact_get['id']);
    $this->assertEquals('Tag B', $contact_get['values'][$contact['id']]['tags']);

    // test format Contact.get tag='id1,id2'
    $contact_get = $this->callAPISuccess('Contact', 'get', [
      'tag' => $tags[0]['id'] . ',' . $tags[1]['id'],
      'return' => 'tag',
    ]);
    $this->assertEquals(1, $contact_get['count']);
    $this->assertEquals($contact['id'], $contact_get['id']);
    $this->assertEquals('Tag B', $contact_get['values'][$contact['id']]['tags']);

    // test format Contact.get tag='id1, id2'
    $contact_get = $this->callAPISuccess('Contact', 'get', [
      'tag' => $tags[0]['id'] . ', ' . $tags[1]['id'],
      'return' => 'tag',
    ]);
    $this->assertEquals(1, $contact_get['count']);
    $this->assertEquals($contact['id'], $contact_get['id']);
    $this->assertEquals('Tag B', $contact_get['values'][$contact['id']]['tags']);

    foreach ($tags as $tag) {
      $this->callAPISuccess('Tag', 'delete', ['id' => $tag['id']]);
    }
    $this->callAPISuccess('Contact', 'delete', [
      'id' => $contact['id'],
      'skip_undelete' => TRUE
    ]);
  }

}
