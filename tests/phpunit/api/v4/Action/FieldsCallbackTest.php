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


namespace Civi\tests\phpunit\api\v4\Action;

use api\v4\Api4TestBase;
use Civi\Api4\Email;

/**
 * @group headless
 */
class FieldsCallbackTest extends Api4TestBase {

  public function setUp(): void {
    // hook_civicrm_entityTypes has special significance in system boot. This seems to be more reliable way to register it.
    \CRM_Utils_Hook::singleton()->setHook('civicrm_entityTypes', [$this, 'hook_civicrm_entityTypes']);
    \CRM_Core_DAO_AllCoreTables::flush();
    parent::setUp();
  }

  public function tearDown(): void {
    \CRM_Utils_Hook::singleton()->reset();
    \CRM_Core_DAO_AllCoreTables::flush();
    parent::tearDown();
  }

  public function testFieldsCallback(): void {
    $getFields = Email::getFields(FALSE)
      ->setAction('get')
      ->execute()->indexBy('name');
    $this->assertGreaterThan(1, $getFields->count());
    // Check new field
    $extraField = $getFields['test_extra_field'];
    $this->assertEquals('Test Extra Field Label', $extraField['label']);
    $this->assertEquals('Test Extra Field Title', $extraField['title']);
    $this->assertEquals('Text', $extraField['input_type']);
    $this->assertEquals(['import'], $extraField['usage']);
    $this->assertTrue($extraField['readonly']);
    $this->assertFalse($extraField['required']);
    // Check modified fields
    $this->assertEquals('Test ID Title', $getFields['id']['title']);
    $this->assertTrue($getFields['email']['readonly']);
  }

  /**
   * @implements CRM_Utils_Hook::entityTypes()
   */
  public function hook_civicrm_entityTypes(&$entityTypes) {
    $entityTypes['Email']['fields_callback'][] = function ($class, &$fields) {
      // Test adding a new field
      $fields['test_extra_field'] = [
        'name' => 'test_extra_field',
        'type' => \CRM_Utils_Type::T_STRING,
        'title' => 'Test Extra Field Title',
        'usage' => ['import' => TRUE, 'tokens' => FALSE],
        'readonly' => TRUE,
        'html' => [
          'type' => 'Text',
          'label' => 'Test Extra Field Label',
        ],
      ];
      // Test modifying some fields
      $fields['id']['title'] = 'Test ID Title';
      $fields['email']['readonly'] = TRUE;
    };
  }

}
