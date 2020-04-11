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
 * $Id$
 *
 */
class CRM_Grant_BAO_Query extends CRM_Core_BAO_Query {

  /**
   * @return array
   */
  public static function &getFields() {
    $fields = [];
    $fields = CRM_Grant_BAO_Grant::exportableFields();
    return $fields;
  }

  /**
   * Build select for CiviGrant.
   *
   * @param $query
   *
   * @return void
   */
  public static function select(&$query) {
    if (!empty($query->_returnProperties['grant_status_id'])) {
      $query->_select['grant_status_id'] = 'grant_status.id as grant_status_id';
      $query->_element['grant_status'] = 1;
      $query->_tables['grant_status'] = $query->_whereTables['grant_status'] = 1;
      $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;
    }

    if (!empty($query->_returnProperties['grant_status'])) {
      $query->_select['grant_status'] = 'grant_status.label as grant_status';
      $query->_element['grant_status'] = 1;
      $query->_tables['grant_status'] = $query->_whereTables['grant_status'] = 1;
      $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;
    }

    if (!empty($query->_returnProperties['grant_type_id'])) {
      $query->_select['grant_type_id'] = 'grant_type.id as grant_type_id';
      $query->_element['grant_type'] = 1;
      $query->_tables['grant_type'] = $query->_whereTables['grant_type'] = 1;
      $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;
    }

    if (!empty($query->_returnProperties['grant_type'])) {
      $query->_select['grant_type'] = 'grant_type.label as grant_type';
      $query->_element['grant_type'] = 1;
      $query->_tables['grant_type'] = $query->_whereTables['grant_type'] = 1;
      $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;
    }

    if (!empty($query->_returnProperties['grant_note'])) {
      $query->_select['grant_note'] = "civicrm_note.note as grant_note";
      $query->_element['grant_note'] = 1;
      $query->_tables['grant_note'] = 1;
    }

    if ($query->_mode & CRM_Contact_BAO_Query::MODE_GRANT) {
      $query->_select['grant_amount_requested'] = 'civicrm_grant.amount_requested as grant_amount_requested';
      $query->_select['grant_amount_granted'] = 'civicrm_grant.amount_granted as grant_amount_granted';
      $query->_select['grant_amount_total'] = 'civicrm_grant.amount_total as grant_amount_total';
      $query->_select['grant_application_received_date'] = 'civicrm_grant.application_received_date as grant_application_received_date ';
      $query->_select['grant_report_received'] = 'civicrm_grant.grant_report_received as grant_report_received';
      $query->_select['grant_money_transfer_date'] = 'civicrm_grant.money_transfer_date as grant_money_transfer_date';
      $query->_element['grant_type_id'] = 1;
      $query->_element['grant_status_id'] = 1;
      $query->_tables['civicrm_grant'] = 1;
      $query->_whereTables['civicrm_grant'] = 1;
    }
  }

  /**
   * Given a list of conditions in params generate the required.
   * where clause
   *
   * @param $query
   *
   * @return void
   */
  public static function where(&$query) {
    foreach ($query->_params as $id => $values) {
      if (!is_array($values) || count($values) != 5) {
        continue;
      }

      if (substr($values[0], 0, 6) == 'grant_') {
        self::whereClauseSingle($values, $query);
      }
    }
  }

  /**
   * @param $values
   * @param \CRM_Contact_BAO_Query $query
   */
  public static function whereClauseSingle(&$values, &$query) {
    list($name, $op, $value, $grouping, $wildcard) = $values;
    switch ($name) {
      case 'grant_money_transfer_date_low':
      case 'grant_money_transfer_date_high':
        $query->dateQueryBuilder($values, 'civicrm_grant',
          'grant_money_transfer_date', 'money_transfer_date',
          'Money Transfer Date'
        );
        return;

      case 'grant_money_transfer_date_notset':
        $query->_where[$grouping][] = "civicrm_grant.money_transfer_date IS NULL";
        $query->_qill[$grouping][] = ts("Grant Money Transfer Date is NULL");
        $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;
        return;

      case 'grant_application_received_date_low':
      case 'grant_application_received_date_high':
        $query->dateQueryBuilder($values, 'civicrm_grant',
          'grant_application_received_date',
          'application_received_date', 'Application Received Date'
        );
        return;

      case 'grant_application_received_date_notset':
        $query->_where[$grouping][] = "civicrm_grant.application_received_date IS NULL";
        $query->_qill[$grouping][] = ts("Grant Application Received Date is NULL");
        $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;
        return;

      case 'grant_due_date_low':
      case 'grant_due_date_high':
        $query->dateQueryBuilder($values, 'civicrm_grant',
          'grant_due_date',
          'grant_due_date', ts('Grant Due Date')
        );
        return;

      case 'grant_due_date_notset':
        $query->_where[$grouping][] = "civicrm_grant.grant_due_date IS NULL";
        $query->_qill[$grouping][] = ts("Grant Due Date is NULL");
        $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;
        return;

      case 'grant_decision_date_low':
      case 'grant_decision_date_high':
        $query->dateQueryBuilder($values, 'civicrm_grant',
          'grant_decision_date',
          'decision_date', ts('Grant Decision Date')
        );
        return;

      case 'grant_decision_date_notset':
        $query->_where[$grouping][] = "civicrm_grant.decision_date IS NULL";
        $query->_qill[$grouping][] = ts("Grant Decision Date is NULL");
        $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;
        return;

      case 'grant_type_id':
      case 'grant_type':
      case 'grant_status_id':
      case 'grant_status':

        if (strstr($name, 'type')) {
          $name = 'grant_type_id';
          $label = ts('Grant Type(s)');
        }
        else {
          $name = 'status_id';
          $label = ts('Grant Status(s)');
        }

        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_grant.$name", $op, $value, "Integer");

        list($qillop, $qillVal) = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Grant_DAO_Grant', $name, $value, $op);
        $query->_qill[$grouping][] = ts("%1 %2 %3", [1 => $label, 2 => $qillop, 3 => $qillVal]);
        $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;

        return;

      case 'grant_report_received':

        if ($value == 1) {
          $yesNo = ts('Yes');
          $query->_where[$grouping][] = "civicrm_grant.grant_report_received $op $value";
        }
        elseif ($value == 0) {
          $yesNo = ts('No');
          $query->_where[$grouping][] = "civicrm_grant.grant_report_received IS NULL";
        }

        $query->_qill[$grouping][] = ts('Grant Report Received = %1', [1 => $yesNo]);
        $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;

        return;

      case 'grant_amount':
      case 'grant_amount_low':
      case 'grant_amount_high':
        $query->numberRangeBuilder($values,
          'civicrm_grant', 'grant_amount', 'amount_total', 'Total Amount'
        );
    }
  }

  /**
   * @param string $name
   * @param $mode
   * @param $side
   *
   * @return null|string
   */
  public static function from($name, $mode, $side) {
    $from = NULL;
    switch ($name) {
      case 'civicrm_grant':
        $from = " $side JOIN civicrm_grant ON civicrm_grant.contact_id = contact_a.id ";
        break;

      case 'grant_status':
        $from .= " $side JOIN civicrm_option_group option_group_grant_status ON (option_group_grant_status.name = 'grant_status')";
        $from .= " $side JOIN civicrm_option_value grant_status ON (civicrm_grant.status_id = grant_status.value AND option_group_grant_status.id = grant_status.option_group_id ) ";
        break;

      case 'grant_type':
        $from .= " $side JOIN civicrm_option_group option_group_grant_type ON (option_group_grant_type.name = 'grant_type')";
        if ($mode & CRM_Contact_BAO_Query::MODE_GRANT) {
          $from .= " INNER JOIN civicrm_option_value grant_type ON (civicrm_grant.grant_type_id = grant_type.value AND option_group_grant_type.id = grant_type.option_group_id ) ";
        }
        else {
          $from .= " $side JOIN civicrm_option_value grant_type ON (civicrm_grant.grant_type_id = grant_type.value AND option_group_grant_type.id = grant_type.option_group_id ) ";
        }
        break;

      case 'grant_note':
        $from .= " $side JOIN civicrm_note ON ( civicrm_note.entity_table = 'civicrm_grant' AND
                                                        civicrm_grant.id = civicrm_note.entity_id )";
        break;
    }
    return $from;
  }

  /**
   * @param $mode
   * @param bool $includeCustomFields
   *
   * @return array|null
   */
  public static function defaultReturnProperties(
    $mode,
    $includeCustomFields = TRUE
  ) {
    $properties = NULL;
    if ($mode & CRM_Contact_BAO_Query::MODE_GRANT) {
      $properties = [
        'contact_type' => 1,
        'contact_sub_type' => 1,
        'sort_name' => 1,
        'grant_id' => 1,
        'grant_type' => 1,
        'grant_status' => 1,
        'grant_amount_requested' => 1,
        'grant_application_received_date' => 1,
        'grant_report_received' => 1,
        'grant_money_transfer_date' => 1,
        'grant_note' => 1,
      ];
    }

    return $properties;
  }

  /**
   * Get the metadata for fields to be included on the grant search form.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function getSearchFieldMetadata() {
    $fields = [
      'grant_report_received',
      'grant_application_received_date',
      'grant_decision_date',
      'grant_money_transfer_date',
      'grant_due_date',
    ];
    $metadata = civicrm_api3('Grant', 'getfields', [])['values'];
    return array_intersect_key($metadata, array_flip($fields));
  }

  /**
   * Transitional function for specifying which fields the tpl can iterate through.
   */
  public static function getTemplateHandlableSearchFields() {
    return array_diff_key(self::getSearchFieldMetadata(), ['grant_report_received' => 1]);
  }

  /**
   * Add all the elements shared between grant search and advanaced search.
   *
   *
   * @param \CRM_Grant_Form_Search $form
   *
   * @return void
   */
  public static function buildSearchForm(&$form) {

    $grantType = CRM_Core_OptionGroup::values('grant_type');
    $form->addSearchFieldMetadata(['Grant' => self::getSearchFieldMetadata()]);
    $form->addFormFieldsFromMetadata();
    $form->assign('grantSearchFields', self::getTemplateHandlableSearchFields());
    $form->add('select', 'grant_type_id', ts('Grant Type'), $grantType, FALSE,
      ['id' => 'grant_type_id', 'multiple' => 'multiple', 'class' => 'crm-select2']
    );

    $grantStatus = CRM_Core_OptionGroup::values('grant_status');
    $form->add('select', 'grant_status_id', ts('Grant Status'), $grantStatus, FALSE,
      ['id' => 'grant_status_id', 'multiple' => 'multiple', 'class' => 'crm-select2']
    );
    $form->addElement('checkbox', 'grant_application_received_date_notset', ts('Date is not set'), NULL);
    $form->addElement('checkbox', 'grant_money_transfer_date_notset', ts('Date is not set'), NULL);
    $form->addElement('checkbox', 'grant_due_date_notset', ts('Date is not set'), NULL);
    $form->addElement('checkbox', 'grant_decision_date_notset', ts('Date is not set'), NULL);

    $form->add('text', 'grant_amount_low', ts('Minimum Amount'), ['size' => 8, 'maxlength' => 8]);
    $form->addRule('grant_amount_low', ts('Please enter a valid money value (e.g. %1).', [1 => CRM_Utils_Money::format('9.99', ' ')]), 'money');

    $form->add('text', 'grant_amount_high', ts('Maximum Amount'), ['size' => 8, 'maxlength' => 8]);
    $form->addRule('grant_amount_high', ts('Please enter a valid money value (e.g. %1).', [1 => CRM_Utils_Money::format('99.99', ' ')]), 'money');

    self::addCustomFormFields($form, ['Grant']);

    $form->assign('validGrant', TRUE);
  }

}
