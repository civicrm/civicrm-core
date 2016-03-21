<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * Test class for UFGroup API - civicrm_uf_*
 * @todo Split UFGroup and UFJoin tests
 *
 * @package   CiviCRM
 * @group headless
 */
class api_v3_UFMatchTest extends CiviUnitTestCase {
  // ids from the uf_group_test.xml fixture
  protected $_ufGroupId = 11;
  protected $_ufFieldId;
  protected $_contactId;
  protected $_apiversion;
  protected $_params = array();


  protected function setUp() {
    parent::setUp();
    $this->_apiversion = 3;
    $this->quickCleanup(
      array(
        'civicrm_group',
        'civicrm_contact',
        'civicrm_uf_group',
        'civicrm_uf_join',
        'civicrm_uf_match',
      )
    );
    $this->_contactId = $this->individualCreate();
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute(
      $this->_dbconn,
      $this->createFlatXMLDataSet(dirname(__FILE__) . '/dataset/uf_group_test.xml')
    );

    $this->_params = array(
      'contact_id' => $this->_contactId,
      'uf_id' => '2',
      'uf_name' => 'blahdyblah@gmail.com',
      'domain_id' => 1,
    );
  }

  public function tearDown() {
    //  Truncate the tables
    $this->quickCleanup(
      array(
        'civicrm_group',
        'civicrm_contact',
        'civicrm_uf_group',
        'civicrm_uf_join',
        'civicrm_uf_match',
      )
    );
  }

  /**
   * Fetch contact id by uf id.
   */
  public function testGetUFMatchID() {
    $params = array(
      'uf_id' => 42,
    );
    $result = $this->callAPISuccess('uf_match', 'get', $params);
    $this->assertEquals($result['values'][$result['id']]['contact_id'], 69);
  }

  public function testGetUFMatchIDWrongParam() {
    $params = 'a string';
    $result = $this->callAPIFailure('uf_match', 'get', $params);
  }

  /**
   * Fetch uf id by contact id.
   */
  public function testGetUFID() {
    $params = array(
      'contact_id' => 69,
    );
    $result = $this->callAPIAndDocument('uf_match', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['values'][$result['id']]['uf_id'], 42);
  }

  public function testGetUFIDWrongParam() {
    $params = 'a string';
    $result = $this->callAPIFailure('uf_match', 'get', $params);
  }

  /**
   * Test civicrm_activity_create() using example code
   */
  public function testUFMatchGetExample() {
    require_once 'api/v3/examples/UFMatch/Get.php';
    $result = UF_match_get_example();
    $expectedResult = UF_match_get_expectedresult();
    $this->assertEquals($result, $expectedResult);
  }

  public function testCreate() {
    $result = $this->callAPISuccess('uf_match', 'create', $this->_params);
    $this->getAndCheck($this->_params, $result['id'], 'uf_match');
  }

  public function testDelete() {
    $result = $this->callAPISuccess('uf_match', 'create', $this->_params);
    $this->assertEquals(1, $this->callAPISuccess('uf_match', 'getcount', array(
      'id' => $result['id'],
    )));
    $this->callAPISuccess('uf_match', 'delete', array(
      'id' => $result['id'],
    ));
    $this->assertEquals(0, $this->callAPISuccess('uf_match', 'getcount', array(
      'id' => $result['id'],
    )));
  }

}
