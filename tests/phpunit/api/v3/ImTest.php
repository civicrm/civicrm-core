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
 *  Test APIv3 civicrm_im_* functions
 *
 *  @package CiviCRM_APIv3
 *  @subpackage API_Contact
 */

class api_v3_ImTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $params;
  protected $id;
  protected $_entity;
  public $_eNoticeCompliant = TRUE;
  public $DBResetRequired = FALSE; function setUp() {
    parent::setUp();

    $this->_entity     = 'im';
    $this->_apiversion = 3;
    $this->_contactID  = $this->organizationCreate();
    $this->params  = array(
      'version' => 3,
      'contact_id' => $this->_contactID,
      'name' => 'My Yahoo IM Handle',
      'location_type_id' => 1,
      'provider_id' => 1,
    );
  }

  function tearDown() {
    $this->quickCleanup(array(
      'civicrm_im',
      'civicrm_contact'
    ));
  }

  public function testCreateIm() {
    $result = civicrm_api($this->_entity, 'create', $this->params);
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->getAndCheck($this->params, $result['id'], $this->_entity);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
  }

  public function testGetIm() {
    $result = civicrm_api($this->_entity, 'create', $this->params);
    $result = civicrm_api($this->_entity, 'get', $this->params);
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
    civicrm_api($this->_entity, 'delete', array('version' => 3, 'id' => $result['id']));
  }

  public function testDeleteIm() {
    $result = civicrm_api($this->_entity, 'create', $this->params);
    $this->assertAPISuccess($result, 'in line ' . __LINE__);
    $deleteParams = array('version' => 3, 'id' => $result['id']);
    $result = civicrm_api($this->_entity, 'delete', $deleteParams);
    $this->documentMe($deleteParams, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $checkDeleted = civicrm_api($this->_entity, 'get', array('version' => 3));
    $this->assertEquals(0, $checkDeleted['count'], 'In line ' . __LINE__);
  }
  public function testDeleteImInvalid() {
    $result = civicrm_api($this->_entity, 'create', $this->params);
    $this->assertAPISuccess($result, 'in line ' . __LINE__);
    $deleteParams = array('version' => 3, 'id' => 600);
    $result = civicrm_api($this->_entity, 'delete', $deleteParams);
    $this->assertEquals(1,$result['is_error'], 'In line ' . __LINE__);
    $checkDeleted = civicrm_api($this->_entity, 'get', array('version' => 3));
    $this->assertEquals(1, $checkDeleted['count'], 'In line ' . __LINE__);
  }
}
