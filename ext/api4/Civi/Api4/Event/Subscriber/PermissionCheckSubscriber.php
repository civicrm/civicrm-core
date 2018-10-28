<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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

namespace Civi\Api4\Event\Subscriber;

use Civi\API\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * For any API requests that correspond to a Doctrine entity
 * ($apiRequest['doctrineClass']), check permissions specified in
 * Civi\API\Annotation\Permission.
 */
class PermissionCheckSubscriber implements EventSubscriberInterface {
  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      Events::AUTHORIZE => [
        ['onApiAuthorize', Events::W_LATE],
      ],
    ];
  }

  /**
   * @param \Civi\API\Event\AuthorizeEvent $event
   *   API authorization event.
   */
  public function onApiAuthorize(\Civi\API\Event\AuthorizeEvent $event) {
    /* @var \Civi\Api4\Generic\AbstractAction $apiRequest */
    $apiRequest = $event->getApiRequest();
    if ($apiRequest['version'] == 4) {
      if (!$apiRequest->getCheckPermissions() || $apiRequest->isAuthorized()) {
        $event->authorize();
        $event->stopPropagation();
      }
    }
  }

}
