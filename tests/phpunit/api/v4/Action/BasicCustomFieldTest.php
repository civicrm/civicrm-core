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


namespace api\v4\Action;

use Civi\Api4\Contact;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\OptionGroup;
use Civi\Api4\Relationship;
use Civi\Api4\RelationshipCache;

/**
 * @group headless
 */
class BasicCustomFieldTest extends BaseCustomValueTest {

  /**
   * @throws \API_Exception
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
    $this->assertNotContains('MyIndividualFields.FavColor', $getFields->setValues(['contact_type' => 'Household'])->execute()->column('name'));

    $contactId = Contact::create(FALSE)
      ->addValue('first_name', 'Johann')
      ->addValue('last_name', 'Tester')
      ->addValue('contact_type', 'Individual')
      ->addValue('MyIndividualFields.FavColor', 'Red')
      ->execute()
      ->first()['id'];

    $contact = Contact::get(FALSE)
      ->addSelect('first_name')
      ->addSelect('MyIndividualFields.FavColor')
      ->addWhere('id', '=', $contactId)
      ->addWhere('MyIndividualFields.FavColor', '=', 'Red')
      ->execute()
      ->first();

    $this->assertEquals('Red', $contact['MyIndividualFields.FavColor']);

    Contact::update()
      ->addWhere('id', '=', $contactId)
      ->addValue('MyIndividualFields.FavColor', 'Blue')
      ->execute();

    $contact = Contact::get(FALSE)
      ->addSelect('MyIndividualFields.FavColor')
      ->addWhere('id', '=', $contactId)
      ->execute()
      ->first();

    $this->assertEquals('Blue', $contact['MyIndividualFields.FavColor']);

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
  }

  public function testWithTwoFields() {
    $optionGroupCount = OptionGroup::get(FALSE)->selectRowCount()->execute()->count();

    // First custom set
    CustomGroup::create(FALSE)
      ->addValue('title', 'MyContactFields')
      ->addValue('extends', 'Contact')
      ->addChain('field1', CustomField::create()
        ->addValue('label', 'FavColor')
        ->addValue('custom_group_id', '$id')
        ->addValue('html_type', 'Text')
        ->addValue('data_type', 'String'))
      ->addChain('field2', CustomField::create()
        ->addValue('label', 'FavFood')
        ->addValue('custom_group_id', '$id')
        ->addValue('html_type', 'Text')
        ->addValue('data_type', 'String'))
      ->execute();

    // Second custom set
    CustomGroup::create(FALSE)
      ->addValue('title', 'MyContactFields2')
      ->addValue('extends', 'Contact')
      ->addChain('field1', CustomField::create()
        ->addValue('label', 'FavColor')
        ->addValue('custom_group_id', '$id')
        ->addValue('html_type', 'Text')
        ->addValue('data_type', 'String'))
      ->addChain('field2', CustomField::create()
        ->addValue('label', 'FavFood')
        ->addValue('custom_group_id', '$id')
        ->addValue('html_type', 'Text')
        ->addValue('is_required', TRUE)
        ->addValue('data_type', 'String'))
      ->execute();

    // Test that no new option groups have been created (these are text fields with no options)
    $this->assertEquals($optionGroupCount, OptionGroup::get(FALSE)->selectRowCount()->execute()->count());

    // Check getFields output
    $fields = Contact::getFields(FALSE)->execute()->indexBy('name');
    $this->assertFalse($fields['MyContactFields2.FavColor']['required']);
    $this->assertTRUE($fields['MyContactFields2.FavColor']['nullable']);
    // Custom fields are never actually *required* in the api, even if is_required = true
    $this->assertFalse($fields['MyContactFields2.FavFood']['required']);
    // But the api will report is_required as not nullable
    $this->assertFalse($fields['MyContactFields2.FavFood']['nullable']);

    $contactId1 = Contact::create(FALSE)
      ->addValue('first_name', 'Johann')
      ->addValue('last_name', 'Tester')
      ->addValue('MyContactFields.FavColor', 'Red')
      ->addValue('MyContactFields.FavFood', 'Cherry')
      ->execute()
      ->first()['id'];

    $contactId2 = Contact::create(FALSE)
      ->addValue('first_name', 'MaryLou')
      ->addValue('last_name', 'Tester')
      ->addValue('MyContactFields.FavColor', 'Purple')
      ->addValue('MyContactFields.FavFood', 'Grapes')
      ->execute()
      ->first()['id'];

    $contact = Contact::get(FALSE)
      ->addSelect('first_name')
      ->addSelect('MyContactFields.FavColor')
      ->addSelect('MyContactFields.FavFood')
      ->addWhere('id', '=', $contactId1)
      ->addWhere('MyContactFields.FavColor', '=', 'Red')
      ->addWhere('MyContactFields.FavFood', '=', 'Cherry')
      ->execute()
      ->first();
    $this->assertArrayHasKey('MyContactFields.FavColor', $contact);
    $this->assertEquals('Red', $contact['MyContactFields.FavColor']);

    // By default custom fields are not returned
    $contact = Contact::get(FALSE)
      ->addWhere('id', '=', $contactId1)
      ->addWhere('MyContactFields.FavColor', '=', 'Red')
      ->addWhere('MyContactFields.FavFood', '=', 'Cherry')
      ->execute()
      ->first();
    $this->assertArrayNotHasKey('MyContactFields.FavColor', $contact);

    // Update 2nd set and ensure 1st hasn't changed
    Contact::update()
      ->addWhere('id', '=', $contactId1)
      ->addValue('MyContactFields2.FavColor', 'Orange')
      ->addValue('MyContactFields2.FavFood', 'Tangerine')
      ->execute();
    $contact = Contact::get(FALSE)
      ->addSelect('MyContactFields.FavColor', 'MyContactFields2.FavColor', 'MyContactFields.FavFood', 'MyContactFields2.FavFood')
      ->addWhere('id', '=', $contactId1)
      ->execute()
      ->first();
    $this->assertEquals('Red', $contact['MyContactFields.FavColor']);
    $this->assertEquals('Orange', $contact['MyContactFields2.FavColor']);
    $this->assertEquals('Cherry', $contact['MyContactFields.FavFood']);
    $this->assertEquals('Tangerine', $contact['MyContactFields2.FavFood']);

    // Update 1st set and ensure 2st hasn't changed
    Contact::update()
      ->addWhere('id', '=', $contactId1)
      ->addValue('MyContactFields.FavColor', 'Blue')
      ->execute();
    $contact = Contact::get(FALSE)
      ->addSelect('custom.*')
      ->addWhere('id', '=', $contactId1)
      ->execute()
      ->first();
    $this->assertEquals('Blue', $contact['MyContactFields.FavColor']);
    $this->assertEquals('Orange', $contact['MyContactFields2.FavColor']);
    $this->assertEquals('Cherry', $contact['MyContactFields.FavFood']);
    $this->assertEquals('Tangerine', $contact['MyContactFields2.FavFood']);

    $search = Contact::get(FALSE)
      ->addClause('OR', ['MyContactFields.FavColor', '=', 'Blue'], ['MyContactFields.FavFood', '=', 'Grapes'])
      ->addSelect('id')
      ->addOrderBy('id')
      ->execute()
      ->indexBy('id');

    $this->assertEquals([$contactId1, $contactId2], array_keys((array) $search));

    $search = Contact::get(FALSE)
      ->addClause('NOT', ['MyContactFields.FavColor', '=', 'Purple'], ['MyContactFields.FavFood', '=', 'Grapes'])
      ->addSelect('id')
      ->addOrderBy('id')
      ->execute()
      ->indexBy('id');

    $this->assertNotContains($contactId2, array_keys((array) $search));

    $search = Contact::get(FALSE)
      ->addClause('NOT', ['MyContactFields.FavColor', '=', 'Purple'], ['MyContactFields.FavFood', '=', 'Grapes'])
      ->addSelect('id')
      ->addOrderBy('id')
      ->execute()
      ->indexBy('id');

    $this->assertContains($contactId1, array_keys((array) $search));
    $this->assertNotContains($contactId2, array_keys((array) $search));

    $search = Contact::get(FALSE)
      ->setWhere([['NOT', ['OR', [['MyContactFields.FavColor', '=', 'Blue'], ['MyContactFields.FavFood', '=', 'Grapes']]]]])
      ->addSelect('id')
      ->addOrderBy('id')
      ->execute()
      ->indexBy('id');

    $this->assertNotContains($contactId1, array_keys((array) $search));
    $this->assertNotContains($contactId2, array_keys((array) $search));
  }

  public function testRelationshipCacheCustomFields() {
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

    $parent = Contact::create(FALSE)
      ->addValue('first_name', 'Parent')
      ->addValue('last_name', 'Tester')
      ->addValue('contact_type', 'Individual')
      ->execute()
      ->first()['id'];

    $child = Contact::create(FALSE)
      ->addValue('first_name', 'Child')
      ->addValue('last_name', 'Tester')
      ->addValue('contact_type', 'Individual')
      ->execute()
      ->first()['id'];

    Relationship::create(FALSE)
      ->addValue('contact_id_a', $parent)
      ->addValue('contact_id_b', $child)
      ->addValue('relationship_type_id', 1)
      ->addValue("$cgName.PetName", 'Buddy')
      ->execute();

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

  public function testMultipleJoinsToCustomTable() {
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

    $parent = Contact::create(FALSE)
      ->addValue('first_name', 'Parent')
      ->addValue('last_name', 'Tester')
      ->addValue("$cgName.FavColor", 'Purple')
      ->execute()
      ->first()['id'];

    $child = Contact::create(FALSE)
      ->addValue('first_name', 'Child')
      ->addValue('last_name', 'Tester')
      ->addValue("$cgName.FavColor", 'Cyan')
      ->execute()
      ->first()['id'];

    Relationship::create(FALSE)
      ->addValue('contact_id_a', $parent)
      ->addValue('contact_id_b', $child)
      ->addValue('relationship_type_id', 1)
      ->execute();

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
   * @throws \API_Exception
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

  public function testUpdateWeights() {
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

    $cid = Contact::create(FALSE)
      ->addValue('first_name', 'Parent')
      ->addValue('last_name', 'Tester')
      ->addValue("$cgName.DateOnly", '2025-05-10')
      ->addValue("$cgName.DateTime", '2025-06-11 12:15:30')
      ->execute()
      ->first()['id'];
    $contact = Contact::get(FALSE)
      ->addSelect('custom.*')
      ->addWhere('id', '=', $cid)
      ->execute()->first();
    // Date field should only return date part
    $this->assertEquals('2025-05-10', $contact["$cgName.DateOnly"]);
    // Date time field should return all
    $this->assertEquals('2025-06-11 12:15:30', $contact["$cgName.DateTime"]);
  }

}
