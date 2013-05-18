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
class CRM_Contribute_BAO_Query {

  /**
   * static field for all the export/import contribution fields
   *
   * @var array
   * @static
   */
  static $_contributionFields = NULL;

  /**
   * Function get the import/export fields for contribution
   *
   * @return array self::$_contributionFields  associative array of contribution fields
   * @static
   */
  static function &getFields() {
    if (!self::$_contributionFields) {
      self::$_contributionFields = array();

      $fields = CRM_Contribute_BAO_Contribution::exportableFields();

      unset($fields['contribution_contact_id']);

      self::$_contributionFields = $fields;
    }
    return self::$_contributionFields;
  }

  /**
   * if contributions are involved, add the specific contribute fields
   *
   * @return void
   * @access public
   */
  static function select(&$query) {
    // if contribute mode add contribution id
    if ($query->_mode & CRM_Contact_BAO_Query::MODE_CONTRIBUTE) {
      $query->_select['contribution_id'] = "civicrm_contribution.id as contribution_id";
      $query->_element['contribution_id'] = 1;
      $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
    }

    // get financial_type
    if (CRM_Utils_Array::value('financial_type', $query->_returnProperties)) {
      $query->_select['financial_type']  = "civicrm_financial_type.name as financial_type";
      $query->_element['financial_type'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['civicrm_financial_type'] = 1;
    }

    // get accounting code
    if (CRM_Utils_Array::value( 'accounting_code', $query->_returnProperties)) {
      $query->_select['accounting_code']  = "civicrm_financial_account.accounting_code as accounting_code";
      $query->_element['accounting_code'] = 1;
      $query->_tables['civicrm_accounting_code'] = 1;
      $query->_tables['civicrm_financial_account'] = 1;
    }

    if (CRM_Utils_Array::value('contribution_note', $query->_returnProperties)) {
      $query->_select['contribution_note'] = "civicrm_note.note as contribution_note";
      $query->_element['contribution_note'] = 1;
      $query->_tables['contribution_note'] = 1;
    }

    if (CRM_Utils_Array::value('contribution_batch', $query->_returnProperties)) {
      $query->_select['contribution_batch'] = "civicrm_batch.title as contribution_batch";
      $query->_element['contribution_batch'] = 1;
      $query->_tables['contribution_batch'] = 1;
    }

    // get contribution_status
    if (CRM_Utils_Array::value('contribution_status_id', $query->_returnProperties)) {
      $query->_select['contribution_status_id'] = "contribution_status.value as contribution_status_id";
      $query->_element['contribution_status_id'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['contribution_status'] = 1;
    }

    // get contribution_status label
    if (CRM_Utils_Array::value('contribution_status', $query->_returnProperties)) {
      $query->_select['contribution_status'] = "contribution_status.label as contribution_status";
      $query->_element['contribution_status'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['contribution_status'] = 1;
    }

    // get payment instruments
    if (CRM_Utils_Array::value('payment_instrument', $query->_returnProperties)) {
      $query->_select['contribution_payment_instrument'] = "payment_instrument.name as contribution_payment_instrument";
      $query->_element['contribution_payment_instrument'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['contribution_payment_instrument'] = 1;
    }

    // get honor contact name
    if (CRM_Utils_Array::value('honor_contact_name', $query->_returnProperties)) {
      $query->_select['contribution_honor_contact_name'] = "civicrm_contact_c.display_name as contribution_honor_contact_name";
      $query->_element['contribution_honor_contact_name'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['contribution_honor_contact_name'] = 1;
    }

    // get honor type label
    if (CRM_Utils_Array::value('honor_type_label', $query->_returnProperties)) {
      $query->_select['contribution_honor_type_label'] = "honor_type.label as contribution_honor_type_label";
      $query->_element['contribution_honor_type_label'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['contribution_honor_type_label'] = 1;
    }

    // get honor contact email
    if (CRM_Utils_Array::value('honor_contact_email', $query->_returnProperties)) {
      $query->_select['contribution_honor_contact_email'] = "honor_email.email as contribution_honor_contact_email";
      $query->_element['contribution_honor_contact_email'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['contribution_honor_contact_email'] = 1;
    }

    // get honor contact id
    if (CRM_Utils_Array::value('honor_contact_id', $query->_returnProperties)) {
      $query->_select['contribution_honor_contact_id'] = "civicrm_contribution.honor_contact_id as contribution_honor_contact_id";
      $query->_element['contribution_honor_contact_id'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
    }


    if (CRM_Utils_Array::value('check_number', $query->_returnProperties)) {
      $query->_select['contribution_check_number'] = "civicrm_contribution.check_number as contribution_check_number";
      $query->_element['contribution_check_number'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
    }

    if (CRM_Utils_Array::value('contribution_campaign_id', $query->_returnProperties)) {
      $query->_select['contribution_campaign_id'] = 'civicrm_contribution.campaign_id as contribution_campaign_id';
      $query->_element['contribution_campaign_id'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
    }

    // LCD 716
    if (CRM_Utils_Array::value('soft_credit_name', $query->_returnProperties)) {
      $query->_select['contribution_soft_credit_name'] = "civicrm_contact_d.display_name as contribution_soft_credit_name";
      $query->_element['contribution_soft_credit_name'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['civicrm_contribution_soft'] = 1;
      $query->_tables['civicrm_contribution_soft_contact'] = 1;
    }

    if (CRM_Utils_Array::value('soft_credit_email', $query->_returnProperties)) {
      $query->_select['contribution_soft_credit_email'] = "soft_email.email as contribution_soft_credit_email";
      $query->_element['contribution_soft_credit_email'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['civicrm_contribution_soft'] = 1;
      $query->_tables['civicrm_contribution_soft_contact'] = 1;
      $query->_tables['civicrm_contribution_soft_email'] = 1;
    }

    if (CRM_Utils_Array::value('soft_credit_phone', $query->_returnProperties)) {
      $query->_select['contribution_soft_credit_email'] = "soft_phone.phone as contribution_soft_credit_phone";
      $query->_element['contribution_soft_credit_phone'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['civicrm_contribution_soft'] = 1;
      $query->_tables['civicrm_contribution_soft_contact'] = 1;
      $query->_tables['civicrm_contribution_soft_phone'] = 1;
    }
    // LCD 716 END
  }

  static function where(&$query) {
    $grouping = NULL;
    foreach (array_keys($query->_params) as $id) {
      if (!CRM_Utils_Array::value(0, $query->_params[$id])) {
        continue;
      }
      if (substr($query->_params[$id][0], 0, 13) == 'contribution_' || substr($query->_params[$id][0], 0, 10) == 'financial_') {
        if ($query->_mode == CRM_Contact_BAO_QUERY::MODE_CONTACTS) {
          $query->_useDistinct = TRUE;
        }
        // CRM-12065
        if (
          $query->_params[$id][0] == 'contribution_type_id' ||
          $query->_params[$id][0] == 'contribution_type'
        ) {
          CRM_Core_Session::setStatus(
            ts('The contribution type criteria is now obsolete, please update your smart group'),
            '',
            'alert'
          );
          continue;
        }

        $grouping = $query->_params[$id][3];
        self::whereClauseSingle($query->_params[$id], $query);
      }
    }
  }

  static function whereClauseSingle(&$values, &$query) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $fields = self::getFields();

    if (!empty($value) && !is_array($value)) {
      $quoteValue = "\"$value\"";
    }

    $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';

    switch ($name) {
      case 'contribution_date':
      case 'contribution_date_low':
      case 'contribution_date_low_time':
      case 'contribution_date_high':
      case 'contribution_date_high_time':
        // process to / from date
        $query->dateQueryBuilder($values,
          'civicrm_contribution', 'contribution_date', 'receive_date', 'Contribution Date'
        );
        return;

      case 'contribution_amount':
      case 'contribution_amount_low':
      case 'contribution_amount_high':
        // process min/max amount
        $query->numberRangeBuilder($values,
          'civicrm_contribution', 'contribution_amount',
          'total_amount', 'Contribution Amount',
          NULL
        );
        return;

      case 'contribution_total_amount':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.total_amount",
          $op, $value, "Money"
        );
        $query->_qill[$grouping][] = ts('Contribution Total Amount %1 %2', array(1 => $op, 2 => $value));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_thankyou_date_is_not_null':
        if ($value) {
          $op = "IS NOT NULL";
          $query->_qill[$grouping][] = ts('Contribution Thank-you Sent');
        }
        else {
          $op = "IS NULL";
          $query->_qill[$grouping][] = ts('Contribution Thank-you Not Sent');
        }
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.thankyou_date", $op);
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_receipt_date_is_not_null':
        if ($value) {
          $op = "IS NOT NULL";
          $query->_qill[$grouping][] = ts('Contribution Receipt Sent');
        }
        else {
          $op = "IS NULL";
          $query->_qill[$grouping][] = ts('Contribution Receipt Not Sent');
        }
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.receipt_date", $op);
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

        case 'financial_type_id':
        case 'financial_type':
        $cType = $value;
        $types = CRM_Contribute_PseudoConstant::financialType();
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.financial_type_id",
          $op, $value, "Integer"
        );
        $query->_qill[$grouping ][] = ts('Financial Type - %1', array(1 => $types[$cType]));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_page_id':
        $cPage = $value;
        $pages = CRM_Contribute_PseudoConstant::contributionPage();
        $query->_where[$grouping][] = "civicrm_contribution.contribution_page_id = $cPage";
        $query->_qill[$grouping][] = ts('Contribution Page - %1', array(1 => $pages[$cPage]));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_pcp_made_through_id':
        $pcPage = $value;
        $pcpages = CRM_Contribute_PseudoConstant::pcPage();
        $query->_where[$grouping][] = "civicrm_contribution_soft.pcp_id = $pcPage";
        $query->_qill[$grouping][] = ts('Personal Campaign Page - %1', array(1 => $pcpages[$pcPage]));
        $query->_tables['civicrm_contribution_soft'] = $query->_whereTables['civicrm_contribution_soft'] = 1;
        return;

      case 'contribution_payment_instrument_id':
      case 'contribution_payment_instrument':
        $pi = $value;
        $pis = CRM_Contribute_PseudoConstant::paymentInstrument();
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.payment_instrument_id",
          $op, $value, "Integer"
        );

        $query->_qill[$grouping][] = ts('Paid By - %1', array(1 => $pis[$pi]));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_in_honor_of':
        $name    = trim($value);
        $newName = str_replace(',', " ", $name);
        $pieces  = explode(' ', $newName);
        foreach ($pieces as $piece) {
          $value = $strtolower(CRM_Core_DAO::escapeString(trim($piece)));
          $value = "'%$value%'";
          $sub[] = " ( contact_b.sort_name LIKE $value )";
        }

        $query->_where[$grouping][] = ' ( ' . implode('  OR ', $sub) . ' ) ';
        $query->_qill[$grouping][] = ts('Honor name like - \'%1\'', array(1 => $name));
        $query->_tables['civicrm_contact_b'] = $query->_whereTables['civicrm_contact_b'] = 1;
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_status':
      case 'contribution_status_id':
        if (is_array($value)) {
          foreach ($value as $k => $v) {
            if ($v) {
              $val[$k] = $k;
            }
          }

          $status = implode(',', $val);

          if (count($val) > 1) {
            $op = 'IN';
            $status = "({$status})";
          }
        }
        else {
          $op = '=';
          $status = $value;
        }

        $statusValues = CRM_Core_OptionGroup::values("contribution_status");

        $names = array();
        if (isset($val) &&
          is_array($val)
        ) {
          foreach ($val as $id => $dontCare) {
            $names[] = $statusValues[$id];
          }
        }
        else {
          $names[] = $statusValues[$value];
        }

        $query->_qill[$grouping][] = ts('Contribution Status %1', array(1 => $op)) . ' ' . implode(' ' . ts('or') . ' ', $names);
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.contribution_status_id",
          $op,
          $status,
          "Integer"
        );
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_source':
        $value = $strtolower(CRM_Core_DAO::escapeString($value));
        if ($wildcard) {
          $value = "%$value%";
          $op = 'LIKE';
        }
        $wc = ($op != 'LIKE') ? "LOWER(civicrm_contribution.source)" : "civicrm_contribution.source";
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($wc, $op, $value, "String");
        $query->_qill[$grouping][] = ts('Contribution Source %1 %2', array(1 => $op, 2 => $quoteValue));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_trxn_id':
      case 'contribution_transaction_id':
        $wc = ($op != 'LIKE') ? "LOWER(civicrm_contribution.trxn_id)" : "civicrm_contribution.trxn_id";
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($wc, $op, $value, "String");
        $query->_qill[$grouping][] = ts('Transaction ID %1 %2', array(1 => $op, 2 => $quoteValue));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_check_number':
        $wc = ($op != 'LIKE') ? "LOWER(civicrm_contribution.check_number)" : "civicrm_contribution.check_number";
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($wc, $op, $value, "String");
        $query->_qill[$grouping][] = ts('Check Number %1 %2', array(1 => $op, 2 => $quoteValue));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_is_test':
      case 'contribution_test':
        // We dont want to include all tests for sql OR CRM-7827
        if (!$value || $query->getOperator() != 'OR') {
          $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.is_test", $op, $value, "Boolean");
          if ($value) {
            $query->_qill[$grouping][] = ts("Only Display Test Contributions");
          }
          $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        }
        return;

      case 'contribution_is_pay_later':
      case 'contribution_pay_later':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.is_pay_later", $op, $value, "Boolean");
        if ($value) {
          $query->_qill[$grouping][] = ts("Find Pay Later Contributions");
        }
        else {
          $query->_qill[$grouping][] = ts("Exclude Pay Later Contributions");
        }
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_recurring':
        if ($value) {
          $query->_where[$grouping][] = "civicrm_contribution.contribution_recur_id IS NOT NULL";
          $query->_qill[$grouping][] = ts("Find Recurring Contributions");
          $query->_tables['civicrm_contribution_recur'] = $query->_whereTables['civicrm_contribution_recur'] = 1;
        }
        else {
          $query->_where[$grouping][] = "civicrm_contribution.contribution_recur_id IS NULL";
          $query->_qill[$grouping][] = ts("Exclude Recurring Contributions");
        }
        return;

      case 'contribution_recur_id':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.contribution_recur_id",
          $op, $value, "Integer"
        );
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_id':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.id", $op, $value, "Integer");
        $query->_qill[$grouping][] = ts('Contribution ID %1 %2', array(1 => $op, 2 => $quoteValue));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_note':
        $value = $strtolower(CRM_Core_DAO::escapeString($value));
        if ($wildcard) {
          $value = "%$value%";
          $op = 'LIKE';
        }
        $wc = ($op != 'LIKE') ? "LOWER(civicrm_note.note)" : "civicrm_note.note";
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($wc, $op, $value, "String");
        $query->_qill[$grouping][] = ts('Contribution Note %1 %2', array(1 => $op, 2 => $quoteValue));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = $query->_whereTables['contribution_note'] = 1;
        return;

      case 'contribution_membership_id':
        $query->_where[$grouping][] = " civicrm_membership.id $op $value";
        $query->_tables['contribution_membership'] = $query->_whereTables['contribution_membership'] = 1;

        return;

      case 'contribution_participant_id':
        $query->_where[$grouping][] = " civicrm_participant.id $op $value";
        $query->_tables['contribution_participant'] = $query->_whereTables['contribution_participant'] = 1;
        return;

      case 'contribution_pcp_display_in_roll':
        $query->_where[$grouping][] = " civicrm_contribution_soft.pcp_display_in_roll $op '$value'";
        if ($value) {
          $query->_qill[$grouping][] = ts("Personal Campaign Page Honor Roll");
        }
        else {
          $query->_qill[$grouping][] = ts("NOT Personal Campaign Page Honor Roll");
        }
        $query->_tables['civicrm_contribution_soft'] = $query->_whereTables['civicrm_contribution_soft'] = 1;
        return;

      // Supporting search for currency type -- CRM-4711

      case 'contribution_currency_type':
        $currencySymbol = CRM_Core_PseudoConstant::currencySymbols('name');
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.currency",
          $op, $currencySymbol[$value], "String"
        );
        $query->_qill[$grouping][] = ts('Currency Type - %1', array(1 => $currencySymbol[$value]));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_campaign_id':
        $campParams = array(
          'op' => $op,
          'campaign' => $value,
          'grouping' => $grouping,
          'tableName' => 'civicrm_contribution',
        );
        CRM_Campaign_BAO_Query::componentSearchClause($campParams, $query);
        return;

      case 'contribution_batch_id':
        $batches = CRM_Batch_BAO_Batch::getBatches();
        $query->_where[$grouping][] = " civicrm_entity_batch.batch_id $op $value";
        $query->_qill[$grouping][] = ts('Batch Name %1 %2', array(1 => $op, 2 => $batches[$value]));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        $query->_tables['contribution_batch'] = $query->_whereTables['contribution_batch'] = 1;
        return;

      default:
        //all other elements are handle in this case
        $fldName    = substr($name, 13);
        if (!isset($fields[$fldName])) {
          // CRM-12597
          CRM_Core_Session::setStatus(ts(
              'We did not recognize the search field: %1. Please check and fix your contribution related smart groups.',
              array(1 => $fldName)
            )
          );
          return;
        }
        $whereTable = $fields[$fldName];
        $value      = trim($value);

        //contribution fields (decimal fields) which don't require a quote in where clause.
        $moneyFields = array('non_deductible_amount', 'fee_amount', 'net_amount');
        //date fields
        $dateFields = array('receive_date', 'cancel_date', 'receipt_date', 'thankyou_date', 'fulfilled_date');

        if (in_array($fldName, $dateFields)) {
          $dataType = "Date";
        }
        elseif (in_array($fldName, $moneyFields)) {
          $dataType = "Money";
        }
        else {
          $dataType = "String";
        }

        $wc = ($op != 'LIKE' && $dataType != 'Date') ? "LOWER($whereTable[where])" : "$whereTable[where]";
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($wc, $op, $value, $dataType);
        $query->_qill[$grouping][] = "$whereTable[title] $op $quoteValue";
        list($tableName, $fieldName) = explode('.', $whereTable['where'], 2);
        $query->_tables[$tableName] = $query->_whereTables[$tableName] = 1;
        if ($tableName == 'civicrm_contribution_product') {
          $query->_tables['civicrm_product'] = $query->_whereTables['civicrm_product'] = 1;
          $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        }
        else {
          $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        }
    }
  }

  static function from($name, $mode, $side) {
    $from = NULL;
    switch ($name) {
      case 'civicrm_contribution':
        $from = " $side JOIN civicrm_contribution ON civicrm_contribution.contact_id = contact_a.id ";
        break;

      case 'civicrm_contribution_recur':
        $from = " $side JOIN civicrm_contribution_recur ON civicrm_contribution.contribution_recur_id = civicrm_contribution_recur.id ";
        break;

      case 'civicrm_financial_type':
        if ($mode & CRM_Contact_BAO_Query::MODE_CONTRIBUTE) {
          $from = " INNER JOIN civicrm_financial_type ON civicrm_contribution.financial_type_id = civicrm_financial_type.id ";
        }
        else {
          $from = " $side JOIN civicrm_financial_type ON civicrm_contribution.financial_type_id = civicrm_financial_type.id ";
        }
        break;

      case 'civicrm_accounting_code':
        $from = " $side JOIN civicrm_entity_financial_account ON civicrm_entity_financial_account.entity_id = civicrm_contribution.financial_type_id AND civicrm_entity_financial_account.entity_table = 'civicrm_financial_type' ";
        $from .= " INNER JOIN civicrm_financial_account ON civicrm_financial_account.id = civicrm_entity_financial_account.financial_account_id ";
        $from .= " INNER JOIN civicrm_option_value cov ON cov.value = civicrm_entity_financial_account.account_relationship AND cov.name = 'Income Account is' ";
        $from .= " INNER JOIN civicrm_option_group cog ON cog.id = cov.option_group_id AND cog.name = 'account_relationship' ";
        break;

      case 'civicrm_contribution_page':
        $from = " $side JOIN civicrm_contribution_page ON civicrm_contribution.contribution_page ON civicrm_contribution.contribution_page.id";
        break;

      case 'civicrm_product':
        $from = " $side  JOIN civicrm_contribution_product ON civicrm_contribution_product.contribution_id = civicrm_contribution.id";
        $from .= " $side  JOIN civicrm_product ON civicrm_contribution_product.product_id =civicrm_product.id ";
        break;

      case 'contribution_payment_instrument':
        $from = " $side JOIN civicrm_option_group option_group_payment_instrument ON ( option_group_payment_instrument.name = 'payment_instrument')";
        $from .= " $side JOIN civicrm_option_value payment_instrument ON (civicrm_contribution.payment_instrument_id = payment_instrument.value
                               AND option_group_payment_instrument.id = payment_instrument.option_group_id ) ";
        break;

      case 'civicrm_contact_b':
        $from .= " $side JOIN civicrm_contact contact_b ON (civicrm_contribution.honor_contact_id = contact_b.id )";
        break;

      case 'contribution_status':
        $from = " $side JOIN civicrm_option_group option_group_contribution_status ON (option_group_contribution_status.name = 'contribution_status')";
        $from .= " $side JOIN civicrm_option_value contribution_status ON (civicrm_contribution.contribution_status_id = contribution_status.value
                               AND option_group_contribution_status.id = contribution_status.option_group_id ) ";
        break;

      case 'contribution_note':
        $from .= " $side JOIN civicrm_note ON ( civicrm_note.entity_table = 'civicrm_contribution' AND
                                                    civicrm_contribution.id = civicrm_note.entity_id )";
        break;

      case 'contribution_honor_contact_name':
        $from .= " $side JOIN civicrm_contact civicrm_contact_c ON (civicrm_contribution.honor_contact_id = civicrm_contact_c.id )";
        break;

      case 'contribution_honor_contact_email':
        $from .= " $side JOIN civicrm_email as honor_email ON (civicrm_contribution.honor_contact_id = honor_email.contact_id AND honor_email.is_primary = 1 )";
        break;

      case 'contribution_honor_type_label':
        $from = " $side JOIN civicrm_option_group option_group_honor_type ON ( option_group_honor_type.name = 'honor_type')";
        $from .= " $side JOIN civicrm_option_value honor_type ON (civicrm_contribution.honor_type_id = honor_type.value
                               AND option_group_honor_type.id = honor_type.option_group_id ) ";
        break;

      case 'contribution_membership':
        $from = " $side  JOIN civicrm_membership_payment ON civicrm_membership_payment.contribution_id = civicrm_contribution.id";
        $from .= " $side  JOIN civicrm_membership ON civicrm_membership_payment.membership_id = civicrm_membership.id ";
        break;

      case 'contribution_participant':
        $from = " $side  JOIN civicrm_participant_payment ON civicrm_participant_payment.contribution_id = civicrm_contribution.id";
        $from .= " $side  JOIN civicrm_participant ON civicrm_participant_payment.participant_id = civicrm_participant.id ";
        break;

      case 'civicrm_contribution_soft':
        $from = " $side JOIN civicrm_contribution_soft ON civicrm_contribution_soft.contribution_id = civicrm_contribution.id";
        break;

      case 'civicrm_contribution_soft_contact':
        $from .= " $side JOIN civicrm_contact civicrm_contact_d ON (civicrm_contribution_soft.contact_id = civicrm_contact_d.id )";
        break;

      case 'civicrm_contribution_soft_email':
        $from .= " $side JOIN civicrm_email as soft_email ON (civicrm_contact_d.id = soft_email.contact_id )";
        break;

      case 'civicrm_contribution_soft_phone':
        $from .= " $side JOIN civicrm_phone as soft_phone ON (civicrm_contact_d.id = soft_phone.contact_id )";
        break;

      case 'contribution_batch':
        $from .= " $side JOIN civicrm_entity_batch ON ( civicrm_entity_batch.entity_table = 'civicrm_contribution' AND
          civicrm_contribution.id = civicrm_entity_batch.entity_id )";
        $from .= " $side JOIN civicrm_batch ON civicrm_entity_batch.batch_id = civicrm_batch.id";
        break;
    }
    return $from;
  }

  static function defaultReturnProperties($mode, $includeCustomFields = TRUE) {
    $properties = NULL;
    if ($mode & CRM_Contact_BAO_Query::MODE_CONTRIBUTE) {
      $properties = array(
        'contact_type' => 1,
        'contact_sub_type' => 1,
        'sort_name' => 1,
        'display_name' => 1,
        'financial_type' => 1,
        'contribution_source' => 1,
        'receive_date' => 1,
        'thankyou_date' => 1,
        'cancel_date' => 1,
        'total_amount' => 1,
        'accounting_code' => 1,
        'payment_instrument' => 1,
        'check_number' => 1,
        'non_deductible_amount' => 1,
        'fee_amount' => 1,
        'net_amount' => 1,
        'trxn_id' => 1,
        'invoice_id' => 1,
        'currency' => 1,
        'cancel_reason' => 1,
        'receipt_date' => 1,
        'product_name' => 1,
        'sku' => 1,
        'product_option' => 1,
        'fulfilled_date' => 1,
        'contribution_start_date' => 1,
        'contribution_end_date' => 1,
        'is_test' => 1,
        'is_pay_later' => 1,
        'contribution_status' => 1,
        'contribution_status_id' => 1,
        'contribution_recur_id' => 1,
        'amount_level' => 1,
        'contribution_note' => 1,
        'contribution_batch' => 1,
        'contribution_campaign_id' => 1
      );

      if ($includeCustomFields) {
        // also get all the custom contribution properties
        $fields = CRM_Core_BAO_CustomField::getFieldsForImport('Contribution');
        if (!empty($fields)) {
          foreach ($fields as $name => $dontCare) {
            $properties[$name] = 1;
          }
        }
      }
    }
    return $properties;
  }

  /**
   * add all the elements shared between contribute search and advnaced search
   *
   * @access public
   *
   * @return void
   * @static
   */
  static function buildSearchForm(&$form) {

    // Added contribution source
    $form->addElement('text', 'contribution_source', ts('Contribution Source'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution', 'source'));

    CRM_Core_Form_Date::buildDateRange($form, 'contribution_date', 1, '_low', '_high', ts('From:'), FALSE, FALSE);

    $form->add('text', 'contribution_amount_low', ts('From'), array('size' => 8, 'maxlength' => 8));
    $form->addRule('contribution_amount_low', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('9.99', ' '))), 'money');

    $form->add('text', 'contribution_amount_high', ts('To'), array('size' => 8, 'maxlength' => 8));
    $form->addRule('contribution_amount_high', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('99.99', ' '))), 'money');

    // Adding select option for curreny type -- CRM-4711
    $form->add('select', 'contribution_currency_type',
      ts('Currency Type'),
      array(
        '' => ts('- any -')) +
      CRM_Core_PseudoConstant::currencySymbols('name')
    );

    $form->add('select', 'financial_type_id',
      ts('Financial Type'),
      array(
        '' => ts('- any -')) +
      CRM_Contribute_PseudoConstant::financialType()
    );

    $form->add('select', 'contribution_page_id',
      ts('Contribution Page'),
      array(
        '' => ts('- any -')) +
      CRM_Contribute_PseudoConstant::contributionPage()
    );


    $form->add('select', 'contribution_payment_instrument_id',
      ts('Payment Instrument'),
      array(
        '' => ts('- any -')) +
      CRM_Contribute_PseudoConstant::paymentInstrument()
    );

    $form->add('select', 'contribution_pcp_made_through_id',
      ts('Personal Campaign Page'),
      array(
        '' => ts('- any -')) +
      CRM_Contribute_PseudoConstant::pcPage()
    );

    $status = array();

    $statusValues = CRM_Core_OptionGroup::values("contribution_status");
    // Remove status values that are only used for recurring contributions or pledges (In Progress, Overdue).
    unset($statusValues['5'], $statusValues['6']);

    foreach ($statusValues as $key => $val) {
      $status[] = $form->createElement('advcheckbox', $key, NULL, $val);
    }

    $form->addGroup($status, 'contribution_status_id', ts('Contribution Status'));

    // Add fields for thank you and receipt
    $form->addYesNo('contribution_thankyou_date_is_not_null', ts('Thank-you sent?'));
    $form->addYesNo('contribution_receipt_date_is_not_null', ts('Receipt sent?'));

    // Add fields for honor search
    $form->addElement('text', 'contribution_in_honor_of', ts("In Honor Of"));

    $form->addYesNo('contribution_pay_later', ts('Contribution is Pay Later?'));
    $form->addYesNo('contribution_recurring', ts('Contribution is Recurring?'));
    $form->addYesNo('contribution_test', ts('Contribution is a Test?'));

    // Add field for transaction ID search
    $form->addElement('text', 'contribution_transaction_id', ts("Transaction ID"));

    $form->addElement('text', 'contribution_check_number', ts('Check Number'));

    // Add field for pcp display in roll search
    $form->addYesNo('contribution_pcp_display_in_roll', ts('Personal Campaign Page Honor Roll?'));

    // Add all the custom searchable fields
    $contribution = array('Contribution');
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE, $contribution);
    if ($groupDetails) {
      $form->assign('contributeGroupTree', $groupDetails);
      foreach ($groupDetails as $group) {
        foreach ($group['fields'] as $field) {
          $fieldId = $field['id'];
          $elementName = 'custom_' . $fieldId;
          CRM_Core_BAO_CustomField::addQuickFormElement($form,
            $elementName,
            $fieldId,
            FALSE, FALSE, TRUE
          );
        }
      }
    }

    CRM_Campaign_BAO_Campaign::addCampaignInComponentSearch($form, 'contribution_campaign_id');

    // Add batch select
    $batches = CRM_Batch_BAO_Batch::getBatches();

    if ( !empty( $batches ) ) {
      $form->add('select', 'contribution_batch_id',
        ts('Batch Name'),
        array( '' => ts('- any -')) + $batches );
    }

    $form->assign('validCiviContribute', TRUE);
    $form->setDefaults(array('contribution_test' => 0));
  }

  static function addShowHide(&$showHide) {
    $showHide->addHide('contributeForm');
    $showHide->addShow('contributeForm_show');
  }

  static function searchAction(&$row, $id) {
  }

  static function tableNames(&$tables) {
    // Add contribution table
    if (CRM_Utils_Array::value('civicrm_product', $tables)) {
      $tables = array_merge(array('civicrm_contribution' => 1), $tables);
    }

    if (CRM_Utils_Array::value('civicrm_contribution_product', $tables) &&
      !CRM_Utils_Array::value('civicrm_product', $tables)) {
      $tables['civicrm_product'] = 1;
    }
  }
}

