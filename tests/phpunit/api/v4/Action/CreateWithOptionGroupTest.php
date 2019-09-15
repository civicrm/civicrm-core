<?php

namespace api\v4\Action;

use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use Civi\Api4\Contact;

/**
 * @group headless
 */
class CreateWithOptionGroupTest extends BaseCustomValueTest {

  /**
   * Remove the custom tables
   */
  public function setUp() {
    $this->dropByPrefix('civicrm_value_financial');
    $this->dropByPrefix('civicrm_value_favorite');
    parent::setUp();
  }

  public function testGetWithCustomData() {
    $group = uniqid('fava');
    $colorField = uniqid('colora');
    $foodField = uniqid('fooda');

    $customGroupId = CustomGroup::create()
      ->setCheckPermissions(FALSE)
      ->addValue('name', $group)
      ->addValue('extends', 'Contact')
      ->execute()
      ->first()['id'];

    CustomField::create()
      ->setCheckPermissions(FALSE)
      ->addValue('label', $colorField)
      ->addValue('name', $colorField)
      ->addValue('option_values', ['r' => 'Red', 'g' => 'Green', 'b' => 'Blue'])
      ->addValue('custom_group_id', $customGroupId)
      ->addValue('html_type', 'Select')
      ->addValue('data_type', 'String')
      ->execute();

    CustomField::create()
      ->setCheckPermissions(FALSE)
      ->addValue('label', $foodField)
      ->addValue('name', $foodField)
      ->addValue('option_values', ['1' => 'Corn', '2' => 'Potatoes', '3' => 'Cheese'])
      ->addValue('custom_group_id', $customGroupId)
      ->addValue('html_type', 'Select')
      ->addValue('data_type', 'String')
      ->execute();

    $customGroupId = CustomGroup::create()
      ->setCheckPermissions(FALSE)
      ->addValue('name', 'FinancialStuff')
      ->addValue('extends', 'Contact')
      ->execute()
      ->first()['id'];

    CustomField::create()
      ->setCheckPermissions(FALSE)
      ->addValue('label', 'Salary')
      ->addValue('custom_group_id', $customGroupId)
      ->addValue('html_type', 'Number')
      ->addValue('data_type', 'Money')
      ->execute();

    Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Jerome')
      ->addValue('last_name', 'Tester')
      ->addValue('contact_type', 'Individual')
      ->addValue("$group.$colorField", 'r')
      ->addValue("$group.$foodField", '1')
      ->addValue('FinancialStuff.Salary', 50000)
      ->execute();

    $result = Contact::get()
      ->setCheckPermissions(FALSE)
      ->addSelect('first_name')
      ->addSelect("$group.$colorField.label")
      ->addSelect("$group.$foodField.label")
      ->addSelect('FinancialStuff.Salary')
      ->addWhere("$group.$foodField.label", 'IN', ['Corn', 'Potatoes'])
      ->addWhere('FinancialStuff.Salary', '>', '10000')
      ->execute()
      ->first();

    $this->assertEquals('Red', $result["$group.$colorField.label"]);
    $this->assertEquals('Corn', $result["$group.$foodField.label"]);
    $this->assertEquals(50000, $result['FinancialStuff.Salary']);
  }

  public function testWithCustomDataForMultipleContacts() {
    $group = uniqid('favb');
    $colorField = uniqid('colorb');
    $foodField = uniqid('foodb');

    $customGroupId = CustomGroup::create()
      ->setCheckPermissions(FALSE)
      ->addValue('name', $group)
      ->addValue('extends', 'Contact')
      ->execute()
      ->first()['id'];

    CustomField::create()
      ->setCheckPermissions(FALSE)
      ->addValue('label', $colorField)
      ->addValue('name', $colorField)
      ->addValue('option_values', ['r' => 'Red', 'g' => 'Green', 'b' => 'Blue'])
      ->addValue('custom_group_id', $customGroupId)
      ->addValue('html_type', 'Select')
      ->addValue('data_type', 'String')
      ->execute();

    CustomField::create()
      ->setCheckPermissions(FALSE)
      ->addValue('label', $foodField)
      ->addValue('name', $foodField)
      ->addValue('option_values', ['1' => 'Corn', '2' => 'Potatoes', '3' => 'Cheese'])
      ->addValue('custom_group_id', $customGroupId)
      ->addValue('html_type', 'Select')
      ->addValue('data_type', 'String')
      ->execute();

    $customGroupId = CustomGroup::create()
      ->setCheckPermissions(FALSE)
      ->addValue('name', 'FinancialStuff')
      ->addValue('extends', 'Contact')
      ->execute()
      ->first()['id'];

    CustomField::create()
      ->setCheckPermissions(FALSE)
      ->addValue('label', 'Salary')
      ->addValue('custom_group_id', $customGroupId)
      ->addValue('html_type', 'Number')
      ->addValue('data_type', 'Money')
      ->execute();

    Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Red')
      ->addValue('last_name', 'Corn')
      ->addValue('contact_type', 'Individual')
      ->addValue("$group.$colorField", 'r')
      ->addValue("$group.$foodField", '1')
      ->addValue('FinancialStuff.Salary', 10000)
      ->execute();

    Contact::create()
      ->setCheckPermissions(FALSE)
      ->addValue('first_name', 'Blue')
      ->addValue('last_name', 'Cheese')
      ->addValue('contact_type', 'Individual')
      ->addValue("$group.$colorField", 'b')
      ->addValue("$group.$foodField", '3')
      ->addValue('FinancialStuff.Salary', 500000)
      ->execute();

    $result = Contact::get()
      ->setCheckPermissions(FALSE)
      ->addSelect('first_name')
      ->addSelect('last_name')
      ->addSelect("$group.$colorField.label")
      ->addSelect("$group.$foodField.label")
      ->addSelect('FinancialStuff.Salary')
      ->addWhere("$group.$foodField.label", 'IN', ['Corn', 'Cheese'])
      ->execute();

    $blueCheese = NULL;
    foreach ($result as $contact) {
      if ($contact['first_name'] === 'Blue') {
        $blueCheese = $contact;
      }
    }

    $this->assertEquals('Blue', $blueCheese["$group.$colorField.label"]);
    $this->assertEquals('Cheese', $blueCheese["$group.$foodField.label"]);
    $this->assertEquals(500000, $blueCheese['FinancialStuff.Salary']);
  }

}
