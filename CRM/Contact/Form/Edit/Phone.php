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
 * Form helper class for a phone object.
 */
class CRM_Contact_Form_Edit_Phone {

  /**
   * Build the form object elements for a phone object.
   *
   * @param CRM_Core_Form $form
   *   Reference to the form object.
   * @param int $addressBlockCount
   *   Block number to build.
   * @param bool $blockEdit
   *   deprecated variable.
   *
   * @deprecated since 6.3 will be removed around 6.10
   */
  public static function buildQuickForm(&$form, $addressBlockCount = NULL, $blockEdit = FALSE) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    // passing this via the session is AWFUL. we need to fix this
    if (!$addressBlockCount) {
      CRM_Core_Error::deprecatedWarning('pass in blockCount');
      $blockId = ($form->get('Phone_Block_Count')) ? $form->get('Phone_Block_Count') : 1;
    }
    else {
      $blockId = $addressBlockCount;
    }

    $form->applyFilter('__ALL__', 'trim');

    //phone type select
    $form->addField("phone[$blockId][phone_type_id]", [
      'entity' => 'phone',
      'class' => 'eight',
      'placeholder' => NULL,
      'title' => ts('Phone Type %1', [1 => $blockId]),
    ]);
    //main phone number with crm_phone class
    $form->addField("phone[$blockId][phone]", [
      'entity' => 'phone',
      'class' => 'crm_phone twelve',
      'aria-label' => ts('Phone %1', [1 => $blockId]),
      'label' => ts('Phone %1:', [1 => $blockId]),
    ]);
    $form->addField("phone[$blockId][phone_ext]", [
      'entity' => 'phone',
      'aria-label' => ts('Phone Extension %1', [1 => $blockId]),
      'label' => ts('ext.', ['context' => 'phone_ext']),
    ]);
    if (isset($form->_contactType) || $blockEdit) {
      //Block type select
      $form->addField("phone[$blockId][location_type_id]", [
        'entity' => 'phone',
        'class' => 'eight',
        'placeholder' => NULL,
        'option_url' => NULL,
        'title' => ts('Phone Location %1', [1 => $blockId]),
      ]);

      //is_Primary radio
      $js = ['id' => 'Phone_' . $blockId . '_IsPrimary', 'onClick' => 'singleSelect( this.id );', 'aria-label' => ts('Phone %1 is primary?', [1 => $blockId])];
      $form->addElement('radio', "phone[$blockId][is_primary]", '', '', '1', $js);
    }
    // TODO: set this up as a group, we need a valid phone_type_id if we have a  phone number
    // $form->addRule( "location[$locationId][phone][$locationId][phone]", ts('Phone number is not valid.'), 'phone' );
  }

}
