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

namespace Civi\API\Event;

/**
 * Class Event
 * @package Civi\API\Event
 */
class Event extends \Symfony\Component\EventDispatcher\Event {

  /**
   * @var \Civi\API\Kernel
   */
  protected $apiKernel;

  /**
   * @var \Civi\API\Provider\ProviderInterface
   *   The API provider responsible for executing the request.
   */
  protected $apiProvider;

  /**
   * @var array
   *   The full description of the API request.
   *
   * @see \Civi\API\Request::create
   */
  protected $apiRequest;

  /**
   * @param \Civi\API\Provider\ProviderInterface $apiProvider
   *   The API responsible for executing the request.
   * @param array $apiRequest
   *   The full description of the API request.
   * @param \Civi\API\Kernel $apiKernel
   */
  public function __construct($apiProvider, $apiRequest, $apiKernel) {
    $this->apiKernel = $apiKernel;
    $this->apiProvider = $apiProvider;
    $this->apiRequest = $apiRequest;
  }

  /**
   * Get api kernel.
   *
   * @return \Civi\API\Kernel
   */
  public function getApiKernel() {
    return $this->apiKernel;
  }

  /**
   * @return \Civi\API\Provider\ProviderInterface
   */
  public function getApiProvider() {
    return $this->apiProvider;
  }

  /**
   * @return array
   */
  public function getApiRequest() {
    return $this->apiRequest;
  }

  /**
   * Create a brief string identifying the entity/action. Useful for
   * pithy matching/switching.
   *
   * Ex: if ($e->getApiRequestSig() === '3.contact.get') { ... }
   *
   * @return string
   *   Ex: '3.contact.get'
   */
  public function getApiRequestSig() {
    return mb_strtolower($this->apiRequest['version'] . '.' . $this->apiRequest['entity'] . '.' . $this->apiRequest['action']);
  }

}
