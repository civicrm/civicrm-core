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

namespace Civi\API\Provider;

/**
 * A wrapping provider overrides an existing API. It has discretion to pass-through
 * to the original API (0 or many times) or to substitute with entirely different
 * behavior.
 *
 * The WrappingProvider does yield any metadata of its own. It's primarily
 * intended for dynamically decorating an existing API.
 */
class WrappingProvider implements ProviderInterface {

  /**
   * @var callable
   *   Function($apiRequest, callable $continue)
   */
  protected $callback;

  /**
   * @var ProviderInterface
   */
  protected $original;

  /**
   * WrappingProvider constructor.
   * @param callable $callback
   * @param \Civi\API\Provider\ProviderInterface $original
   */
  public function __construct($callback, \Civi\API\Provider\ProviderInterface $original) {
    $this->callback = $callback;
    $this->original = $original;
  }

  public function invoke($apiRequest) {
    // $continue = function($a) { return $this->original->invoke($a); };
    $continue = [$this->original, 'invoke'];
    return call_user_func($this->callback, $apiRequest, $continue);
  }

  public function getEntityNames($version) {
    // return $version == $this->version ? [$this->entity] : [];
    throw new \CRM_Core_Exception("Not support: WrappingProvider::getEntityNames()");
  }

  public function getActionNames($version, $entity) {
    // return $version == $this->version && $this->entity == $entity ? [$this->action] : [];
    throw new \CRM_Core_Exception("Not support: WrappingProvider::getActionNames()");
  }

}
