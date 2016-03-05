<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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
 *
 */
class CRM_Grant_BAO_Query {
  /**
   * @return array
   */
  public static function &getFields() {
    $fields = array();
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
   * @param $query
   */
  public static function whereClauseSingle(&$values, &$query) {
    $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
    list($name, $op, $value, $grouping, $wildcard) = $values;
    $val = $names = array();
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

      case 'grant_application_received_notset':
        $query->_where[$grouping][] = "civicrm_grant.application_received_date IS NULL";
        $query->_qill[$grouping][] = ts("Grant Application Received Date is NULL");
        $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;
        return;

      case 'grant_due_date_low':
      case 'grant_due_date_high':
        $query->dateQueryBuilder($values, 'civicrm_grant',
          'grant_due_date',
          'grant_due_date', 'Grant Due Date'
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
          'decision_date', 'Grant Decision Date'
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
          $label = 'Grant Type(s)';
        }
        else {
          $name = 'status_id';
          $label = 'Grant Status(s)';
        }

        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_grant.$name", $op, $value, "Integer");

        list($qillop, $qillVal) = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Grant_DAO_Grant', $name, $value, $op);
        $query->_qill[$grouping][] = ts("%1 %2 %3", array(1 => $label, 2 => $qillop, 3 => $qillVal));
        $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;

        return;

      case 'grant_report_received':

        if ($value == 1) {
          $yesNo = 'Yes';
          $query->_where[$grouping][] = "civicrm_grant.grant_report_received $op $value";
        }
        elseif ($value == 0) {
          $yesNo = 'No';
          $query->_where[$grouping][] = "civicrm_grant.grant_report_received IS NULL";
        }

        $query->_qill[$grouping][] = "Grant Report Received = $yesNo ";
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
   * Getter for the qill object.
   *
   * @return string
   */
  public function qill() {
    return (isset($this->_qill)) ? $this->_qill : "";
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
      $properties = array(
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
      );
    }

    return $properties;
  }

  /**
   * Add all the elements shared between grant search and advanaced search.
   *
   *
   * @param CRM_Core_Form $form
   *
   * @return void
   */
  public static function buildSearchForm(&$form) {

    $grantType = CRM_Core_OptionGroup::values('grant_type');
    $form->add('select', 'grant_type_id', ts('Grant Type'), $grantType, FALSE,
      array('id' => 'grant_type_id', 'multiple' => 'multiple', 'class' => 'crm-select2')
    );

    $grantStatus = CRM_Core_OptionGroup::values('grant_status');
    $form->add('select', 'grant_status_id', ts('Grant Status'), $grantStatus, FALSE,
      array('id' => 'grant_status_id', 'multiple' => 'multiple', 'class' => 'crm-select2')
    );

    $form->addDate('grant_application_received_date_low', ts('App. Received Date - From'), FALSE, array('formatType' => 'searchDate'));
    $form->addDate('grant_application_received_date_high', ts('To'), FALSE, array('formatType' => 'searchDate'));

    $form->addElement('checkbox', 'grant_application_received_notset', '', NULL);

    $form->addDate('grant_money_transfer_date_low', ts('Money Sent Date - From'), FALSE, array('formatType' => 'searchDate'));
    $form->addDate('grant_money_transfer_date_high', ts('To'), FALSE, array('formatType' => 'searchDate'));

    $form->addElement('checkbox', 'grant_money_transfer_date_notset', '', NULL);

    $form->addDate('grant_due_date_low', ts('Report Due Date - From'), FALSE, array('formatType' => 'searchDate'));
    $form->addDate('grant_due_date_high', ts('To'), FALSE, array('formatType' => 'searchDate'));

    $form->addElement('checkbox', 'grant_due_date_notset', '', NULL);

    $form->addDate('grant_decision_date_low', ts('Grant Decision Date - From'), FALSE, array('formatType' => 'searchDate'));
    $form->addDate('grant_decision_date_high', ts('To'), FALSE, array('formatType' => 'searchDate'));

    $form->addElement('checkbox', 'grant_decision_date_notset', '', NULL);

    $form->addYesNo('grant_report_received', ts('Grant report received?'), TRUE);

    $form->add('text', 'grant_amount_low', ts('Minimum Amount'), array('size' => 8, 'maxlength' => 8));
    $form->addRule('grant_amount_low', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('9.99', ' '))), 'money');

    $form->add('text', 'grant_amount_high', ts('Maximum Amount'), array('size' => 8, 'maxlength' => 8));
    $form->addRule('grant_amount_high', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('99.99', ' '))), 'money');

    // add all the custom  searchable fields
    $grant = array('Grant');
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE, $grant);
    if ($groupDetails) {
      $form->assign('grantGroupTree', $groupDetails);
      foreach ($groupDetails as $group) {
        foreach ($group['fields'] as $field) {
          $fieldId = $field['id'];
          $elementName = 'custom_' . $fieldId;
          CRM_Core_BAO_CustomField::addQuickFormElement($form, $elementName, $fieldId, FALSE, TRUE);
        }
      }
    }

    $form->assign('validGrant', TRUE);
  }

  /**
   * @param $row
   * @param int $id
   */
  public static function searchAction(&$row, $id) {
  }

  /**
   * @param $tables
   */
  public static function tableNames(&$tables) {
  }

}
