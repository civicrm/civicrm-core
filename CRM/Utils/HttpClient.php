<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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

/**
 * This class handles HTTP downloads
 *
 * FIXME: fetch() and get() report errors differently -- e.g.
 * fetch() returns fatal and get() returns an error code. Should
 * refactor both (or get a third-party HTTP library) but don't
 * want to deal with that so late in the 4.3 dev cycle.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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
    if (!function_exists('curl_init')) {
      CRM_Core_Error::fatal('Cannot install this extension - curl is not installed!');
    }

    list($ch, $caConfig) = $this->createCurl($remoteFile);
    if (preg_match('/^https:/', $remoteFile) && !$caConfig->isEnableSSL()) {
      CRM_Core_Error::fatal('Cannot install this extension - does not support SSL');
    }

    $fp = @fopen($localFile, "w");
    if (!$fp) {
      return self::STATUS_WRITE_ERROR;
    }
    curl_setopt($ch, CURLOPT_FILE, $fp);

    curl_exec($ch);
    if (curl_errno($ch)) {
      return self::STATUS_DL_ERROR;
    }
    else {
      curl_close($ch);
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
    if (!function_exists('curl_init')) {
      // CRM-13805
      CRM_Core_Session::setStatus(
        ts('As a result, actions like retrieving the CiviCRM news feed will fail. Talk to your server administrator or hosting company to rectify this.'),
        ts('Curl is not installed')
      );
      return [self::STATUS_DL_ERROR, NULL];
    }

    list($ch, $caConfig) = $this->createCurl($remoteFile);

    if (preg_match('/^https:/', $remoteFile) && !$caConfig->isEnableSSL()) {
      // CRM_Core_Error::fatal('Cannot install this extension - does not support SSL');
      return [self::STATUS_DL_ERROR, NULL];
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($ch);
    if (curl_errno($ch)) {
      return [self::STATUS_DL_ERROR, $data];
    }
    else {
      curl_close($ch);
    }

    return [self::STATUS_OK, $data];
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
    if (!function_exists('curl_init')) {
      //CRM_Core_Error::fatal('Cannot install this extension - curl is not installed!');
      return [self::STATUS_DL_ERROR, NULL];
    }

    list($ch, $caConfig) = $this->createCurl($remoteFile);

    if (preg_match('/^https:/', $remoteFile) && !$caConfig->isEnableSSL()) {
      // CRM_Core_Error::fatal('Cannot install this extension - does not support SSL');
      return [self::STATUS_DL_ERROR, NULL];
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POST, count($params));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    $data = curl_exec($ch);
    if (curl_errno($ch)) {
      return [self::STATUS_DL_ERROR, $data];
    }
    else {
      curl_close($ch);
    }

    return [self::STATUS_OK, $data];
  }

  /**
   * @param string $remoteFile
   * @return array
   *   (0 => resource, 1 => CA_Config_Curl)
   */
  protected function createCurl($remoteFile) {
    $caConfig = CA_Config_Curl::probe([
      'verify_peer' => (bool) Civi::settings()->get('verifySSL'),
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $remoteFile);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    if ($this->isRedirectSupported()) {
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    }
    if ($this->connectionTimeout !== NULL) {
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectionTimeout);
    }
    if (preg_match('/^https:/', $remoteFile) && $caConfig->isEnableSSL()) {
      curl_setopt_array($ch, $caConfig->toCurlOptions());
    }

    return [$ch, $caConfig];
  }

  /**
   * @return bool
   */
  public function isRedirectSupported() {
    return (ini_get('open_basedir') == '') && (ini_get('safe_mode') == 'Off' || ini_get('safe_mode') == '' || ini_get('safe_mode') === FALSE);
  }

}
