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

use api\v4\Api4TestBase;
use Civi\Api4\Contact;
use Civi\Api4\Contribution;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\OptionGroup;
use Civi\Api4\RelationshipCache;

/**
 * @group headless
 */
class BasicCustomFieldTest extends Api4TestBase {

  /**
   * @throws \CRM_Core_Exception
   */
  public function testWithLinkField(): void {
    $this->createTestRecord('CustomGroup', [
      'title' => 'MyIndividualFields',
      'extends' => 'Individual',
    ]);

    $this->createTestRecord('CustomField', [
      'label' => 'MyLink',
      'custom_group_id.name' => 'MyIndividualFields',
      'html_type' => 'Text',
      // Will default to 2047 characters
      'data_type' => 'Link',
      // Test that adding an index works for such a large field
      'is_searchable' => TRUE,
    ]);

    // Individual fields should show up when contact_type = null|Individual but not other contact types
    $getFields = Contact::getFields(FALSE);
    $this->assertEquals('Custom', $getFields->execute()->indexBy('name')['MyIndividualFields.MyLink']['type']);
    $this->assertContains('MyIndividualFields.MyLink', $getFields->setValues(['contact_type' => 'Individual'])->execute()->column('name'));
    $this->assertNotContains('MyIndividualFields.MyLink', $getFields->setValues(['contact_type:name' => 'Household'])->execute()->column('name'));

    $contactId = $this->createTestRecord('Contact', [
      'first_name' => 'Johann',
      'last_name' => 'Tester',
      'contact_type' => 'Individual',
      'MyIndividualFields.MyLink' => 'http://example.com/?q=123&a=456',
    ])['id'];

    $contact = Contact::get(FALSE)
      ->addSelect('first_name')
      ->addSelect('MyIndividualFields.MyLink')
      ->addWhere('id', '=', $contactId)
      ->addWhere('MyIndividualFields.MyLink', 'LIKE', '%q=123&a=456')
      ->execute()
      ->first();

    $this->assertEquals('http://example.com/?q=123&a=456', $contact['MyIndividualFields.MyLink']);

    $veryLongLink = 'http://example.com/?a=' . str_repeat('a', 500) . '&b=' . str_repeat('b', 500) . '&c=' . str_repeat('c', 500) . '&d=' . str_repeat('d', 500);
    Contact::update()
      ->addWhere('id', '=', $contactId)
      ->addValue('MyIndividualFields.MyLink', $veryLongLink)
      ->execute();

    $contact = Contact::get(FALSE)
      ->addSelect('MyIndividualFields.MyLink')
      ->addWhere('id', '=', $contactId)
      ->execute()
      ->first();

    $this->assertSame($veryLongLink, $contact['MyIndividualFields.MyLink']);

    // Try setting to null
    Contact::update()
      ->addWhere('id', '=', $contactId)
      ->addValue('MyIndividualFields.MyLink', NULL)
      ->execute();
    $contact = Contact::get(FALSE)
      ->addSelect('MyIndividualFields.MyLink')
      ->addWhere('id', '=', $contactId)
      ->execute()
      ->first();
    $this->assertEquals(NULL, $contact['MyIndividualFields.MyLink']);

    // Disable the field and it disappears from getFields and from the API output.
    CustomField::update(FALSE)
      ->addWhere('custom_group_id:name', '=', 'MyIndividualFields')
      ->addWhere('name', '=', 'MyLink')
      ->addValue('is_active', FALSE)
      ->execute();

    $getFields = Contact::getFields(FALSE)
      ->execute()->column('name');
    $this->assertContains('first_name', $getFields);
    $this->assertNotContains('MyIndividualFields.MyLink', $getFields);

    $contact = Contact::get(FALSE)
      ->addSelect('MyIndividualFields.MyLink')
      ->addWhere('id', '=', $contactId)
      ->execute()
      ->first();
    $this->assertArrayNotHasKey('MyIndividualFields.MyLink', $contact);
  }

  public function testWithTwoFields(): void {
    $optionGroupCount = OptionGroup::get(FALSE)->selectRowCount()->execute()->count();

    // First custom set - use underscores in the names to ensure the API doesn't have a problem with them
    $this->createTestRecord('CustomGroup', [
      'title' => 'MyContactFields',
      'extends' => 'Contact',
    ]);
    $this->createTestRecord('CustomField', [
      'label' => '_Color',
      'custom_group_id.name' => 'MyContactFields',
      'html_type' => 'Text',
      'data_type' => 'String',
    ]);
    $this->createTestRecord('CustomField', [
      'label' => '_Food',
      'custom_group_id.name' => 'MyContactFields',
      'html_type' => 'Text',
      'data_type' => 'String',
    ]);

    // Second custom set
    $this->createTestRecord('CustomGroup', [
      'title' => 'MyContactFields_',
      'extends' => 'Contact',
    ]);
    $this->createTestRecord('CustomField', [
      'label' => '_Color',
      'custom_group_id.name' => 'MyContactFields_',
      'html_type' => 'Text',
      'data_type' => 'String',
    ]);
    $this->createTestRecord('CustomField', [
      'label' => '_Food',
      'custom_group_id.name' => 'MyContactFields_',
      'html_type' => 'Text',
      'is_required' => TRUE,
      'is_view' => TRUE,
      'data_type' => 'String',
    ]);

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

    $this->createTestRecord('CustomGroup', [
      'title' => $cgName,
      'extends' => 'Relationship',
    ]);

    $this->createTestRecord('CustomField', [
      'label' => 'PetName',
      'custom_group_id.name' => $cgName,
      'html_type' => 'Text',
      'data_type' => 'String',
    ]);

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

    $this->createTestRecord('CustomGroup', [
      'title' => $cgName,
      'extends' => 'Contact',
    ]);

    $this->createTestRecord('CustomField', [
      'label' => 'FavColor',
      'custom_group_id.name' => $cgName,
      'html_type' => 'Text',
      'data_type' => 'String',
    ]);

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

    $this->createTestRecord('CustomGroup', [
      'title' => 'MyIndividualFields',
      'extends' => 'Contact',
    ]);

    // This one doesn't make sense to have an option group.
    CustomField::create(FALSE)
      ->addValue('label', 'FavColor')
      ->addValue('custom_group_id.name', 'MyIndividualFields')
      ->addValue('html_type', 'Number')
      ->addValue('data_type', 'Money')
      ->execute();

    // This one might be ok if we planned to then use the autocreated option
    // group, but if we go on to create our own after then we have an extra
    // unused group.
    CustomField::create(FALSE)
      ->addValue('label', 'FavMovie')
      ->addValue('custom_group_id.name', 'MyIndividualFields')
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

    $customGroup = $this->createTestRecord('CustomGroup', [
      'title' => 'MyIndividualFields',
      'extends' => 'Contact',
    ]);

    CustomField::create(FALSE)
      ->addValue('label', 'FavMovie')
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'Select')
      ->addValue('data_type', 'String')
      ->addValue('option_group_id', $optionGroupId)
      ->execute();

    $this->createTestRecord('Contact', [
      'first_name' => 'Johann',
      'last_name' => 'Tester',
      'contact_type' => 'Individual',
      'MyIndividualFields.FavMovie:label' => '',
    ]);
  }

  public function testUpdateWeights(): void {
    $getValues = function($groupName) {
      return CustomField::get(FALSE)
        ->addWhere('custom_group_id.name', '=', $groupName)
        ->addOrderBy('weight')
        ->execute()->column('weight', 'name');
    };

    // Create 2 custom groups. Control group is to ensure updating one doesn't affect the other
    foreach (['controlGroup', 'experimentalGroup'] as $groupName) {
      $customGroups[$groupName] = $this->createTestRecord('CustomGroup', [
        'title' => $groupName,
        'extends' => 'Individual',
      ]);
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

    $this->createTestRecord('CustomGroup', [
      'title' => $cgName,
      'extends' => 'Contact',
    ])['id'];
    $this->createTestRecord('CustomField', [
      'label' => 'DateOnly',
      'custom_group_id.name' => $cgName,
      'html_type' => 'Select Date',
      'data_type' => 'Date',
      'date_format' => 'mm/dd/yy',
    ]);
    $this->createTestRecord('CustomField', [
      'label' => 'DateTime',
      'custom_group_id.name' => $cgName,
      'html_type' => 'Select Date',
      'data_type' => 'Date',
      'date_format' => 'mm/dd/yy',
      'time_format' => '1',
    ]);

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
    $fieldUnfiltered = CustomGroup::getFields(FALSE)
      ->setLoadOptions(['id', 'name', 'grouping'])
      ->addWhere('name', '=', 'extends_entity_column_id')
      ->execute()->first();
    $this->assertCount(3, $fieldUnfiltered['options']);

    $fieldFilteredByParticipant = CustomGroup::getFields(FALSE)
      ->setLoadOptions(['id', 'name', 'grouping'])
      ->addWhere('name', '=', 'extends_entity_column_id')
      ->addValue('extends', 'Participant')
      ->execute()->first();
    $this->assertEquals($fieldUnfiltered['options'], $fieldFilteredByParticipant['options']);

    $participantOptions = array_column($fieldFilteredByParticipant['options'], 'grouping', 'name');
    $this->assertEquals('event_id', $participantOptions['ParticipantEventName']);
    $this->assertEquals('event_id.event_type_id', $participantOptions['ParticipantEventType']);
    $this->assertEquals('role_id', $participantOptions['ParticipantRole']);

    $fieldFilteredByContact = CustomGroup::getFields(FALSE)
      ->setLoadOptions(['id', 'name', 'grouping'])
      ->addWhere('name', '=', 'extends_entity_column_id')
      ->addValue('extends', 'Contact')
      ->execute()->first();
    $this->assertEquals([], $fieldFilteredByContact['options']);
  }

  public function testExtendsMetadata(): void {
    $field = CustomGroup::getFields(FALSE)
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
    $contributionGroup = $this->createTestRecord('CustomGroup', [
      'extends' => 'Contribution',
      'title' => 'Contribution_Fields',
      'extends_entity_column_value:name' => ['Test_Type'],
    ]);
    $this->createTestRecord('CustomField', [
      'custom_group_id' => $contributionGroup['id'],
      'label' => 'Dummy',
      'html_type' => 'Text',
    ]);
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

    $field = CustomGroup::getFields(FALSE)
      ->setLoadOptions(['id', 'name', 'label'])
      ->addValue('extends_entity_column_id:name', 'ParticipantEventName')
      ->addWhere('name', '=', 'extends_entity_column_value')
      ->execute()->first();
    $eventOptions = array_column($field['options'], 'label', 'id');
    $this->assertEquals('Test Fun Event', $eventOptions[$event1['id']]);
    $this->assertEquals('Test Fun Event2', $eventOptions[$event2['id']]);
    $this->assertEquals('Test Me Event', $eventOptions[$event3['id']]);

    $field = CustomGroup::getFields(FALSE)
      ->setLoadOptions(['id', 'name', 'label'])
      ->addValue('extends_entity_column_id:name', 'ParticipantEventType')
      ->addWhere('name', '=', 'extends_entity_column_value')
      ->execute()->first();
    $eventOptions = array_column($field['options'], 'name');
    $this->assertContains('Meeting', $eventOptions);
    $this->assertContains('Fundraiser', $eventOptions);

    $field = CustomGroup::getFields(FALSE)
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

    $custom = $this->createTestRecord('CustomGroup', [
      'title' => $cgName,
      'extends' => 'Contact',
    ]);
    $field1 = $this->createTestRecord('CustomField', [
      'label' => 'RichText',
      'custom_group_id.name' => $cgName,
      'html_type' => 'RichTextEditor',
      'data_type' => 'Memo',
    ]);
    $field2 = $this->createTestRecord('CustomField', [
      'label' => 'TextArea',
      'custom_group_id.name' => $cgName,
      'html_type' => 'TextArea',
      'data_type' => 'Memo',
    ]);

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
    $dbVal = \CRM_Core_DAO::singleValueQuery("SELECT {$field1['column_name']} FROM {$custom['table_name']}");
    $this->assertEquals('<em>Hello</em><br />APIv4 & RichText!', $dbVal);
    $dbVal = \CRM_Core_DAO::singleValueQuery("SELECT {$field2['column_name']} FROM {$custom['table_name']}");
    $this->assertEquals('<em>Hello</em><br />APIv4 & TextArea!', $dbVal);

    // APIv3 should work the same way
    civicrm_api3('Contact', 'create', [
      'id' => $cid,
      "custom_{$field1['id']}" => '<em>Hello</em><br />APIv3 & RichText!',
      "custom_{$field2['id']}" => '<em>Hello</em><br />APIv3 & TextArea!',
    ]);
    $dbVal = \CRM_Core_DAO::singleValueQuery("SELECT {$field1['column_name']} FROM {$custom['table_name']}");
    $this->assertEquals('<em>Hello</em><br />APIv3 & RichText!', $dbVal);
    $dbVal = \CRM_Core_DAO::singleValueQuery("SELECT {$field2['column_name']} FROM {$custom['table_name']}");
    $this->assertEquals('<em>Hello</em><br />APIv3 & TextArea!', $dbVal);
  }

}
