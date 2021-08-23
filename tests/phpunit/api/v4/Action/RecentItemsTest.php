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

  /**
   * This locks in a fix to ensure that if a user doesn't have permission to view the is_deleted field that doesn't hard fail if that field happens to be in an APIv4 call.
   */
  public function testIsDeletedPermission(): void {
    $cid = $this->createLoggedInUser();

    $aid = Activity::create(FALSE)
      ->addValue('activity_type_id:name', 'Meeting')
      ->addValue('source_contact_id', $cid)
      ->addValue('subject', 'Hello recent!')
      ->execute()->first()['id'];

    $this->assertEquals(1, $this->getRecentItemCount(['type' => 'Activity', 'id' => $aid]));

    Activity::delete(FALSE)->addWhere('id', '=', $aid)->execute();

    $this->assertEquals(0, $this->getRecentItemCount(['type' => 'Activity', 'id' => $aid]));
  }

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
