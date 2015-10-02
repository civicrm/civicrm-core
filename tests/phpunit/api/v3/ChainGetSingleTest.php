<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * This class is intended to test variable leakage in chained getsingle requests
 *
 * @package CiviCRM_APIv3
 */
class api_v3_ChainGetSingleTest extends CiviUnitTestCase {
  public $DBResetRequired = FALSE;

  /**
   * (non-PHPdoc)
   * @see CiviUnitTestCase::tearDown()
   */
  public function tearDown() {
    $tablesToTruncate = array(
      'civicrm_campaign',
      'civicrm_event'
    );
    $this->quickCleanup($tablesToTruncate, TRUE);
  }

  public function testCampaignEvent() {
    // campaigns are automatically created for each event
    $event0 = CRM_Core_DAO::createTestObject('CRM_Event_DAO_Event');
    $event1 = CRM_Core_DAO::createTestObject('CRM_Event_DAO_Event');
    $event2 = CRM_Core_DAO::createTestObject('CRM_Event_DAO_Event');

    // remove campaign association from second event
    $event1->campaign_id = 'null';
    $event1->save();

    $api = $this->callAPIAndDocument('event', 'get', array(
      'api.campaign.getsingle' => array(),
      'sequential' => 1,
    ), __FUNCTION__, __FILE__);

    $this->assertEquals($event0->id, $api['values'][0]['api.campaign.getsingle']['id']);
    $this->assertEquals($event2->id, $api['values'][2]['api.campaign.getsingle']['id']);

    // CRM-17327: When chaining a getsingle call, if the getsingle for an
    // entity fails, the API erroneously appends the previous entity's
    // api.*.getsingle property to the current entity. In other words, without
    // the patch for CRM-17327, the following test erroneously passes:
    // $this->assertEquals($event0->id, $api['values'][1]['api.campaign.getsingle']['id']);
    $this->assertArrayNotHasKey('id', $api['values'][1]['api.campaign.getsingle']);
  }

}
