<?php

namespace Civi\Schema;

class EntityTest extends \CiviUnitTestCase {

  public function testGetMeta(): void {
    $entity = \Civi::entity('Activity');

    $this->assertEquals('civicrm_activity', $entity->getMeta('table'));
    $this->assertEquals('CRM_Activity_DAO_Activity', $entity->getMeta('class'));
    $this->assertEquals('Activity', $entity->getMeta('title'));
    $this->assertEquals('Activities', $entity->getMeta('title_plural'));
    $this->assertEquals('Past or future actions concerning one or more contacts.', $entity->getMeta('description'));
    $this->assertEquals('fa-tasks', $entity->getMeta('icon'));
    $this->assertEquals('subject', $entity->getMeta('label_field'));
    $this->assertEquals(['id'], $entity->getMeta('primary_keys'));
    $this->assertNotEmpty($entity->getMeta('paths'));
    foreach ($entity->getMeta('paths') as $path) {
      $this->assertStringStartsWith('civicrm/', $path);
    }
  }

  public function testGetFields(): void {
    $entity = \Civi::entity('Activity');

    $fields = $entity->getFields();
    $this->assertTrue($fields['id']['primary_key']);
    $this->assertTrue($fields['id']['auto_increment']);
    $this->assertTrue($fields['id']['required']);
    $this->assertTrue(empty($fields['created_date']['required']));
    $this->assertEquals('Relationship', $fields['relationship_id']['entity_reference']['entity']);
    $this->assertEquals('engagement_index', $fields['engagement_level']['pseudoconstant']['option_group_name']);
    $this->assertTrue(empty($fields['weight']['usage']));
    $this->assertEquals('datetime', $fields['activity_date_time']['sql_type']);
    $this->assertEquals('timestamp', $fields['modified_date']['sql_type']);
    $this->assertEquals('CiviCampaign', $fields['campaign_id']['component']);
    $this->assertEquals('EntityRef', $fields['campaign_id']['input_type']);
    $this->assertEquals('activity_modified_date', $fields['modified_date']['unique_name']);
    $this->assertEquals('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', $fields['modified_date']['default']);
    $this->assertTrue($fields['modified_date']['readonly']);
    $this->assertFalse($fields['is_deleted']['default']);
    $this->assertTrue(empty($fields['is_deleted']['localizable']));
  }

}
