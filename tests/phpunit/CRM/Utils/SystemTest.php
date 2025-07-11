<?php


/**
 * Class CRM_Utils_SystemTest
 * @group headless
 */
class CRM_Utils_SystemTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  public function testUrlQueryString(): void {
    $config = CRM_Core_Config::singleton();
    $this->assertTrue($config->userSystem instanceof CRM_Utils_System_UnitTests);
    $expected = '/index.php?q=civicrm/foo/bar&foo=ab&bar=cd%26ef';
    $actual = CRM_Utils_System::url('civicrm/foo/bar', 'foo=ab&bar=cd%26ef', FALSE, NULL, FALSE);
    $this->assertEquals($expected, $actual);
  }

  public function testUrlQueryArray(): void {
    $config = CRM_Core_Config::singleton();
    $this->assertTrue($config->userSystem instanceof CRM_Utils_System_UnitTests);
    $expected = '/index.php?q=civicrm/foo/bar&foo=ab&bar=cd%26ef';
    $actual = CRM_Utils_System::url('civicrm/foo/bar', [
      'foo' => 'ab',
      'bar' => 'cd&ef',
    ], FALSE, NULL, FALSE);
    $this->assertEquals($expected, $actual);
  }

  public function testEvalUrl(): void {
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
    $this->hookClass->setHook('civicrm_alterRedirect', [$this, 'hook_civicrm_alterRedirect']);
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
    $this->assertEquals($context['expected']['scheme'] ?? NULL, $urlQuery->getScheme());
    $this->assertEquals($context['expected']['host'] ?? NULL, $urlQuery->getHost());
    $this->assertEquals($context['expected']['query'] ?? NULL, $urlQuery->getQuery());
    $this->assertEquals($context['original'], CRM_Utils_Url::unparseUrl($urlQuery));

    throw new CRM_Core_Exception(ts('hook called'));
  }

  /**
   * Get urls for testing.
   *
   * @return array
   */
  public static function getURLs() {
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

  /**
   * Test extern url.
   */
  public function testExternUrl(): void {
    $siteKey = mt_rand();
    $apiKey = mt_rand();
    $restUrl = CRM_Utils_System::externUrl('extern/rest', "entity=Contact&action=get&key=$siteKey&api_key=$apiKey");
    $this->assertStringContainsString('extern/rest.php', $restUrl);
    $this->assertStringContainsString('?', $restUrl);
    $this->assertStringContainsString('entity=Contact', $restUrl);
    $this->assertStringContainsString('action=get', $restUrl);
    $this->assertStringContainsString("key=$siteKey", $restUrl);
    $this->assertStringContainsString("api_key=$apiKey", $restUrl);
  }

  /**
   * Test the alterExternUrl hook.
   *
   * @param string $path
   * @param array $expected
   *
   * @dataProvider getExternURLs
   */
  public function testAlterExternUrlHook($path, $expected) {
    Civi::dispatcher()->addListener('hook_civicrm_alterExternUrl', [$this, 'hook_civicrm_alterExternUrl']);
    $externUrl = CRM_Utils_System::externUrl($path, $expected['query']);
    $this->assertStringContainsString('/path/altered/by/hook', $externUrl, 'Hook failed to alter URL path');
    $this->assertStringContainsString($expected['query'] . '&thisWas=alteredByHook', $externUrl, 'Hook failed to alter URL query');
  }

  /**
   * Hook for alterExternUrl.
   *
   * @param \Civi\Core\Event\GenericHookEvent $event
   * @param string $hookName
   */
  public function hook_civicrm_alterExternUrl(\Civi\Core\Event\GenericHookEvent $event, $hookName) {
    $this->assertEquals('hook_civicrm_alterExternUrl', $hookName);
    $this->assertTrue($event->hasField('url'));
    $this->assertTrue($event->hasField('path'));
    $this->assertTrue($event->hasField('query'));
    $this->assertTrue($event->hasField('fragment'));
    $this->assertTrue($event->hasField('absolute'));
    $this->assertTrue($event->hasField('isSSL'));
    $event->url = $event->url->withPath('/path/altered/by/hook');
    $event->url = $event->url->withQuery($event->query . '&thisWas=alteredByHook');
  }

  /**
   * Get extern url params for testing.
   *
   * @return array
   */
  public static function getExternURLs() {
    return [
      [
        'extern/url',
        [
          'path' => 'extern/url',
          'query' => 'u=1&qid=1',
        ],
      ],
      [
        'extern/open',
        [
          'path' => 'extern/open',
          'query' => 'q=1',
        ],
      ],
    ];
  }

  /**
   * Demonstrate the, um, "flexibility" of isNull
   */
  public function testIsNull(): void {
    $this->assertTrue(CRM_Utils_System::isNull(NULL));
    $this->assertTrue(CRM_Utils_System::isNull(''));
    $this->assertTrue(CRM_Utils_System::isNull('null'));
    // Not sure how to test this one because phpunit itself throws an error.
    // $this->assertTrue(CRM_Utils_System::isNull($someUnsetVariable));

    // but...
    $this->assertFalse(CRM_Utils_System::isNull('NULL'));
    $this->assertFalse(CRM_Utils_System::isNull('Null'));

    // probably ok?
    $this->assertTrue(CRM_Utils_System::isNull([]));

    // ok
    $this->assertFalse(CRM_Utils_System::isNull(0));

    // sure
    $arr = [
      1 => NULL,
    ];
    $this->assertTrue(CRM_Utils_System::isNull($arr[1]));
    $this->assertTrue(CRM_Utils_System::isNull($arr));

    // but then a little confusing
    $arr = [
      'IN' => NULL,
    ];
    $this->assertFalse(CRM_Utils_System::isNull($arr));

    // now just guessing
    $obj = new StdClass();
    $this->assertFalse(CRM_Utils_System::isNull($obj));
    $obj->anything = NULL;
    $this->assertFalse(CRM_Utils_System::isNull($obj));

    // this is ok
    $arr = [
      1 => [
        'foo' => 'bar',
      ],
      2 => [
        'a' => NULL,
      ],
    ];
    $this->assertFalse(CRM_Utils_System::isNull($arr));

    $arr = [
      1 => $obj,
    ];
    $this->assertFalse(CRM_Utils_System::isNull($arr));

    // sure
    $arr = [
      1 => NULL,
      2 => '',
      3 => 'null',
    ];
    $this->assertTrue(CRM_Utils_System::isNull($arr));
  }

  public static function crudLinkExamples() {
    return [
      'Contact:update' => [
        ['entity' => 'Contact', 'action' => 'update', 'id' => 123],
        ['title' => 'Edit Contact', 'url' => '/index.php?q=civicrm/contact/add&reset=1&action=update&cid=123'],
      ],
      'civicrm_contact:UPDATE' => [
        ['entity_table' => 'civicrm_contact', 'action' => CRM_Core_Action::UPDATE, 'entity_id' => 123],
        ['title' => 'Edit Contact', 'url' => '/index.php?q=civicrm/contact/add&reset=1&action=update&cid=123'],
      ],
      'civicrm_activity:ADD' => [
        ['entity_table' => 'civicrm_activity', 'action' => CRM_Core_Action::ADD],
        ['title' => 'New Activity', 'url' => '/index.php?q=civicrm/activity&reset=1&action=add&context=standalone'],
      ],
      'Contribution:delete' => [
        ['entity' => 'Contribution', 'action' => 'DELETE', 'id' => 456],
        ['title' => 'Delete Contribution', 'url' => '/index.php?q=civicrm/contact/view/contribution&reset=1&action=delete&id=456'],
      ],
    ];
  }

  /**
   * @dataProvider crudLinkExamples
   */
  public function testCrudLink($params, $expectedResult) {
    $result = CRM_Utils_System::createDefaultCrudLink($params);
    $this->assertEquals($expectedResult, $result);
  }

  /**
   * Test that flushing cache clears the asset cache.
   */
  public function testFlushCacheClearsAssetCache(): void {
    // We need to get the file path for the folder and there isn't a public
    // method to get it, so create a file in the folder using public methods,
    // then get the path from that, then flush the cache, then check if the
    // folder is empty.
    \Civi::dispatcher()->addListener('hook_civicrm_buildAsset', [$this, 'flushCacheClearsAssetCache_buildAsset']);
    $fakeFile = \Civi::service("asset_builder")->getPath('fakeFile.json');

    Civi::rebuild(['system' => TRUE])->execute();

    $fileList = scandir(dirname($fakeFile));
    // count should be 2, just the standard . and ..
    $this->assertCount(2, $fileList);
  }

  /**
   * Implementation of a hook for civicrm_buildAsset() for testFlushCacheClearsAssetCache.
   * Awkward wording of above sentence is because phpcs is bugging me about it.
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public function flushCacheClearsAssetCache_buildAsset(\Civi\Core\Event\GenericHookEvent $e) {
    if ($e->asset === 'fakeFile.json') {
      $e->mimeType = 'application/json';
      $e->content = '{}';
    }
  }

}
