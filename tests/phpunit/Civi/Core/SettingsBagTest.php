<?php
namespace Civi\Core;

use Civi\Api4\Setting;

class SettingsBagTest extends \CiviUnitTestCase {

  protected $origSetting;

  public $mandates;

  protected function setUp(): void {
    $this->origSetting = $GLOBALS['civicrm_setting'];

    parent::setUp();
    $this->useTransaction(TRUE);

    $this->mandates = [];
  }

  public function tearDown(): void {
    $GLOBALS['civicrm_setting'] = $this->origSetting;
    parent::tearDown();
  }

  /**
   * CRM-19610 - Ensure InnoDb FTS doesn't break search preferenes when disabled.
   */
  public function testInnoDbFTS(): void {

    $settingsBag = \Civi::settings();

    $settingsBag->set("enable_innodb_fts", "0");
    $this->assertEquals(0, $settingsBag->get('enable_innodb_fts'));
  }

  public function testMaxFileSizeDefault(): void {
    $settingsBag = \Civi::settings();
    $defaultValue = $settingsBag->getDefault('maxFileSize');

    // Question: how is an empty value '' treated when retrieving a seting?
    Setting::set()->addValue('maxFileSize', '')->execute();

    // Get the value from settingsBag
    $value = $settingsBag->get('maxFileSize');

    // Get the value from cv
    $cvVal = exec('cv setting:get maxFileSize --out json');
    $cvVal = json_decode($cvVal, TRUE)[0]['value'];

    // Get the value from the api
    $api3Value = civicrm_api3('Setting', 'getsingle')['maxFileSize'];
    $api4Value = Setting::get()->addSelect('maxFileSize')->execute()->single()['value'];

    // They should all be the same, right?
    $this->assertSame($defaultValue, $cvVal);
    $this->assertSame($defaultValue, $value);
    $this->assertSame($defaultValue, $api3Value);
    $this->assertSame($defaultValue, $api4Value);

  }

}
