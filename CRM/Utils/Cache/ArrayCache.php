<?php

/**
 * Class CRM_Utils_Cache_Arraycache
 */
class CRM_Utils_Cache_Arraycache implements CRM_Utils_Cache_Interface {

  /**
   * The cache storage container, an in memory array by default
   */
  private $_cache;

  /**
   * Constructor.
   *
   * @param array $config
   *   An array of configuration params.
   *
   * @return \CRM_Utils_Cache_Arraycache
   */
  public function __construct($config) {
    $this->_cache = array();
  }

  /**
   * @param string $key
   * @param mixed $value
   */
  public function set($key, &$value) {
    $this->_cache[$key] = $value;
  }

  /**
   * @param string $key
   *
   * @return mixed
   */
  public function get($key) {
    return CRM_Utils_Array::value($key, $this->_cache);
  }

  /**
   * @param string $key
   */
  public function delete($key) {
    unset($this->_cache[$key]);
  }

  public function flush() {
    unset($this->_cache);
    $this->_cache = array();
  }

}
