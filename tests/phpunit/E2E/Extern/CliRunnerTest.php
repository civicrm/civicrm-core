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

  protected function setUp(): void {
    parent::setUp();

    foreach (['CIVI_CORE', 'CMS_ROOT', 'CMS_URL', 'ADMIN_USER'] as $var) {
      if (empty($GLOBALS['_CV'][$var])) {
        $this->markTestSkipped("Test environment does provide the civibuild/cv variable ($var)");
      }
    }
  }

  /**
   * Perform permission-checks using "on-behalf-of" mechanics.
   */
  public function testPermissionLookup() {
    $name = 'cv';
    $this->assertNotEmpty($this->findCommand($name), 'The command "$name" does not appear in the PATH.');
    $perms = ['administer CiviCRM', 'profile edit', 'sign CiviCRM Petition'];

    $r = 'cv ev @USER @PHP';
    $asAnon = ['@USER' => ''];
    $asAdmin = ['@USER' => '--user=' . escapeshellarg($GLOBALS['_CV']['ADMIN_USER'])];
    $asDemo = ['@USER' => '--user=' . escapeshellarg($GLOBALS['_CV']['DEMO_USER'])];

    // == This variant would validate the main `check()` has "consistency"
    // $checkFunc = 'CRM_Core_Permission::check';
    // $anonId = 0;
    // $adminId = CRM_Core_BAO_UFMatch::getContactId(\CRM_Core_Config::singleton()->userSystem->getUfId($GLOBALS['_CV']['ADMIN_USER']));
    // $demoId = CRM_Core_BAO_UFMatch::getContactId(\CRM_Core_Config::singleton()->userSystem->getUfId($GLOBALS['_CV']['DEMO_USER']));

    // == This variant would validate the internal `check()` adapter has "consistency"
    $checkFunc = 'CRM_Core_Config::singleton()->userPermissionClass->check';
    $anonId = 0;
    $adminId = \CRM_Core_Config::singleton()->userSystem->getUfId($GLOBALS['_CV']['ADMIN_USER']);
    $demoId = \CRM_Core_Config::singleton()->userSystem->getUfId($GLOBALS['_CV']['DEMO_USER']);

    $this->assertNotEmpty($adminId, 'Failed to resolve admin ID');
    $this->assertNotEmpty($demoId, 'Failed to resolve demo ID');

    foreach ($perms as $perm) {
      $anon['viewedByAdmin'] = $this->callRunnerJson($r, "$checkFunc(\"$perm\", $anonId)", $asAdmin);
      $anon['viewedByDemo'] = $this->callRunnerJson($r, "$checkFunc(\"$perm\", $anonId)", $asDemo);
      $anon['viewedByAnon'] = $this->callRunnerJson($r, "$checkFunc(\"$perm\", $anonId)", $asAnon);
      // $anon['viewedBySelf'] = $this->callRunnerJson($r, "$checkFunc(\"$perm\")", $asAnon);

      $demo['viewedByAdmin'] = $this->callRunnerJson($r, "$checkFunc(\"$perm\", $demoId)", $asAdmin);
      $demo['viewedByDemo'] = $this->callRunnerJson($r, "$checkFunc(\"$perm\", $demoId)", $asDemo);
      $demo['viewedByAnon'] = $this->callRunnerJson($r, "$checkFunc(\"$perm\", $demoId)", $asAnon);
      // $demo['viewedBySelf'] = $this->callRunnerJson($r, "$checkFunc(\"$perm\")", $asAdmin);

      $admin['viewedByAdmin'] = $this->callRunnerJson($r, "$checkFunc(\"$perm\", $adminId)", $asAdmin);
      $admin['viewedByDemo'] = $this->callRunnerJson($r, "$checkFunc(\"$perm\", $adminId)", $asDemo);
      $admin['viewedByAnon'] = $this->callRunnerJson($r, "$checkFunc(\"$perm\", $adminId)", $asAnon);
      // $admin['viewedBySelf'] = $this->callRunnerJson($r, "$checkFunc(\"$perm\")", $asAdmin);

      $report = print_r(['anon' => $anon, 'demo' => $demo, 'admin' => $admin], 1);

      $this->assertEquals(1, count(array_unique($anon)), "For permission \"$perm\" of anon(cid=$anonId), permissions should be consistent: " . $report);
      $this->assertEquals(1, count(array_unique($demo)), "For permission \"$perm\" of demo(cid=$demoId), permissions should be consistent: " . $report);
      $this->assertEquals(1, count(array_unique($admin)), "For permission \"$perm\" of admin(cid=$adminId), permissions should be consistent: " . $report);
    }
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
   * Use a CLI runner to start a bidirectional command pipe.
   *
   * This ensures that there are no funny headers or constraints of bidirectional data-flow.
   *
   * @param string $name
   *   The name of the command we're testing with.
   *   Ex: 'cv'
   * @param string $runner
   *   Ex: 'cv ev @PHP'
   * @dataProvider getRunners
   */
  public function testPipe($name, $runner) {
    $cmd = strtr($runner, ['@PHP' => escapeshellarg('Civi::pipe("t");')]);
    $rpc = new \Civi\Pipe\BasicPipeClient($cmd);

    $this->assertEquals('trusted', $rpc->getWelcome()['t'], "Expect standard Civi::pipe header when starting via $name");

    $r = $rpc->call('echo', ['a' => 123]);
    $this->assertEquals(['a' => 123], $r);

    $r = $rpc->call('echo', [4, 5, 6]);
    $this->assertEquals([4, 5, 6], $r);
  }

  /**
   * @param string $runner
   *   Ex: 'cv ev @PHP'
   * @param string $phpExpr
   *   PHP expression to evaluate and return. (Encoded+decoded as JSON)
   * @param string $vars
   *   Extra key-value pairs to include in command.
   * @return mixed
   *   The result of running $phpExpr through the given $runner.
   */
  protected function callRunnerJson($runner, $phpExpr, $vars = []) {
    $json = $this->callRunnerOk($runner, "echo json_encode($phpExpr);", $vars);
    return json_decode($json);
  }

  /**
   * @param string $runner
   *   Ex: 'cv ev @PHP'
   * @param string $phpStmt
   *   PHP code to execute
   * @param string $vars
   *   Extra key-value pairs to include in command.
   * @return string
   *   The console output of running $phpStmt through the given $runner.
   */
  protected function callRunnerOk($runner, $phpStmt, $vars = []) {
    $vars['@PHP'] = escapeshellarg($phpStmt);
    $cmd = strtr($runner, $vars);
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
