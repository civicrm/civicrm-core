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
 * Test class for Template API - civicrm_msg_template*
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_MessageTemplateTest extends CiviUnitTestCase {
  /**
   * Assume empty database with just civicrm_data.
   */
  protected $entity = 'MessageTemplate';
  protected $params;


  public function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
    $this->useTransaction(TRUE);
    $template = CRM_Core_DAO::createTestObject('CRM_Core_DAO_MessageTemplate')->toArray();
    $this->params = array(
      'msg_title' => $template['msg_title'],
      'msg_subject' => $template['msg_subject'],
      'msg_text' => $template['msg_text'],
      'msg_html' => $template['msg_html'],
      'workflow_id' => $template['workflow_id'],
      'is_default' => $template['is_default'],
      'is_reserved' => $template['is_reserved'],
    );
  }

  /**
   * Test create function succeeds.
   */
  public function testCreate() {
    $result = $this->callAPIAndDocument('MessageTemplate', 'create', $this->params, __FUNCTION__, __FILE__);
    $this->getAndCheck($this->params, $result['id'], $this->entity);
  }

  /**
   * Test get function succeeds.
   *
   * This is actually largely tested in the get action on create.
   *
   * Add extra checks for any 'special' return values or
   * behaviours
   */
  public function testGet() {
    $result = $this->callAPIAndDocument('MessageTemplate', 'get', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  /**
   * Check the delete function succeeds.
   */
  public function testDelete() {
    $entity = $this->createTestEntity();
    $result = $this->callAPIAndDocument('MessageTemplate', 'delete', array('id' => $entity['id']), __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess($this->entity, 'get', array(
      'id' => $entity['id'],
    ));
    $this->assertEquals(0, $checkDeleted['count']);
  }

}
