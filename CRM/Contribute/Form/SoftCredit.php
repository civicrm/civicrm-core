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
    // by default generate 5 blocks
    $form->_softCredit['item_count'] = 6;
    for ($rowNumber = 1; $rowNumber <= $form->_softCredit['item_count']; $rowNumber++) {
      CRM_Contact_Form_NewContact::buildQuickForm($form, $rowNumber, NULL, FALSE, $prefix);
      $form->addMoney("{$prefix}amount[{$rowNumber}]", ts('Amount'));
    }

    $form->assign('rowCount', $form->_softCredit['item_count']);

    // Tell tpl to hide Soft Credit field if contribution is linked directly to a PCP Page
    if (CRM_Utils_Array::value('pcp_made_through_id', $form->_values)) {
      $form->assign('pcpLinked', 1);
    }
    $form->addElement('hidden', 'soft_contact_id', '', array('id' => 'soft_contact_id'));
  }

  /**
   * Function used to set defaults for soft credit block
   */
  static function setDefaultValues(&$defaults) {
    $csParams = array('contribution_id' => $defaults['id']);
    $softCredit = CRM_Contribute_BAO_Contribution::getSoftContribution($csParams, TRUE);

    if (CRM_Utils_Array::value('soft_credit_to', $softCredit)) {
      $softCredit['sort_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
        $softCredit['soft_credit_to'], 'sort_name'
      );
    }
    $values['soft_credit_to'] = CRM_Utils_Array::value('sort_name', $softCredit);
    $values['softID'] = CRM_Utils_Array::value('soft_credit_id', $softCredit);
    $values['soft_contact_id'] = CRM_Utils_Array::value('soft_credit_to', $softCredit);

    if (CRM_Utils_Array::value('pcp_id', $softCredit)) {
      $pcpId = CRM_Utils_Array::value('pcp_id', $softCredit);
      $pcpTitle = CRM_Core_DAO::getFieldValue('CRM_PCP_DAO_PCP', $pcpId, 'title');
      $contributionPageTitle = CRM_PCP_BAO_PCP::getPcpPageTitle($pcpId, 'contribute');
      $values['pcp_made_through'] = CRM_Utils_Array::value('sort_name', $softCredit) . " :: " . $pcpTitle . " :: " . $contributionPageTitle;
      $values['pcp_made_through_id'] = CRM_Utils_Array::value('pcp_id', $softCredit);
      $values['pcp_display_in_roll'] = CRM_Utils_Array::value('pcp_display_in_roll', $softCredit);
      $values['pcp_roll_nickname'] = CRM_Utils_Array::value('pcp_roll_nickname', $softCredit);
      $values['pcp_personal_note'] = CRM_Utils_Array::value('pcp_personal_note', $softCredit);
    }

  }
}

