<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * form helper class for an Demographics object
 */
class CRM_Contact_Form_Edit_CustomData {

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  static function preProcess(&$form) {
    $form->_type = CRM_Utils_Request::retrieve('type', 'String', CRM_Core_DAO::$_nullObject);
    $form->_subType = CRM_Utils_Request::retrieve('subType', 'String', CRM_Core_DAO::$_nullObject);

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
   * build the form elements for CustomData object
   *
   * @param CRM_Core_Form $form       reference to the form object
   *
   * @return void
   * @access public
   * @static
   */
  static function buildQuickForm(&$form) {
    if(!empty($form->_submitValues)) {
      if ($customValueCount = CRM_Utils_Array::value('hidden_custom_group_count', $form->_submitValues)) {
        if (is_array($customValueCount)) {
          if (array_key_exists(0, $customValueCount)) {
            unset($customValueCount[0]);
          }
          $form->_customValueCount = $customValueCount;
          $form->assign( 'customValueCount', $customValueCount);
        }
      }
    }
    CRM_Custom_Form_CustomData::buildQuickForm($form);

    //build custom data.
    $contactSubType = NULL;
    if (CRM_Utils_Array::value("hidden_custom", $_POST) &&
      CRM_Utils_Array::value('contact_sub_type', $_POST)
    ) {
      $contactSubType = $_POST['contact_sub_type'];
    }
    else {
      $contactSubType = CRM_Utils_Array::value('contact_sub_type', $form->_values);
    }
    $form->assign('contactType', $form->_contactType);
    $form->assign('contactSubType', $contactSubType);
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  static function setDefaultValues(&$form, &$defaults) {
    $defaults += CRM_Custom_Form_CustomData::setDefaultValues($form);
  }
}

