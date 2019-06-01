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
 * This class is intended to test ACL permission using the multisite module
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 * @group headless
 */
class api_v3_ACLPermissionTest extends CiviUnitTestCase {

  use CRMTraits_ACL_PermissionTrait;

  public $DBResetRequired = FALSE;
  protected $_entity;

  public function setUp() {
    parent::setUp();
    $baoObj = new CRM_Core_DAO();
    $baoObj->createTestObject('CRM_Pledge_BAO_Pledge', [], 1, 0);
    $baoObj->createTestObject('CRM_Core_BAO_Phone', [], 1, 0);
    $this->prepareForACLs();
  }

  /**
   * (non-PHPdoc)
   * @see CiviUnitTestCase::tearDown()
   */
  public function tearDown() {
    $this->cleanUpAfterACLs();
    $tablesToTruncate = [
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
      'civicrm_activity',
      'civicrm_activity_contact',
      'civicrm_note',
      'civicrm_entity_tag',
      'civicrm_tag',
    ];
    $this->quickCleanup($tablesToTruncate);
  }

  /**
   * Function tests that an empty where hook returns no results.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testContactGetNoResultsHook($version) {
    $this->_apiversion = $version;
    $this->hookClass->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereHookNoResults',
    ]);
    $result = $this->callAPISuccess('contact', 'get', [
      'check_permissions' => 1,
      'return' => 'display_name',
    ]);
    $this->assertEquals(0, $result['count']);
  }

  /**
   * Function tests that an empty where hook returns exactly 1 result with "view my contact".
   *
   * CRM-16512 caused contacts with Edit my contact to be able to view all records.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testContactGetOneResultHookWithViewMyContact($version) {
    $this->_apiversion = $version;
    $this->createLoggedInUser();
    $this->hookClass->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereHookNoResults',
    ]);
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'view my contact',
    ];
    $result = $this->callAPISuccess('contact', 'get', [
      'check_permissions' => 1,
      'return' => 'display_name',
    ]);
    $this->assertEquals(1, $result['count']);
  }

  /**
   * Function tests that a user with "edit my contact" can edit themselves.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testContactEditHookWithEditMyContact($version) {
    $this->_apiversion = $version;
    $cid = $this->createLoggedInUser();
    $this->hookClass->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereHookNoResults',
    ]);
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'edit my contact',
    ];
    $this->callAPISuccess('contact', 'create', [
      'check_permissions' => 1,
      'id' => $cid,
      'first_name' => 'NewName',
    ]);
  }

  /**
   * Ensure contact permissions do not block contact-less location entities.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testAddressWithoutContactIDAccess($version) {
    $this->_apiversion = $version;
    $ownID = $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'view all contacts',
    ];
    $this->callAPISuccess('Address', 'create', [
      'city' => 'Mouseville',
      'location_type_id' => 'Main',
      'api.LocBlock.create' => 1,
      'contact_id' => $ownID,
    ]);
    $this->callAPISuccessGetSingle('Address', [
      'city' => 'Mouseville',
      'check_permissions' => 1,
    ]);
    CRM_Core_DAO::executeQuery('UPDATE civicrm_address SET contact_id = NULL WHERE contact_id = %1', [
      1 => [
        $ownID,
        'Integer',
      ],
    ]);
    $this->callAPISuccessGetSingle('Address', [
      'city' => 'Mouseville',
      'check_permissions' => 1,
    ]);
  }

  /**
   * Ensure contact permissions extend to related entities like email
   * @param int $version
   * @dataProvider versionThreeAndFour
   * FIXME: Finish api4 part
   */
  public function testRelatedEntityPermissions($version) {
    $this->_apiversion = $version;
    $this->createLoggedInUser();
    $disallowedContact = $this->individualCreate([], 0);
    $this->allowedContactId = $this->individualCreate([], 1);
    $this->hookClass->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereOnlyOne',
    ]);
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $testEntities = [
      'Email' => ['email' => 'null@nothing', 'location_type_id' => 1],
      'Phone' => ['phone' => '123456', 'location_type_id' => 1],
      'IM' => ['name' => 'hello', 'location_type_id' => 1],
      'Website' => ['url' => 'http://test'],
      'Address' => [
        'street_address' => '123 Sesame St.',
        'location_type_id' => 1,
      ],
    ];
    foreach ($testEntities as $entity => $params) {
      $params += [
        'contact_id' => $disallowedContact,
        'check_permissions' => 1,
      ];
      // We should be prevented from getting or creating entities for a contact we don't have permission for
      $this->callAPIFailure($entity, 'create', $params);
      $this->callAPISuccess($entity, 'create', ['check_permissions' => 0] + $params);
      $results = $this->callAPISuccess($entity, 'get', [
        'contact_id' => $disallowedContact,
        'check_permissions' => 1,
      ]);
      $this->assertEquals(0, $results['count']);

      // We should be allowed to create and get for contacts we do have permission on
      $params['contact_id'] = $this->allowedContactId;
      $this->callAPISuccess($entity, 'create', $params);
      $results = $this->callAPISuccess($entity, 'get', [
        'contact_id' => $this->allowedContactId,
        'check_permissions' => 1,
      ]);
      $this->assertGreaterThan(0, $results['count']);
    }
    if ($version == 4) {
      $this->markTestIncomplete('Skipping entity_id related perms in api4 for now.');
    }
    $newTag = civicrm_api3('Tag', 'create', [
      'name' => 'Foo123',
    ]);
    $relatedEntities = [
      'Note' => ['note' => 'abc'],
      'EntityTag' => ['tag_id' => $newTag['id']],
    ];
    foreach ($relatedEntities as $entity => $params) {
      $params += [
        'entity_id' => $disallowedContact,
        'entity_table' => 'civicrm_contact',
        'check_permissions' => 1,
      ];
      // We should be prevented from getting or creating entities for a contact we don't have permission for
      $this->callAPIFailure($entity, 'create', $params);
      $this->callAPISuccess($entity, 'create', ['check_permissions' => 0] + $params);
      $results = $this->callAPISuccess($entity, 'get', [
        'entity_id' => $disallowedContact,
        'entity_table' => 'civicrm_contact',
        'check_permissions' => 1,
      ]);
      $this->assertEquals(0, $results['count']);

      // We should be allowed to create and get for entities we do have permission on
      $params['entity_id'] = $this->allowedContactId;
      $this->callAPISuccess($entity, 'create', $params);
      $results = $this->callAPISuccess($entity, 'get', [
        'entity_id' => $this->allowedContactId,
        'entity_table' => 'civicrm_contact',
        'check_permissions' => 1,
      ]);
      $this->assertGreaterThan(0, $results['count']);
    }
  }

  /**
   * Function tests all results are returned.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testContactGetAllResultsHook($version) {
    $this->_apiversion = $version;
    $this->hookClass->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereHookAllResults',
    ]);
    $result = $this->callAPISuccess('contact', 'get', [
      'check_permissions' => 1,
      'return' => 'display_name',
    ]);

    $this->assertEquals(2, $result['count']);
  }

  /**
   * Function tests that deleted contacts are not returned.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testContactGetPermissionHookNoDeleted($version) {
    $this->_apiversion = $version;
    $this->callAPISuccess('contact', 'create', ['id' => 2, 'is_deleted' => 1]);
    $this->hookClass->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereHookAllResults',
    ]);
    $result = $this->callAPISuccess('contact', 'get', [
      'check_permissions' => 1,
      'return' => 'display_name',
    ]);
    $this->assertEquals(1, $result['count']);
  }

  /**
   * Test permissions limited by hook.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testContactGetHookLimitingHook($version) {
    $this->_apiversion = $version;
    $this->hookClass->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereOnlySecond',
    ]);

    $result = $this->callAPISuccess('contact', 'get', [
      'check_permissions' => 1,
      'return' => 'display_name',
    ]);
    $this->assertEquals(1, $result['count']);
  }

  /**
   * Confirm that without check permissions we still get 2 contacts returned.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testContactGetHookLimitingHookDontCheck($version) {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess('contact', 'get', [
      'check_permissions' => 0,
      'return' => 'display_name',
    ]);
    $this->assertEquals(2, $result['count']);
  }

  /**
   * Check that id works as a filter.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testContactGetIDFilter($version) {
    $this->_apiversion = $version;
    $this->hookClass->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereHookAllResults',
    ]);
    $result = $this->callAPISuccess('contact', 'get', [
      'sequential' => 1,
      'id' => 2,
      'check_permissions' => 1,
    ]);

    $this->assertEquals(1, $result['count']);
    $this->assertEquals(2, $result['id']);
  }

  /**
   * Check that address IS returned.
   */
  public function testContactGetAddressReturned() {
    $this->hookClass->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereOnlySecond',
    ]);
    $fullresult = $this->callAPISuccess('contact', 'get', [
      'sequential' => 1,
    ]);
    //return doesn't work for all keys - can't fix that here so let's skip ...
    //prefix & suffix are inconsistent due to  CRM-7929
    // unsure about others but return doesn't work on them
    $elementsReturnDoesntSupport = [
      'prefix',
      'suffix',
      'gender',
      'current_employer',
      'phone_id',
      'phone_type_id',
      'phone',
      'worldregion_id',
      'world_region',
    ];
    $expectedReturnElements = array_diff(array_keys($fullresult['values'][0]), $elementsReturnDoesntSupport);
    $result = $this->callAPISuccess('contact', 'get', [
      'check_permissions' => 1,
      'return' => $expectedReturnElements,
      'sequential' => 1,
    ]);
    $this->assertEquals(1, $result['count']);
    foreach ($expectedReturnElements as $element) {
      $this->assertArrayHasKey($element, $result['values'][0]);
    }
  }

  /**
   * Check that pledge IS not returned.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testContactGetPledgeIDNotReturned($version) {
    $this->_apiversion = $version;
    $this->hookClass->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereHookAllResults',
    ]);
    $this->callAPISuccess('contact', 'get', [
      'sequential' => 1,
    ]);
    $result = $this->callAPISuccess('contact', 'get', [
      'check_permissions' => 1,
      'return' => 'pledge_id',
      'sequential' => 1,
    ]);
    $this->assertArrayNotHasKey('pledge_id', $result['values'][0]);
  }

  /**
   * Check that pledge IS not an allowable filter.
   */
  public function testContactGetPledgeIDNotFiltered() {
    $this->hookClass->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereHookAllResults',
    ]);
    $this->callAPISuccess('contact', 'get', [
      'sequential' => 1,
    ]);
    $result = $this->callAPISuccess('contact', 'get', [
      'check_permissions' => 1,
      'pledge_id' => 1,
      'sequential' => 1,
    ]);
    $this->assertEquals(2, $result['count']);
  }

  /**
   * Check that chaining doesn't bypass permissions
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testContactGetPledgeNotChainable($version) {
    $this->_apiversion = $version;
    $this->hookClass->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereOnlySecond',
    ]);
    $this->callAPISuccess('contact', 'get', [
      'sequential' => 1,
    ]);
    $this->callAPIFailure('contact', 'get', [
      'check_permissions' => 1,
      'api.pledge.get' => 1,
      'sequential' => 1,
    ],
      'Error in call to Pledge_get : API permission check failed for Pledge/get call; insufficient permission: require access CiviCRM and access CiviPledge'
    );
  }

  public function setupCoreACL() {
    $this->createLoggedInUser();
    $this->_permissionedDisabledGroup = $this->groupCreate([
      'title' => 'pick-me-disabled',
      'is_active' => 0,
      'name' => 'pick-me-disabled',
    ]);
    $this->_permissionedGroup = $this->groupCreate([
      'title' => 'pick-me-active',
      'is_active' => 1,
      'name' => 'pick-me-active',
    ]);
    $this->setupACL();
  }

  /**
   * @dataProvider entities
   * confirm that without check permissions we still get 2 contacts returned
   * @param $entity
   */
  public function testEntitiesGetHookLimitingHookNoCheck($entity) {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [];
    $this->setUpEntities($entity);
    $this->hookClass->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereHookNoResults',
    ]);
    $result = $this->callAPISuccess($entity, 'get', [
      'check_permissions' => 0,
      'return' => 'contact_id',
    ]);
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
    $this->hookClass->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereHookNoResults',
    ]);
    $result = $this->callAPISuccess($entity, 'get', [
      'check_permissions' => 0,
      'return' => 'contact_id',
    ]);
    $this->assertEquals(2, $result['count']);
  }

  /**
   * @dataProvider entities
   * confirm that with check permissions we don't get entities
   * @param $entity
   * @throws \PHPUnit_Framework_IncompleteTestError
   */
  public function testEntitiesGetCoreACLLimitingCheck($entity) {
    $this->setupCoreACL();
    $this->setUpEntities($entity);
    $result = $this->callAPISuccess($entity, 'get', [
      'check_permissions' => 1,
      'return' => 'contact_id',
    ]);
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
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [];
    $this->setUpEntities($entity);
    $this->hookClass->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereHookNoResults',
    ]);
    $result = $this->callAPISuccess($entity, 'get', [
      'check_permission' => 1,
    ]);
    $this->assertEquals(0, $result['count']);
  }

  /**
   * @return array
   */
  public static function entities() {
    return [
      ['contribution'],
      ['participant'],
    // @todo array('pledge' => 'pledge')
    ];
  }

  /**
   * Create 2 entities
   * @param $entity
   */
  public function setUpEntities($entity) {
    $baoObj = new CRM_Core_DAO();
    $baoObj->createTestObject(_civicrm_api3_get_BAO($entity), [], 2, 0);
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'access CiviContribute',
      'access CiviEvent',
      'view event participants',
    ];
  }

  /**
   * Basic check that an unpermissioned call keeps working and permissioned call fails.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetActivityNoPermissions($version) {
    $this->_apiversion = $version;
    $this->setPermissions([]);
    $this->callAPISuccess('Activity', 'get', []);
    $this->callAPIFailure('Activity', 'get', ['check_permissions' => 1]);
  }

  /**
   * View all activities is enough regardless of contact ACLs.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetActivityViewAllActivitiesDoesntCutItAnymore($version) {
    $this->_apiversion = $version;
    $activity = $this->activityCreate();
    $this->setPermissions(['view all activities', 'access CiviCRM']);
    $this->callAPISuccessGetCount('Activity', [
      'check_permissions' => 1,
      'id' => $activity['id'],
    ], 0);
  }

  /**
   * View all activities is required unless id is passed in.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetActivityViewAllContactsEnoughWithoutID($version) {
    $this->_apiversion = $version;
    $this->setPermissions(['view all contacts', 'access CiviCRM']);
    $this->callAPISuccess('Activity', 'get', ['check_permissions' => 1]);
  }

  /**
   * Without view all activities contact level acls are used.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetActivityViewAllContactsEnoughWIthID($version) {
    $this->_apiversion = $version;
    $activity = $this->activityCreate();
    $this->setPermissions(['view all contacts', 'access CiviCRM']);
    $this->callAPISuccess('Activity', 'getsingle', [
      'check_permissions' => 1,
      'id' => $activity['id'],
    ]);
  }

  /**
   * Check the error message is not a permission error.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetActivityAccessCiviCRMEnough($version) {
    $this->_apiversion = $version;
    $activity = $this->activityCreate();
    $this->setPermissions(['access CiviCRM']);
    $this->callAPIFailure('Activity', 'getsingle', [
      'check_permissions' => 1,
      'id' => $activity['id'],
    ], 'Expected one Activity but found 0');
    $this->callAPISuccessGetCount('Activity', [
      'check_permissions' => 1,
      'id' => $activity['id'],
    ], 0);
  }

  /**
   * Check that component related activity filtering.
   *
   * If the contact does NOT have permission to 'view all contacts' but they DO have permission
   * to view the contact in question they will only see the activities of components they have access too.
   *
   * (logically the same component limit should apply when they have access to view all too but....
   * adding test for 'how it is at the moment.)
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetActivityCheckPermissionsByComponent($version) {
    $this->_apiversion = $version;
    $activity = $this->activityCreate(['activity_type_id' => 'Contribution']);
    $activity2 = $this->activityCreate(['activity_type_id' => 'Pledge Reminder']);
    $this->hookClass->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereHookAllResults',
    ]);
    $this->setPermissions(['access CiviCRM', 'access CiviContribute']);
    $this->callAPISuccessGetSingle('Activity', [
      'check_permissions' => 1,
      'id' => ['IN' => [$activity['id'], $activity2['id']]],
    ]);
    $this->callAPISuccessGetCount('Activity', [
      'check_permissions' => 1,
      'id' => ['IN' => [$activity['id'], $activity2['id']]],
    ], 1);

  }

  /**
   * Check that component related activity filtering works for CiviCase.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetActivityCheckPermissionsByCaseComponent($version) {
    $this->_apiversion = $version;
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
    $activity = $this->activityCreate(['activity_type_id' => 'Open Case']);
    $activity2 = $this->activityCreate(['activity_type_id' => 'Pledge Reminder']);
    $this->hookClass->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereHookAllResults',
    ]);
    $this->setPermissions([
      'access CiviCRM',
      'access CiviContribute',
      'access all cases and activities',
    ]);
    $this->callAPISuccessGetSingle('Activity', [
      'check_permissions' => 1,
      'id' => ['IN' => [$activity['id'], $activity2['id']]],
    ]);
    $this->callAPISuccessGetCount('Activity', [
      'check_permissions' => 1,
      'id' => ['IN' => [$activity['id'], $activity2['id']]],
    ], 1);
  }

  /**
   * Check that activities can be retrieved by ACL.
   *
   * The activities api applies ACLs in a very limited circumstance, if id is passed in.
   * Otherwise it sticks with the blunt original permissions.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetActivityByACL($version) {
    $this->_apiversion = $version;
    $this->setPermissions(['access CiviCRM']);
    $activity = $this->activityCreate();

    $this->hookClass->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereHookAllResults',
    ]);
    $this->callAPISuccessGetSingle('Activity', [
      'check_permissions' => 1,
      'id' => $activity['id'],
    ]);
    $this->callAPISuccessGetCount('Activity', [
      'check_permissions' => 1,
      'id' => $activity['id'],
    ]);
  }

  /**
   * To leverage ACL permission to view an activity you must be able to see any of the contacts.
   * FIXME: Api4
   */
  public function testGetActivityByAclCannotViewAllContacts() {
    $activity = $this->activityCreate(['assignee_contact_id' => $this->individualCreate()]);
    $contacts = $this->getActivityContacts($activity);
    $this->setPermissions(['access CiviCRM']);

    foreach ($contacts as $role => $contact_id) {
      $this->allowedContactId = $contact_id;
      $this->hookClass->setHook('civicrm_aclWhereClause', [
        $this,
        'aclWhereOnlyOne',
      ]);
      $this->cleanupCachedPermissions();
      $result = $this->callAPISuccessGetSingle('Activity', [
        'check_permissions' => 1,
        'id' => $activity['id'],
        'return' => [
          'source_contact_id',
          'target_contact_id',
          'assignee_contact_id',
        ],
      ]);
      foreach ([
        'source_contact',
        'target_contact',
        'assignee_contact',
      ] as $roleName) {
        $roleKey = $roleName . '_id';
        if ($role !== $roleKey) {
          $this->assertTrue(empty($result[$roleKey]), "Only contact in $role is permissioned to be returned, not $roleKey");
        }
        else {
          $this->assertEquals([$contact_id], (array) $result[$roleKey]);
          $this->assertTrue(!empty($result[$roleName . '_name']));
        }
      }
    }
  }

  /**
   * To leverage ACL permission to view an activity you must be able to see any of the contacts.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetActivityByAclCannotViewAnyContacts($version) {
    $this->_apiversion = $version;
    $activity = $this->activityCreate();
    $contacts = $this->getActivityContacts($activity);
    $this->setPermissions(['access CiviCRM']);

    foreach ($contacts as $contact_id) {
      $this->callAPIFailure('Activity', 'getsingle', [
        'check_permissions' => 1,
        'id' => $activity['id'],
      ]);
    }
  }

  /**
   * Check that if the source contact is deleted but we can view the others we can see the activity.
   *
   * CRM-18409.
   *
   * @throws \CRM_Core_Exception
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetActivityACLSourceContactDeleted($version) {
    $this->_apiversion = $version;
    $this->setPermissions(['access CiviCRM', 'delete contacts']);
    $activity = $this->activityCreate();
    $contacts = $this->getActivityContacts($activity);

    $this->hookClass->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereHookAllResults',
    ]);
    $this->contactDelete($contacts['source_contact_id']);
    $this->callAPISuccess('Activity', 'getsingle', [
      'check_permissions' => 1,
      'id' => $activity['id'],
    ]);
  }

  /**
   * Test get activities multiple ids with check permissions
   * CRM-20441
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testActivitiesGetMultipleIdsCheckPermissions($version) {
    $this->_apiversion = $version;
    $this->createLoggedInUser();
    $activity = $this->activityCreate();
    $activity2 = $this->activityCreate();
    $this->setPermissions(['access CiviCRM']);
    $this->hookClass->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereHookAllResults',
    ]);
    // Get activities associated with contact $this->_contactID.
    $params = [
      'id' => ['IN' => [$activity['id'], $activity2['id']]],
      'check_permissions' => TRUE,
    ];
    $result = $this->callAPISuccess('activity', 'get', $params);
    $this->assertEquals(2, $result['count']);
  }

  /**
   * Test get activities multiple ids with check permissions
   * Limit access to One contact
   * CRM-20441
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testActivitiesGetMultipleIdsCheckPermissionsLimitedACL($version) {
    $this->_apiversion = $version;
    $this->createLoggedInUser();
    $activity = $this->activityCreate();
    $contacts = $this->getActivityContacts($activity);
    $this->setPermissions(['access CiviCRM']);
    foreach ($contacts as $contact_id) {
      $this->allowedContacts[] = $contact_id;
    }
    $this->hookClass->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereMultipleContacts',
    ]);
    $contact2 = $this->individualCreate();
    $activity2 = $this->activityCreate(['source_contact_id' => $contact2]);
    // Get activities associated with contact $this->_contactID.
    $params = [
      'id' => ['IN' => [$activity['id']]],
      'check_permissions' => TRUE,
    ];
    $result = $this->callAPISuccess('activity', 'get', $params);
    $this->assertEquals(1, $result['count']);
    $this->callAPIFailure('activity', 'getsingle', array_merge($params, [
      'id' => [
        'IN',
        [$activity2['id']],
      ],
    ]));
  }

  /**
   * Test get activities multiple ids with check permissions
   * CRM-20441
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testActivitiesGetMultipleIdsCheckPermissionsNotIN($version) {
    $this->_apiversion = $version;
    $this->createLoggedInUser();
    $activity = $this->activityCreate();
    $activity2 = $this->activityCreate();
    $this->setPermissions(['access CiviCRM']);
    $this->hookClass->setHook('civicrm_aclWhereClause', [
      $this,
      'aclWhereHookAllResults',
    ]);
    // Get activities associated with contact $this->_contactID.
    $params = [
      'id' => ['NOT IN' => [$activity['id'], $activity2['id']]],
      'check_permissions' => TRUE,
    ];
    $result = $this->callAPISuccess('activity', 'get', $params);
    $this->assertEquals(0, $result['count']);
  }

  /**
   * Get the contacts for the activity.
   *
   * @param $activity
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getActivityContacts($activity) {
    $contacts = [];

    $activityContacts = $this->callAPISuccess('ActivityContact', 'get', [
      'activity_id' => $activity['id'],
    ]);

    $activityRecordTypes = $this->callAPISuccess('ActivityContact', 'getoptions', ['field' => 'record_type_id']);
    foreach ($activityContacts['values'] as $activityContact) {
      $type = $activityRecordTypes['values'][$activityContact['record_type_id']];
      switch ($type) {
        case 'Activity Source':
          $contacts['source_contact_id'] = $activityContact['contact_id'];
          break;

        case 'Activity Targets':
          $contacts['target_contact_id'] = $activityContact['contact_id'];
          break;

        case 'Activity Assignees':
          $contacts['assignee_contact_id'] = $activityContact['contact_id'];
          break;

      }
    }
    return $contacts;
  }

  /**
   * Test that the 'everyone' group can be given access to a contact.
   * FIXME: Api4
   */
  public function testGetACLEveryonePermittedEntity() {
    $this->setupScenarioCoreACLEveryonePermittedToGroup();
    $this->callAPISuccessGetCount('Contact', [
      'id' => $this->scenarioIDs['Contact']['permitted_contact'],
      'check_permissions' => 1,
    ], 1);

    $this->callAPISuccessGetCount('Contact', [
      'id' => $this->scenarioIDs['Contact']['non_permitted_contact'],
      'check_permissions' => 1,
    ], 0);

    // Also check that we can access ACLs through a path that uses the acl_contact_cache table.
    // historically this has caused errors due to the key_constraint on that table.
    // This is a bit of an artificial check as we have to amp up permissions to access this api.
    // However, the lower level function is more directly accessed through the Contribution & Event & Profile
    $dupes = $this->callAPISuccess('Contact', 'duplicatecheck', [
      'match' => [
        'first_name' => 'Anthony',
        'last_name' => 'Anderson',
        'contact_type' => 'Individual',
        'email' => 'anthony_anderson@civicrm.org',
      ],
      'check_permissions' => 0,
    ]);
    $this->assertEquals(2, $dupes['count']);
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];

    $dupes = $this->callAPISuccess('Contact', 'duplicatecheck', [
      'match' => [
        'first_name' => 'Anthony',
        'last_name' => 'Anderson',
        'contact_type' => 'Individual',
        'email' => 'anthony_anderson@civicrm.org',
      ],
      'check_permissions' => 1,
    ]);
    $this->assertEquals(1, $dupes['count']);

  }

}
