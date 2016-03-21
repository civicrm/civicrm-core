<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * An extension container is a locally-accessible source tree which can be
 * scanned for extensions.
 */
class CRM_Extension_Container_Collection implements CRM_Extension_Container_Interface {

  /**
   * @var array ($name => CRM_Extension_Container_Interface)
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $containers;

  /**
   * @var CRM_Utils_Cache_Interface|NULL
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $cache;

  /**
   * @var string the cache key used for any data stored by this container
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $cacheKey;

  /**
   * @var array ($key => $containerName)
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $k2c;

  /**
   * @param array $containers
   *   Array($name => CRM_Extension_Container_Interface) in order from highest
   *   priority (winners) to lowest priority (losers).
   * @param CRM_Utils_Cache_Interface $cache
   *   Cache in which to store extension metadata.
   * @param string $cacheKey
   *   Unique name for this container.
   */
  public function __construct($containers, CRM_Utils_Cache_Interface $cache = NULL, $cacheKey = NULL) {
    $this->containers = $containers;
    $this->cache = $cache;
    $this->cacheKey = $cacheKey;
  }

  /**
   * @inheritDoc
   *
   * @return array
   */
  public function checkRequirements() {
    $errors = array();
    foreach ($this->containers as $container) {
      $errors = array_merge($errors, $container->checkRequirements());
    }
    return $errors;
  }

  /**
   * @inheritDoc
   *
   * @return array_keys
   */
  public function getKeys() {
    $k2c = $this->getKeysToContainer();
    return array_keys($k2c);
  }

  /**
   * @inheritDoc
   *
   * @param string $key
   */
  public function getPath($key) {
    return $this->getContainer($key)->getPath($key);
  }

  /**
   * @inheritDoc
   *
   * @param string $key
   */
  public function getResUrl($key) {
    return $this->getContainer($key)->getResUrl($key);
  }

  /**
   * @inheritDoc
   */
  public function refresh() {
    if ($this->cache) {
      $this->cache->delete($this->cacheKey);
    }
    foreach ($this->containers as $container) {
      $container->refresh();
    }
  }

  /**
   * Get the container which defines a particular key.
   *
   * @param string $key
   *   Extension name.
   *
   * @throws CRM_Extension_Exception_MissingException
   * @return CRM_Extension_Container_Interface
   */
  public function getContainer($key) {
    $k2c = $this->getKeysToContainer();
    if (isset($k2c[$key]) && isset($this->containers[$k2c[$key]])) {
      return $this->containers[$k2c[$key]];
    }
    else {
      throw new CRM_Extension_Exception_MissingException("Unknown extension: $key");
    }
  }

  /**
   * Get a list of all keys in these containers -- and the
   * name of the container which defines each key.
   *
   * @return array
   *   ($key => $containerName)
   */
  public function getKeysToContainer() {
    if ($this->cache) {
      $k2c = $this->cache->get($this->cacheKey);
    }
    if (!isset($k2c) || !is_array($k2c)) {
      $k2c = array();
      $containerNames = array_reverse(array_keys($this->containers));
      foreach ($containerNames as $name) {
        $keys = $this->containers[$name]->getKeys();
        foreach ($keys as $key) {
          $k2c[$key] = $name;
        }
      }
      if ($this->cache) {
        $this->cache->set($this->cacheKey, $k2c);
      }
    }
    return $k2c;
  }

}
