<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2020                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Check that various CLI runners are able to bootstrap an environment with
 * reasonable paths.
 *
 * This test assumes that you have built with "civibuild" (or configured cv.json)
 * to provide canonical/expected values.
 *
 * It then executes the any supported CLI tools (cv, drush, wp) and checks if
 * their output matches the expected value.
 *
 * @group e2e
 */
class E2E_Extern_CliRunnerTest extends CiviEndToEndTestCase {

  protected function setUp() {
    parent::setUp();

    foreach (['CIVI_CORE', 'CMS_ROOT', 'CMS_URL'] as $var) {
      if (empty($GLOBALS['_CV'][$var])) {
        $this->markTestSkipped("Test environment does provide the civibuild/cv variable ($var)");
      }
    }
  }

  protected function tearDown() {
    parent::tearDown();
  }

  /**
   * @return array
   *   Each case gives a name (eg "cv") and template for executing the command
   *   (eg "cv ev @PHP").
   */
  public function getRunners() {
    $cliRunners = [];

    if (CIVICRM_UF === 'WordPress') {
      $cliRunners['wp'] = ['wp', 'wp eval \'civicrm_initialize();\'@PHP'];
    }
    if (CIVICRM_UF === 'Drupal' || CIVICRM_UF === 'Backdrop') {
      $cliRunners['drush'] = ['drush', 'drush ev \'civicrm_initialize();\'@PHP'];
    }
    // TODO: Drupal8 w/drush (doesn't use civicrm_initialize?)

    $cliRunners['cv'] = ['cv', 'cv ev @PHP'];

    return $cliRunners;
  }

  public function getRunnersAndPaths() {
    $exs = [];
    foreach ($this->getRunners() as $runner) {
      $exs[] = array_merge($runner, ['[civicrm.root]/css/civicrm.css']);
      $exs[] = array_merge($runner, ['[civicrm.packages]/jquery/css/images/arrow.png']);
    }
    return $exs;
  }

  /**
   * @param string $name
   *   The name of the command we're testing with.
   *   Ex: 'cv'
   * @param string $r
   *   Ex: 'cv ev @PHP'
   * @dataProvider getRunners
   */
  public function testBasicPathUrl($name, $r) {
    $this->assertNotEmpty($this->findCommand($name), 'The command "$name" does not appear in the PATH.');

    $cv = $GLOBALS['_CV'];
    $this->assertEquals($cv['CIVI_CORE'], $this->callRunnerJson($r, '$GLOBALS[\'civicrm_root\']'));
    $this->assertEquals($cv['CMS_URL'] . 'foo', $this->callRunnerJson($r, 'Civi::paths()->getUrl(\'[cms.root]/foo\')'));
    $this->assertEquals($cv['CMS_ROOT'] . 'foo', $this->callRunnerJson($r, 'Civi::paths()->getPath(\'[cms.root]/foo\')'));
    $this->assertEquals($cv['CIVI_CORE'] . 'css/civicrm.css', $this->callRunnerJson($r, 'Civi::paths()->getPath(\'[civicrm.root]/css/civicrm.css\')'));

    $ufrUrl = $this->callRunnerJson($r, 'CRM_Core_Config::singleton()->userFrameworkResourceURL');
    $crmUrl = $this->callRunnerJson($r, 'Civi::paths()->getUrl("[civicrm.root]/.")');
    $this->assertEquals(rtrim($crmUrl, '/'), rtrim($ufrUrl, '/'));
  }

  /**
   * For some URLs, we don't have a good environment variable for predicting the URL.
   * Instead, we'll just see if the generated URLs match the generated paths.
   *
   * @param string $name
   *   The name of the command we're testing with.
   *   Ex: 'cv'
   * @param string $r
   *   Ex: 'cv ev @PHP'
   * @param string $fileExpr
   *   Ex: '[civicrm.root]/LICENSE'
   * @dataProvider getRunnersAndPaths
   */
  public function testPathUrlMatch($name, $r, $fileExpr) {
    $this->assertNotEmpty($this->findCommand($name), 'The command "$name" does not appear in the PATH.');
    $localPath = $this->callRunnerJson($r, "Civi::paths()->getPath('$fileExpr')");
    $remoteUrl = $this->callRunnerJson($r, "Civi::paths()->getUrl('$fileExpr')");
    $this->assertFileExists($localPath);
    $localContent = file_get_contents($localPath);
    $this->assertNotEmpty($localContent);
    $this->assertEquals($localContent, file_get_contents($remoteUrl),
      "civicrm.css should yield same content via local path ($localPath) or HTTP URL ($remoteUrl)"
    );
  }

  /**
   * @param string $runner
   *   Ex: 'cv ev @PHP'
   * @param string $phpExpr
   *   PHP expression to evaluate and return. (Encoded+decoded as JSON)
   * @return mixed
   *   The result of running $phpExpr through the given $runner.
   */
  protected function callRunnerJson($runner, $phpExpr) {
    $json = $this->callRunnerOk($runner, "echo json_encode($phpExpr);");
    return json_decode($json);
  }

  /**
   * @param string $runner
   *   Ex: 'cv ev @PHP'
   * @param string $phpStmt
   *   PHP code to execute
   * @return string
   *   The console output of running $phpStmt through the given $runner.
   */
  protected function callRunnerOk($runner, $phpStmt) {
    $cmd = strtr($runner, ['@PHP' => escapeshellarg($phpStmt)]);
    exec($cmd, $output, $val);
    $this->assertEquals(0, $val, "Command returned error ($cmd) ($val)");
    return implode("", $output);
  }

  protected function findCommand($name) {
    $paths = explode(PATH_SEPARATOR, getenv('PATH'));
    foreach ($paths as $path) {
      if (file_exists("$path/$name")) {
        return "$path/$name";
      }
    }
    return NULL;
  }

}
