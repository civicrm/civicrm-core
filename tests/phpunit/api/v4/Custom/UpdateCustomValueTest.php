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
use Civi\Api4\CustomField;
use Civi\Api4\CustomGroup;
use CRM_Core_BAO_CustomValueTable as CustomValueTable;

/**
 * @group headless
 */
class UpdateCustomValueTest extends CustomTestBase {

  public function testGetWithCustomData(): void {

    $customGroup = CustomGroup::create(FALSE)
      ->addValue('title', 'MyContactFields')
      ->addValue('extends', 'Contact')
      ->execute()
      ->first();

    CustomField::create(FALSE)
      ->addValue('label', 'FavColor')
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'Text')
      ->addValue('data_type', 'String')
      ->execute();

    $contactId = $this->createTestRecord('Contact', [
      'first_name' => 'Red',
      'last_name' => 'Tester',
      'contact_type' => 'Individual',
      'MyContactFields.FavColor' => 'Red',
    ])['id'];

    Contact::update(FALSE)
      ->addWhere('id', '=', $contactId)
      ->addValue('first_name', 'Red')
      ->addValue('last_name', 'Tester')
      ->addValue('contact_type', 'Individual')
      ->addValue('MyContactFields.FavColor', 'Blue')
      ->execute();

    $result = CustomValueTable::getEntityValues($contactId, 'Contact');

    $this->assertEquals(1, count($result));
    $this->assertContains('Blue', $result);
  }

}
