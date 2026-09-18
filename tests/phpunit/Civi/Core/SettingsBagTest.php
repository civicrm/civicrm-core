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

  /**
   * A DSN composed from the CIVICRM_DB_* environment variables must survive
   * being read back by DB::parseDSN(), which decodes with rawurldecode().
   *
   * @dataProvider dsnComponentProvider
   * @param string $username
   * @param string $password
   * @param string $database
   */
  public function testInterpolateDsnRoundTrip(string $username, string $password, string $database): void {
    $bag = new SettingsBag(0, NULL);
    $bag->loadDefaults([
      'civicrm_db_host' => 'db.example.org',
      'civicrm_db_port' => 3306,
      'civicrm_db_name' => $database,
      'civicrm_db_user' => $username,
      'civicrm_db_password' => $password,
    ]);
    $bag->loadMandatory([]);

    $parsed = \DB::parseDSN($bag->get('civicrm_db_dsn'));

    $this->assertSame($username, $parsed['username']);
    $this->assertSame($password, $parsed['password']);
    $this->assertSame($database, $parsed['database']);
    $this->assertSame('db.example.org', $parsed['hostspec']);
  }

  /**
   * Data provider for testInterpolateDsnRoundTrip.
   * @return array
   */
  public static function dsnComponentProvider(): array {
    return [
      'plain' => ['civicrm', 'secret', 'civicrm'],
      'hash' => ['civicrm', 'pa#ss', 'civicrm'],
      'slash' => ['civicrm', 'pa/ss', 'civicrm'],
      'at sign' => ['civicrm', 'pa@ss', 'civicrm'],
      'percent' => ['civicrm', 'pa%ss', 'civicrm'],
      'space' => ['civicrm', 'pa ss', 'civicrm'],
      'plus' => ['civicrm', 'pa+ss', 'civicrm'],
      'special database' => ['civicrm', 'secret', 'db name'],
    ];
  }

}
