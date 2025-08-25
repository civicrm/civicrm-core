<?php

namespace Civi\Schema;

use Civi\Api4\Activity;
use Civi\Core\Event\GenericHookEvent;
use CRM_Activity_BAO_Activity;

class EntityTest extends \CiviUnitTestCase {

  public function testGetMeta(): void {
    $entity = \Civi::entity('Activity');

    $this->assertEquals('Activity', $entity->getMeta('name'));
    $this->assertEquals('civicrm_activity', $entity->getMeta('table'));
    $this->assertEquals('CRM_Activity_DAO_Activity', $entity->getMeta('class'));
    $this->assertEquals('Activity', $entity->getMeta('title'));
    $this->assertEquals('Activities', $entity->getMeta('title_plural'));
    $this->assertEquals('Past or future actions concerning one or more contacts.', $entity->getMeta('description'));
    $this->assertEquals('fa-tasks', $entity->getMeta('icon'));
    $this->assertEquals('subject', $entity->getMeta('label_field'));
    $this->assertSame(['id'], $entity->getMeta('primary_keys'));
    $this->assertSame('id', $entity->getMeta('primary_key'));
    $this->assertSame('1.1', $entity->getMeta('add'));
    $this->assertTrue($entity->getMeta('log'));
    $this->assertNotEmpty($entity->getMeta('paths'));
    foreach ($entity->getMeta('paths') as $path) {
      $this->assertStringStartsWith('civicrm/', $path);
    }
  }

  /**
   * Ensures getFields returns expected results and tests the `civi.entity.fields` listener.
   *
   * @return void
   */
  public function testGetFieldsWithHookAlterations(): void {
    $dispatcher = \Civi::service('dispatcher');
    $dispatcher->addListener('civi.entity.fields::Activity', [$this, 'onActivityFields']);

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

    // Test hook alterations
    $this->assertSame('Altered ID Title', $fields['id']['title']);
    // Ensure it also works with getField
    $this->assertSame('Altered ID Title', $entity->getField('id')['title']);
    // Test new field
    $this->assertSame('New Field', $fields['new_field']['title']);

    // Test with legacy DAO adapter
    $baoFields = CRM_Activity_BAO_Activity::fields();
    $this->assertSame('Altered ID Title', $baoFields['activity_id']['title']);
    $this->assertSame('New Field', $baoFields['new_field']['title']);

    // Test with Api4 getFields
    $apiFields = Activity::getFields(FALSE)
      ->execute()->indexBy('name');
    // Test hook alterations
    $this->assertSame('Altered ID Title', $apiFields['id']['title']);
    // Test new field
    $this->assertSame('New Field', $apiFields['new_field']['title']);
    $this->assertSame('New fake field', $apiFields['new_field']['description']);
  }

  public function onActivityFields(GenericHookEvent $event): void {
    $this->assertSame('Activity', $event->entity);
    // Alter existing field
    $event->fields['id']['title'] = 'Altered ID Title';

    // Insert new field
    $event->fields['new_field'] = [
      'title' => 'New Field',
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => 'New fake field',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ];
  }

}
