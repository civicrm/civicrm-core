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

use Civi\API\Provider\WrappingProvider;

/**
 * Class PrepareEvent
 * @package Civi\API\Event
 *
 * Apply any pre-execution filtering to the API request.
 *
 * Event name: 'civi.api.prepare'
 */
class PrepareEvent extends Event {

  /**
   * @param array $apiRequest
   *   The full description of the API request.
   * @return static
   */
  public function setApiRequest($apiRequest) {
    // Elevate from 'protected' to 'public'.
    return parent::setApiRequest($apiRequest);
  }

  /**
   * Replace the normal implementation of an API call with some wrapper.
   *
   * The wrapper has discretion to call -- or not call -- or iterate with --
   * the original API implementation, with original or substituted arguments.
   *
   * Ex:
   *
   * $event->wrapApi(function($apiRequest, $continue){
   *   echo "Hello\n";
   *   $continue($apiRequest);
   *   echo "Goodbye\n";
   * });
   *
   * @param callable $callback
   *   The custom API implementation.
   *   Function(array $apiRequest, callable $continue).
   * @return PrepareEvent
   */
  public function wrapApi($callback) {
    $this->apiProvider = new WrappingProvider($callback, $this->apiProvider);
    return $this;
  }

}
