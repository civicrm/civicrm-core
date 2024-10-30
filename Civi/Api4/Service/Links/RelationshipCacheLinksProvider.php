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

namespace Civi\Api4\Service\Links;

use Civi\Core\Event\GenericHookEvent;

/**
 * @service
 * @internal
 */
class RelationshipCacheLinksProvider extends \Civi\Core\Service\AutoSubscriber {

  public static function getSubscribedEvents(): array {
    return [
      'civi.api4.getLinks' => 'alterRelationshipCacheLinks',
    ];
  }

  public static function alterRelationshipCacheLinks(GenericHookEvent $e): void {
    if ($e->entity === 'RelationshipCache') {
      foreach ($e->links as &$link) {
        $link['entity'] = 'Relationship';
      }
    }
  }

}
