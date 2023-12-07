<?php

namespace Civi\Core;

use Civi\Core\Exception\UnknownAssetException;

/**
 * Class AssetBuilder
 * @package Civi\Core
 * @service asset_builder
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
 * ```
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
 * ```
 *
 * Assets can be parameterized. Each combination of ($asset,$params)
 * will be cached separately. For example, we might want a copy of
 * 'api-fields.json' which only includes a handful of chosen entities.
 * Simply pass the chosen entities into `getUrl()`, then update
 * the definition to use `$params['entities']`, as in:
 *
 * ```
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
 * ```
 *
 * Note: These assets are designed to hold non-sensitive data, such as
 * aggregated JS or common metadata. There probably are ways to
 * secure it (e.g. alternative digest() calculations), but the
 * current implementation is KISS.
 */
class AssetBuilder extends \Civi\Core\Service\AutoService {

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

  /**
   * @var mixed
   */
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
    \CRM_Utils_Hook::getAssetUrl($name, $params);

    if (!$this->isValidName($name)) {
      throw new \RuntimeException("Invalid dynamic asset name");
    }

    if ($this->isCacheEnabled()) {
      $fileName = $this->build($name, $params);
      return $this->getCacheUrl($fileName);
    }
    else {
      return \CRM_Utils_System::url('civicrm/asset/builder', [
        // The 'an' and 'ad' provide hints for cache lifespan and debugging/inspection.
        'an' => $name,
        'ad' => $this->digest($name, $params),
        'aj' => \Civi::service('crypto.jwt')->encode([
          'asset' => [$name, $params],
          'exp' => 86400 * (floor(\CRM_Utils_Time::time() / 86400) + 2),
          // Caching-friendly TTL -- We want the URL to be stable for a decent amount of time.
        ], ['SIGN', 'WEAK_SIGN']),
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
      try {
        $rendered = $this->render($name, $params);
        file_put_contents($this->getCachePath($fileName), $rendered['content']);
        return $fileName;
      }
      catch (UnknownAssetException $e) {
        // unexpected error, log and continue
        \Civi::log()->error('Unexpected error while rendering a file in the AssetBuilder: ' . $e->getMessage(), ['exception' => $e]);
      }
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
   *
   * @param bool $removeDir Should folder itself be removed too.
   */
  public function clear($removeDir = TRUE) {
    \CRM_Utils_File::cleanDir($this->getCachePath(), $removeDir);
  }

  /**
   * Determine the local path of a cache file.
   *
   * @param string|null $fileName
   *   Ex: 'angular.abcd1234abcd1234.json'.
   * @return string
   *   URL.
   *   Ex: '/var/www/files/civicrm/dyn/angular.abcd1234abcd1234.json'.
   */
  protected function getCachePath($fileName = NULL) {
    // imageUploadDir has the correct functional properties but a wonky name.
    $suffix = ($fileName === NULL) ? '' : (DIRECTORY_SEPARATOR . $fileName);
    return \CRM_Utils_File::addTrailingSlash(\CRM_Core_Config::singleton()->imageUploadDir)
      . 'dyn' . $suffix;
  }

  /**
   * Determine the URL of a cache file.
   *
   * @param string|null $fileName
   *   Ex: 'angular.abcd1234abcd1234.json'.
   * @return string
   *   URL.
   *   Ex: 'http://example.org/files/civicrm/dyn/angular.abcd1234abcd1234.json'.
   */
  protected function getCacheUrl($fileName = NULL) {
    // imageUploadURL has the correct functional properties but a wonky name.
    $suffix = ($fileName === NULL) ? '' : ('/' . $fileName);
    return \CRM_Utils_File::addTrailingSlash(\CRM_Core_Config::singleton()->imageUploadURL, '/')
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
    \CRM_Utils_System::sendResponse(new \GuzzleHttp\Psr7\Response($asset['statusCode'], ['Content-Type' => $asset['mimeType']], $asset['content']));
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
      /** @var Assetbuilder $assets */
      $assets = \Civi::service('asset_builder');

      $obj = \Civi::service('crypto.jwt')->decode($get['aj'], ['SIGN', 'WEAK_SIGN']);
      $arr = json_decode(json_encode($obj), TRUE);
      return $assets->render($arr['asset'][0], $arr['asset'][1]);
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
