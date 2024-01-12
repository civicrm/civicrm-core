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

use Civi\Api4\CustomGroup;
use Civi\Api4\UFGroup;

/**
 * @group headless
 */
class CustomProfileFieldTest extends CustomTestBase {

  public function testExportProfileWithCustomFields(): void {
    $customGroup = CustomGroup::create(FALSE)
      ->addValue('title', 'ProfileGroup')
      ->addValue('extends', 'Individual')
      ->execute()
      ->first();

    $custom1 = $this->createTestRecord('CustomField', [
      'label' => 'F1',
      'custom_group_id' => $customGroup['id'],
    ]);
    $custom2 = $this->createTestRecord('CustomField', [
      'label' => 'F2',
      'custom_group_id' => $customGroup['id'],
    ]);

    $profile = $this->createTestRecord('UFGroup');
    $field0 = $this->createTestRecord('UFField', [
      'uf_group_id' => $profile['id'],
      'field_name' => 'first_name',
    ]);
    $field1 = $this->createTestRecord('UFField', [
      'uf_group_id' => $profile['id'],
      'field_name' => 'custom_' . $custom1['id'],
    ]);
    $field2 = $this->createTestRecord('UFField', [
      'uf_group_id' => $profile['id'],
      'field_name:name' => 'ProfileGroup.F2',
    ]);

    $export = UFGroup::export(FALSE)
      ->setId($profile['id'])
      ->execute();

    $this->assertCount(4, $export);

    $this->assertEquals('UFGroup', $export[0]['entity']);
    // First Name field should not use pseudoconstant
    $this->assertEquals('first_name', $export[1]['params']['values']['field_name']);
    $this->assertArrayNotHasKey('field_name:name', $export[1]['params']['values']);
    $this->assertEquals('First Name', $export[1]['params']['values']['label']);

    // Custom fields should use pseudoconstants
    $this->assertEquals('ProfileGroup.F1', $export[2]['params']['values']['field_name:name']);
    $this->assertArrayNotHasKey('field_name', $export[2]['params']['values']);
    $this->assertEquals('F1', $export[2]['params']['values']['label']);

    // Custom fields should use pseudoconstants
    $this->assertEquals('ProfileGroup.F2', $export[3]['params']['values']['field_name:name']);
    $this->assertArrayNotHasKey('field_name', $export[3]['params']['values']);
    $this->assertEquals('F2', $export[3]['params']['values']['label']);
  }

}
