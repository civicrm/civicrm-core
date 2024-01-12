<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *  Test APIv3 civicrm_user_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 * @group headless
 */
class api_v3_UserTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $params;
  protected $_entity = 'User';
  protected $contactID;

  public function setUp(): void {
    parent::setUp();
    $this->contactID = $this->createLoggedInUser();
    $this->params = [
      'contact_id' => $this->contactID,
      'sequential' => 1,
    ];
  }

  public function testUserGet(): void {
    $result = $this->callAPISuccess($this->_entity, 'get', $this->params);
    $this->assertEquals(1, $result['count']);
    $this->assertEquals($this->contactID, $result['values'][0]['contact_id']);
    $this->assertEquals(6, $result['values'][0]['id']);
    $this->assertEquals('superman', $result['values'][0]['name']);
  }

  /**
   * Test retrieval of label metadata.
   */
  public function testGetFields(): void {
    $result = $this->callAPISuccess($this->_entity, 'getfields', ['action' => 'get']);
    $this->assertArrayKeyExists('name', $result['values']);
  }

}
