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
