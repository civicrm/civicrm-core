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

  public function testGetOptionsBasic(): void {
    $fields = ActionSchedule::getFields(FALSE)
      ->setLoadOptions(['id', 'name', 'label', 'icon'])
      ->execute()
      ->indexBy('name');

    $this->assertContainsEquals(['id' => '1', 'name' => 'activity_type', 'label' => 'Activity', 'icon' => 'fa-tasks'], $fields['mapping_id']['options']);
    $this->assertContainsEquals(['id' => 'contribpage', 'name' => 'contribpage', 'label' => 'Contribution Page', 'icon' => 'fa-credit-card'], $fields['mapping_id']['options']);

    $this->assertContainsEquals(['id' => 'day', 'name' => 'day', 'label' => 'days'], $fields['start_action_unit']['options']);
    $this->assertContainsEquals(['id' => 'week', 'name' => 'week', 'label' => 'weeks'], $fields['repetition_frequency_unit']['options']);
    $this->assertContainsEquals(['id' => 'month', 'name' => 'month', 'label' => 'months'], $fields['end_frequency_unit']['options']);

    $this->assertEquals('manual', $fields['recipient']['options'][0]['name']);
    $this->assertEquals('group', $fields['recipient']['options'][1]['name']);

    $this->assertEquals('1', $fields['limit_to']['options'][0]['id']);
    $this->assertEquals('limit', $fields['limit_to']['options'][0]['name']);
    $this->assertEquals('2', $fields['limit_to']['options'][1]['id']);
    $this->assertEquals('add', $fields['limit_to']['options'][1]['name']);

    $this->assertEquals(['name', 'label', 'icon'], $fields['mapping_id']['suffixes']);
  }

  public function testGetFieldsForActivity(): void {
    $fields = ActionSchedule::getFields(FALSE)
      ->setLoadOptions(TRUE)
      ->addValue('mapping_id:name', 'activity_type')
      ->execute()
      ->indexBy('name');

    $this->assertEquals('Activity Type', $fields['entity_value']['label']);
    $this->assertContains('Meeting', $fields['entity_value']['options']);
    $this->assertEquals('Activity Status', $fields['entity_status']['label']);
    $this->assertContains('Scheduled', $fields['entity_status']['options']);
    $this->assertArrayHasKey('activity_date_time', $fields['start_action_date']['options']);
    $this->assertArrayHasKey('1', $fields['limit_to']['options']);
    $this->assertArrayNotHasKey('2', $fields['limit_to']['options']);
  }

}
