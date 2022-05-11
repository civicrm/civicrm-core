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

use api\v4\Api4TestBase;
use Civi\Api4\Relationship;

/**
 * @group headless
 */
class CaseTest extends Api4TestBase {

  public function setUp(): void {
    parent::setUp();
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
  }

  public function testCreateUsingLoggedInUser() {
    $uid = $this->createLoggedInUser();

    $contactID = $this->createTestRecord('Contact')['id'];

    $case = $this->createTestRecord('Case', [
      'creator_id' => 'user_contact_id',
      'contact_id' => $contactID,
    ]);

    $relationships = Relationship::get(FALSE)
      ->addWhere('case_id', '=', $case['id'])
      ->execute();

    $this->assertCount(1, $relationships);
    $this->assertEquals($uid, $relationships[0]['contact_id_b']);
    $this->assertEquals($contactID, $relationships[0]['contact_id_a']);
  }

  public function testCgExtendsObjects() {
    $this->createTestRecord('CaseType', [
      'title' => 'Test Case Type',
      'name' => 'test_case_type1',
    ]);

    $field = \Civi\Api4\CustomGroup::getFields(FALSE)
      ->setLoadOptions(TRUE)
      ->addValue('extends', 'Case')
      ->addWhere('name', '=', 'extends_entity_column_value')
      ->execute()
      ->first();

    $this->assertContains('Test Case Type', $field['options']);
  }

}
