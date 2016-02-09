<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * This class is intended to test ACL permission using the multisite module
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 * @group headless
 */
class api_v3_ACLPermissionTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  public $DBResetRequired = FALSE;
  protected $_entity;
  protected $allowedContactId = 0;

  public function setUp() {
    parent::setUp();
    $baoObj = new CRM_Core_DAO();
    $baoObj->createTestObject('CRM_Pledge_BAO_Pledge', array(), 1, 0);
    $baoObj->createTestObject('CRM_Core_BAO_Phone', array(), 1, 0);
    $config = CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = array();
  }

  /**
   * (non-PHPdoc)
   * @see CiviUnitTestCase::tearDown()
   */
  public function tearDown() {
    CRM_Utils_Hook::singleton()->reset();
    $tablesToTruncate = array(
      'civicrm_contact',
      'civicrm_group_contact',
      'civicrm_group',
      'civicrm_acl',
      'civicrm_acl_cache',
      'civicrm_acl_entity_role',
      'civicrm_acl_contact_cache',
      'civicrm_contribution',
      'civicrm_participant',
      'civicrm_uf_match',
    );
    $this->quickCleanup($tablesToTruncate);
    $config = CRM_Core_Config::singleton();
    unset($config->userPermissionClass->permissions);
  }

  /**
   * Function tests that an empty where hook returns no results.
   */
  public function testContactGetNoResultsHook() {
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereHookNoResults'));
    $result = $this->callAPISuccess('contact', 'get', array(
      'check_permissions' => 1,
      'return' => 'display_name',
    ));
    $this->assertEquals(0, $result['count']);
  }

  /**
   * Function tests that an empty where hook returns exactly 1 result with "view my contact".
   *
   * CRM-16512 caused contacts with Edit my contact to be able to view all records.
   */
  public function testContactGetOneResultHookWithViewMyContact() {
    $this->createLoggedInUser();
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereHookNoResults'));
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM', 'view my contact');
    $result = $this->callAPISuccess('contact', 'get', array(
      'check_permissions' => 1,
      'return' => 'display_name',
    ));
    $this->assertEquals(1, $result['count']);
  }

  /**
   * Function tests that a user with "edit my contact" can edit themselves.
   */
  public function testContactEditHookWithEditMyContact() {
    $cid = $this->createLoggedInUser();
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereHookNoResults'));
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM', 'edit my contact');
    $this->callAPISuccess('contact', 'create', array(
      'check_permissions' => 1,
      'id' => $cid,
    ));
  }

  /**
   * Ensure contact permissions extend to related entities like email
   */
  public function testRelatedEntityPermissions() {
    $this->createLoggedInUser();
    $disallowedContact = $this->individualCreate(array(), 0);
    $this->allowedContactId = $this->individualCreate(array(), 1);
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereOnlyOne'));
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM');
    $testEntities = array(
      'Email' => array('email' => 'null@nothing', 'location_type_id' => 1),
      'Phone' => array('phone' => '123456', 'location_type_id' => 1),
      'IM' => array('name' => 'hello', 'location_type_id' => 1),
      'Website' => array('url' => 'http://test'),
      'Address' => array('street_address' => '123 Sesame St.', 'location_type_id' => 1),
    );
    foreach ($testEntities as $entity => $params) {
      $params += array(
        'contact_id' => $disallowedContact,
        'check_permissions' => 1,
      );
      // We should be prevented from getting or creating entities for a contact we don't have permission for
      $this->callAPIFailure($entity, 'create', $params);
      $results = $this->callAPISuccess($entity, 'get', array('contact_id' => $disallowedContact, 'check_permissions' => 1));
      $this->assertEquals(0, $results['count']);

      // We should be allowed to create and get for contacts we do have permission on
      $params['contact_id'] = $this->allowedContactId;
      $this->callAPISuccess($entity, 'create', $params);
      $results = $this->callAPISuccess($entity, 'get', array('contact_id' => $this->allowedContactId, 'check_permissions' => 1));
      $this->assertGreaterThan(0, $results['count']);
    }
  }

  /**
   * Function tests all results are returned.
   */
  public function testContactGetAllResultsHook() {
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereHookAllResults'));
    $result = $this->callAPISuccess('contact', 'get', array(
      'check_permissions' => 1,
      'return' => 'display_name',
    ));

    $this->assertEquals(2, $result['count']);
  }

  /**
   * Function tests that deleted contacts are not returned.
   */
  public function testContactGetPermissionHookNoDeleted() {
    $this->callAPISuccess('contact', 'create', array('id' => 2, 'is_deleted' => 1));
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereHookAllResults'));
    $result = $this->callAPISuccess('contact', 'get', array(
      'check_permissions' => 1,
      'return' => 'display_name',
    ));
    $this->assertEquals(1, $result['count']);
  }

  /**
   * Test permissions limited by hook.
   */
  public function testContactGetHookLimitingHook() {
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereOnlySecond'));

    $result = $this->callAPISuccess('contact', 'get', array(
      'check_permissions' => 1,
      'return' => 'display_name',
    ));
    $this->assertEquals(1, $result['count']);
  }

  /**
   * Confirm that without check permissions we still get 2 contacts returned.
   */
  public function testContactGetHookLimitingHookDontCheck() {
    $result = $this->callAPISuccess('contact', 'get', array(
      'check_permissions' => 0,
      'return' => 'display_name',
    ));
    $this->assertEquals(2, $result['count']);
  }

  /**
   * Check that id works as a filter.
   */
  public function testContactGetIDFilter() {
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereHookAllResults'));
    $result = $this->callAPISuccess('contact', 'get', array(
      'sequential' => 1,
      'id' => 2,
      'check_permissions' => 1,
    ));

    $this->assertEquals(1, $result['count']);
    $this->assertEquals(2, $result['id']);
  }

  /**
   * Check that address IS returned.
   */
  public function testContactGetAddressReturned() {
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereOnlySecond'));
    $fullresult = $this->callAPISuccess('contact', 'get', array(
      'sequential' => 1,
    ));
    //return doesn't work for all keys - can't fix that here so let's skip ...
    //prefix & suffix are inconsistent due to  CRM-7929
    // unsure about others but return doesn't work on them
    $elementsReturnDoesntSupport = array(
      'prefix',
      'suffix',
      'gender',
      'current_employer',
      'phone_id',
      'phone_type_id',
      'phone',
      'worldregion_id',
      'world_region',
    );
    $expectedReturnElements = array_diff(array_keys($fullresult['values'][0]), $elementsReturnDoesntSupport);
    $result = $this->callAPISuccess('contact', 'get', array(
      'check_permissions' => 1,
      'return' => $expectedReturnElements,
      'sequential' => 1,
    ));
    $this->assertEquals(1, $result['count']);
    foreach ($expectedReturnElements as $element) {
      $this->assertArrayHasKey($element, $result['values'][0]);
    }
  }

  /**
   * Check that pledge IS not returned.
   */
  public function testContactGetPledgeIDNotReturned() {
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereHookAllResults'));
    $this->callAPISuccess('contact', 'get', array(
      'sequential' => 1,
    ));
    $result = $this->callAPISuccess('contact', 'get', array(
      'check_permissions' => 1,
      'return' => 'pledge_id',
      'sequential' => 1,
    ));
    $this->assertArrayNotHasKey('pledge_id', $result['values'][0]);
  }

  /**
   * Check that pledge IS not an allowable filter.
   */
  public function testContactGetPledgeIDNotFiltered() {
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereHookAllResults'));
    $this->callAPISuccess('contact', 'get', array(
      'sequential' => 1,
    ));
    $result = $this->callAPISuccess('contact', 'get', array(
      'check_permissions' => 1,
      'pledge_id' => 1,
      'sequential' => 1,
    ));
    $this->assertEquals(2, $result['count']);
  }

  /**
   * Check that chaining doesn't bypass permissions
   */
  public function testContactGetPledgeNotChainable() {
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereOnlySecond'));
    $this->callAPISuccess('contact', 'get', array(
      'sequential' => 1,
    ));
    $this->callAPIFailure('contact', 'get', array(
        'check_permissions' => 1,
        'api.pledge.get' => 1,
        'sequential' => 1,
      ),
      'Error in call to pledge_get : API permission check failed for pledge/get call; missing permission: access CiviCRM.'
    );
  }

  public function setupCoreACL() {
    $this->createLoggedInUser();
    $this->_permissionedDisabledGroup = $this->groupCreate(array(
      'title' => 'pick-me-disabled',
      'is_active' => 0,
      'name' => 'pick-me-disabled',
    ));
    $this->_permissionedGroup = $this->groupCreate(array(
      'title' => 'pick-me-active',
      'is_active' => 1,
      'name' => 'pick-me-active',
    ));
    $this->setupACL();
  }

  /**
   * @dataProvider entities
   * confirm that without check permissions we still get 2 contacts returned
   * @param $entity
   */
  public function testEntitiesGetHookLimitingHookNoCheck($entity) {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array();
    $this->setUpEntities($entity);
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereHookNoResults'));
    $result = $this->callAPISuccess($entity, 'get', array(
      'check_permissions' => 0,
      'return' => 'contact_id',
    ));
    $this->assertEquals(2, $result['count']);
  }

  /**
   * @dataProvider entities
   * confirm that without check permissions we still get 2 entities returned
   * @param $entity
   */
  public function testEntitiesGetCoreACLLimitingHookNoCheck($entity) {
    $this->setupCoreACL();
    //CRM_Core_Config::singleton()->userPermissionClass->permissions = array();
    $this->setUpEntities($entity);
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereHookNoResults'));
    $result = $this->callAPISuccess($entity, 'get', array(
      'check_permissions' => 0,
      'return' => 'contact_id',
    ));
    $this->assertEquals(2, $result['count']);
  }

  /**
   * @dataProvider entities
   * confirm that with check permissions we don't get entities
   * @param $entity
   * @throws \PHPUnit_Framework_IncompleteTestError
   */
  public function testEntitiesGetCoreACLLimitingCheck($entity) {
    $this->markTestIncomplete('this does not work in 4.4 but can be enabled in 4.5 or a security release of 4.4 including the important security fix CRM-14877');
    $this->setupCoreACL();
    $this->setUpEntities($entity);
    $result = $this->callAPISuccess($entity, 'get', array(
      'check_permissions' => 1,
      'return' => 'contact_id',
    ));
    $this->assertEquals(0, $result['count']);
  }

  /**
   * @dataProvider entities
   * Function tests that an empty where hook returns no results
   * @param string $entity
   * @throws \PHPUnit_Framework_IncompleteTestError
   */
  public function testEntityGetNoResultsHook($entity) {
    $this->markTestIncomplete('hook acls only work with contacts so far');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array();
    $this->setUpEntities($entity);
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereHookNoResults'));
    $result = $this->callAPISuccess($entity, 'get', array(
      'check_permission' => 1,
    ));
    $this->assertEquals(0, $result['count']);
  }

  /**
   * @return array
   */
  public static function entities() {
    return array(array('contribution'), array('participant'));// @todo array('pledge' => 'pledge')
  }

  /**
   * Create 2 entities
   * @param $entity
   */
  public function setUpEntities($entity) {
    $baoObj = new CRM_Core_DAO();
    $baoObj->createTestObject(_civicrm_api3_get_BAO($entity), array(), 2, 0);
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array(
      'access CiviCRM',
      'access CiviContribute',
      'access CiviEvent',
      'view event participants',
    );
  }

  /**
   * No results returned.
   *
   * @implements CRM_Utils_Hook::aclWhereClause
   *
   * @param string $type
   * @param array $tables
   * @param array $whereTables
   * @param int $contactID
   * @param string $where
   */
  public function aclWhereHookNoResults($type, &$tables, &$whereTables, &$contactID, &$where) {
  }

  /**
   * All results returned.
   *
   * @implements CRM_Utils_Hook::aclWhereClause
   *
   * @param string $type
   * @param array $tables
   * @param array $whereTables
   * @param int $contactID
   * @param string $where
   */
  public function aclWhereHookAllResults($type, &$tables, &$whereTables, &$contactID, &$where) {
    $where = " (1) ";
  }

  /**
   * All but first results returned.
   * @implements CRM_Utils_Hook::aclWhereClause
   * @param $type
   * @param $tables
   * @param $whereTables
   * @param $contactID
   * @param $where
   */
  public function aclWhereOnlySecond($type, &$tables, &$whereTables, &$contactID, &$where) {
    $where = " contact_a.id > 1";
  }

  /**
   * Only specified contact returned.
   * @implements CRM_Utils_Hook::aclWhereClause
   * @param $type
   * @param $tables
   * @param $whereTables
   * @param $contactID
   * @param $where
   */
  public function aclWhereOnlyOne($type, &$tables, &$whereTables, &$contactID, &$where) {
    $where = " contact_a.id = " . $this->allowedContactId;
  }

}
