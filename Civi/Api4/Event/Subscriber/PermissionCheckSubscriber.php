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

namespace Civi\Api4\Event\Subscriber;

use Civi\API\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * For any API requests that correspond to a Doctrine entity
 * ($apiRequest['doctrineClass']), check permissions specified in
 * Civi\API\Annotation\Permission.
 *
 * @service civi.api4.permissionCheck
 */
class PermissionCheckSubscriber extends \Civi\Core\Service\AutoService implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents(): array {
    return [
      'civi.api.authorize' => [
        ['onApiAuthorize', Events::W_LATE],
      ],
    ];
  }

  /**
   * @param \Civi\API\Event\AuthorizeEvent $event
   *   API authorization event.
   */
  public function onApiAuthorize(\Civi\API\Event\AuthorizeEvent $event) {
    /** @var \Civi\Api4\Generic\AbstractAction $apiRequest */
    $apiRequest = $event->getApiRequest();
    if ($apiRequest['version'] == 4) {
      if (
        !$apiRequest->getCheckPermissions() ||
        // This action checks permissions internally
        $apiRequest->getActionName() === 'getLinks' ||
        $apiRequest->isAuthorized()
      ) {
        $event->authorize();
        $event->stopPropagation();
      }
    }
  }

}
