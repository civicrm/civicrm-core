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

/**
 * Test class for Batch API - civicrm_batch_*
 *
 *  @package CiviCRM_APIv3
 */
class api_v3_BatchTest extends CiviUnitTestCase {
  public $_eNoticeCompliant = TRUE;
  protected $_apiversion;
  protected $_params;
  /**
   *  Constructor
   *
   *  Initialize configuration
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   *
   * @access protected
   */
  protected function setUp() {
    $this->_apiversion = 3;
    $this->_params = array(
      'version' => $this->_apiversion,
    );
    parent::setUp();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   * @access protected
   */
  protected function tearDown() {}

  /**
   * Create a sample batch
   */
  function batchCreate() {
    $params = $this->_params;
    $params['name'] = $params['title'] = 'Batch_' . mt_rand(0, 999999);
    $params['status_id'] = 1;
    $result = civicrm_api('batch', 'create', $params);
    return $result['id'];
  }

  ///////////////// civicrm_batch_get methods

  /**
   * Test civicrm_batch_get with wrong params type.
   */
  public function testGetWrongParamsType() {
    $params = 'is_string';
    $result = $this->callAPIFailure('batch', 'get', $params);
    $this->assertEquals('Input variable `params` is not an array', $result['error_message'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_batch_get - success expected.
   */
  public function testGet() {
    $params = array(
      'id' => $this->batchCreate(),
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('batch', 'get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals($params['id'], $result['id'], 'In line ' . __LINE__);
  }


  ///////////////// civicrm_batch_create methods

  /**
   * Test civicrm_batch_create with empty params.
   */
  function testCreateEmptyParams() {
    $this->callAPIFailure('batch', 'create', $this->_params);
  }

  /**
   * Test civicrm_batch_create - success expected.
   */
  function testCreate() {
    $params = array(
      'name' => 'New_Batch_03',
      'title' => 'New Batch 03',
      'description' => 'This is description for New Batch 03',
      'total' => '300.33',
      'item_count' => 3,
      'status_id' => 1,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('batch', 'create', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertNotNull($result['id'], 'In line ' . __LINE__);

    // Fetch batch and compare values
    $saved = civicrm_api('batch', 'getSingle', $result);
    unset($params['version']);
    foreach ($params as $key => $value) {
      $this->assertEquals($value, $saved[$key], 'In line ' . __LINE__);
    }
  }

  /**
   * Test civicrm_batch_create with id.
   */
  function testUpdate() {
    $params = array(
      'name' => 'New_Batch_04',
      'title' => 'New Batch 04',
      'description' => 'This is description for New Batch 04',
      'total' => '400.44',
      'item_count' => 4,
      'version' => $this->_apiversion,
      'id' => $this->batchCreate(),
    );

    $result = civicrm_api('batch', 'create', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertNotNull($result['id'], 'In line ' . __LINE__);

    // Fetch batch and compare values
    $saved = civicrm_api('batch', 'getSingle', $result);
    unset($params['version']);
    foreach ($params as $key => $value) {
      $this->assertEquals($value, $saved[$key], 'In line ' . __LINE__);
    }
  }

  ///////////////// civicrm_batch_delete methods

  /**
   * Test civicrm_batch_delete without batch id.
   */
  function testDeleteWithoutBatchId() {
    $result = civicrm_api('batch', 'delete', array('version' => $this->_apiversion));
    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals('Mandatory key(s) missing from params array: id', $result['error_message'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_batch_delete with wrong batch id type.
   */
  function testDeleteWrongParams() {
    $result = $this->callAPIFailure('batch', 'delete', 'tyttyd');
    $this->assertEquals('Input variable `params` is not an array', $result['error_message'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_batch_delete using the old $params['batch_id'] syntax.
   */
  function testBatchDeleteOldSyntax() {
    $batchID = $this->batchCreate();
    $params = array(
      'batch_id' => $batchID,
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('batch', 'delete', $params);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_batch_delete using the new $params['id'] syntax
   */
  function testBatchDeleteCorrectSyntax() {
    $batchID = $this->batchCreate();
    $params = array(
      'id' => $batchID,
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('batch', 'delete', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
  }

}
