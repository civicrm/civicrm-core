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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

class CRM_Campaign_BAO_CampaignTest extends CiviUnitTestCase {

  public function testCampaignSummary() {
    $loggedInContact = $this->createLoggedInUser();
    $contact = $this->individualCreate();
    $this->callAPISuccess('Campaign', 'create', [
      'title' => 'CiviCRM Unit Test Campaign',
      'campaign_type_id' => 'Direct Mail',
      'status_id' => 'In Progress',
    ]);
    try {
      CRM_Campaign_BAO_Campaign::getCampaignSummary(['status_id' => '0))+and+0+--+-f']);
      $this->fail('Campaign Summary should have validated the status_id');
    }
    catch (Exception $e) {
      if ($e->getMessage() === 'DB Error: syntax error') {
        $this->fail('Campaign Summary should have validated the status_id');
      }
    }
    $this->assertEquals(1, CRM_Campaign_BAO_Campaign::getCampaignSummary(['status_id' => 2], TRUE));
    $this->assertEquals(1, CRM_Campaign_BAO_Campaign::getCampaignSummary(['status_id' => [2, 3]], TRUE));
  }

} 
