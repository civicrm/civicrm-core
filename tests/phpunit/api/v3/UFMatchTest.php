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
class api_v3_UFMatchTest extends CiviUnitTestCase {
  /**
   * ids from the uf_group_test.xml fixture
   * @var int
   */
  protected $_ufGroupId = 11;
  protected $_ufFieldId;
  protected $_contactId;
  protected $_params = [];

  protected function setUp() {
    parent::setUp();
    $this->quickCleanup(
      [
        'civicrm_group',
        'civicrm_contact',
        'civicrm_uf_group',
        'civicrm_uf_join',
        'civicrm_uf_match',
      ]
    );
    $this->_contactId = $this->individualCreate();
    $this->loadXMLDataSet(dirname(__FILE__) . '/dataset/uf_group_test.xml');

    $this->_params = [
      'contact_id' => $this->_contactId,
      'uf_id' => '2',
      'uf_name' => 'blahdyblah@gmail.com',
      'domain_id' => 1,
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
   * Fetch contact id by uf id.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetUFMatchID($version) {
    $this->_apiversion = $version;
    $params = [
      'uf_id' => 42,
    ];
    $result = $this->callAPISuccess('uf_match', 'get', $params);
    $this->assertEquals($result['values'][$result['id']]['contact_id'], 69);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetUFMatchIDWrongParam($version) {
    $this->_apiversion = $version;
    $params = 'a string';
    $result = $this->callAPIFailure('uf_match', 'get', $params);
  }

  /**
   * Fetch uf id by contact id.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetUFID($version) {
    $this->_apiversion = $version;
    $params = [
      'contact_id' => 69,
    ];
    $result = $this->callAPIAndDocument('uf_match', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['values'][$result['id']]['uf_id'], 42);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetUFIDWrongParam($version) {
    $this->_apiversion = $version;
    $params = 'a string';
    $result = $this->callAPIFailure('uf_match', 'get', $params);
  }

  /**
   * Test civicrm_activity_create() using example code
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testUFMatchGetExample($version) {
    $this->_apiversion = $version;
    require_once 'api/v3/examples/UFMatch/Get.ex.php';
    $result = UF_match_get_example();
    $expectedResult = UF_match_get_expectedresult();
    $this->assertEquals($result, $expectedResult);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreate($version) {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess('uf_match', 'create', $this->_params);
    $this->getAndCheck($this->_params, $result['id'], 'uf_match');
  }

  /**
   * Test Civi to CMS email sync optional
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testUFNameMatchSync($version) {
    $this->_apiversion = $version;
    $this->callAPISuccess('uf_match', 'create', $this->_params);
    $email1 = substr(sha1(rand()), 0, 7) . '@test.com';
    $email2 = substr(sha1(rand()), 0, 7) . '@test.com';

    // Case A: Enable CMS integration
    Civi::settings()->set('syncCMSEmail', TRUE);
    $this->callAPISuccess('email', 'create', [
      'contact_id' => $this->_contactId,
      'email' => $email1,
      'is_primary' => 1,
    ]);
    $ufName = $this->callAPISuccess('uf_match', 'getvalue', [
      'contact_id' => $this->_contactId,
      'return' => 'uf_name',
    ]);
    $this->assertEquals($email1, $ufName);

    // Case B: Disable CMS integration
    Civi::settings()->set('syncCMSEmail', FALSE);
    $this->callAPISuccess('email', 'create', [
      'contact_id' => $this->_contactId,
      'email' => $email2,
      'is_primary' => 1,
    ]);
    $ufName = $this->callAPISuccess('uf_match', 'getvalue', [
      'contact_id' => $this->_contactId,
      'return' => 'uf_name',
    ]);
    $this->assertNotEquals($email2, $ufName, 'primary email will not match if changed on disabled CMS integration setting');
    $this->assertEquals($email1, $ufName);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testDelete($version) {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess('uf_match', 'create', $this->_params);
    $this->assertEquals(1, $this->callAPISuccess('uf_match', 'getcount', [
      'id' => $result['id'],
    ]));
    $this->callAPISuccess('uf_match', 'delete', [
      'id' => $result['id'],
    ]);
    $this->assertEquals(0, $this->callAPISuccess('uf_match', 'getcount', [
      'id' => $result['id'],
    ]));
  }

}
