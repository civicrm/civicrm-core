<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * form helper class for a phone object
 */
class CRM_Contact_Form_Edit_Phone {

  /**
   * build the form elements for a phone object
   *
   * @param CRM_Core_Form $form       reference to the form object
   * @param int           $addressBlockCount block number to build
   * @param boolean       $blockEdit         is it block edit
   *
   * @return void
   * @access public
   * @static
   */
  static function buildQuickForm(&$form, $addressBlockCount = NULL, $blockEdit = FALSE) {
    // passing this via the session is AWFUL. we need to fix this
    if (!$addressBlockCount) {
      $blockId = ($form->get('Phone_Block_Count')) ? $form->get('Phone_Block_Count') : 1;
    }
    else {
      $blockId = $addressBlockCount;
    }

    $form->applyFilter('__ALL__', 'trim');

    //phone type select
    $form->addSelect("phone[$blockId][phone_type_id]", array('entity' => 'phone', 'class' => 'eight', 'placeholder' => NULL));

    //main phone number with crm_phone class
    $form->add('text', "phone[$blockId][phone]", ts('Phone'), array_merge(CRM_Core_DAO::getAttribute('CRM_Core_DAO_Phone', 'phone'), array('class' => 'crm_phone twelve')));
    // phone extension
    $form->addElement('text', "phone[$blockId][phone_ext]", ts('Extension'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Phone', 'phone_ext'));

    if (isset($form->_contactType) || $blockEdit) {
      //Block type select
      $form->addSelect("phone[$blockId][location_type_id]", array('entity' => 'phone', 'class' => 'eight', 'placeholder' => NULL));

      //is_Primary radio
      $js = array('id' => 'Phone_' . $blockId . '_IsPrimary', 'onClick' => 'singleSelect( this.id );');
      $form->addElement('radio', "phone[$blockId][is_primary]", '', '', '1', $js);
    }
    // TODO: set this up as a group, we need a valid phone_type_id if we have a  phone number
    // $form->addRule( "location[$locationId][phone][$locationId][phone]", ts('Phone number is not valid.'), 'phone' );
  }
}

