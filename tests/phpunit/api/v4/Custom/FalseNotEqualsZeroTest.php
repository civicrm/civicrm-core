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
use Civi\Api4\CustomField;

/**
 * @group headless
 */
class FalseNotEqualsZeroTest extends Api4TestBase {

  public function testFalseNotEqualsZero(): void {

    $customGroup = $this->createTestRecord('CustomGroup', [
      'title' => 'MyContactFields',
      'extends' => 'Contact',
    ]);

    CustomField::create(FALSE)
      ->addValue('label', 'Lightswitch')
      ->addValue('custom_group_id', $customGroup['id'])
      ->addValue('html_type', 'Radio')
      ->addValue('data_type', 'Boolean')
      ->execute();

    $contactId = $this->createTestRecord('Contact', [
      'first_name' => 'Red',
      'last_name' => 'Tester',
      'contact_type' => 'Individual',
      'MyContactFields.Lightswitch' => FALSE,
    ])['id'];

    $result = Contact::get($contactId, 'Contact')
      ->addSelect('MyContactFields.Lightswitch')
      ->addWhere('id', '=', $contactId)
      ->execute()
      ->first()['MyContactFields.Lightswitch'];

    $this->assertNotNull($result);
  }

}
