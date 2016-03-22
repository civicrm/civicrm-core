<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
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
   *   Is it block edit.
   */
  public static function buildQuickForm(&$form, $addressBlockCount = NULL, $blockEdit = FALSE) {
    // passing this via the session is AWFUL. we need to fix this
    if (!$addressBlockCount) {
      $blockId = ($form->get('Phone_Block_Count')) ? $form->get('Phone_Block_Count') : 1;
    }
    else {
      $blockId = $addressBlockCount;
    }

    $form->applyFilter('__ALL__', 'trim');

    //phone type select
    $form->addField("phone[$blockId][phone_type_id]", array(
      'entity' => 'phone',
      'class' => 'eight',
      'placeholder' => NULL,
    ));
    //main phone number with crm_phone class
    $form->addField("phone[$blockId][phone]", array('entity' => 'phone', 'class' => 'crm_phone twelve'));
    $form->addField("phone[$blockId][phone_ext]", array('entity' => 'phone'));
    if (isset($form->_contactType) || $blockEdit) {
      //Block type select
      $form->addField("phone[$blockId][location_type_id]", array(
        'entity' => 'phone',
          'class' => 'eight',
          'placeholder' => NULL,
          'option_url' => NULL,
        ));

      //is_Primary radio
      $js = array('id' => 'Phone_' . $blockId . '_IsPrimary', 'onClick' => 'singleSelect( this.id );');
      $form->addElement('radio', "phone[$blockId][is_primary]", '', '', '1', $js);
    }
    // TODO: set this up as a group, we need a valid phone_type_id if we have a  phone number
    // $form->addRule( "location[$locationId][phone][$locationId][phone]", ts('Phone number is not valid.'), 'phone' );
  }

}
