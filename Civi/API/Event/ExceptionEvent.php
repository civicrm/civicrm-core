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
 * Handle any exceptions that occur while processing an API request.
 *
 * Event name: 'civi.api.exception'
 */
class ExceptionEvent extends Event {

  /**
   * @var \Exception
   */
  private $exception;

  /**
   * @param \Exception $exception
   *   The exception which arose while processing the API request.
   * @param \Civi\API\Provider\ProviderInterface $apiProvider
   *   The API provider responsible for executing the request.
   * @param array $apiRequest
   *   The full description of the API request.
   * @param \Civi\API\Kernel $apiKernel
   *   The kernel which fired the event.
   */
  public function __construct($exception, $apiProvider, $apiRequest, $apiKernel) {
    $this->exception = $exception;
    parent::__construct($apiProvider, $apiRequest, $apiKernel);
  }

  /**
   * @return \Exception
   */
  public function getException() {
    return $this->exception;
  }

}
