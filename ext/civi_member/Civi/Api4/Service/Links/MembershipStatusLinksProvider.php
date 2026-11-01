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

use Civi\Core\Attribute\CiviEventListener;
use Civi\Core\Event\GenericHookEvent;

class MembershipStatusLinksProvider {
  use LinksProviderTrait;

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @return void
   */
  #[CiviEventListener(event: 'civi.api4.getLinks')]
  public static function alterMembershipStatusLinks(GenericHookEvent $e): void {
    if ($e->entity === 'MembershipStatus') {
      foreach ($e->links as $index => $link) {
        // Reserved membership status should not be edited via UI
        if (in_array($link['ui_action'], ['update', 'delete'], TRUE)) {
          $e->links[$index]['conditions'][] = ['is_reserved', '=', FALSE];
        }
      }
    }
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  #[CiviEventListener(event: 'hook_civicrm_searchKitTasks')]
  public function alterMembershipStatusTasks(GenericHookEvent $event): void {
    if (!empty($event->tasks['MembershipStatus'])) {
      // Reserved membership status should not be edited via UI
      $event->tasks['MembershipStatus']['update']['conditions'][] = ['is_reserved', '=', FALSE];
      $event->tasks['MembershipStatus']['delete']['conditions'][] = ['is_reserved', '=', FALSE];
      $event->tasks['MembershipStatus']['enable']['conditions'][] = ['is_reserved', '=', FALSE];
      $event->tasks['MembershipStatus']['disable']['conditions'][] = ['is_reserved', '=', FALSE];
    }
  }

}
