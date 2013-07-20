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
 *  Test APIv3 civicrm_tag_* functions
 *
 *  @package CiviCRM_APIv3
 *  @subpackage API_Core
 */

class api_v3_TagTest extends CiviUnitTestCase {
  protected $_apiversion;
  public $_eNoticeCompliant = TRUE;
  function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
  }

  function tearDown() {}

  ///////////////// civicrm_tag_get methods

  /**
   * Test civicrm_tag_get with wrong params type.
   */
  public function testGetWrongParamsType() {
    $params = 'is_string';
    $result = $this->callAPIFailure('tag', 'get', $params);
    $this->assertEquals('Input variable `params` is not an array', $result['error_message'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_tag_get with wrong params.
   */
  public function testGetWrongParams() {
    $params = array('name' => 'Wrong Tag Name', 'version' => $this->_apiversion);
    $result = civicrm_api('tag', 'get', $params);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(0, $result['count'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_tag_get - success expected.
   */
  public function testGet() {
    $tag = $this->tagCreate(NULL);
    $this->assertEquals(0, $tag['is_error'], 'In line ' . __LINE__);

    $params = array(
      'id' => $tag['id'],
      'name' => $tag['values'][$tag['id']]['name'],
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('tag', 'get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals($tag['values'][$tag['id']]['description'], $result['values'][$tag['id']]['description'], 'In line ' . __LINE__);
    $this->assertEquals($tag['values'][$tag['id']]['name'], $result['values'][$tag['id']]['name'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_tag_get - success expected.
   */
  public function testGetReturnArray() {
    $description = "demonstrates use of Return as an array";
    $subfile     = "getReturnArray";
    $tag         = $this->tagCreate(NULL);
    $this->assertEquals(0, $tag['is_error'], 'In line ' . __LINE__);

    $params = array(
      'id' => $tag['id'],
      'name' => $tag['values'][$tag['id']]['name'],
      'version' => $this->_apiversion,
      'return' => array('name'),
    );
    $result = civicrm_api('tag', 'get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertTrue(empty($result['values'][$tag['id']]['description']), 'In line ' . __LINE__);
    $this->assertEquals($tag['values'][$tag['id']]['name'], $result['values'][$tag['id']]['name'], 'In line ' . __LINE__);
  }

  ///////////////// civicrm_tag_create methods

  /**
   * Test civicrm_tag_create with wrong params type.
   */
  function testCreateWrongParamsType() {
    $params = 'a string';
    $result = $this->callAPIFailure('tag', 'create', $params);
    $this->assertEquals('Input variable `params` is not an array', $result['error_message'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_tag_create with empty params.
   */
  function testCreateEmptyParams() {
    $params = array('version' => $this->_apiversion);
    $result = $this->callAPIFailure('tag', 'create', $params);
    $this->assertEquals('Mandatory key(s) missing from params array: name', $result['error_message'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_tag_create
   */
  function testCreatePasstagInParams() {
    $params = array(
      'tag' => 10,
      'name' => 'New Tag23',
      'description' => 'This is description for New Tag 02',
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('tag', 'create', $params);
    $this->assertEquals(10, $result['id'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_tag_create - success expected.
   */
  function testCreate() {
    $params = array(
      'name' => 'New Tag3',
      'description' => 'This is description for New Tag 02',
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('tag', 'create', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertNotNull($result['id'], 'In line ' . __LINE__);
    $params['used_for'] = 'civicrm_contact';
    $this->getAndCheck($params, $result['id'], 'tag');
  }

  /**
   * Test civicrm_tag_create contribution tag- success expected. Test checks that used_for is set
   * and not over-written by default on update
   */
  function testCreateContributionTag() {
    $params = array(
      'name' => 'New Tag4',
      'description' => 'This is description for New Cont tag',
      'version' => $this->_apiversion,
      'used_for' => 'civicrm_contribution',
    );
    $result = civicrm_api('tag', 'create', $params);
    $this->assertAPISuccess($result, "contribution tag created");
    $check = civicrm_api('tag', 'get', array('version' => 3));
    $this->getAndCheck($params, $result['id'], 'tag', 0, __FUNCTION__ . ' tag first created');
    unset($params['used_for']);
    $this->assertAPISuccess($result, 'tag created');
    $params['id'] = $result['id'];
    $result = civicrm_api('tag', 'create', $params);
    $this->assertAPISuccess($result);
    $params['used_for'] = 'civicrm_contribution';
    $this->getAndCheck($params, $result['id'], 'tag', 1, __FUNCTION__ . ' tag updated in line ' . __LINE__);
  }
  ///////////////// civicrm_tag_delete methods

  /**
   * Test civicrm_tag_delete with wrong parameters type.
   */
  function testDeleteWrongParamsType() {
    $tag = 'is string';
    $result = $this->callAPIFailure('tag', 'delete', $tag);
    $this->assertEquals('Input variable `params` is not an array', $result['error_message'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_tag_delete with empty parameters.
   */
  function testDeleteEmptyParams() {
    $tag = array('version' => $this->_apiversion);
    $result = $this->callAPIFailure('tag', 'delete', $tag);
    $this->assertEquals('Mandatory key(s) missing from params array: id', $result['error_message'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_tag_delete without tag id.
   */
  function testDeleteWithoutTagId() {
    $tag = array('version' => 3);

    $result = $this->callAPIFailure('tag', 'delete', $tag);
    $this->assertEquals('Mandatory key(s) missing from params array: id', $result['error_message'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_tag_delete with wrong tag id type.
   */
  function testDeleteWrongParams() {
    $result = $this->callAPIFailure('tag', 'delete', 'tyttyd');
    $this->assertEquals('Input variable `params` is not an array', $result['error_message'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_tag_delete .
   */
  function testTagDeleteOldSyntax() {
    $tagID = $this->tagCreate(NULL);
    $params = array(
      'tag_id' => $tagID['id'],
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('tag', 'delete', $params);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_tag_delete = $params['id'] is correct
   */
  function testTagDeleteCorrectSyntax() {
    $tagID = $this->tagCreate(NULL);
    $params = array(
      'id' => $tagID['id'],
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('tag', 'delete', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
  }

  function testTaggetfields() {
    $description = "demonstrate use of getfields to interogate api";
    $params      = array('version' => 3, 'action' => 'create');
    $result      = civicrm_api('tag', 'getfields', $params);
    $this->assertEquals('civicrm_contact', $result['values']['used_for']['api.default']);
  }
}

