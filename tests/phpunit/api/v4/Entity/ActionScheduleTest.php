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
      ->setLoadOptions(['id', 'name', 'label'])
      ->execute()
      ->indexBy('name');

    $this->assertContains(['id' => '1', 'name' => 'activity_type', 'label' => 'Activity'], $fields['mapping_id']['options']);
    $this->assertContains(['id' => 'contribpage', 'name' => 'contribpage', 'label' => 'Contribution Page'], $fields['mapping_id']['options']);

    $this->assertContains(['id' => 'day', 'name' => 'day', 'label' => 'days'], $fields['start_action_unit']['options']);
    $this->assertContains(['id' => 'week', 'name' => 'week', 'label' => 'weeks'], $fields['repetition_frequency_unit']['options']);
    $this->assertContains(['id' => 'month', 'name' => 'month', 'label' => 'months'], $fields['end_frequency_unit']['options']);

    $this->assertEquals('manual', $fields['recipient']['options'][0]['name']);
    $this->assertEquals('group', $fields['recipient']['options'][1]['name']);

    $this->assertEquals('1', $fields['limit_to']['options'][0]['id']);
    $this->assertEquals('limit', $fields['limit_to']['options'][0]['name']);
    $this->assertEquals('2', $fields['limit_to']['options'][1]['id']);
    $this->assertEquals('add', $fields['limit_to']['options'][1]['name']);
  }

}
