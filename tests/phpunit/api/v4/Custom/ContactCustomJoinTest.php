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
use Civi\Api4\CustomGroup;
use Civi\Api4\CustomField;

/**
 * @group headless
 */
class ContactCustomJoinTest extends CustomTestBase {

  /**
   * Add test to ensure that in the very unusual and not really supported situation where there is a space in the
   * custom group machine name. This is not supported but has been seen in the wild and as such we have this test to lock in the fix for dev/mail#103
   *
   * @throws \CRM_Core_Exception
   */
  public function testContactCustomJoin(): void {
    $customGroup = CustomGroup::create()->setValues([
      'name' => 'D - Identification_20',
      'table_name' => 'civicrm_value_demographics',
      'title' => 'D - Identification',
      'extends' => 'Individual',
    ])->execute();
    CustomGroup::create()->setValues([
      'name' => 'other',
      'title' => 'other',
      'extends' => 'Individual',
    ])->execute();
    \CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_group SET name = 'D - Identification_20' WHERE id = %1", [1 => [$customGroup[0]['id'], 'Integer']]);
    $customField = CustomField::create()->setValues([
      'label' => 'Test field',
      'name' => 'test field',
      'custom_group_id' => $customGroup[0]['id'],
      'html_type' => 'Text',
      'data_type' => 'String',
    ])->execute();
    \CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_field SET name = 'D - Identification_20' WHERE id = %1", [1 => [$customField[0]['id'], 'Integer']]);
    CustomField::create()->setValues([
      'label' => 'other',
      'name' => 'other',
      'custom_group_id:name' => 'other',
      'html_type' => 'Text',
      'data_type' => 'String',
    ])->execute();
    $contactID = Contact::create()->setValues([
      'contact_type' => 'Individual',
      'first_name' => 'Ben',
      'other.other' => 'other',
      'D - Identification_20.D - Identification_20' => 10,
    ])->execute()->first()['id'];
    $this->assertEquals(10, Contact::get()->addSelect('*')
      ->addSelect('D - Identification_20.D - Identification_20')
      ->addWhere('id', '=', $contactID)
      ->execute()->first()['D - Identification_20.D - Identification_20']);

    $this->assertEquals(10, Contact::get()
      ->addSelect('*')
      ->addSelect('custom.*')
      ->addWhere('id', '=', $contactID)
      ->execute()->first()['D - Identification_20.D - Identification_20']
    );
  }

}
