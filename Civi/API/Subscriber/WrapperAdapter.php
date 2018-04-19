<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
    return array(
      Events::PREPARE => array('onApiPrepare', Events::W_MIDDLE),
      Events::RESPOND => array('onApiRespond', Events::W_EARLY * 2),
    );
  }

  /**
   * @var array(\API_Wrapper)
   */
  protected $defaults;

  /**
   * @param array $defaults
   *   array(\API_Wrapper).
   */
  public function __construct($defaults = array()) {
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
    if (!isset($apiRequest['wrappers'])) {
      $apiRequest['wrappers'] = $this->defaults;
      \CRM_Utils_Hook::apiWrappers($apiRequest['wrappers'], $apiRequest);
    }
    return $apiRequest['wrappers'];
  }

}
