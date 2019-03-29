<?php

namespace Civi\Core;

use Civi\Core\Exception\UnknownAssetException;

/**
 * Class AssetBuilder
 * @package Civi\Core
 *
 * The AssetBuilder is used to manage semi-dynamic assets.
 * In normal production use, these assets are built on first
 * reference and then stored in a public-facing cache folder.
 * (In debug mode, these assets are constructed during every request.)
 *
 * There are generally two aspects to usage -- creating a URL
 * for the asset, and defining the content of the asset.
 *
 * For example, suppose we wanted to define a static file
 * named "api-fields.json" which lists all the fields of
 * all the API entities.
 *
 * @code
 * // Build a URL to `api-fields.json`.
 * $url = \Civi::service('asset_builder')->getUrl('api-fields.json');
 *
 * // Define the content of `api-fields.json`.
 * function hook_civicrm_buildAsset($asset, $params, &$mimeType, &$content) {
 *   if ($asset !== 'api-fields.json') return;
 *
 *   $entities = civicrm_api3('Entity', 'get', array());
 *   $fields = array();
 *   foreach ($entities['values'] as $entity) {
 *     $fields[$entity] = civicrm_api3($entity, 'getfields');
 *   }
 *
 *   $mimeType = 'application/json';
 *   $content = json_encode($fields);
 * }
 * @endCode
 *
 * Assets can be parameterized. Each combination of ($asset,$params)
 * will be cached separately. For example, we might want a copy of
 * 'api-fields.json' which only includes a handful of chosen entities.
 * Simply pass the chosen entities into `getUrl()`, then update
 * the definition to use `$params['entities']`, as in:
 *
 * @code
 * // Build a URL to `api-fields.json`.
 * $url = \Civi::service('asset_builder')->getUrl('api-fields.json', array(
 *   'entities' => array('Contact', 'Phone', 'Email', 'Address'),
 * ));
 *
 * // Define the content of `api-fields.json`.
 * function hook_civicrm_buildAsset($asset, $params, &$mimeType, &$content) {
 *   if ($asset !== 'api-fields.json') return;
 *
 *   $fields = array();
 *   foreach ($params['entities'] as $entity) {
 *     $fields[$entity] = civicrm_api3($entity, 'getfields');
 *   }
 *
 *   $mimeType = 'application/json';
 *   $content = json_encode($fields);
 * }
 * @endCode
 *
 * Note: These assets are designed to hold non-sensitive data, such as
 * aggregated JS or common metadata. There probably are ways to
 * secure it (e.g. alternative digest() calculations), but the
 * current implementation is KISS.
 */
class AssetBuilder {

  /**
   * @return array
   *   Array(string $value => string $label).
   */
  public static function getCacheModes() {
    return [
      '0' => ts('Disable'),
      '1' => ts('Enable'),
      'auto' => ts('Auto'),
    ];
  }

  protected $cacheEnabled;

  /**
   * AssetBuilder constructor.
   * @param $cacheEnabled
   */
  public function __construct($cacheEnabled = NULL) {
    if ($cacheEnabled === NULL) {
      $cacheEnabled = \Civi::settings()->get('assetCache');
      if ($cacheEnabled === 'auto') {
        $cacheEnabled = !\CRM_Core_Config::singleton()->debug;
      }
      $cacheEnabled = (bool) $cacheEnabled;
    }
    $this->cacheEnabled = $cacheEnabled;
  }

  /**
   * Determine if $name is a well-formed asset name.
   *
   * @param string $name
   * @return bool
   */
  public function isValidName($name) {
    return preg_match(';^[a-zA-Z0-9\.\-_/]+$;', $name)
    && strpos($name, '..') === FALSE
    && strpos($name, '.') !== FALSE;
  }

  /**
   * @param string $name
   *   Ex: 'angular.json'.
   * @param array $params
   * @return string
   *   URL.
   *   Ex: 'http://example.org/files/civicrm/dyn/angular.abcd1234abcd1234.json'.
   */
  public function getUrl($name, $params = []) {
    if (!$this->isValidName($name)) {
      throw new \RuntimeException("Invalid dynamic asset name");
    }

    if ($this->isCacheEnabled()) {
      $fileName = $this->build($name, $params);
      return $this->getCacheUrl($fileName);
    }
    else {
      return \CRM_Utils_System::url('civicrm/asset/builder', [
        'an' => $name,
        'ap' => $this->encode($params),
        'ad' => $this->digest($name, $params),
      ], TRUE, NULL, FALSE);
    }
  }

  /**
   * @param string $name
   *   Ex: 'angular.json'.
   * @param array $params
   * @return string
   *   URL.
   *   Ex: '/var/www/files/civicrm/dyn/angular.abcd1234abcd1234.json'.
   */
  public function getPath($name, $params = []) {
    if (!$this->isValidName($name)) {
      throw new \RuntimeException("Invalid dynamic asset name");
    }

    $fileName = $this->build($name, $params);
    return $this->getCachePath($fileName);
  }

  /**
   * Build the cached copy of an $asset.
   *
   * @param string $name
   *   Ex: 'angular.json'.
   * @param array $params
   * @param bool $force
   *   Build the asset anew, even if it already exists.
   * @return string
   *   File name (relative to cache folder).
   *   Ex: 'angular.abcd1234abcd1234.json'.
   * @throws UnknownAssetException
   */
  public function build($name, $params, $force = FALSE) {
    if (!$this->isValidName($name)) {
      throw new UnknownAssetException("Asset name is malformed");
    }
    $nameParts = explode('.', $name);
    array_splice($nameParts, -1, 0, [$this->digest($name, $params)]);
    $fileName = implode('.', $nameParts);
    if ($force || !file_exists($this->getCachePath($fileName))) {
      // No file locking, but concurrent writers should produce
      // the same data, so we'll just plow ahead.

      if (!file_exists($this->getCachePath())) {
        mkdir($this->getCachePath());
      }

      $rendered = $this->render($name, $params);
      file_put_contents($this->getCachePath($fileName), $rendered['content']);
      return $fileName;
    }
    return $fileName;
  }

  /**
   * Generate the content for a dynamic asset.
   *
   * @param string $name
   * @param array $params
   * @return array
   *   Array with keys:
   *     - statusCode: int, ex: 200.
   *     - mimeType: string, ex: 'text/html'.
   *     - content: string, ex: '<body>Hello world</body>'.
   * @throws \CRM_Core_Exception
   */
  public function render($name, $params = []) {
    if (!$this->isValidName($name)) {
      throw new UnknownAssetException("Asset name is malformed");
    }
    \CRM_Utils_Hook::buildAsset($name, $params, $mimeType, $content);
    if ($mimeType === NULL && $content === NULL) {
      throw new UnknownAssetException("Unrecognized asset name: $name");
    }
    // Beg your pardon, sir. Please may I have an HTTP response class instead?
    return [
      'statusCode' => 200,
      'mimeType' => $mimeType,
      'content' => $content,
    ];
  }

  /**
   * Clear out any cache files.
   */
  public function clear() {
    \CRM_Utils_File::cleanDir($this->getCachePath());
  }

  /**
   * Determine the local path of a cache file.
   *
   * @param string|NULL $fileName
   *   Ex: 'angular.abcd1234abcd1234.json'.
   * @return string
   *   URL.
   *   Ex: '/var/www/files/civicrm/dyn/angular.abcd1234abcd1234.json'.
   */
  protected function getCachePath($fileName = NULL) {
    // imageUploadDir has the correct functional properties but a wonky name.
    $suffix = ($fileName === NULL) ? '' : (DIRECTORY_SEPARATOR . $fileName);
    return
      \CRM_Utils_File::addTrailingSlash(\CRM_Core_Config::singleton()->imageUploadDir)
      . 'dyn' . $suffix;
  }

  /**
   * Determine the URL of a cache file.
   *
   * @param string|NULL $fileName
   *   Ex: 'angular.abcd1234abcd1234.json'.
   * @return string
   *   URL.
   *   Ex: 'http://example.org/files/civicrm/dyn/angular.abcd1234abcd1234.json'.
   */
  protected function getCacheUrl($fileName = NULL) {
    // imageUploadURL has the correct functional properties but a wonky name.
    $suffix = ($fileName === NULL) ? '' : ('/' . $fileName);
    return
      \CRM_Utils_File::addTrailingSlash(\CRM_Core_Config::singleton()->imageUploadURL, '/')
      . 'dyn' . $suffix;
  }

  /**
   * Create a unique identifier for the $params.
   *
   * This identifier is designed to avoid accidental cache collisions.
   *
   * @param string $name
   * @param array $params
   * @return string
   */
  protected function digest($name, $params) {
    // WISHLIST: For secure digest, generate+persist privatekey & call hash_hmac.
    ksort($params);
    $digest = md5(
      $name .
      \CRM_Core_Resources::singleton()->getCacheCode() .
      \CRM_Core_Config_Runtime::getId() .
      json_encode($params)
    );
    return $digest;
  }

  /**
   * Encode $params in a format that's optimized for shorter URLs.
   *
   * @param array $params
   * @return string
   */
  protected function encode($params) {
    if (empty($params)) {
      return '';
    }

    $str = json_encode($params);
    if (function_exists('gzdeflate')) {
      $str = gzdeflate($str);
    }
    return base64_encode($str);
  }

  /**
   * @param string $str
   * @return array
   */
  protected function decode($str) {
    if ($str === NULL || $str === FALSE || $str === '') {
      return [];
    }

    $str = base64_decode($str);
    if (function_exists('gzdeflate')) {
      $str = gzinflate($str);
    }
    return json_decode($str, TRUE);
  }

  /**
   * @return bool
   */
  public function isCacheEnabled() {
    return $this->cacheEnabled;
  }

  /**
   * @param bool|null $cacheEnabled
   * @return AssetBuilder
   */
  public function setCacheEnabled($cacheEnabled) {
    $this->cacheEnabled = $cacheEnabled;
    return $this;
  }

  /**
   * (INTERNAL ONLY)
   *
   * Execute a page-request for `civicrm/asset/builder`.
   */
  public static function pageRun() {
    // Beg your pardon, sir. Please may I have an HTTP response class instead?
    $asset = self::pageRender($_GET);
    if (function_exists('http_response_code')) {
      // PHP 5.4+
      http_response_code($asset['statusCode']);
    }
    else {
      header('X-PHP-Response-Code: ' . $asset['statusCode'], TRUE, $asset['statusCode']);
    }

    header('Content-Type: ' . $asset['mimeType']);
    echo $asset['content'];
    \CRM_Utils_System::civiExit();
  }

  /**
   * (INTERNAL ONLY)
   *
   * Execute a page-request for `civicrm/asset/builder`.
   *
   * @param array $get
   *   The _GET values.
   * @return array
   *   Array with keys:
   *     - statusCode: int, ex 200.
   *     - mimeType: string, ex 'text/html'.
   *     - content: string, ex '<body>Hello world</body>'.
   */
  public static function pageRender($get) {
    // Beg your pardon, sir. Please may I have an HTTP response class instead?
    try {
      $assets = \Civi::service('asset_builder');
      return $assets->render($get['an'], $assets->decode($get['ap']));
    }
    catch (UnknownAssetException $e) {
      return [
        'statusCode' => 404,
        'mimeType' => 'text/plain',
        'content' => $e->getMessage(),
      ];
    }
  }

}
