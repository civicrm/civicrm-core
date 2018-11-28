<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
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
    $form->addField("im[$blockId][provider_id]", array('entity' => 'im', 'class' => 'eight', 'placeholder' => NULL));
    //Block type select
    $form->addField("im[$blockId][location_type_id]", array('entity' => 'im', 'class' => 'eight', 'placeholder' => NULL, 'option_url' => NULL));

    //IM box
    $form->addField("im[$blockId][name]", array('entity' => 'im', 'aria-label' => ts('Instant Messenger %1', [1 => $blockId])));
    //is_Primary radio
    $js = array('id' => 'IM_' . $blockId . '_IsPrimary', 'aria-label' => ts('Instant Messenger %1 is primary?', [1 => $blockId]));
    if (!$blockEdit) {
      $js['onClick'] = 'singleSelect( this.id );';
    }

    $form->addElement('radio', "im[$blockId][is_primary]", '', '', '1', $js);
  }

}
