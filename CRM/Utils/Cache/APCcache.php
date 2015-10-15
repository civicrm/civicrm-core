<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */
class CRM_Utils_Cache_APCcache implements CRM_Utils_Cache_Interface {
  const DEFAULT_TIMEOUT = 3600;
  const DEFAULT_PREFIX = '';

  /**
   * The default timeout to use.
   *
   * @var int
   */
  protected $_timeout = self::DEFAULT_TIMEOUT;

  /**
   * The prefix prepended to cache keys.
   *
   * If we are using the same memcache instance for multiple CiviCRM
   * installs, we must have a unique prefix for each install to prevent
   * the keys from clobbering each other.
   *
   * @var string
   */
  protected $_prefix = self::DEFAULT_PREFIX;

  /**
   * Constructor.
   *
   * @param array $config
   *   An array of configuration params.
   *
   * @return \CRM_Utils_Cache_APCcache
   */
  public function __construct(&$config) {
    if (isset($config['timeout'])) {
      $this->_timeout = intval($config['timeout']);
    }
    if (isset($config['prefix'])) {
      $this->_prefix = $config['prefix'];
    }
  }

  /**
   * @param $key
   * @param $value
   *
   * @return bool
   */
  public function set($key, &$value) {
    if (!apc_store($this->_prefix . $key, $value, $this->_timeout)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @param $key
   *
   * @return mixed
   */
  public function &get($key) {
    return apc_fetch($this->_prefix . $key);
  }

  /**
   * @param $key
   *
   * @return bool|string[]
   */
  public function delete($key) {
    return apc_delete($this->_prefix . $key);
  }

  public function flush() {
    $allinfo = apc_cache_info('user');
    $keys = $allinfo['cache_list'];
    $prefix = $this->_prefix . "CRM_";  // Our keys follows this pattern: ([A-Za-z0-9_]+)?CRM_[A-Za-z0-9_]+
    $lp = strlen($prefix);              // Get prefix length

    foreach ($keys as $key) {
      $name = $key['info'];
      if ($prefix == substr($name, 0, $lp)) {
        // Ours?
        apc_delete($this->_prefix . $name);
      }
    }
  }

}
