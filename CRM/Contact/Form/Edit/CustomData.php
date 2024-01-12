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
   *
   * @throws \CRM_Core_Exception
   */
  public static function preProcess(&$form) {
    $customDataType = CRM_Utils_Request::retrieve('type', 'String');

    if ($customDataType) {
      $form->assign('addBlock', TRUE);
      $form->assign('blockName', 'CustomData');
    }

    CRM_Custom_Form_CustomData::preProcess($form, NULL, NULL, NULL,
      $customDataType ?: $form->_contactType
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
    $customValueCount = $form->_submitValues['hidden_custom_group_count'] ?? NULL;
    if (is_array($customValueCount)) {
      if (array_key_exists(0, $customValueCount)) {
        unset($customValueCount[0]);
      }
      $form->_customValueCount = $customValueCount;
      $form->assign('customValueCount', $customValueCount);
    }
    CRM_Custom_Form_CustomData::buildQuickForm($form);

    //build custom data.
    $contactSubType = NULL;
    if (!empty($_POST["hidden_custom"]) && !empty($_POST['contact_sub_type'])) {
      $contactSubType = $_POST['contact_sub_type'];
    }
    else {
      $contactSubType = $form->_values['contact_sub_type'] ?? NULL;
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
