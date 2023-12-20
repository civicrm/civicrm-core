<?php
namespace Civi\Search;

use api\v4\Api4TestBase;
use Civi\Test\CiviEnvBuilder;

require_once __DIR__ . '/../../../../../../tests/phpunit/api/v4/Api4TestBase.php';

/**
 * @group headless
 */
class AdminTest extends Api4TestBase {

  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()->installMe(__DIR__)->apply();
  }

  /**
   */
  public function testGetJoins(): void {
    \CRM_Core_BAO_ConfigSetting::disableComponent('CiviCase');
    $allowedEntities = Admin::getSchema();
    $this->assertArrayNotHasKey('Case', $allowedEntities);
    $this->assertArrayNotHasKey('CaseContact', $allowedEntities);

    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
    $allowedEntities = Admin::getSchema();
    $this->assertArrayHasKey('Case', $allowedEntities);
    $this->assertArrayHasKey('CaseContact', $allowedEntities);

    $joins = Admin::getJoins($allowedEntities);
    $this->assertNotEmpty($joins);

    $groupContactJoins = \CRM_Utils_Array::findAll($joins['Group'], [
      'entity' => 'Contact',
      'bridge' => 'GroupContact',
      'alias' => 'Group_GroupContact_Contact',
      'multi' => TRUE,
    ]);
    $this->assertCount(1, $groupContactJoins);
    $this->assertEquals(
      ['GroupContact', ['id', '=', 'Group_GroupContact_Contact.group_id']],
      $groupContactJoins[0]['conditions']
    );
    $this->assertEquals(
      [['Group_GroupContact_Contact.status:name', '=', '"Added"']],
      $groupContactJoins[0]['defaults']
    );

    $relationshipJoins = \CRM_Utils_Array::findAll($joins['Contact'], [
      'entity' => 'Contact',
      'bridge' => 'RelationshipCache',
      'alias' => 'Contact_RelationshipCache_Contact',
      'multi' => TRUE,
    ]);
    $this->assertCount(1, $relationshipJoins);
    $this->assertEquals(
      ['RelationshipCache', ['id', '=', 'Contact_RelationshipCache_Contact.far_contact_id']],
      $relationshipJoins[0]['conditions']
    );
    $this->assertEquals(
      [['Contact_RelationshipCache_Contact.near_relation:name', '=', '"Child of"']],
      $relationshipJoins[0]['defaults']
    );

    $relationshipCacheJoins = $joins['RelationshipCache'];
    $this->assertCount(4, $relationshipCacheJoins);
    $this->assertEquals(['RelationshipType', 'Contact', 'Contact', 'Case'], array_column($relationshipCacheJoins, 'entity'));

    $eventParticipantJoins = \CRM_Utils_Array::findAll($joins['Event'], [
      'entity' => 'Participant',
      'alias' => 'Event_Participant_event_id',
      'multi' => TRUE,
    ]);
    $this->assertCount(1, $eventParticipantJoins);
    $this->assertNull($eventParticipantJoins[0]['bridge'] ?? NULL);
    $this->assertEquals(
      [['id', '=', 'Event_Participant_event_id.event_id']],
      $eventParticipantJoins[0]['conditions']
    );

    $tagActivityJoins = \CRM_Utils_Array::findAll($joins['Tag'], [
      'entity' => 'Activity',
      'bridge' => 'EntityTag',
      'alias' => 'Tag_EntityTag_Activity',
      'multi' => TRUE,
    ]);
    $this->assertCount(1, $tagActivityJoins);
    $this->assertEquals(
      ['EntityTag', ['id', '=', 'Tag_EntityTag_Activity.tag_id']],
      $tagActivityJoins[0]['conditions']
    );

    $activityTagJoins = \CRM_Utils_Array::findAll($joins['Activity'], [
      'entity' => 'Tag',
      'bridge' => 'EntityTag',
      'alias' => 'Activity_EntityTag_Tag',
      'multi' => TRUE,
    ]);
    $this->assertCount(1, $activityTagJoins);
    $this->assertEquals(
      ['EntityTag', ['id', '=', 'Activity_EntityTag_Tag.entity_id'], ['Activity_EntityTag_Tag.entity_table', '=', "'civicrm_activity'"]],
      $activityTagJoins[0]['conditions']
    );

    // Ensure joins exist btw custom group & custom fields
    $customGroupToField = \CRM_Utils_Array::findAll($joins['CustomGroup'], [
      'entity' => 'CustomField',
      'multi' => TRUE,
    ]);
    $this->assertCount(1, $customGroupToField);
    $customFieldToGroup = \CRM_Utils_Array::findAll($joins['CustomField'], [
      'entity' => 'CustomGroup',
      'multi' => FALSE,
    ]);
    $this->assertCount(1, $customFieldToGroup);

    // Ensure joins btw option group and option value
    $optionGroupToValue = \CRM_Utils_Array::findAll($joins['OptionGroup'], [
      'entity' => 'OptionValue',
      'multi' => TRUE,
    ]);
    $this->assertCount(1, $optionGroupToValue);
    $optionValueToGroup = \CRM_Utils_Array::findAll($joins['OptionValue'], [
      'entity' => 'OptionGroup',
      'multi' => FALSE,
    ]);
    $this->assertCount(1, $optionValueToGroup);
  }

  public function testEntityRefGetJoins(): void {
    $this->createTestRecord('CustomGroup', [
      'title' => 'EntityRefFields',
      'extends' => 'Individual',
    ]);
    $this->createTestRecord('CustomField', [
      'label' => 'Favorite Nephew',
      'name' => 'favorite_nephew',
      'custom_group_id.name' => 'EntityRefFields',
      'html_type' => 'Autocomplete-Select',
      'data_type' => 'EntityReference',
      'fk_entity' => 'Contact',
    ]);
    $allowedEntities = Admin::getSchema();
    $joins = Admin::getJoins($allowedEntities);

    $entityRefJoin = \CRM_Utils_Array::findAll($joins['Contact'], ['alias' => 'Contact_Contact_favorite_nephew']);
    $this->assertCount(1, $entityRefJoin);
    $this->assertEquals([['EntityRefFields.favorite_nephew', '=', 'Contact_Contact_favorite_nephew.id']], $entityRefJoin[0]['conditions']);
    $this->assertStringContainsString('Favorite Nephew', $entityRefJoin[0]['label']);
  }

  public function testMultiRecordCustomGetJoins(): void {
    $this->createTestRecord('CustomGroup', [
      'title' => 'Multiple Things',
      'name' => 'MultiRecordActivity',
      'extends' => 'Activity',
      'is_multiple' => TRUE,
    ]);
    $this->createTestRecord('CustomField', [
      'label' => 'Ref Group',
      'name' => 'ref_group',
      'custom_group_id.name' => 'MultiRecordActivity',
      'html_type' => 'Autocomplete-Select',
      'data_type' => 'EntityReference',
      'fk_entity' => 'Group',
    ]);
    $allowedEntities = Admin::getSchema();
    $joins = Admin::getJoins($allowedEntities);

    $entityRefJoin = \CRM_Utils_Array::findAll($joins['Custom_MultiRecordActivity'], ['alias' => 'Custom_MultiRecordActivity_Group_ref_group']);
    $this->assertCount(1, $entityRefJoin);
    $this->assertEquals([['ref_group', '=', 'Custom_MultiRecordActivity_Group_ref_group.id']], $entityRefJoin[0]['conditions']);

    $reverseJoin = \CRM_Utils_Array::findAll($joins['Group'], ['alias' => 'Group_Custom_MultiRecordActivity_ref_group']);
    $this->assertCount(1, $reverseJoin);
    $this->assertEquals([['id', '=', 'Group_Custom_MultiRecordActivity_ref_group.ref_group']], $reverseJoin[0]['conditions']);

    $activityToCustomJoin = \CRM_Utils_Array::findAll($joins['Activity'], ['alias' => 'Activity_Custom_MultiRecordActivity_entity_id']);
    $this->assertCount(1, $activityToCustomJoin);
    $this->assertEquals([['id', '=', 'Activity_Custom_MultiRecordActivity_entity_id.entity_id']], $activityToCustomJoin[0]['conditions']);
    $this->assertEquals('Multiple Things', $activityToCustomJoin[0]['label']);

    $customToActivityJoin = \CRM_Utils_Array::findAll($joins['Custom_MultiRecordActivity'], ['alias' => 'Custom_MultiRecordActivity_Activity_entity_id']);
    $this->assertCount(1, $customToActivityJoin);
    $this->assertEquals([['entity_id', '=', 'Custom_MultiRecordActivity_Activity_entity_id.id']], $customToActivityJoin[0]['conditions']);
    $this->assertEquals('Multiple Things Activity', $customToActivityJoin[0]['label']);
  }

}
