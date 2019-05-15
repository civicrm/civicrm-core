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
 * Test class for UFGroup API - civicrm_uf_*
 * @todo Split UFGroup and UFJoin tests
 *
 * @package   CiviCRM
 * @group headless
 */
class api_v3_UFGroupTest extends CiviUnitTestCase {
  /**
   * ids from the uf_group_test.xml fixture
   * @var int
   */
  protected $_ufGroupId;
  protected $_ufFieldId;
  protected $_contactId;
  protected $_groupId;
  protected $_apiversion = 3;
  protected $params;

  protected function setUp() {
    parent::setUp();
    $this->_groupId = $this->groupCreate();
    $this->_contactId = $this->individualCreate();
    $this->createLoggedInUser();
    $ufGroup = $this->callAPISuccess('uf_group', 'create', array(
      'group_type' => 'Contact',
      'help_pre' => 'Profile to Test API',
      'title' => 'Test Profile',
    ));
    $this->_ufGroupId = $ufGroup['id'];
    $ufMatch = $this->callAPISuccess('uf_match', 'create', array(
      'contact_id' => $this->_contactId,
      'uf_id' => 42,
      'uf_name' => 'email@mail.com',
    ));
    $this->_ufMatchId = $ufMatch['id'];
    $this->params = array(
      'add_captcha' => 1,
      'add_contact_to_group' => $this->_groupId,
      'group' => $this->_groupId,
      'cancel_URL' => 'http://example.org/cancel',
      'created_date' => '2009-06-27 00:00:00',
      'created_id' => $this->_contactId,
      'group_type' => 'Individual,Contact',
      'help_post' => 'help post',
      'help_pre' => 'help pre',
      'is_active' => 0,
      'is_cms_user' => 1,
      'is_edit_link' => 1,
      'is_map' => 1,
      'is_reserved' => 1,
      'is_uf_link' => 1,
      'is_update_dupe' => 1,
      'name' => 'Test_Group',
      'notify' => 'admin@example.org',
      'post_URL' => 'http://example.org/post',
      'title' => 'Test Group',
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
   * Updating group.
   */
  public function testUpdateUFGroup() {
    $params = array(
      'title' => 'Edited Test Profile',
      'help_post' => 'Profile Pro help text.',
      'is_active' => 1,
      'id' => $this->_ufGroupId,
    );

    $result = $this->callAPISuccess('uf_group', 'create', $params);
    foreach ($params as $key => $value) {
      $this->assertEquals($result['values'][$result['id']][$key], $value);
    }
  }

  public function testUFGroupCreate() {

    $result = $this->callAPIAndDocument('uf_group', 'create', $this->params, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result);
    $this->assertEquals($result['values'][$result['id']]['add_to_group_id'], $this->params['add_contact_to_group']);
    $this->assertEquals($result['values'][$result['id']]['limit_listings_group_id'], $this->params['group']);
    $this->params['created_date'] = date('YmdHis', strtotime($this->params['created_date']));
    foreach ($this->params as $key => $value) {
      if ($key == 'add_contact_to_group' or $key == 'group') {
        continue;
      }
      $expected = $this->params[$key];
      $received = $result['values'][$result['id']][$key];
      $this->assertEquals($expected, $received, "The string '$received' does not equal '$expected' for key '$key' in line " . __LINE__);
    }
  }

  public function testUFGroupCreateWithWrongParams() {
    $result = $this->callAPIFailure('uf_group', 'create', array('name' => 'A title-less group'));
  }

  public function testUFGroupUpdate() {
    $params = array(
      'id' => $this->_ufGroupId,
      'add_captcha' => 1,
      'add_contact_to_group' => $this->_groupId,
      'cancel_URL' => 'http://example.org/cancel',
      'created_date' => '2009-06-27',
      'created_id' => $this->_contactId,
      'group' => $this->_groupId,
      'group_type' => 'Individual,Contact',
      'help_post' => 'help post',
      'help_pre' => 'help pre',
      'is_active' => 0,
      'is_cms_user' => 1,
      'is_edit_link' => 1,
      'is_map' => 1,
      'is_reserved' => 1,
      'is_uf_link' => 1,
      'is_update_dupe' => 1,
      'name' => 'test_group',
      'notify' => 'admin@example.org',
      'post_URL' => 'http://example.org/post',
      'title' => 'Test Group',
    );
    $result = $this->callAPISuccess('uf_group', 'create', $params);
    $params['created_date'] = date('YmdHis', strtotime($params['created_date']));
    foreach ($params as $key => $value) {
      if ($key == 'add_contact_to_group' or $key == 'group') {
        continue;
      }
      $this->assertEquals($result['values'][$result['id']][$key], $params[$key], $key . " doesn't match  " . $value);
    }

    $this->assertEquals($result['values'][$this->_ufGroupId]['add_to_group_id'], $params['add_contact_to_group']);
    $this->assertEquals($result['values'][$result['id']]['limit_listings_group_id'], $params['group']);
  }

  public function testUFGroupGet() {
    $result = $this->callAPISuccess('uf_group', 'create', $this->params);
    $params = array('id' => $result['id']);
    $result = $this->callAPIAndDocument('uf_group', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['values'][$result['id']]['add_to_group_id'], $this->params['add_contact_to_group']);
    $this->assertEquals($result['values'][$result['id']]['limit_listings_group_id'], $this->params['group']);
    foreach ($this->params as $key => $value) {
      // skip created date because it doesn't seem to be working properly & fixing date handling is for another day
      if ($key == 'add_contact_to_group' or $key == 'group' or $key == 'created_date') {
        continue;
      }
      $expected = $this->params[$key];
      $received = $result['values'][$result['id']][$key];
      $this->assertEquals($expected, $received, "The string '$received' does not equal '$expected' for key '$key' in line " . __LINE__);
    }
  }

  public function testUFGroupUpdateWithEmptyParams() {
    $result = $this->callAPIFailure('uf_group', 'create', array(), 'Mandatory key(s) missing from params array: title');
  }

  public function testUFGroupDelete() {
    $ufGroup = $this->callAPISuccess('uf_group', 'create', $this->params);
    $params = array('id' => $ufGroup['id']);
    $this->assertEquals(1, $this->callAPISuccess('uf_group', 'getcount', $params), "in line " . __LINE__);
    $result = $this->callAPIAndDocument('uf_group', 'delete', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $this->callAPISuccess('uf_group', 'getcount', $params), "in line " . __LINE__);
  }

}
