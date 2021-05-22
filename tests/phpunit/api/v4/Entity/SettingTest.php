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

use Civi\Api4\Setting;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class SettingTest extends UnitTestCase {

  public function testSettingASetting() {
    $setting = Setting::set()->addValue('menubar_position', 'above-crm-container')->setCheckPermissions(FALSE)->execute()->first();
    $this->assertEquals('above-crm-container', $setting['value']);
    $setting = Setting::get()->addSelect('menubar_position')->setCheckPermissions(FALSE)->execute()->first();
    $this->assertEquals('above-crm-container', $setting['value']);

    $setting = Setting::revert()->addSelect('menubar_position')->setCheckPermissions(FALSE)->execute()->indexBy('name')->column('value');
    $this->assertEquals(['menubar_position' => 'over-cms-menu'], $setting);
    $setting = civicrm_api4('Setting', 'get', ['select' => ['menubar_position'], 'checkPermissions' => FALSE], 0);
    $this->assertEquals('over-cms-menu', $setting['value']);
  }

  public function testInvalidSetting() {
    $message = '';
    try {
      Setting::set()->addValue('not_a_real_setting!', 'hello')->setCheckPermissions(FALSE)->execute();
    }
    catch (\API_Exception $e) {
      $message = $e->getMessage();
    }
    $this->assertStringContainsString('setting', $message);
  }

  public function testSerailizedSetting() {
    $set = \Civi\Api4\Setting::set(FALSE)
      ->addValue('contact_edit_options:name', [
        'CommunicationPreferences',
        'CustomData',
      ])
      ->execute();

    $setting = \Civi\Api4\Setting::get(FALSE)
      ->addSelect('contact_edit_options')
      ->execute()->first();
    $this->assertTrue(is_array($setting['value']));
    $this->assertTrue(is_numeric($setting['value'][0]));

    $setting = \Civi\Api4\Setting::get(FALSE)
      ->addSelect('contact_edit_options:label')
      ->execute()->first();
    $this->assertEquals(['Communication Preferences', 'Custom Data'], $setting['value']);
    $this->assertEquals('contact_edit_options:label', $setting['name']);
  }

  /**
   * Ensure settings work with the "index" mode.
   */
  public function testSettingsWithIndexParam() {
    $settings = civicrm_api4('Setting', 'get', [], ['name' => 'value']);
    $stringValues = FALSE;
    $arrayValues = FALSE;
    // With indexing by [name => value], keys should be string and values should be string/array
    foreach ($settings as $name => $value) {
      $this->assertTrue(is_string($name) && !is_numeric($name));
      if (is_string($value)) {
        $stringValues = TRUE;
      }
      elseif (is_array($value)) {
        $arrayValues = TRUE;
      }
    }
    $this->assertTrue($stringValues);
    $this->assertTrue($arrayValues);
  }

  /**
   * Make sure options load from getFields.
   */
  public function testSettingGetFieldsOptions() {
    $setting = civicrm_api4('Setting', 'getFields', [
      'select' => ['options'],
      'loadOptions' => FALSE,
    ], 'name');
    $this->assertTrue($setting['contact_edit_options']['options']);

    $setting = civicrm_api4('Setting', 'getFields', [
      'select' => ['options'],
      'where' => [['name', '=', 'contact_edit_options']],
      'loadOptions' => TRUE,
    ], 'name');
    $this->assertContains('Custom Data', $setting['contact_edit_options']['options']);

    $setting = civicrm_api4('Setting', 'getFields', [
      'select' => ['options'],
      'loadOptions' => ['id', 'name', 'label'],
    ], 'name');
    $this->assertTrue(is_array($setting['contact_edit_options']['options'][0]));
  }

  /**
   * Ensure settings default values unserialize.
   */
  public function testSettingUnserializeDefaults() {
    $setting = civicrm_api4('Setting', 'getFields', ['where' => [['name', '=', 'contact_view_options']]], 0);
    $this->assertTrue(is_array($setting['default']));
  }

}
