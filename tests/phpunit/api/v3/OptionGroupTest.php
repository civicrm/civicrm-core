<?php

/*
 +--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2013                                |
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
class api_v3_OptionGroupTest extends CiviUnitTestCase {
  protected $_apiversion;
  public $_eNoticeCompliant = TRUE;

  function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
  }

  function tearDown() {}
  /*
  * Good to test option group as a representative on the Camel Case
  */

  public function testGetOptionGroupGetFields() {
    $result = civicrm_api('option_group', 'getfields', array('version' => 3));
    $this->assertFalse(empty($result['values']), 'In line ' . __LINE__);
  }
  public function testGetOptionGroupGetFieldsCreateAction() {
    $result = civicrm_api('option_group', 'getfields', array('action' => 'create', 'version' => 3));
    $this->assertFalse(empty($result['values']), 'In line ' . __LINE__);
    $this->assertEquals($result['values']['name']['api.unique'], 1);
  }

  public function testGetOptionGroupByID() {
    $result = civicrm_api('option_group', 'get', array('id' => 1, 'version' => 3));
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['id'], 'In line ' . __LINE__);
  }

  public function testGetOptionGroupByName() {
    $params = array('name' => 'preferred_communication_method', 'version' => 3);
    $result = civicrm_api('option_group', 'get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['id'], 'In line ' . __LINE__);
  }

  public function testGetOptionGroup() {
    $result = civicrm_api('option_group', 'get', array('version' => 3));
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertGreaterThan(1, $result['count'], 'In line ' . __LINE__);
  }

  public function testGetOptionDoesNotExist() {
    $result = civicrm_api('option_group', 'get', array('name' => 'FSIGUBSFGOMUUBSFGMOOUUBSFGMOOBUFSGMOOIIB', 'version' => 3));
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(0, $result['count'], 'In line ' . __LINE__);
  }
  public function testGetOptionCreateSuccess() {
    $params = array('version' => $this->_apiversion, 'sequential' => 1, 'name' => 'civicrm_event.amount.560', 'is_reserved' => 1, 'is_active' => 1, 'api.OptionValue.create' => array('label' => 'workshop', 'value' => 35, 'is_default' => 1, 'is_active' => 1, 'format.only_id' => 1));
    $result = civicrm_api('OptionGroup', 'create', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals('civicrm_event.amount.560', $result['values'][0]['name'], 'In line ' . __LINE__);
    $this->assertTrue(is_integer($result['values'][0]['api.OptionValue.create']));
    $this->assertGreaterThan(0, $result['values'][0]['api.OptionValue.create']);
    civicrm_api('OptionGroup', 'delete', array('version' => 3, 'id' => $result['id']));
  }
  /*
   * Test the error message when a failure is due to a key duplication issue
   */

  public function testGetOptionCreateFailOnDuplicate() {
    $params = array(
      'version' => $this->_apiversion,
      'sequential' => 1,
      'name' => 'civicrm_dup entry',
      'is_reserved' => 1,
      'is_active' => 1,
    );
    $result1 = civicrm_api('OptionGroup', 'create', $params);
    $this->assertAPISuccess($result1);
    $result = civicrm_api('OptionGroup', 'create', $params);
    civicrm_api('OptionGroup', 'delete', array('version' => 3, 'id' => $result1['id']));
    $this->assertEquals("Field: `name` must be unique. An conflicting entity already exists - id: " . $result1['id'], $result['error_message']);
  }

  /*
   * Test that transaction is completely rolled back on fail.
   * Also check error returned
   */

  public function testGetOptionCreateFailRollback() {
    $countFirst = civicrm_api('OptionGroup', 'getcount', array(
        'version' => 3,
        'options' => array('limit' => 5000),
      )
    );
    $params = array(
      'version' => $this->_apiversion,
      'sequential' => 1,
      'name' => 'civicrm_rolback_test',
      'is_reserved' => 1,
      'is_active' => 1,
      'api.OptionValue.create' => array(
        'label' => 'invalid entry',
        'value' => 35,
        'domain_id' => 999,
        'is_active' => '0',
        'debug' => 0,
      ),
    );
    $result = $this->callAPIFailure('OptionGroup', 'create', $params);
    $countAfter = civicrm_api('OptionGroup', 'getcount', array(
        'version' => 3,
        'options' => array('limit' => 5000),
      )
    );
    $this->assertEquals($countFirst, $countAfter,
      'Count of option groups should not have changed due to rollback triggered by option value In line ' . __LINE__
    );
  }
}

