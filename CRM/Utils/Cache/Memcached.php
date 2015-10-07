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
class CRM_Utils_Cache_Memcached implements CRM_Utils_Cache_Interface {
  const DEFAULT_HOST = 'localhost';
  const DEFAULT_PORT = 11211;
  const DEFAULT_TIMEOUT = 3600;
  const DEFAULT_PREFIX = '';
  const MAX_KEY_LEN = 62;

  /**
   * The host name of the memcached server
   *
   * @var string
   */
  protected $_host = self::DEFAULT_HOST;

  /**
   * The port on which to connect on
   *
   * @var int
   */
  protected $_port = self::DEFAULT_PORT;

  /**
   * The default timeout to use
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
   * The actual memcache object.
   *
   * @var resource
   */
  protected $_cache;

  /**
   * Constructor.
   *
   * @param array $config
   *   An array of configuration params.
   *
   * @return \CRM_Utils_Cache_Memcached
   */
  public function __construct($config) {
    if (isset($config['host'])) {
      $this->_host = $config['host'];
    }
    if (isset($config['port'])) {
      $this->_port = $config['port'];
    }
    if (isset($config['timeout'])) {
      $this->_timeout = $config['timeout'];
    }
    if (isset($config['prefix'])) {
      $this->_prefix = $config['prefix'];
    }

    $this->_cache = new Memcached();

    if (!$this->_cache->addServer($this->_host, $this->_port)) {
      // dont use fatal here since we can go in an infinite loop
      echo 'Could not connect to Memcached server';
      CRM_Utils_System::civiExit();
    }
  }

  /**
   * @param $key
   * @param $value
   *
   * @return bool
   * @throws Exception
   */
  public function set($key, &$value) {
    $key = $this->cleanKey($key);
    if (!$this->_cache->set($key, $value, $this->_timeout)) {
      CRM_Core_Error::debug('Result Code: ', $this->_cache->getResultMessage());
      CRM_Core_Error::fatal("memcached set failed, wondering why?, $key", $value);
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
    $key = $this->cleanKey($key);
    $result = $this->_cache->get($key);
    return $result;
  }

  /**
   * @param $key
   *
   * @return mixed
   */
  public function delete($key) {
    $key = $this->cleanKey($key);
    return $this->_cache->delete($key);
  }

  /**
   * @param $key
   *
   * @return mixed|string
   */
  public function cleanKey($key) {
    $key = preg_replace('/\s+|\W+/', '_', $this->_prefix . $key);
    if (strlen($key) > self::MAX_KEY_LEN) {
      $md5Key = md5($key);  // this should be 32 characters in length
      $subKeyLen = self::MAX_KEY_LEN - 1 - strlen($md5Key);
      $key = substr($key, 0, $subKeyLen) . "_" . $md5Key;
    }
    return $key;
  }

  /**
   * @return mixed
   */
  public function flush() {
    return $this->_cache->flush();
  }

}
