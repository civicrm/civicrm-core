<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */
require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/CiviSeleniumSettings.php';

define('CIVICRM_WEBTEST', 1);

/**
 * Check that we handle redirects appropriately.
 */
class WebTest_Utils_RedirectTest extends CiviUnitTestCase {
  protected $url;
  protected $ch;

  /**
   * @param string|null $name
   */
  public function __construct($name = NULL) {
    parent::__construct($name);

    $this->settings = new CiviSeleniumSettings();
    if (property_exists($this->settings, 'serverStartupTimeOut') && $this->settings->serverStartupTimeOut) {
      global $CiviSeleniumTestCase_polled;
      if (!$CiviSeleniumTestCase_polled) {
        $CiviSeleniumTestCase_polled = TRUE;
        CRM_Utils_Network::waitForServiceStartup(
          $this->drivers[0]->getHost(),
          $this->drivers[0]->getPort(),
          $this->settings->serverStartupTimeOut
        );
      }
    }
  }

  protected function setUp() {
    parent::setUp();
    //URL should eventually be adapted for multisite
    $this->url = $this->settings->sandboxURL;

    $this->ch = curl_init();
    curl_setopt($this->ch, CURLOPT_HEADER, FALSE);
    curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, FALSE);
    // curl_setopt($this->ch, CURLOPT_ENCODING, 'gzip');
    // curl_setopt($this->ch, CURLOPT_VERBOSE, 0);
  }

  /**
   * Try redirect.
   *
   * @param string $input_url
   * @param string $expected_url
   */
  private function tryRedirect($input_url, $expected_url) {
    // file_put_contents('php://stderr', $input_url . "\n", FILE_APPEND);
    $url = $this->url . '/' . $input_url;
    $expected_url = $this->url . '/' . $expected_url;
    curl_setopt($this->ch, CURLOPT_URL, $url);
    $req = curl_exec($this->ch);
    $this->assertEquals(0, curl_errno($this->ch), 'cURL error: ' . curl_error($this->ch));
    if (!curl_errno($this->ch)) {
      $info = curl_getinfo($this->ch);
      // file_put_contents('php://stderr', print_r($info,1), FILE_APPEND);
      $this->assertEquals($expected_url, $info['redirect_url']);
      $this->assertEquals('302', $info['http_code']);
    }
  }

  /**
   * Handle onsite redirects with absolute URL.
   */
  public function testAbsoluteOnsiteRedirect() {
    $this->tryRedirect("civicrm/contribute/transact?qfKey=xxx&entryURL={$this->url}/civicrm/contribute/transact%3Fid%3D1", 'civicrm/contribute/transact?id=1');
  }

  /**
   * Handle onsite redirects with slash prefix and query params.
   */
  public function testOnsiteRedirectWithSlashPrefixAndQueryParams() {
    $this->tryRedirect('civicrm/contribute/transact?qfKey=xxx&entryURL=/civicrm/contribute/transact%3Fid%3D1', 'civicrm/contribute/transact?id=1');
  }

  /**
   * Handle onsite redirects with non-CiviCRM paths.
   */
  public function testOtherpathRedirect() {
    $this->tryRedirect('civicrm/contribute/transact?qfKey=xxx&entryURL=asdf', 'asdf');
  }

  /**
   * Handle offsite redirects without path as onsite redirects.
   */
  public function testOffsiteRedirectNoPath() {
    $this->tryRedirect('civicrm/contribute/transact?qfKey=xxx&entryURL=http://evil.example.com/', '');
  }

  /**
   * Handle offsite redirects with paths as onsite redirects.
   */
  public function testOffsiteRedirectWithPath() {
    $this->tryRedirect('civicrm/contribute/transact?qfKey=xxx&entryURL=http://evil.example.com/civicrm', 'civicrm');
  }

}
