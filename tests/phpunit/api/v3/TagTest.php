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
  protected $_apiversion =3;
  public $_eNoticeCompliant = TRUE;
  function setUp() {
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
    $params = array('name' => 'Wrong Tag Name');
    $result = $this->callAPISuccess('tag', 'get', $params);
    $this->assertEquals(0, $result['count'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_tag_get - success expected.
   */
  public function testGet() {
    $tag = $this->tagCreate(NULL);

    $params = array(
      'id' => $tag['id'],
      'name' => $tag['values'][$tag['id']]['name'],
    );
    $result = $this->callAPIAndDocument('tag', 'get', $params, __FUNCTION__, __FILE__);
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

    $params = array(
      'id' => $tag['id'],
      'name' => $tag['values'][$tag['id']]['name'],
      'return' => array('name'),
    );
    $result = $this->callAPIAndDocument('tag', 'get', $params, __FUNCTION__, __FILE__, $description, $subfile);
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
    $result = $this->callAPIFailure('tag', 'create', array(),'Mandatory key(s) missing from params array: name');
  }

  /**
   * Test civicrm_tag_create
   */
  function testCreatePasstagInParams() {
    $params = array(
      'tag' => 10,
      'name' => 'New Tag23',
      'description' => 'This is description for New Tag 02',
    );
    $result = $this->callAPISuccess('tag', 'create', $params);
    $this->assertEquals(10, $result['id'], 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_tag_create - success expected.
   */
  function testCreate() {
    $params = array(
      'name' => 'New Tag3',
      'description' => 'This is description for New Tag 02',
    );

    $result = $this->callAPIAndDocument('tag', 'create', $params, __FUNCTION__, __FILE__);
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
      'used_for' => 'civicrm_contribution',
    );
    $result = $this->callAPISuccess('tag', 'create', $params);
    $check = $this->callAPISuccess('tag', 'get', array());
    $this->getAndCheck($params, $result['id'], 'tag', 0, __FUNCTION__ . ' tag first created');
    unset($params['used_for']);
    $params['id'] = $result['id'];
    $result = $this->callAPISuccess('tag', 'create', $params);
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
    $result = $this->callAPIFailure('tag', 'delete', array(), 'Mandatory key(s) missing from params array: id');
  }

  /**
   * Test civicrm_tag_delete without tag id.
   */
  function testDeleteWithoutTagId() {
    $result = $this->callAPIFailure('tag', 'delete', array(), 'Mandatory key(s) missing from params array: id');
  }

  /**
   * Test civicrm_tag_delete .
   */
  function testTagDeleteOldSyntax() {
    $tagID = $this->tagCreate(NULL);
    $params = array(
      'tag_id' => $tagID['id'],
    );
    $result = $this->callAPISuccess('tag', 'delete', $params);
  }

  /**
   * Test civicrm_tag_delete = $params['id'] is correct
   */
  function testTagDeleteCorrectSyntax() {
    $tagID = $this->tagCreate(NULL);
    $params = array(
      'id' => $tagID['id'],
    );
    $result = $this->callAPIAndDocument('tag', 'delete', $params, __FUNCTION__, __FILE__);
  }

  function testTagGetfields() {
    $description = "demonstrate use of getfields to interogate api";
    $params      = array('action' => 'create');
    $result      = $this->callAPIAndDocument('tag', 'getfields', $params, __FUNCTION__, __FILE__, $description, NULL, 'getfields');
    $this->assertEquals('civicrm_contact', $result['values']['used_for']['api.default']);
  }
}

