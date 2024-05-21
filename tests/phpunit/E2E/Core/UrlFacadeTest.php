<?php

namespace E2E\Core;

use Civi;
use Civi\Core\Url;

/**
 * Test generation of URLs via `Civi::url()` (`Civi\Core\Url`).
 *
 * This class is focused on portable aspects of the functionality.
 * There is also some coverage of the UF-specific parts in the E2E suite.
 *
 * @see \E2E\Core\PathUrlTest
 * @group e2e
 */
class UrlFacadeTest extends \CiviEndToEndTestCase {

  public function setUp(): void {
    $parts = explode('/', CIVICRM_UF_BASEURL);
    $this->assertRegexp(';^[a-z0-9\.\-]+(:\d+)?$;', $parts[2], 'CIVICRM_UF_BASEURL should have domain name and/or port');
    $tmpVars['_SERVER']['HTTP_HOST'] = $parts[2];
    \CRM_Utils_GlobalStack::singleton()->push($tmpVars);

    parent::setUp();
  }

  protected function tearDown(): void {
    parent::tearDown();
    \CRM_Utils_GlobalStack::singleton()->pop();
  }

  public function testAbsoluteRelative() {
    $absolutes = [];
    $absolutes['flag'] = Civi::url('backend://civicrm/admin', 'a');
    $absolutes['method'] = Civi::url('backend://civicrm/admin')->setPreferFormat('absolute');
    $absolutes['ext'] = Civi::url('ext://org.civicrm.search_kit/js/foobar.js', 'a');
    $absolutes['asset'] = Civi::url('asset://[civicrm.packages]/js/foobar.js', 'a');

    $relatives = [];
    $relatives['default'] = Civi::url('backend://civicrm/admin');
    $relatives['flag'] = Civi::url('backend://civicrm/admin', 'r');
    $relatives['method'] = Civi::url('backend://civicrm/admin')->setPreferFormat('relative');
    $relatives['ext'] = Civi::url('ext://org.civicrm.search_kit/js/foobar.js', 'r');
    $relatives['asset'] = Civi::url('asset://[civicrm.packages]/js/foobar.js', 'r');

    foreach ($absolutes as $key => $url) {
      $this->assertRegExp(';^https?://;', (string) $url, "absolutes[$key] should be absolute URL");
    }
    foreach ($relatives as $key => $url) {
      $this->assertNotRegExp(';^https?://;', (string) $url, "relatives[$key] should be relative URL");
    }
  }

  public function testPath() {
    $examples = [];
    $examples[] = ['civicrm/ajax/api4', Civi::url('service://civicrm/ajax/api4')];
    $examples[] = ['civicrm/ajax/api4/Contact/get+stuff', Civi::url('service://civicrm/ajax/api4/Contact/get+stuff')];
    $examples[] = ['civicrm/ajax/api4/Contact/get+stuff', Civi::url('service://civicrm/ajax/api4')->addPath(['Contact', 'get stuff'])];
    $examples[] = ['civicrm/ajax/api4/Contact/get+stuff', Civi::url('service://civicrm/ajax/api4/Contact')->addPath('get+stuff')];
    $examples[] = ['civicrm/ajax/api4/Contact/get+stuff', Civi::url('service://civicrm/ajax/api4/Contact')->addPath(['get stuff'])];
    $examples[] = ['civicrm/new-path', Civi::url('service://civicrm/old-path')->setPath('civicrm/new-path')];

    foreach ($examples as $key => $example) {
      /** @var \Civi\Core\Url $url */
      [$expected, $url] = $example;
      $this->assertEquals($expected, $url->getPath(), sprintf("%s at %d should be have matching property", __FUNCTION__, $key));
      $this->assertUrlComponentContains('path', $expected, $url, sprintf('%s at %d: ', __FUNCTION__, $key));
    }
  }

  public function testQuery() {
    $examples = [];
    $examples[] = ['reset=1&id=9', Civi::url('frontend://civicrm/profile/view?reset=1&id=9')];
    $examples[] = ['reset=1&id=9', Civi::url('frontend://civicrm/profile/view')->addQuery('reset=1&id=9')];
    $examples[] = ['reset=1&id=9', Civi::url('frontend://civicrm/profile/view')->addQuery(['reset' => 1, 'id' => 9])];
    $examples[] = ['noise=Hello+world%3F', Civi::url('frontend://civicrm/profile/view?noise=Hello+world%3F')];
    $examples[] = ['noise=Hello+world%3F', Civi::url('frontend://civicrm/profile/view')->addQuery('noise=Hello+world%3F')];
    $examples[] = ['noise=Hello+world%3F', Civi::url('frontend://civicrm/profile/view')->addQuery(['noise' => 'Hello world?'])];
    $examples[] = ['reset=1&id=9', Civi::url('frontend://civicrm/profile/view?forget=this')->setQuery('reset=1&id=9')];
    $examples[] = ['reset=1&id=9', Civi::url('frontend://civicrm/profile/view?forget=this')->setQuery(['reset' => 1, 'id' => 9])];
    $examples[] = ['reset=1&id=9', Civi::url('frontend://civicrm/profile/view?forget=this')->setQuery('reset=1')->addQuery('id=9')];

    foreach ($examples as $key => $example) {
      /** @var \Civi\Core\Url $url */
      [$expected, $url] = $example;
      $this->assertEquals($expected, $url->getQuery(), sprintf("%s at %d should be have matching property", __FUNCTION__, $key));
      $this->assertUrlComponentContains('query', $expected, $url, sprintf('%s at %d: ', __FUNCTION__, $key));
    }
  }

  public function testFragment() {
    $examples = [];
    $examples[] = ['/mailing/new', Civi::url('frontend://civicrm/a/#/mailing/new')];
    $examples[] = ['/mailing/new', Civi::url('frontend://civicrm/a/#/')->addFragment('mailing/new')];
    $examples[] = ['/mailing/new', Civi::url('frontend://civicrm/a/#/')->addFragment('/mailing/new')];
    $examples[] = ['/mailing/new', Civi::url('frontend://civicrm/a/#/')->addFragment(['mailing', 'new'])];
    $examples[] = [NULL, Civi::url('frontend://civicrm/a/#/mailing/new')->setFragment(NULL)];
    $examples[] = ['/mailing/new+stuff', Civi::url('frontend://civicrm/a/#/mailing/new+stuff?extra=1')];
    $examples[] = ['/mailing/new+stuff', Civi::url('frontend://civicrm/a/#/mailing?extra=1')->addFragment('new+stuff')];
    $examples[] = ['/mailing/new+stuff', Civi::url('frontend://civicrm/a/#/mailing?extra=1')->addFragment(['new stuff'])];
    $examples[] = ['/mailing/new+stuff', Civi::url('frontend://civicrm/a/#/ignore?extra=1')->setFragment('/mailing/new+stuff')];
    $examples[] = ['/mailing/new+stuff', Civi::url('frontend://civicrm/a/#/ignore?extra=1')->setFragment(['', 'mailing', 'new stuff'])];

    foreach ($examples as $key => $example) {
      /** @var \Civi\Core\Url $url */
      [$expected, $url] = $example;
      $this->assertEquals($expected, $url->getFragment(), sprintf("%s at %d should be have matching property", __FUNCTION__, $key));
      if ($expected !== NULL) {
        $this->assertStringContainsString($expected, (string) $url, sprintf("%s at %d should be have matching output", __FUNCTION__, $key));
      }
    }
  }

  public function testFragmentQuery() {
    $examples = [];
    $examples[] = ['angularDebug=1&extra=hello+world%3F', Civi::url('frontend://civicrm/a/#/mailing/new?angularDebug=1&extra=hello+world%3F')];
    $examples[] = ['angularDebug=1&extra=hello+world%3F', Civi::url('frontend://civicrm/a/#/mailing/new?angularDebug=1')->addFragmentQuery('extra=hello+world%3F')];
    $examples[] = ['angularDebug=1&extra=hello+world%3F', Civi::url('frontend://civicrm/a/#/mailing/new')->addFragmentQuery('angularDebug=1&extra=hello+world%3F')];
    $examples[] = ['angularDebug=1&extra=hello+world%3F', Civi::url('frontend://civicrm/a/#/mailing/new')->addFragmentQuery(['angularDebug' => 1, 'extra' => 'hello world?'])];
    $examples[] = ['angularDebug=1&extra=hello+world%3F', Civi::url('frontend://civicrm/a/#/mailing/new')->setFragmentQuery('angularDebug=1&extra=hello+world%3F')];
    $examples[] = ['angularDebug=1&extra=hello+world%3F', Civi::url('frontend://civicrm/a/#/mailing/new')->setFragmentQuery(['angularDebug' => 1, 'extra' => 'hello world?'])];

    foreach ($examples as $key => $example) {
      /** @var \Civi\Core\Url $url */
      [$expected, $url] = $example;
      $this->assertEquals($expected, $url->getFragmentQuery(), sprintf("%s at %d should be have matching property", __FUNCTION__, $key));
      if ($expected !== NULL) {
        $this->assertStringContainsString($expected, (string) $url, sprintf("%s at %d should be have matching output", __FUNCTION__, $key));
      }
    }
  }

  public function testVars(): void {
    $vars = ['hi' => 'hello world?', 'contact' => 123];

    $examples = [];
    $examples[] = ['path', 'civicrm/admin/hello+world%3F', Civi::url('backend://civicrm/admin/[hi]?x=1')];
    $examples[] = ['query', 'msg=hello+world%3F&id=123', Civi::url('backend://civicrm/admin?msg=[hi]&id=[contact]')];
    $examples[] = ['query', 'a=123&b=456', Civi::url('backend://civicrm/admin?a=[1]&b=[2]')->addVars([1 => 123, 2 => 456])];
    $examples[] = ['fragment', '/page?msg=hello+world%3F', Civi::url('backend://civicrm/a/#/page?msg=[hi]')];
    $examples[] = ['query', 'a=hello+world%3F&b=Au+re%2Fvoir', Civi::url('frontend://civicrm/user?a=[hi]&b=[bye]')->addVars(['bye' => 'Au re/voir'])];
    $examples[] = ['query', 'some_xyz=123', Civi::url('//civicrm/foo?some_[key]=123')->addVars(['key' => 'xyz'])];

    // Unrecognized []'s are preserved as literals, which allows interop with deep form fields
    $examples[] = ['query', 'some[key]=123', Civi::url('//civicrm/foo?some[key]=123')];

    foreach ($examples as $key => $example) {
      /** @var \Civi\Core\Url $url */
      [$field, $expected, $url] = $example;
      $url->addVars($vars);
      $this->assertUrlComponentContains($field, $expected, $url, sprintf('%s at %d: ', __FUNCTION__, $key));
    }
  }

  public function testFunkyStartPoints(): void {
    $baseline = (string) \Civi::url('frontend://civicrm/event/info?id=1');
    $this->assertUrlComponentContains('path', 'civicrm/event/info', $baseline);

    $alternatives = [
      // Start with nothing!
      \Civi::url()
        ->setScheme('frontend')
        ->setPath(['civicrm', 'event', 'info'])
        ->addQuery(['id' => 1]),

      // Start with nothing! And build it backwards!
      \Civi::url()
        ->addQuery(['id' => 1])
        ->addPath('civicrm')->addPath('event')->addPath('info')
        ->setScheme('frontend'),

      // Start with just the scheme
      \Civi::url('frontend:')
        ->addPath('civicrm/event/info')
        ->addQuery('id=1'),

      // Start with just the path
      \Civi::url('civicrm/event/info')
        ->setScheme('frontend')
        ->addQuery(['id' => 1]),
    ];
    foreach ($alternatives as $key => $alternative) {
      $this->assertEquals($baseline, (string) $alternative, "Alternative #$key should match baseline");
    }
  }

  protected function assertUrlComponentContains($expectField, $expectValue, string $renderedUrl, string $message = ''): void {
    $parsedUrl = parse_url($renderedUrl);
    // if ($expectField === 'path' && !\CRM_Utils_Constant::value('CIVICRM_CLEANURL')) {
    if ($expectField === 'path' && CIVICRM_UF === 'WordPress') {
      $expectField = 'query';
      $expectValue = \CRM_Core_Config::singleton()->userFrameworkURLVar . '=' . urlencode($expectValue);
    }
    $actualValue = $parsedUrl[$expectField];
    if ($expectField === 'query' && CIVICRM_UF === 'Drupal8') {
      // These characters may be URL encoded -- even when they don't need to be. We'll accept either form.
      $replace = ['%20' => '+', '%2F' => '/', '%5B' => '[', '%5D' => ']'];
      $expectValue = strtr($expectValue, $replace);
      $actualValue = strtr($actualValue, $replace);
    }
    $this->assertStringContainsString($expectValue, $actualValue, $message . sprintf("Field \"%s\" should contain \"%s\". (Full URL: %s)", $expectField, $expectValue, $renderedUrl));
  }

}
