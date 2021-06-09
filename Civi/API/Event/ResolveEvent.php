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
 * Class ResolveEvent
 * @package Civi\API\Event
 *
 * Determine which API provider executes the given request. For successful
 * execution, at least one listener must invoke
 * $event->setProvider($provider).
 *
 * Event name: 'civi.api.resolve'
 */
class ResolveEvent extends Event {

  /**
   * @param array $apiRequest
   *   The full description of the API request.
   * @param \Civi\API\Kernel $apiKernel
   *   The kernel which fired the event.
   */
  public function __construct($apiRequest, $apiKernel) {
    parent::__construct(NULL, $apiRequest, $apiKernel);
  }

  /**
   * @param \Civi\API\Provider\ProviderInterface $apiProvider
   *   The API provider responsible for executing the request.
   */
  public function setApiProvider($apiProvider) {
    $this->apiProvider = $apiProvider;
  }

  /**
   * @param array $apiRequest
   *   The full description of the API request.
   */
  public function setApiRequest($apiRequest) {
    // Elevate from 'protected' to 'public'.
    return parent::setApiRequest($apiRequest);
  }

}
