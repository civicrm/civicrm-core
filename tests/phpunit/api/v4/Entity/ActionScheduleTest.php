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

  protected ?array $enableComponents = ['*'];

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

    $this->assertSame('manual', $fields['recipient']['options'][0]['name']);
    $this->assertSame('group', $fields['recipient']['options'][1]['name']);

    $this->assertSame(1, $fields['limit_to']['options'][0]['id']);
    $this->assertSame('limit', $fields['limit_to']['options'][0]['name']);
    $this->assertSame(2, $fields['limit_to']['options'][1]['id']);
    $this->assertSame('add', $fields['limit_to']['options'][1]['name']);

    $this->assertEquals(['name', 'label', 'icon'], $fields['mapping_id']['suffixes']);
  }

  public function testGetFieldsForActivity(): void {
    $fields = ActionSchedule::getFields(FALSE)
      ->setLoadOptions(TRUE)
      ->addValue('mapping_id:name', 'activity_type')
      ->execute()
      ->indexBy('name');

    $this->assertSame('Activity Type', $fields['entity_value']['label']);
    $this->assertContains('Meeting', $fields['entity_value']['options']);
    $this->assertSame('Activity Status', $fields['entity_status']['label']);
    $this->assertContains('Scheduled', $fields['entity_status']['options']);
    $this->assertArrayHasKey('activity_date_time', $fields['start_action_date']['options']);
    $this->assertArrayHasKey('1', $fields['limit_to']['options']);
    $this->assertArrayNotHasKey('2', $fields['limit_to']['options']);

    $this->assertSame(0, $fields['start_action_offset']['input_attrs']['min']);
  }

  /**
   * Tests that pseudoconstant caching correctly differentiates records with different
   * control_field chains (e.g. entity_status depends on entity_value which depends on mapping_id).
   */
  public function testPseudoconstantsWithChainedControlFields(): void {
    $activitySchedule = $this->createTestRecord('ActionSchedule', [
      'title' => 'Test Activity Reminder',
      'mapping_id' => 1,
      'entity_value' => [3],
      'entity_status' => [1],
      'absolute_date' => '2026-12-01',
      'is_active' => FALSE,
    ]);

    $participantSchedule = $this->createTestRecord('ActionSchedule', [
      'title' => 'Test Participant Reminder',
      'mapping_id' => 2,
      'entity_value' => [3],
      'entity_status' => [1],
      'absolute_date' => '2026-12-01',
      'is_active' => FALSE,
    ]);

    $ids = [$activitySchedule['id'], $participantSchedule['id']];

    // Case 1: mapping_id and entity_value are both in the select clause
    $resultsWithMapping = ActionSchedule::get(FALSE)
      ->addWhere('id', 'IN', $ids)
      ->addOrderBy('id', 'ASC')
      ->addSelect('id', 'mapping_id', 'entity_value', 'entity_status:label')
      ->execute()
      ->indexBy('id');

    $this->assertSame(['Scheduled'], $resultsWithMapping[$activitySchedule['id']]['entity_status:label']);
    $this->assertSame(['Registered'], $resultsWithMapping[$participantSchedule['id']]['entity_status:label']);

    // Case 2: mapping_id is omitted from the select clause (relies on DAO lookup)
    $resultsWithoutMapping = ActionSchedule::get(FALSE)
      ->addWhere('id', 'IN', $ids)
      ->addOrderBy('id', 'ASC')
      ->addSelect('id', 'entity_value', 'entity_status:label')
      ->execute()
      ->indexBy('id');

    $this->assertSame(['Scheduled'], $resultsWithoutMapping[$activitySchedule['id']]['entity_status:label']);
    $this->assertSame(['Registered'], $resultsWithoutMapping[$participantSchedule['id']]['entity_status:label']);
  }

}
