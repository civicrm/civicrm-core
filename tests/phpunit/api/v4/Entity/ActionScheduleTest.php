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
use Civi\Api4\ActionSchedule;

/**
 * Test ActionSchedule functionality
 *
 * @group headless
 */
class ActionScheduleTest extends Api4TestBase {

  public function testGetOptionsBasic() {
    $fields = ActionSchedule::getFields(FALSE)
      ->setLoadOptions(TRUE)
      ->execute()
      ->indexBy('name');

    $this->assertArrayHasKey('1', $fields['mapping_id']['options']);
    $this->assertArrayHasKey('contribpage', $fields['mapping_id']['options']);

    $this->assertArrayHasKey('day', $fields['start_action_unit']['options']);
    $this->assertArrayHasKey('week', $fields['repetition_frequency_unit']['options']);
    $this->assertArrayHasKey('month', $fields['end_frequency_unit']['options']);

    $this->assertArrayHasKey('manual', $fields['recipient']['options']);
    $this->assertArrayHasKey('group', $fields['recipient']['options']);

    $this->assertArrayHasKey('1', $fields['limit_to']['options']);
    $this->assertArrayHasKey('2', $fields['limit_to']['options']);
  }

}
