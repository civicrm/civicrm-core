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
    // Setting::set()->addValue('maxFileSize', '')->execute();
    $settingsBag->set('maxFileSize', '');

    // Get the value from settingsBag
    $bagGet = $settingsBag->get('maxFileSize');

    // Get the value from cv setting:get
    $exec = exec('cv setting:get maxFileSize --out json');
    $cvGetCmd = json_decode($exec, TRUE)[0]['value'];

    // Get the value from cv php:eval
    $exec = exec('cv ev \'return Civi::settings()->get("maxFileSize");\' --out json');
    $cvEvalCmd = json_decode($exec, TRUE);

    // Get the value from the api
    $api3Value = civicrm_api3('Setting', 'getsingle')['maxFileSize'];
    $api4Value = Setting::get()->addSelect('maxFileSize')->execute()->single()['value'];

    // Do we get the same result if we re-hydrate the cache?
    \CRM_Core_Config::singleton(TRUE, TRUE);
    $bagGetRedux = \Civi::settings()->get('maxFileSize');

    // They should all be the same, right?
    $report = json_encode([
      'bagGet' => $bagGet,
      'cvGetCmd' => $cvGetCmd,
      'cvEvalCmd' => $cvEvalCmd,
      'api3Value' => $api3Value,
      'api4Value' => $api4Value,
      'bagGetRedux' => $bagGetRedux,
    ], JSON_PRETTY_PRINT);
    $this->assertSame($defaultValue, $cvGetCmd, "Check value consistency. Report: $report");
    $this->assertSame($defaultValue, $cvEvalCmd, "Check value consistency. Report: $report");
    $this->assertSame($defaultValue, $bagGet, "Check value consistency. Report: $report");
    $this->assertSame($defaultValue, (int) $api3Value, "Check value consistency. Report: $report");
    $this->assertSame($defaultValue, $api4Value, "Check value consistency. Report: $report");
    $this->assertSame($defaultValue, $bagGetRedux, "Check value consistency. Report: $report");
  }

}
