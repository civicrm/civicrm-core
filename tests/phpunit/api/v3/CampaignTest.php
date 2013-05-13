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

