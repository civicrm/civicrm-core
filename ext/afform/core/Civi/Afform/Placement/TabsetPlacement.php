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
 * Runtime code for placing Afforms as tabs
 */
class TabsetPlacement extends AutoSubscriber {

  const TABSETS = [
    'civicrm/contact/view' => 'contact_summary_tab',
    'civicrm/event/manage' => 'event_manage_tab',
  ];

  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_tabset' => 'onTabset',
    ];
  }

  /**
   * Implements hook_civicrm_tabset().
   *
   * Adds afforms as contextual summary tabs.
   */
  public static function onTabset(GenericHookEvent $e) {
    $placement = self::TABSETS[$e->tabsetName] ?? NULL;
    if (!$placement) {
      return;
    }
    $context = $e->context;
    $tabs = &$e->tabs;
    // Event tab should only be placed when viewing an event
    if ($placement === 'event_manage_tab' && empty($context['event_id'])) {
      return;
    }
    // Contact type and sub-type are treated as a single filter
    if (!empty($context['contact_type'])) {
      $context['contact_type'] = array_merge((array) $context['contact_type'], $context['contact_sub_type'] ?? []);
    }

    $existingTabs = array_map(fn($key, $tab) => $tab['id'] ?? $key, array_keys($tabs), array_values($tabs));

    $afforms = PlacementUtils::getAfformsForPlacement($placement);
    $weight = 111;
    foreach ($afforms as $afform) {
      if (PlacementUtils::matchesContextFilters($placement, $afform, $context)) {
        // Convention is to name the afform like "afformTabMyInfo" which gets the tab name "my_info"
        $tabId = \CRM_Utils_String::convertStringToSnakeCase(preg_replace('#^(afformtab|afsearchtab|afform|afsearch)#i', '', $afform['name']));
        // Support overriding custom fields on the contact summary tab
        if ($placement === 'contact_summary_tab' && str_starts_with($tabId, 'custom_')) {
          // custom group tab forms use name, but need to replace tabs using ID
          // remove 'afsearchTabCustom_' from the form name to get the group name
          $groupName = substr($afform['name'], 18);
          $group = \CRM_Core_BAO_CustomGroup::getGroup(['name' => $groupName]);
          if ($group) {
            $tabId = 'custom_' . $group['id'];
          }
        }
        // If a tab with that id already exists, allow the afform to replace it.
        $existingTab = array_search($tabId, $existingTabs);
        if ($existingTab !== FALSE) {
          unset($tabs[$existingTab]);
        }
        $tabs[$tabId] = [
          'id' => $tabId,
          'title' => $afform['title'],
          'weight' => $afform['placement_weight'] ?? $weight++,
          'icon' => 'crm-i ' . ($afform['icon'] ?: 'fa-list-alt'),
          'is_active' => TRUE,
          // Used by ContactLayout for managing tabs
          'contact_type' => PlacementUtils::filterContactTypes($afform['placement_filters']['contact_type'] ?? []) ?: NULL,
          'template' => 'afform/InlineAfform.tpl',
          'module' => $afform['module_name'],
          'directive' => $afform['directive_name'],
        ];
        // If this is the real contact summary page (and not a callback from ContactLayoutEditor), load module
        // and assign entity id to required smarty variable
        if (empty($context['caller'])) {
          \CRM_Core_Smarty::singleton()->assign('afformOptions', PlacementUtils::getAfformContextOptions($placement, $context));
          \Civi::service('angularjs.loader')->addModules($afform['module_name']);
        }
      }
    }
  }

}
