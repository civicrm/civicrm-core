<?php


/**
 * Class CRM_Utils_SystemTest
 * @group headless
 */
class CRM_Utils_SystemTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function testUrlQueryString() {
    $config = CRM_Core_Config::singleton();
    $this->assertTrue($config->userSystem instanceof CRM_Utils_System_UnitTests);
    $expected = '/index.php?q=civicrm/foo/bar&foo=ab&bar=cd%26ef';
    $actual = CRM_Utils_System::url('civicrm/foo/bar', 'foo=ab&bar=cd%26ef', FALSE, NULL, FALSE);
    $this->assertEquals($expected, $actual);
  }

  public function testUrlQueryArray() {
    $config = CRM_Core_Config::singleton();
    $this->assertTrue($config->userSystem instanceof CRM_Utils_System_UnitTests);
    $expected = '/index.php?q=civicrm/foo/bar&foo=ab&bar=cd%26ef';
    $actual = CRM_Utils_System::url('civicrm/foo/bar', array(
      'foo' => 'ab',
      'bar' => 'cd&ef',
    ), FALSE, NULL, FALSE);
    $this->assertEquals($expected, $actual);
  }

  public function testEvalUrl() {
    $this->assertEquals(FALSE, CRM_Utils_System::evalUrl(FALSE));
    $this->assertEquals('http://example.com/', CRM_Utils_System::evalUrl('http://example.com/'));
    $this->assertEquals('http://example.com/?cms=UnitTests', CRM_Utils_System::evalUrl('http://example.com/?cms={uf}'));
  }

  /**
   * Test the redirect hook.
   *
   * @param string $url
   * @param array $parsedUrl
   *
   * @dataProvider getURLs
   */
  public function testRedirectHook($url, $parsedUrl) {
    $this->hookClass->setHook('civicrm_alterRedirect', array($this, 'hook_civicrm_alterRedirect'));
    try {
      CRM_Utils_System::redirect($url, [
        'expected' => $parsedUrl,
        'original' => $url,
      ]);
    }
    catch (CRM_Core_Exception $e) {
      $this->assertEquals(ts('hook called'), $e->getMessage());
      return;
    }
    $this->fail('Exception should have been thrown if hook was called');
  }

  /**
   * Hook for alterRedirect.
   *
   * We do some checks here.
   *
   * @param \Psr\Http\Message\UriInterface $urlQuery
   * @param array $context
   *
   * @throws \CRM_Core_Exception
   */
  public function hook_civicrm_alterRedirect($urlQuery, $context) {
    $this->assertEquals(CRM_Utils_Array::value('scheme', $context['expected']), $urlQuery->getScheme());
    $this->assertEquals(CRM_Utils_Array::value('host', $context['expected']), $urlQuery->getHost());
    $this->assertEquals(CRM_Utils_Array::value('query', $context['expected']), $urlQuery->getQuery());
    $this->assertEquals($context['original'], CRM_Utils_Url::unparseUrl($urlQuery));

    throw new CRM_Core_Exception(ts('hook called'));
  }

  /**
   * Get urls for testing.
   *
   * @return array
   */
  public function getURLs() {
    return [
      [
        'https://example.com?ab=cd',
        [
          'scheme' => 'https',
          'host' => 'example.com',
          'query' => 'ab=cd',
        ],
      ],
      [
        'http://myuser:mypass@foo.bar:123/whiz?a=b&c=d',
        [
          'scheme' => 'http',
          'host' => 'foo.bar',
          'port' => 123,
          'user' => 'myuser',
          'pass' => 'mypass',
          'path' => '/whiz',
          'query' => 'a=b&c=d',
        ],
      ],
      [
        '/foo/bar',
        [
          'path' => '/foo/bar',
        ],
      ],
    ];
  }

}
