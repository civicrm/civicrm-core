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

use Civi\Api4\Activity;
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;

/**
 * @group headless
 */
class CustomFieldAlterTest extends BaseCustomValueTest {

  public function testChangeSerialize() {
    $contact = $this->createEntity(['type' => 'Individual']);

    $customGroup = CustomGroup::create(FALSE)
      ->addValue('title', 'MyFieldsToAlter')
      ->addValue('extends', 'Activity')
      ->addChain('field1', CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('label', 'TestOptions')
        ->addValue('html_type', 'Select')
        ->addValue('option_values', [
          1 => 'One',
          2 => 'Two',
          3 => 'Three',
          4 => 'Four',
        ]), 0
      )
      ->addChain('field2', CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('label', 'TestText')
        ->addValue('html_type', 'Text'), 0
      )
      ->addChain('field3', CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('serialize', TRUE)
        ->addValue('label', 'TestCountry')
        ->addValue('data_type', 'Country')
        ->addValue('html_type', 'Select'), 0
      )
      ->addChain('field4', CustomField::create()
        ->addValue('custom_group_id', '$id')
        ->addValue('serialize', TRUE)
        ->addValue('is_required', TRUE)
        ->addValue('label', 'TestContact')
        ->addValue('data_type', 'ContactReference')
        ->addValue('html_type', 'Autocomplete-Select'), 0
      )
      ->execute()
      ->first();

    Activity::save(FALSE)
      ->setDefaults(['activity_type_id' => 1, 'source_contact_id' => $contact['id']])
      ->addRecord(['subject' => 'A1', 'MyFieldsToAlter.TestText' => 'A1', 'MyFieldsToAlter.TestOptions' => '1'])
      ->addRecord(['subject' => 'A2', 'MyFieldsToAlter.TestText' => 'A2', 'MyFieldsToAlter.TestOptions' => '2'])
      ->addRecord(['subject' => 'A3', 'MyFieldsToAlter.TestText' => 'A3', 'MyFieldsToAlter.TestOptions' => ''])
      ->addRecord(['subject' => 'A4', 'MyFieldsToAlter.TestText' => 'A4', 'MyFieldsToAlter.TestCountry' => [1228, 1039]])
      ->execute();

    $result = Activity::get(FALSE)
      ->addWhere('MyFieldsToAlter.TestText', 'IS NOT NULL')
      ->addSelect('custom.*', 'subject', 'MyFieldsToAlter.TestOptions:label')
      ->execute()->indexBy('subject');

    $this->assertCount(4, $result);
    $this->assertEquals(1, $result['A1']['MyFieldsToAlter.TestOptions']);
    $this->assertEquals(2, $result['A2']['MyFieldsToAlter.TestOptions']);
    $this->assertEquals('One', $result['A1']['MyFieldsToAlter.TestOptions:label']);
    $this->assertEquals('Two', $result['A2']['MyFieldsToAlter.TestOptions:label']);
    $this->assertTrue(empty($result['A3']['MyFieldsToAlter.TestOptions']));
    $this->assertTrue(empty($result['A4']['MyFieldsToAlter.TestOptions']));

    // Change options field to multiselect
    CustomField::update(FALSE)
      ->addWhere('id', '=', $customGroup['field1']['id'])
      ->addValue('serialize', TRUE)
      ->execute();

    $result = Activity::get(FALSE)
      ->addWhere('MyFieldsToAlter.TestText', 'IS NOT NULL')
      ->addSelect('custom.*', 'subject', 'MyFieldsToAlter.TestOptions:label')
      ->execute()->indexBy('subject');

    $this->assertEquals([1], $result['A1']['MyFieldsToAlter.TestOptions']);
    $this->assertEquals([2], $result['A2']['MyFieldsToAlter.TestOptions']);
    $this->assertEquals(['One'], $result['A1']['MyFieldsToAlter.TestOptions:label']);
    $this->assertTrue(empty($result['A3']['MyFieldsToAlter.TestOptions']));
    $this->assertTrue(empty($result['A4']['MyFieldsToAlter.TestOptions']));

    // Change back to single-select
    CustomField::update(FALSE)
      ->addWhere('id', '=', $customGroup['field1']['id'])
      ->addValue('serialize', FALSE)
      ->execute();

    $result = Activity::get(FALSE)
      ->addWhere('MyFieldsToAlter.TestText', 'IS NOT NULL')
      ->addSelect('custom.*', 'subject', 'MyFieldsToAlter.TestOptions:label')
      ->execute()->indexBy('subject');

    $this->assertCount(4, $result);
    $this->assertEquals(1, $result['A1']['MyFieldsToAlter.TestOptions']);
    $this->assertEquals(2, $result['A2']['MyFieldsToAlter.TestOptions']);
    $this->assertEquals('One', $result['A1']['MyFieldsToAlter.TestOptions:label']);
    $this->assertEquals('Two', $result['A2']['MyFieldsToAlter.TestOptions:label']);
    $this->assertTrue(empty($result['A3']['MyFieldsToAlter.TestOptions']));
    $this->assertTrue(empty($result['A4']['MyFieldsToAlter.TestOptions']));

    // Change country field from multiselect to single
    CustomField::update(FALSE)
      ->addWhere('id', '=', $customGroup['field3']['id'])
      ->addValue('serialize', FALSE)
      ->execute();

    $result = Activity::get(FALSE)
      ->addWhere('MyFieldsToAlter.TestCountry', 'IS NOT NULL')
      ->addSelect('custom.*', 'subject')
      ->execute()->indexBy('subject');
    $this->assertCount(1, $result);
    // The two values originally entered will now be one value
    $this->assertEquals(1228, $result['A4']['MyFieldsToAlter.TestCountry']);

    // Change country field from single to multiselect
    CustomField::update(FALSE)
      ->addWhere('id', '=', $customGroup['field3']['id'])
      ->addValue('serialize', TRUE)
      ->execute();

    $result = Activity::get(FALSE)
      ->addWhere('MyFieldsToAlter.TestCountry', 'IS NOT NULL')
      ->addSelect('custom.*', 'subject')
      ->execute()->indexBy('subject');
    $this->assertCount(1, $result);
    // The two values originally entered will now be one value
    $this->assertEquals([1228], $result['A4']['MyFieldsToAlter.TestCountry']);

    // Repeatedly change contact ref field to ensure FK index is correctly added/dropped with no SQL error
    for ($i = 1; $i < 6; ++$i) {
      CustomField::update(FALSE)
        ->addWhere('id', '=', $customGroup['field4']['id'])
        ->addValue('serialize', $i % 2 == 0)
        ->addValue('is_required', $i % 2 == 0)
        ->execute();
    }

    // Check that custom table exists and is then removed when group is deleted
    $this->assertNotNull(\CRM_Core_DAO::singleValueQuery("SHOW TABLES LIKE '{$customGroup['table_name']}';"));

    CustomField::delete(FALSE)
      ->addWhere('custom_group_id', '=', $customGroup['id'])
      ->execute();
    CustomGroup::delete(FALSE)
      ->addWhere('id', '=', $customGroup['id'])
      ->execute();
    // The table should be gone
    $this->assertNull(\CRM_Core_DAO::singleValueQuery("SHOW TABLES LIKE '{$customGroup['table_name']}';"));
  }

}
