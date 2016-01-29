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
 * Test class for Batch API - civicrm_batch_*
 *
 * @package CiviCRM_APIv3
 */
class api_v3_BatchTest extends CiviUnitTestCase {

  protected $_params = array();
  protected $_entity = 'batch';

  /**
   * Sets up the fixture, for example, opens a network connection.
   *
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  /**
   * Test civicrm_batch_get - success expected.
   */
  public function testGet() {
    $params = array(
      'id' => $this->batchCreate(),
    );
    $result = $this->callAPIAndDocument('batch', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($params['id'], $result['id']);
  }

  /**
   * Test civicrm_batch_create - success expected.
   */
  public function testCreate() {
    $params = array(
      'name' => 'New_Batch_03',
      'title' => 'New Batch 03',
      'description' => 'This is description for New Batch 03',
      'total' => '300.33',
      'item_count' => 3,
      'status_id' => 1,
    );

    $result = $this->callAPIAndDocument('batch', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['id']);
    $this->getAndCheck($params, $result['id'], $this->_entity);
  }

  /**
   * Test civicrm_batch_create with id.
   */
  public function testUpdate() {
    $params = array(
      'name' => 'New_Batch_04',
      'title' => 'New Batch 04',
      'description' => 'This is description for New Batch 04',
      'total' => '400.44',
      'item_count' => 4,
      'id' => $this->batchCreate(),
    );

    $result = $this->callAPIAndDocument('batch', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['id']);
    $this->getAndCheck($params, $result['id'], $this->_entity);
  }

  /**
   * Test civicrm_batch_delete using the old $params['batch_id'] syntax.
   */
  public function testBatchDeleteOldSyntax() {
    $batchID = $this->batchCreate();
    $params = array(
      'batch_id' => $batchID,
    );
    $result = $this->callAPISuccess('batch', 'delete', $params);
  }

  /**
   * Test civicrm_batch_delete using the new $params['id'] syntax.
   */
  public function testBatchDeleteCorrectSyntax() {
    $batchID = $this->batchCreate();
    $params = array(
      'id' => $batchID,
    );
    $result = $this->callAPIAndDocument('batch', 'delete', $params, __FUNCTION__, __FILE__);
  }

}
