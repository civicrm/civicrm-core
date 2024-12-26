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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * An extension container is a locally-accessible source tree which can be
 * scanned for extensions.
 */
class CRM_Extension_Container_Collection implements CRM_Extension_Container_Interface {

  /**
   * Containers.
   *
   * Format is [$name => CRM_Extension_Container_Interface]
   *
   * @var array
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $containers;

  /**
   * @var CRM_Utils_Cache_Interface|null
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $cache;

  /**
   * The cache key used for any data stored by this container.
   *
   * @var string
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $cacheKey;

  /**
   * K2C ....
   *
   * Format is ($key => $containerName).
   *
   * @var array
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $k2c;

  /**
   * Class constructor.
   *
   * @param array $containers
   *   Array($name => CRM_Extension_Container_Interface) in order from highest
   *   priority (winners) to lowest priority (losers).
   * @param CRM_Utils_Cache_Interface|null $cache
   *   Cache in which to store extension metadata.
   * @param string $cacheKey
   *   Unique name for this container.
   */
  public function __construct($containers, ?CRM_Utils_Cache_Interface $cache = NULL, $cacheKey = NULL) {
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
    $errors = [];
    foreach ($this->containers as $container) {
      $errors = array_merge($errors, $container->checkRequirements());
    }
    return $errors;
  }

  /**
   * @inheritDoc
   *
   * @return array
   */
  public function getKeys() {
    $k2c = $this->getKeysToContainer();
    return array_keys($k2c);
  }

  /**
   * @inheritDoc
   *
   * @param string $key
   *
   * @throws \CRM_Extension_Exception_MissingException
   */
  public function getPath($key) {
    return $this->getContainer($key)->getPath($key);
  }

  /**
   * @inheritDoc
   *
   * @param string $key
   *
   * @throws \CRM_Extension_Exception_MissingException
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
      $k2c = [];
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
