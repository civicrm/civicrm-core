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
   * @param bool $fresh
   * @return CRM_Cxn_CiviCxnHttp
   */
  public static function singleton($fresh = FALSE) {
    if (self::$singleton === NULL || $fresh) {
      $cache = CRM_Utils_Cache::create(array(
        'name' => 'CiviCxnHttp',
        'type' => Civi::settings()->get('debug_enabled') ? 'ArrayCache' : array('SqlGroup', 'ArrayCache'),
        'prefetch' => FALSE,
      ));

      self::$singleton = new CRM_Cxn_CiviCxnHttp($cache);
    }
    return self::$singleton;
  }

  /**
   * @param CRM_Utils_Cache_Interface|NULL $cache
   *   The cache data store.
   */
  public function __construct($cache) {
    $this->cache = $cache;
  }

  /**
   * @param string $verb
   * @param string $url
   * @param string $blob
   * @param array $headers
   *   Array of headers (e.g. "Content-type" => "text/plain").
   * @return array
   *   array($headers, $blob, $code)
   */
  public function send($verb, $url, $blob, $headers = array()) {
    $lowVerb = strtolower($verb);

    if ($lowVerb === 'get' && $this->cache) {
      $cachePath = 'get/' . md5($url);
      $cacheLine = $this->cache->get($cachePath);
      if ($cacheLine && $cacheLine['expires'] > CRM_Utils_Time::getTimeRaw()) {
        return $cacheLine['data'];
      }
    }

    $result = parent::send($verb, $url, $blob, $headers);

    if ($lowVerb === 'get' && $this->cache) {
      $expires = CRM_Utils_Http::parseExpiration($result[0]);
      if ($expires !== NULL) {
        $cachePath = 'get/' . md5($url);
        $cacheLine = array(
          'url' => $url,
          'expires' => $expires,
          'data' => $result,
        );
        $this->cache->set($cachePath, $cacheLine);
      }
    }

    return $result;
  }

  protected function createStreamOpts($verb, $url, $blob, $headers) {
    $result = parent::createStreamOpts($verb, $url, $blob, $headers);

    $caConfig = CA_Config_Stream::probe(array(
      'verify_peer' => (bool) Civi::settings()->get('verifySSL'),
    ));
    if ($caConfig->isEnableSSL()) {
      $result['ssl'] = $caConfig->toStreamOptions();
    }
    if (!$caConfig->isEnableSSL() && preg_match('/^https:/', $url)) {
      CRM_Core_Error::fatal('Cannot fetch document - system does not support SSL');
    }

    return $result;
  }

}
