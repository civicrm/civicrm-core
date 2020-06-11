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

/**
 * Class CRM_Core_BAO_CustomValueTest
 * @group headless
 */
class CRM_Core_BAO_CustomValueTest extends CiviUnitTestCase {

  public function testTypeCheckWithValidInput() {

    $values = [
      'Memo' => 'Test1',
      'String' => 'Test',
      'Int' => 1,
      'Float' => 10.00,
      'Date' => '2008-06-24',
      'Boolean' => TRUE,
      'StateProvince' => 'California',
      'Country' => 'US',
      'Link' => 'http://civicrm.org',
    ];
    foreach ($values as $type => $value) {
      $valid = CRM_Core_BAO_CustomValue::typecheck($type, $value);
      if ($type == 'Date') {
        $this->assertEquals($valid, '2008-06-24', 'Checking type ' . $type . ' for returned CustomField Type.');
      }
      else {
        $this->assertEquals($valid, TRUE, 'Checking type ' . $type . ' for returned CustomField Type.');
      }
    }
  }

  public function testTypeCheckWithInvalidInput() {
    $values = ['check1' => 'chk'];
    foreach ($values as $type => $value) {
      $valid = CRM_Core_BAO_CustomValue::typecheck($type, $value);
      $this->assertEquals($valid, NULL, 'Checking invalid type for returned CustomField Type.');
    }
  }

  public function testTypeCheckWithWrongInput() {
    $values = [
      'String' => 1,
      'Boolean' => 'US',
    ];
    foreach ($values as $type => $value) {
      $valid = CRM_Core_BAO_CustomValue::typecheck($type, $value);
      $this->assertEquals($valid, NULL, 'Checking type ' . $type . ' for returned CustomField Type.');
    }
  }

  public function testTypeToFieldWithValidInput() {
    $values = [
      'String' => 'char_data',
      'File' => 'char_data',
      'Boolean' => 'int_data',
      'Int' => 'int_data',
      'StateProvince' => 'int_data',
      'Country' => 'int_data',
      'Float' => 'float_data',
      'Memo' => 'memo_data',
      'Money' => 'decimal_data',
      'Date' => 'date_data',
      'Link' => 'char_data',
    ];

    foreach ($values as $type => $value) {
      $valid = CRM_Core_BAO_CustomValue::typeToField($type);
      $this->assertEquals($valid, $value, 'Checking type ' . $type . ' for returned CustomField Type.');
    }
  }

  public function testTypeToFieldWithWrongInput() {
    $values = [
      'String' => 'memo_data',
      'File' => 'date_data',
      'Boolean' => 'char_data',
    ];
    foreach ($values as $type => $value) {
      $valid = CRM_Core_BAO_CustomValue::typeToField($type);
      $this->assertNotEquals($valid, $value, 'Checking type ' . $type . ' for returned CustomField Type.');
    }
  }

  public function testFixCustomFieldValue() {
    $customGroup = $this->customGroupCreate(['extends' => 'Individual']);
    $params = [
      'email' => 'abc@example.com',
    ];

    foreach ([
      [
        'custom_group_id' => $customGroup['id'],
        'data_type' => 'Memo',
        'html_type' => 'TextArea',
        'default_value' => '',
        'search_value' => '%note%',
        'expected_value' => ['LIKE' => '%note%'],
      ],
      [
        'custom_group_id' => $customGroup['id'],
        'data_type' => 'String',
        'html_type' => 'Autocomplete-Select',
        'default_value' => '',
        'search_value' => 'R,Y',
        'expected_value' => ['IN' => ['R', 'Y']],
        'option_values' => [
          [
            'label' => 'Red',
            'value' => 'R',
            'weight' => 1,
            'is_active' => 1,
          ],
          [
            'label' => 'Yellow',
            'value' => 'Y',
            'weight' => 2,
            'is_active' => 1,
          ],
          [
            'label' => 'Green',
            'value' => 'G',
            'weight' => 3,
            'is_active' => 1,
          ],
        ],
      ],
    ] as $field) {
      $id = $this->customFieldCreate($field)['id'];
      $customKey = 'custom_' . $id;
      $params[$customKey] = $field['search_value'];
      CRM_Core_BAO_CustomValue::fixCustomFieldValue($params);
      $this->assertEquals($params[$customKey], $field['expected_value'], 'Checking the returned value of type ' . $field['data_type']);

      // delete created custom field
      $this->customFieldDelete($id);
    }

    $this->customGroupDelete($customGroup['id']);
  }

  public function testFixCustomFieldValueWithEmptyParams() {
    $params = [];
    $result = CRM_Core_BAO_CustomValue::fixCustomFieldValue($params);
    $this->assertEquals($result, NULL, 'Checking the returned value of type Memo.');
  }

}
