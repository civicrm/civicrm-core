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
class CRM_Grant_BAO_Grant_Utils {

  /**
   * Function to process payment after confirmation
   *
   * @param object  $form   form object
   * @param int     $contactID       contact id
   * @param int     $component   component id
   *
   * @return array associated array
   *
   * @static
   * @access public
   */
  static function processConfirm(&$form,
    $params,
    $contactID,
    $grantTypeId,
    $component = 'grant',
    $fieldTypes = NULL
  ) {

    $params['grantApplicationPageID'] = $form->_params['grantApplicationPageID'] = $form->_values['id'];
    $params['contactID'] = $form->_params['contactID'] = $contactID;
    $grant = CRM_Grant_Form_Grant_Confirm::processApplication(
      $form,
      $params,
      $contactID,
      $grantTypeId,
      TRUE
    );
      
    if ($grant) {
      $form->_params['grantID'] = $grant->id;
    }
    $now = date('YmdHis');
    $form->_params['grantTypeID'] = $grantTypeId;
    $form->_params['item_name'] = $form->_params['description'];
    $form->_params['application_received_date'] = $now;
    $form->set('params', $form->_params);
    // finally send an email receipt
    if ($grant) {   
      $form->_values['grant_id'] = $grant->id;
      CRM_Grant_BAO_GrantApplicationPage::sendMail($contactID, 
        $form->_values,
        FALSE,
        $fieldTypes
      );
    }
  }
 }


