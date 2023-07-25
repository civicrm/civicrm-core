<?php

namespace Civi\Core;

use Civi;

/**
 * Test generation of URLs via `Civi::url()` (`Civi\Core\Url`).
 *
 * This class is focused on portable aspects of the functionality.
 * There is also some coverage of the UF-specific parts in the E2E suite.
 *
 * @see \E2E\Core\PathUrlTest
 * @group headless
 */
class UrlTest extends \CiviUnitTestCase {

  public function setUp(): void {
    $parts = explode('/', CIVICRM_UF_BASEURL);
    $this->assertRegexp(';^[a-z0-9\.\-]+(:\d+)?$;', $parts[2], 'CIVICRM_UF_BASEURL should have domain name and/or port');
    $tmpVars['_SERVER']['HTTP_HOST'] = $parts[2];
    \CRM_Utils_GlobalStack::singleton()->push($tmpVars);

    parent::setUp();
    $this->useTransaction();
  }

  protected function tearDown(): void {
    parent::tearDown();
    \CRM_Utils_GlobalStack::singleton()->pop();
  }

  public function testAbsoluteRelative() {
    $absolutes = [];
    $absolutes['flag'] = Civi::url('backend://civicrm/admin', 'a');
    $absolutes['method'] = Civi::url('backend://civicrm/admin')->setPreferFormat('absolute');

    $relatives = [];
    $relatives['default'] = Civi::url('backend://civicrm/admin');
    $relatives['flag'] = Civi::url('backend://civicrm/admin', 'r');
    $relatives['method'] = Civi::url('backend://civicrm/admin')->setPreferFormat('relative');

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
      $this->assertStringContainsString($expected, (string) $url, sprintf("%s at %d should be have matching output", __FUNCTION__, $key));
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
      $this->assertStringContainsString($expected, (string) $url, sprintf("%s at %d should be have matching output", __FUNCTION__, $key));
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

}
