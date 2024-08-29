<?php

namespace E2E\Core;

use Civi;
use Civi\Core\Url;
use Civi\Test\RemoteTestFunction;

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
    $this->assertMatchesRegularExpression(';^[a-z0-9\.\-]+(:\d+)?$;', $parts[2], 'CIVICRM_UF_BASEURL should have domain name and/or port');
    $tmpVars['_SERVER']['HTTP_HOST'] = $parts[2];
    \CRM_Utils_GlobalStack::singleton()->push($tmpVars);

    parent::setUp();
  }

  protected function tearDown(): void {
    parent::tearDown();
    \CRM_Utils_GlobalStack::singleton()->pop();
  }

  public function testAbsoluteRelative(): void {
    $absolutes = $this->remote('getAbsolutes', function () {
      $absolutes = [];
      $absolutes['flag'] = Civi::url('backend://civicrm/admin', 'a');
      $absolutes['method'] = Civi::url('backend://civicrm/admin')->setPreferFormat('absolute');
      $absolutes['ext'] = Civi::url('ext://org.civicrm.search_kit/js/foobar.js', 'a');
      $absolutes['asset'] = Civi::url('asset://[civicrm.packages]/js/foobar.js', 'a');
      $absolutes['http'] = Civi::url('http://example.com/foo', 'a');
      $absolutes['https'] = Civi::url('https://example.com/foo', 'a');

      // Return both raw object and server-rendered string.
      return array_map(fn($u) => ['obj' => $u, 'str' => (string) $u], $absolutes);
    });

    $relatives = $this->remote('getRelatives', function () {
      $relatives = [];
      $relatives['default'] = Civi::url('backend://civicrm/admin');
      $relatives['flag'] = Civi::url('backend://civicrm/admin', 'r');
      $relatives['method'] = Civi::url('backend://civicrm/admin')->setPreferFormat('relative');
      $relatives['ext'] = Civi::url('ext://org.civicrm.search_kit/js/foobar.js', 'r');
      $relatives['asset'] = Civi::url('asset://[civicrm.packages]/js/foobar.js', 'r');

      // Return both raw object and server-rendered string.
      return array_map(fn($u) => ['obj' => $u, 'str' => (string) $u], $relatives);
    });

    $this->assertTrue(count($absolutes) > 4, 'Response should provide some well-formed examples');
    foreach ($absolutes as $key => $url) {
      $this->assertMatchesRegularExpression(';^https?://;', (string) $url['obj'], "(Local render) absolutes[$key] should be absolute URL");
      $this->assertMatchesRegularExpression(';^https?://;', $url['str'], "(Remote render) absolutes[$key] should be absolute URL");
    }

    $this->assertTrue(count($relatives) > 4, 'Response should provide some well-formed examples');
    foreach ($relatives as $key => $url) {
      $this->assertDoesNotMatchRegularExpression(';^https?://;', (string) $url['obj'], "(Local render) relatives[$key] should be relative URL");
      $this->assertDoesNotMatchRegularExpression(';^https?://;', $url['str'], "(Remote render) relatives[$key] should be relative URL");
    }
  }

  public function testHttp(): void {
    $examples = [];
    $examples[] = 'http://example.com';
    $examples[] = 'https://example.com';
    $examples[] = 'http://example.com:8888';
    $examples[] = 'https://example.com:8000';
    $examples[] = 'http://example.com/some/path?var=123';
    $examples[] = 'https://example.com/other/path#some-fragment';

    foreach ($examples as $example) {
      // Do a round-trip parse and re-encode
      $localRender = (string) Civi::url($example);
      $this->assertEquals($example, $localRender, '(Local render) Value should match');

      $remoteRender = $this->remote(__FUNCTION__, fn($e) => (string) Civi::url($e), [$example]);
      $this->assertEquals($example, $remoteRender, '(Local render) Value should match');
    }
  }

  public function testPath() {
    $examples = $this->remote(__FUNCTION__, function() {
      $examples = [];
      $examples[] = ['civicrm/ajax/api4', Civi::url('service://civicrm/ajax/api4')];
      $examples[] = ['civicrm/ajax/api4/Contact/get+stuff', Civi::url('service://civicrm/ajax/api4/Contact/get+stuff')];
      $examples[] = ['civicrm/ajax/api4/Contact/get+stuff', Civi::url('service://civicrm/ajax/api4')->addPath(['Contact', 'get stuff'])];
      $examples[] = ['civicrm/ajax/api4/Contact/get+stuff', Civi::url('service://civicrm/ajax/api4/Contact')->addPath('get+stuff')];
      $examples[] = ['civicrm/ajax/api4/Contact/get+stuff', Civi::url('service://civicrm/ajax/api4/Contact')->addPath(['get stuff'])];
      $examples[] = ['civicrm/new-path', Civi::url('service://civicrm/old-path')->setPath('civicrm/new-path')];
      return array_map(fn($u) => $u + [2 => (string) $u[1]], $examples);
    });

    $this->assertTrue(count($examples) > 4, 'Response should provide some well-formed examples');
    foreach ($examples as $key => $example) {
      /** @var \Civi\Core\Url $urlObj */
      [$expected, $urlObj, $urlStr] = $example;
      $this->assertEquals($expected, $urlObj->getPath(), sprintf("%s at %d should be have matching property", __FUNCTION__, $key));
      $this->assertUrlComponentContains('path', $expected, $urlObj, sprintf('(Local render) %s at %d: ', __FUNCTION__, $key));
      $this->assertUrlComponentContains('path', $expected, $urlStr, sprintf('(Remote render) %s at %d: ', __FUNCTION__, $key));
    }
  }

  public function testQuery() {
    $examples = $this->remote(__FUNCTION__, function() {
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
      $examples[] = ['reset=1&id=9&foo=1', Civi::url('https://example.com/base?reset=1&id=9&foo=1')];
      $examples[] = ['reset=1&id=9&foo=2', Civi::url('http://example.com/base?reset=1&id=9&foo=2')];
      return array_map(fn($u) => $u + [2 => (string) $u[1]], $examples);
    });

    foreach ($examples as $key => $example) {
      /** @var \Civi\Core\Url $urlObj */
      [$expected, $urlObj, $urlStr] = $example;
      $this->assertEquals($expected, $urlObj->getQuery(), sprintf("%s at %d should be have matching property", __FUNCTION__, $key));
      $this->assertUrlComponentContains('query', $expected, $urlObj, sprintf('(Local render) %s at %d: ', __FUNCTION__, $key));
      $this->assertUrlComponentContains('query', $expected, $urlStr, sprintf('(Remote render) %s at %d: ', __FUNCTION__, $key));
    }
  }

  public function testFragment(): void {
    $examples = $this->remote(__FUNCTION__, function () {
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
      return array_map(fn($u) => $u + [2 => (string) $u[1]], $examples);
    });

    $this->assertTrue(count($examples) > 4, 'Response should provide some well-formed examples');
    foreach ($examples as $key => $example) {
      /** @var \Civi\Core\Url $url */
      [$expected, $urlObj, $urlStr] = $example;
      $this->assertEquals($expected, $urlObj->getFragment(), sprintf("%s at %d should be have matching property", __FUNCTION__, $key));
      if ($expected !== NULL) {
        $this->assertStringContainsString($expected, (string) $urlObj, sprintf("On E2E thread, %s at %d should be have matching output", __FUNCTION__, $key));
        $this->assertStringContainsString($expected, $urlStr, sprintf("On HTTP thread, %s at %d should be have matching output", __FUNCTION__, $key));
      }
      else {
        $this->assertStringNotContainsString('#', (string) $urlObj);
        $this->assertStringNotContainsString('#', $urlStr);
      }
    }
  }

  public function testFragmentQuery() {
    $examples = $this->remote(__FUNCTION__, function () {
      $examples = [];
      $examples[] = ['angularDebug=1&extra=hello+world%3F', Civi::url('frontend://civicrm/a/#/mailing/new?angularDebug=1&extra=hello+world%3F')];
      $examples[] = ['angularDebug=1&extra=hello+world%3F', Civi::url('frontend://civicrm/a/#/mailing/new?angularDebug=1')->addFragmentQuery('extra=hello+world%3F')];
      $examples[] = ['angularDebug=1&extra=hello+world%3F', Civi::url('frontend://civicrm/a/#/mailing/new')->addFragmentQuery('angularDebug=1&extra=hello+world%3F')];
      $examples[] = ['angularDebug=1&extra=hello+world%3F', Civi::url('frontend://civicrm/a/#/mailing/new')->addFragmentQuery(['angularDebug' => 1, 'extra' => 'hello world?'])];
      $examples[] = ['angularDebug=1&extra=hello+world%3F', Civi::url('frontend://civicrm/a/#/mailing/new')->setFragmentQuery('angularDebug=1&extra=hello+world%3F')];
      $examples[] = ['angularDebug=1&extra=hello+world%3F', Civi::url('frontend://civicrm/a/#/mailing/new')->setFragmentQuery(['angularDebug' => 1, 'extra' => 'hello world?'])];
      return array_map(fn($u) => $u + [2 => (string) $u[1]], $examples);
    });

    foreach ($examples as $key => $example) {
      /** @var \Civi\Core\Url $urlObj */
      [$expected, $urlObj, $urlStr] = $example;
      $this->assertEquals($expected, $urlObj->getFragmentQuery(), sprintf("%s at %d should be have matching property", __FUNCTION__, $key));
      if ($expected !== NULL) {
        $this->assertStringContainsString($expected, (string) $urlObj, sprintf("%s at %d should be have matching output", __FUNCTION__, $key));
        $this->assertStringContainsString($expected, $urlStr, sprintf("%s at %d should be have matching output", __FUNCTION__, $key));

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
    $alternatives = $this->remote(__FUNCTION__, function () {
      $alternatives = [
        // Fully formed
        \Civi::url('frontend://civicrm/event/info?id=1'),

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
      return array_map(fn($u) => ['obj' => $u, 'str' => (string) $u], $alternatives);
    });

    $baseline = $alternatives[0]['str'];

    $this->assertUrlComponentContains('path', 'civicrm/event/info', $baseline);
    foreach ($alternatives as $key => $example) {
      $this->assertUrlComponentContains('path', 'civicrm/event/info', $example['str']);
      $this->assertUrlComponentContains('path', 'civicrm/event/info', (string) $example['obj']);
      $this->assertUrlComponentContains('query', 'id=1', $example['str']);
      $this->assertUrlComponentContains('query', 'id=1', (string) $example['obj']);
    }
  }

  public function testCustomSchemeClean(): void {
    Civi::dispatcher()->addListener('&civi.url.render.custom', function(Url $url, &$result) {
      $result = Civi::url('https://example.com/base')
        ->merge($url, ['path', 'query', 'fragment', 'fragmentQuery', 'flags']);
    });

    $this->assertEquals('https://example.com/base/foo', Civi::url('custom://foo')->__toString());
    $this->assertEquals('https://example.com/base/foo/bar?x=1', Civi::url('custom://foo/bar?x=1')->__toString());
    $this->assertEquals('https://example.com/base/foo/bar#whiz', Civi::url('custom://foo/bar#whiz')->__toString());
  }

  public function testCustomSchemeDirty(): void {
    Civi::dispatcher()->addListener('&civi.url.render.custom', function(Url $url, &$result) {
      $result = Civi::url('https://example.com/dirty.jsp')
        ->addQuery(['q' => $url->getPath()])
        ->merge($url, ['query', 'fragment', 'fragmentQuery', 'flags']);
    });

    $this->assertEquals('https://example.com/dirty.jsp?q=foo', Civi::url('custom://foo')->__toString());
    $this->assertEquals('https://example.com/dirty.jsp?q=foo%2Fbar&x=1', Civi::url('custom://foo/bar?x=1')->__toString());
    $this->assertEquals('https://example.com/dirty.jsp?q=foo%2Fbar#whiz', Civi::url('custom://foo/bar#whiz')->__toString());
  }

  protected function assertUrlComponentContains($expectField, $expectValue, string $renderedUrl, string $message = ''): void {
    $parsedUrl = parse_url($renderedUrl);
    // if ($expectField === 'path' && !\CRM_Utils_Constant::value('CIVICRM_CLEANURL')) {
    if ($expectField === 'path' && CIVICRM_UF === 'WordPress' && !str_contains($parsedUrl['path'], $expectValue)) {
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

  /**
   * @param string $name
   *   Logical name of the remote function.
   *   Most tests only have one, so they tend to use eponymous __FUNCTION__. But if you have multiple RFCs, they should differ.
   * @param \Closure $closure
   *   Function to call.
   *   Note: For debugging, you may look to the adjacent file ("myFunction.rfc.php").
   * @param array $args
   *   Data to pass into the function.
   * @return mixed
   */
  protected function remote(string $name, \Closure $closure, array $args = []) {
    $rtf = RemoteTestFunction::register(get_class($this), $name, $closure);

    // For UrlFacadeTest, it's handy to get back "Url" objects.
    $rtf->setResponseType('application/php-serialized')
      /**
       * @param \Psr\Http\Message\ResponseInterface $r
       */
      ->setResponseDecoder(function($r) {
        return unserialize((string) $r->getBody(), ['allowed_classes' => [Url::class]]);
      });

    return $rtf->execute($args);
  }

}
