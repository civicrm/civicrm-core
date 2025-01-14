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

}
