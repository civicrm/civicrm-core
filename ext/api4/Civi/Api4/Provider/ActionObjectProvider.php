<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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

namespace Civi\Api4\Provider;

use Civi\API\Event\ResolveEvent;
use Civi\API\Provider\ProviderInterface;
use Civi\Api4\Generic\AbstractAction;
use Civi\API\Events;
use Civi\Api4\Generic\Result;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Accept $apiRequests based on \Civi\API\Action
 */
class ActionObjectProvider implements EventSubscriberInterface, ProviderInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    // Using a high priority allows adhoc implementations
    // to override standard implementations -- which is
    // handy for testing/mocking.
    return [
      Events::RESOLVE => [
        ['onApiResolve', Events::W_EARLY],
      ],
    ];
  }
  /**
   * @param ResolveEvent $event
   *   API resolution event.
   */
  public function onApiResolve(ResolveEvent $event) {
    $apiRequest = $event->getApiRequest();
    if ($apiRequest instanceof AbstractAction) {
      $event->setApiRequest($apiRequest);
      $event->setApiProvider($this);
      $event->stopPropagation();
    }
  }

  /**
   * @inheritDoc
   *
   * @param array|AbstractAction $apiRequest
   *
   * @return array|mixed
   */
  public function invoke($apiRequest) {
    $result = new Result();
    $result->action = $apiRequest['action'];
    $result->entity = $apiRequest['entity'];
    $apiRequest->_run($result);
    return $result;
  }

  /**
   * @inheritDoc
   * @param int $version
   * @return array
   */
  public function getEntityNames($version) {
    /** FIXME */
    return [];
  }

  /**
   * @inheritDoc
   * @param int $version
   * @param string $entity
   * @return array
   */
  public function getActionNames($version, $entity) {
    /** FIXME Civi\API\V4\Action\GetActions */
    return [];
  }

}
