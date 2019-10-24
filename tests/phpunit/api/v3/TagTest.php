<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 *  Test APIv3 civicrm_tag_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Core
 * @group headless
 */
class api_v3_TagTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  /**
   * @var array
   * @ids array of values to be cleaned up in the tear down
   */
  protected $ids = [];
  /**
   * Tag id.
   *
   * @var int
   */
  protected $tag = [];

  protected $tagID;

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
    $this->tag = $this->tagCreate();
    $this->ids['tag'][] = $this->tagID = $this->tag['id'];
  }

  ///////////////// civicrm_tag_get methods

  /**
   * Test civicrm_tag_get with wrong params.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetWrongParams($version) {
    $this->_apiversion = $version;
    $params = ['name' => 'Wrong Tag Name'];
    $result = $this->callAPISuccess('tag', 'get', $params);
    $this->assertEquals(0, $result['count']);
  }

  /**
   * Test civicrm_tag_get - success expected.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGet($version) {
    $this->_apiversion = $version;
    $params = [
      'id' => $this->tagID,
      'name' => $this->tag['name'],
    ];
    $result = $this->callAPIAndDocument('tag', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($this->tag['description'], $result['values'][$this->tagID]['description']);
    $this->assertEquals($this->tag['name'], $result['values'][$this->tagID]['name']);
  }

  /**
   * Test civicrm_tag_get - success expected.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetReturnArray($version) {
    $this->_apiversion = $version;
    $description = "Demonstrates use of Return as an array.";
    $subfile = "GetReturnArray";

    $params = [
      'id' => $this->tagID,
      'name' => $this->tag['name'],
      'return' => ['name'],
    ];
    $result = $this->callAPIAndDocument('tag', 'get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertTrue(empty($result['values'][$this->tagID]['description']));
    $this->assertEquals($this->tag['name'], $result['values'][$this->tagID]['name']);
  }

  ///////////////// civicrm_tag_create methods

  /**
   * Test civicrm_tag_create with empty params.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateEmptyParams($version) {
    $this->_apiversion = $version;
    $result = $this->callAPIFailure('tag', 'create', [], 'name');
  }

  /**
   * Test civicrm_tag_create.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreatePasstagInParams($version) {
    $this->_apiversion = $version;
    $params = [
      'tag' => 10,
      'name' => 'New Tag23',
      'description' => 'This is description for New Tag 02',
    ];
    $result = $this->callAPISuccess('tag', 'create', $params);
    $this->assertEquals(10, $result['id']);
  }

  /**
   * Test civicrm_tag_create - success expected.
   * Skipping v4 because used_for is an array
   */
  public function testCreate() {
    $params = [
      'name' => 'Super Heros',
      'description' => 'Outside undie-wearers',
    ];
    $result = $this->callAPIAndDocument('tag', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['id']);
    $params['used_for'] = 'civicrm_contact';
    $this->getAndCheck($params, $result['id'], 'tag');
  }

  /**
   * Test civicrm_tag_create activity tag- success expected.
   *
   * Test checks that used_for is set and not over-written by default on update.
   *
   * Skipping v4 because used_for is an array
   */
  public function testCreateEntitySpecificTag() {
    $params = [
      'name' => 'New Tag4',
      'description' => 'This is description for New Activity tag',
      'used_for' => 'civicrm_activity',
    ];
    $result = $this->callAPISuccess('tag', 'create', $params);
    $this->callAPISuccess('tag', 'get', []);
    $this->getAndCheck($params, $result['id'], 'tag', 0, __FUNCTION__ . ' tag first created');
    unset($params['used_for']);
    $params['id'] = $result['id'];
    $result = $this->callAPISuccess('tag', 'create', $params);
    $params['used_for'] = 'civicrm_activity';
    $this->getAndCheck($params, $result['id'], 'tag', 1, __FUNCTION__ . ' tag updated in line ' . __LINE__);
  }

  ///////////////// civicrm_tag_delete methods

  /**
   * Test civicrm_tag_delete without tag id.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testDeleteWithoutTagId($version) {
    $this->_apiversion = $version;
    $result = $this->callAPIFailure('tag', 'delete', []);
  }

  /**
   * Test civicrm_tag_delete .
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testTagDeleteOldSyntax($version) {
    $this->_apiversion = $version;
    $params = [
      'tag_id' => $this->tagID,
    ];
    $result = $this->callAPISuccess('tag', 'delete', $params);
    unset($this->ids['tag']);
  }

  /**
   * Test civicrm_tag_delete = $params['id'] is correct
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testTagDeleteCorrectSyntax($version) {
    $this->_apiversion = $version;
    $params = [
      'id' => $this->tagID,
    ];
    $result = $this->callAPIAndDocument('tag', 'delete', $params, __FUNCTION__, __FILE__);
    unset($this->ids['tag']);
  }

  public function testTagGetfields() {
    $description = "Demonstrate use of getfields to interrogate api.";
    $params = ['action' => 'create'];
    $result = $this->callAPIAndDocument('tag', 'getfields', $params, __FUNCTION__, __FILE__, $description, NULL);
    $this->assertEquals('civicrm_contact', $result['values']['used_for']['api.default']);
  }

  public function testTagGetList() {
    $description = "Demonstrates use of api.getlist for autocomplete and quicksearch applications.";
    $params = [
      'input' => $this->tag['name'],
      'extra' => ['used_for'],
    ];
    $result = $this->callAPIAndDocument('tag', 'getlist', $params, __FUNCTION__, __FILE__, $description);
    $this->assertEquals($this->tag['id'], $result['values'][0]['id']);
    $this->assertEquals($this->tag['description'], $result['values'][0]['description'][0]);
    $this->assertEquals($this->tag['used_for'], $result['values'][0]['extra']['used_for']);
  }

}
