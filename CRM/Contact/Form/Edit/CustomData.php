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
 * Form helper class for an Demographics object.
 */
class CRM_Contact_Form_Edit_CustomData {

  /**
   * Build all the data structures needed to build the form.
   *
   * @param CRM_Core_Form $form
   */
  public static function preProcess(&$form) {
    $form->_type = CRM_Utils_Request::retrieve('type', 'String');
    $form->_subType = CRM_Utils_Request::retrieve('subType', 'String');

    //build the custom data as other blocks.
    //$form->assign( "addBlock", false );
    if ($form->_type) {
      $form->_addBlockName = 'CustomData';
      $form->assign("addBlock", TRUE);
      $form->assign("blockName", $form->_addBlockName);
    }

    CRM_Custom_Form_CustomData::preProcess($form, NULL, $form->_subType, NULL,
      ($form->_type) ? $form->_type : $form->_contactType
    );

    //assign group tree after build.
    $form->assign('groupTree', $form->_groupTree);
  }

  /**
   * Build the form object elements for CustomData object.
   *
   * @param CRM_Core_Form $form
   *   Reference to the form object.
   */
  public static function buildQuickForm(&$form) {
    if (!empty($form->_submitValues)) {
      if ($customValueCount = CRM_Utils_Array::value('hidden_custom_group_count', $form->_submitValues)) {
        if (is_array($customValueCount)) {
          if (array_key_exists(0, $customValueCount)) {
            unset($customValueCount[0]);
          }
          $form->_customValueCount = $customValueCount;
          $form->assign('customValueCount', $customValueCount);
        }
      }
    }
    CRM_Custom_Form_CustomData::buildQuickForm($form);

    //build custom data.
    $contactSubType = NULL;
    if (!empty($_POST["hidden_custom"]) && !empty($_POST['contact_sub_type'])) {
      $contactSubType = $_POST['contact_sub_type'];
    }
    else {
      $contactSubType = CRM_Utils_Array::value('contact_sub_type', $form->_values);
    }
    $form->assign('contactType', $form->_contactType);
    $form->assign('contactSubType', $contactSubType);
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @param CRM_Core_Form $form
   * @param array $defaults
   */
  public static function setDefaultValues(&$form, &$defaults) {
    $defaults += CRM_Custom_Form_CustomData::setDefaultValues($form);
  }

}
