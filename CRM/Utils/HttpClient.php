<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

require_once dirname(dirname(__DIR__)) . '/packages/vendor/autoload.php';

/**
 * This class handles HTTP downloads
 *
 * FIXME: fetch() and get() report errors differently -- e.g.
 * fetch() returns fatal and get() returns an error code. Should
 * refactor both
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class CRM_Utils_HttpClient {

  const STATUS_OK = 'ok';
  const STATUS_WRITE_ERROR = 'write-error';
  const STATUS_DL_ERROR = 'dl-error';

  /**
   * @var CRM_Utils_HttpClient
   */
  protected static $singleton;

  /**
   * @var int|NULL
   *   seconds; or NULL to use system default
   */
  protected $connectionTimeout;


  /**
   * @var string
   * SSL Certificate Path
   */
  protected $sslCertificatePath;

  /**
   * @return CRM_Utils_HttpClient
   */
  public static function singleton() {
    if (!self::$singleton) {
      self::$singleton = new CRM_Utils_HttpClient();
    }
    return self::$singleton;
  }

  /**
   * @param null $connectionTimeout
   */
  public function __construct($connectionTimeout = NULL) {
    $this->connectionTimeout = $connectionTimeout;
    $this->sslCertificatePath = dirname(dirname(__DIR__)) . '/packages/CA/Config/cacert.pem';
  }

  /**
   * Download the remote zipfile.
   *
   * @param string $remoteFile
   *   URL of a .zip file.
   * @param string $localFile
   *   Path at which to store the .zip file.
   * @return STATUS_OK|STATUS_WRITE_ERROR|STATUS_DL_ERROR
   */
  public function fetch($remoteFile, $localFile) {
    // Download extension zip file ...
    $caConfig = $this->getCaConfig();
    if (preg_match('/^https:/', $remoteFile) && !$caConfig->isEnableSSL()) {
      CRM_Core_Error::fatal('Cannot install this extension - does not support SSL');
    }

    $fp = @fopen($localFile, "w");
    if (!$fp) {
      // Fixme: throw error instead of setting message
      CRM_Core_Session::setStatus(ts('Unable to write to %1.<br />Is the location writable?', array(1 => $localFile)), ts('Write Error'), 'error');
      return self::STATUS_WRITE_ERROR;
    }

    $guzzleClient = new GuzzleHttp\Client();
    try {
      $guzzleClient->get($remoteFile, array(
        'headers' => array('Accept-Encoding' => 'gzip'),
        'debug' => FALSE,
        'save_to' => $localFile,
        'verify' => $this->sslCertificatePath,
      ));
    }
    catch (GuzzleHttp\Exception\ClientException $e) {
      $response = $e->getResponse();
      $errorMessage = $response->getStatusCode() . " - " . $response->getReasonPhrase();
      CRM_Core_Session::setStatus(ts('Unable to download extension from %1. Error Message: %2',
        array(1 => $remoteFile, 2 => $errorMessage)), ts('Extension Download Error'), 'error');
      return self::STATUS_DL_ERROR;
    }
    fclose($fp);

    return self::STATUS_OK;
  }

  /**
   * Send an HTTP GET for a remote resource.
   *
   * @param string $remoteFile
   *   URL of remote file.
   * @return array
   *   array(0 => STATUS_OK|STATUS_DL_ERROR, 1 => string)
   */
  public function get($remoteFile) {
    // Download extension zip file ...
    $caConfig = $this->getCaConfig();
    if (preg_match('/^https:/', $remoteFile) && !$caConfig->isEnableSSL()) {
      // CRM_Core_Error::fatal('Cannot install this extension - does not support SSL');
      return array(self::STATUS_DL_ERROR, NULL);
    }

    $guzzleClient = new GuzzleHttp\Client();
    try {
      $data = $guzzleClient->get($remoteFile, array(
        'headers' => array('Accept-Encoding' => 'gzip'),
        'debug' => FALSE,
        'verify' => $this->sslCertificatePath,
      ))->getBody();
      return array(self::STATUS_OK, $data);
    }
    catch (GuzzleHttp\Exception\ClientException $e) {
      $response = $e->getResponse();
      $data = $response->getStatusCode() . " - " . $response->getReasonPhrase();
      return array(self::STATUS_DL_ERROR, $data);
    }
  }

  /**
   * Send an HTTP POST for a remote resource.
   *
   * @param string $remoteFile
   *   URL of a .zip file.
   * @param array $params
   *
   * @return array
   *   array(0 => STATUS_OK|STATUS_DL_ERROR, 1 => string)
   */
  public function post($remoteFile, $params) {
    // Download extension zip file ...
    $caConfig = $this->getCaConfig();
    if (preg_match('/^https:/', $remoteFile) && !$caConfig->isEnableSSL()) {
      // CRM_Core_Error::fatal('Cannot install this extension - does not support SSL');
      return array(self::STATUS_DL_ERROR, NULL);
    }

    $guzzleClient = new GuzzleHttp\Client();
    try {
      $data = $guzzleClient->post($remoteFile, array(
        'headers' => array('Accept-Encoding' => 'gzip'),
        'debug' => FALSE,
        'verify' => $this->sslCertificatePath,
        'body' => $params,
      ))->getBody();
      return array(self::STATUS_OK, $data);
    }
    catch (GuzzleHttp\Exception\ClientException $e) {
      $response = $e->getResponse();
      $data = $response->getStatusCode() . " - " . $response->getReasonPhrase();
      return array(self::STATUS_DL_ERROR, $data);
    }
  }

  /**
   * @return CA_Config_Curl
   */
  protected function getCaConfig() {
    $caConfig = CA_Config_Curl::probe(array(
      'verify_peer' => (bool) CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'verifySSL'),
    ));
    return $caConfig;
  }

}
