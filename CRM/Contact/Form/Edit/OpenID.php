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
   * @deprecated since 6.3 will be removed around 6.10
   */
  public static function buildQuickForm(&$form, $blockCount = NULL, $blockEdit = FALSE) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    if (!$blockCount) {
      CRM_Core_Error::deprecatedWarning('pass in blockCount');
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
    $form->addElement('select', "openid[$blockId][location_type_id]", '', CRM_Core_DAO_Address::buildOptions('location_type_id'));

    //is_Primary radio
    $js = ['id' => "OpenID_" . $blockId . "_IsPrimary"];
    if (!$blockEdit) {
      $js['onClick'] = 'singleSelect( this.id );';
    }

    $form->addElement('radio', "openid[$blockId][is_primary]", '', '', '1', $js);
  }

}
