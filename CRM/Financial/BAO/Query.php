<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @copyright CiviCRM LLC (c) 2004-2017
 */
class CRM_Financial_BAO_Query extends CRM_Core_BAO_Query {

  public static function select(&$query) {
    $returnProperties = array_keys(self::selectorReturnProperties());

    $contactFields = array('sort_name');
    foreach ($returnProperties as $fieldName) {
      if (!empty($query->_returnProperties[$fieldName])) {
        if (in_array($fieldName, $contactFields)) {
          $query->_select[$fieldName] = "contact_a.{$fieldName} as $fieldName";
          $query->_element[$fieldName] = 1;
          continue;
        }
        $columnName = str_replace('financial_trxn_', '', $fieldName);
        $query->_select[$fieldName] = "civicrm_financial_trxn.{$columnName} as $fieldName";
        $query->_element[$fieldName] = 1;
        $query->_tables['civicrm_contribution'] = 1;
        $query->_tables['civicrm_financial_trxn'] = 1;
      }
    }
  }

  /**
   * Add all the elements shared between contribute search and advnaced search.
   *
   * @param CRM_Core_Form $form
   */
  public static function buildSearchForm(&$form) {
    CRM_Core_Form_Date::buildDateRange($form, 'financial_trxn_trxn_date', 1, '_low', '_high', ts('From'), FALSE, FALSE);

    $form->add('text', 'financial_trxn_amount_low', ts('From'), array('size' => 8, 'maxlength' => 8));
    $form->addRule('financial_trxn_amount_low', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('9.99', ' '))), 'money');

    $form->add('text', 'financial_trxn_amount_high', ts('To'), array('size' => 8, 'maxlength' => 8));
    $form->addRule('financial_trxn_amount_high', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('99.99', ' '))), 'money');

    $form->add('text', 'contribution_id', ts('Invoice ID'), array('size' => 6, 'maxlength' => 8));

    $form->add('text', 'financial_trxn_trxn_id', ts('Transaction ID'), array('size' => 6, 'maxlength' => 8));

    foreach (array(
      'financial_trxn_currency' => 'Contribution',
      'financial_trxn_status_id' => 'Contribution',
      'financial_trxn_payment_instrument_id' => 'Contribution',
      'financial_trxn_card_type_id' => 'FinancialTrxn',
      'financial_trxn_check_number' => 'FinancialTrxn',
      'financial_trxn_pan_truncation' => 'FinancialTrxn',
    ) as $fieldName => $entity) {
      $columnName = str_replace('financial_trxn_', '', $fieldName);
      $columnName = ($columnName == 'status_id') ? 'contribution_status_id' : $columnName;
      $form->addField($fieldName, array('entity' => $entity, 'name' => $columnName, 'action' => 'get'));
    }

    // Add batch select
    $batches = CRM_Contribute_PseudoConstant::batch();

    if (!empty($batches)) {
      $form->add('select', 'contribution_batch_id',
        ts('Batch Name'),
        array(
          '' => ts('- any -'),
          // CRM-19325
          'IS NULL' => ts('None'),
        ) + $batches,
        FALSE, array('class' => 'crm-select2')
      );
    }

    $form->assign('validCiviContribute', TRUE);
    $form->setDefaults(array('contribution_test' => 0));
  }

  /**
   * Get the list of fields required to populate the selector.
   *
   * The default return properties array returns far too many fields for 'everyday use. Every field you add to this array
   * kills a small kitten so add carefully.
   */
   public static function selectorReturnProperties() {
     $properties = array(
       'sort_name' => 1,
       'financial_trxn_id' => 1,
       'financial_trxn_trxn_date' => 1,
       'financial_trxn_total_amount' => 1,
       'financial_trxn_currency' => 1,
       'financial_trxn_trxn_id' => 1,
       'financial_trxn_status_id' => 1,
       'financial_trxn_payment_processor_id' => 1,
       'financial_trxn_payment_instrument_id' => 1,
       'financial_trxn_card_type_id' => 1,
       'financial_trxn_check_number' => 1,
       'financial_trxn_pan_truncation' => 1,
     );

     return $properties;
   }

}
