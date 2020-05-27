<?php

/**
 * Class CRM_Utils_HttpClientTest
 * @group headless
 */
class CRM_Utils_HttpClientTest extends CiviUnitTestCase {

  const VALID_HTTP_URL = 'http://httpclienttest-http.civicrm.org/index.html';
  const VALID_HTTP_REGEX = '/This is httpclienttest-http\./';
  const VALID_HTTPS_URL = 'https://httpclienttest-https.civicrm.org/index.html';
  const VALID_HTTPS_REGEX = '/This is httpclienttest-https\./';
  const SELF_SIGNED_HTTPS_URL = 'https://httpclienttest-selfsign.civicrm.org:4433/index.html';
  const SELF_SIGNED_HTTPS_REGEX = '/This is httpclienttest-selfsign\./';

  /**
   * @var string
   * Path to which we can store temp file
   */
  protected $tmpFile;

  /**
   * @var CRM_Utils_HttpClient
   */
  protected $client;

  public function setUp() {
    parent::setUp();

    $this->tmpFile = $this->createTempDir() . '/example.txt';

    $result = civicrm_api('Setting', 'create', [
      'version' => 3,
      'verifySSL' => TRUE,
    ]);
    $this->assertAPISuccess($result);
    $this->client = new CRM_Utils_HttpClient();
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
  }

  public function testFetchHttps_valid() {
    $result = $this->client->fetch(self::VALID_HTTPS_URL, $this->tmpFile);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $result);
    $this->assertRegExp(self::VALID_HTTPS_REGEX, file_get_contents($this->tmpFile));
  }

  public function testFetchHttps_invalid_verify() {
    $result = $this->client->fetch(self::SELF_SIGNED_HTTPS_URL, $this->tmpFile);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_DL_ERROR, $result);
    $this->assertEquals('', file_get_contents($this->tmpFile));
  }

  public function testFetchHttps_invalid_noVerify() {
    $result = civicrm_api('Setting', 'create', [
      'version' => 3,
      'verifySSL' => FALSE,
    ]);
    $this->assertAPISuccess($result);

    $result = $this->client->fetch(self::SELF_SIGNED_HTTPS_URL, $this->tmpFile);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $result);
    $this->assertRegExp(self::SELF_SIGNED_HTTPS_REGEX, file_get_contents($this->tmpFile));
  }

  public function testFetchHttp_badOutFile() {
    $result = $this->client->fetch(self::VALID_HTTP_URL, '/ba/d/path/too/utput');
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_WRITE_ERROR, $result);
  }

  public function testGetHttp() {
    list($status, $data) = $this->client->get(self::VALID_HTTP_URL);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
    $this->assertRegExp(self::VALID_HTTP_REGEX, $data);
  }

  public function testGetHttps_valid() {
    list($status, $data) = $this->client->get(self::VALID_HTTPS_URL);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
    $this->assertRegExp(self::VALID_HTTPS_REGEX, $data);
  }

  public function testGetHttps_invalid_verify() {
    list($status, $data) = $this->client->get(self::SELF_SIGNED_HTTPS_URL);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_DL_ERROR, $status);
    $this->assertEquals('', $data);
  }

  public function testGetHttps_invalid_noVerify() {
    $result = civicrm_api('Setting', 'create', [
      'version' => 3,
      'verifySSL' => FALSE,
    ]);
    $this->assertAPISuccess($result);

    list($status, $data) = $this->client->get(self::SELF_SIGNED_HTTPS_URL);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
    $this->assertRegExp(self::SELF_SIGNED_HTTPS_REGEX, $data);
  }

}
