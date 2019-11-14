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
 * Class RespondEvent
 * @package Civi\API\Event
 */
class RespondEvent extends Event {
  /**
   * @var mixed
   */
  private $response;

  /**
   * @param \Civi\API\Provider\ProviderInterface $apiProvider
   *   The API provider responsible for executing the request.
   * @param array $apiRequest
   *   The full description of the API request.
   * @param mixed $response
   *   The response to return to the client.
   * @param \Civi\API\Kernel $apiKernel
   *   The kernel which fired the event.
   */
  public function __construct($apiProvider, $apiRequest, $response, $apiKernel) {
    $this->response = $response;
    parent::__construct($apiProvider, $apiRequest, $apiKernel);
  }

  /**
   * @return mixed
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * @param mixed $response
   *   The response to return to the client.
   */
  public function setResponse($response) {
    $this->response = $response;
  }

}
