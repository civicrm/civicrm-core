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
 * Test class for CRM_Event_Page_ManageEvent.
 * @group headless
 */
class CRM_Event_Page_ManageEventTest extends CiviUnitTestCase {

  /**
   * Only campaign ids from the search form reach the where clause.
   *
   * @dataProvider getCampaignFilters
   */
  public function testCampaignFilter($campaignFilter, ?string $expectedClause): void {
    $page = new CRM_Event_Page_ManageEvent();
    $page->set('campaign_id', $campaignFilter);
    $params = [];
    $where = $page->whereClause($params, FALSE, FALSE);

    if ($expectedClause) {
      $this->assertStringContainsString($expectedClause, $where);
    }
    else {
      $this->assertStringNotContainsString('campaign_id', $where);
    }
  }

  public static function getCampaignFilters(): array {
    return [
      'campaign ids' => [['3', '5'], '( campaign_id IN ( 3 , 5 ) )'],
      'single campaign id' => ['3', '( campaign_id IN ( 3 ) )'],
      'sql in the list' => [['3', '0) OR (1=1'], '( campaign_id IN ( 3 ) )'],
      'no campaign id at all' => ['0) OR (1=1', NULL],
    ];
  }

}
