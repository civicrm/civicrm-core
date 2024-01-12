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

namespace api\v4\Entity;

use api\v4\Api4TestBase;
use Civi\Api4\PCP;

/**
 * @group headless
 */
class PCPTest extends Api4TestBase {

  public function testPCPGetWithDynamicJoins(): void {
    $events = $this->saveTestRecords('Event', [
      'records' => [
        ['title' => 'Abc'],
        ['title' => 'Def'],
      ],
    ]);
    $contributionPages = $this->saveTestRecords('ContributionPage', [
      'records' => [
        ['title' => 'Ghi'],
        ['title' => 'Jkl'],
      ],
    ]);
    $pcps = (array) $this->saveTestRecords('PCP', [
      'records' => [
        ['title' => 'A', 'page_type' => 'contribute', 'page_id' => $contributionPages[0]['id']],
        ['title' => 'B', 'page_type:name' => 'ContributionPage', 'page_id' => $contributionPages[1]['id']],
        ['title' => 'C', 'page_type' => 'event', 'page_id' => $events[0]['id']],
        ['title' => 'D', 'page_type:name' => 'Event', 'page_id' => $events[1]['id']],
      ],
    ]);

    $results = PCP::get(TRUE)
      ->addSelect('title', 'contribution_page.title', 'event.title')
      ->addJoin('ContributionPage AS contribution_page', 'LEFT', ['page_type', '=', "'contribute'"], ['page_id', '=', 'contribution_page.id'])
      ->addJoin('Event AS event', 'LEFT', ['page_type:name', '=', "'Event'"], ['page_id', '=', 'event.id'])
      ->addWhere('id', 'IN', array_column($pcps, 'id'))
      ->execute()->indexBy('title');

    $this->assertCount(4, $results);
    $this->assertEquals('Ghi', $results['A']['contribution_page.title']);
    $this->assertEquals('Jkl', $results['B']['contribution_page.title']);
    $this->assertEquals('Abc', $results['C']['event.title']);
    $this->assertEquals('Def', $results['D']['event.title']);
    $this->assertNull($results['C']['contribution_page.title']);
    $this->assertNull($results['D']['contribution_page.title']);
    $this->assertNull($results['A']['event.title']);
    $this->assertNull($results['B']['event.title']);
  }

}
