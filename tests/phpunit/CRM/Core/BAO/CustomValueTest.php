<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Class CRM_Core_BAO_CustomValueTest
 * @group headless
 */
class CRM_Core_BAO_CustomValueTest extends CiviUnitTestCase {

  public function testTypeCheckWithValidInput() {

    $values = array(
      'Memo' => 'Test1',
      'String' => 'Test',
      'Int' => 1,
      'Float' => 10.00,
      'Date' => '2008-06-24',
      'Boolean' => TRUE,
      'StateProvince' => 'California',
      'Country' => 'US',
      'Link' => 'http://civicrm.org',
    );
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
    $values = array('check1' => 'chk');
    foreach ($values as $type => $value) {
      $valid = CRM_Core_BAO_CustomValue::typecheck($type, $value);
      $this->assertEquals($valid, NULL, 'Checking invalid type for returned CustomField Type.');
    }
  }

  public function testTypeCheckWithWrongInput() {
    $values = array(
      'String' => 1,
      'Boolean' => 'US',
    );
    foreach ($values as $type => $value) {
      $valid = CRM_Core_BAO_CustomValue::typecheck($type, $value);
      $this->assertEquals($valid, NULL, 'Checking type ' . $type . ' for returned CustomField Type.');
    }
  }

  public function testTypeToFieldWithValidInput() {
    $values = array(
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
    );

    foreach ($values as $type => $value) {
      $valid = CRM_Core_BAO_CustomValue::typeToField($type);
      $this->assertEquals($valid, $value, 'Checking type ' . $type . ' for returned CustomField Type.');
    }
  }

  public function testTypeToFieldWithWrongInput() {
    $values = array(
      'String' => 'memo_data',
      'File' => 'date_data',
      'Boolean' => 'char_data',
    );
    foreach ($values as $type => $value) {
      $valid = CRM_Core_BAO_CustomValue::typeToField($type);
      $this->assertNotEquals($valid, $value, 'Checking type ' . $type . ' for returned CustomField Type.');
    }
  }

  public function fixCustomFieldValue() {
    $customGroup = $this->customGroupCreate(array('extends' => 'Individual'));

    $fields = array(
      'custom_group_id' => $customGroup['id'],
      'data_type' => 'Memo',
      'html_type' => 'TextArea',
      'default_value' => '',
    );

    $customField = $this->customFieldCreate($fields);

    $custom = 'custom_' . $customField['id'];
    $params = array(
      'email' => 'abc@webaccess.co.in',
      $custom => 'note',
    );

    CRM_Core_BAO_CustomValue::fixCustomFieldValue($params);
    $this->assertEquals($params[$custom], '%note%', 'Checking the returned value of type Memo.');

    $this->customFieldDelete($customField['id']);
    $this->customGroupDelete($customGroup['id']);
  }

  public function testFixCustomFieldValueWithEmptyParams() {
    $params = array();
    $result = CRM_Core_BAO_CustomValue::fixCustomFieldValue($params);
    $this->assertEquals($result, NULL, 'Checking the returned value of type Memo.');
  }

}
