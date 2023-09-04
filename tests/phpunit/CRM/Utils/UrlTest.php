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

}
