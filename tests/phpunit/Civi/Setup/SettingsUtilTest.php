<?php
namespace Civi\Setup;

/**
 * Class SettingsUtilTest
 * @package Civi\Setup
 * @group headless
 */
class SettingsUtilTest extends \CiviUnitTestCase {

  /**
   * Credentials must survive the trip through civicrm.settings.php.
   *
   * SettingsUtil writes them percent-encoded into the CIVICRM_DSN literal, and
   * PEAR::DB reads them back with rawurldecode(), so whatever went in has to
   * come back out unchanged.
   *
   * @dataProvider credentialProvider
   * @param string $username
   * @param string $password
   * @param string $database
   */
  public function testCredentialsSurviveSettingsFile(string $username, string $password, string $database) {
    $model = $this->makeModel($username, $password, $database);
    $params = SettingsUtil::createParams($model);
    $settings = SettingsUtil::evaluate($this->getTemplatePath(), $params);

    // Anchored to the start of a line so this does not match the example DSN
    // in the template's own doc-comment.
    $this->assertSame(1, preg_match("/^\s*define\('CIVICRM_DSN', '(.*)'\);/m", $settings, $matches),
      'Could not find the CIVICRM_DSN definition in the generated settings file.');
    $parsed = \DB::parseDSN(stripslashes($matches[1]));

    $this->assertSame($username, $parsed['username']);
    $this->assertSame($password, $parsed['password']);
    $this->assertSame($database, $parsed['database']);
    $this->assertSame('db.example.org', $parsed['hostspec']);
    $this->assertSame('3306', $parsed['port']);
  }

  /**
   * Data provider for testCredentialsSurviveSettingsFile.
   *
   * Excludes quotes and backslashes, which are the separate concern of the
   * addslashes() applied on top.
   *
   * @return array
   */
  public static function credentialProvider():array {
    return [
      'plain' => ['civicrm', 'secret', 'civicrm'],
      'hash' => ['civicrm', 'pa#ss', 'civicrm'],
      'slash' => ['civicrm', 'pa/ss', 'civicrm'],
      'question mark' => ['civicrm', 'pa?ss', 'civicrm'],
      'at sign' => ['civicrm', 'pa@ss', 'civicrm'],
      'colon' => ['civicrm', 'pa:ss', 'civicrm'],
      'percent' => ['civicrm', 'pa%ss', 'civicrm'],
      'ampersand' => ['civicrm', 'pa&ss', 'civicrm'],
      'space' => ['civicrm', 'pa ss', 'civicrm'],
      'plus' => ['civicrm', 'pa+ss', 'civicrm'],
      'space and plus' => ['civicrm', 'pa +ss', 'civicrm'],
      'special username' => ['user name', 'secret', 'civicrm'],
      'special database' => ['civicrm', 'secret', 'db name'],
      'empty password' => ['civicrm', '', 'civicrm'],
    ];
  }

  /**
   * A space must be written as %20, never as '+'.
   *
   * PEAR::DB reads the DSN with rawurldecode(), which would take a '+'
   * literally.
   */
  public function testSpaceIsEncodedAsPercent20() {
    $params = SettingsUtil::createParams($this->makeModel('civicrm', 'pa ss', 'civicrm'));
    $this->assertSame('pa%20ss', $params['dbPass']);
  }

  /**
   * @return string
   */
  private function getTemplatePath(): string {
    return dirname(__DIR__, 4) . '/templates/CRM/common/civicrm.settings.php.template';
  }

  /**
   * @param string $username
   * @param string $password
   * @param string $database
   * @return \Civi\Setup\Model
   */
  private function makeModel(string $username, string $password, string $database): Model {
    $db = [
      'server' => 'db.example.org:3306',
      'username' => $username,
      'password' => $password,
      'database' => $database,
      'ssl_params' => [],
    ];
    $model = new Model();
    $model->srcPath = dirname(__DIR__, 4);
    $model->templateCompilePath = '/tmp/templates_c';
    $model->cmsBaseUrl = 'http://example.org/';
    $model->cms = 'Standalone';
    $model->db = $db;
    $model->cmsDb = $db;
    $model->siteKey = 'abcd1234ABCD9876';
    $model->credKeys = ['::abcd1234ABCD9876'];
    $model->signKeys = ['jwt-hs256::abcd1234ABCD9876'];
    $model->deployID = '1234ABCD9876';
    $model->paths = [];
    $model->mandatorySettings = [];
    return $model;
  }

}
