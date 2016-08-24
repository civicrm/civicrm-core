<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but   |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This form records additional payments needed when event/contribution is partially paid.
 */
class CRM_Contribute_Form_PaymentDetails {

  /**
   * Set variables up before form is built.
   *
   * @param CRM_Core_Form $form
   */
  public static function preProcess() {

  }

  /**
   * This function sets the default values for the form in edit/view mode
   * the default values are retrieved from the database
   *
   * @param CRM_Core_Form $form
   * @param integer $contriId
   */
  public static function setDefaultValues(&$defaults, $contriId) {
    //CRM-13420
    if (empty($defaults['payment_instrument_id'])) {
      $defaults['payment_instrument_id'] = key(CRM_Core_OptionGroup::values('payment_instrument', FALSE, FALSE, FALSE, 'AND is_default = 1'));
    }

    list($defaults['receive_date'], $defaults['receive_date_time']) = CRM_Utils_Date::setDateDefaults();

    // CRM-4395
    if ($contriId) {
      $contribution = new CRM_Contribute_DAO_Contribution();
      $contribution->id = $contriId;
      $contribution->find(TRUE);
      foreach (array(
        'financial_type_id',
        'payment_instrument_id',
        'contribution_status_id',
        'receive_date',
        'total_amount',
      ) as $f) {
        if ($f == 'receive_date') {
          list($defaults['receive_date']) = CRM_Utils_Date::setDateDefaults($contribution->$f);
        }
        else {
          $defaults[$f] = $contribution->$f;
        }
      }
    }
  }

  /**
   * Build the form object.
   *
   * @param CRM_Core_Form $form
   */
  public static function buildQuickForm(&$form) {
    // Check permissions for financial type first
    if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()) {
      CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($financialTypes, $form->_action);
    }
    else {
      $financialTypes = CRM_Contribute_PseudoConstant::financialType();
    }
    $form->add('select', 'financial_type_id',
      ts('Financial Type'),
      array('' => ts('- select -')) + $financialTypes
    );

    $form->addDateTime('receive_date', ts('Received'), FALSE, array('formatType' => 'activityDateTime'));
    $form->add('select', 'payment_instrument_id',
      ts('Payment Method'),
      array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::paymentInstrument()
    );

    // don't show transaction id in batch update mode
    $path = CRM_Utils_System::currentPath();
    $form->assign('showTransactionId', FALSE);
    if ($path != 'civicrm/contact/search/basic') {
      $form->add('text', 'trxn_id', ts('Transaction ID'));
      $form->addRule('trxn_id', ts('Transaction ID already exists in Database.'),
        'objectExists', array('CRM_Contribute_DAO_Contribution', $form->_id, 'trxn_id')
      );
      $form->assign('showTransactionId', TRUE);
    }

    $form->add('select', 'credit_card_type',
      ts('Credit Card Type'),
      array('' => ts('- select -')) + CRM_Core_PseudoConstant::get('CRM_Financial_DAO_FinancialTrxn', 'credit_card_type')
    );
    $status = CRM_Contribute_PseudoConstant::contributionStatus();

    // CRM-14417 suppressing contribution statuses that are NOT relevant to new participant registrations
    $statusName = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    foreach (array(
      'Cancelled',
      'Failed',
      'In Progress',
      'Overdue',
      'Refunded',
      'Pending refund',
    ) as $suppress) {
      unset($status[CRM_Utils_Array::key($suppress, $statusName)]);
    }

    $form->add('select', 'contribution_status_id',
      ts('Payment Status'), $status
    );

    $form->add('text', 'check_number', ts('Check Number'),
      CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution', 'check_number')
    );

    $form->add('text', 'total_amount', ts('Amount'),
      CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution', 'total_amount')
    );
    $form->addRule('total_amount', ts('Please enter a valid amount.'), 'money');
  }

}
