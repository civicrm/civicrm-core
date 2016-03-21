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
 * Form helper class for an OpenID object.
 */
class CRM_Contact_Form_Edit_OpenID {

  /**
   * Build the form object elements for an open id object.
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
      $blockId = ($form->get('OpenID_Block_Count')) ? $form->get('OpenID_Block_Count') : 1;
    }
    else {
      $blockId = $blockCount;
    }
    $form->applyFilter('__ALL__', 'trim');

    $form->addElement('text', "openid[$blockId][openid]", ts('OpenID'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_OpenID', 'openid')
    );
    $form->addRule("openid[$blockId][openid]", ts('OpenID is not a valid URL.'), 'url');

    //Block type
    $form->addElement('select', "openid[$blockId][location_type_id]", '', CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id'));

    //is_Primary radio
    $js = array('id' => "OpenID_" . $blockId . "_IsPrimary");
    if (!$blockEdit) {
      $js['onClick'] = 'singleSelect( this.id );';
    }

    $form->addElement('radio', "openid[$blockId][is_primary]", '', '', '1', $js);
  }

}
