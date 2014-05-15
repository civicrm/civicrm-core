<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * This class is intended to test ACL permission using the multisite module
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 */

class api_v3_ACLPermissionTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $_params;
  protected $hookClass = NULL;
  public $DBResetRequired = FALSE;



  protected $_entity;

  function setUp() {
    parent::setUp();
    $baoObj = new CRM_Core_DAO();
    $baoObj->createTestObject('CRM_Pledge_BAO_Pledge', array(), 1, 0);
    $baoObj->createTestObject('CRM_Core_BAO_Phone', array(), 1, 0);
    $this->hookClass = CRM_Utils_Hook::singleton();
    $config = CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = array();
  }

  /**
   * (non-PHPdoc)
   * @see CiviUnitTestCase::tearDown()
   */
  function tearDown() {
    CRM_Utils_Hook::singleton()->reset();
    $tablesToTruncate = array(
      'civicrm_contact',
    );
    $this->quickCleanup($tablesToTruncate);
    $config = CRM_Core_Config::singleton();
    unset($config->userPermissionClass->permissions);
  }

  /**
   * Function tests that an empty where hook returns no results
   */
  function testContactGetNoResultsHook() {
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereHookNoResults'));
    $result = $this->callAPISuccess('contact', 'get', array(
      'check_permissions' => 1,
      'return' => 'display_name',
    ));
    $this->assertEquals(0, $result['count']);
  }

  /**
   * Function tests all results are returned
   */
  function testContactGetAllResultsHook() {
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereHookAllResults'));
    $result = $this->callAPISuccess('contact', 'get', array(
      'check_permissions' => 1,
      'return' => 'display_name',
    ));

    $this->assertEquals(2, $result['count']);
  }

  /**
   * Function tests that deleted contacts are not returned
   */
  function testContactGetPermissionHookNoDeleted() {
    $result = $this->callAPISuccess('contact', 'create', array('id' => 2, 'is_deleted' => 1));
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereHookAllResults'));
    $result = $this->callAPISuccess('contact', 'get', array(
      'check_permissions' => 1,
      'return' => 'display_name',
    ));
    $this->assertEquals(1, $result['count']);
  }

  /**
   * test permissions limited by hook
   */
  function testContactGetHookLimitingHook() {
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereOnlySecond'));

    $result = $this->callAPISuccess('contact', 'get', array(
      'check_permissions' => 1,
      'return' => 'display_name',
    ));
    $this->assertEquals(1, $result['count']);
  }

  /**
   * confirm that without check permissions we still get 2 contacts returned
   */
  function testContactGetHookLimitingHookDontCheck() {
    //
    $result = $this->callAPISuccess('contact', 'get', array(
      'check_permissions' => 0,
      'return' => 'display_name',
    ));
    $this->assertEquals(2, $result['count']);
  }

  /**
   * Check that id works as a filter
   */
  function testContactGetIDFilter() {
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
   * Check that address IS returned
   */
  function testContactGetAddressReturned() {
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
      'world_region'
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
   * Check that pledge IS not returned
   */
  function testContactGetPledgeIDNotReturned() {
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereHookAllResults'));
    $fullresult = $this->callAPISuccess('contact', 'get', array(
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
   * Check that pledge IS not an allowable filter
   */
  function testContactGetPledgeIDNotFiltered() {
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereHookAllResults'));
    $fullresult = $this->callAPISuccess('contact', 'get', array(
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
  function testContactGetPledgeNotChainable() {
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereOnlySecond'));
    $fullresult = $this->callAPISuccess('contact', 'get', array(
      'sequential' => 1,
    ));
    $result = $this->callAPIFailure('contact', 'get', array(
        'check_permissions' => 1,
        'api.pledge.get' => 1,
        'sequential' => 1,
      ),
      'Error in call to pledge_get : API permission check failed for pledge/get call; missing permission: access CiviCRM.'
    );
  }

  /**
   * no results returned
   */
  function aclWhereHookNoResults($type, &$tables, &$whereTables, &$contactID, &$where) {
  }

  /**
   * all results returned
   */
  function aclWhereHookAllResults($type, &$tables, &$whereTables, &$contactID, &$where) {
    $where = " (1) ";
  }

  /**
   * full results returned
   */
  function aclWhereOnlySecond($type, &$tables, &$whereTables, &$contactID, &$where) {
    $where = " contact_a.id > 1";
  }


}

