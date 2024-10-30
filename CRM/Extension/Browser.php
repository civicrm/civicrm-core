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

use GuzzleHttp\Exception\GuzzleException;

/**
 * This class glues together the various parts of the extension
 * system.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Extension_Browser {

  /**
   * Relative path below remote repository URL for single extensions file.
   */
  const SINGLE_FILE_PATH = '/single';

  /**
   * @var GuzzleHttp\Client
   */
  protected $guzzleClient;

  /**
   * @var string
   */
  public $repoUrl;

  /**
   * @var string
   */
  public $indexPath;

  /**
   * @var array
   */
  protected $_remotesDiscovered;

  /**
   * @return \GuzzleHttp\Client
   */
  public function getGuzzleClient(): \GuzzleHttp\Client {
    return $this->guzzleClient ?? new \GuzzleHttp\Client();
  }

  /**
   * @param \GuzzleHttp\Client $guzzleClient
   */
  public function setGuzzleClient(\GuzzleHttp\Client $guzzleClient) {
    $this->guzzleClient = $guzzleClient;
  }

  /**
   * @param string $repoUrl
   *   URL of the remote repository.
   * @param string $indexPath
   *   Relative path of the 'index' file within the repository.
   */
  public function __construct($repoUrl, $indexPath) {
    $this->repoUrl = $repoUrl;
    $this->indexPath = empty($indexPath) ? self::SINGLE_FILE_PATH : $indexPath;
  }

  /**
   * Determine whether the system policy allows downloading new extensions.
   *
   * This is reflection of *policy* and *intent*; it does not indicate whether
   * the browser will actually *work*. For that, see checkRequirements().
   *
   * @return bool
   */
  public function isEnabled() {
    return (FALSE !== $this->getRepositoryUrl());
  }

  /**
   * @return string
   */
  public function getRepositoryUrl() {
    return $this->repoUrl;
  }

  /**
   * Refresh the cache of remotely-available extensions.
   */
  public function refresh() {
    \Civi::cache('extension_browser')->flush();
  }

  /**
   * Determine whether downloading is supported.
   *
   * @return array
   *   List of error messages; empty if OK.
   */
  public function checkRequirements() {
    if (!$this->isEnabled()) {
      return [];
    }

    // We used to check for the cache filesystem permissions, but it is now stored in DB
    // If no new requirements have come up, consider removing this function after CiviCRM 5.60.
    // The tests may need to be updated as well (tests/phpunit/CRM/Extension/BrowserTest.php).
    $errors = [];
    return $errors;
  }

  /**
   * Get a list of all available extensions.
   *
   * @return CRM_Extension_Info[]
   *   ($key => CRM_Extension_Info)
   */
  public function getExtensions() {
    if (!$this->isEnabled() || count($this->checkRequirements())) {
      return [];
    }

    $exts = [];
    $remote = $this->_discoverRemote();

    if (is_array($remote)) {
      foreach ($remote as $dc => $e) {
        $exts[$e->key] = $e;
      }
    }

    return $exts;
  }

  /**
   * Get a description of a particular extension.
   *
   * @param string $key
   *   Fully-qualified extension name.
   *
   * @return CRM_Extension_Info|NULL
   */
  public function getExtension($key) {
    // TODO optimize performance -- we don't need to fetch/cache the entire repo
    $exts = $this->getExtensions();
    if (array_key_exists($key, $exts)) {
      return $exts[$key];
    }
    else {
      return NULL;
    }
  }

  /**
   * @return CRM_Extension_Info[]
   * @throws CRM_Extension_Exception_ParseException
   */
  private function _discoverRemote() {
    $remotes = json_decode($this->grabCachedJson(), TRUE);
    $this->_remotesDiscovered = [];

    foreach ((array) $remotes as $id => $xml) {
      $ext = CRM_Extension_Info::loadFromString($xml);
      $this->_remotesDiscovered[] = $ext;
    }

    return $this->_remotesDiscovered;
  }

  /**
   * Loads the extensions data from the cache file. If it is empty
   * or doesn't exist, try fetching from remote instead.
   *
   * @return string
   */
  private function grabCachedJson() {
    $cacheKey = $this->getCacheKey();
    $json = \Civi::cache('extension_browser')->get($cacheKey);
    if ($json === NULL) {
      $json = $this->grabRemoteJson();
    }
    return $json;
  }

  /**
   * Connects to public server and grabs the list of publicly available
   * extensions.
   *
   * @return string
   * @throws \CRM_Extension_Exception
   */
  private function grabRemoteJson() {
    set_error_handler(['CRM_Extension_Browser', 'downloadError']);

    if (FALSE === $this->getRepositoryUrl()) {
      // don't check if the user has configured civi not to check an external
      // url for extensions. See CRM-10575.
      return '';
    }

    $url = $this->getRepositoryUrl() . $this->indexPath;
    $client = $this->getGuzzleClient();
    // Timeout should be a minimum of 10 seconds. See https://lab.civicrm.org/infra/ops/-/issues/1009.
    $timeout = max(10, \Civi::settings()->get('http_timeout'));
    try {
      $response = $client->request('GET', $url, [
        'timeout' => $timeout,
      ]);
    }
    catch (GuzzleException $e) {
      throw new CRM_Extension_Exception(ts('It is not possible to contact the CiviCRM extensions directory. You may be missing out on the latest updates to extensions. Check that you can view the <a %1>extension feed</a>. If that works check that your webserver can make external HTTP requests.', [1 => 'href="' . $this->getRepositoryUrl() . '"']), 'connection_error');
    }
    finally {
      restore_error_handler();
    }

    if ($response->getStatusCode() !== 200) {
      throw new CRM_Extension_Exception(ts('It is not possible to contact the CiviCRM extensions directory. You may be missing out on the latest updates to extensions. Check that you can view the <a %1>extension feed</a>. If that works check that your webserver can make external HTTP requests.', [1 => 'href="' . $this->getRepositoryUrl() . '"']), 'connection_error');
    }

    $json = $response->getBody()->getContents();
    $cacheKey = $this->getCacheKey();
    \Civi::cache('extension_browser')->set($cacheKey, $json);
    return $json;
  }

  /**
   * Returns a cache key based on the repository URL, which can be updated
   * by admins in civicrm.settings.php or passed as a command-line option to cv.
   */
  private function getCacheKey() {
    return 'extdir_' . md5($this->getRepositoryUrl());
  }

  /**
   * A dummy function required for suppressing download errors.
   *
   * @param $errorNumber
   * @param $errorString
   */
  public static function downloadError($errorNumber, $errorString) {
  }

}
