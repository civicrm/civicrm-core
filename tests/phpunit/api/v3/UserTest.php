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
class api_v3_UserWebsiteTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $params;
  protected $_entity = 'User';
  protected $contactID;

  public $DBResetRequired = FALSE;

  public function setUp() {
    parent::setUp();
    $this->contactID = $this->createLoggedInUser();
    $this->params = array(
      'contact_id' => $this->contactID,
      'sequential' => 1,
    );
  }

  public function testUserGet() {
    $result = $this->callAPIAndDocument($this->_entity, 'get', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertEquals($this->contactID, $result['values'][0]['contact_id']);
    $this->assertEquals(6, $result['values'][0]['id']);
    $this->assertEquals('superman', $result['values'][0]['name']);
  }

  /**
   * Test retrieval of label metadata.
   */
  public function testGetFields() {
    $result = $this->callAPIAndDocument($this->_entity, 'getfields', array('action' => 'get'), __FUNCTION__, __FILE__);
    $this->assertArrayKeyExists('name', $result['values']);
  }

}
