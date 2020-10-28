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

namespace Civi\API\Subscriber;

use Civi\API\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This is a wrapper for the legacy "API Wrapper" interface which allows
 * wrappers to run through the new kernel. It translates from dispatcher events
 * ('civi.api.prepare', 'civi.api.respond') to wrapper calls ('fromApiInput', 'toApiOutput').
 */
class WrapperAdapter implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.api.prepare' => ['onApiPrepare', Events::W_MIDDLE],
      'civi.api.respond' => ['onApiRespond', Events::W_EARLY * 2],
    ];
  }

  /**
   * @var \API_Wrapper[]
   */
  protected $defaults;

  /**
   * @param array $defaults
   *   array(\API_Wrapper).
   */
  public function __construct($defaults = []) {
    $this->defaults = $defaults;
  }

  /**
   * @param \Civi\API\Event\PrepareEvent $event
   *   API preparation event.
   */
  public function onApiPrepare(\Civi\API\Event\PrepareEvent $event) {
    $apiRequest = $event->getApiRequest();

    // For input filtering, process $apiWrappers in forward order
    foreach ($this->getWrappers($apiRequest) as $apiWrapper) {
      $apiRequest = $apiWrapper->fromApiInput($apiRequest);
    }

    $event->setApiRequest($apiRequest);
  }

  /**
   * @param \Civi\API\Event\RespondEvent $event
   *   API response event.
   */
  public function onApiRespond(\Civi\API\Event\RespondEvent $event) {
    $apiRequest = $event->getApiRequest();
    $result = $event->getResponse();

    // For output filtering, process $apiWrappers in reverse order
    foreach (array_reverse($this->getWrappers($apiRequest)) as $apiWrapper) {
      $result = $apiWrapper->toApiOutput($apiRequest, $result);
    }

    $event->setResponse($result);
  }

  /**
   * @param array $apiRequest
   *   The full API request.
   * @return array<\API_Wrapper>
   */
  public function getWrappers($apiRequest) {
    if (!isset($apiRequest['wrappers']) || is_null($apiRequest['wrappers'])) {
      $apiRequest['wrappers'] = $apiRequest['version'] < 4 ? $this->defaults : [];
      \CRM_Utils_Hook::apiWrappers($apiRequest['wrappers'], $apiRequest);
    }
    return $apiRequest['wrappers'];
  }

}
