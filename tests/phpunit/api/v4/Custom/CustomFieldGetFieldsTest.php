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

  public function testCustomFieldTypes(): void {
    $customGroupName = CustomGroup::create(FALSE)
      ->addValue('extends', 'Activity')
      ->addValue('title', __FUNCTION__)
      ->execute()->single()['name'];

    $customFields = $this->saveTestRecords('CustomField', [
      'records' => [
        [
          'name' => 'dateonly',
          'data_type' => 'Date',
          'html_type' => 'Select Date',
          'date_format' => 'mm/dd/yy',
        ],
        [
          'name' => 'datetime',
          'data_type' => 'Date',
          'html_type' => 'Select Date',
          'date_format' => 'mm/dd/yy',
          'time_format' => '1',
        ],
        [
          'name' => 'int',
          'data_type' => 'Int',
          'html_type' => 'Text',
        ],
        [
          'name' => 'float',
          'data_type' => 'Float',
          'html_type' => 'Text',
        ],
        [
          'name' => 'money',
          'data_type' => 'Money',
          'html_type' => 'Text',
        ],
        [
          'name' => 'memo',
          'data_type' => 'Memo',
          'html_type' => 'TextArea',
          'note_columns' => 55,
        ],
        [
          'name' => 'str',
          'data_type' => 'String',
          'html_type' => 'Text',
          'default_value' => 'Hello',
          'text_length' => 123,
        ],
        [
          'name' => 'multiselect',
          'data_type' => 'String',
          'html_type' => 'Select',
          'option_values' => [
            'red' => 'Red',
            'blue' => 'Blue',
          ],
          'serialize' => 1,
        ],
        [
          'name' => 'autocomplete',
          'data_type' => 'String',
          'html_type' => 'Autocomplete-Select',
          'option_values' => [
            'red' => 'Red',
            'blue' => 'Blue',
          ],
        ],
        [
          'name' => 'bool',
          'data_type' => 'Boolean',
          'html_type' => 'Radio',
        ],
        [
          'name' => 'state',
          'data_type' => 'StateProvince',
          'html_type' => 'Select',
        ],
        [
          'name' => 'country',
          'data_type' => 'Country',
          'html_type' => 'Select',
          'serialize' => 1,
        ],
        [
          'name' => 'file',
          'data_type' => 'File',
          'html_type' => 'File',
        ],
        [
          'name' => 'entityref',
          'data_type' => 'EntityReference',
          'html_type' => 'Autocomplete-Select',
          'fk_entity' => 'Activity',
          'fk_entity_on_delete' => 'cascade',
        ],
        [
          'name' => 'contactref',
          'data_type' => 'ContactReference',
          'html_type' => 'Autocomplete-Select',
          'filter' => 'action=get&group=123',
        ],
      ],
      'defaults' => ['custom_group_id.name' => $customGroupName],
    ])->indexBy('name');

    $fields = Activity::getFields(FALSE)
      ->addWhere('name', 'LIKE', $customGroupName . '.%')
      ->setAction('create')
      ->execute()->indexBy('name');

    // Check date field
    $field = $fields["$customGroupName.dateonly"];
    $this->assertSame('Date', $field['input_type']);
    $this->assertSame('Date', $field['data_type']);
    $this->assertFalse($field['input_attrs']['time']);
    $this->assertSame('mm/dd/yy', $field['input_attrs']['date']);
    $this->assertFalse($field['options']);
    $this->assertNull($field['operators']);
    $this->assertNull($field['serialize']);

    // Check datetime field
    $field = $fields["$customGroupName.datetime"];
    $this->assertSame('Date', $field['input_type']);
    $this->assertSame('Timestamp', $field['data_type']);
    $this->assertSame(12, $field['input_attrs']['time']);
    $this->assertSame('mm/dd/yy', $field['input_attrs']['date']);
    $this->assertFalse($field['options']);
    $this->assertNull($field['operators']);
    $this->assertNull($field['serialize']);

    // Check int field
    $field = $fields["$customGroupName.int"];
    $this->assertSame('Number', $field['input_type']);
    $this->assertSame('Integer', $field['data_type']);
    $this->assertSame(1, $field['input_attrs']['step']);
    $this->assertFalse($field['options']);
    $this->assertNull($field['operators']);
    $this->assertNull($field['serialize']);

    // Check float field
    $field = $fields["$customGroupName.float"];
    $this->assertSame('Number', $field['input_type']);
    $this->assertSame('Float', $field['data_type']);
    $this->assertSame(.01, $field['input_attrs']['step']);
    $this->assertFalse($field['options']);
    $this->assertNull($field['operators']);
    $this->assertNull($field['serialize']);

    // Check money field
    $field = $fields["$customGroupName.money"];
    $this->assertSame('Text', $field['input_type']);
    $this->assertSame('Money', $field['data_type']);
    $this->assertArrayNotHasKey('step', $field['input_attrs']);
    $this->assertFalse($field['options']);
    $this->assertNull($field['operators']);
    $this->assertNull($field['serialize']);

    // Check memo field
    $field = $fields["$customGroupName.memo"];
    $this->assertSame('TextArea', $field['input_type']);
    $this->assertSame('Text', $field['data_type']);
    $this->assertSame(4, $field['input_attrs']['rows']);
    $this->assertSame(55, $field['input_attrs']['cols']);
    $this->assertArrayNotHasKey('step', $field['input_attrs']);
    $this->assertFalse($field['options']);
    $this->assertNull($field['operators']);
    $this->assertNull($field['serialize']);

    // Check str field
    $field = $fields["$customGroupName.str"];
    $this->assertSame('Text', $field['input_type']);
    $this->assertSame('String', $field['data_type']);
    $this->assertEquals('Hello', $field['default_value']);
    $this->assertSame(123, $field['input_attrs']['maxlength']);
    $this->assertArrayNotHasKey('step', $field['input_attrs']);
    $this->assertArrayNotHasKey('rows', $field['input_attrs']);
    $this->assertArrayNotHasKey('cols', $field['input_attrs']);
    $this->assertFalse($field['options']);
    $this->assertNull($field['operators']);
    $this->assertNull($field['serialize']);

    // Check multiselect field
    $field = $fields["$customGroupName.multiselect"];
    $this->assertSame('Select', $field['input_type']);
    $this->assertSame('String', $field['data_type']);
    $this->assertNull($field['default_value']);
    $this->assertArrayNotHasKey('step', $field['input_attrs']);
    $this->assertArrayNotHasKey('rows', $field['input_attrs']);
    $this->assertArrayNotHasKey('cols', $field['input_attrs']);
    $this->assertTrue($field['options']);
    $this->assertNull($field['operators']);
    $this->assertSame(1, $field['serialize']);
    $this->assertTrue($field['input_attrs']['multiple']);

    // Check autocomplete field
    $field = $fields["$customGroupName.autocomplete"];
    $this->assertSame('EntityRef', $field['input_type']);
    $this->assertSame('String', $field['data_type']);
    $this->assertEquals('OptionValue', $field['fk_entity']);
    $this->assertEquals('value', $field['fk_column']);
    $this->assertNull($field['default_value']);
    $this->assertArrayNotHasKey('step', $field['input_attrs']);
    $this->assertArrayNotHasKey('rows', $field['input_attrs']);
    $this->assertArrayNotHasKey('cols', $field['input_attrs']);
    $this->assertFalse($field['options']);
    $this->assertNull($field['operators']);
    $this->assertNull($field['serialize']);
    $this->assertTrue(empty($field['input_attrs']['multiple']));
    $this->assertEquals(['option_group_id' => $customFields['autocomplete']['option_group_id']], $field['input_attrs']['filter']);

    // Check bool field
    $field = $fields["$customGroupName.bool"];
    $this->assertSame('Radio', $field['input_type']);
    $this->assertSame('Boolean', $field['data_type']);
    $this->assertNull($field['default_value']);
    $this->assertArrayNotHasKey('step', $field['input_attrs']);
    $this->assertArrayNotHasKey('rows', $field['input_attrs']);
    $this->assertArrayNotHasKey('cols', $field['input_attrs']);
    $this->assertFalse($field['options']);
    $this->assertNull($field['operators']);
    $this->assertNull($field['serialize']);

    // Check state field
    $field = $fields["$customGroupName.state"];
    $this->assertSame('Select', $field['input_type']);
    $this->assertSame('Integer', $field['data_type']);
    $this->assertNull($field['default_value']);
    $this->assertArrayNotHasKey('step', $field['input_attrs']);
    $this->assertArrayNotHasKey('rows', $field['input_attrs']);
    $this->assertArrayNotHasKey('cols', $field['input_attrs']);
    $this->assertTrue($field['options']);
    $this->assertNull($field['operators']);
    $this->assertNull($field['serialize']);

    // Check country field
    $field = $fields["$customGroupName.country"];
    $this->assertSame('Select', $field['input_type']);
    $this->assertSame('Integer', $field['data_type']);
    $this->assertNull($field['default_value']);
    $this->assertArrayNotHasKey('step', $field['input_attrs']);
    $this->assertArrayNotHasKey('rows', $field['input_attrs']);
    $this->assertArrayNotHasKey('cols', $field['input_attrs']);
    $this->assertTrue($field['options']);
    $this->assertNull($field['operators']);
    $this->assertSame(1, $field['serialize']);
    $this->assertTrue($field['input_attrs']['multiple']);

    // Check file field
    $field = $fields["$customGroupName.file"];
    $this->assertSame('File', $field['input_type']);
    $this->assertSame('Integer', $field['data_type']);
    $this->assertEquals('File', $field['fk_entity']);
    $this->assertEquals('id', $field['fk_column']);
    $this->assertNull($field['default_value']);
    $this->assertArrayNotHasKey('step', $field['input_attrs']);
    $this->assertArrayNotHasKey('rows', $field['input_attrs']);
    $this->assertArrayNotHasKey('cols', $field['input_attrs']);
    $this->assertFalse($field['options']);
    $this->assertNull($field['operators']);
    $this->assertNull($field['serialize']);

    // Check entityref field
    $field = $fields["$customGroupName.entityref"];
    $this->assertSame('EntityRef', $field['input_type']);
    $this->assertSame('Integer', $field['data_type']);
    $this->assertEquals('Activity', $field['fk_entity']);
    $this->assertEquals('id', $field['fk_column']);
    $this->assertNull($field['default_value']);
    $this->assertArrayNotHasKey('step', $field['input_attrs']);
    $this->assertArrayNotHasKey('rows', $field['input_attrs']);
    $this->assertArrayNotHasKey('cols', $field['input_attrs']);
    $this->assertFalse($field['options']);
    $this->assertNull($field['operators']);
    $this->assertNull($field['serialize']);

    // Check contactref field
    $field = $fields["$customGroupName.contactref"];
    $this->assertSame('EntityRef', $field['input_type']);
    $this->assertSame('Integer', $field['data_type']);
    $this->assertEquals('Contact', $field['fk_entity']);
    $this->assertEquals('id', $field['fk_column']);
    $this->assertNull($field['default_value']);
    $this->assertArrayNotHasKey('step', $field['input_attrs']);
    $this->assertArrayNotHasKey('rows', $field['input_attrs']);
    $this->assertArrayNotHasKey('cols', $field['input_attrs']);
    $this->assertFalse($field['options']);
    $this->assertNull($field['operators']);
    $this->assertNull($field['serialize']);
    $this->assertEquals(['groups' => 123], $field['input_attrs']['filter']);
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
    $this->assertSame('Custom', $getField['type']);
    $this->assertSame('Activity', $getField['entity']);
    $this->assertSame('EntityRef', $getField['input_type']);
    $this->assertSame($grp['table_name'], $getField['table_name']);
    $this->assertSame($field['column_name'], $getField['column_name']);
    $this->assertFalse($getField['required']);
    $this->assertSame($grp['name'] . '.' . $field['name'], $getField['name']);
    $this->assertEquals(['contact_type' => 'Household', 'groups' => 2], $getField['input_attrs']['filter']);
  }

}
