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
    $this->assertStringContainsString('Hello recent!', \CRM_Utils_Recent::get()[0]['title']);

    $aid2 = Activity::create(FALSE)
      ->addValue('activity_type_id:name', 'Meeting')
      ->addValue('source_contact_id', $cid)
      ->addValue('subject', 'Goodbye recent!')
      ->execute()->first()['id'];
    $this->assertEquals(1, $this->getRecentItemCount(['type' => 'Activity', 'id' => $aid2]));
    $this->assertStringContainsString('Goodbye recent!', \CRM_Utils_Recent::get()[0]['title']);

    Activity::delete(FALSE)->addWhere('id', '=', $aid1)->execute();

    $this->assertEquals(0, $this->getRecentItemCount(['type' => 'Activity', 'id' => $aid1]));
    $this->assertEquals(1, $this->getRecentItemCount(['type' => 'Activity', 'id' => $aid2]));
  }

  /**
   * @param array $props
   * @return int
   */
  private function getRecentItemCount($props) {
    $count = 0;
    foreach (\CRM_Utils_Recent::get() as $item) {
      foreach ($props as $key => $val) {
        if (($item[$key] ?? NULL) != $val) {
          continue 2;
        }
      }
      ++$count;
    }
    return $count;
  }

}
