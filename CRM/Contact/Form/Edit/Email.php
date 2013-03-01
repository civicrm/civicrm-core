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
 * form helper class for an Email object
 */
class CRM_Contact_Form_Edit_Email {

  /**
   * build the form elements for an email object
   *
   * @param CRM_Core_Form $form              reference to the form object
   * @param int           $blockCount block number to build
   * @param boolean       $blockEdit         is it block edit
   *
   * @return void
   * @access public
   * @static
   */
  static function buildQuickForm(&$form, $blockCount = NULL, $blockEdit = FALSE) {
    // passing this via the session is AWFUL. we need to fix this
    if (!$blockCount) {
      $blockId = ($form->get('Email_Block_Count')) ? $form->get('Email_Block_Count') : 1;
    }
    else {
      $blockId = $blockCount;
    }

    $form->applyFilter('__ALL__', 'trim');

    //Email box
    $form->addElement('text', "email[$blockId][email]", ts('Email'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Email', 'email'));
    $form->addRule("email[$blockId][email]", ts('Email is not valid.'), 'email');
    if (isset($form->_contactType) || $blockEdit) {
      //Block type
      $form->addElement('select', "email[$blockId][location_type_id]", '', CRM_Core_PseudoConstant::locationType());

      $multipleBulk = CRM_Core_BAO_Email::isMultipleBulkMail();

      //On-hold select
      if ($multipleBulk) {
        $holdOptions = array(0 => ts('- select -'),
          1 => ts('On Hold Bounce'),
          2 => ts('On Hold Opt Out'),
        );
        $form->addElement('select', "email[$blockId][on_hold]", '', $holdOptions);
      }
      else {
        $form->addElement('advcheckbox', "email[$blockId][on_hold]", NULL);
      }

      //Bulkmail checkbox
      $form->assign('multipleBulk', $multipleBulk);
      if ($multipleBulk) {
        $js = array('id' => "Email_" . $blockId . "_IsBulkmail");
        $form->addElement('advcheckbox', "email[$blockId][is_bulkmail]", NULL, '', $js);
      }
      else {
        $js = array('id' => "Email_" . $blockId . "_IsBulkmail");
        if (!$blockEdit) {
          $js['onClick'] = 'singleSelect( this.id );';
        }
        $form->addElement('radio', "email[$blockId][is_bulkmail]", '', '', '1', $js);
      }

      //is_Primary radio
      $js = array('id' => "Email_" . $blockId . "_IsPrimary");
      if (!$blockEdit) {
        $js['onClick'] = 'singleSelect( this.id );';
      }

      $form->addElement('radio', "email[$blockId][is_primary]", '', '', '1', $js);

      if (CRM_Utils_System::getClassName($form) == 'CRM_Contact_Form_Contact') {

        $form->add('textarea', "email[$blockId][signature_text]", ts('Signature (Text)'),
          array('rows' => 2, 'cols' => 40)
        );

        $form->addWysiwyg("email[$blockId][signature_html]", ts('Signature (HTML)'),
          array('rows' => 2, 'cols' => 40)
        );
      }
    }
  }
}

