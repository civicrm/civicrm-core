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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to check extra permission for SavedSearches
 * @service civi.api4.searchKit
 */
class SearchKitSubscriber extends \Civi\Core\Service\AutoService implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.api.authorize' => [
        ['onApiAuthorize', -200],
      ],
    ];
  }

  /**
   * Alters APIv4 permissions to allow users with 'manage own search_kit' to create/delete a SavedSearch
   *
   * @param \Civi\API\Event\AuthorizeEvent $event
   *   API authorization event.
   */
  public function onApiAuthorize(\Civi\API\Event\AuthorizeEvent $event) {
    /** @var \Civi\Api4\Generic\AbstractAction $apiRequest */
    $apiRequest = $event->getApiRequest();
    if ($apiRequest['version'] == 4 && $apiRequest->getEntityName() === 'SavedSearch') {
      if (\CRM_Core_Permission::check('manage own search_kit')) {
        $event->authorize();
        $event->stopPropagation();
      }
    }
  }

}
