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
   * Constructor
   *
   * @param array $config an array of configuration params
   *
   * @return \CRM_Utils_Cache_Arraycache
   */
  function __construct($config) {
    $this->_cache = array();
  }

  /**
   * @param string $key
   * @param mixed $value
   */
  function set($key, &$value) {
    $this->_cache[$key] = $value;
  }

  /**
   * @param string $key
   *
   * @return mixed
   */
  function get($key) {
    return CRM_Utils_Array::value($key, $this->_cache);
  }

  /**
   * @param string $key
   */
  function delete($key) {
    unset($this->_cache[$key]);
  }

  function flush() {
    unset($this->_cache);
    $this->_cache = array();
  }
}

