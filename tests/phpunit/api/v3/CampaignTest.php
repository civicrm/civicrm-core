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
 * Class api_v3_CampaignTest
 * @group headless
 */
class api_v3_CampaignTest extends CiviUnitTestCase {
  protected $params;
  protected $id;

  public function setUp(): void {
    $this->params = [
      'title' => "campaign title",
      'description' => "Call people, ask for money",
      'created_date' => 'first sat of July 2008',
    ];
    parent::setUp();
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');
    $this->useTransaction(TRUE);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateCampaign($version) {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess('campaign', 'create', $this->params);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->getAndCheck(array_merge($this->params, ['created_date' => '2008-07-05 00:00:00']), $result['id'], 'campaign', TRUE);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetCampaign($version) {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess('campaign', 'create', $this->params);
    $result = $this->callAPISuccess('campaign', 'get', $this->params);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testDeleteCampaign($version) {
    $this->_apiversion = $version;
    $this->callAPISuccess('campaign', 'create', $this->params);
    $entity = $this->callAPISuccess('campaign', 'get', ($this->params));
    $delete = ['id' => $entity['id']];
    $result = $this->callAPISuccess('campaign', 'delete', $delete);

    $checkDeleted = $this->callAPISuccess('campaign', 'get', []);
    $this->assertEquals(0, $checkDeleted['count']);
  }

}
