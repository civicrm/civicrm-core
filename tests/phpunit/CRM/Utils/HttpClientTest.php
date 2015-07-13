<?php
require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Class CRM_Utils_HttpClientTest
 */
class CRM_Utils_HttpClientTest extends CiviUnitTestCase {

  const VALID_HTTP_URL = 'http://sandbox.civicrm.org/';
  const VALID_HTTP_REGEX = '/<html/';
  const VALID_HTTPS_URL = 'https://civicrm.org/INSTALL.mysql.txt';
  const VALID_HTTPS_REGEX = '/MySQL/';
  const SELF_SIGNED_HTTPS_URL = 'https://www-test.civicrm.org:4433/index.html';
  const SELF_SIGNED_HTTPS_REGEX = '/self-signed/';

  /**
   * @var string path to which we can store temp file
   */
  protected $tmpFile;

  /**
   * @var CRM_Utils_HttpClient
   */
  protected $client;

  /**
   * @var CRM_Utils_Cache_Arraycache
   */
  protected $cache;

  public function setUp() {
    parent::setUp();
    CRM_Utils_Time::resetTime();

    $this->tmpFile = $this->createTempDir() . '/example.txt';

    $result = civicrm_api('Setting', 'create', array(
      'version' => 3,
      'verifySSL' => TRUE,
    ));
    $this->assertAPISuccess($result);
    $this->cache = new CRM_Utils_Cache_Arraycache(array());
    $this->client = new CRM_Utils_HttpClient(NULL, $this->cache);
  }

  public function tearDown() {
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_setting WHERE name = 'verifySSL'");
    CRM_Core_Config::singleton(TRUE);
    parent::tearDown();
  }

  public function testFetchHttp() {
    $result = $this->client->fetch(self::VALID_HTTP_URL, $this->tmpFile);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $result);
    $this->assertRegExp(self::VALID_HTTP_REGEX, file_get_contents($this->tmpFile));
    $this->assertCacheSize(0);
  }

  public function testFetchHttps_valid() {
    $result = $this->client->fetch(self::VALID_HTTPS_URL, $this->tmpFile);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $result);
    $this->assertRegExp(self::VALID_HTTPS_REGEX, file_get_contents($this->tmpFile));
    $this->assertCacheSize(0);
  }

  public function testFetchHttps_invalid_verify() {
    $result = $this->client->fetch(self::SELF_SIGNED_HTTPS_URL, $this->tmpFile);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_DL_ERROR, $result);
    $this->assertEquals('', file_get_contents($this->tmpFile));
    $this->assertCacheSize(0);
  }

  public function testFetchHttps_invalid_noVerify() {
    $result = civicrm_api('Setting', 'create', array(
      'version' => 3,
      'verifySSL' => FALSE,
    ));
    $this->assertAPISuccess($result);

    $result = $this->client->fetch(self::SELF_SIGNED_HTTPS_URL, $this->tmpFile);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $result);
    $this->assertRegExp(self::SELF_SIGNED_HTTPS_REGEX, file_get_contents($this->tmpFile));
    $this->assertCacheSize(0);
  }

  public function testFetchHttp_badOutFile() {
    $result = $this->client->fetch(self::VALID_HTTP_URL, '/ba/d/path/too/utput');
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_WRITE_ERROR, $result);
    $this->assertCacheSize(0);
  }

  public function testGetHttp() {
    list($status, $data) = $this->client->get(self::VALID_HTTP_URL);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
    $this->assertRegExp(self::VALID_HTTP_REGEX, $data);
    $this->assertCacheSize(0);
  }

  public function testGetHttp_forceTtl() {
    // First call warms the cache
    list($status, $data) = $this->client->get(self::VALID_HTTP_URL, array(
      'forceTtl' => 30,
    ));
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
    $this->assertRegExp(self::VALID_HTTP_REGEX, $data);
    $this->assertCacheSize(1);

    // To make sure we actually receive a value from the cache
    // instead of redownloading, hack the cache.
    $all = $this->cache->getAll();
    foreach ($all as $k => $cacheLine) {
      $cacheLine['data'] = 'Sneakytooclever';
      $this->cache->set($k, $cacheLine);
    }

    // Now make a new request which hits the cache.
    list($status, $data) = $this->client->get(self::VALID_HTTP_URL, array(
      'forceTtl' => 30,
    ));
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
    $this->assertEquals('Sneakytooclever', $data); // the hacked value from our dirty cache
    $this->assertCacheSize(1);

    // And make a request with caching disabled
    list($status, $data) = $this->client->get(self::VALID_HTTP_URL);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
    $this->assertRegExp(self::VALID_HTTP_REGEX, $data); // the real value
    $this->assertCacheSize(1);
  }

  public function testGetHttps_valid() {
    list($status, $data) = $this->client->get(self::VALID_HTTPS_URL);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
    $this->assertRegExp(self::VALID_HTTPS_REGEX, $data);
    $this->assertCacheSize(0);
  }

  public function testGetHttps_invalid_verify() {
    list($status, $data) = $this->client->get(self::SELF_SIGNED_HTTPS_URL);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_DL_ERROR, $status);
    $this->assertEquals('', $data);
    $this->assertCacheSize(0);
  }

  public function testGetHttps_invalid_noVerify() {
    $result = civicrm_api('Setting', 'create', array(
      'version' => 3,
      'verifySSL' => FALSE,
    ));
    $this->assertAPISuccess($result);

    list($status, $data) = $this->client->get(self::SELF_SIGNED_HTTPS_URL);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
    $this->assertRegExp(self::SELF_SIGNED_HTTPS_REGEX, $data);
    $this->assertCacheSize(0);
  }

  public function assertCacheSize($expectedCount) {
    //$actualCount = CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_cache WHERE group_name = "HttpClient"');
    $actualCount = count($this->cache->getAll());
    $this->assertEquals($expectedCount, $actualCount);
  }

}
