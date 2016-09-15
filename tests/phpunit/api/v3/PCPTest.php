<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * Test class for Batch API - civicrm_batch_*
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_PCPTest extends CiviUnitTestCase {

  protected $_params = array();
  protected $_entity = 'PCP';

  /**
   * Sets up the fixture, for example, opens a network connection.
   *
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  /**
   * Build params.
   */
  private function pcpBlockParams() {
    $contribPage = CRM_Core_DAO::createTestObject('CRM_Contribute_DAO_ContributionPage');
    $contribPageId = $contribPage->id;
    $supporterProfile = CRM_Core_DAO::createTestObject('CRM_Core_DAO_UFGroup');
    $supporterProfileId = $supporterProfile->id;

    $params = array(
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $contribPageId,
      'supporter_profile_id' => $supporterProfileId,
      'target_entity_id' => 1,
      'is_approval_needed' => 1,
      'is_tellfriend_enabled' => 1,
      'tellfriend_limit' => 1,
      'link_text' => 'Create your own PCP',
      'is_active' => 1,
    );

    return $params;
  }

  /**
   * Build params.
   */
  private function pcpParams() {
    $contact = CRM_Core_DAO::createTestObject('CRM_Contact_DAO_Contact');
    $contactId = $contact->id;
    $contribPage = CRM_Core_DAO::createTestObject('CRM_Contribute_DAO_ContributionPage');
    $contribPageId = $contribPage->id;

    $params = array(
      'contact_id' => $contactId,
      'status_id' => '1',
      'title' => 'My PCP',
      'intro_text' => 'Hey you, contribute now!',
      'page_text' => 'You better give more.',
      'donate_link_text' => 'Donate Now',
      'page_id' => $contribPageId,
      'is_thermometer' => 1,
      'is_honor_roll' => 1,
      'goal_amount' => 10000.00,
      'is_active' => 1,
    );

    return $params;
  }

  /**
   * Test civicrm_batch_get - success expected.
   */
  public function testGet() {
    $pcpBlockParams = self::pcpBlockParams();
    $pcpBlock = CRM_PCP_BAO_PCPBlock::create($pcpBlockParams);
    $params = self::pcpParams();
    $params['pcp_block_id'] = $pcpBlock->id;
    $pcp = $this->callAPISuccess('pcp', 'create', $params);
    $result = $this->callAPIAndDocument('pcp', 'get', array('id' => $pcp['id']), __FUNCTION__, __FILE__);
    $this->assertEquals($pcp['id'], $result['id']);
  }

  /**
   * Test civicrm_batch_delete using the new $params['id'] syntax.
   */
  public function testDelete() {
    $pcpBlockParams = self::pcpBlockParams();
    $pcpBlock = CRM_PCP_BAO_PCPBlock::create($pcpBlockParams);
    $params = self::pcpParams();
    $params['pcp_block_id'] = $pcpBlock->id;
    $pcp = $this->callAPISuccess('pcp', 'create', $params);
    $params = array(
      'id' => $pcp['id'],
    );
    $result = $this->callAPIAndDocument('pcp', 'delete', $params, __FUNCTION__, __FILE__);
  }

}
