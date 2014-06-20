<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This caching provider stores all cached items as a "group" in the
 * "civicrm_cache" table. The entire 'group' may be prefetched when
 * instantiating the cache provider.
 */
class CRM_Utils_Cache_SqlGroup implements CRM_Utils_Cache_Interface {

  /**
   * The host name of the memcached server
   *
   * @var string
   */
  protected $group;

  /**
   * @var int $componentID The optional component ID (so componenets can share the same name space)
   */
  protected $componentID;

  /**
   * @var array in-memory cache to optimize redundant get()s
   */
  protected $frontCache;

  /**
   * Constructor
   *
   * @param array $config an array of configuration params
   *   - group: string
   *   - componentID: int
   *   - prefetch: bool, whether to preemptively read the entire cache group; default: TRUE
   *
   * @throws RuntimeException
   * @return \CRM_Utils_Cache_SqlGroup
   */
  function __construct($config) {
    if (isset($config['group'])) {
      $this->group = $config['group'];
    } else {
      throw new RuntimeException("Cannot construct SqlGroup cache: missing group");
    }
    if (isset($config['componentID'])) {
      $this->componentID = $config['componentID'];
    } else {
      $this->componentID = NULL;
    }
    $this->frontCache = array();
    if (CRM_Utils_Array::value('prefetch', $config, TRUE)) {
      $this->prefetch();
    }
  }

  /**
   * @param string $key
   * @param mixed $value
   */
  function set($key, &$value) {
    CRM_Core_BAO_Cache::setItem($value, $this->group, $key, $this->componentID);
    $this->frontCache[$key] = $value;
  }

  /**
   * @param string $key
   *
   * @return mixed
   */
  function get($key) {
    if (! array_key_exists($key, $this->frontCache)) {
      $this->frontCache[$key] = CRM_Core_BAO_Cache::getItem($this->group, $key, $this->componentID);
    }
    return $this->frontCache[$key];
  }

  /**
   * @param $key
   * @param null $default
   *
   * @return mixed
   */
  function getFromFrontCache($key, $default = NULL) {
    return CRM_Utils_Array::value($key, $this->frontCache, $default);
  }

  /**
   * @param string $key
   */
  function delete($key) {
    CRM_Core_BAO_Cache::deleteGroup($this->group, $key);
    unset($this->frontCache[$key]);
  }

  function flush() {
    CRM_Core_BAO_Cache::deleteGroup($this->group);
    $this->frontCache = array();
  }

  function prefetch() {
    $this->frontCache = CRM_Core_BAO_Cache::getItems($this->group, $this->componentID);
  }
}
