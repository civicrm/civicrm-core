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
class CRM_Pledge_BAO_Query extends CRM_Core_BAO_Query {
  /**
   * Get pledge fields.
   *
   * @param bool $checkPermission
   *
   * @return array
   */
  public static function getFields($checkPermission = TRUE) {
    return CRM_Pledge_BAO_Pledge::exportableFields($checkPermission);
  }

  /**
   * Build select for Pledge.
   *
   * @param CRM_Contact_BAO_Query $query
   */
  public static function select(&$query) {

    $statusId = implode(',', array_keys(CRM_Core_PseudoConstant::accountOptionValues("contribution_status", NULL, " AND v.name IN  ('Pending', 'Overdue')")));
    if (($query->_mode & CRM_Contact_BAO_Query::MODE_PLEDGE) || !empty($query->_returnProperties['pledge_id'])) {
      $query->_select['pledge_id'] = 'civicrm_pledge.id as pledge_id';
      $query->_element['pledge_id'] = 1;
      $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
    }

    // add pledge select
    if (!empty($query->_returnProperties['pledge_amount'])) {
      $query->_select['pledge_amount'] = 'civicrm_pledge.amount as pledge_amount';
      $query->_element['pledge_amount'] = 1;
      $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
    }

    if (!empty($query->_returnProperties['pledge_create_date'])) {
      $query->_select['pledge_create_date'] = 'civicrm_pledge.create_date as pledge_create_date';
      $query->_element['pledge_create_date'] = 1;
      $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
    }

    if (!empty($query->_returnProperties['pledge_start_date'])) {
      $query->_select['pledge_start_date'] = 'civicrm_pledge.start_date as pledge_start_date';
      $query->_element['pledge_start_date'] = 1;
      $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
    }

    if (!empty($query->_returnProperties['pledge_status_id'])) {
      $query->_select['pledge_status_id'] = 'pledge_status.value as pledge_status_id';
      $query->_element['pledge_status'] = 1;
      $query->_tables['pledge_status'] = $query->_whereTables['pledge_status'] = 1;
      $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
    }

    if (!empty($query->_returnProperties['pledge_status'])) {
      $query->_select['pledge_status'] = 'pledge_status.label as pledge_status';
      $query->_element['pledge_status'] = 1;
      $query->_tables['pledge_status'] = $query->_whereTables['pledge_status'] = 1;
      $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
    }

    if (!empty($query->_returnProperties['pledge_total_paid'])) {
      $query->_select['pledge_total_paid'] = ' (SELECT sum(civicrm_pledge_payment.actual_amount) FROM civicrm_pledge_payment WHERE civicrm_pledge_payment.pledge_id = civicrm_pledge.id AND civicrm_pledge_payment.status_id = 1 ) as pledge_total_paid';
      $query->_element['pledge_total_paid'] = 1;
    }

    if (!empty($query->_returnProperties['pledge_next_pay_date'])) {
      $query->_select['pledge_next_pay_date'] = " (SELECT civicrm_pledge_payment.scheduled_date FROM civicrm_pledge_payment WHERE civicrm_pledge_payment.pledge_id = civicrm_pledge.id AND civicrm_pledge_payment.status_id IN ({$statusId}) ORDER BY civicrm_pledge_payment.scheduled_date ASC LIMIT 0, 1) as pledge_next_pay_date";
      $query->_element['pledge_next_pay_date'] = 1;
    }

    if (!empty($query->_returnProperties['pledge_next_pay_amount'])) {
      $query->_select['pledge_next_pay_amount'] = " (SELECT civicrm_pledge_payment.scheduled_amount FROM civicrm_pledge_payment WHERE civicrm_pledge_payment.pledge_id = civicrm_pledge.id AND civicrm_pledge_payment.status_id IN ({$statusId}) ORDER BY civicrm_pledge_payment.scheduled_date ASC LIMIT 0, 1) as pledge_next_pay_amount";
      $query->_element['pledge_next_pay_amount'] = 1;

      $query->_select['pledge_outstanding_amount'] = " (SELECT sum(civicrm_pledge_payment.scheduled_amount) FROM civicrm_pledge_payment WHERE civicrm_pledge_payment.pledge_id = civicrm_pledge.id AND civicrm_pledge_payment.status_id = 6 ) as pledge_outstanding_amount";
      $query->_element['pledge_outstanding_amount'] = 1;
    }

    if (!empty($query->_returnProperties['pledge_financial_type'])) {
      $query->_select['pledge_financial_type'] = "(SELECT civicrm_financial_type.name FROM civicrm_financial_type WHERE civicrm_financial_type.id = civicrm_pledge.financial_type_id) as pledge_financial_type";
      $query->_element['pledge_financial_type'] = 1;
      $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
    }

    if (!empty($query->_returnProperties['pledge_contribution_page_id'])) {
      $query->_select['pledge_contribution_page_id'] = 'civicrm_pledge.contribution_page_id as pledge_contribution_page_id';
      $query->_element['pledge_contribution_page_id'] = 1;
      $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
    }

    if (!empty($query->_returnProperties['pledge_payment_id'])) {
      $query->_select['pledge_payment_id'] = 'civicrm_pledge_payment.id as pledge_payment_id';
      $query->_element['pledge_payment_id'] = 1;
      $query->_tables['civicrm_pledge_payment'] = $query->_whereTables['civicrm_pledge_payment'] = 1;
    }

    if (!empty($query->_returnProperties['pledge_payment_scheduled_amount'])) {
      $query->_select['pledge_payment_scheduled_amount'] = 'civicrm_pledge_payment.scheduled_amount as pledge_payment_scheduled_amount';
      $query->_element['pledge_payment_scheduled_amount'] = 1;
      $query->_tables['civicrm_pledge_payment'] = $query->_whereTables['civicrm_pledge_payment'] = 1;
    }

    if (!empty($query->_returnProperties['pledge_payment_scheduled_date'])) {
      $query->_select['pledge_payment_scheduled_date'] = 'civicrm_pledge_payment.scheduled_date as pledge_payment_scheduled_date';
      $query->_element['pledge_payment_scheduled_date'] = 1;
      $query->_tables['civicrm_pledge_payment'] = $query->_whereTables['civicrm_pledge_payment'] = 1;
    }

    if (!empty($query->_returnProperties['pledge_payment_paid_amount'])) {
      $query->_select['pledge_payment_paid_amount'] = 'civicrm_pledge_payment.actual_amount as pledge_payment_paid_amount';
      $query->_element['pledge_payment_paid_amount'] = 1;
      $query->_tables['civicrm_pledge_payment'] = $query->_whereTables['civicrm_pledge_payment'] = 1;
    }

    if (!empty($query->_returnProperties['pledge_payment_paid_date'])) {
      $query->_select['pledge_payment_paid_date'] = 'payment_contribution.receive_date as pledge_payment_paid_date';
      $query->_element['pledge_payment_paid_date'] = 1;
      $query->_tables['civicrm_pledge_payment'] = $query->_whereTables['civicrm_pledge_payment'] = 1;
      $query->_tables['payment_contribution'] = $query->_whereTables['payment_contribution'] = 1;
    }

    if (!empty($query->_returnProperties['pledge_payment_reminder_date'])) {
      $query->_select['pledge_payment_reminder_date'] = 'civicrm_pledge_payment.reminder_date as pledge_payment_reminder_date';
      $query->_element['pledge_payment_reminder_date'] = 1;
      $query->_tables['civicrm_pledge_payment'] = $query->_whereTables['civicrm_pledge_payment'] = 1;
    }

    if (!empty($query->_returnProperties['pledge_payment_reminder_count'])) {
      $query->_select['pledge_payment_reminder_count'] = 'civicrm_pledge_payment.reminder_count as pledge_payment_reminder_count';
      $query->_element['pledge_payment_reminder_count'] = 1;
      $query->_tables['civicrm_pledge_payment'] = $query->_whereTables['civicrm_pledge_payment'] = 1;
    }

    if (!empty($query->_returnProperties['pledge_payment_status_id'])) {
      $query->_select['pledge_payment_status_id'] = 'payment_status.name as pledge_payment_status_id';
      $query->_element['pledge_payment_status_id'] = 1;
      $query->_tables['payment_status'] = $query->_whereTables['payment_status'] = 1;
      $query->_tables['civicrm_pledge_payment'] = $query->_whereTables['civicrm_pledge_payment'] = 1;
    }

    if (!empty($query->_returnProperties['pledge_payment_status'])) {
      $query->_select['pledge_payment_status'] = 'payment_status.label as pledge_payment_status';
      $query->_element['pledge_payment_status'] = 1;
      $query->_tables['payment_status'] = $query->_whereTables['payment_status'] = 1;
      $query->_tables['civicrm_pledge_payment'] = $query->_whereTables['civicrm_pledge_payment'] = 1;
    }

    if (!empty($query->_returnProperties['pledge_frequency_interval'])) {
      $query->_select['pledge_frequency_interval'] = 'civicrm_pledge.frequency_interval as pledge_frequency_interval';
      $query->_element['pledge_frequency_interval'] = 1;
      $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
    }

    if (!empty($query->_returnProperties['pledge_frequency_unit'])) {
      $query->_select['pledge_frequency_unit'] = 'civicrm_pledge.frequency_unit as pledge_frequency_unit';
      $query->_element['pledge_frequency_unit'] = 1;
      $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
    }

    if (!empty($query->_returnProperties['pledge_is_test'])) {
      $query->_select['pledge_is_test'] = 'civicrm_pledge.is_test as pledge_is_test';
      $query->_element['pledge_is_test'] = 1;
      $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
    }
    if (!empty($query->_returnProperties['pledge_campaign_id'])) {
      $query->_select['pledge_campaign_id'] = 'civicrm_pledge.campaign_id as pledge_campaign_id';
      $query->_element['pledge_campaign_id'] = 1;
      $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
    }

    if (!empty($query->_returnProperties['pledge_currency'])) {
      $query->_select['pledge_currency'] = 'civicrm_pledge.currency as pledge_currency';
      $query->_element['pledge_currency'] = 1;
      $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
    }
  }

  /**
   * @param $query
   */
  public static function where(&$query) {
    $grouping = NULL;
    foreach (array_keys($query->_params) as $id) {
      if (empty($query->_params[$id][0])) {
        continue;
      }
      if (substr($query->_params[$id][0], 0, 7) == 'pledge_') {
        if ($query->_mode == CRM_Contact_BAO_QUERY::MODE_CONTACTS) {
          $query->_useDistinct = TRUE;
        }
        $grouping = $query->_params[$id][3];
        self::whereClauseSingle($query->_params[$id], $query);
      }
    }
  }

  /**
   * @param $values
   * @param $query
   */
  public static function whereClauseSingle(&$values, &$query) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    switch ($name) {
      case 'pledge_create_date_low':
      case 'pledge_create_date_high':
        // process to / from date
        $query->dateQueryBuilder($values,
          'civicrm_pledge', 'pledge_create_date', 'create_date', 'Pledge Made'
        );
      case 'pledge_start_date_low':
      case 'pledge_start_date_high':
        // process to / from date
        $query->dateQueryBuilder($values,
          'civicrm_pledge', 'pledge_start_date', 'start_date', 'Pledge Start Date'
        );
        return;

      case 'pledge_end_date_low':
      case 'pledge_end_date_high':
        // process to / from date
        $query->dateQueryBuilder($values,
          'civicrm_pledge', 'pledge_end_date', 'end_date', 'Pledge End Date'
        );
        return;

      case 'pledge_payment_date_low':
      case 'pledge_payment_date_high':
        // process to / from date
        $query->dateQueryBuilder($values,
          'civicrm_pledge_payment', 'pledge_payment_date', 'scheduled_date', 'Payment Scheduled'
        );
        return;

      case 'pledge_amount':
      case 'pledge_amount_low':
      case 'pledge_amount_high':
        // process min/max amount
        $query->numberRangeBuilder($values,
          'civicrm_pledge', 'pledge_amount', 'amount', 'Pledge Amount'
        );
        return;

      case 'pledge_installments_low':
      case 'pledge_installments_high':
        // process min/max amount
        $query->numberRangeBuilder($values,
          'civicrm_pledge', 'pledge_installments', 'installments', 'Number of Installments'
        );
        return;

      case 'pledge_acknowledge_date_is_not_null':
        if ($value) {
          $op = "IS NOT NULL";
          $query->_qill[$grouping][] = ts('Pledge Acknowledgement Sent');
        }
        else {
          $op = "IS NULL";
          $query->_qill[$grouping][] = ts('Pledge Acknowledgement  Not Sent');
        }
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_pledge.acknowledge_date", $op);
        return;

      case 'pledge_payment_status_id':
      case 'pledge_status_id':
        if ($name == 'pledge_status_id') {
          $tableName = 'civicrm_pledge';
          $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
          $label = "Pledge Status";
          $qillDAO = 'CRM_Pledge_DAO_Pledge';
          $qillField = 'status_id';
        }
        else {
          $tableName = 'civicrm_pledge_payment';
          $query->_tables['civicrm_pledge_payment'] = $query->_whereTables['civicrm_pledge_payment'] = 1;
          $label = "Pledge Payment Status";
          $qillDAO = 'CRM_Contribute_DAO_Contribution';
          $qillField = 'contribution_status_id';
        }
        $name = 'status_id';
        if (!empty($value) && is_array($value) && !in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
          $value = array('IN' => $value);
        }

        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("$tableName.$name",
          $op,
          $value,
          'Integer'
        );
        list($qillop, $qillVal) = CRM_Contact_BAO_Query::buildQillForFieldValue($qillDAO, $qillField, $value, $op);
        $query->_qill[$grouping][] = ts('%1 %2 %3', array(1 => $label, 2 => $qillop, 3 => $qillVal));
        return;

      case 'pledge_test':
      case 'pledge_is_test':
        // We dont want to include all tests for sql OR CRM-7827
        if (!$value || $query->getOperator() != 'OR') {
          $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('civicrm_pledge.is_test',
            $op,
            $value,
            'Boolean'
          );
          if ($value) {
            $query->_qill[$grouping][] = ts('Pledge is a Test');
          }
          $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
        }
        return;

      case 'pledge_financial_type_id':
        $type = CRM_Contribute_PseudoConstant::financialType($value);
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('civicrm_pledge.financial_type_id',
          $op,
          $value,
          'Integer'
        );
        $query->_qill[$grouping][] = ts('Financial Type - %1', array(1 => $type));
        $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
        return;

      case 'pledge_contribution_page_id':
        $page = CRM_Contribute_PseudoConstant::contributionPage($value);
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('civicrm_pledge.contribution_page_id',
          $op,
          $value,
          'Integer'
        );
        $query->_qill[$grouping][] = ts('Financial Page - %1', array(1 => $page));
        $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
        return;

      case 'pledge_id':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_pledge.id",
          $op,
          $value,
          "Integer"
        );
        $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
        return;

      case 'pledge_frequency_interval':
        $query->_where[$grouping][] = "civicrm_pledge.frequency_interval $op $value";
        $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
        return;

      case 'pledge_frequency_unit':
        $query->_where[$grouping][] = "civicrm_pledge.frequency_unit $op $value";
        $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
        return;

      case 'pledge_contact_id':
      case 'pledge_campaign_id':
        $name = str_replace('pledge_', '', $name);
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_pledge.$name", $op, $value, 'Integer');
        list($op, $value) = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Pledge_DAO_Pledge', $name, $value, $op);
        $label = ($name == 'campaign_id') ? 'Campaign' : 'Contact ID';
        $query->_qill[$grouping][] = ts('%1 %2 %3', array(1 => $label, 2 => $op, 3 => $value));
        $query->_tables['civicrm_pledge'] = $query->_whereTables['civicrm_pledge'] = 1;
        return;
    }
  }

  /**
   * From clause.
   *
   * @param string $name
   * @param string $mode
   * @param string $side
   *
   * @return null|string
   */
  public static function from($name, $mode, $side) {
    $from = NULL;

    switch ($name) {
      case 'civicrm_pledge':
        $from = " $side JOIN civicrm_pledge  ON civicrm_pledge.contact_id = contact_a.id ";
        break;

      case 'pledge_status':
        $from .= " $side JOIN civicrm_option_group option_group_pledge_status ON (option_group_pledge_status.name = 'pledge_status')";
        $from .= " $side JOIN civicrm_option_value pledge_status ON (civicrm_pledge.status_id = pledge_status.value AND option_group_pledge_status.id = pledge_status.option_group_id ) ";
        break;

      case 'pledge_financial_type':
        $from .= " $side JOIN civicrm_financial_type ON civicrm_pledge.financial_type_id = civicrm_financial_type.id ";
        break;

      case 'civicrm_pledge_payment':
        $from .= " $side JOIN civicrm_pledge_payment  ON civicrm_pledge_payment.pledge_id = civicrm_pledge.id ";
        break;

      case 'payment_contribution':
        $from .= " $side JOIN civicrm_contribution payment_contribution ON civicrm_pledge_payment.contribution_id  = payment_contribution.id ";
        break;

      case 'payment_status':
        $from .= " $side JOIN civicrm_option_group option_group_payment_status ON (option_group_payment_status.name = 'contribution_status')";
        $from .= " $side JOIN civicrm_option_value payment_status ON (civicrm_pledge_payment.status_id = payment_status.value AND option_group_payment_status.id = payment_status.option_group_id ) ";
        break;
    }

    return $from;
  }

  /**
   * Ideally this function should include fields that are displayed in the selector.
   *
   * @param int $mode
   * @param bool $includeCustomFields
   *
   * @return array|null
   */
  public static function defaultReturnProperties(
    $mode,
    $includeCustomFields = TRUE
  ) {
    $properties = NULL;

    if ($mode & CRM_Contact_BAO_Query::MODE_PLEDGE) {
      $properties = array(
        'contact_type' => 1,
        'contact_sub_type' => 1,
        'sort_name' => 1,
        'display_name' => 1,
        'pledge_id' => 1,
        'pledge_amount' => 1,
        'pledge_total_paid' => 1,
        'pledge_create_date' => 1,
        'pledge_start_date' => 1,
        'pledge_next_pay_date' => 1,
        'pledge_next_pay_amount' => 1,
        'pledge_status' => 1,
        'pledge_status_id' => 1,
        'pledge_is_test' => 1,
        'pledge_contribution_page_id' => 1,
        'pledge_financial_type' => 1,
        'pledge_frequency_interval' => 1,
        'pledge_frequency_unit' => 1,
        'pledge_currency' => 1,
        'pledge_campaign_id' => 1,
      );
    }
    return $properties;
  }

  /**
   * This includes any extra fields that might need for export etc.
   *
   * @param string $mode
   *
   * @return array|null
   */
  public static function extraReturnProperties($mode) {
    $properties = NULL;

    if ($mode & CRM_Contact_BAO_Query::MODE_PLEDGE) {
      $properties = array(
        'pledge_balance_amount' => 1,
        'pledge_payment_id' => 1,
        'pledge_payment_scheduled_amount' => 1,
        'pledge_payment_scheduled_date' => 1,
        'pledge_payment_paid_amount' => 1,
        'pledge_payment_paid_date' => 1,
        'pledge_payment_reminder_date' => 1,
        'pledge_payment_reminder_count' => 1,
        'pledge_payment_status_id' => 1,
        'pledge_payment_status' => 1,
      );

      // also get all the custom pledge properties
      $fields = CRM_Core_BAO_CustomField::getFieldsForImport('Pledge');
      if (!empty($fields)) {
        foreach ($fields as $name => $dontCare) {
          $properties[$name] = 1;
        }
      }
    }
    return $properties;
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function buildSearchForm(&$form) {
    // pledge related dates
    CRM_Core_Form_Date::buildDateRange($form, 'pledge_start_date', 1, '_low', '_high', ts('From'), FALSE);
    CRM_Core_Form_Date::buildDateRange($form, 'pledge_end_date', 1, '_low', '_high', ts('From'), FALSE);
    CRM_Core_Form_Date::buildDateRange($form, 'pledge_create_date', 1, '_low', '_high', ts('From'), FALSE);

    // pledge payment related dates
    CRM_Core_Form_Date::buildDateRange($form, 'pledge_payment_date', 1, '_low', '_high', ts('From'), FALSE);

    $form->addYesNo('pledge_test', ts('Pledge is a Test?'), TRUE);
    $form->add('text', 'pledge_amount_low', ts('From'), array('size' => 8, 'maxlength' => 8));
    $form->addRule('pledge_amount_low', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('9.99', ' '))), 'money');

    $form->add('text', 'pledge_amount_high', ts('To'), array('size' => 8, 'maxlength' => 8));
    $form->addRule('pledge_amount_high', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('99.99', ' '))), 'money');

    $form->add('select', 'pledge_status_id',
      ts('Pledge Status'), CRM_Pledge_BAO_Pledge::buildOptions('status_id'),
      FALSE, array('class' => 'crm-select2', 'multiple' => 'multiple')
    );

    $form->addYesNo('pledge_acknowledge_date_is_not_null', ts('Acknowledgement sent?'), TRUE);

    $form->add('text', 'pledge_installments_low', ts('From'), array('size' => 8, 'maxlength' => 8));
    $form->addRule('pledge_installments_low', ts('Please enter a number'), 'integer');

    $form->add('text', 'pledge_installments_high', ts('To'), array('size' => 8, 'maxlength' => 8));
    $form->addRule('pledge_installments_high', ts('Please enter number.'), 'integer');

    $form->add('select', 'pledge_payment_status_id',
      ts('Pledge Payment Status'), CRM_Pledge_BAO_PledgePayment::buildOptions('status_id'),
      FALSE, array('class' => 'crm-select2', 'multiple' => 'multiple')
    );

    $form->add('select', 'pledge_financial_type_id',
      ts('Financial Type'),
      array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::financialType(),
      FALSE, array('class' => 'crm-select2')
    );

    $form->add('select', 'pledge_contribution_page_id',
      ts('Contribution Page'),
      array('' => ts('- any -')) + CRM_Contribute_PseudoConstant::contributionPage(),
      FALSE, array('class' => 'crm-select2')
    );

    // add fields for pledge frequency
    $form->add('text', 'pledge_frequency_interval', ts('Every'), array('size' => 8, 'maxlength' => 8));
    $form->addRule('pledge_frequency_interval', ts('Please enter valid Pledge Frequency Interval'), 'integer');
    $frequencies = CRM_Core_OptionGroup::values('recur_frequency_units');
    foreach ($frequencies as $val => $label) {
      $freqUnitsDisplay["'{$val}'"] = ts('%1(s)', array(1 => $label));
    }

    $form->add('select', 'pledge_frequency_unit',
      ts('Pledge Frequency'),
      array('' => ts('- any -')) + $freqUnitsDisplay
    );

    self::addCustomFormFields($form, array('Pledge'));

    CRM_Campaign_BAO_Campaign::addCampaignInComponentSearch($form, 'pledge_campaign_id');

    $form->assign('validCiviPledge', TRUE);
    $form->setDefaults(array('pledge_test' => 0));
  }

  /**
   * @param $tables
   */
  public static function tableNames(&$tables) {
    // add status table
    if (!empty($tables['pledge_status']) || !empty($tables['civicrm_pledge_payment'])) {
      $tables = array_merge(array('civicrm_pledge' => 1), $tables);
    }
  }

}
