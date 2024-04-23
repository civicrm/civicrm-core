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

use Civi\Api4\Contact;
use Civi\Api4\Contribution;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\OptionGroup;
use Civi\Api4\RelationshipCache;

/**
 * @group headless
 */
class BasicCustomFieldTest extends CustomTestBase {

  /**
   * @throws \CRM_Core_Exception
   */
  public function testWithSingleField(): void {
    $customGroup = CustomGroup::create(FALSE)
      ->addValue('title', 'MyIndividualFields')
      ->addValue('extends', 'Individual')
      ->execute()
      ->first();

    CustomField::create(FALSE)
      ->addValue('label', 'FavColor')
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'Text')
      ->addValue('data_type', 'String')
      ->execute();

    // Individual fields should show up when contact_type = null|Individual but not other contact types
    $getFields = Contact::getFields(FALSE);
    $this->assertEquals('Custom', $getFields->execute()->indexBy('name')['MyIndividualFields.FavColor']['type']);
    $this->assertContains('MyIndividualFields.FavColor', $getFields->setValues(['contact_type' => 'Individual'])->execute()->column('name'));
    $this->assertNotContains('MyIndividualFields.FavColor', $getFields->setValues(['contact_type:name' => 'Household'])->execute()->column('name'));

    $contactId = $this->createTestRecord('Contact', [
      'first_name' => 'Johann',
      'last_name' => 'Tester',
      'contact_type' => 'Individual',
      'MyIndividualFields.FavColor' => '<Red>',
    ])['id'];

    $contact = Contact::get(FALSE)
      ->addSelect('first_name')
      ->addSelect('MyIndividualFields.FavColor')
      ->addWhere('id', '=', $contactId)
      ->addWhere('MyIndividualFields.FavColor', '=', '<Red>')
      ->execute()
      ->first();

    $this->assertEquals('<Red>', $contact['MyIndividualFields.FavColor']);

    Contact::update()
      ->addWhere('id', '=', $contactId)
      ->addValue('MyIndividualFields.FavColor', 'Blue&Pink')
      ->execute();

    $contact = Contact::get(FALSE)
      ->addSelect('MyIndividualFields.FavColor')
      ->addWhere('id', '=', $contactId)
      ->execute()
      ->first();

    $this->assertEquals('Blue&Pink', $contact['MyIndividualFields.FavColor']);

    // Try setting to null
    Contact::update()
      ->addWhere('id', '=', $contactId)
      ->addValue('MyIndividualFields.FavColor', NULL)
      ->execute();
    $contact = Contact::get(FALSE)
      ->addSelect('MyIndividualFields.FavColor')
      ->addWhere('id', '=', $contactId)
      ->execute()
      ->first();
    $this->assertEquals(NULL, $contact['MyIndividualFields.FavColor']);

    // Disable the field and it disappears from getFields and from the API output.
    CustomField::update(FALSE)
      ->addWhere('custom_group_id:name', '=', 'MyIndividualFields')
      ->addWhere('name', '=', 'FavColor')
      ->addValue('is_active', FALSE)
      ->execute();

    $getFields = Contact::getFields(FALSE)
      ->execute()->column('name');
    $this->assertContains('first_name', $getFields);
    $this->assertNotContains('MyIndividualFields.FavColor', $getFields);

    $contact = Contact::get(FALSE)
      ->addSelect('MyIndividualFields.FavColor')
      ->addWhere('id', '=', $contactId)
      ->execute()
      ->first();
    $this->assertArrayNotHasKey('MyIndividualFields.FavColor', $contact);
  }

  public function testWithTwoFields(): void {
    $optionGroupCount = OptionGroup::get(FALSE)->selectRowCount()->execute()->count();

    // First custom set - use underscores in the names to ensure the API doesn't have a problem with them
    CustomGroup::create(FALSE)
      ->addValue('title', 'MyContactFields')
      ->addValue('extends', 'Contact')
      ->addChain('field1', CustomField::create()
        ->addValue('label', '_Color')
        ->addValue('custom_group_id', '$id')
        ->addValue('html_type', 'Text')
        ->addValue('data_type', 'String'))
      ->addChain('field2', CustomField::create()
        ->addValue('label', '_Food')
        ->addValue('custom_group_id', '$id')
        ->addValue('html_type', 'Text')
        ->addValue('data_type', 'String'))
      ->execute();

    // Second custom set
    CustomGroup::create(FALSE)
      ->addValue('title', 'MyContactFields_')
      ->addValue('extends', 'Contact')
      ->addChain('field1', CustomField::create()
        ->addValue('label', '_Color')
        ->addValue('custom_group_id', '$id')
        ->addValue('html_type', 'Text')
        ->addValue('data_type', 'String'))
      ->addChain('field2', CustomField::create()
        ->addValue('label', '_Food')
        ->addValue('custom_group_id', '$id')
        ->addValue('html_type', 'Text')
        ->addValue('is_required', TRUE)
        ->addValue('is_view', TRUE)
        ->addValue('data_type', 'String'))
      ->execute();

    // Test that no new option groups have been created (these are text fields with no options)
    $this->assertEquals($optionGroupCount, OptionGroup::get(FALSE)->selectRowCount()->execute()->count());

    // Check getFields output
    $fields = Contact::getFields(FALSE)->execute()->indexBy('name');
    $this->assertFalse($fields['MyContactFields_._Color']['required']);
    $this->assertTRUE($fields['MyContactFields_._Color']['nullable']);
    // Custom fields are never actually *required* in the api, even if is_required = true
    $this->assertFalse($fields['MyContactFields_._Food']['required']);
    // But the api will report is_required as not nullable
    $this->assertFalse($fields['MyContactFields_._Food']['nullable']);
    $this->assertEquals(['export', 'duplicate_matching', 'token', 'import'], $fields['MyContactFields._Food']['usage']);
    $this->assertEquals(['export', 'duplicate_matching', 'token'], $fields['MyContactFields_._Food']['usage']);

    $contactId1 = $this->createTestRecord('Contact', [
      'first_name' => 'Johann',
      'last_name' => 'Tester',
      'MyContactFields._Color' => 'Red',
      'MyContactFields._Food' => 'Cherry',
    ])['id'];

    $contactId2 = $this->createTestRecord('Contact', [
      'first_name' => 'MaryLou',
      'last_name' => 'Tester',
      'MyContactFields._Color' => 'Purple',
      'MyContactFields._Food' => 'Grapes',
    ])['id'];

    $contact = Contact::get(FALSE)
      ->addSelect('first_name')
      ->addSelect('MyContactFields._Color')
      ->addSelect('MyContactFields._Food')
      ->addWhere('id', '=', $contactId1)
      ->addWhere('MyContactFields._Color', '=', 'Red')
      ->addWhere('MyContactFields._Food', '=', 'Cherry')
      ->execute()
      ->first();
    $this->assertArrayHasKey('MyContactFields._Color', $contact);
    $this->assertEquals('Red', $contact['MyContactFields._Color']);

    // By default custom fields are not returned
    $contact = Contact::get(FALSE)
      ->addWhere('id', '=', $contactId1)
      ->addWhere('MyContactFields._Color', '=', 'Red')
      ->addWhere('MyContactFields._Food', '=', 'Cherry')
      ->execute()
      ->first();
    $this->assertArrayNotHasKey('MyContactFields._Color', $contact);

    // Update 2nd set and ensure 1st hasn't changed
    Contact::update()
      ->addWhere('id', '=', $contactId1)
      ->addValue('MyContactFields_._Color', 'Orange')
      ->addValue('MyContactFields_._Food', 'Tangerine')
      ->execute();
    $contact = Contact::get(FALSE)
      ->addSelect('MyContactFields._Color', 'MyContactFields_._Color', 'MyContactFields._Food', 'MyContactFields_._Food')
      ->addWhere('id', '=', $contactId1)
      ->execute()
      ->first();
    $this->assertEquals('Red', $contact['MyContactFields._Color']);
    $this->assertEquals('Orange', $contact['MyContactFields_._Color']);
    $this->assertEquals('Cherry', $contact['MyContactFields._Food']);
    $this->assertEquals('Tangerine', $contact['MyContactFields_._Food']);

    // Update 1st set and ensure 2st hasn't changed
    Contact::update()
      ->addWhere('id', '=', $contactId1)
      ->addValue('MyContactFields._Color', 'Blue')
      ->execute();
    $contact = Contact::get(FALSE)
      ->addSelect('custom.*')
      ->addWhere('id', '=', $contactId1)
      ->execute()
      ->first();
    $this->assertEquals('Blue', $contact['MyContactFields._Color']);
    $this->assertEquals('Orange', $contact['MyContactFields_._Color']);
    $this->assertEquals('Cherry', $contact['MyContactFields._Food']);
    $this->assertEquals('Tangerine', $contact['MyContactFields_._Food']);

    $search = Contact::get(FALSE)
      ->addClause('OR', ['MyContactFields._Color', '=', 'Blue'], ['MyContactFields._Food', '=', 'Grapes'])
      ->addSelect('id')
      ->addOrderBy('id')
      ->execute()
      ->indexBy('id');

    $this->assertEquals([$contactId1, $contactId2], array_keys((array) $search));

    $search = Contact::get(FALSE)
      ->addClause('NOT', ['MyContactFields._Color', '=', 'Purple'], ['MyContactFields._Food', '=', 'Grapes'])
      ->addSelect('id')
      ->addOrderBy('id')
      ->execute()
      ->indexBy('id');

    $this->assertNotContains($contactId2, array_keys((array) $search));

    $search = Contact::get(FALSE)
      ->addClause('NOT', ['MyContactFields._Color', '=', 'Purple'], ['MyContactFields._Food', '=', 'Grapes'])
      ->addSelect('id')
      ->addOrderBy('id')
      ->execute()
      ->indexBy('id');

    $this->assertContains($contactId1, array_keys((array) $search));
    $this->assertNotContains($contactId2, array_keys((array) $search));

    $search = Contact::get(FALSE)
      ->setWhere([['NOT', ['OR', [['MyContactFields._Color', '=', 'Blue'], ['MyContactFields._Food', '=', 'Grapes']]]]])
      ->addSelect('id')
      ->addOrderBy('id')
      ->execute()
      ->indexBy('id');

    $this->assertNotContains($contactId1, array_keys((array) $search));
    $this->assertNotContains($contactId2, array_keys((array) $search));
  }

  public function testRelationshipCacheCustomFields(): void {
    $cgName = uniqid('RelFields');

    $customGroup = CustomGroup::create(FALSE)
      ->addValue('title', $cgName)
      ->addValue('extends', 'Relationship')
      ->execute()
      ->first();

    CustomField::create(FALSE)
      ->addValue('label', 'PetName')
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'Text')
      ->addValue('data_type', 'String')
      ->execute();

    // Adding custom field to Relationship entity also adds it to RelationshipCache entity
    $this->assertCount(1, RelationshipCache::getFields(FALSE)
      ->addWhere('name', '=', "$cgName.PetName")
      ->execute()
    );

    $parent = $this->createTestRecord('Contact', [
      'first_name' => 'Parent',
      'last_name' => 'Tester',
      'contact_type' => 'Individual',
    ])['id'];

    $child = $this->createTestRecord('Contact', [
      'first_name' => 'Child',
      'last_name' => 'Tester',
      'contact_type' => 'Individual',
    ])['id'];

    $this->createTestRecord('Relationship', [
      'contact_id_a' => $parent,
      'contact_id_b' => $child,
      'relationship_type_id' => 1,
      "$cgName.PetName" => 'Buddy',
    ]);

    // Test get directly from relationshipCache entity
    $results = RelationshipCache::get(FALSE)
      ->addSelect("$cgName.PetName")
      ->addWhere("$cgName.PetName", '=', 'Buddy')
      ->execute();

    $this->assertCount(2, $results);
    $this->assertEquals('Buddy', $results[0]["$cgName.PetName"]);

    // Test get via bridge INNER join
    $result = Contact::get(FALSE)
      ->addSelect('relative.display_name', "relative.$cgName.PetName")
      ->addJoin('Contact AS relative', 'INNER', 'RelationshipCache')
      ->addWhere('id', '=', $parent)
      ->addWhere('relative.relationship_type_id', '=', 1)
      ->execute()->single();
    $this->assertEquals('Child Tester', $result['relative.display_name']);
    $this->assertEquals('Buddy', $result["relative.$cgName.PetName"]);

    // Test get via bridge LEFT join
    $result = Contact::get(FALSE)
      ->addSelect('relative.display_name', "relative.$cgName.PetName")
      ->addJoin('Contact AS relative', 'LEFT', 'RelationshipCache')
      ->addWhere('id', '=', $parent)
      ->addWhere('relative.relationship_type_id', '=', 1)
      ->execute()->single();
    $this->assertEquals('Child Tester', $result['relative.display_name']);
    $this->assertEquals('Buddy', $result["relative.$cgName.PetName"]);
  }

  public function testMultipleJoinsToCustomTable(): void {
    $cgName = uniqid('My');

    CustomGroup::create(FALSE)
      ->addValue('title', $cgName)
      ->addValue('extends', 'Contact')
      ->addChain('field1', CustomField::create()
        ->addValue('label', 'FavColor')
        ->addValue('custom_group_id', '$id')
        ->addValue('html_type', 'Text')
        ->addValue('data_type', 'String'))
      ->execute();

    $parent = $this->createTestRecord('Contact', [
      'first_name' => 'Parent',
      'last_name' => 'Tester',
      "$cgName.FavColor" => 'Purple',
    ])['id'];

    $child = $this->createTestRecord('Contact', [
      'first_name' => 'Child',
      'last_name' => 'Tester',
      "$cgName.FavColor" => 'Cyan',
    ])['id'];

    $this->createTestRecord('Relationship', [
      'contact_id_a' => $parent,
      'contact_id_b' => $child,
      'relationship_type_id' => 1,
    ]);

    $results = Contact::get(FALSE)
      ->addSelect('first_name', 'child.first_name', "$cgName.FavColor", "child.$cgName.FavColor")
      ->addWhere('id', '=', $parent)
      ->addJoin('Contact AS child', 'INNER', 'RelationshipCache', ['id', '=', 'child.far_contact_id'])
      ->execute();

    $this->assertCount(1, $results);
    $this->assertEquals('Parent', $results[0]['first_name']);
    $this->assertEquals('Child', $results[0]['child.first_name']);
    $this->assertEquals('Purple', $results[0]["$cgName.FavColor"]);
    $this->assertEquals('Cyan', $results[0]["child.$cgName.FavColor"]);
  }

  /**
   * Some types are creating a dummy option group even if we don't have
   * any option values.
   * @throws \CRM_Core_Exception
   */
  public function testUndesiredOptionGroupCreation(): void {
    $optionGroupCount = OptionGroup::get(FALSE)->selectRowCount()->execute()->count();

    $customGroup = CustomGroup::create(FALSE)
      ->addValue('title', 'MyIndividualFields')
      ->addValue('extends', 'Individual')
      ->execute()
      ->first();

    // This one doesn't make sense to have an option group.
    CustomField::create(FALSE)
      ->addValue('label', 'FavColor')
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'Number')
      ->addValue('data_type', 'Money')
      ->execute();

    // This one might be ok if we planned to then use the autocreated option
    // group, but if we go on to create our own after then we have an extra
    // unused group.
    CustomField::create(FALSE)
      ->addValue('label', 'FavMovie')
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'Select')
      ->addValue('data_type', 'String')
      ->execute();

    $this->assertEquals($optionGroupCount, OptionGroup::get(FALSE)->selectRowCount()->execute()->count());
  }

  /**
   * Pseudoconstant lookups that are passed an empty string return NULL, not an empty string.
   * @throws \CRM_Core_Exception
   */
  public function testPseudoConstantCreate(): void {
    $optionGroupId = $this->createTestRecord('OptionGroup')['id'];
    $this->createTestRecord('OptionValue', ['option_group_id' => $optionGroupId]);

    $customGroup = CustomGroup::create(FALSE)
      ->addValue('title', 'MyIndividualFields')
      ->addValue('extends', 'Individual')
      ->execute()
      ->first();

    CustomField::create(FALSE)
      ->addValue('label', 'FavMovie')
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'Select')
      ->addValue('data_type', 'String')
      ->addValue('option_group_id', $optionGroupId)
      ->execute();

    Contact::create(FALSE)
      ->addValue('first_name', 'Johann')
      ->addValue('last_name', 'Tester')
      ->addValue('contact_type', 'Individual')
      ->addValue('MyIndividualFields.FavMovie:label', '')
      ->execute();
  }

  public function testUpdateWeights(): void {
    $getValues = function($groupName) {
      return CustomField::get(FALSE)
        ->addWhere('custom_group_id.name', '=', $groupName)
        ->addOrderBy('weight')
        ->execute()->indexBy('name')->column('weight');
    };

    // Create 2 custom groups. Control group is to ensure updating one doesn't affect the other
    foreach (['controlGroup', 'experimentalGroup'] as $groupName) {
      $customGroups[$groupName] = CustomGroup::create(FALSE)
        ->addValue('title', $groupName)
        ->addValue('name', $groupName)
        ->addValue('extends', 'Individual')
        ->execute()->first();
      $sampleData = [
        ['label' => 'One', 'html_type' => 'Select', 'option_values' => ['a' => 'A', 'b' => 'B']],
        ['label' => 'Two'],
        ['label' => 'Three', 'html_type' => 'Select', 'option_values' => ['c' => 'C', 'd' => 'D']],
        ['label' => 'Four'],
      ];
      CustomField::save(FALSE)
        ->setRecords($sampleData)
        ->addDefault('custom_group_id.name', $groupName)
        ->addDefault('html_type', 'Text')
        ->execute();
      // Default weights should have been set during create
      $this->assertEquals(['One' => 1, 'Two' => 2, 'Three' => 3, 'Four' => 4], $getValues($groupName));
    }

    // Testing custom group weights

    $originalControlGroupWeight = $customGroups['controlGroup']['weight'];
    $originalExperimentalGroupWeight = $customGroups['experimentalGroup']['weight'];

    // Ensure default weights were set for custom groups
    $this->assertEquals($originalControlGroupWeight + 1, $originalExperimentalGroupWeight);
    // Updating custom group weight
    $newExperimentalGroupWeight = CustomGroup::update(FALSE)
      ->addValue('id', $customGroups['experimentalGroup']['id'])
      ->addValue('weight', $originalControlGroupWeight)
      ->execute()->first()['weight'];
    // The other group's weight should have auto-adjusted
    $newControlGroupWeight = CustomGroup::get(FALSE)
      ->addWhere('id', '=', $customGroups['controlGroup']['id'])
      ->execute()->first()['weight'];
    $this->assertEquals($newExperimentalGroupWeight + 1, $newControlGroupWeight);

    // Testing custom field weights

    // Move third option to second position
    CustomField::update(FALSE)
      ->addWhere('custom_group_id.name', '=', 'experimentalGroup')
      ->addWhere('name', '=', 'Three')
      ->addValue('weight', 2)
      ->execute();
    // Experimental group should be updated, control group should not
    $this->assertEquals(['One' => 1, 'Three' => 2, 'Two' => 3, 'Four' => 4], $getValues('experimentalGroup'));
    $this->assertEquals(['One' => 1, 'Two' => 2, 'Three' => 3, 'Four' => 4], $getValues('controlGroup'));

    // Move first option to last position
    CustomField::update(FALSE)
      ->addWhere('custom_group_id.name', '=', 'experimentalGroup')
      ->addWhere('name', '=', 'One')
      ->addValue('weight', 4)
      ->execute();
    // Experimental group should be updated, control group should not
    $this->assertEquals(['Three' => 1, 'Two' => 2, 'Four' => 3, 'One' => 4], $getValues('experimentalGroup'));
    $this->assertEquals(['One' => 1, 'Two' => 2, 'Three' => 3, 'Four' => 4], $getValues('controlGroup'));
  }

  /**
   * Ensure custom date fields only return the date part
   */
  public function testCustomDateFields(): void {
    $cgName = uniqid('My');

    CustomGroup::create(FALSE)
      ->addValue('title', $cgName)
      ->addValue('extends', 'Contact')
      ->addChain('field1', CustomField::create()
        ->addValue('label', 'DateOnly')
        ->addValue('custom_group_id', '$id')
        ->addValue('html_type', 'Select Date')
        ->addValue('data_type', 'Date')
        ->addValue('date_format', 'mm/dd/yy'))
      ->addChain('field2', CustomField::create()
        ->addValue('label', 'DateTime')
        ->addValue('custom_group_id', '$id')
        ->addValue('html_type', 'Select Date')
        ->addValue('data_type', 'Date')
        ->addValue('date_format', 'mm/dd/yy')
        ->addValue('time_format', '1'))
      ->execute();

    $cid = $this->createTestRecord('Contact', [
      'first_name' => 'Parent',
      'last_name' => 'Tester',
      "$cgName.DateOnly" => '2025-05-10',
      "$cgName.DateTime" => '2025-06-11 12:15:30',
    ])['id'];
    $contact = Contact::get(FALSE)
      ->addSelect('custom.*')
      ->addWhere('id', '=', $cid)
      ->execute()->first();
    // Date field should only return date part
    $this->assertEquals('2025-05-10', $contact["$cgName.DateOnly"]);
    // Date time field should return all
    $this->assertEquals('2025-06-11 12:15:30', $contact["$cgName.DateTime"]);
  }

  public function testExtendsIdFilter(): void {
    $fieldUnfiltered = \Civi\Api4\CustomGroup::getFields(FALSE)
      ->setLoadOptions(['id', 'name', 'grouping'])
      ->addWhere('name', '=', 'extends_entity_column_id')
      ->execute()->first();
    $this->assertCount(3, $fieldUnfiltered['options']);

    $fieldFilteredByParticipant = \Civi\Api4\CustomGroup::getFields(FALSE)
      ->setLoadOptions(['id', 'name', 'grouping'])
      ->addWhere('name', '=', 'extends_entity_column_id')
      ->addValue('extends', 'Participant')
      ->execute()->first();
    $this->assertEquals($fieldUnfiltered['options'], $fieldFilteredByParticipant['options']);

    $participantOptions = array_column($fieldFilteredByParticipant['options'], 'grouping', 'name');
    $this->assertEquals('event_id', $participantOptions['ParticipantEventName']);
    $this->assertEquals('event_id.event_type_id', $participantOptions['ParticipantEventType']);
    $this->assertEquals('role_id', $participantOptions['ParticipantRole']);

    $fieldFilteredByContact = \Civi\Api4\CustomGroup::getFields(FALSE)
      ->setLoadOptions(['id', 'name', 'grouping'])
      ->addWhere('name', '=', 'extends_entity_column_id')
      ->addValue('extends', 'Contact')
      ->execute()->first();
    $this->assertEquals([], $fieldFilteredByContact['options']);
  }

  public function testExtendsMetadata(): void {
    $field = \Civi\Api4\CustomGroup::getFields(FALSE)
      ->setLoadOptions(['id', 'name', 'grouping'])
      ->addWhere('name', '=', 'extends')
      ->execute()->first();
    $options = array_column($field['options'], 'grouping', 'id');
    $this->assertArrayNotHasKey('ParticipantRole', $options);
    $this->assertArrayHasKey('Participant', $options);
    $this->assertEquals('contact_sub_type', $options['Individual']);
    $this->assertEquals('case_type_id', $options['Case']);

    // Test contribution type
    $financialType = $this->createTestRecord('FinancialType', [
      'name' => 'Test_Type',
      'is_deductible' => TRUE,
      'is_reserved' => FALSE,
    ]);
    $financialType2 = $this->createTestRecord('FinancialType', [
      'name' => 'Fake_Type',
      'is_deductible' => TRUE,
      'is_reserved' => FALSE,
    ]);
    $contributionGroup = CustomGroup::create(FALSE)
      ->addValue('extends', 'Contribution')
      ->addValue('title', 'Contribution_Fields')
      ->addValue('extends_entity_column_value:name', ['Test_Type'])
      ->addChain('fields', CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('label', 'Dummy')
        ->addValue('html_type', 'Text')
      )
      ->execute()->single();
    $this->assertContainsEquals($financialType['id'], $contributionGroup['extends_entity_column_value']);

    $getFieldsWithTestType = Contribution::getFields(FALSE)
      ->addValue('financial_type_id:name', 'Test_Type')
      ->execute()->indexBy('name');
    // Field should be included due to financial type
    $this->assertArrayHasKey('Contribution_Fields.Dummy', $getFieldsWithTestType);

    $getFieldsWithoutTestType = Contribution::getFields(FALSE)
      ->addValue('financial_type_id:name', 'Fake_Type')
      ->execute()->indexBy('name');
    // Field should be excluded due to financial type
    $this->assertArrayNotHasKey('Contribution_Fields.Dummy', $getFieldsWithoutTestType);
  }

  public function testExtendsParticipantMetadata(): void {
    $event1 = $this->createTestRecord('Event', [
      'event_type_id:name' => 'Fundraiser',
      'title' => 'Test Fun Event',
      'start_date' => '2022-05-02 18:24:00',
    ]);
    $event2 = $this->createTestRecord('Event', [
      'event_type_id:name' => 'Fundraiser',
      'title' => 'Test Fun Event2',
      'start_date' => '2022-05-02 18:24:00',
    ]);
    $event3 = $this->createTestRecord('Event', [
      'event_type_id:name' => 'Meeting',
      'title' => 'Test Me Event',
      'start_date' => '2022-05-02 18:24:00',
    ]);

    $field = \Civi\Api4\CustomGroup::getFields(FALSE)
      ->setLoadOptions(['id', 'name', 'label'])
      ->addValue('extends_entity_column_id:name', 'ParticipantEventName')
      ->addWhere('name', '=', 'extends_entity_column_value')
      ->execute()->first();
    $eventOptions = array_column($field['options'], 'label', 'id');
    $this->assertEquals('Test Fun Event', $eventOptions[$event1['id']]);
    $this->assertEquals('Test Fun Event2', $eventOptions[$event2['id']]);
    $this->assertEquals('Test Me Event', $eventOptions[$event3['id']]);

    $field = \Civi\Api4\CustomGroup::getFields(FALSE)
      ->setLoadOptions(['id', 'name', 'label'])
      ->addValue('extends_entity_column_id:name', 'ParticipantEventType')
      ->addWhere('name', '=', 'extends_entity_column_value')
      ->execute()->first();
    $eventOptions = array_column($field['options'], 'name');
    $this->assertContains('Meeting', $eventOptions);
    $this->assertContains('Fundraiser', $eventOptions);

    $field = \Civi\Api4\CustomGroup::getFields(FALSE)
      ->setLoadOptions(['id', 'name', 'label'])
      ->addValue('extends_entity_column_id:name', 'ParticipantRole')
      ->addWhere('name', '=', 'extends_entity_column_value')
      ->execute()->first();
    $roleOptions = array_column($field['options'], 'name');
    $this->assertContains('Volunteer', $roleOptions);
    $this->assertContains('Attendee', $roleOptions);
  }

  /**
   * Ensure rich-text html fields store html correctly
   */
  public function testRichTextHTML(): void {
    $cgName = uniqid('My');

    $custom = CustomGroup::create(FALSE)
      ->addValue('title', $cgName)
      ->addValue('extends', 'Contact')
      ->addChain('field1', CustomField::create()
        ->addValue('label', 'RichText')
        ->addValue('custom_group_id', '$id')
        ->addValue('html_type', 'RichTextEditor')
        ->addValue('data_type', 'Memo'),
      0)
      ->addChain('field2', CustomField::create()
        ->addValue('label', 'TextArea')
        ->addValue('custom_group_id', '$id')
        ->addValue('html_type', 'TextArea')
        ->addValue('data_type', 'Memo'),
      0)
      ->execute()->first();

    $cid = $this->createTestRecord('Contact', [
      'first_name' => 'One',
      'last_name' => 'Tester',
      "$cgName.RichText" => '<em>Hello</em><br />APIv4 & RichText!',
      "$cgName.TextArea" => '<em>Hello</em><br />APIv4 & TextArea!',
    ])['id'];
    $contact = Contact::get(FALSE)
      ->addSelect('custom.*')
      ->addWhere('id', '=', $cid)
      ->execute()->first();
    $this->assertEquals('<em>Hello</em><br />APIv4 & RichText!', $contact["$cgName.RichText"]);
    $this->assertEquals('<em>Hello</em><br />APIv4 & TextArea!', $contact["$cgName.TextArea"]);

    // The html should have been stored unescaped
    $dbVal = \CRM_Core_DAO::singleValueQuery("SELECT {$custom['field1']['column_name']} FROM {$custom['table_name']}");
    $this->assertEquals('<em>Hello</em><br />APIv4 & RichText!', $dbVal);
    $dbVal = \CRM_Core_DAO::singleValueQuery("SELECT {$custom['field2']['column_name']} FROM {$custom['table_name']}");
    $this->assertEquals('<em>Hello</em><br />APIv4 & TextArea!', $dbVal);

    // APIv3 should work the same way
    civicrm_api3('Contact', 'create', [
      'id' => $cid,
      "custom_{$custom['field1']['id']}" => '<em>Hello</em><br />APIv3 & RichText!',
      "custom_{$custom['field2']['id']}" => '<em>Hello</em><br />APIv3 & TextArea!',
    ]);
    $dbVal = \CRM_Core_DAO::singleValueQuery("SELECT {$custom['field1']['column_name']} FROM {$custom['table_name']}");
    $this->assertEquals('<em>Hello</em><br />APIv3 & RichText!', $dbVal);
    $dbVal = \CRM_Core_DAO::singleValueQuery("SELECT {$custom['field2']['column_name']} FROM {$custom['table_name']}");
    $this->assertEquals('<em>Hello</em><br />APIv3 & TextArea!', $dbVal);
  }

}
