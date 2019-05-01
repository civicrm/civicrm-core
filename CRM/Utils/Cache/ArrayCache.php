<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Class CRM_Utils_Cache_Arraycache
 */
class CRM_Utils_Cache_Arraycache implements CRM_Utils_Cache_Interface {

  use CRM_Utils_Cache_NaiveMultipleTrait;
  // TODO Native implementation
  use CRM_Utils_Cache_NaiveHasTrait;

  const DEFAULT_TIMEOUT = 3600;

  /**
   * The cache storage container, an in memory array by default
   * @var array
   */
  protected $_cache;

  protected $_expires;

  /**
   * Constructor.
   *
   * @param array $config
   *   An array of configuration params.
   *
   * @return \CRM_Utils_Cache_Arraycache
   */
  public function __construct($config) {
    $this->_cache = [];
    $this->_expires = [];
  }

  /**
   * @param string $key
   * @param mixed $value
   * @param null|int|\DateInterval $ttl
   * @return bool
   * @throws \Psr\SimpleCache\InvalidArgumentException
   */
  public function set($key, $value, $ttl = NULL) {
    CRM_Utils_Cache::assertValidKey($key);
    $this->_cache[$key] = $this->reobjectify($value);
    $this->_expires[$key] = CRM_Utils_Date::convertCacheTtlToExpires($ttl, self::DEFAULT_TIMEOUT);
    return TRUE;
  }

  /**
   * @param string $key
   * @param mixed $default
   *
   * @return mixed
   * @throws \Psr\SimpleCache\InvalidArgumentException
   */
  public function get($key, $default = NULL) {
    CRM_Utils_Cache::assertValidKey($key);
    if (isset($this->_expires[$key]) && is_numeric($this->_expires[$key]) && $this->_expires[$key] <= time()) {
      return $default;
    }
    if (array_key_exists($key, $this->_cache)) {
      return $this->reobjectify($this->_cache[$key]);
    }
    return $default;
  }

  /**
   * @param string $key
   * @return bool
   * @throws \Psr\SimpleCache\InvalidArgumentException
   */
  public function delete($key) {
    CRM_Utils_Cache::assertValidKey($key);

    unset($this->_cache[$key]);
    unset($this->_expires[$key]);
    return TRUE;
  }

  public function flush() {
    unset($this->_cache);
    unset($this->_expires);
    $this->_cache = [];
    return TRUE;
  }

  public function clear() {
    return $this->flush();
  }

  private function reobjectify($value) {
    if (is_object($value)) {
      return unserialize(serialize($value));
    }
    if (is_array($value)) {
      foreach ($value as $p) {
        if (is_object($p)) {
          return unserialize(serialize($value));
        }
      }
    }
    return $value;
  }

  /**
   * @param string $key
   * @return int|null
   */
  public function getExpires($key) {
    return $this->_expires[$key] ?: NULL;
  }

}
