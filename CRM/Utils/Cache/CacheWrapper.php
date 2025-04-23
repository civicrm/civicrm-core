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
 */
class CRM_Utils_Cache_CacheWrapper implements CRM_Utils_Cache_Interface {

  /**
   * @var string
   */
  private $serviceName;

  /**
   * @var CRM_Utils_Cache_Interface
   */
  private $delegate;

  /**
   * @param \CRM_Utils_Cache_Interface $delegate
   * @param string $serviceName
   */
  public function __construct(\CRM_Utils_Cache_Interface $delegate, $serviceName) {
    $this->delegate = $delegate;
    $this->serviceName = $serviceName;
  }

  public function getMultiple($keys, $default = NULL) {
    return $this->delegate->getMultiple($keys, $default);
  }

  public function setMultiple($values, $ttl = NULL) {
    return $this->delegate->setMultiple($values, $ttl);
  }

  public function deleteMultiple($keys) {
    $this->dispatchClearEvent($keys);
    return $this->delegate->deleteMultiple($keys);
  }

  public function set($key, $value, $ttl = NULL) {
    return $this->delegate->set($key, $value, $ttl);
  }

  public function get($key, $default = NULL) {
    return $this->delegate->get($key, $default);
  }

  public function delete($key) {
    $this->dispatchClearEvent([$key]);
    return $this->delegate->delete($key);
  }

  /**
   * @deprecated
   */
  public function flush() {
    return $this->clear();
  }

  public function clear() {
    $this->dispatchClearEvent();
    return $this->delegate->clear();
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    return $this->delegate->garbageCollection();
  }

  public function has($key) {
    return $this->delegate->has($key);
  }

  /**
   * @param string $key
   * @return int|null
   */
  public function getExpires($key) {
    if (method_exists($this->delegate, 'getExpires')) {
      return $this->delegate->getExpires($key);
    }
    return NULL;
  }

  private function dispatchClearEvent($keys = NULL) {
    // FIXME: When would name ever be empty?
    if ($this->serviceName) {
      $hookParams = [
        'items' => $keys,
      ];
      $event = \Civi\Core\Event\GenericHookEvent::create($hookParams);
      Civi::dispatcher()->dispatch("civi.cache.$this->serviceName.clear", $event);
    }
  }

}
