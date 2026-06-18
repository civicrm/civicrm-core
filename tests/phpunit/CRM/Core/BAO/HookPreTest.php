<?php

use Civi\Api4\Individual;
use Civi\Core\Event\PreEvent;

/**
 * @group headless
 */
class CRM_Core_BAO_HookPreTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;

  public function testCustomValuesWithHookPre(): void {
    $customGroupId = $this->createCustomGroup([
      'name' => 'testGroupWithHookPre',
      'extends' => 'Individual',
    ]);
    $field1Id = $this->createTextCustomField([
      'custom_group_id' => $customGroupId,
      'default_value' => NULL,
      'name' => 'field1',
    ])['id'];
    $field2Id = $this->createIntCustomField([
      'custom_group_id' => $customGroupId,
      'name' => 'field2',
      'default_value' => NULL,
    ])['id'];
    $field3Id = $this->createBooleanCustomField([
      'custom_group_id' => $customGroupId,
      'name' => 'field3',
    ])['id'];
    $field4Id = $this->createStringCheckboxCustomField([
      'custom_group_id' => $customGroupId,
      'name' => 'field4',
    ])['id'];

    Civi::dispatcher()->addListener('hook_civicrm_pre::Individual', [$this, 'customValuesWithHookPreCallback']);

    // Will invoke testCustomValuesWithHookPreCallback()
    $cid = Individual::create(FALSE)
      ->addValue('first_name', 'Mr. Wrong')
      ->addValue('testGroupWithHookPre.field1', 'wrong value')
      ->addValue('testGroupWithHookPre.field2', 123)
      ->addValue('testGroupWithHookPre.field3', FALSE)
      ->addValue('testGroupWithHookPre.field4:label', ['Lilac', 'Purple'])
      ->execute()->single()['id'];

    // Assert values have been altered by hook callback
    $contact = Individual::get(FALSE)
      ->addWhere('id', '=', $cid)
      ->addSelect('testGroupWithHookPre.*')
      ->execute()->single();

    $this->assertSame('correct value', $contact['testGroupWithHookPre.field1']);
    $this->assertSame(456, $contact['testGroupWithHookPre.field2']);
    $this->assertEquals(TRUE, $contact['testGroupWithHookPre.field3']);

    // Try with api3 using the old custom_id syntax
    $result = civicrm_api3('Contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => 'Mr. Wrong',
      "custom_$field1Id" => "wrong value",
      "custom_$field2Id" => 123,
      "custom_$field3Id" => 0,
      "custom_$field4Id" => CRM_Utils_Array::implodePadded(['L', 'P']),
    ]);
    $contact = civicrm_api3('Contact', 'getsingle', [
      'id' => $result['id'],
      'return' => "custom_$field1Id,custom_$field2Id,custom_$field3Id",
    ]);
    $this->assertEquals('correct value', $contact["custom_$field1Id"]);
    $this->assertEquals(456, $contact["custom_$field2Id"]);
    $this->assertEquals(TRUE, $contact["custom_$field3Id"]);

    // Test the weird format from CRM_Contact_Form_Edit
    $params = [
      'contact_type' => 'Individual',
      'first_name' => 'Mr. Wrong',
      "custom_{$field1Id}_-1" => "wrong value",
      "custom_{$field2Id}_-1" => 123,
      "custom_{$field3Id}_-1" => 0,
      "custom_{$field4Id}_-1" => ['L', 'P'],
    ];
    CRM_Utils_Hook::pre('create', 'Individual', NULL, $params);

    // The weird format should be preserved
    $this->assertEquals('correct value', $params["custom_{$field1Id}_-1"]);
    $this->assertEquals(456, $params["custom_{$field2Id}_-1"]);
    $this->assertEquals(TRUE, $params["custom_{$field3Id}_-1"]);
    $this->assertEquals(['M', 'V'], $params["custom_{$field4Id}_-1"]);
  }

  public function customValuesWithHookPreCallback(PreEvent $event) {
    $irrelevantParams = ['check_permissions', 'modified_date', 'version', 'skip_greeting_processing', 'testGroupWithHookPre.field4:label'];

    // getValues() should return all custom fields in longName format even if they were set in a shortName format
    $getValues = $event->getValues();
    // Ignore irrelevant params passed in from the api
    CRM_Utils_Array::remove($getValues, $irrelevantParams);
    $this->assertEquals([
      'contact_type' => 'Individual',
      'first_name' => 'Mr. Wrong',
      'testGroupWithHookPre.field1' => 'wrong value',
      'testGroupWithHookPre.field2' => 123,
      'testGroupWithHookPre.field3' => FALSE,
      'testGroupWithHookPre.field4' => ['L', 'P'],
    ], $getValues);

    $this->assertSame('Mr. Wrong', $event->getValue('first_name'));
    $this->assertSame('wrong value', $event->getValue('testGroupWithHookPre.field1'));
    $this->assertSame(123, $event->getValue('testGroupWithHookPre.field2'));
    $this->assertEquals(FALSE, $event->getValue('testGroupWithHookPre.field3'));
    $this->assertEquals(['L', 'P'], $event->getValue('testGroupWithHookPre.field4'));

    $event->mergeValues([
      'first_name' => 'Mr. Right',
      'testGroupWithHookPre.field1' => 'correct value',
      'testGroupWithHookPre.field2' => 456,
      'testGroupWithHookPre.field3' => TRUE,
      'testGroupWithHookPre.field4' => ['M', 'V'],
    ]);

    $this->assertSame('Mr. Right', $event->getValue('first_name'));
    $this->assertSame('correct value', $event->getValue('testGroupWithHookPre.field1'));
    $this->assertSame(456, $event->getValue('testGroupWithHookPre.field2'));
    $this->assertEquals(TRUE, $event->getValue('testGroupWithHookPre.field3'));
    $this->assertEquals(['M', 'V'], $event->getValue('testGroupWithHookPre.field4'));

    // Inspect the custom array
    $customData = $event->params['custom'];
    $fieldInfo = CRM_Core_BAO_CustomGroup::getGroup(['name' => 'testGroupWithHookPre'])['fields'];
    $fieldIds = array_column($fieldInfo, 'id', 'name');

    // Custom array should contain all custom field ids
    $this->assertEquals(array_values($fieldIds), array_keys($customData));
    $this->assertSame('correct value', $customData[$fieldIds['field1']][-1]['value']);
    $this->assertSame(456, $customData[$fieldIds['field2']][-1]['value']);
    $this->assertEquals(TRUE, $customData[$fieldIds['field3']][-1]['value']);
    $this->assertEquals(CRM_Utils_Array::implodePadded(['M', 'V']), $customData[$fieldIds['field4']][-1]['value']);

    // getValues() should return all custom fields in longName format as-set by the hook
    $getValues = $event->getValues();
    // Ignore irrelevant params passed in from the api
    CRM_Utils_Array::remove($getValues, $irrelevantParams);
    $this->assertEquals([
      'contact_type' => 'Individual',
      'first_name' => 'Mr. Right',
      'testGroupWithHookPre.field1' => 'correct value',
      'testGroupWithHookPre.field2' => 456,
      'testGroupWithHookPre.field3' => TRUE,
      'testGroupWithHookPre.field4' => ['M', 'V'],
    ], $getValues);
  }

}
