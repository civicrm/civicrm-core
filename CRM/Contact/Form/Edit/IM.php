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
 * Form helper class for an IM object.
 */
class CRM_Contact_Form_Edit_IM {

  /**
   * Build the form object elements for an IM object.
   *
   * @param CRM_Core_Form $form
   *   Reference to the form object.
   * @param int $blockCount
   *   Block number to build.
   * @param bool $blockEdit
   *   Is it block edit.
   */
  public static function buildQuickForm(&$form, $blockCount = NULL, $blockEdit = FALSE) {
    if (!$blockCount) {
      $blockId = ($form->get('IM_Block_Count')) ? $form->get('IM_Block_Count') : 1;
    }
    else {
      $blockId = $blockCount;
    }
    $form->applyFilter('__ALL__', 'trim');

    //IM provider select
    $form->addField("im[$blockId][provider_id]", ['entity' => 'im', 'class' => 'eight', 'placeholder' => NULL, 'title' => ts('IM Type %1', [1 => $blockId])]);
    //Block type select
    $form->addField("im[$blockId][location_type_id]", ['entity' => 'im', 'class' => 'eight', 'placeholder' => NULL, 'option_url' => NULL, 'title' => ts('IM Location %1', [1 => $blockId])]);

    //IM box
    $form->addField("im[$blockId][name]", ['entity' => 'im', 'aria-label' => ts('Instant Messenger %1', [1 => $blockId])]);
    //is_Primary radio
    $js = ['id' => 'IM_' . $blockId . '_IsPrimary', 'aria-label' => ts('Instant Messenger %1 is primary?', [1 => $blockId])];
    if (!$blockEdit) {
      $js['onClick'] = 'singleSelect( this.id );';
    }

    $form->addElement('radio', "im[$blockId][is_primary]", '', '', '1', $js);
  }

}
