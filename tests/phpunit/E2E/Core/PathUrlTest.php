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
  public function testSystemRouter(): void {
    $this->assertUrlContentRegex(';class="CRM_Mailing_Form_Subscribe";',
      \CRM_Utils_System::url('civicrm/mailing/subscribe', 'reset=1', TRUE, NULL, FALSE, TRUE));
  }

  /**
   * `Civi::paths()->getUrl()` should generate working URLs.
   */
  public function testPaths_getUrl(): void {
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
  public function testPaths_getPath(): void {
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
  public function testPaths_getVariable(): void {
    $pathAndUrl = ['cms.root', 'civicrm.root', 'civicrm.packages', 'civicrm.files'];
    $pathOnly = ['civicrm.private', 'civicrm.log', 'civicrm.compile'];
    $urlOnly = [];

    foreach (array_merge($pathOnly, $pathAndUrl) as $var) {
      $path = \Civi::paths()->getVariable($var, 'path');
      $this->assertTrue(file_exists($path) && is_dir($path), "The path for $var should be a valid directory.");
    }

    foreach (array_merge($urlOnly, $pathAndUrl) as $var) {
      $url = \Civi::paths()->getVariable($var, 'url');
      $this->assertMatchesRegularExpression(';^https?:;', $url, "The URL for $var should resolve a URL.");
    }
  }

  /**
   * Get URLs through Civi::url().
   *
   * @see \Civi\Core\UrlTest
   */
  public function testUrl(): void {
    // Make some requests for actual URLs
    $this->assertUrlContentRegex(';MIT-LICENSE.txt;', \Civi::url('[civicrm.packages]/jquery/plugins/jquery.timeentry.js', 'a'));
    $this->assertUrlContentRegex(';MIT-LICENSE.txt;', \Civi::url('asset://[civicrm.packages]/jquery/plugins/jquery.timeentry.js', 'a'));
    // crm-10n.js needs a fair few url params
    $this->assertUrlContentRegex(
        ';Please enter a valid email address;',
         \Civi::url('assetBuilder://crm-l10n.js', 'a')->addQuery(\CRM_Core_Resources::getL10nJsParams())
    );
    $this->assertUrlContentRegex(';.module..crmSearchAdmin;', \Civi::url('ext://org.civicrm.search_kit/ang/crmSearchAdmin.module.js', 'a'));
    $this->assertUrlContentRegex(';crm-section event_date_time-section;', \Civi::url('frontend://civicrm/event/info?id=1', 'a'));

    // Check for well-formedness of some URLs
    $urlPatterns = [];
    switch (CIVICRM_UF) {
      case 'Drupal':
      case 'Drupal8':
      case 'Backdrop':
      case 'Standalone':
        $urlPatterns[] = [';/civicrm/event/info\?reset=1&id=9;', \Civi::url('frontend://civicrm/event/info?reset=1')->addQuery('id=9')];
        $urlPatterns[] = [';/civicrm/admin\?reset=1;', \Civi::url('backend://civicrm/admin')->addQuery(['reset' => 1])];
        break;

      case 'WordPress':
        $urlPatterns[] = [';civiwp=CiviCRM.*civicrm.*event.*info.*reset=1&id=9;', \Civi::url('frontend://civicrm/event/info?reset=1')->addQuery('id=9')];
        $urlPatterns[] = [';/wp-admin.*civicrm.*admin.*reset=1;', \Civi::url('backend://civicrm/admin?reset=1')];
        break;

      case 'Joomla':
        $urlPatterns[] = [';/index.php\?.*task=civicrm/event/info&reset=1&id=9;', \Civi::url('frontend://civicrm/event/info?reset=1')->addQuery('id=9')];
        $urlPatterns[] = [';/administrator/.*task=civicrm/admin/reset=1;', \Civi::url('backend://civicrm/admin')->addQuery('reset=1')];
        break;

      default:
        $this->fail('Unrecognized UF: ' . CIVICRM_UF);
    }

    $urlPatterns[] = [';^https?://.*civicrm;', \Civi::url('frontend://civicrm/event/info?reset=1', 'a')];
    $urlPatterns[] = [';^https://.*civicrm;', \Civi::url('frontend://civicrm/event/info?reset=1', 'as')];
    $urlPatterns[] = [';civicrm(/|%2F)a(/|%2F).*#/mailing/new\?angularDebug=1;', \Civi::url('backend://civicrm/a/#/mailing/new?angularDebug=1')];
    $urlPatterns[] = [';/jquery.timeentry.js\?r=.*#foo;', \Civi::url('asset://[civicrm.packages]/jquery/plugins/jquery.timeentry.js', 'c')->addFragment('foo')];
    $urlPatterns[] = [';/stuff.js\?r=.*#foo;', \Civi::url('ext://org.civicrm.search_kit/stuff.js', 'c')->addFragment('foo')];
    $urlPatterns[] = [';#foo;', \Civi::url('assetBuilder://crm-l10n.js?locale=en_US')->addFragment('foo')->addQuery(\CRM_Core_Resources::getL10nJsParams())];

    // Some test-harnesses have HTTP_HOST. Some don't. It's pre-req for truly relative URLs.
    if (!empty($_SERVER['HTTP_HOST'])) {
      $urlPatterns[] = [';^/.*civicrm.*ajax.*api4.*Contact.*get;', \Civi::url('backend://civicrm/ajax/api4/Contact/get', 'r')];
    }

    $this->assertNotEmpty($urlPatterns);
    foreach ($urlPatterns as $urlPattern) {
      $this->assertRegExp($urlPattern[0], $urlPattern[1]);
    }
  }

  /**
   * Check that 'frontend://', 'backend://', and 'current://' have the expected relations.
   */
  public function testUrl_FrontBackCurrent(): void {
    $front = (string) \Civi::url('frontend://civicrm/profile/view');
    $back = (string) \Civi::url('backend://civicrm/profile/view');
    $current = (string) \Civi::url('current://civicrm/profile/view');
    $this->assertStringContainsString('profile', $front);
    $this->assertStringContainsString('profile', $back);
    $this->assertStringContainsString('profile', $current);
    if (CIVICRM_UF === 'WordPress' || CIVICRM_UF === 'Joomla') {
      $this->assertNotEquals($front, $back, "On WordPress/Joomla, some URLs should support frontend+backend flavors.");
    }
    else {
      $this->assertEquals($front, $back, "On Drupal/Backdrop/Standalone, frontend and backend URLs should look the same.");
    }
    $this->assertEquals($back, $current, "Within E2E tests, current routing style is backend.");
    // For purposes of this test, it doesn't matter if "current" is frontend or backend - as long as it's consistent.
  }

  public function testUrl_DefaultUI(): void {
    $adminDefault = (string) \Civi::url('default://civicrm/admin');
    $adminBackend = (string) \Civi::url('backend://civicrm/admin');
    $this->assertEquals($adminBackend, $adminDefault, "civicrm/admin should default to backend");

    $userDefault = (string) \Civi::url('default://civicrm/user');
    $userBackend = (string) \Civi::url('frontend://civicrm/user');
    $this->assertEquals($userBackend, $userDefault, "civicrm/user should default to frontend");
  }

  /**
   * @param string $expectContentRegex
   * @param string $url
   */
  private function assertUrlContentRegex($expectContentRegex, $url) {
    $this->assertMatchesRegularExpression(';^https?:;', $url, "The URL ($url) should be absolute.");
    $content = file_get_contents($url);
    $this->assertMatchesRegularExpression($expectContentRegex, $content);
  }

  /**
   * @param string $expectContentRegex
   * @param string $file
   */
  private function assertFileContentRegex($expectContentRegex, $file) {
    $this->assertFileExists($file);
    $content = file_get_contents($file);
    $this->assertMatchesRegularExpression($expectContentRegex, $content);
  }

  /**
   * @link https://lab.civicrm.org/dev/core/issues/1637
   */
  public function testGetUrl_WpAdmin(): void {
    $config = \CRM_Core_Config::singleton();
    if ($config->userFramework !== 'WordPress') {
      $this->markTestSkipped('This test only applies to WP sites.');
    }

    // NOTE: For backend admin forms (eg `civicrm/contribute`) on WP, it doesn't matter
    // if cleanURL's are enabled. Those are always be dirty URLs.

    // WORKAROUND: There's some issue where the URL gets a diff value in WP E2E env
    // than in normal WP env. The `cv url` command seems to behave more
    // representatively, though this technique is harder to inspect with xdebug.
    $url = cv('url civicrm/contribute?reset=1 --entry=backend');
    // $url = \CRM_Utils_System::url('civicrm/contribute', 'reset=1', TRUE, NULL, FALSE);

    $parts = parse_url($url);
    parse_str($parts['query'], $queryParts);
    $this->assertEquals('CiviCRM', $queryParts['page']);
    $this->assertEquals('civicrm/contribute', $queryParts['q']);
    $this->assertEquals('1', $queryParts['reset']);

    // As an E2E test for wp-demo, this assertion is specifically valid for wp-demo.
    $this->assertEquals('/wp-admin/admin.php', $parts['path']);
  }

}
