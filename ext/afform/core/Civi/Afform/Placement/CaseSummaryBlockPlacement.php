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
class CaseSummaryBlockPlacement extends AutoSubscriber {

  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_buildForm' => 'onBuildForm',
    ];
  }

  /**
   * Implements hook_civicrm_buildForm().
   *
   * Adds Afforms to case summary screen.
   */
  public static function onBuildForm(GenericHookEvent $e) {
    if ($e->formName !== 'CRM_Case_Form_CaseView') {
      return;
    }
    $afforms = PlacementUtils::getAfformsForPlacement('case_summary_block');
    $afformOptions = $context = [
      'case_id' => $e->form->get('id'),
      'contact_id' => $e->form->get('cid'),
    ];
    $weight = 1;
    foreach ($afforms as $afform) {
      if (PlacementUtils::matchesContextFilters('case_summary_block', $afform, $context)) {
        $block = [
          'module' => $afform['module_name'],
          'directive' => $afform['directive_name'],
          // cannot use 'form' because the case summary screen is already a <form>
          'wrapper' => 'div',
        ];
        $content = \CRM_Core_Smarty::singleton()->fetchWith('afform/InlineAfform.tpl', [
          'afformOptions' => $afformOptions,
          'block' => $block,
        ]);
        \CRM_Core_Region::instance('case-view-custom-data-view')->add([
          'markup' => $content,
          'name' => 'afform:' . $afform['name'],
          'weight' => $afform['placement_weight'] ?? $weight++,
        ]);
        \Civi::service('angularjs.loader')->addModules($afform['module_name']);
      }
    }
  }

}
