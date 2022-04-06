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


namespace api\v4\Action;

use api\v4\UnitTestCase;
use Civi\Api4\Activity;
use Civi\Api4\RecentItem;

/**
 * @group headless
 */
class RecentItemsTest extends UnitTestCase {

  public function testAddDeleteActivity(): void {
    $cid = $this->createLoggedInUser();

    $aid1 = Activity::create(FALSE)
      ->addValue('activity_type_id:name', 'Meeting')
      ->addValue('source_contact_id', $cid)
      ->addValue('subject', 'Hello recent!')
      ->execute()->first()['id'];
    $this->assertEquals(1, $this->getRecentItemCount(['type' => 'Activity', 'id' => $aid1]));
    $recentItem = RecentItem::get(FALSE)->execute()->first();
    $this->assertStringContainsString('Hello recent!', $recentItem['title']);
    $this->assertStringContainsString("id=$aid1", $recentItem['view_url']);
    $this->assertEquals('fa-slideshare', $recentItem['icon']);

    $aid2 = Activity::create(FALSE)
      ->addValue('activity_type_id:name', 'Meeting')
      ->addValue('source_contact_id', $cid)
      ->addValue('subject', 'Goodbye recent!')
      ->execute()->first()['id'];
    $this->assertEquals(1, $this->getRecentItemCount(['type' => 'Activity', 'entity_id' => $aid2]));
    $this->assertStringContainsString('Goodbye recent!', RecentItem::get(FALSE)->execute()[0]['title']);

    Activity::delete(FALSE)->addWhere('id', '=', $aid1)->execute();

    $this->assertEquals(0, $this->getRecentItemCount(['entity_type' => 'Activity', 'entity_id' => $aid1]));
    $this->assertEquals(1, $this->getRecentItemCount(['entity_type' => 'Activity', 'entity_id' => $aid2]));
  }

  /**
   * @param array $props
   * @return int
   */
  private function getRecentItemCount($props) {
    $recent = RecentItem::get(FALSE);
    foreach ($props as $key => $val) {
      $recent->addWhere($key, '=', $val);
    }
    return $recent->execute()->count();
  }

}
