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
   * Maximum age (in seconds) of an abandoned temporary file.
   *
   * Writes go to a temporary file which is then renamed over the real file.
   * If a process dies before renaming, garbage collection removes the leftover.
   */
  const STALE_TEMP_TIMEOUT = 3600;

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
   * The resolved cache directory, or NULL if not yet resolved.
   *
   * @var string|null
   */
  protected $cacheDir = NULL;

  /**
   * Whether the directory for this cache's prefix has been created.
   *
   * @var bool
   */
  protected $dirReady = FALSE;

  /**
   * Whether a failure to create the cache directory has been logged.
   *
   * @var bool
   */
  protected $dirAlerted = FALSE;

  /**
   * Constructor.
   *
   * @param array $config
   *   An array of configuration params.
   *   - prefetch: bool, whether to read the whole cache group on construction.
   *     Default FALSE. Enable it only when the caller expects to read most of
   *     the keys in the group, e.g. the extension system.
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
    if ($config['prefetch'] ?? FALSE) {
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

    $expires = CRM_Utils_Date::convertCacheTtlToExpires($ttl, $this->_timeout);
    $serialized = serialize([
      'created' => time(),
      'expires' => $expires,
      'value' => $value,
      'key' => $key,
    ]);

    $key_path = $this->keyPath($key);
    if (!$this->ensureCacheDir()) {
      return FALSE;
    }
    if (!$this->writeAtomically($this->getCacheFile($key_path), $serialized)) {
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
   * Replace a cache file atomically.
   *
   * The data is written to a temporary file in the same directory and then
   * renamed over the target. Readers see either the old or the new file,
   * never a partial write.
   *
   * @param string $path
   * @param string $data
   *
   * @return bool
   */
  private function writeAtomically(string $path, string $data): bool {
    try {
      $suffix = $this->randomSuffix();
    }
    catch (\Throwable $e) {
      // A cache write is optional. Fail it (a miss) rather than fall back to a
      // name that is only unique with high probability.
      return FALSE;
    }
    $tempPath = $path . '.' . $suffix . '.tmp';

    // The directory may have been removed by a concurrent clear().
    if (!$this->writeTempFile($tempPath, $data)) {
      if (!$this->ensureCacheDir(TRUE) || !$this->writeTempFile($tempPath, $data)) {
        @unlink($tempPath);
        return FALSE;
      }
    }

    // The rename is the commit point: readers see the old file or the new one,
    // never a partial write. There is no fsync: a power loss can leave an
    // empty or stale entry (a miss), which is acceptable for a cache.
    if ($this->renameFile($tempPath, $path)) {
      return TRUE;
    }

    // This is a state check, not an errno check: rename() does not expose
    // errno. A concurrent clear() removes the temp file, the directory, or
    // both. If both are still there, the failure is something else
    // (permissions, a full disk, a directory in place of the target), and
    // rewriting the temp will not help.
    if (is_dir(dirname($tempPath)) && file_exists($tempPath)) {
      @unlink($tempPath);
      return FALSE;
    }
    if (!$this->ensureCacheDir(TRUE) || !$this->writeTempFile($tempPath, $data)) {
      @unlink($tempPath);
      return FALSE;
    }
    if ($this->renameFile($tempPath, $path)) {
      return TRUE;
    }

    @unlink($tempPath);
    return FALSE;
  }

  /**
   * Write a temporary cache file.
   *
   * @param string $path
   * @param string $data
   *
   * @return bool
   *   TRUE if the full payload was written.
   */
  private function writeTempFile(string $path, string $data): bool {
    if (@file_put_contents($path, $data) === strlen($data)) {
      return TRUE;
    }
    @unlink($path);
    return FALSE;
  }

  /**
   * Generate a unique suffix for a temporary file.
   *
   * Split out so tests can simulate an unavailable CSPRNG.
   *
   * @return string
   */
  protected function randomSuffix(): string {
    return bin2hex(random_bytes(6));
  }

  /**
   * Rename a cache file.
   *
   * Split out so tests can simulate a failed rename.
   *
   * @param string $from
   * @param string $to
   *
   * @return bool
   */
  protected function renameFile(string $from, string $to): bool {
    return @rename($from, $to);
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
    $groupDir = $this->getCacheDir() . $this->_prefix;
    $files = \CRM_Utils_File::findFiles($groupDir, '*.txt', TRUE);
    foreach ($files as $filename) {
      @unlink($groupDir . $filename);
    }
    foreach (glob($groupDir . '*.tmp') ?: [] as $tempFile) {
      @unlink($tempFile);
    }
    @rmdir($groupDir);
    $this->dirReady = FALSE;
    $this->valueCache = [];
    $this->expiresCache = [];

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    $garbage_collected = FALSE;
    $dir = $this->getCacheDir();

    $files = \CRM_Utils_File::findFiles($dir, '*.txt', TRUE);
    foreach ($files as $filename) {
      $path = $dir . '/' . $filename;
      if (!is_file($path)) {
        continue;
      }
      $cache = $this->getContents($path);
      if (isset($cache)) {
        $item = CRM_Core_BAO_Cache::decode($cache);
        if ($item['expires'] < CRM_Utils_Time::time()) {
          if (@unlink($path)) {
            clearstatcache(FALSE, $path);
            $garbage_collected = TRUE;
          }
        }
      }
    }

    // Remove temporary files left behind by a process that died mid-write.
    $tempFiles = \CRM_Utils_File::findFiles($dir, '*.tmp', TRUE);
    foreach ($tempFiles as $filename) {
      $path = $dir . '/' . $filename;
      if ((@filemtime($path) ?: 0) < CRM_Utils_Time::time() - self::STALE_TEMP_TIMEOUT) {
        if (@unlink($path)) {
          $garbage_collected = TRUE;
        }
      }
    }

    return $garbage_collected;
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
    if ($this->cacheDir === NULL) {
      $this->cacheDir = \Civi::paths()->getPath('[civicrm.private]/filecache');
    }
    return $this->cacheDir;
  }

  /**
   * Ensure that the directory for this cache's prefix exists.
   *
   * This is only needed when writing; reads tolerate a missing directory (the
   * entry is simply absent).
   *
   * @param bool $force
   *   Re-check the directory even if it was already created by this instance.
   *
   * @return bool
   */
  protected function ensureCacheDir(bool $force = FALSE): bool {
    if ($this->dirReady && !$force) {
      return TRUE;
    }
    $dir = $this->getCacheDir() . $this->_prefix;
    if ($force) {
      // PHP caches the last stat result. A concurrent clear() may have removed
      // the directory since this instance checked it, so force a real stat
      // instead of trusting the cache.
      clearstatcache();
    }
    if (!is_dir($dir)) {
      \CRM_Utils_File::createDir($dir, FALSE);
    }
    // createDir() returns NULL when the directory already exists, so check the
    // directory itself. Another process may have created it since the first
    // check; that is a success, not a failure.
    if (!is_dir($dir)) {
      $this->logDirectoryFailure($dir);
      return FALSE;
    }
    $this->dirReady = TRUE;
    return TRUE;
  }

  /**
   * Report that the cache directory could not be created.
   *
   * This is a system configuration problem, so it belongs in the log rather
   * than in a status message shown to whichever user triggers the write.
   * Write through Civi when the container is available; otherwise use PHP's
   * error log, because the Civi logger may depend on services that are not
   * booted yet.
   *
   * @param string $dir
   */
  private function logDirectoryFailure(string $dir): void {
    if ($this->dirAlerted) {
      return;
    }
    $this->dirAlerted = TRUE;
    $message = 'Failed to create the cache directory "' . $dir . '". Please update the settings or file permissions.';
    if (\Civi\Core\Container::isContainerBooted()) {
      try {
        \Civi::log()->error($message);
        return;
      }
      catch (\Throwable $e) {
        // Fall back to PHP's error log; logging must never make a cache write throw.
      }
    }
    error_log($message);
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
    return $this->getCacheDir() . $key_path . '.txt';
  }

}
