<?php

namespace E2E\Core;

use Civi\Core\Url;

/**
 * Class PathUrlTest
 * @package E2E\Core
 * @group e2e
 *
 * Check that various paths and URLs are generated correctly.
 */
class PathUrlTest extends \CiviEndToEndTestCase {

  /**
   * `CRM_Utils_System::url()` should generate working URLs.
   */
  public function testSystemRouter() {
    $this->assertUrlContentRegex(';class="CRM_Mailing_Form_Subscribe";',
      \CRM_Utils_System::url('civicrm/mailing/subscribe', 'reset=1', TRUE, NULL, FALSE, TRUE));
  }

  /**
   * `Civi::paths()->getUrl()` should generate working URLs.
   */
  public function testPaths_getUrl() {
    $p = \Civi::paths();

    $this->assertUrlContentRegex(';MIT-LICENSE.txt;',
      $p->getUrl('[civicrm.packages]/jquery/plugins/jquery.timeentry.js', 'absolute'));
    $this->assertUrlContentRegex(';https://civicrm.org/licensing;',
      $p->getUrl('[civicrm.root]/js/Common.js', 'absolute'));
    $this->assertUrlContentRegex(';Copyright jQuery Foundation;',
      $p->getUrl('[civicrm.bower]/jquery/dist/jquery.js', 'absolute'));
  }

  /**
   * `Civi::paths()->getPath()` should generate working paths.
   */
  public function testPaths_getPath() {
    $p = \Civi::paths();

    $this->assertFileContentRegex(';MIT-LICENSE.txt;',
      $p->getPath('[civicrm.packages]/jquery/plugins/jquery.timeentry.js'));
    $this->assertFileContentRegex(';https://civicrm.org/licensing;',
      $p->getPath('[civicrm.root]/js/Common.js'));
    $this->assertFileContentRegex(';Copyright jQuery Foundation;',
      $p->getPath('[civicrm.bower]/jquery/dist/jquery.js'));
  }

  /**
   * `Civi::paths()->getVariable()` should generate working paths+URLs.
   */
  public function testPaths_getVariable() {
    $pathAndUrl = ['cms.root', 'civicrm.root', 'civicrm.packages', 'civicrm.files'];
    $pathOnly = ['civicrm.private', 'civicrm.log', 'civicrm.compile'];
    $urlOnly = [];

    foreach (array_merge($pathOnly, $pathAndUrl) as $var) {
      $path = \Civi::paths()->getVariable($var, 'path');
      $this->assertTrue(file_exists($path) && is_dir($path), "The path for $var should be a valid directory.");
    }

    foreach (array_merge($urlOnly, $pathAndUrl) as $var) {
      $url = \Civi::paths()->getVariable($var, 'url');
      $this->assertRegExp(';^https?:;', $url, "The URL for $var should resolve a URL.");
    }
  }

  /**
   * @param string $expectContentRegex
   * @param string $url
   */
  private function assertUrlContentRegex($expectContentRegex, $url) {
    $this->assertRegexp(';^https?:;', $url, "The URL ($url) should be absolute.");
    $content = file_get_contents($url);
    $this->assertRegexp($expectContentRegex, $content);
  }

  /**
   * @param string $expectContentRegex
   * @param string $file
   */
  private function assertFileContentRegex($expectContentRegex, $file) {
    $this->assertFileExists($file);
    $content = file_get_contents($file);
    $this->assertRegexp($expectContentRegex, $content);
  }

}
