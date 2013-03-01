<?php
// $Id$


require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_CampaignTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $params;
  protected $id;
  public $_eNoticeCompliant = TRUE;
  public $DBResetRequired = FALSE; function setUp() {
    $this->_apiversion = 3;
    $this->params = array(
      'version' => 3,
      'title' => "campaign title",
      'description' => "Call people, ask for money",
      'created_date' => 'first sat of July 2008',
    );
    parent::setUp();
  }

  function tearDown() {}

  public function testCreateCampaign() {
    $description = "Create a campaign - Note use of relative dates here http://www.php.net/manual/en/datetime.formats.relative.php";
    $result = civicrm_api('campaign', 'create', $this->params);
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__, $description);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
    $this->getAndCheck(array_merge($this->params, array('created_date' => '2008-07-05 00:00:00')), $result['id'], 'campaign', TRUE);
  }

  public function testGetCampaign() {
    $result = civicrm_api('campaign', 'create', $this->params);
    $result = civicrm_api('campaign', 'get', ($this->params));
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
    $this->id = $result['id'];
  }

  public function testDeleteCampaign() {
    $entity = civicrm_api('campaign', 'get', ($this->params));
    $delete = array('version' => 3, 'id' => $entity['id']);
    $result = civicrm_api('campaign', 'delete', $delete);
    $this->documentMe($delete, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);

    $checkDeleted = civicrm_api('campaign', 'get', array(
      'version' => 3,
      ));
    $this->assertEquals(0, $checkDeleted['count'], 'In line ' . __LINE__);
  }
}

