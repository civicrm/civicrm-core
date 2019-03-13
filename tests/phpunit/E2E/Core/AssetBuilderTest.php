<?php

namespace E2E\Core;

use Civi\Core\AssetBuilder;
use Civi\Core\Event\GenericHookEvent;


/**
 * Class AssetBuilderTest
 * @package E2E\Core
 * @group e2e
 */
class AssetBuilderTest extends \CiviEndToEndTestCase {

  protected $fired;

  /**
   * @inheritDoc
   */
  protected function setUp() {
    parent::setUp();

    \Civi::service('asset_builder')->clear();

    $this->fired['hook_civicrm_buildAsset'] = 0;
    \Civi::dispatcher()->addListener('hook_civicrm_buildAsset', array($this, 'counter'));
    \Civi::dispatcher()->addListener('hook_civicrm_buildAsset', array($this, 'buildSquareTxt'));
    \Civi::dispatcher()->addListener('hook_civicrm_buildAsset', array($this, 'buildSquareJs'));
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see \CRM_Utils_Hook::buildAsset()
   */
  public function counter(GenericHookEvent $e) {
    $this->fired['hook_civicrm_buildAsset']++;
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see \CRM_Utils_Hook::buildAsset()
   */
  public function buildSquareTxt(GenericHookEvent $e) {
    if ($e->asset !== 'square.txt') {
      return;
    }
    $this->assertTrue(in_array($e->params['x'], array(11, 12)));

    $e->mimeType = 'text/plain';
    $e->content = "Square: " . ($e->params['x'] * $e->params['x']);
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see \CRM_Utils_Hook::buildAsset()
   */
  public function buildSquareJs(GenericHookEvent $e) {
    if ($e->asset !== 'square.js') {
      return;
    }
    $this->assertTrue(in_array($e->params['x'], array(11, 12)));

    $e->mimeType = 'application/javascript';
    $e->content = "var square=" . ($e->params['x'] * $e->params['x']) . ';';
  }

  /**
   * Get a list of example assets to build/request.
   * @return array
   */
  public function getExamples() {
    $examples = array();

    $examples[] = array(
      0 => 'square.txt',
      1 => array('x' => 11),
      2 => 'text/plain',
      3 => 'Square: 121',
    );
    $examples[] = array(
      0 => 'square.txt',
      1 => array('x' => 12),
      2 => 'text/plain',
      3 => 'Square: 144',
    );
    $examples[] = array(
      0 => 'square.js',
      1 => array('x' => 12),
      2 => 'application/javascript',
      3 => 'var square=144;',
    );

    return $examples;
  }

  /**
   * @param string $asset
   *   Ex: 'square.txt'.
   * @param array $params
   *   Ex: [x=>12].
   * @param string $expectedMimeType
   *   Ex: 'text/plain'.
   * @param string $expectedContent
   *   Ex: 'Square: 144'.
   * @dataProvider getExamples
   */
  public function testRender($asset, $params, $expectedMimeType, $expectedContent) {
    $asset = \Civi::service('asset_builder')->render($asset, $params);
    $this->assertEquals(1, $this->fired['hook_civicrm_buildAsset']);
    $this->assertEquals($expectedMimeType, $asset['mimeType']);
    $this->assertEquals($expectedContent, $asset['content']);
  }

  /**
   * @param string $asset
   *   Ex: 'square.txt'.
   * @param array $params
   *   Ex: [x=>12].
   * @param string $expectedMimeType
   *   Ex: 'text/plain'.
   * @param string $expectedContent
   *   Ex: 'Square: 144'.
   * @dataProvider getExamples
   */
  public function testGetUrl_cached($asset, $params, $expectedMimeType, $expectedContent) {
    \Civi::service('asset_builder')->setCacheEnabled(TRUE);
    $url = \Civi::service('asset_builder')->getUrl($asset, $params);
    $this->assertEquals(1, $this->fired['hook_civicrm_buildAsset']);
    $this->assertRegExp(';^https?:.*dyn/square.[0-9a-f]+.(txt|js)$;', $url);
    $this->assertEquals($expectedContent, file_get_contents($url));
    // Note: This actually relies on httpd to determine MIME type.
    // That could be ambiguous for javascript.
    $this->assertContains("Content-Type: $expectedMimeType", $http_response_header);
    $this->assertNotEmpty(preg_grep(';HTTP/1.1 200;', $http_response_header));
  }

  /**
   * @param string $asset
   *   Ex: 'square.txt'.
   * @param array $params
   *   Ex: [x=>12].
   * @param string $expectedMimeType
   *   Ex: 'text/plain'.
   * @param string $expectedContent
   *   Ex: 'Square: 144'.
   * @dataProvider getExamples
   */
  public function testGetUrl_uncached($asset, $params, $expectedMimeType, $expectedContent) {
    \Civi::service('asset_builder')->setCacheEnabled(FALSE);
    $url = \Civi::service('asset_builder')->getUrl($asset, $params);
    $this->assertEquals(0, $this->fired['hook_civicrm_buildAsset']);
    // Ex: Traditional URLs on D7 have "/". Traditional URLs on WP have "%2F".
    $this->assertRegExp(';^https?:.*civicrm(/|%2F)asset(/|%2F)builder.*square.(txt|js);', $url);

    // Simulate a request. Our fake hook won't fire in a real request.
    parse_str(parse_url($url, PHP_URL_QUERY), $get);
    $asset = AssetBuilder::pageRender($get);
    $this->assertEquals($expectedMimeType, $asset['mimeType']);
    $this->assertEquals($expectedContent, $asset['content']);
  }

  public function testInvalid() {
    \Civi::service('asset_builder')->setCacheEnabled(FALSE);
    $url = \Civi::service('asset_builder')->getUrl('invalid.json');
    $this->assertEmpty(file_get_contents($url));
    $this->assertNotEmpty(preg_grep(';HTTP/1.1 404;', $http_response_header),
      'Expect to find HTTP 404. Found: ' . json_encode(preg_grep(';^HTTP;', $http_response_header)));
  }

}
