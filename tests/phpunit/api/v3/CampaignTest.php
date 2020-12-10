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

  public $DBResetRequired = FALSE;

  public function setUp() {
    $this->params = [
      'title' => "campaign title",
      'description' => "Call people, ask for money",
      'created_date' => 'first sat of July 2008',
    ];
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateCampaign($version) {
    $this->_apiversion = $version;
    $description = "Create a campaign - Note use of relative dates here:
      @link http://www.php.net/manual/en/datetime.formats.relative.php.";
    $result = $this->callAPIAndDocument('campaign', 'create', $this->params, __FUNCTION__, __FILE__, $description);
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
    $result = $this->callAPIAndDocument('campaign', 'get', $this->params, __FUNCTION__, __FILE__);
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
    $result = $this->callAPIAndDocument('campaign', 'delete', $delete, __FUNCTION__, __FILE__);

    $checkDeleted = $this->callAPISuccess('campaign', 'get', []);
    $this->assertEquals(0, $checkDeleted['count']);
  }

}
