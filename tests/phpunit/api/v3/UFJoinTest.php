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
class api_v3_UFJoinTest extends CiviUnitTestCase {
  /**
   * ids from the uf_group_test.xml fixture
   * @var int
   */
  protected $_ufGroupId = 11;
  protected $_ufFieldId;
  protected $_contactId = 69;

  protected function setUp() {
    parent::setUp();
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
    $this->loadXMLDataSet(dirname(__FILE__) . '/dataset/uf_group_test.xml');
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
   * Find uf join group id.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testFindUFGroupId($version) {
    $this->_apiversion = $version;
    $params = [
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'uf_group_id' => $this->_ufGroupId,
      'is_active' => 1,
    ];
    $ufJoin = $this->callAPISuccess('uf_join', 'create', $params);

    $searchParams = [
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
    ];
    $result = $this->callAPISuccess('uf_join', 'get', $searchParams);

    foreach ($result['values'] as $key => $value) {
      $this->assertEquals($value['uf_group_id'], $this->_ufGroupId);
    }
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testUFJoinEditWithoutUFGroupId($version) {
    $this->_apiversion = $version;
    $params = [
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'is_active' => 1,
    ];
    $result = $this->callAPIFailure('uf_join', 'create', $params);
    $this->assertContains('Mandatory', $result['error_message']);
    $this->assertContains('missing', $result['error_message']);
    $this->assertContains('uf_group_id', $result['error_message']);
  }

  /**
   * Create/update uf join
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateUFJoin($version) {
    $this->_apiversion = $version;
    $params = [
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'uf_group_id' => $this->_ufGroupId,
      'is_active' => 1,
      'sequential' => 1,
    ];
    $ufJoin = $this->callAPIAndDocument('uf_join', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($ufJoin['values'][0]['module'], $params['module']);
    $this->assertEquals($ufJoin['values'][0]['uf_group_id'], $params['uf_group_id']);
    $this->assertEquals($ufJoin['values'][0]['is_active'], $params['is_active']);

    $params = [
      'id' => $ufJoin['id'],
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'uf_group_id' => $this->_ufGroupId,
      'is_active' => 0,
      'sequential' => 1,
    ];
    $ufJoinUpdated = $this->callAPISuccess('uf_join', 'create', $params);
    $this->assertEquals($ufJoinUpdated['values'][0]['module'], $params['module']);
    $this->assertEquals($ufJoinUpdated['values'][0]['uf_group_id'], $params['uf_group_id']);
    $this->assertEquals($ufJoinUpdated['values'][0]['is_active'], $params['is_active']);
  }

  /**
   * Ensure we can create a survey join which is less common than event or contribution
   * joins.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateSurveyUFJoin($version) {
    $this->_apiversion = $version;
    $params = [
      'module' => 'CiviCampaign',
      'entity_table' => 'civicrm_survey',
      'entity_id' => 1,
      'weight' => 1,
      'uf_group_id' => $this->_ufGroupId,
      'is_active' => 1,
      'sequential' => 1,
    ];
    $ufJoin = $this->callAPIAndDocument('uf_join', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($ufJoin['values'][0]['module'], $params['module']);
    $this->assertEquals($ufJoin['values'][0]['uf_group_id'], $params['uf_group_id']);
    $this->assertEquals($ufJoin['values'][0]['is_active'], $params['is_active']);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testFindUFJoinEmptyParams($version) {
    $this->_apiversion = $version;
    $result = $this->callAPIFailure('uf_join', 'create', []);
    $this->assertContains('Mandatory', $result['error_message']);
    $this->assertContains('missing', $result['error_message']);
    $this->assertContains('module', $result['error_message']);
    $this->assertContains('uf_group_id', $result['error_message']);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateUFJoinWithoutUFGroupId($version) {
    $this->_apiversion = $version;
    $params = [
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'is_active' => 1,
    ];
    $result = $this->callAPIFailure('uf_join', 'create', $params);
    $this->assertContains('Mandatory', $result['error_message']);
    $this->assertContains('missing', $result['error_message']);
    $this->assertContains('uf_group_id', $result['error_message']);
  }

  /**
   * Find uf join id.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetUFJoinId($version) {
    $this->_apiversion = $version;
    $params = [
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'weight' => 1,
      'uf_group_id' => $this->_ufGroupId,
      'is_active' => 1,
    ];

    $ufJoin = $this->callAPISuccess('uf_join', 'create', $params);
    $searchParams = [
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'sequential' => 1,
    ];

    $result = $this->callAPIAndDocument('uf_join', 'get', $searchParams, __FUNCTION__, __FILE__);
    $this->assertEquals($result['values'][0]['module'], $params['module']);
    $this->assertEquals($result['values'][0]['uf_group_id'], $params['uf_group_id']);
    $this->assertEquals($result['values'][0]['entity_id'], $params['entity_id']);
  }

  /**
   * Test civicrm_activity_create() using example code.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testUFJoinCreateExample($version) {
    $this->_apiversion = $version;
    require_once 'api/v3/examples/UFJoin/Create.ex.php';
    $result = UF_join_create_example();
    $expectedResult = UF_join_create_expectedresult();
    $this->assertEquals($result, $expectedResult);
  }

}
