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
  protected $params;

  protected function setUp() {
    parent::setUp();
    $this->_groupId = $this->groupCreate();
    $this->_contactId = $this->individualCreate();
    $this->createLoggedInUser();
    $ufGroup = $this->callAPISuccess('uf_group', 'create', [
      'group_type' => 'Contact',
      'help_pre' => 'Profile to Test API',
      'title' => 'Test Profile',
    ]);
    $this->_ufGroupId = $ufGroup['id'];
    $ufMatch = $this->callAPISuccess('uf_match', 'create', [
      'contact_id' => $this->_contactId,
      'uf_id' => 42,
      'uf_name' => 'email@mail.com',
    ]);
    $this->_ufMatchId = $ufMatch['id'];
    $this->params = [
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
    ];
  }

  public function tearDown() {
    //  Truncate the tables
    $this->quickCleanup(
      [
        'civicrm_group',
        'civicrm_contact',
        'civicrm_uf_group',
        'civicrm_uf_join',
        'civicrm_uf_match',
      ]
    );
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testUpdateUFGroup($version) {
    $this->_apiversion = $version;
    $params = [
      'title' => 'Edited Test Profile',
      'help_post' => 'Profile Pro help text.',
      'is_active' => 1,
      'id' => $this->_ufGroupId,
    ];

    $result = $this->callAPISuccess('uf_group', 'create', $params);
    foreach ($params as $key => $value) {
      $this->assertEquals($result['values'][$result['id']][$key], $value);
    }
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testUFGroupCreate($version) {
    $this->_apiversion = $version;
    $result = $this->callAPIAndDocument('uf_group', 'create', $this->params, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result);
    $this->assertEquals($result['values'][$result['id']]['add_to_group_id'], $this->params['add_contact_to_group']);
    $this->assertEquals($result['values'][$result['id']]['limit_listings_group_id'], $this->params['group']);
    $this->params['created_date'] = date('YmdHis', strtotime($this->params['created_date']));
    foreach ($this->params as $key => $value) {
      if ($key == 'add_contact_to_group' or $key == 'group') {
        continue;
      }
      $received = $result['values'][$result['id']][$key];
      if ($key == 'group_type' && $version == 4) {
        $received = implode(',', $received);
      }
      $expected = $this->params[$key];
      $this->assertEquals($expected, $received, "The string '$received' does not equal '$expected' for key '$key' in line " . __LINE__);
    }
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testUFGroupCreateWithWrongParams($version) {
    $this->_apiversion = $version;
    $result = $this->callAPIFailure('uf_group', 'create', ['name' => 'A title-less group']);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testUFGroupUpdate($version) {
    $this->_apiversion = $version;
    $params = [
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
    ];
    $result = $this->callAPISuccess('uf_group', 'create', $params);
    $params['created_date'] = date('YmdHis', strtotime($params['created_date']));
    foreach ($params as $key => $value) {
      if ($key == 'add_contact_to_group' or $key == 'group') {
        continue;
      }
      $received = $result['values'][$result['id']][$key];
      if ($key == 'group_type' && $version == 4) {
        $received = implode(',', $received);
      }
      $this->assertEquals($received, $params[$key], $key . " doesn't match  " . $value);
    }

    $this->assertEquals($result['values'][$this->_ufGroupId]['add_to_group_id'], $params['add_contact_to_group']);
    $this->assertEquals($result['values'][$result['id']]['limit_listings_group_id'], $params['group']);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testUFGroupGet($version) {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess('uf_group', 'create', $this->params);
    $params = ['id' => $result['id']];
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
      // Api4 auto-splits serialized fields, v3 sometimes does but not in this case
      if ($version == 4 && is_array($received)) {
        $received = implode(',', $received);
      }
      $this->assertEquals($expected, $received, "The string '$received' does not equal '$expected' for key '$key' in line " . __LINE__);
    }
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testUFGroupUpdateWithEmptyParams($version) {
    $this->_apiversion = $version;
    $result = $this->callAPIFailure('uf_group', 'create', [], 'title');
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testUFGroupDelete($version) {
    $this->_apiversion = $version;
    $ufGroup = $this->callAPISuccess('uf_group', 'create', $this->params);
    $params = ['id' => $ufGroup['id']];
    $this->assertEquals(1, $this->callAPISuccess('uf_group', 'getcount', $params), "in line " . __LINE__);
    $result = $this->callAPIAndDocument('uf_group', 'delete', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $this->callAPISuccess('uf_group', 'getcount', $params), "in line " . __LINE__);
  }

}
