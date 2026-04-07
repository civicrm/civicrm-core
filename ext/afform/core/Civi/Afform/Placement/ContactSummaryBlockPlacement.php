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
 * Code for placing Afforms as Contact Summary Blocks at runtime,
 * and declaring blocks for ContactLayout Editor.
 */
class ContactSummaryBlockPlacement extends AutoSubscriber {

  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_pageRun' => 'onPageRun',
      'hook_civicrm_contactSummaryBlocks' => 'onGetBlocks',
    ];
  }

  /**
   * Implements hook_civicrm_pageRun().
   *
   * Adds afforms as contact summary blocks.
   */
  public static function onPageRun(GenericHookEvent $e) {
    $page = $e->page;
    if (!in_array(get_class($page), ['CRM_Contact_Page_View_Summary', 'CRM_Contact_Page_View_Print'])) {
      return;
    }
    $afforms = PlacementUtils::getAfformsForPlacement('contact_summary_block');
    $side = 'left';
    $weight = ['left' => 1, 'right' => 1];
    $afformOptions = $context = [
      'contact_id' => $page->get('cid'),
    ];
    foreach ($afforms as $afform) {
      if (PlacementUtils::matchesContextFilters('contact_summary_block', $afform, $context)) {
        $block = [
          'module' => $afform['module_name'],
          'directive' => $afform['directive_name'],
        ];
        $content = \CRM_Core_Smarty::singleton()->fetchWith('afform/InlineAfform.tpl', ['afformOptions' => $afformOptions, 'block' => $block]);
        \CRM_Core_Region::instance("contact-basic-info-$side")->add([
          'markup' => '<div class="crm-summary-block">' . $content . '</div>',
          'name' => 'afform:' . $afform['name'],
          'weight' => $weight[$side]++,
        ]);
        \Civi::service('angularjs.loader')->addModules($afform['module_name']);
        $side = $side === 'left' ? 'right' : 'left';
      }
    }
  }

  /**
   * Implements hook_civicrm_contactSummaryBlocks().
   *
   * @link https://github.com/civicrm/org.civicrm.contactlayout
   */
  public static function onGetBlocks(GenericHookEvent $e) {
    $afforms = \Civi\Api4\Afform::get()
      ->setSelect(['name', 'title', 'directive_name', 'module_name', 'type', 'type:icon', 'type:label', 'placement_filters'])
      ->addWhere('placement', 'CONTAINS', 'contact_summary_block')
      ->addOrderBy('title')
      ->execute();
    foreach ($afforms as $index => $afform) {
      // Create a group per afform type
      $e->blocks += [
        "afform_{$afform['type']}" => [
          'title' => $afform['type:label'],
          'icon' => $afform['type:icon'],
          'blocks' => [],
        ],
      ];
      // If the form specifies contact types, resolve them to just the parent types (Individual, Organization, Household)
      // because ContactLayout doesn't care about sub-types
      $contactType = PlacementUtils::filterContactTypes($afform['placement_filters']['contact_type'] ?? []);
      $e->blocks["afform_{$afform['type']}"]['blocks'][$afform['name']] = [
        'title' => $afform['title'],
        'contact_type' => $contactType ?: NULL,
        'tpl_file' => 'afform/InlineAfform.tpl',
        'module' => $afform['module_name'],
        'directive' => $afform['directive_name'],
        'sample' => [
          $afform['type:label'],
        ],
        'edit' => 'civicrm/admin/afform#/edit/' . $afform['name'],
        'system_default' => [0, $index % 2],
      ];
    }
  }

}
