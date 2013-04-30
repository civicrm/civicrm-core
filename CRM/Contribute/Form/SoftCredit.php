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
 * This class build form elements for select exitsing or create new soft block
 */
class CRM_Contribute_Form_SoftCredit {

  /**
   * Function used to build form element for soft credit block
   *
   * @param object   $form form object
   * @access public
   *
   * @return void
   */
  static function buildQuickForm(&$form) {
    $prefix = 'soft_credit_';
    $form->_softCredit['item_count'] = 10;
    for ($rowNumber = 1; $rowNumber <= $form->_softCredit['item_count']; $rowNumber++) {
      CRM_Contact_Form_NewContact::buildQuickForm($form, $rowNumber, NULL, FALSE, $prefix);
      $form->add('text', "{$prefix}contact[{$rowNumber}][amount]");
    }
    
    // If we have a contact for this contribution, pass cid= to the dataUrl to exclude current contact from autocomplete results
    if ($form->_contactID) {
      $dataUrl = CRM_Utils_System::url('civicrm/ajax/rest',
        "className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&reset=1&context=softcredit&cid={$form->_contactID}",
        FALSE, NULL, FALSE
      );
    } 
    else {
      $dataUrl = CRM_Utils_System::url('civicrm/ajax/rest',
        'className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&reset=1&context=softcredit',
        FALSE, NULL, FALSE
      );
    }
    $form->assign('dataUrl', $dataUrl);

    $form->addElement('text', 'soft_credit_to', ts('Soft Credit To'));
    // Tell tpl to hide Soft Credit field if contribution is linked directly to a PCP Page
    if (CRM_Utils_Array::value('pcp_made_through_id', $form->_values)) {
      $form->assign('pcpLinked', 1);
    }
    $form->addElement('hidden', 'soft_contact_id', '', array('id' => 'soft_contact_id'));
  }
}

