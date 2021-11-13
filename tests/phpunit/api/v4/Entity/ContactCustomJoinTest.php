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


namespace api\v4\Entity;

use Civi\Api4\Contact;
use Civi\Api4\CustomGroup;
use Civi\Api4\CustomField;
use api\v4\Action\BaseCustomValueTest;

/**
 * @group headless
 */
class ContactCustomJoinTest extends BaseCustomValueTest {

  /**
   * Add test to ensure that in the very unusual and not really supported situation where there is a space in the
   * custom group machine name. This is not supported but has been seen in the wild and as such we have this test to lock in the fix for dev/mail#103
   */
  public function testContactCustomJoin() {
    $customGroup = CustomGroup::create()->setValues([
      'name' => 'D - Identification_20',
      'table_name' => 'civicrm_value_demographics',
      'title' => 'D - Identification',
      'extends' => 'Individual',
    ])->execute();
    \CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_group SET name = 'D - Identification_20' WHERE id = %1", [1 => [$customGroup[0]['id'], 'Integer']]);
    $customField = CustomField::create()->setValues([
      'label' => 'Test field',
      'custom_group_id' => $customGroup[0]['id'],
      'html_type' => 'Text',
      'data_type' => 'String',
    ])->execute();
    Contact::get()->addSelect('*')->addSelect('custom.*')->execute();
  }

}
