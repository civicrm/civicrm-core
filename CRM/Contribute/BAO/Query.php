<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 */
class CRM_Contribute_BAO_Query {

  /**
   * Static field for all the export/import contribution fields.
   *
   * @var array
   */
  static $_contributionFields = NULL;

  static $_contribOrSoftCredit = "only_contribs";

  /**
   * Function get the import/export fields for contribution.
   *
   * @return array
   *   self::$_contributionFields  associative array of contribution fields
   */
  public static function &getFields() {
    if (!self::$_contributionFields) {
      self::$_contributionFields = array();

      $fields = CRM_Contribute_BAO_Contribution::exportableFields();

      unset($fields['contribution_contact_id']);

      self::$_contributionFields = $fields;
    }
    return self::$_contributionFields;
  }

  /**
   * If contributions are involved, add the specific contribute fields.
   *
   * @param CRM_Contact_BAO_Query $query
   */
  public static function select(&$query) {
    // if contribute mode add contribution id
    if ($query->_mode & CRM_Contact_BAO_Query::MODE_CONTRIBUTE) {
      $query->_select['contribution_id'] = "civicrm_contribution.id as contribution_id";
      $query->_element['contribution_id'] = 1;
      $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
    }

    // get financial_type
    if (!empty($query->_returnProperties['financial_type'])) {
      $query->_select['financial_type'] = "civicrm_financial_type.name as financial_type";
      $query->_element['financial_type'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['civicrm_financial_type'] = 1;
    }

    // get accounting code
    if (!empty($query->_returnProperties['accounting_code'])) {
      $query->_select['accounting_code'] = "civicrm_financial_account.accounting_code as accounting_code";
      $query->_element['accounting_code'] = 1;
      $query->_tables['civicrm_accounting_code'] = 1;
      $query->_tables['civicrm_financial_account'] = 1;
    }

    if (!empty($query->_returnProperties['contribution_note'])) {
      $query->_select['contribution_note'] = "civicrm_note.note as contribution_note";
      $query->_element['contribution_note'] = 1;
      $query->_tables['contribution_note'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
    }

    if (!empty($query->_returnProperties['contribution_batch'])) {
      $query->_select['contribution_batch'] = "civicrm_batch.title as contribution_batch";
      $query->_element['contribution_batch'] = 1;
      $query->_tables['contribution_batch'] = 1;
    }

    if (!empty($query->_returnProperties['contribution_source'])) {
      $query->_select['contribution_source'] = "civicrm_contribution.source as contribution_source";
      $query->_element['contribution_source'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
    }

    // get contribution_status
    if (!empty($query->_returnProperties['contribution_status_id'])) {
      $query->_select['contribution_status_id'] = "contribution_status.value as contribution_status_id";
      $query->_element['contribution_status_id'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['contribution_status'] = 1;
    }

    // get contribution_status label
    if (!empty($query->_returnProperties['contribution_status'])) {
      $query->_select['contribution_status'] = "contribution_status.label as contribution_status";
      $query->_element['contribution_status'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['contribution_status'] = 1;
    }

    // get payment instrument
    if (!empty($query->_returnProperties['payment_instrument'])) {
      $query->_select['payment_instrument'] = "contribution_payment_instrument.label as payment_instrument";
      $query->_element['payment_instrument'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['contribution_payment_instrument'] = 1;
    }

    // get payment instrument id
    if (!empty($query->_returnProperties['payment_instrument_id'])) {
      $query->_select['instrument_id'] = "contribution_payment_instrument.value as instrument_id";
      $query->_select['payment_instrument_id'] = "contribution_payment_instrument.value as payment_instrument_id";
      $query->_element['instrument_id'] = $query->_element['payment_instrument_id'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['contribution_payment_instrument'] = 1;
    }

    if (!empty($query->_returnProperties['check_number'])) {
      $query->_select['contribution_check_number'] = "civicrm_contribution.check_number as contribution_check_number";
      $query->_element['contribution_check_number'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
    }

    if (!empty($query->_returnProperties['contribution_campaign_id'])) {
      $query->_select['contribution_campaign_id'] = 'civicrm_contribution.campaign_id as contribution_campaign_id';
      $query->_element['contribution_campaign_id'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
    }

    // LCD 716
    if (!empty($query->_returnProperties['contribution_soft_credit_name'])) {
      $query->_select['contribution_soft_credit_name'] = "civicrm_contact_d.sort_name as contribution_soft_credit_name";
      $query->_element['contribution_soft_credit_name'] = 1;

      // also include contact id. Will help build hyperlinks
      $query->_select['contribution_soft_credit_contact_id'] = "civicrm_contact_d.id as contribution_soft_credit_contact_id";
      $query->_element['contribution_soft_credit_contact_id'] = 1;

      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['civicrm_contribution_soft'] = 1;
      $query->_tables['civicrm_contribution_soft_contact'] = 1;
    }

    if (!empty($query->_returnProperties['contribution_soft_credit_contact_id'])) {
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['civicrm_contribution_soft'] = 1;
    }

    if (!empty($query->_returnProperties['contribution_soft_credit_amount'])) {
      $query->_select['contribution_soft_credit_amount'] = "civicrm_contribution_soft.amount as contribution_soft_credit_amount";
      $query->_element['contribution_soft_credit_amount'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['civicrm_contribution_soft'] = 1;
    }

    if (!empty($query->_returnProperties['contribution_soft_credit_type'])) {
      $query->_select['contribution_soft_credit_type'] = "contribution_softcredit_type.label as contribution_soft_credit_type";
      $query->_element['contribution_soft_credit_type'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['contribution_softcredit_type'] = 1;
    }

    if (!empty($query->_returnProperties['contribution_soft_credit_contribution_id'])) {
      $query->_select['contribution_soft_credit_contribution_id'] = "civicrm_contribution_soft.contribution_id as contribution_soft_credit_contribution_id";
      $query->_element['contribution_soft_credit_contribution_id'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['civicrm_contribution_soft'] = 1;
    }

    if (!empty($query->_returnProperties['contribution_soft_credit_email'])) {
      $query->_select['contribution_soft_credit_email'] = "soft_email.email as contribution_soft_credit_email";
      $query->_element['contribution_soft_credit_email'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['civicrm_contribution_soft'] = 1;
      $query->_tables['civicrm_contribution_soft_contact'] = 1;
      $query->_tables['civicrm_contribution_soft_email'] = 1;
    }

    if (!empty($query->_returnProperties['contribution_soft_credit_phone'])) {
      $query->_select['contribution_soft_credit_email'] = "soft_phone.phone as contribution_soft_credit_phone";
      $query->_element['contribution_soft_credit_phone'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['civicrm_contribution_soft'] = 1;
      $query->_tables['civicrm_contribution_soft_contact'] = 1;
      $query->_tables['civicrm_contribution_soft_phone'] = 1;
    }
    if (!empty($query->_returnProperties['contribution_campaign_title'])) {
      $query->_select['contribution_campaign_title'] = "civicrm_campaign.title as contribution_campaign_title";
      $query->_element['contribution_campaign_title'] = $query->_tables['civicrm_campaign'] = 1;
    }
    // LCD 716 END
  }

  /**
   * Get where clause.
   *
   * @param CRM_Contact_BAO_Query $query
   */
  public static function where(&$query) {
    $grouping = NULL;
    self::initializeAnySoftCreditClause($query);
    foreach (array_keys($query->_params) as $id) {
      if (empty($query->_params[$id][0])) {
        continue;
      }
      if (substr($query->_params[$id][0], 0, 13) == 'contribution_' || substr($query->_params[$id][0], 0, 10) == 'financial_'  || substr($query->_params[$id][0], 0, 8) == 'payment_') {
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

  /**
   * Get where clause for a single value.
   *
   * @param array $values
   * @param CRM_Contact_BAO_Query $query
   */
  public static function whereClauseSingle(&$values, &$query) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    $quoteValue = NULL;
    $fields = array_merge(CRM_Contribute_BAO_Contribution::fields(), self::getFields());

    if (!empty($value) && !is_array($value)) {
      $quoteValue = "\"$value\"";
    }

    $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
    foreach (self::getRecurringFields() as $dateField => $dateFieldTitle) {
      if (self::buildDateWhere($values, $query, $name, $dateField, $dateFieldTitle)) {
        return;
      }
    }
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

      case 'financial_type':
      case 'contribution_page':
      case 'payment_instrument':
      case 'contribution_payment_instrument':
      case 'contribution_status':
        $name .= '_id';
      case 'financial_type_id':
      case 'invoice_id':
      case 'payment_instrument_id':
      case 'contribution_payment_instrument_id':
      case 'contribution_page_id':
      case 'contribution_status_id':
      case 'contribution_id':
      case 'contribution_currency_type':
      case 'contribution_currency':
      case 'contribution_source':
      case 'contribution_trxn_id':
      case 'contribution_check_number':
      case 'contribution_contact_id':
      case (strpos($name, '_amount') !== FALSE):
      case (strpos($name, '_date') !== FALSE && $name != 'contribution_fulfilled_date'):
        $qillName = $name;
        $pseudoExtraParam = NULL;
        // @todo including names using a switch statement & then using an 'if' to filter them out is ... odd!
        if ((strpos($name, '_amount') !== FALSE) || (strpos($name, '_date') !== FALSE) || in_array($name,
            array(
              'contribution_id',
              'contribution_currency',
              'contribution_source',
              'contribution_trxn_id',
              'contribution_check_number',
              'contribution_payment_instrument_id',
              'contribution_contact_id',
            )
          )
        ) {
          $name = str_replace('contribution_', '', $name);
          if (!in_array($name, array('source', 'id', 'contact_id'))) {
            $qillName = str_replace('contribution_', '', $qillName);
          }
        }
        if (in_array($name, array('contribution_currency', 'contribution_currency_type'))) {
          $qillName = $name = 'currency';
          $pseudoExtraParam = array('labelColumn' => 'name');
        }

        $dataType = !empty($fields[$qillName]['type']) ? CRM_Utils_Type::typeToString($fields[$qillName]['type']) : 'String';

        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.$name", $op, $value, $dataType);
        list($op, $value) = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Contribute_DAO_Contribution', $name, $value, $op, $pseudoExtraParam);
        $query->_qill[$grouping][] = ts('%1 %2 %3', array(1 => $fields[$qillName]['title'], 2 => $op, 3 => $value));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_pcp_made_through_id':
      case 'contribution_soft_credit_type_id':
        $qillName = $name;
        if ($name == 'contribution_pcp_made_through_id') {
          $qillName = $name = 'pcp_id';
          $fields[$name] = array('title' => ts('Personal Campaign Page'), 'type' => 2);
        }
        if ($name == 'contribution_soft_credit_type_id') {
          $qillName = str_replace('_id', '', $qillName);
          $fields[$qillName]['type'] = $fields[$qillName]['data_type'];
          $name = str_replace('contribution_', '', $name);
        }
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution_soft.$name",
          $op, $value, CRM_Utils_Type::typeToString($fields[$qillName]['type'])
        );
        list($op, $value) = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Contribute_DAO_ContributionSoft', $name, $value, $op);
        $query->_qill[$grouping][] = ts('%1 %2 %3', array(1 => $fields[$qillName]['title'], 2 => $op, 3 => $value));
        $query->_tables['civicrm_contribution_soft'] = $query->_whereTables['civicrm_contribution_soft'] = 1;
        return;

      case 'contribution_or_softcredits':
        if ($value == 'only_scredits') {
          $query->_where[$grouping][] = "contribution_search_scredit_combined.scredit_id IS NOT NULL";
          $query->_qill[$grouping][] = ts('Contributions OR Soft Credits? - Soft Credits Only');
          $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
          $query->_tables['civicrm_contribution_soft'] = $query->_whereTables['civicrm_contribution_soft'] = 1;
        }
        elseif ($value == 'both_related') {
          $query->_where[$grouping][] = "contribution_search_scredit_combined.filter_id IS NOT NULL";
          $query->_qill[$grouping][] = ts('Contributions OR Soft Credits? - Soft Credits with related Hard Credit');
          $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
          $query->_tables['civicrm_contribution_soft'] = $query->_whereTables['civicrm_contribution_soft'] = 1;
        }
        elseif ($value == 'both') {
          $query->_qill[$grouping][] = ts('Contributions OR Soft Credits? - Both');
          $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
          $query->_tables['civicrm_contribution_soft'] = $query->_whereTables['civicrm_contribution_soft'] = 1;
        }
        // default option: $value == 'only_contribs'
        return;

      case 'contribution_is_test':
        // By default is Contribution Search form we choose is_test = 0 in otherwords always show active contribution
        // so in case if any one choose any Yes/No avoid the default clause otherwise it will be conflict in whereClause
        $key = array_search('civicrm_contribution.is_test = 0', $query->_where[$grouping]);
        if (!empty($key)) {
          unset($query->_where[$grouping][$key]);
        }
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
        $batches = CRM_Contribute_PseudoConstant::batch();
        $query->_where[$grouping][] = " civicrm_entity_batch.batch_id $op $value";
        $query->_qill[$grouping][] = ts('Batch Name %1 %2', array(1 => $op, 2 => $batches[$value]));
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        $query->_tables['contribution_batch'] = $query->_whereTables['contribution_batch'] = 1;
        return;

      default:
        //all other elements are handle in this case
        $fldName = substr($name, 13);
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
        $value = trim($value);

        $dataType = "String";
        if (!empty($whereTable['type'])) {
          $dataType = CRM_Utils_Type::typeToString($whereTable['type']);
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

  /**
   * Get from clause.
   *
   * @param string $name
   * @param string $mode
   * @param string $side
   *
   * @return NULL|string
   */
  public static function from($name, $mode, $side) {
    $from = NULL;
    switch ($name) {
      case 'civicrm_contribution':
        $from = " $side JOIN civicrm_contribution ON civicrm_contribution.contact_id = contact_a.id ";
        if (in_array(self::$_contribOrSoftCredit, array("only_scredits", "both_related", "both"))) {
          // switch the from table if its only soft credit search
          $from = " $side JOIN contribution_search_scredit_combined ON contribution_search_scredit_combined.contact_id = contact_a.id ";
          $from .= " $side JOIN civicrm_contribution ON civicrm_contribution.id = contribution_search_scredit_combined.id ";
          $from .= " $side JOIN civicrm_contribution_soft ON civicrm_contribution_soft.id = contribution_search_scredit_combined.scredit_id";
        }
        break;

      case 'civicrm_contribution_recur':
        if ($mode == 1) {
          // in contact mode join directly onto profile - in case no contributions exist yet
          $from = " $side JOIN civicrm_contribution_recur ON contact_a.id = civicrm_contribution_recur.contact_id ";
        }
        else {
          $from = " $side JOIN civicrm_contribution_recur ON civicrm_contribution.contribution_recur_id = civicrm_contribution_recur.id ";
        }
        break;

      case 'civicrm_financial_type':
        if ($mode & CRM_Contact_BAO_Query::MODE_CONTRIBUTE) {
          $from = " INNER JOIN civicrm_financial_type ON civicrm_contribution.financial_type_id = civicrm_financial_type.id ";
        }
        else {
          $from = " $side JOIN civicrm_financial_type ON civicrm_contribution.financial_type_id = civicrm_financial_type.id ";
        }
        break;

      case 'civicrm_financial_account':
        if ($mode & CRM_Contact_BAO_Query::MODE_CONTACTS) {
          $from = " $side JOIN civicrm_financial_account ON contact_a.id = civicrm_financial_account.contact_id ";
        }
        break;

      case 'civicrm_accounting_code':
        if ($mode & CRM_Contact_BAO_Query::MODE_CONTRIBUTE) {
          $from = " $side JOIN civicrm_entity_financial_account ON civicrm_entity_financial_account.entity_id = civicrm_contribution.financial_type_id AND civicrm_entity_financial_account.entity_table = 'civicrm_financial_type' ";
          $from .= " INNER JOIN civicrm_financial_account ON civicrm_financial_account.id = civicrm_entity_financial_account.financial_account_id ";
          $from .= " INNER JOIN civicrm_option_value cov ON cov.value = civicrm_entity_financial_account.account_relationship AND cov.name = 'Income Account is' ";
          $from .= " INNER JOIN civicrm_option_group cog ON cog.id = cov.option_group_id AND cog.name = 'account_relationship' ";
        }
        break;

      case 'civicrm_contribution_page':
        $from = " $side JOIN civicrm_contribution_page ON civicrm_contribution.contribution_page_id = civicrm_contribution_page.id";
        break;

      case 'civicrm_product':
        $from = " $side  JOIN civicrm_contribution_product ON civicrm_contribution_product.contribution_id = civicrm_contribution.id";
        $from .= " $side  JOIN civicrm_product ON civicrm_contribution_product.product_id =civicrm_product.id ";
        break;

      case 'contribution_payment_instrument':
        $from = " $side JOIN civicrm_option_group option_group_payment_instrument ON ( option_group_payment_instrument.name = 'payment_instrument')";
        $from .= " $side JOIN civicrm_option_value contribution_payment_instrument ON (civicrm_contribution.payment_instrument_id = contribution_payment_instrument.value
                               AND option_group_payment_instrument.id = contribution_payment_instrument.option_group_id ) ";
        break;

      case 'contribution_status':
        $from = " $side JOIN civicrm_option_group option_group_contribution_status ON (option_group_contribution_status.name = 'contribution_status')";
        $from .= " $side JOIN civicrm_option_value contribution_status ON (civicrm_contribution.contribution_status_id = contribution_status.value
                               AND option_group_contribution_status.id = contribution_status.option_group_id ) ";
        break;

      case 'contribution_softcredit_type':
        $from = " $side JOIN civicrm_option_group option_group_contribution_softcredit_type ON
          (option_group_contribution_softcredit_type.name = 'soft_credit_type')";
        $from .= " $side JOIN civicrm_option_value contribution_softcredit_type ON
          ( civicrm_contribution_soft.soft_credit_type_id = contribution_softcredit_type.value
          AND option_group_contribution_softcredit_type.id = contribution_softcredit_type.option_group_id )";
        break;

      case 'contribution_note':
        $from .= " $side JOIN civicrm_note ON ( civicrm_note.entity_table = 'civicrm_contribution' AND
                                                    civicrm_contribution.id = civicrm_note.entity_id )";
        break;

      case 'contribution_membership':
        $from = " $side  JOIN civicrm_membership_payment ON civicrm_membership_payment.contribution_id = civicrm_contribution.id";
        $from .= " $side  JOIN civicrm_membership ON civicrm_membership_payment.membership_id = civicrm_membership.id ";
        break;

      case 'civicrm_campaign':
        //CRM-16764 - get survey clause from campaign bao
        if (!CRM_Campaign_BAO_Query::$_applySurveyClause) {
          $from = " $side  JOIN civicrm_campaign ON civicrm_campaign.id = civicrm_contribution.campaign_id";
        }
        break;

      case 'contribution_participant':
        $from = " $side  JOIN civicrm_participant_payment ON civicrm_participant_payment.contribution_id = civicrm_contribution.id";
        $from .= " $side  JOIN civicrm_participant ON civicrm_participant_payment.participant_id = civicrm_participant.id ";
        break;

      case 'civicrm_contribution_soft':
        if (!in_array(self::$_contribOrSoftCredit, array("only_scredits", "both_related", "both"))) {
          $from = " $side JOIN civicrm_contribution_soft ON civicrm_contribution_soft.contribution_id = civicrm_contribution.id";
        }
        break;

      case 'civicrm_contribution_soft_contact':
        if (in_array(self::$_contribOrSoftCredit, array("only_scredits", "both_related", "both"))) {
          $from .= " $side JOIN civicrm_contact civicrm_contact_d ON (civicrm_contribution.contact_id = civicrm_contact_d.id )
            AND contribution_search_scredit_combined.scredit_id IS NOT NULL";
        }
        else {
          $from .= " $side JOIN civicrm_contact civicrm_contact_d ON (civicrm_contribution_soft.contact_id = civicrm_contact_d.id )";
        }
        break;

      case 'civicrm_contribution_soft_email':
        $from .= " $side JOIN civicrm_email as soft_email ON (civicrm_contact_d.id = soft_email.contact_id )";
        break;

      case 'civicrm_contribution_soft_phone':
        $from .= " $side JOIN civicrm_phone as soft_phone ON (civicrm_contact_d.id = soft_phone.contact_id )";
        break;

      case 'contribution_batch':
        $from .= " $side JOIN civicrm_entity_financial_trxn ON (
        civicrm_entity_financial_trxn.entity_table = 'civicrm_contribution'
        AND civicrm_contribution.id = civicrm_entity_financial_trxn.entity_id )";

        $from .= " $side JOIN civicrm_financial_trxn ON (
        civicrm_entity_financial_trxn.financial_trxn_id = civicrm_financial_trxn.id )";

        $from .= " $side JOIN civicrm_entity_batch ON ( civicrm_entity_batch.entity_table = 'civicrm_financial_trxn'
        AND civicrm_financial_trxn.id = civicrm_entity_batch.entity_id )";

        $from .= " $side JOIN civicrm_batch ON civicrm_entity_batch.batch_id = civicrm_batch.id";
        break;
    }
    return $from;
  }

  /**
   * Initialise the soft credit clause.
   *
   * @param CRM_Contact_BAO_Query $query
   */
  public static function initializeAnySoftCreditClause(&$query) {
    if (self::isSoftCreditOptionEnabled($query->_params)) {
      if ($query->_mode & CRM_Contact_BAO_Query::MODE_CONTRIBUTE) {
        unset($query->_distinctComponentClause);
        $query->_rowCountClause = " count(civicrm_contribution.id)";
        $query->_groupByComponentClause = " GROUP BY contribution_search_scredit_combined.id, contribution_search_scredit_combined.contact_id, contribution_search_scredit_combined.scredit_id ";
      }
    }
  }

  /**
   * Check if soft credits are enables.
   *
   * @param array $queryParams
   *
   * @return bool
   */
  public static function isSoftCreditOptionEnabled($queryParams = array()) {
    static $tempTableFilled = FALSE;
    if (!empty($queryParams)) {
      foreach (array_keys($queryParams) as $id) {
        if (empty($queryParams[$id][0])) {
          continue;
        }
        if ($queryParams[$id][0] == 'contribution_or_softcredits') {
          self::$_contribOrSoftCredit = $queryParams[$id][2];
        }
      }
    }
    if (in_array(self::$_contribOrSoftCredit,
      array("only_scredits", "both_related", "both"))) {
      if (!$tempTableFilled) {
        // build a temp table which is union of contributions and soft credits
        // note: group-by in first part ensures uniqueness in counts
        $tempQuery = "
            CREATE TEMPORARY TABLE IF NOT EXISTS contribution_search_scredit_combined AS
               SELECT con.id as id, con.contact_id, cso.id as filter_id, NULL as scredit_id
                 FROM civicrm_contribution con
            LEFT JOIN civicrm_contribution_soft cso ON con.id = cso.contribution_id
             GROUP BY id, contact_id, scredit_id
            UNION ALL
               SELECT scredit.contribution_id as id, scredit.contact_id, scredit.id as filter_id, scredit.id as scredit_id
                 FROM civicrm_contribution_soft as scredit";
        CRM_Core_DAO::executeQuery($tempQuery);
        $tempTableFilled = TRUE;
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get return properties for soft credits.
   *
   * @param bool $isExportMode
   *
   * @return array
   */
  public static function softCreditReturnProperties($isExportMode = FALSE) {
    $properties = array(
      'contribution_soft_credit_name' => 1,
      'contribution_soft_credit_amount' => 1,
      'contribution_soft_credit_type' => 1,
    );
    if ($isExportMode) {
      $properties['contribution_soft_credit_contribution_id'] = 1;
    }
    return $properties;
  }

  /**
   * @param $mode
   * @param bool $includeCustomFields
   *
   * @return array|NULL
   */
  public static function defaultReturnProperties($mode, $includeCustomFields = TRUE) {
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
        'payment_instrument_id' => 1,
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
        'contribution_campaign_title' => 1,
        'contribution_campaign_id' => 1,
      );
      if (self::isSoftCreditOptionEnabled()) {
        $properties = array_merge($properties, self::softCreditReturnProperties());
      }
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
   * Add all the elements shared between contribute search and advnaced search.
   *
   *
   * @param CRM_Core_Form $form
   *
   * @return void
   */
  public static function buildSearchForm(&$form) {

    // Added contribution source
    $form->addElement('text', 'contribution_source', ts('Contribution Source'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution', 'source'));

    CRM_Core_Form_Date::buildDateRange($form, 'contribution_date', 1, '_low', '_high', ts('From:'), FALSE);

    $form->add('text', 'contribution_amount_low', ts('From'), array('size' => 8, 'maxlength' => 8));
    $form->addRule('contribution_amount_low', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('9.99', ' '))), 'money');

    $form->add('text', 'contribution_amount_high', ts('To'), array('size' => 8, 'maxlength' => 8));
    $form->addRule('contribution_amount_high', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('99.99', ' '))), 'money');

    // Adding select option for curreny type -- CRM-4711
    $form->add('select', 'contribution_currency_type',
      ts('Currency Type'),
      array(
        '' => ts('- any -'),
      ) +
      CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'currency', array('labelColumn' => 'name')),
      FALSE, array('class' => 'crm-select2')
    );

    // CRM-13848
    $form->addSelect('financial_type_id',
      array('entity' => 'contribution', 'multiple' => 'multiple', 'context' => 'search')
    );

    $form->add('select', 'contribution_page_id',
      ts('Contribution Page'),
      array(
        '' => ts('- any -'),
      ) +
      CRM_Contribute_PseudoConstant::contributionPage(),
      FALSE, array('class' => 'crm-select2')
    );

    $form->addSelect('payment_instrument_id',
      array('entity' => 'contribution', 'label' => ts('Payment Method'), 'option_url' => NULL, 'placeholder' => ts('- any -'))
    );

    // Fixme: Not a true entityRef field. Relies on PCP.js.tpl
    $form->add('text', 'contribution_pcp_made_through_id', ts('Personal Campaign Page'), array('class' => 'twenty', 'id' => 'pcp_made_through_id', 'placeholder' => ts('- any -')));
    // stores the label
    $form->add('hidden', 'pcp_made_through');

    $statusValues = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'contribution_status_id');
    // Remove status values that are only used for recurring contributions or pledges (In Progress, Overdue).
    unset($statusValues['5'], $statusValues['6']);
    $form->add('select', 'contribution_status_id',
      ts('Contribution Status'), $statusValues,
      FALSE, array('class' => 'crm-select2', 'multiple' => 'multiple')
    );

    // Add fields for thank you and receipt
    $form->addYesNo('contribution_thankyou_date_is_not_null', ts('Thank-you sent?'), TRUE);
    $form->addYesNo('contribution_receipt_date_is_not_null', ts('Receipt sent?'), TRUE);

    $form->addYesNo('contribution_pay_later', ts('Contribution is Pay Later?'), TRUE);
    $form->addYesNo('contribution_recurring', ts('Contribution is Recurring?'), TRUE);

    // Recurring contribution fields
    foreach (self::getRecurringFields() as $key => $label) {
      CRM_Core_Form_Date::buildDateRange($form, $key, 1, '_low', '_high');
      // If data has been entered for a recurring field, tell the tpl layer to open the pane
      if (!empty($form->_formValues[$key . '_relative']) || !empty($form->_formValues[$key . '_low']) || !empty($form->_formValues[$key . '_high'])) {
        $form->assign('contribution_recur_pane_open', TRUE);
      }
    }

    $form->addYesNo('contribution_test', ts('Contribution is a Test?'), TRUE);

    // Add field for transaction ID search
    $form->addElement('text', 'contribution_trxn_id', ts("Transaction ID"));
    $form->addElement('text', 'invoice_id', ts("Invoice ID"));
    $form->addElement('text', 'contribution_check_number', ts('Check Number'));

    // Add field for pcp display in roll search
    $form->addYesNo('contribution_pcp_display_in_roll', ts('Personal Campaign Page Honor Roll?'), TRUE);

    // Soft credit related fields
    $options = array(
      'only_contribs' => ts('Contributions Only'),
      'only_scredits' => ts('Soft Credits Only'),
      'both_related' => ts('Soft Credits with related Hard Credit'),
      'both' => ts('Both'),
    );
    $form->add('select', 'contribution_or_softcredits', ts('Contributions OR Soft Credits?'), $options, FALSE, array('class' => "crm-select2"));
    $form->addSelect(
      'contribution_soft_credit_type_id',
      array(
        'entity' => 'contribution_soft',
        'field' => 'soft_credit_type_id',
        'multiple' => TRUE,
        'context' => 'search',
      )
    );

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
    $batches = CRM_Contribute_PseudoConstant::batch();

    if (!empty($batches)) {
      $form->add('select', 'contribution_batch_id',
        ts('Batch Name'),
        array('' => ts('- any -')) + $batches,
        FALSE, array('class' => 'crm-select2')
      );
    }

    $form->assign('validCiviContribute', TRUE);
    $form->setDefaults(array('contribution_test' => 0));
  }

  /**
   * Function that may not be needed.
   *
   * @param array $row
   * @param int $id
   */
  public static function searchAction(&$row, $id) {
  }

  /**
   * Get table names.
   *
   * @todo better function comment needed - what IS the point of this?
   *
   * @param array $tables
   */
  public static function tableNames(&$tables) {
    // Add contribution table
    if (!empty($tables['civicrm_product'])) {
      $tables = array_merge(array('civicrm_contribution' => 1), $tables);
    }

    if (!empty($tables['civicrm_contribution_product']) && empty($tables['civicrm_product'])) {
      $tables['civicrm_product'] = 1;
    }
  }

  /**
   * Add the where for dates.
   *
   * @param array $values
   *   Array of query values.
   * @param object $query
   *   The query object.
   * @param string $name
   *   Query field that is set.
   * @param string $field
   *   Name of field to be set.
   * @param string $title
   *   Title of the field.
   *
   * @return bool
   */
  public static function buildDateWhere(&$values, $query, $name, $field, $title) {
    $fieldPart = strpos($name, $field);
    if ($fieldPart === FALSE) {
      return NULL;
    }
    // we only have recurring dates using this ATM so lets' short cut to find the table name
    $table = 'contribution_recur';
    $fieldName = explode($table . '_', $field);
    $query->dateQueryBuilder($values,
      'civicrm_' . $table, $field, $fieldName[1], $title
    );
    return TRUE;
  }

  /**
   * Get fields for recurring contributions.
   *
   * @return array
   */
  public static function getRecurringFields() {
    return array(
      'contribution_recur_start_date' => ts('Recurring Contribution Start Date'),
      'contribution_recur_next_sched_contribution_date' => ts('Next Scheduled Recurring Contribution'),
      'contribution_recur_cancel_date' => ts('Recurring Contribution Cancel Date'),
      'contribution_recur_end_date' => ts('Recurring Contribution End Date'),
      'contribution_recur_create_date' => ('Recurring Contribution Create Date'),
      'contribution_recur_modified_date' => ('Recurring Contribution Modified Date'),
      'contribution_recur_failure_retry_date' => ts('Failed Recurring Contribution Retry Date'),
    );
  }

}
