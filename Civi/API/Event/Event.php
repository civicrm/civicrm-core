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
class Event extends \Civi\Core\Event\GenericHookEvent {

  use RequestTrait;

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
   * @param \Civi\API\Provider\ProviderInterface $apiProvider
   *   The API responsible for executing the request.
   * @param array $apiRequest
   *   The full description of the API request.
   * @param \Civi\API\Kernel $apiKernel
   */
  public function __construct($apiProvider, $apiRequest, $apiKernel) {
    $this->apiKernel = $apiKernel;
    $this->apiProvider = $apiProvider;
    $this->setApiRequest($apiRequest);
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

}
