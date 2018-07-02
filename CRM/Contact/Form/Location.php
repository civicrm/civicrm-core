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
class CRM_Contact_Form_Location {

  /**
   * Set variables up before form is built.
   *
   * @param CRM_Core_Form $form
   */
  public static function preProcess(&$form) {
    $form->_addBlockName = CRM_Utils_Request::retrieve('block', 'String');
    $additionalblockCount = CRM_Utils_Request::retrieve('count', 'Positive');

    $form->assign('addBlock', FALSE);
    if ($form->_addBlockName && $additionalblockCount) {
      $form->assign('addBlock', TRUE);
      $form->assign('blockName', $form->_addBlockName);
      $form->assign('blockId', $additionalblockCount);
      $form->set($form->_addBlockName . '_Block_Count', $additionalblockCount);
    }

    if (is_a($form, 'CRM_Event_Form_ManageEvent_Location')
    || is_a($form, 'CRM_Contact_Form_Domain')) {
      $form->_blocks = array(
        'Address' => ts('Address'),
        'Email' => ts('Email'),
        'Phone' => ts('Phone'),
      );
    }

    $form->assign('blocks', $form->_blocks);
    $form->assign('className', CRM_Utils_System::getClassName($form));

    // get address sequence.
    if (!$addressSequence = $form->get('addressSequence')) {
      $addressSequence = CRM_Core_BAO_Address::addressSequence();
      $form->set('addressSequence', $addressSequence);
    }
    $form->assign('addressSequence', $addressSequence);
  }

  /**
   * Build the form object.
   *
   * @param CRM_Core_Form $form
   */
  public static function buildQuickForm(&$form) {
    // required for subsequent AJAX requests.
    $ajaxRequestBlocks = array();
    $generateAjaxRequest = 0;

    //build 1 instance of all blocks, without using ajax ...
    foreach ($form->_blocks as $blockName => $label) {
      $name = strtolower($blockName);

      $instances = array(1);
      if (!empty($_POST[$name]) && is_array($_POST[$name])) {
        $instances = array_keys($_POST[$name]);
      }
      elseif (property_exists($form, '_values') && !empty($form->_values[$name]) && is_array($form->_values[$name])) {
        $instances = array_keys($form->_values[$name]);
      }

      foreach ($instances as $instance) {
        if ($instance == 1) {
          $form->assign('addBlock', FALSE);
          $form->assign('blockId', $instance);
        }
        else {
          //we are going to build other block instances w/ AJAX
          $generateAjaxRequest++;
          $ajaxRequestBlocks[$blockName][$instance] = TRUE;
        }

        $form->set($blockName . '_Block_Count', $instance);
        $formName = 'CRM_Contact_Form_Edit_' . $blockName;
        $formName::buildQuickForm($form);
      }
    }

    //assign to generate AJAX request for building extra blocks.
    $form->assign('generateAjaxRequest', $generateAjaxRequest);
    $form->assign('ajaxRequestBlocks', $ajaxRequestBlocks);
  }

}
