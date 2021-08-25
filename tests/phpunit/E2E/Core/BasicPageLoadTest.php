<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace E2E\Core;

use Civi\Test\Invasive;
use GuzzleHttp\Client;

/**
 * Walk through a list of common pages. Perform a full E2E request for the page,
 * ensure that each returns a well-formed response (eg HTTP 200). Each request must run as a specific
 * user (eg `ADMIN_USER` or `DEMO_USER`).
 *
 * When running this test, you may set some environment variables to influence it:
 *
 * - DEBUG=1: Output summary information for each HTTP request
 * - DEBUG=2: Output detailed information for each HTTP request
 * - PAGE_REGEX=';civicrm/activity;': Only test URLs that match the regex
 * - PAGE_LIMIT=100: Only test up to $x URLs.
 *
 * For example, you might run this command:
 *
 *   PAGE_REGEX=';civicrm/activity;' PAGE_LIMIT=3 DEBUG=1 phpunit7 tests/phpunit/E2E/Core/PageSmokeTest.php
 *
 * As part of the DEBUG output, it will provide the "curl log". You can copy-paste these
 * curl commands ot repeat the request.
 *
 * @package E2E\Core
 * @group e2e
 */
class BasicPageLoadTest extends \CiviEndToEndTestCase {

  use \Civi\Test\HttpTestTrait;

  const AUTH_TTL = 60 * 15;

  const IGNORE_URL_REGEX = ';civicrm(/|%2F)logout;';

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    if (!\CRM_Extension_System::singleton()->getMapper()->isActiveModule('authx')) {
      \civicrm_api3('Extension', 'install', ['key' => 'authx']);
    }
  }

  protected function setUp(): void {
    parent::setUp();
  }

  public function testAdminPages() {
    $http = $this->createGuzzle(['cookies' => new \GuzzleHttp\Cookie\CookieJar()]);
    $http->post('civicrm/authx/login', ['authx_user' => $GLOBALS['_CV']['ADMIN_USER']]);

    $urls = $this->parseNavUrls(json_decode($http->get('route://civicrm/ajax/navmenu')->getBody(), TRUE));
    $this->assertGreaterThan(100, count($urls), 'If fewer than 100 menu items are found, then something must be wrong with fetching the menu.');
    $urls = $this->filterTestUrls($urls);

    // Assert that URLs returned decent results. We want a full list of failed/erroneous pages.
    $fmtArr = function($arr) {
      $buf = '';
      foreach ($arr as $k => $v) {
        $buf .= sprintf("# URL: %s\n%s\n\n", $k, trim($v));
      }
      return $buf;
    };
    $actualValidations = $this->validateUrls($http, $urls);
    $expectValidations = array_fill_keys($urls, 'ok');
    $this->assertEquals($fmtArr($expectValidations), $fmtArr($actualValidations));
  }

  private function validateUrls(Client $http, array $urls): array {
    $matchHeader = function ($response, $header, $type) {
      return !empty(preg_grep($type, $response->getHeader($header)));
    };

    $result = [];
    foreach ($urls as $url) {
      $response = $http->get($url);
      $errs = [];
      if ($response->getStatusCode() != 200) {
        $errs[] = 'Error: HTTP ' . $response->getStatusCode();
      }
      elseif ($matchHeader($response, 'Content-Type', ';text/html;')) {
        $errs = array_merge($errs, static::checkResponseHtmlBasicParse((string) $response->getBody()));
        $errs = array_merge($errs, \CRM_Utils_HTMLTidy::validate((string) $response->getBody()));
      }
      else {
        $errs[] = "Unrecognized response type: " . json_encode($response->getHeader('Content-Type'));
      }
      $result[$url] = empty($errs) ? 'ok' : implode("\n", $errs);
    }
    return $result;
  }

  /**
   * Given a menu-tree, extract a list of URLs from the navigation menu.
   *
   * @param array $menuData
   *   See `civicrm/ajax/navmenu`
   * @return array
   *   List of absolute URLs.
   */
  private function parseNavUrls(array $menuData): array {
    $internalBase = parse_url(CIVICRM_UF_BASEURL, PHP_URL_SCHEME) . '://'
      . parse_url(CIVICRM_UF_BASEURL, PHP_URL_HOST)
      . (($port = parse_url(CIVICRM_UF_BASEURL, PHP_URL_PORT)) ? ":$port" : '');

    \CRM_Utils_Array::flatten($menuData['menu'], $flatMenu);
    $urls = array_filter($flatMenu, function($key) {
      return preg_match('/\.url$/', $key);
    }, ARRAY_FILTER_USE_KEY);

    $urls = array_map(['CRM_Utils_String', 'unstupifyUrl'], $urls);

    $urls = array_map(function($value) use ($internalBase) {
      return ($value[0] === '/' ? $internalBase : '') . $value;
    }, $urls);

    $urls = preg_grep('/' . preg_quote($internalBase, '/') . '/', $urls);

    $urls = array_unique($urls);
    sort($urls);
    return $urls;
  }

  public static function checkResponseHtmlBasicParse(string $body): array {
    $oldLibXMLErrors = libxml_use_internal_errors();
    try {
      libxml_use_internal_errors(TRUE);
      $doc = new \DOMDocument();
      if (!$doc->loadHTML($body)) {
        return [Invasive::call(['CRM_Utils_XML', 'formatErrors'], [libxml_get_errors()])];
      }
      // $doc->validate() sounds good but doesn't seem to work.
    }
    finally {
      libxml_use_internal_errors($oldLibXMLErrors);
    }
    return [];
  }

  /**
   * Apply any optional runtime filters. These can be used to focus on a specific set of pages.
   *
   * @param string[] $urls
   * @return string[]
   */
  private function filterTestUrls(array $urls): array {
    $urls = preg_grep(self::IGNORE_URL_REGEX, $urls, PREG_GREP_INVERT);
    if (getenv('PAGE_REGEX')) {
      $urls = preg_grep(getenv('PAGE_REGEX'), $urls);
    }
    if (getenv('PAGE_LIMIT')) {
      $urls = array_slice(array_values($urls), 0, getenv('PAGE_LIMIT'));
    }
    return $urls;
  }

}
