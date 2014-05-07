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
class api_v3_CaseTypeTest extends CiviUnitTestCase {
  protected $_apiversion = 3;

  function setUp() {
    $this->_entity = 'CaseType';

    parent::setUp();
    $this->_apiversion = 3;
    $this->tablesToTruncate = array(
      'civicrm_case_type',
    );
    $this->quickCleanup($this->tablesToTruncate);
    $this->createLoggedInUser();
    $session = CRM_Core_Session::singleton();
    $this->_loggedInUser = $session->get('userID');

  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   */
  function tearDown() {
    $this->quickCleanup($this->tablesToTruncate, TRUE);
  }

  /**
   * check with empty array
   */
  function testCaseTypeCreateEmpty() {
    $result = $this->callAPIFailure('CaseType', 'create', array());
  }

  /**
   * check if required fields are not passed
   */
  function testCaseTypeCreateWithoutRequired() {
    $params = array(
      'name' => 'this case should fail',
    );
    $result = $this->callAPIFailure('CaseType', 'create', $params);

    $params = array(
      'name' => 'this case should fail',
      'weight' => 4,
    );
    $result = $this->callAPIFailure('CaseType', 'create', $params);
  }

  /*
     * test create methods with valid data
     * success expected
     */
  function testCaseTypeCreate() {
    // Create Case Type
    $params = array(
      'title' => 'Application',
      'name' => 'Application',
      'is_active' => 1,
      'weight' => 4,
    );

    $result = $this->callAPISuccess('CaseType', 'create', $params);
    $id = $result['id'];

    // Check result
    $result = $this->callAPISuccess('CaseType', 'get', array('id' => $id));
    $this->assertEquals($result['values'][$id]['id'], $id, 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$id]['title'], $params['title'], 'in line ' . __LINE__);
  }

  /**
   * Test update (create with id) function with valid parameters
   */
  function testCaseTypeUpdate() {
    // Create Case Type
    $params =  array(
      'title' => 'Application',
      'name' => 'Application',
      'is_active' => 1,
      'weight' => 4,
    );
    $result = $this->callAPISuccess('CaseType', 'create', $params);
    $id = $result['id'];
    $result = $this->callAPISuccess('CaseType', 'get', array('id' => $id));
    $caseType = $result['values'][$id];

    // Update Case Type
    $params = array('id' => $id);
    $params['title'] = $caseType['title'] = 'Something Else';
    $result = $this->callAPISuccess('CaseType', 'create', $params);

    // Verify that updated case Type is exactly equal to the original with new title
    $result = $this->callAPISuccess('CaseType', 'get', array('id' => $id));
    $this->assertEquals($result['values'][$id], $caseType, 'in line ' . __LINE__);
  }

  /**
   * Test delete function with valid parameters
   */
  function testCaseTypeDelete() {
    // Create Case Type
    $params =  array(
      'title' => 'Application',
      'name' => 'Application',
      'is_active' => 1,
      'weight' => 4,
    );
    $result = $this->callAPISuccess('CaseType', 'create', $params);

    $id = $result['id'];
    $result = $this->callAPISuccess('CaseType', 'delete', array('id' => $id));

    // Check result - case type should no longer exist
    $result = $this->callAPISuccess('CaseType', 'get', array('id' => $id));
    $this->assertEquals(0, $result['count']);
  }
}

