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

require_once 'CiviTest/CiviCaseTestCase.php';

/**
 * Class api_v3_CaseTypeTest
 */
class api_v3_CaseTypeTest extends CiviCaseTestCase {

  function setUp() {
    $this->quickCleanup(array('civicrm_case_type'));
    parent::setUp();

    $this->fixtures['Application_with_Definition'] = array(
      'title' => 'Application with Definition',
      'name' => 'Application_with_Definition',
      'is_active' => 1,
      'weight' => 4,
      'definition' => array(
        'activityTypes' => array(
          array('name' => 'First act'),
        ),
        'activitySets' => array(
          array(
            'name' => 'set1',
            'label' => 'Label 1',
            'timeline' => 1,
            'activityTypes' => array(
              array('name' => 'Open Case', 'status' => 'Completed'),
            ),
          ),
        ),
        'caseRoles' => array(
          array('name' => 'First role', 'creator' => 1, 'manager' => 1),
        ),
      )
    );
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   */
  function tearDown() {
    parent::tearDown();
    $this->quickCleanup(array('civicrm_case_type'));
  }

  /**
   * check with empty array
   */
  function testCaseTypeCreateEmpty() {
    $this->callAPIFailure('CaseType', 'create', array());
  }

  /**
   * check if required fields are not passed
   */
  function testCaseTypeCreateWithoutRequired() {
    $params = array(
      'name' => 'this case should fail',
    );
    $this->callAPIFailure('CaseType', 'create', $params);

    $params = array(
      'name' => 'this case should fail',
      'weight' => 4,
    );
    $this->callAPIFailure('CaseType', 'create', $params);
  }

  /**
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
   * Create a case with an invalid name
   */
  function testCaseTypeCreate_invalidName() {
    // Create Case Type
    $params = array(
      'title' => 'Application',
      'name' => 'Appl ication', // spaces are not allowed
      'is_active' => 1,
      'weight' => 4,
    );

    $this->callAPIFailure('CaseType', 'create', $params);
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
    $this->callAPISuccess('CaseType', 'create', $params);

    // Verify that updated case Type is exactly equal to the original with new title
    $result = $this->callAPISuccess('CaseType', 'get', array('id' => $id));
    $this->assertEquals($result['values'][$id], $caseType, 'in line ' . __LINE__);
  }

  /**
   * Test delete function with valid parameters
   */
  function testCaseTypeDelete_New() {
    // Create Case Type
    $params =  array(
      'title' => 'Application',
      'name' => 'Application',
      'is_active' => 1,
      'weight' => 4,
    );
    $result = $this->callAPISuccess('CaseType', 'create', $params);

    $id = $result['id'];
    $this->callAPISuccess('CaseType', 'delete', array('id' => $id));

    // Check result - case type should no longer exist
    $result = $this->callAPISuccess('CaseType', 'get', array('id' => $id));
    $this->assertEquals(0, $result['count']);
  }

  /**
   * test create methods with xml file
   * success expected
   */
  function testCaseTypeCreateWithDefinition() {
    // Create Case Type
    $params = $this->fixtures['Application_with_Definition'];
    $result = $this->callAPISuccess('CaseType', 'create', $params);
    $id = $result['id'];

    // Check result
    $result = $this->callAPISuccess('CaseType', 'get', array('id' => $id));
    $this->assertEquals($result['values'][$id]['id'], $id, 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$id]['title'], $params['title'], 'in line ' . __LINE__);
    $this->assertEquals($result['values'][$id]['definition'], $params['definition'], 'in line ' . __LINE__);

    $caseXml = CRM_Case_XMLRepository::singleton()->retrieve('Application_with_Definition');
    $this->assertTrue($caseXml instanceof SimpleXMLElement);
  }

  /**
   * Create a CaseType+case then delete the CaseType.
   */
  function testCaseTypeDelete_InUse() {
    // Create Case Type
    $params = $this->fixtures['Application_with_Definition'];
    $createCaseType = $this->callAPISuccess('CaseType', 'create', $params);

    $createCase = $this->callAPISuccess('Case', 'create', array(
      'case_type_id' => $createCaseType['id'],
      'contact_id' => $this->_loggedInUser,
      'subject' => 'Example',
    ));

    // Deletion fails while case-type is in-use
    $deleteCaseType = $this->callAPIFailure('CaseType', 'delete', array('id' => $createCaseType['id']));
    $this->assertEquals("Cannot delete case type -- 1 record(s) depend on it", $deleteCaseType['error_message']);
    $getCaseType = $this->callAPISuccess('CaseType', 'get', array('id' => $createCaseType['id']));
    $this->assertEquals(1, $getCaseType['count']);

    // Deletion succeeds when it's not in-use
    $this->callAPISuccess('Case', 'delete', array('id' => $createCase['id']));

    // Check result - case type should no longer exist
    $this->callAPISuccess('CaseType', 'delete', array('id' => $createCaseType['id']));
    $getCaseType = $this->callAPISuccess('CaseType', 'get', array('id' => $createCaseType['id']));
    $this->assertEquals(0, $getCaseType['count']);
  }
}

