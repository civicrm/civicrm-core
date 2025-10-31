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
class CRM_Utils_Cache_NoCache implements CRM_Utils_Cache_Interface {

  // TODO Consider native implementation.
  use CRM_Utils_Cache_NaiveMultipleTrait;
  // TODO Native implementation
  use CRM_Utils_Cache_NaiveHasTrait;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   */
  static private $_singleton = NULL;

  /**
   * Constructor.
   *
   * @param array $config
   *   An array of configuration params.
   *
   * @return \CRM_Utils_Cache_NoCache
   */
  public function __construct($config) {
  }

  /**
   * @param string $key
   * @param mixed $value
   * @param null|int|\DateInterval $ttl
   *
   * @return bool
   */
  public function set($key, $value, $ttl = NULL) {
    return FALSE;
  }

  /**
   * @param string $key
   * @param mixed $default
   *
   * @return null
   */
  public function get($key, $default = NULL) {
    return $default;
  }

  /**
   * @param string $key
   *
   * @return bool
   */
  public function delete($key) {
    return FALSE;
  }

  /**
   * @return bool
   */
  public function flush() {
    return FALSE;
  }

  public function clear() {
    return $this->flush();
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    return FALSE;
  }

}
