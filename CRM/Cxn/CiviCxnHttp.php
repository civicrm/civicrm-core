<?php

/**
 * Class CRM_Cxn_CiviCxnHttp
 *
 * This extends the PhpHttp client used by CiviConnect and adds:
 *  - Force-cache support for GET requests
 *  - Compliance with SSL policy
 */
class CRM_Cxn_CiviCxnHttp extends \Civi\Cxn\Rpc\Http\PhpHttp {

  protected static $singleton = NULL;

  /**
   * @var CRM_Utils_Cache_Interface|null
   */
  protected $cache;

  /**
   * Singleton object.
   *
   * @param bool $fresh
   *
   * @return CRM_Cxn_CiviCxnHttp
   * @throws \CRM_Core_Exception
   */
  public static function singleton($fresh = FALSE) {
    if (self::$singleton === NULL || $fresh) {
      $cache = CRM_Utils_Cache::create([
        'name' => 'CiviCxnHttp',
        'type' => Civi::settings()->get('debug_enabled') ? 'ArrayCache' : ['SqlGroup', 'ArrayCache'],
        'prefetch' => FALSE,
      ]);

      self::$singleton = new CRM_Cxn_CiviCxnHttp($cache);
    }
    return self::$singleton;
  }

  /**
   * The cache data store.
   *
   * @param CRM_Utils_Cache_Interface|null $cache
   */
  public function __construct($cache) {
    $this->cache = $cache;
  }

  /**
   * Send.
   *
   * @param string $verb
   * @param string $url
   * @param string $blob
   * @param array $headers
   *   Array of headers (e.g. "Content-type" => "text/plain").
   * @return array
   *   array($headers, $blob, $code)
   */
  public function send($verb, $url, $blob, $headers = []) {
    $lowVerb = strtolower($verb);

    if ($lowVerb === 'get' && $this->cache) {
      $cachePath = 'get_' . md5($url);
      $cacheLine = $this->cache->get($cachePath);
      if ($cacheLine && $cacheLine['expires'] > CRM_Utils_Time::getTimeRaw()) {
        return $cacheLine['data'];
      }
    }

    $result = parent::send($verb, $url, $blob, $headers);

    if ($lowVerb === 'get' && $this->cache) {
      $expires = CRM_Utils_Http::parseExpiration($result[0]);
      if ($expires !== NULL) {
        $cachePath = 'get_' . md5($url);
        $cacheLine = [
          'url' => $url,
          'expires' => $expires,
          'data' => $result,
        ];
        $this->cache->set($cachePath, $cacheLine);
      }
    }

    return $result;
  }

  /**
   * Create stream options.
   *
   * @param string $verb
   * @param string $url
   * @param string $blob
   * @param array $headers
   *
   * @return array
   * @throws \Exception
   */
  protected function createStreamOpts($verb, $url, $blob, $headers) {
    $result = parent::createStreamOpts($verb, $url, $blob, $headers);

    $caConfig = CA_Config_Stream::probe([
      'verify_peer' => (bool) Civi::settings()->get('verifySSL'),
    ]);
    if ($caConfig->isEnableSSL()) {
      $result['ssl'] = $caConfig->toStreamOptions();
    }
    if (!$caConfig->isEnableSSL() && preg_match('/^https:/', $url)) {
      throw new CRM_Core_Exception('Cannot fetch document - system does not support SSL');
    }

    return $result;
  }

  /**
   * Get cache.
   *
   * @return \CRM_Utils_Cache_Interface|null
   */
  public function getCache() {
    return $this->cache;
  }

}
