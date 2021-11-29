<?php
namespace Civi\Search;

use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class AdminTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  public function setUpHeadless() {
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
  }

}
