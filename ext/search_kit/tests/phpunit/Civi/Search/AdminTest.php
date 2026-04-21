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

    $groupContactJoins = \CRM_Utils_Array::filter($joins['Group'], [
      'entity' => 'Contact',
      'bridge' => 'GroupContact',
      'alias' => 'Group_GroupContact_Contact',
      'multi' => TRUE,
    ]);
    $this->assertCount(1, $groupContactJoins);
    $groupContactJoin = reset($groupContactJoins);
    $this->assertEquals(
      ['GroupContact', ['id', '=', 'Group_GroupContact_Contact.group_id']],
      $groupContactJoin['conditions']
    );
    $this->assertEquals(
      [['Group_GroupContact_Contact.status:name', '=', '"Added"']],
      $groupContactJoin['defaults']
    );

    $relationshipJoins = \CRM_Utils_Array::filter($joins['Contact'], [
      'entity' => 'Contact',
      'bridge' => 'RelationshipCache',
      'alias' => 'Contact_RelationshipCache_Contact',
      'multi' => TRUE,
    ]);
    $this->assertCount(1, $relationshipJoins);
    $relationshipJoin = reset($relationshipJoins);
    $this->assertEquals(
      ['RelationshipCache', ['id', '=', 'Contact_RelationshipCache_Contact.far_contact_id']],
      $relationshipJoin['conditions']
    );
    $this->assertEquals(
      [['Contact_RelationshipCache_Contact.near_relation:name', '=', '"Child of"']],
      $relationshipJoin['defaults']
    );

    $relationshipCacheJoins = $joins['RelationshipCache'];
    $this->assertCount(4, $relationshipCacheJoins);
    $this->assertEquals(['RelationshipType', 'Contact', 'Contact', 'Case'], array_column($relationshipCacheJoins, 'entity'));

    $eventParticipantJoins = \CRM_Utils_Array::filter($joins['Event'], [
      'entity' => 'Participant',
      'alias' => 'Event_Participant_event_id',
      'multi' => TRUE,
    ]);
    $this->assertCount(1, $eventParticipantJoins);
    $eventParticipantJoin = reset($eventParticipantJoins);
    $this->assertNull($eventParticipantJoin['bridge'] ?? NULL);
    $this->assertEquals(
      [['id', '=', 'Event_Participant_event_id.event_id']],
      $eventParticipantJoin['conditions']
    );

    $tagActivityJoins = \CRM_Utils_Array::filter($joins['Tag'], [
      'entity' => 'Activity',
      'bridge' => 'EntityTag',
      'alias' => 'Tag_EntityTag_Activity',
      'multi' => TRUE,
    ]);
    $this->assertCount(1, $tagActivityJoins);
    $tagActivityJoin = reset($tagActivityJoins);
    $this->assertEquals(
      ['EntityTag', ['id', '=', 'Tag_EntityTag_Activity.tag_id']],
      $tagActivityJoin['conditions']
    );

    $activityTagJoins = \CRM_Utils_Array::filter($joins['Activity'], [
      'entity' => 'Tag',
      'bridge' => 'EntityTag',
      'alias' => 'Activity_EntityTag_Tag',
      'multi' => TRUE,
    ]);
    $this->assertCount(1, $activityTagJoins);
    $activityTagJoin = reset($activityTagJoins);
    $this->assertEquals(
      ['EntityTag', ['id', '=', 'Activity_EntityTag_Tag.entity_id'], ['Activity_EntityTag_Tag.entity_table', '=', "'civicrm_activity'"]],
      $activityTagJoin['conditions']
    );

    // Ensure joins exist btw custom group & custom fields
    $customGroupToField = \CRM_Utils_Array::filter($joins['CustomGroup'], [
      'entity' => 'CustomField',
      'multi' => TRUE,
    ]);
    $this->assertCount(1, $customGroupToField);
    $customFieldToGroup = \CRM_Utils_Array::filter($joins['CustomField'], [
      'entity' => 'CustomGroup',
      'multi' => FALSE,
    ]);
    $this->assertCount(1, $customFieldToGroup);

    // Ensure joins btw option group and option value
    $optionGroupToValue = \CRM_Utils_Array::filter($joins['OptionGroup'], [
      'entity' => 'OptionValue',
      'multi' => TRUE,
    ]);
    $this->assertCount(1, $optionGroupToValue);
    $optionValueToGroup = \CRM_Utils_Array::filter($joins['OptionValue'], [
      'entity' => 'OptionGroup',
      'multi' => FALSE,
    ]);
    $this->assertCount(1, $optionValueToGroup);

    // Location joins
    $addressJoins = \CRM_Utils_Array::filter($joins['Individual'], [
      'entity' => 'Address',
      'multi' => TRUE,
    ]);
    $this->assertCount(1, $addressJoins);
    $addressJoin = reset($addressJoins);
    $this->assertEquals(
      [['id', '=', 'Contact_Address_contact_id.contact_id']],
      $addressJoin['conditions']
    );
    $this->assertEquals(
      [['Contact_Address_contact_id.is_primary', '=', TRUE]],
      $addressJoin['defaults']
    );

    // LocBlock joins
    $locBlockAddress = array_values(\CRM_Utils_Array::filter($joins['LocBlock'], [
      'entity' => 'Address',
    ]));
    $this->assertCount(2, $locBlockAddress);
    $this->assertEquals(
      [['address_id', '=', 'LocBlock_Address_address_id.id']],
      $locBlockAddress[0]['conditions']
    );
    // Should have no defaults because it's a straight 1-1 join
    $this->assertEquals(
      [],
      $locBlockAddress[0]['defaults']
    );
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

    $entityRefJoins = \CRM_Utils_Array::filter($joins['Contact'], ['alias' => 'Contact_Contact_favorite_nephew']);
    $this->assertCount(1, $entityRefJoins);
    $entityRefJoin = reset($entityRefJoins);
    $this->assertEquals([['EntityRefFields.favorite_nephew', '=', 'Contact_Contact_favorite_nephew.id']], $entityRefJoin['conditions']);
    $this->assertStringContainsString('Favorite Nephew', $entityRefJoin['label']);
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

    $entityRefJoins = \CRM_Utils_Array::filter($joins['Custom_MultiRecordActivity'], ['alias' => 'Custom_MultiRecordActivity_Group_ref_group']);
    $this->assertCount(1, $entityRefJoins);
    $entityRefJoin = reset($entityRefJoins);
    $this->assertEquals([['ref_group', '=', 'Custom_MultiRecordActivity_Group_ref_group.id']], $entityRefJoin['conditions']);

    $reverseJoins = \CRM_Utils_Array::filter($joins['Group'], ['alias' => 'Group_Custom_MultiRecordActivity_ref_group']);
    $this->assertCount(1, $reverseJoins);
    $reverseJoin = reset($reverseJoins);
    $this->assertEquals([['id', '=', 'Group_Custom_MultiRecordActivity_ref_group.ref_group']], $reverseJoin['conditions']);

    $activityToCustomJoins = \CRM_Utils_Array::filter($joins['Activity'], ['alias' => 'Activity_Custom_MultiRecordActivity_entity_id']);
    $this->assertCount(1, $activityToCustomJoins);
    $activityToCustomJoin = reset($activityToCustomJoins);
    $this->assertEquals([['id', '=', 'Activity_Custom_MultiRecordActivity_entity_id.entity_id']], $activityToCustomJoin['conditions']);
    $this->assertEquals('Multiple Things', $activityToCustomJoin['label']);

    $customToActivityJoins = \CRM_Utils_Array::filter($joins['Custom_MultiRecordActivity'], ['alias' => 'Custom_MultiRecordActivity_Activity_entity_id']);
    $this->assertCount(1, $customToActivityJoins);
    $customToActivityJoin = reset($customToActivityJoins);
    $this->assertEquals([['entity_id', '=', 'Custom_MultiRecordActivity_Activity_entity_id.id']], $customToActivityJoin['conditions']);
    $this->assertEquals('Multiple Things Activity', $customToActivityJoin['label']);
  }

}
