<?php

/**
 * Class CRM_Utils_UrlTest
 * @group headless
 */
class CRM_Utils_UrlTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  /**
   * Equal cases.
   *
   * @return array
   */
  public function relativeCases() {
    // array(0 => $absoluteUrl, 1 => $currentHost, 2 => $expectedResult)
    $cases = [];
    $cases[] = ['//example.com/', 'example.com', '/'];
    $cases[] = ['http://example.com/', 'example.com', '/'];
    $cases[] = ['http://example.com/foo', 'example.com', '/foo'];
    $cases[] = ['https://example.com/foo/bar', 'example.com', '/foo/bar'];
    $cases[] = ['http://example.com/foo?bar#baz', 'example.com', '/foo?bar#baz'];

    $cases[] = ['//example.com/', 'example.com:8001', '//example.com/'];
    $cases[] = ['http://example.com/', 'example.com:8001', 'http://example.com/'];
    $cases[] = ['http://example.com/foo', 'example.com:8001', 'http://example.com/foo'];
    $cases[] = ['https://example.com/foo/bar', 'example.com:8001', 'https://example.com/foo/bar'];
    $cases[] = ['http://example.com/foo?bar#baz', 'example.com:8001', 'http://example.com/foo?bar#baz'];

    $cases[] = ['//example.com:8001/', 'example.com:8001', '/'];
    $cases[] = ['http://example.com:8001/', 'example.com:8001', '/'];
    $cases[] = ['http://example.com:8001/foo', 'example.com:8001', '/foo'];
    $cases[] = ['https://example.com:8001/foo/bar', 'example.com:8001', '/foo/bar'];
    $cases[] = ['http://example.com:8001/foo?bar#baz', 'example.com:8001', '/foo?bar#baz'];

    $cases[] = ['//sub.example.com/', 'example.com', '//sub.example.com/'];
    $cases[] = ['http://sub.example.com/', 'example.com', 'http://sub.example.com/'];
    $cases[] = ['http://sub.example.com/foo', 'example.com', 'http://sub.example.com/foo'];

    $cases[] = ['//sub.example.com/', 'sub.example.com', '/'];
    $cases[] = ['http://sub.example.com/', 'sub.example.com', '/'];
    $cases[] = ['http://sub.example.com/foo/bar', 'sub.example.com', '/foo/bar'];

    $cases[] = ['http://127.0.0.1/foo/bar', 'sub.example.com', 'http://127.0.0.1/foo/bar'];
    $cases[] = ['https://127.0.0.1/foo/bar', '127.0.0.1', '/foo/bar'];

    $cases[] = ['http://[2001:aaaa:bbbb:cccc:dddd:eeee:ffff:9999]:8001/foo/bar', 'sub.example.com', 'http://[2001:aaaa:bbbb:cccc:dddd:eeee:ffff:9999]:8001/foo/bar'];
    $cases[] = ['https://[2001:aaaa:bbbb:cccc:dddd:eeee:ffff:9999]:8001/foo/bar', '[2001:aaaa:bbbb:cccc:dddd:eeee:ffff:9999]:8001', '/foo/bar'];

    // return CRM_Utils_Array::subset($cases, [2]);
    return $cases;
  }

  /**
   * Test relative URL conversions.
   *
   * @param string $absoluteUrl
   * @param string $currentHost
   * @param string $expectedResult
   *
   * @dataProvider relativeCases
   */
  public function testEquals($absoluteUrl, $currentHost, $expectedResult) {
    $actual = CRM_Utils_Url::toRelative($absoluteUrl, $currentHost);
    $this->assertEquals($expectedResult, $actual, "Within \"$currentHost\", \"$absoluteUrl\" should render as \"$expectedResult\"");
  }

  public function getChildOfExamples(): array {
    $es = [];

    $es[] = ['http://example.com/child', 'http://example.com/', TRUE];
    $es[] = ['http://example.com:8888/child', 'http://example.com:8888/', TRUE];
    $es[] = ['http://example.com/page.php?foo=bar', 'http://example.com/page.php', TRUE];

    $es[] = ['http://example.com/', 'http://example.com/nope', FALSE];
    $es[] = ['http://example.com/child', 'http://example.com:8888/', FALSE];
    $es[] = ['http://example.com:8888/', 'http://example.com:8888/nope', FALSE];
    $es[] = ['http://example.com/page.php?foo=bar', 'http://example.com/page.php?x=y', FALSE];

    // These rely on implicit HTTP_HOST.

    $es[] = ['/router.php', '/', TRUE];
    $es[] = ['/router.php/foo', '/router.php', TRUE];
    $es[] = ['/router.php', 'http://childof.example.com/router.php/page', FALSE];
    $es[] = ['http://childof.example.com/router.php/page', '/router.php', TRUE];
    $es[] = ['http://childof.example.com/page.php', '/', TRUE];
    $es[] = ['http://childof.example.com/page.php', 'http://childof.example.com/', TRUE];

    $es[] = ['/page.php', 'http://other.example.com/', FALSE];
    $es[] = ['/', 'http://other.example.com/', FALSE];

    return $es;
    // return [$es[9]];
  }

  /**
   * @param string $childStr
   * @param string $parentStr
   * @param bool $expected
   * @return void
   * @dataProvider getChildOfExamples
   */
  public function testIsChildOf(string $childStr, string $parentStr, bool $expected): void {
    $oldHost = $_SERVER['HTTP_HOST'] ?? NULL;
    $_SERVER['HTTP_HOST'] = 'childof.example.com';
    $autoclean = CRM_Utils_AutoClean::with(fn() => $_SERVER['HTTP_HOST'] = $oldHost);

    $allFormats = [
      'string' => fn($url) => $url,
      'psr7' => ['CRM_Utils_Url', 'parseUrl'],
      'civi' => ['Civi', 'url'],
    ];
    $childFormats = $parentFormats = $allFormats;
    // $childFormats = CRM_Utils_Array::subset($allFormats, ['string']);
    // $parentFormats = CRM_Utils_Array::subset($allFormats, ['string']);

    $count = 0;
    foreach ($childFormats as $childFmtName => $childFmt) {
      foreach ($parentFormats as $parentFmtName => $parentFmt) {
        if ($childFmtName === 'civi' && $childStr[0] === '/') {
          continue; /* In Civi::url(), URLs are relative to the router -- not the domain. */
        }
        if ($parentFmtName === 'civi' && $parentStr[0] === '/') {
          continue; /* In Civi::url(), URLs are relative to the router -- not the domain. */
        }

        $childValue = $childFmt($childStr);
        $parentValue = $parentFmt($parentStr);
        $message = sprintf('Check isChildOf(%s(%s), %s(%s))', $childFmtName, $childStr, $parentFmtName, $parentStr);
        $actual = CRM_Utils_Url::isChildOf($childValue, $parentValue);
        $this->assertEquals($expected, $actual, $message);
        $count++;
      }
    }

    $this->assertTrue($count > 0, "Should have at least 1 check");
  }

}
