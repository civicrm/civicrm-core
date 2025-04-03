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
use Civi\Api4\CustomField;
use Civi\Api4\Utils\CoreUtil;

/**
 * @group headless
 */
class CoreUtilTest extends Api4TestBase {

  public function setUp(): void {
    \CRM_Core_BAO_ConfigSetting::enableAllComponents();
    parent::setUp();
  }

  /**
   */
  public function testGetApiNameFromTableName(): void {
    $this->assertEquals('Contact', CoreUtil::getApiNameFromTableName('civicrm_contact'));
    $this->assertNull(CoreUtil::getApiNameFromTableName('civicrm_nothing'));

    $singleGroup = $this->createTestRecord('CustomGroup', [
      'title' => uniqid(),
      'extends' => 'Contact',
    ]);

    $this->assertNull(CoreUtil::getApiNameFromTableName($singleGroup['table_name']));

    $multiGroup = $this->createTestRecord('CustomGroup', [
      'title' => uniqid(),
      'extends' => 'Contact',
      'is_multiple' => TRUE,
    ]);
    CustomField::save(FALSE)
      ->addDefault('html_type', 'Text')
      ->addDefault('custom_group_id', $multiGroup['id'])
      ->addRecord(['label' => 'MyField1'])
      ->execute();

    $this->assertEquals('Custom_' . $multiGroup['name'], CoreUtil::getApiNameFromTableName($multiGroup['table_name']));
    $this->assertEquals($multiGroup['table_name'], CoreUtil::getTableName('Custom_' . $multiGroup['name']));
  }

  public function testGetApiClass(): void {
    $this->assertEquals('Civi\Api4\Contact', CoreUtil::getApiClass('Contact'));
    $this->assertEquals('Civi\Api4\CiviCase', CoreUtil::getApiClass('Case'));
    $this->assertNull(CoreUtil::getApiClass('NothingAtAll'));

    $singleGroup = $this->createTestRecord('CustomGroup', [
      'title' => uniqid(),
      'extends' => 'Contact',
    ]);

    $this->assertNull(CoreUtil::getApiClass($singleGroup['name']));

    $multiGroup = $this->createTestRecord('CustomGroup', [
      'title' => uniqid(),
      'extends' => 'Contact',
      'is_multiple' => TRUE,
    ]);

    $this->assertEquals('Civi\Api4\CustomValue', CoreUtil::getApiClass('Custom_' . $multiGroup['name']));
  }

  public function getNamespaceExamples(): array {
    return [
      ['\Foo', 'Foo'],
      ['\Foo\Bar', 'Bar'],
      ['Baz', 'Baz'],
    ];
  }

  /**
   * @dataProvider getNamespaceExamples
   */
  public function testStripNamespace($input, $expected): void {
    $this->assertEquals($expected, CoreUtil::stripNamespace($input));
  }

  public function testGetRefCountTotal(): void {
    $fileId = $this->createTestRecord('File', [
      'mime_type' => 'text/plain',
      'file_name' => 'test123.txt',
      'content' => 'Hello 123',
    ])['id'];

    $this->assertEquals(0, CoreUtil::getRefCountTotal('File', $fileId));

    $activity = $this->createTestRecord('Activity');
    $this->createTestRecord('EntityFile', [
      'file_id' => $fileId,
      'entity_table' => 'civicrm_activity',
      'entity_id' => $activity['id'],
    ]);

    $this->assertEquals(1, CoreUtil::getRefCountTotal('File', $fileId));

    $tagId = $this->createTestRecord('Tag', [
      'used_for' => 'civicrm_file',
      'name' => 'testFileTag',
      'label' => 'testFileTag',
    ])['id'];

    $this->createTestRecord('EntityTag', [
      'entity_table' => 'civicrm_file',
      'entity_id' => $fileId,
      'tag_id' => $tagId,
    ]);

    $this->assertEquals(2, CoreUtil::getRefCountTotal('File', $fileId));

    $customGroup = $this->createTestRecord('CustomGroup', [
      'extends' => 'Activity',
      'title' => 'TestActivityFields',
    ]);
    $customField = $this->createTestRecord('CustomField', [
      'custom_group_id.name' => 'TestActivityFields',
      'label' => 'TestFileField',
      'html_type' => 'File',
      'data_type' => 'File',
    ]);

    $this->createTestRecord('Activity', [
      'TestActivityFields.TestFileField' => $fileId,
    ]);
    // For now, ensure extra EntityFile was created for the custom field
    $this->assertEquals('1', \CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM civicrm_entity_file WHERE entity_table = '{$customGroup['table_name']}'"));
    // For now, ensure extra EntityFile record gets cleaned up, because that's not handled by the custom field business logic
    $this->registerTestRecord('EntityFile', [['file_id', '=', $fileId]]);

    // File should exist in the custom value table
    $idCheck = \CRM_Core_DAO::singleValueQuery("SELECT {$customField['column_name']} FROM {$customGroup['table_name']}");
    $this->assertEquals($fileId, $idCheck);

    $this->assertEquals(3, CoreUtil::getRefCountTotal('File', $fileId));

    $activity2 = $this->createTestRecord('Activity');
    $this->createTestRecord('EntityFile', [
      'file_id' => $fileId,
      'entity_table' => 'civicrm_activity',
      'entity_id' => $activity2['id'],
    ]);

    $this->assertEquals(4, CoreUtil::getRefCountTotal('File', $fileId));
  }

}
