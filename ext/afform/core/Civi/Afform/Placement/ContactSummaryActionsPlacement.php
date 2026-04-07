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

namespace Civi\Afform\Placement;

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoSubscriber;

/**
 * Runtime code for placing Afforms in the Contact Summary Actions menu
 */
class ContactSummaryActionsPlacement extends AutoSubscriber {

  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_summaryActions' => 'onSummaryActions',
    ];
  }

  /**
   * Implements hook_civicrm_summaryActions().
   *
   * Adds afforms to the contact summary actions menu.
   */
  public static function onSummaryActions(GenericHookEvent $e) {
    if (empty($e->contactID)) {
      return;
    }
    $context = ['contact_id' => $e->contactID];
    $afforms = PlacementUtils::getAfformsForPlacement('contact_summary_actions');
    foreach ($afforms as $afform) {
      if (PlacementUtils::matchesContextFilters('contact_summary_actions', $afform, $context)) {
        $e->actions['otherActions'][$afform['name']] = [
          'title' => $afform['title'],
          'weight' => $afform['placement_weight'] ?? 0,
          'icon' => 'crm-i ' . ($afform['icon'] ?: 'fa-list-alt'),
          'class' => 'crm-popup',
          'href' => \CRM_Utils_System::url($afform['server_route'], '', FALSE, "?contact_id=$e->contactID"),
        ];
      }
    }
  }

}
