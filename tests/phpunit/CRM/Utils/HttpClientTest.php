<?php
require_once 'CiviTest/CiviUnitTestCase.php';
class CRM_Utils_HttpClientTest extends CiviUnitTestCase {

  const VALID_HTTP_URL = 'http://civicrm.org/INSTALL.mysql.txt';
  const VALID_HTTP_REGEX = '/MySQL/';
  const VALID_HTTPS_URL = 'https://drupal.org/INSTALL.mysql.txt';
  const VALID_HTTPS_REGEX = '/MySQL/';
  const SELF_SIGNED_HTTPS_URL = 'https://self-signed.onebitwise.com:4443/';
  const SELF_SIGNED_HTTPS_REGEX = '/self-signed/';

  /**
   * @var string path to which we can store temp file
   */
  protected $tmpFile;

  public function setUp() {
    parent::setUp();

    $this->tmpFile = $this->createTempDir() . '/example.txt';

    $result = civicrm_api('Setting', 'create', array(
      'version' => 3,
      'verifySSL' => TRUE,
    ));
    $this->assertAPISuccess($result);
  }

  public function tearDown() {
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_setting WHERE name = 'verifySSL'");
    CRM_Core_Config::singleton(TRUE);
    parent::tearDown();
  }

  function testFetchHttp() {
    $result = CRM_Utils_HttpClient::fetch(self::VALID_HTTP_URL, $this->tmpFile);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $result);
    $this->assertRegExp(self::VALID_HTTP_REGEX, file_get_contents($this->tmpFile));
  }

  function testFetchHttps_valid() {
    $result = CRM_Utils_HttpClient::fetch(self::VALID_HTTPS_URL, $this->tmpFile);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $result);
    $this->assertRegExp(self::VALID_HTTPS_REGEX, file_get_contents($this->tmpFile));
  }

  function testFetchHttps_invalid_verify() {
    $result = CRM_Utils_HttpClient::fetch(self::SELF_SIGNED_HTTPS_URL, $this->tmpFile);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_DL_ERROR, $result);
    $this->assertEquals('', file_get_contents($this->tmpFile));
  }

  function testFetchHttps_invalid_noVerify() {
    $result = civicrm_api('Setting', 'create', array(
      'version' => 3,
      'verifySSL' => FALSE,
    ));
    $this->assertAPISuccess($result);

    $result = CRM_Utils_HttpClient::fetch(self::SELF_SIGNED_HTTPS_URL, $this->tmpFile);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $result);
    $this->assertRegExp(self::SELF_SIGNED_HTTPS_REGEX, file_get_contents($this->tmpFile));
  }

  function testFetchHttp_badOutFile() {
    $result = CRM_Utils_HttpClient::fetch(self::VALID_HTTP_URL, '/ba/d/path/too/utput');
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_WRITE_ERROR, $result);
  }

}
