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


namespace api\v4\Custom;

use Civi\Api4\Activity;
use Civi\Api4\Contact;
use Civi\Api4\ContactType;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\Event;
use Civi\Api4\Individual;
use Civi\Api4\Organization;
use Civi\Api4\Participant;

/**
 * @group headless
 */
class CustomFieldGetFieldsTest extends CustomTestBase {

  private $subTypeName = 'Sub_Tester';

  public function tearDown(): void {
    parent::tearDown();
    Contact::delete(FALSE)
      ->addWhere('id', '>', 0)
      ->execute();
    Participant::delete(FALSE)
      ->addWhere('id', '>', 0)
      ->execute();
    Event::delete(FALSE)
      ->addWhere('id', '>', 0)
      ->execute();
    ContactType::delete(FALSE)
      ->addWhere('name', '=', $this->subTypeName)
      ->execute();
  }

  public function testDisabledAndHiddenFields(): void {
    // Create a custom group with one enabled and one disabled field
    CustomGroup::create(FALSE)
      ->addValue('extends', 'Activity')
      ->addValue('title', 'act_test_grp')
      ->execute();
    $this->saveTestRecords('CustomField', [
      'records' => [
        ['label' => 'enabled_field'],
        ['label' => 'disabled_field', 'is_active' => FALSE],
        ['label' => 'hidden_field', 'html_type' => 'Hidden'],
      ],
      'defaults' => ['custom_group_id.name' => 'act_test_grp'],
    ]);

    // Only the enabled fields show up
    $getFields = Activity::getFields(FALSE)->execute()->indexBy('name');
    $this->assertArrayHasKey('act_test_grp.enabled_field', $getFields);
    $this->assertArrayHasKey('act_test_grp.hidden_field', $getFields);
    $this->assertArrayNotHasKey('act_test_grp.disabled_field', $getFields);

    // Hidden field does not have option lists
    $this->assertFalse($getFields['act_test_grp.hidden_field']['options']);
    $this->assertNull($getFields['act_test_grp.hidden_field']['suffixes']);

    // Disable the entire custom group
    CustomGroup::update(FALSE)
      ->addWhere('name', '=', 'act_test_grp')
      ->addValue('is_active', FALSE)
      ->execute();

    // Neither field shows up as the whole group is disabled
    $getFields = Activity::getFields(FALSE)->execute()->column('name');
    $this->assertNotContains('act_test_grp.enabled_field', $getFields);
    $this->assertNotContains('act_test_grp.disabled_field', $getFields);
  }

  public function testCustomGetFieldsWithContactSubType(): void {
    ContactType::create(FALSE)
      ->addValue('name', $this->subTypeName)
      ->addValue('label', $this->subTypeName)
      ->addValue('parent_id:name', 'Individual')
      ->execute();

    $contact1 = Individual::create(FALSE)
      ->execute()->first();
    $contact2 = Individual::create(FALSE)->addValue('contact_sub_type', [$this->subTypeName])
      ->execute()->first();
    $org = Organization::create(FALSE)
      ->execute()->first();

    // Individual sub-type custom group
    CustomGroup::create(FALSE)
      ->addValue('extends', 'Individual')
      ->addValue('extends_entity_column_value', [$this->subTypeName])
      ->addValue('title', 'contact_sub')
      ->execute();
    CustomField::create(FALSE)
      ->addValue('custom_group_id.name', 'contact_sub')
      ->addValue('label', 'sub_field')
      ->addValue('html_type', 'Text')
      ->execute();

    // Organization custom group
    CustomGroup::create(FALSE)
      ->addValue('extends', 'Organization')
      ->addValue('title', 'org_group')
      ->execute();
    CustomField::create(FALSE)
      ->addValue('custom_group_id.name', 'org_group')
      ->addValue('label', 'sub_field')
      ->addValue('html_type', 'Text')
      ->execute();

    // Unconditional Contact CustomGroup
    CustomGroup::create(FALSE)
      ->addValue('extends', 'Contact')
      ->addValue('title', 'always')
      ->addChain('field', CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('label', 'on')
        ->addValue('html_type', 'Text')
      )->execute();

    $allFields = Contact::getFields(FALSE)
      ->execute()->indexBy('name');
    $this->assertArrayHasKey('contact_sub.sub_field', $allFields);
    $this->assertArrayHasKey('org_group.sub_field', $allFields);
    $this->assertArrayHasKey('always.on', $allFields);

    $fieldsWithSubtype = Contact::getFields(FALSE)
      ->addValue('id', $contact2['id'])
      ->execute()->indexBy('name');
    $this->assertArrayHasKey('contact_sub.sub_field', $fieldsWithSubtype);
    $this->assertArrayNotHasKey('org_group.sub_field', $fieldsWithSubtype);
    $this->assertArrayHasKey('always.on', $fieldsWithSubtype);

    $fieldsWithSubtype = Individual::getFields(FALSE)
      ->addValue('id', $contact2['id'])
      ->execute()->indexBy('name');
    $this->assertArrayHasKey('contact_sub.sub_field', $fieldsWithSubtype);
    $this->assertArrayNotHasKey('org_group.sub_field', $fieldsWithSubtype);
    $this->assertArrayHasKey('always.on', $fieldsWithSubtype);

    $fieldsWithSubtype = Contact::getFields(FALSE)
      ->addValue('id', $contact2['id'])
      ->execute()->indexBy('name');
    $this->assertArrayHasKey('contact_sub.sub_field', $fieldsWithSubtype);
    $this->assertArrayNotHasKey('org_group.sub_field', $fieldsWithSubtype);
    $this->assertArrayHasKey('always.on', $fieldsWithSubtype);

    $fieldsNoSubtype = Contact::getFields(FALSE)
      ->addValue('id', $contact1['id'])
      ->execute()->indexBy('name');
    $this->assertArrayNotHasKey('contact_sub.sub_field', $fieldsNoSubtype);
    $this->assertArrayNotHasKey('org_group.sub_field', $fieldsNoSubtype);
    $this->assertArrayHasKey('always.on', $fieldsNoSubtype);

    $groupFields = Organization::getFields(FALSE)
      ->addValue('id', $org['id'])
      ->execute()->indexBy('name');
    $this->assertArrayNotHasKey('contact_sub.sub_field', $groupFields);
    $this->assertArrayHasKey('org_group.sub_field', $groupFields);
    $this->assertArrayHasKey('always.on', $groupFields);

    $groupFields = Contact::getFields(FALSE)
      ->addValue('id', $org['id'])
      ->execute()->indexBy('name');
    $this->assertArrayNotHasKey('contact_sub.sub_field', $groupFields);
    $this->assertArrayHasKey('org_group.sub_field', $groupFields);
    $this->assertArrayHasKey('always.on', $groupFields);
  }

  public function testCustomGetFieldsForParticipantSubTypes(): void {
    $event1 = Event::create(FALSE)
      ->addValue('title', 'Test1')
      ->addValue('event_type_id:name', 'Meeting')
      ->addValue('start_date', 'now')
      ->execute()->first();
    $event2 = Event::create(FALSE)
      ->addValue('title', 'Test2')
      ->addValue('event_type_id:name', 'Meeting')
      ->addValue('start_date', 'now')
      ->execute()->first();
    $event3 = Event::create(FALSE)
      ->addValue('title', 'Test3')
      ->addValue('event_type_id:name', 'Conference')
      ->addValue('start_date', 'now')
      ->execute()->first();
    $event4 = Event::create(FALSE)
      ->addValue('title', 'Test4')
      ->addValue('event_type_id:name', 'Fundraiser')
      ->addValue('start_date', 'now')
      ->execute()->first();

    $cid = Contact::create(FALSE)->execute()->single()['id'];

    $sampleData = [
      ['event_id' => $event1['id'], 'role_id:name' => ['Attendee']],
      ['event_id' => $event2['id'], 'role_id:name' => ['Attendee', 'Volunteer']],
      ['event_id' => $event3['id'], 'role_id:name' => ['Attendee']],
      ['event_id' => $event4['id'], 'role_id:name' => ['Host']],
    ];
    $participants = Participant::save(FALSE)
      ->addDefault('contact_id', $cid)
      ->addDefault('status_id:name', 'Registered')
      ->setRecords($sampleData)
      ->execute();

    // CustomGroup based on Event Type = Meeting|Conference
    CustomGroup::create(FALSE)
      ->addValue('extends', 'Participant')
      ->addValue('extends_entity_column_id:name', 'ParticipantEventType')
      ->addValue('extends_entity_column_value:name', ['Meeting', 'Conference'])
      ->addValue('title', 'meeting_conference')
      ->addChain('field', CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('label', 'sub_field')
        ->addValue('html_type', 'Text')
      )
      ->execute();

    // CustomGroup based on Participant Role
    CustomGroup::create(FALSE)
      ->addValue('extends', 'Participant')
      ->addValue('extends_entity_column_id:name', 'ParticipantRole')
      ->addValue('extends_entity_column_value:name', ['Volunteer', 'Host'])
      ->addValue('title', 'volunteer_host')
      ->addChain('field', CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('label', 'sub_field')
        ->addValue('html_type', 'Text')
      )
      ->execute();

    // CustomGroup based on Specific Events
    CustomGroup::create(FALSE)
      ->addValue('extends', 'Participant')
      ->addValue('extends_entity_column_id:name', 'ParticipantEventName')
      ->addValue('extends_entity_column_value', [$event2['id'], $event3['id']])
      ->addValue('title', 'event_2_and_3')
      ->addChain('field', CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('label', 'sub_field')
        ->addValue('html_type', 'Text')
      )
      ->execute();

    // Unconditional Participant CustomGroup
    CustomGroup::create(FALSE)
      ->addValue('extends', 'Participant')
      ->addValue('title', 'always')
      ->addChain('field', CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('label', 'on')
        ->addValue('html_type', 'Text')
      )
      ->execute();

    $allFields = Participant::getFields(FALSE)->execute()->indexBy('name');
    $this->assertArrayHasKey('meeting_conference.sub_field', $allFields);
    $this->assertArrayHasKey('volunteer_host.sub_field', $allFields);
    $this->assertArrayHasKey('event_2_and_3.sub_field', $allFields);
    $this->assertArrayHasKey('always.on', $allFields);

    $participant0Fields = Participant::getFields(FALSE)
      ->addValue('id', $participants[0]['id'])
      ->execute()->indexBy('name');
    $this->assertArrayHasKey('meeting_conference.sub_field', $participant0Fields);
    $this->assertArrayNotHasKey('volunteer_host.sub_field', $participant0Fields);
    $this->assertArrayNotHasKey('event_2_and_3.sub_field', $participant0Fields);
    $this->assertArrayHasKey('always.on', $participant0Fields);

    $participant1Fields = Participant::getFields(FALSE)
      ->addValue('id', $participants[1]['id'])
      ->execute()->indexBy('name');
    $this->assertArrayHasKey('meeting_conference.sub_field', $participant1Fields);
    $this->assertArrayHasKey('volunteer_host.sub_field', $participant1Fields);
    $this->assertArrayHasKey('event_2_and_3.sub_field', $participant1Fields);
    $this->assertArrayHasKey('always.on', $participant1Fields);

    $participant2Fields = Participant::getFields(FALSE)
      ->addValue('id', $participants[2]['id'])
      ->execute()->indexBy('name');
    $this->assertArrayHasKey('meeting_conference.sub_field', $participant2Fields);
    $this->assertArrayNotHasKey('volunteer_host.sub_field', $participant2Fields);
    $this->assertArrayHasKey('event_2_and_3.sub_field', $participant2Fields);

    $participant3Fields = Participant::getFields(FALSE)
      ->addValue('id', $participants[3]['id'])
      ->execute()->indexBy('name');
    $this->assertArrayNotHasKey('meeting_conference.sub_field', $participant3Fields);
    $this->assertArrayHasKey('volunteer_host.sub_field', $participant3Fields);
    $this->assertArrayNotHasKey('event_3_and_3.sub_field', $participant3Fields);
    $this->assertArrayHasKey('always.on', $participant3Fields);

    $event1Fields = Participant::getFields(FALSE)
      ->addValue('event_id', $event1['id'])
      ->execute()->indexBy('name');
    $this->assertArrayHasKey('meeting_conference.sub_field', $event1Fields);
    $this->assertArrayNotHasKey('event_2_and_3.sub_field', $event1Fields);
    $this->assertArrayHasKey('always.on', $event1Fields);

    $event4Fields = Participant::getFields(FALSE)
      ->addValue('event_id', $event4['id'])
      ->execute()->indexBy('name');
    $this->assertArrayNotHasKey('meeting_conference.sub_field', $event4Fields);
    $this->assertArrayNotHasKey('event_3_and_3.sub_field', $event4Fields);
    $this->assertArrayHasKey('always.on', $event4Fields);
  }

  public function testFiltersAreReturnedForContactRefFields(): void {
    $grp = CustomGroup::create(FALSE)
      ->addValue('extends', 'Activity')
      ->addValue('title', 'act_test_grp2')
      ->execute()->single();
    $field = $this->createTestRecord('CustomField', [
      'data_type' => 'ContactReference',
      'html_type' => 'Autocomplete-Select',
      'custom_group_id' => $grp['id'],
      'filter' => 'action=get&contact_type=Household&group=2',
    ]);
    $getField = Activity::getFields(FALSE)
      ->addWhere('custom_field_id', '=', $field['id'])
      ->execute()->single();
    $this->assertEquals(['contact_type' => 'Household', 'groups' => 2], $getField['input_attrs']['filter']);
  }

}
