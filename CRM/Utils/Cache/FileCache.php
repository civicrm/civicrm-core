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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Class CRM_Utils_Cache_FileCache
 */
class CRM_Utils_Cache_FileCache implements CRM_Utils_Cache_Interface {

  // TODO Consider native implementation.
  use CRM_Utils_Cache_NaiveMultipleTrait;
  // TODO Native implementation
  use CRM_Utils_Cache_NaiveHasTrait;

  const DEFAULT_TIMEOUT = 3600;
  const DEFAULT_PREFIX = '';

  /**
   * Max key length to be used for file systems.
   *
   * This applies separately to each folder-part/file-part.
   *
   * PSR-16 only requires 64 chars. So anything longer is generous.
   */
  const MAX_KEY_LEN = 100;

  /**
   * The default timeout to use.
   *
   * @var int
   */
  protected $_timeout = self::DEFAULT_TIMEOUT;

  /**
   * The prefix prepended to cache keys.
   *
   * If we are using the same instance for multiple CiviCRM installs,
   * we must have a unique prefix for each install to prevent
   * the keys from clobbering each other.
   *
   * @var string
   */
  protected $_prefix = self::DEFAULT_PREFIX;

  /**
   * In-memory cache to optimize redundant get()s.
   *
   * @var array
   */
  protected $valueCache;

  /**
   * In-memory cache to optimize redundant get()s.
   *
   * @var array
   *   Note: expiresCache[$key]===NULL means cache-miss
   */
  protected $expiresCache;

  /**
   * Constructor.
   *
   * @param array $config
   *   An array of configuration params.
   *
   * @return \CRM_Utils_Cache_FileCache
   */
  public function __construct(&$config) {
    if (isset($config['timeout'])) {
      $this->_timeout = intval($config['timeout']);
    }
    if (isset($config['prefix'])) {
      $this->_prefix = $this->cleanPrefix($config['prefix']);
    }
    $this->valueCache = [];
    if ($config['prefetch'] ?? TRUE) {
      $this->prefetch();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value, $ttl = NULL) {
    CRM_Utils_Cache::assertValidKey($key);
    if (is_int($ttl) && $ttl <= 0) {
      return $this->delete($key);
    }

    $expires = CRM_Utils_Date::convertCacheTtlToExpires($ttl, self::DEFAULT_TIMEOUT);
    $serialized = serialize([
      'created' => time(),
      'expires' => $expires,
      'value' => $value,
      'key' => $key,
    ]);

    $key_path = $this->keyPath($key);
    if (!file_put_contents($this->getCacheFile($key_path), $serialized, LOCK_EX)) {
      return FALSE;
    }

    $this->valueCache[$key_path] = $this->reobjectify($value);
    $this->expiresCache[$key_path] = $expires;

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $default = NULL) {
    CRM_Utils_Cache::assertValidKey($key);

    $key_path = $this->keyPath($key);
    if (!isset($this->expiresCache[$key_path]) || time() >= $this->expiresCache[$key_path]) {
      $path = $this->getCacheFile($key_path);
      if (!file_exists($path)) {
        return $default;
      }
      $cache = $this->getContents($path);
      if (!$cache) {
        return $default;
      }
      $item = unserialize($cache);
      if ($item !== FALSE) {
        $this->expiresCache[$key_path] = $item['expires'];
        $this->valueCache[$key_path] = $item['value'];
      }
    }

    return (isset($this->expiresCache[$key_path]) && time() < $this->expiresCache[$key_path]) ? $this->reobjectify($this->valueCache[$key_path]) : $default;
  }

  /**
   * Get file contents
   *
   * @param string $path
   *
   * @return string
   */
  protected function getContents($path): string {
    $contents = '';

    $handle = fopen($path, 'rb');
    if ($handle) {
      try {
        if (flock($handle, LOCK_SH)) {
          clearstatcache(TRUE, $path);
          $contents = fread($handle, filesize($path) ?: 1);
          flock($handle, LOCK_UN);
        }
      } finally {
        fclose($handle);
      }
    }

    return $contents;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    CRM_Utils_Cache::assertValidKey($key);
    $key_path = $this->keyPath($key);
    $path = $this->getCacheFile($key_path);
    $success = TRUE;

    if (file_exists($path)) {
      try {
        if (@unlink($path)) {
          clearstatcache(FALSE, $path);
          unset($this->valueCache[$key_path]);
          unset($this->expiresCache[$key_path]);
        }
        else {
          $success = FALSE;
        }
      }
      catch (ErrorException) {
        $success = FALSE;
      }
    }
    return $success;
  }

  /**
   * {@inheritdoc}
   */
  public function flush() {
    return $this->clear();
  }

  /**
   * {@inheritdoc}
   */
  public function clear() {
    $files = \CRM_Utils_File::findFiles($this->getCacheDir() . $this->_prefix, '*.txt', TRUE);
    foreach ($files as $filename) {
      $path = $this->getCacheDir() . $this->_prefix . $filename;
      if (file_exists($path)) {
        if (@unlink($path)) {
          clearstatcache(FALSE, $path);
        }
      }
    }
    @rmdir($this->getCacheDir() . $this->_prefix);
    $this->valueCache = [];
    $this->expiresCache = [];

    return TRUE;
  }

  /**
   * Prefetch
   */
  public function prefetch() {
    $files = \CRM_Utils_File::findFiles($this->getCacheDir() . $this->_prefix, '*.txt', TRUE);
    $this->valueCache = [];
    $this->expiresCache = [];
    foreach ($files as $filename) {
      $path = $this->getCacheDir() . $this->_prefix . $filename;
      if (!is_file($path)) {
        continue;
      }
      $cache = $this->getContents($path);
      if (isset($cache)) {
        $item = CRM_Core_BAO_Cache::decode($cache);

        $key = substr($filename, 0, -4);
        $key_path = $this->_prefix . $key;
        $this->valueCache[$key_path] = $item['value'];
        $this->expiresCache[$key_path] = $item['expires'];
      }
    }
  }

  /**
   * @param mixed $value
   *
   * @return object
   */
  private function reobjectify($value) {
    return is_object($value) ? unserialize(serialize($value)) : $value;
  }

  /**
   * {@inheritdoc}
   */
  public function has($key) {
    CRM_Utils_Cache::assertValidKey($key);
    $key_path = $this->keyPath($key);
    $this->get($key);
    return isset($this->expiresCache[$key_path]) && time() < $this->expiresCache[$key_path];
  }

  /**
   * @param string $key
   *
   * @return string
   */
  protected function cleanKey($key) {
    return $this->shortenPathSegment($key);
  }

  /**
   * Full key path
   *   The prefix plus the key, used for the filename path and for temporary
   *   storage in the in-memory array.
   *
   * @param string $key
   *
   * @return string
   *   The prefix used as a directory plus the cleaned key.
   */
  protected function keyPath($key) {
    return $this->_prefix . $this->cleanKey($key);
  }

  /**
   * @param string $prefix
   *
   * @return string
   */
  protected function cleanPrefix($prefix) {
    $prefix = preg_replace('/\s+|\W+/', '_', trim($prefix, CRM_Utils_Cache::DELIMITER));
    $prefix = $this->shortenPathSegment($prefix);

    return DIRECTORY_SEPARATOR . $prefix . DIRECTORY_SEPARATOR;
  }

  /**
   * Shortens path segment so it fits within a maximum name length
   *
   * @param string $segment
   *
   * @return string
   *   A name shortened to a maximum length that will be safe for most file
   *   systems. As much of the original string as possible is preserved, with
   *   a unique hash attached.
   */
  protected function shortenPathSegment($segment) {
    // If name is particularly safe/readable, then pass-thru verbatim.
    if (strlen($segment) <= self::MAX_KEY_LEN && preg_match('/^[a-zA-Z0-9\_\-]+$/', $segment)) {
      // Exclude dots in order to prevent (a) collisions with 'hash.XXX.XXX.txt' and (b) accidental hiding in the directory-list.
      return $segment;
    }
    // If there is anything trixy in the name, then prefer hash-based identifier.
    else {
      return ('hash'
        . '.' . substr(preg_replace('/[^a-zA-Z0-9]/', '', $segment), 0, 20)
        // ^^ Give a mnemonic hint to admin browsing files
        . '.' . hash('sha256', $segment)
        // ^^ File-systems vary in case-sensitivity. Hex encoding is distinctive either way.
        );
    }
  }

  /**
   * Get the directory for cache.
   *
   * @return string
   *   The main cache directory.
   */
  protected function getCacheDir() {
    $dir = \Civi::paths()->getPath('[civicrm.private]/filecache');

    if (!is_dir($dir) && !CRM_Utils_File::createDir($dir, FALSE)) {
      $alertMessage = ts('Failed to create the cache directory. Please update the settings or file permissions.');
      try {
        CRM_Core_Session::setStatus($alertMessage);
      }
      catch (\Error $e) {
        echo $alertMessage;
      }
    }

    return $dir;
  }

  /**
   *
   * @param string $key_path
   *   The prefix plus clean key.
   *
   * @return string
   *   The full filename path.
   */
  protected function getCacheFile($key_path) {
    CRM_Utils_File::createDir(self::getCacheDir() . $this->_prefix, FALSE);

    return self::getCacheDir() . $key_path . '.txt';
  }

}
