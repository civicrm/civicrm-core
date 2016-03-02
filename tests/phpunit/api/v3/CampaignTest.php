<?php
/*
 +--------------------------------------------------------------------+
| CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * Class api_v3_CampaignTest
 * @group headless
 */
class api_v3_CampaignTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $params;
  protected $id;

  public $DBResetRequired = FALSE;

  public function setUp() {
    $this->_apiversion = 3;
    $this->params = array(
      'title' => "campaign title",
      'description' => "Call people, ask for money",
      'created_date' => 'first sat of July 2008',
    );
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  public function testCreateCampaign() {
    $description = "Create a campaign - Note use of relative dates here:
      @link http://www.php.net/manual/en/datetime.formats.relative.php.";
    $result = $this->callAPIAndDocument('campaign', 'create', $this->params, __FUNCTION__, __FILE__, $description);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->getAndCheck(array_merge($this->params, array('created_date' => '2008-07-05 00:00:00')), $result['id'], 'campaign', TRUE);
  }

  public function testGetCampaign() {
    $result = $this->callAPISuccess('campaign', 'create', $this->params);
    $result = $this->callAPIAndDocument('campaign', 'get', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  public function testDeleteCampaign() {
    $this->callAPISuccess('campaign', 'create', $this->params);
    $entity = $this->callAPISuccess('campaign', 'get', ($this->params));
    $delete = array('id' => $entity['id']);
    $result = $this->callAPIAndDocument('campaign', 'delete', $delete, __FUNCTION__, __FILE__);

    $checkDeleted = $this->callAPISuccess('campaign', 'get', array());
    $this->assertEquals(0, $checkDeleted['count']);
  }

}
