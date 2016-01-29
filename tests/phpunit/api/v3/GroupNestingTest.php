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
 * Test class for GroupNesting API - civicrm_group_nesting_*
 *
 * @package   CiviCRM
 */
class api_v3_GroupNestingTest extends CiviUnitTestCase {
  protected $_apiversion;

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp() {
    $this->_apiversion = 3;
    parent::setUp();

    //  Insert a row in civicrm_group creating option group
    //  from_email_address group
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createXMLDataSet(
        dirname(__FILE__) . '/dataset/group_admins.xml'
      )
    );

    //  Insert a row in civicrm_group creating option group
    //  from_email_address group
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createXMLDataSet(
        dirname(__FILE__) . '/dataset/group_subscribers.xml'
      )
    );

    //  Insert a row in civicrm_group creating option group
    //  from_email_address group
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createXMLDataSet(
        dirname(__FILE__) . '/dataset/group_nesting.xml'
      )
    );
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  protected function tearDown() {
    //  Truncate the tables
    $this->quickCleanup(
      array(
        'civicrm_group',
        'civicrm_group_nesting',
        'civicrm_contact',
        'civicrm_uf_group',
        'civicrm_uf_join',
        'civicrm_uf_match',
      )
    );
  }

  ///////////////// civicrm_group_nesting_get methods

  /**
   * Test civicrm_group_nesting_get.
   */
  public function testGet() {
    $params = array(
      'parent_group_id' => 1,
      'child_group_id' => 2,
    );

    $result = $this->callAPIAndDocument('group_nesting', 'get', $params, __FUNCTION__, __FILE__);
    // expected data loaded in setUp
    $expected = array(
      1 => array(
        'id' => 1,
        'child_group_id' => 2,
        'parent_group_id' => 1,
      ),
    );

    $this->assertEquals($expected, $result['values']);
  }

  /**
   * Test civicrm_group_nesting_get with just one
   * param (child_group_id).
   */
  public function testGetWithChildGroupId() {
    $params = array(
      'child_group_id' => 4,
    );

    $result = $this->callAPISuccess('group_nesting', 'get', $params);

    // expected data loaded in setUp
    $expected = array(
      3 => array(
        'id' => 3,
        'child_group_id' => 4,
        'parent_group_id' => 1,
      ),
      4 => array(
        'id' => 4,
        'child_group_id' => 4,
        'parent_group_id' => 2,
      ),
    );

    $this->assertEquals($expected, $result['values']);
  }

  /**
   * Test civicrm_group_nesting_get with just one
   * param (parent_group_id).
   */
  public function testGetWithParentGroupId() {
    $params = array(
      'parent_group_id' => 1,
    );

    $result = $this->callAPISuccess('group_nesting', 'get', $params);

    // expected data loaded in setUp
    $expected = array(
      1 => array(
        'id' => 1,
        'child_group_id' => 2,
        'parent_group_id' => 1,
      ),
      2 => array(
        'id' => 2,
        'child_group_id' => 3,
        'parent_group_id' => 1,
      ),
      3 => array(
        'id' => 3,
        'child_group_id' => 4,
        'parent_group_id' => 1,
      ),
    );

    $this->assertEquals($expected, $result['values']);
  }

  /**
   * Test civicrm_group_nesting_get for no records results.
   * Success expected. (these tests are of marginal value as are in syntax conformance,
   * don't copy & paste
   */
  public function testGetEmptyResults() {
    $params = array(
      'parent_group_id' => 1,
      'child_group_id' => 700,
    );
    $result = $this->callAPISuccess('group_nesting', 'get', $params);
  }

  ///////////////// civicrm_group_nesting_create methods

  /**
   * Test civicrm_group_nesting_create.
   */
  public function testCreate() {
    // groups id=1 and id=2 loaded in setUp
    $params = array(
      'parent_group_id' => 1,
      'child_group_id' => 3,
    );

    $result = $this->callAPIAndDocument('group_nesting', 'create', $params, __FUNCTION__, __FILE__);

    // we have 4 group nesting records in the example
    // data, expecting next number to be the id for newly created
    $id = 5;
    $this->assertDBState('CRM_Contact_DAO_GroupNesting', $id, $params);
  }

  /**
   * Test civicrm_group_nesting_create with empty parameter array.
   * Error expected.
   */
  public function testCreateWithEmptyParams() {
    $result = $this->callAPIFailure('group_nesting', 'create', array());
  }

  ///////////////// civicrm_group_nesting_remove methods

  /**
   * Test civicrm_group_nesting_remove.
   */
  public function testDelete() {
    // groups id=1 and id=2 loaded in setUp
    $getparams = array(
      'parent_group_id' => 1,
      'child_group_id' => 2,
    );

    $result = $this->callAPISuccess('group_nesting', 'get', $getparams);
    $params = array('id' => $result['id']);
    $result = $this->callAPIAndDocument('group_nesting', 'delete', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $this->callAPISuccess('group_nesting', 'getcount', $getparams));
  }

  /**
   * Test civicrm_group_nesting_remove with empty parameter array.
   * Error expected.
   */
  public function testDeleteWithEmptyParams() {
    $result = $this->callAPIFailure('group_nesting', 'delete', array());
  }

}
