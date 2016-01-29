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
 *  Test APIv3 civicrm_website_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 */
class api_v3_WebsiteTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $params;
  protected $id;
  protected $_entity;

  public $DBResetRequired = FALSE;

  public function setUp() {
    parent::setUp();
    $this->useTransaction();

    $this->_entity = 'website';
    $this->_contactID = $this->organizationCreate();
    $this->params = array(
      'contact_id' => $this->_contactID,
      'url' => 'website.com',
      'website_type_id' => 1,
    );
  }

  public function testCreateWebsite() {
    $result = $this->callAPIAndDocument($this->_entity, 'create', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->getAndCheck($this->params, $result['id'], $this->_entity);
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  public function testGetWebsite() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $result = $this->callAPIAndDocument($this->_entity, 'get', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->callAPISuccess('website', 'delete', array('id' => $result['id']));
  }

  public function testDeleteWebsite() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $deleteParams = array('id' => $result['id']);
    $result = $this->callAPIAndDocument($this->_entity, 'delete', $deleteParams, __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get', array());
    $this->assertEquals(0, $checkDeleted['count']);
  }

  public function testDeleteWebsiteInvalid() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $deleteParams = array('id' => 600);
    $result = $this->callAPIFailure($this->_entity, 'delete', $deleteParams);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get', array());
    $this->assertEquals(1, $checkDeleted['count']);
  }

  /**
   * Test retrieval of metadata.
   */
  public function testGetMetadata() {
    $result = $this->callAPIAndDocument($this->_entity, 'get', array(
      'options' => array(
        'metadata' => array('fields'),
      ),
    ), __FUNCTION__, __FILE__, 'Demonostrates returning field metadata', 'GetWithMetadata');
    $this->assertEquals('Website', $result['metadata']['fields']['url']['title']);
  }

  /**
   * Test retrieval of label metadata.
   */
  public function testGetFields() {
    $result = $this->callAPIAndDocument($this->_entity, 'getfields', array('action' => 'get'), __FUNCTION__, __FILE__);
    $this->assertArrayKeyExists('url', $result['values']);
  }

}
