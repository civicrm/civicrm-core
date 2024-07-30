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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Form helper class for an Website object.
 */
class CRM_Contact_Form_Edit_Website {

  /**
   * Build the form object elements for an Website object.
   *
   * @param CRM_Core_Form $form
   *   Reference to the form object.
   * @param int $blockCount
   *   Block number to build.
   */
  public static function buildQuickForm(&$form, $blockCount = NULL) {
    if (!$blockCount) {
      $blockId = ($form->get('Website_Block_Count')) ? $form->get('Website_Block_Count') : 1;
    }
    else {
      $blockId = $blockCount;
    }

    $form->applyFilter('__ALL__', 'trim');

    //Website type select
    $form->addField("website[$blockId][website_type_id]", ['entity' => 'website', 'class' => 'eight', 'placeholder' => NULL, 'title' => ts('Website Type %1', [1 => $blockId])]);

    //Website box
    $form->addField("website[$blockId][url]", ['entity' => 'website', 'aria-label' => ts('Website URL %1', [1 => $blockId])]);
    $form->addRule("website[$blockId][url]", ts('Enter a valid web address beginning with \'http://\' or \'https://\'.'), 'url');

  }

}
