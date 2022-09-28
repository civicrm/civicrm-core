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
 */
class CRM_Extension_BootCache {

  protected $locked = FALSE;

  protected $data;

  /**
   * Define a persistent value in the extension's boot-cache.
   *
   * This value is retained as part of the boot-cache. It will be loaded
   * very quickly (eg via php op-code caching). However, as a trade-off,
   * you will not be able to change/reset at runtime - it will only
   * reset in response to a system-wide flush or redeployment.
   *
   * Ex: $mix->define('initTime', function() { return time(); });
   *
   * @param string $key
   * @param mixed $callback
   * @return mixed
   *   The value of $callback (either cached or fresh)
   */
  public function define($key, $callback) {
    if (!isset($this->data[$key])) {
      $this->set($key, $callback($this));
    }
    return $this->data[$key];
  }

  /**
   * Determine if $key has been set.
   *
   * @param string $key
   * @return bool
   */
  public function has($key) {
    return isset($this->data[$key]);
  }

  /**
   * Get the value of $key.
   *
   * @param string $key
   * @param mixed $default
   * @return mixed
   */
  public function get($key, $default = NULL) {
    return $this->data[$key] ?? $default;
  }

  /**
   * Set a value in the cache.
   *
   * This operation is only valid on the first page-load when a cache is built.
   *
   * @param string $key
   * @param mixed $value
   * @return static
   * @throws \Exception
   */
  public function set($key, $value) {
    if ($this->locked) {
      throw new \Exception("Cannot modify a locked boot-cache.");
    }
    $this->data[$key] = $value;
    return $this;
  }

  public function lock() {
    $this->locked = TRUE;
  }

}
