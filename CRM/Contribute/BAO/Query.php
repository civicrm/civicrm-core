<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contribute_BAO_Query extends CRM_Core_BAO_Query {

  public static $_contribOrSoftCredit = "only_contribs";

  public static $_contribRecurPayment = NULL;

  /**
   * Function get the searchable fields for contribution.
   *
   * This is basically the contribution fields plus some related entity fields.
   *
   * @param bool $checkPermission
   *
   * @return array
   *   Associative array of contribution fields
   */
  public static function getFields($checkPermission = TRUE) {
    if (!isset(\Civi::$statics[__CLASS__]) || !isset(\Civi::$statics[__CLASS__]['fields']) || !isset(\Civi::$statics[__CLASS__]['fields']['contribution'])) {
      $recurFields = CRM_Contribute_DAO_ContributionRecur::fields();
      foreach ($recurFields as $fieldKey => $field) {
        // We can only safely add in those with unique names as those without could clobber others.
        // The array is keyed by unique names so if it doesn't match the key there is no unique name & we unset
        // Refer to CRM_Contribute_Form_SearchTest for existing tests ... and to add more!
        if ($field['name'] === $fieldKey) {
          unset($recurFields[$fieldKey]);
        }
      }
      $fields = array_merge($recurFields, CRM_Contribute_BAO_Contribution::exportableFields($checkPermission));
      CRM_Contribute_BAO_Contribution::appendPseudoConstantsToFields($fields);
      unset($fields['contribution_contact_id']);
      \Civi::$statics[__CLASS__]['fields']['contribution'] = $fields;
    }
    return \Civi::$statics[__CLASS__]['fields']['contribution'];
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
      $query->_tables['civicrm_financial_trxn'] = 1;
      $query->_tables['contribution_batch'] = 1;
    }

    if (!empty($query->_returnProperties['contribution_campaign_title'])) {
      $query->_select['contribution_campaign_title'] = "civicrm_campaign.title as contribution_campaign_title";
      $query->_element['contribution_campaign_title'] = $query->_tables['civicrm_campaign'] = 1;
    }

    self::addSoftCreditFields($query);
  }

  /**
   * Get where clause.
   *
   * @param CRM_Contact_BAO_Query $query
   *
   * @throws \CRM_Core_Exception
   */
  public static function where(&$query) {
    self::initializeAnySoftCreditClause($query);
    foreach (array_keys($query->_params) as $id) {
      if (empty($query->_params[$id][0])) {
        continue;
      }
      if (substr($query->_params[$id][0], 0, 13) == 'contribution_' || substr($query->_params[$id][0], 0, 10) == 'financial_'  || substr($query->_params[$id][0], 0, 8) == 'payment_') {
        if ($query->_mode == CRM_Contact_BAO_Query::MODE_CONTACTS) {
          $query->_useDistinct = TRUE;
        }

        self::whereClauseSingle($query->_params[$id], $query);
      }
    }
  }

  /**
   * Get where clause for a single value.
   *
   * @param array $values
   * @param CRM_Contact_BAO_Query $query
   *
   * @throws \CRM_Core_Exception
   */
  public static function whereClauseSingle(&$values, &$query) {
    [$name, $op, $value, $grouping, $wildcard] = $values;

    $quoteValue = NULL;
    $fields = self::getFields();

    if (!empty($value) && !is_array($value)) {
      $quoteValue = "\"$value\"";
    }

    $fieldAliases = self::getLegacySupportedFields();

    $fieldName = $name = self::getFieldName($values);
    $qillName = $name;
    if (in_array($name, $fieldAliases)) {
      $qillName = array_search($name, $fieldAliases);
    }
    $pseudoExtraParam = [];
    $fieldSpec = CRM_Utils_Array::value($fieldName, $fields, []);
    $tableName = CRM_Utils_Array::value('table_name', $fieldSpec, 'civicrm_contribution');
    $dataType = CRM_Utils_Type::typeToString(CRM_Utils_Array::value('type', $fieldSpec));
    if ($dataType === 'Timestamp' || $dataType === 'Date') {
      $title = empty($fieldSpec['unique_title']) ? $fieldSpec['title'] : $fieldSpec['unique_title'];
      $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
      $query->dateQueryBuilder($values,
        $tableName, $fieldName, $fieldSpec['name'], $title
      );
      return;
    }

    switch ($name) {
      case 'contribution_date':
      case 'contribution_date_low':
      case 'contribution_date_low_time':
      case 'contribution_date_high':
      case 'contribution_date_high_time':
        CRM_Core_Error::deprecatedFunctionWarning('search by receive_date');
        // process to / from date
        $query->dateQueryBuilder($values,
          'civicrm_contribution', 'contribution_date', 'receive_date', ts('Contribution Date')
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

      case 'financial_type_id':
      case 'invoice_id':
      case 'invoice_number':
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
      case 'contribution_campaign_id':

        $fieldNamesNotToStripContributionFrom = [
          'contribution_currency_type',
          'contribution_status_id',
          'contribution_page_id',
        ];
        // @todo these are mostly legacy params. Find a better way to deal with them.
        if (!in_array($name, $fieldNamesNotToStripContributionFrom)
        ) {
          if (!isset($fields[$name])) {
            $qillName = str_replace('contribution_', '', $qillName);
          }
          $name = str_replace('contribution_', '', $name);
        }
        if (in_array($name, ['contribution_currency', 'contribution_currency_type'])) {
          $qillName = $name = 'currency';
          $pseudoExtraParam = ['labelColumn' => 'name'];
        }

        $dataType = !empty($fields[$qillName]['type']) ? CRM_Utils_Type::typeToString($fields[$qillName]['type']) : 'String';

        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution.$name", $op, $value, $dataType);
        [$op, $value] = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Contribute_DAO_Contribution', $name, $value, $op, $pseudoExtraParam);
        if (!($name == 'id' && $value == 0)) {
          $query->_qill[$grouping][] = ts('%1 %2 %3', [1 => $fields[$qillName]['title'], 2 => $op, 3 => $value]);
        }
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        return;

      case 'contribution_pcp_made_through_id':
      case 'contribution_soft_credit_type_id':
        $qillName = $name;
        if ($name == 'contribution_pcp_made_through_id') {
          $qillName = $name = 'pcp_id';
          $fields[$name] = ['title' => ts('Personal Campaign Page'), 'type' => 2];
        }
        if ($name == 'contribution_soft_credit_type_id') {
          $qillName = str_replace('_id', '', $qillName);
          $fields[$qillName]['type'] = $fields[$qillName]['data_type'];
          $name = str_replace('contribution_', '', $name);
        }
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution_soft.$name",
          $op, $value, CRM_Utils_Type::typeToString($fields[$qillName]['type'])
        );
        [$op, $value] = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Contribute_DAO_ContributionSoft', $name, $value, $op);
        $query->_qill[$grouping][] = ts('%1 %2 %3', [1 => $fields[$qillName]['title'], 2 => $op, 3 => $value]);
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
          $query->_qill[$grouping][] = ts('Contributions OR Soft Credits? - Contributions and their related soft credit');
          $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
          $query->_tables['civicrm_contribution_soft'] = $query->_whereTables['civicrm_contribution_soft'] = 1;
        }
        elseif ($value == 'both') {
          $query->_qill[$grouping][] = ts('Contributions OR Soft Credits? - All');
          $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
          $query->_tables['civicrm_contribution_soft'] = $query->_whereTables['civicrm_contribution_soft'] = 1;
        }
        elseif ($value == 'only_contribs_unsoftcredited') {
          $query->_where[$grouping][] = "contribution_search_scredit_combined.filter_id IS NULL";
          $query->_qill[$grouping][] = ts('Contributions OR Soft Credits? - Contributions without a soft credit');
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

      case 'contribution_recur_payment_processor_id':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution_recur.payment_processor_id", $op, $value, "String");
        $paymentProcessors = civicrm_api3('PaymentProcessor', 'get', []);
        $paymentProcessorNames = [];
        foreach ($value as $paymentProcessorId) {
          $paymentProcessorNames[] = $paymentProcessors['values'][$paymentProcessorId]['name'];
        }
        $query->_qill[$grouping][] = ts("Recurring Contribution Payment Processor %1 %2", [1 => $op, 2 => implode(', ', $paymentProcessorNames)]);
        $query->_tables['civicrm_contribution_recur'] = $query->_whereTables['civicrm_contribution_recur'] = 1;
        return;

      case 'contribution_recur_processor_id':
      case 'contribution_recur_trxn_id':
        $spec = $fields[$name];
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($spec['where'],
          $op, $value, "String"
        );
        $query->_qill[$grouping][] = ts("Recurring Contribution %1 %2 '%3'", [1 => $fields[$name]['title'], 2 => $op, 3 => $value]);
        $query->_tables[$spec['table_name']] = $query->_whereTables[$spec['table_name']] = 1;
        return;

      case 'contribution_recur_payment_made':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution_recur.id", 'IS NOT EMPTY');
        if ($value == 2) {
          $query->_qill[$grouping][] = ts("Recurring contributions with at least one payment");
          self::$_contribRecurPayment = TRUE;
        }
        else {
          $query->_qill[$grouping][] = ts("All recurring contributions regardless of payments");
          self::$_contribRecurPayment = FALSE;
        }
        $query->_tables['civicrm_contribution_recur'] = $query->_whereTables['civicrm_contribution_recur'] = 1;
        return;

      case 'contribution_recur_contribution_status_id':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_contribution_recur.contribution_status_id", $op, $value, 'String');
        [$op, $value] = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Contribute_DAO_ContributionRecur', 'contribution_status_id', $value, $op, $pseudoExtraParam);
        $query->_qill[$grouping][] = ts("Recurring Contribution Status %1 '%2'", [1 => $op, 2 => $value]);
        $query->_tables['civicrm_contribution_recur'] = $query->_whereTables['civicrm_contribution_recur'] = 1;
        return;

      case 'contribution_note':
        $value = CRM_Core_DAO::escapeString($value);
        if ($wildcard) {
          $value = "%$value%";
          $op = 'LIKE';
        }
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('civicrm_note.note', $op, $value, "String");
        $query->_qill[$grouping][] = ts('Contribution Note %1 %2', [1 => $op, 2 => $quoteValue]);
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

      case 'contribution_batch_id':
        [$qillOp, $qillValue] = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Batch_BAO_EntityBatch', 'batch_id', $value, $op);
        $query->_qill[$grouping][] = ts('Batch Name %1 %2', [1 => $qillOp, 2 => $qillValue]);
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('civicrm_entity_batch.batch_id', $op, $value);
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        $query->_tables['civicrm_financial_trxn'] = $query->_whereTables['civicrm_financial_trxn'] = 1;
        $query->_tables['contribution_batch'] = $query->_whereTables['contribution_batch'] = 1;
        return;

      case 'contribution_product_id':
        // CRM-16713 - contribution search by premiums on 'Find Contribution' form.
        $qillName = $name;
        [$operator, $productValue] = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Contribute_DAO_Product', $name, $value, $op);
        $query->_qill[$grouping][] = ts('%1 %2 %3', [1 => $fields[$qillName]['title'], 2 => $operator, 3 => $productValue]);
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_product.id", $op, $value);
        $query->_tables['civicrm_product'] = $query->_whereTables['civicrm_product'] = 1;
        return;

      case 'contribution_is_payment':
        $query->_where[$grouping][] = " civicrm_financial_trxn.is_payment $op $value";
        $query->_tables['civicrm_financial_trxn'] = $query->_whereTables['civicrm_financial_trxn'] = 1;
        return;

      case 'financial_trxn_card_type_id':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('civicrm_financial_trxn.card_type_id', $op, $value);
        $query->_tables['civicrm_financial_trxn'] = $query->_whereTables['civicrm_financial_trxn'] = 1;
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        [$op, $value] = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Financial_DAO_FinancialTrxn', 'card_type_id', $value, $op);
        $query->_qill[$grouping][] = ts('Card Type %1 %2', [1 => $op, 2 => $value]);
        return;

      case 'financial_trxn_pan_truncation':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('civicrm_financial_trxn.pan_truncation', $op, $value);
        $query->_tables['civicrm_financial_trxn'] = $query->_whereTables['civicrm_financial_trxn'] = 1;
        $query->_tables['civicrm_contribution'] = $query->_whereTables['civicrm_contribution'] = 1;
        [$op, $value] = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Financial_DAO_FinancialTrxn', 'pan_truncation', $value, $op);
        $query->_qill[$grouping][] = ts('Card Number %1 %2', [1 => $op, 2 => $value]);
        return;

      default:
        //all other elements are handle in this case
        $fldName = substr($name, 13);
        if (!isset($fields[$fldName])) {
          return;
        }
        $whereTable = $fields[$fldName];
        if (!is_array($value)) {
          $value = trim($value);
        }

        $dataType = 'String';
        if (!empty($whereTable['type'])) {
          $dataType = CRM_Utils_Type::typeToString($whereTable['type']);
        }

        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($whereTable['where'], $op, $value, $dataType);
        $query->_qill[$grouping][] = "$whereTable[title] $op $quoteValue";
        [$tableName] = explode('.', $whereTable['where'], 2);
        $query->_tables[$tableName] = $query->_whereTables[$tableName] = 1;
        if ($tableName === 'civicrm_contribution_product') {
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
   * @param int $mode
   * @param string $side
   *
   * @return NULL|string
   */
  public static function from($name, $mode, $side) {
    $from = NULL;
    switch ($name) {
      case 'civicrm_contribution':
        $from = " $side JOIN civicrm_contribution ON civicrm_contribution.contact_id = contact_a.id ";
        if (in_array(self::$_contribOrSoftCredit, ["only_scredits", "both_related", "both", "only_contribs_unsoftcredited"])) {
          // switch the from table if its only soft credit search
          $from = " $side JOIN " . \Civi::$statics[__CLASS__]['soft_credit_temp_table_name'] . " as contribution_search_scredit_combined ON contribution_search_scredit_combined.contact_id = contact_a.id ";
          $from .= " $side JOIN civicrm_contribution ON civicrm_contribution.id = contribution_search_scredit_combined.id ";
          $from .= " $side JOIN civicrm_contribution_soft ON civicrm_contribution_soft.id = contribution_search_scredit_combined.scredit_id";
        }
        break;

      case 'civicrm_contribution_recur':
        if ($mode == 1) {
          // 'Made payment for the recurring contributions?' is ticked yes
          $from = " $side JOIN civicrm_contribution_recur ON contact_a.id = civicrm_contribution_recur.contact_id ";
          if (self::$_contribRecurPayment == TRUE) {
            $from .= " INNER JOIN civicrm_contribution cr ON cr.contribution_recur_id = civicrm_contribution_recur.id ";
          }
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
        if (!in_array(self::$_contribOrSoftCredit, ["only_scredits", "both_related", "both", "only_contribs_unsoftcredited"])) {
          $from = " $side JOIN civicrm_contribution_soft ON civicrm_contribution_soft.contribution_id = civicrm_contribution.id";
        }
        break;

      case 'civicrm_contribution_soft_contact':
        if (in_array(self::$_contribOrSoftCredit, ["only_scredits", "both_related", "both", "only_contribs_unsoftcredited"])) {
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
        $from .= " $side JOIN civicrm_entity_batch ON ( civicrm_entity_batch.entity_table = 'civicrm_financial_trxn'
        AND civicrm_financial_trxn.id = civicrm_entity_batch.entity_id )";

        $from .= " $side JOIN civicrm_batch ON civicrm_entity_batch.batch_id = civicrm_batch.id";
        break;

      case 'civicrm_financial_trxn':
        $from .= " $side JOIN civicrm_entity_financial_trxn ON (
          civicrm_entity_financial_trxn.entity_table = 'civicrm_contribution'
          AND civicrm_contribution.id = civicrm_entity_financial_trxn.entity_id )";

        $from .= " $side JOIN civicrm_financial_trxn ON (
          civicrm_entity_financial_trxn.financial_trxn_id = civicrm_financial_trxn.id )";
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
    // @todo have a generic initialize on all components that gets called every query
    // & rename this to match that fn name.
    if ($query->_mode & CRM_Contact_BAO_Query::MODE_CONTRIBUTE) {
      if (self::isSoftCreditOptionEnabled($query->_params)) {
        unset($query->_distinctComponentClause);
        $query->_rowCountClause = " count(civicrm_contribution.id)";
        $query->_groupByComponentClause = " GROUP BY contribution_search_scredit_combined.id, contribution_search_scredit_combined.contact_id, contribution_search_scredit_combined.scredit_id ";
      }
      else {
        $query->_distinctComponentClause = ' civicrm_contribution.id';
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
  public static function isSoftCreditOptionEnabled($queryParams = []) {
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
      ["only_scredits", "both_related", "both", "only_contribs_unsoftcredited"])) {
      if (!isset(\Civi::$statics[__CLASS__]['soft_credit_temp_table_name'])) {
        // build a temp table which is union of contributions and soft credits
        // note: group-by in first part ensures uniqueness in counts
        $tempQuery = '
               SELECT con.id as id, con.contact_id, cso.id as filter_id, NULL as scredit_id
                 FROM civicrm_contribution con
            LEFT JOIN civicrm_contribution_soft cso ON con.id = cso.contribution_id
             GROUP BY id, contact_id, scredit_id, cso.id
            UNION ALL
               SELECT scredit.contribution_id as id, scredit.contact_id, scredit.id as filter_id, scredit.id as scredit_id
                 FROM civicrm_contribution_soft as scredit';
        \Civi::$statics[__CLASS__]['soft_credit_temp_table_name'] = CRM_Utils_SQL_TempTable::build()->createWithQuery(
          $tempQuery
        )->getName();
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
    $properties = [
      'contribution_soft_credit_name' => 1,
      'contribution_soft_credit_amount' => 1,
      'contribution_soft_credit_type' => 1,
    ];
    if ($isExportMode) {
      $properties['contribution_soft_credit_contact_id'] = 1;
      $properties['contribution_soft_credit_contribution_id'] = 1;
    }
    return $properties;
  }

  /**
   * Get the list of fields required to populate the selector.
   *
   * The default return properties array returns far too many fields for 'everyday use. Every field you add to this array
   * kills a small kitten so add carefully.
   *
   * @param array $queryParams
   * @return array
   */
  public static function selectorReturnProperties($queryParams) {
    $properties = [
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
      'financial_type' => 1,
      'contribution_source' => 1,
      'is_test' => 1,
      'receive_date' => 1,
      'is_pay_later' => 1,
      'thankyou_date' => 1,
      'total_amount' => 1,
      // Without this value here we get an e-notice BUT the value does not appear to be rendered anywhere.
      'contribution_campaign_id' => 1,
      'contribution_status_id' => 1,
      // @todo return this & fix query to do pseudoconstant thing.
      'contribution_status' => 1,
      'currency' => 1,
      'contribution_cancel_date' => 1,
      'contribution_recur_id' => 1,
    ];
    if (self::isSiteHasProducts()) {
      $properties['product_name'] = 1;
      $properties['contribution_product_id'] = 1;
    }
    if (self::isSoftCreditOptionEnabled($queryParams)) {
      $properties = array_merge($properties, self::softCreditReturnProperties());
    }

    return $properties;
  }

  /**
   * Do any products exist in this site's database.
   *
   * @return bool
   */
  public static function isSiteHasProducts() {
    if (!isset(\Civi::$statics[__CLASS__]['has_products'])) {
      \Civi::$statics[__CLASS__]['has_products'] = (bool) CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_contribution_product LIMIT 1');
    }
    return \Civi::$statics[__CLASS__]['has_products'];
  }

  /**
   * Function you should avoid.
   *
   * This function returns default properties for contribution queries. However, they are
   * far more than are required in 'most' cases and you should always try to return the return properties
   * you actually require.
   *
   * It would be nice to throw an e-notice when this is called but it would trash the tests :-(.
   *
   * @param int $mode
   * @param bool $includeCustomFields
   *
   * @return array|NULL
   */
  public static function defaultReturnProperties($mode, $includeCustomFields = TRUE) {
    $properties = NULL;
    if ($mode & CRM_Contact_BAO_Query::MODE_CONTRIBUTE) {
      $properties = [
        // add
        'contact_type' => 1,
        // fields
        'contact_sub_type' => 1,
        // to
        'sort_name' => 1,
        //this
        'display_name' => 1,
        // array
        'financial_type' => 1,
        // to
        'contribution_source' => 1,
        // strangle
        'receive_date' => 1,
        // site
        'thankyou_date' => 1,
        // performance
        'contribution_cancel_date' => 1,
        // and
        'total_amount' => 1,
        // torture
        'accounting_code' => 1,
        // small
        'payment_instrument' => 1,
        // kittens
        'payment_instrument_id' => 1,
        // argh
        'contribution_check_number' => 1,
        // no
        'non_deductible_amount' => 1,
        // not
        'fee_amount' => 1,
        // another
        'net_amount' => 1,
        // expensive
        'trxn_id' => 1,
        // join
        'invoice_id' => 1,
        'invoice_number' => 1,
        // added
        'currency' => 1,
        // to
        'cancel_reason' => 1,
        //every
        'receipt_date' => 1,
        // query
        //whether
        // or
        // not
        // the
        // field
        // is
        'is_test' => 1,
        // actually
        'is_pay_later' => 1,
        // required
        'contribution_status' => 1,
        // instead
        'contribution_status_id' => 1,
        // of
        'contribution_recur_id' => 1,
        // adding
        'amount_level' => 1,
        // here
        'contribution_note' => 1,
        // set
        'contribution_batch' => 1,
        // return properties
        'contribution_campaign_title' => 1,
        // on
        'contribution_campaign_id' => 1,
        // calling
        //function
      ];
      if (self::isSiteHasProducts()) {
        $properties['fulfilled_date'] = 1;
        $properties['product_name'] = 1;
        $properties['contribution_product_id'] = 1;
        $properties['product_option'] = 1;
        $properties['sku'] = 1;
        $properties['contribution_start_date'] = 1;
        $properties['contribution_end_date'] = 1;
      }
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
   * Get the metadata for fields to be included on the search form.
   *
   * @throws \CRM_Core_Exception
   */
  public static function getSearchFieldMetadata() {
    $fields = [
      'contribution_source',
      'cancel_reason',
      'invoice_number',
      'receive_date',
      'contribution_cancel_date',
      'contribution_page_id',
      'contribution_id',
    ];
    $metadata = civicrm_api3('Contribution', 'getfields', [])['values'];
    $metadata['contribution_id'] = $metadata['id'];
    return array_intersect_key($metadata, array_flip($fields));
  }

  /**
   * Add all the elements shared between contribute search and advanced search.
   *
   * @param \CRM_Contribute_Form_Search $form
   *
   * @throws \CRM_Core_Exception
   */
  public static function buildSearchForm(&$form) {

    $form->addSearchFieldMetadata(['Contribution' => self::getSearchFieldMetadata()]);
    $form->addSearchFieldMetadata(['ContributionRecur' => CRM_Contribute_BAO_ContributionRecur::getContributionRecurSearchFieldMetadata()]);
    $form->addFormFieldsFromMetadata();

    $form->add('text', 'contribution_amount_low', ts('From'), ['size' => 8, 'maxlength' => 8]);
    $form->addRule('contribution_amount_low', ts('Please enter a valid money value (e.g. %1).', [1 => CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency('9.99')]), 'money');

    $form->add('text', 'contribution_amount_high', ts('To'), ['size' => 8, 'maxlength' => 8]);
    $form->addRule('contribution_amount_high', ts('Please enter a valid money value (e.g. %1).', [1 => CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency('99.99')]), 'money');

    // Adding select option for curreny type -- CRM-4711
    $form->add('select2', 'contribution_currency_type',
      ts('Currency Type'),
      Civi::entity('Contribution')->getOptions('currency', [], FALSE, TRUE),
      FALSE,
      ['placeholder' => ts('- any -')]
    );

    // CRM-13848
    $form->addSelect('financial_type_id',
      ['entity' => 'contribution', 'multiple' => 'multiple', 'context' => 'search', 'options' => CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'search')]
    );

    // use contribution_payment_instrument_id instead of payment_instrument_id
    // Contribution Edit form (pop-up on contribution/Contact(display Result as Contribution) open on search form),
    // then payment method change action not working properly because of same html ID present two time on one page
    $form->addSelect('contribution_payment_instrument_id',
      ['entity' => 'contribution', 'field' => 'payment_instrument_id', 'multiple' => 'multiple', 'label' => ts('Payment Method'), 'option_url' => NULL, 'placeholder' => ts('- any -')]
    );

    $form->add('select',
      'contribution_pcp_made_through_id',
      ts('Personal Campaign Page'),
      CRM_Contribute_PseudoConstant::pcPage(), FALSE, ['class' => 'crm-select2', 'multiple' => 'multiple', 'placeholder' => ts('- any -')]);

    $statusValues = CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'search');
    $form->add('select', 'contribution_status_id',
      ts('Contribution Status'), $statusValues,
      FALSE, ['class' => 'crm-select2', 'multiple' => 'multiple']
    );

    // Add fields for thank you and receipt
    $form->addYesNo('contribution_thankyou_date_is_not_null', ts('Thank-you sent?'), TRUE);
    $form->addYesNo('contribution_receipt_date_is_not_null', ts('Receipt sent?'), TRUE);

    $form->addYesNo('contribution_pay_later', ts('Contribution is Pay Later?'), TRUE);
    $form->addYesNo('contribution_recurring', ts('Contribution is Recurring?'), TRUE);

    $form->addYesNo('contribution_test', ts('Contribution is a Test?'), TRUE);
    $form->addYesNo('is_template', ts('Contribution is Template?'), TRUE);
    // Add field for transaction ID search
    $form->addElement('text', 'contribution_trxn_id', ts("Transaction ID"));
    $form->addElement('text', 'contribution_check_number', ts('Check Number'));

    // Add field for pcp display in roll search
    $form->addYesNo('contribution_pcp_display_in_roll', ts('Personal Campaign Page Honor Roll?'), TRUE);

    // Soft credit related fields
    $options = [
      'only_contribs' => ts('Contributions Only'),
      'only_scredits' => ts('Soft Credits Only'),
      'both_related' => ts('Contributions and their related soft credit'),
      'both' => ts('All'),
      'only_contribs_unsoftcredited' => ts('Contributions without a soft credit'),
    ];
    $form->add('select', 'contribution_or_softcredits', ts('Contributions OR Soft Credits?'), $options, FALSE, ['class' => "crm-select2"]);
    $form->addSelect(
      'contribution_soft_credit_type_id',
      [
        'entity' => 'contribution_soft',
        'field' => 'soft_credit_type_id',
        'multiple' => TRUE,
        'context' => 'search',
      ]
    );

    $form->addField('financial_trxn_card_type_id', ['entity' => 'FinancialTrxn', 'name' => 'card_type_id', 'action' => 'get', 'label' => ts('Card Type')]);

    $form->add('text', 'financial_trxn_pan_truncation', ts('Card Number'), [
      'size' => 5,
      'maxlength' => 4,
      'autocomplete' => 'off',
    ]);

    if (CRM_Contribute_BAO_Query::isSiteHasProducts()) {
      // CRM-16713 - contribution search by premiums on 'Find Contribution' form.
      $form->add('select', 'contribution_product_id',
        ts('Premium'),
        CRM_Contribute_PseudoConstant::products(),
        FALSE, [
          'class' => 'crm-select2',
          'multiple' => 'multiple',
          'placeholder' => ts('- any -'),
        ]
      );
    }
    else {
      $form->addOptionalQuickFormElement('contribution_product_id');
    }

    self::addCustomFormFields($form, ['Contribution']);

    CRM_Campaign_BAO_Campaign::addCampaignInComponentSearch($form, 'contribution_campaign_id');

    // Add batch select
    $batches = CRM_Contribute_PseudoConstant::batch();

    if (!empty($batches)) {
      $form->add('select', 'contribution_batch_id',
        ts('Batch Name'),
        [
          '' => ts('- any -'),
          // CRM-19325
          'IS NULL' => ts('None'),
        ] + $batches,
        FALSE, ['class' => 'crm-select2']
      );
    }
    else {
      $form->addOptionalQuickFormElement('contribution_batch_id');
    }

    $form->assign('validCiviContribute', TRUE);
    $form->setDefaults(['contribution_test' => 0]);
    $form->setDefaults(['is_template' => 0]);

    CRM_Contribute_BAO_ContributionRecur::recurringContribution($form);
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
      $tables = array_merge(['civicrm_contribution' => 1], $tables);
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
   * Add the soft credit fields to the select fields.
   *
   * Extracted into separate function to improve readability of main select function.
   *
   * @param CRM_Contact_BAO_Query $query
   */
  private static function addSoftCreditFields(&$query) {
    $includeSoftCredits = self::isSoftCreditOptionEnabled($query->_params);
    if (!empty($query->_returnProperties['contribution_soft_credit_name'])) {
      if ($includeSoftCredits) {
        $query->_select['contribution_soft_credit_name'] = "civicrm_contact_d.sort_name as contribution_soft_credit_name";
        // also include contact id. Will help build hyperlinks
        $query->_select['contribution_soft_credit_contact_id'] = "civicrm_contact_d.id as contribution_soft_credit_contact_id";
      }
      $query->_element['contribution_soft_credit_name'] = 1;
      $query->_element['contribution_soft_credit_contact_id'] = 1;

      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['civicrm_contribution_soft'] = 1;
      $query->_tables['civicrm_contribution_soft_contact'] = 1;
    }

    if (!empty($query->_returnProperties['contribution_soft_credit_contact_id'])) {
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['civicrm_contribution_soft'] = 1;
      $query->_tables['civicrm_contribution_soft_contact'] = 1;
    }

    if (!empty($query->_returnProperties['contribution_soft_credit_amount'])) {
      if ($includeSoftCredits) {
        $query->_select['contribution_soft_credit_amount'] = "civicrm_contribution_soft.amount as contribution_soft_credit_amount";
      }
      $query->_element['contribution_soft_credit_amount'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['civicrm_contribution_soft'] = 1;
    }

    if (!empty($query->_returnProperties['contribution_soft_credit_type'])) {
      if ($includeSoftCredits) {
        $query->_select['contribution_soft_credit_type'] = "contribution_softcredit_type.label as contribution_soft_credit_type";
      }
      $query->_element['contribution_soft_credit_type'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['contribution_softcredit_type'] = 1;
    }

    if (!empty($query->_returnProperties['contribution_soft_credit_contribution_id'])) {
      if ($includeSoftCredits) {
        $query->_select['contribution_soft_credit_contribution_id'] = "civicrm_contribution_soft.contribution_id as contribution_soft_credit_contribution_id";
      }
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

    if (!empty($query->_returnProperties['contribution_soft_credit_pcp_id'])) {
      $query->_select['contribution_soft_credit_pcp_id'] = "civicrm_contribution_soft.pcp_id as contribution_soft_credit_pcp_id";
      $query->_element['contribution_soft_credit_pcp_id'] = 1;
      $query->_tables['civicrm_contribution'] = 1;
      $query->_tables['civicrm_contribution_soft'] = 1;
    }
  }

}
