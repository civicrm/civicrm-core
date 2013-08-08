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
   * @param array   $paymentParams   array with payment related key
   * value pairs
   * @param array   $premiumParams   array with premium related key
   * value pairs
   * @param int     $contactID       contact id
     * @param int     $contributionTypeId   financial type id
   * @param int     $component   component id
   *
   * @return array associated array
   *
   * @static
   * @access public
   */
  static function processConfirm(&$form,
    &$paymentParams,
    &$premiumParams,
    $contactID,
    $grantTypeId,
    $component = 'grant',
    $fieldTypes = NULL
  ) {
    CRM_Core_Payment_Form::mapParams($form->_bltID, $form->_params, $paymentParams, TRUE);

    //CRM-11456
    $paymentParams['contributionPageID'] = $form->_params['contributionPageID'] = $form->_values['id'];


    $payment = NULL;
    $paymentObjError = ts('The system did not record payment details for this payment and so could not process the transaction. Please report this error to the site administrator.');
    if (CRM_Utils_Array::value('is_monetary', $form->_values) && $form->_amount > 0.0 && is_array($form->_paymentProcessor)) {
      $payment = CRM_Core_Payment::singleton($form->_mode, $form->_paymentProcessor, $form);
    }

    //fix for CRM-2062
    $now = date('YmdHis');

    $result = NULL;
   
      // this is not going to come back, i.e. we fill in the other details
      // when we get a callback from the payment processor
      // also add the contact ID and contribution ID to the params list
      $paymentParams['contactID'] = $form->_params['contactID'] = $contactID;
      $grant = CRM_Grant_Form_Grant_Confirm::processContribution(
        $form,
        $paymentParams,
        NULL,
        $contactID,
        $grantTypeId,
        TRUE, TRUE, TRUE
      );
      
      if ($grant) {
        $form->_params['contributionID'] = $grant->id;
      }
      
      $form->_params['contributionTypeID'] = $grantTypeId;
      $form->_params['item_name'] = $form->_params['description'];
      $form->_params['receive_date'] = $now;
      $form->set('params', $form->_params);
      // finally send an email receipt
      if ($grant) {
          
          $form->_values['contribution_id'] = $grant->id;
        
          
          CRM_Grant_BAO_GrantApplicationPage::sendMail($contactID, 
                                                        $form->_values, FALSE,
                                                        FALSE, $fieldTypes
                                                        );
      }
  }

 

  

  static function createCMSUser(&$params, $contactID, $mail) {
    // lets ensure we only create one CMS user
    static $created = FALSE;

    if ($created) {
      return;
    }
    $created = TRUE;

    if (CRM_Utils_Array::value('cms_create_account', $params)) {
      $params['contactID'] = $contactID;
      if (!CRM_Core_BAO_CMSUser::create($params, $mail)) {
        CRM_Core_Error::statusBounce(ts('Your profile is not saved and Account is not created.'));
      }
    }
  }

  static function processAPIContribution($params) {
    if (empty($params) || array_key_exists('error', $params)) {
      return FALSE;
    }

    // add contact using dedupe rule
    $dedupeParams = CRM_Dedupe_Finder::formatParams($params, 'Individual');
    $dedupeParams['check_permission'] = FALSE;
    $dupeIds = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual');
    // if we find more than one contact, use the first one
    if (CRM_Utils_Array::value(0, $dupeIds)) {
      $params['contact_id'] = $dupeIds[0];
    }
    $contact = CRM_Contact_BAO_Contact::create($params);
    if (!$contact->id) {
      return FALSE;
    }

    // only pass transaction params to contribution::create, if available
    if (array_key_exists('transaction', $params)) {
      $params = $params['transaction'];
      $params['contact_id'] = $contact->id;
    }

    // handle contribution custom data
    $customFields = CRM_Core_BAO_CustomField::getFields('Grant',
      FALSE,
      FALSE,
      CRM_Utils_Array::value('financial_type_id',
        $params
      )
    );
    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
      $customFields,
      CRM_Utils_Array::value('id', $params, NULL),
      'Grant'
    );
   
    $contribution = &CRM_Contribute_BAO_Contribution::create($params,
      CRM_Core_DAO::$_nullArray
    );
    if (!$contribution->id) {
      return FALSE;
    }

    return TRUE;
  }
  
  }


