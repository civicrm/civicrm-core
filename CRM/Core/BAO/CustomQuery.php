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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Core_BAO_CustomQuery {
  CONST PREFIX = 'custom_value_';

  /**
   * the set of custom field ids
   *
   * @var array
   */
  protected $_ids;

  /**
   * the select clause
   *
   * @var array
   */
  public $_select;

  /**
   * the name of the elements that are in the select clause
   * used to extract the values
   *
   * @var array
   */
  public $_element;

  /**
   * the tables involved in the query
   *
   * @var array
   */
  public $_tables;
  public $_whereTables;

  /**
   * the where clause
   *
   * @var array
   */
  public $_where;

  /**
   * The english language version of the query
   *
   * @var array
   */
  public $_qill;

  /**
   * The cache to translate the option values into labels
   *
   * @var array
   */
  public $_options;

  /**
   * The custom fields information
   *
   * @var array
   */
  public $_fields;

  /**
   * Searching for contacts?
   *
   * @var boolean
   */
  protected $_contactSearch;

  /**
   * This stores custom data group types and tables that it extends
   *
   * @var array
   * @static
   */
  static $extendsMap = array(
    'Contact' => 'civicrm_contact',
    'Individual' => 'civicrm_contact',
    'Household' => 'civicrm_contact',
    'Organization' => 'civicrm_contact',
    'Contribution' => 'civicrm_contribution',
    'Membership' => 'civicrm_membership',
    'Participant' => 'civicrm_participant',
    'Group' => 'civicrm_group',
    'Relationship' => 'civicrm_relationship',
    'Event' => 'civicrm_event',
    'Case' => 'civicrm_case',
    'Activity' => 'civicrm_activity',
    'Pledge' => 'civicrm_pledge',
    'Grant' => 'civicrm_grant',
    'Address' => 'civicrm_address',
  );

  /**
   * class constructor
   *
   * Takes in a set of custom field ids andsets up the data structures to
   * generate a query
   *
   * @param  array  $ids     the set of custom field ids
   *
   * @access public
   */ function __construct($ids, $contactSearch = FALSE) {
    $this->_ids = &$ids;

    $this->_select      = array();
    $this->_element     = array();
    $this->_tables      = array();
    $this->_whereTables = array();
    $this->_where       = array();
    $this->_qill        = array();
    $this->_options     = array();

    $this->_fields = array();
    $this->_contactSearch = $contactSearch;

    if (empty($this->_ids)) {
      return;
    }

    // initialize the field array
    $tmpArray = array_keys($this->_ids);
    $idString = implode(',', $tmpArray);
    $query    = "
SELECT f.id, f.label, f.data_type,
       f.html_type, f.is_search_range,
       f.option_group_id, f.custom_group_id,
       f.column_name, g.table_name,
       f.date_format,f.time_format
  FROM civicrm_custom_field f,
       civicrm_custom_group g
 WHERE f.custom_group_id = g.id
   AND g.is_active = 1
   AND f.is_active = 1
   AND f.id IN ( $idString )";

    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      // get the group dao to figure which class this custom field extends
      $extends = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $dao->custom_group_id, 'extends');
      if (array_key_exists($extends, self::$extendsMap)) {
        $extendsTable = self::$extendsMap[$extends];
      }
      elseif (in_array($extends, CRM_Contact_BAO_ContactType::subTypes())) {
        // if $extends is a subtype, refer contact table
        $extendsTable = self::$extendsMap['Contact'];
      }
      $this->_fields[$dao->id] = array(
        'id' => $dao->id,
        'label' => $dao->label,
        'extends' => $extendsTable,
        'data_type' => $dao->data_type,
        'html_type' => $dao->html_type,
        'is_search_range' => $dao->is_search_range,
        'column_name' => $dao->column_name,
        'table_name' => $dao->table_name,
        'option_group_id' => $dao->option_group_id,
      );

      // store it in the options cache to make things easier
      // during option lookup
      $this->_options[$dao->id] = array();
      $this->_options[$dao->id]['attributes'] = array(
        'label' => $dao->label,
        'data_type' => $dao->data_type,
        'html_type' => $dao->html_type,
      );

      $optionGroupID = NULL;
      $htmlTypes = array('CheckBox', 'Radio', 'Select', 'Multi-Select', 'AdvMulti-Select', 'Autocomplete-Select');
      if (in_array($dao->html_type, $htmlTypes) && $dao->data_type != 'ContactReference') {
        if ($dao->option_group_id) {
          $optionGroupID = $dao->option_group_id;
        }
        elseif ($dao->data_type != 'Boolean') {
          $errorMessage = ts("The custom field %1 is corrupt. Please delete and re-build the field",
            array(1 => $dao->label)
          );
          CRM_Core_Error::fatal($errorMessage);
        }
      }
      elseif ($dao->html_type == 'Select Date') {
        $this->_options[$dao->id]['attributes']['date_format'] = $dao->date_format;
        $this->_options[$dao->id]['attributes']['time_format'] = $dao->time_format;
      }

      // build the cache for custom values with options (label => value)
      if ($optionGroupID != NULL) {
        $query = "
SELECT label, value
  FROM civicrm_option_value
 WHERE option_group_id = $optionGroupID
";

        $option = CRM_Core_DAO::executeQuery($query);
        while ($option->fetch()) {
          $dataType = $this->_fields[$dao->id]['data_type'];
          if ($dataType == 'Int' || $dataType == 'Float') {
            $num = round($option->value, 2);
            $this->_options[$dao->id]["$num"] = $option->label;
          }
          else {
            $this->_options[$dao->id][$option->value] = $option->label;
          }
        }
        $options = $this->_options[$dao->id];
        //unset attributes to avoid confussion
        unset($options['attributes']);
        CRM_Utils_Hook::customFieldOptions($dao->id, $options, FALSE);
      }
    }
  }

  /**
   * generate the select clause and the associated tables
   * for the from clause
   *
   * @param  NULL
   *
   * @return void
   * @access public
   */
  function select() {
    if (empty($this->_fields)) {
      return;
    }

    foreach ($this->_fields as $id => $field) {
      $name = $field['table_name'];
      $fieldName = 'custom_' . $field['id'];
      $this->_select["{$name}_id"] = "{$name}.id as {$name}_id";
      $this->_element["{$name}_id"] = 1;
      $this->_select[$fieldName] = "{$field['table_name']}.{$field['column_name']} as $fieldName";
      $this->_element[$fieldName] = 1;
      $joinTable = NULL;
      if ($field['extends'] == 'civicrm_contact') {
        $joinTable = 'contact_a';
      }
      elseif ($field['extends'] == 'civicrm_contribution') {
        $joinTable = 'civicrm_contribution';
      }
      elseif ($field['extends'] == 'civicrm_participant') {
        $joinTable = 'civicrm_participant';
      }
      elseif ($field['extends'] == 'civicrm_membership') {
        $joinTable = 'civicrm_membership';
      }
      elseif ($field['extends'] == 'civicrm_pledge') {
        $joinTable = 'civicrm_pledge';
      }
      elseif ($field['extends'] == 'civicrm_activity') {
        $joinTable = 'civicrm_activity';
      }
      elseif ($field['extends'] == 'civicrm_relationship') {
        $joinTable = 'civicrm_relationship';
      }
      elseif ($field['extends'] == 'civicrm_grant') {
        $joinTable = 'civicrm_grant';
      }
      elseif ($field['extends'] == 'civicrm_address') {
        $joinTable = 'civicrm_address';
      }
      elseif ($field['extends'] == 'civicrm_case') {
        $joinTable = 'civicrm_case';
      }

      if ($joinTable) {
        $this->_tables[$name] = "\nLEFT JOIN $name ON $name.entity_id = $joinTable.id";
        if ($this->_ids[$id]) {
          $this->_whereTables[$name] = $this->_tables[$name];
        }
        if ($joinTable != 'contact_a') {
          $this->_whereTables[$joinTable] = $this->_tables[$joinTable] = 1;
        }
        elseif ($this->_contactSearch) {
          CRM_Contact_BAO_Query::$_openedPanes[ts('Custom Fields')] = TRUE;
        }
      }
    }
  }

  /**
   * generate the where clause and also the english language
   * equivalent
   *
   * @param NULL
   *
   * @return void
   *
   * @access public
   */
  function where() {
    //CRM_Core_Error::debug( 'fld', $this->_fields );
    //CRM_Core_Error::debug( 'ids', $this->_ids );

    foreach ($this->_ids as $id => $values) {

      // Fixed for Isuue CRM 607
      if (CRM_Utils_Array::value($id, $this->_fields) === NULL ||
        !$values
      ) {
        continue;
      }

      $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';

      foreach ($values as $tuple) {
        list($name, $op, $value, $grouping, $wildcard) = $tuple;

        // fix $value here to escape sql injection attacks
        $field = $this->_fields[$id];
        $qillValue = CRM_Core_BAO_CustomField::getDisplayValue($value, $id, $this->_options);

        if (!is_array($value)) {
          $value = CRM_Core_DAO::escapeString(trim($value));
        }

        $fieldName = "{$field['table_name']}.{$field['column_name']}";
        switch ($field['data_type']) {
          case 'String':
            $sql = "$fieldName";
            // if we are coming in from listings,
            // for checkboxes the value is already in the right format and is NOT an array
            if (is_array($value)) {

              //ignoring $op value for checkbox and multi select
              $sqlValue   = array();
              $sqlOP      = ' AND ';
              $sqlOPlabel = ts('match ALL');
              if ($field['html_type'] == 'CheckBox') {
                foreach ($value as $k => $v) {
                  if ($v) {
                    if ($k == 'CiviCRM_OP_OR') {
                      $sqlOP = ' OR ';
                      $sqlOPlabel = ts('match ANY');
                      continue;
                    }

                    $sqlValue[] = "( $sql like '%" . CRM_Core_DAO::VALUE_SEPARATOR . $k . CRM_Core_DAO::VALUE_SEPARATOR . "%' ) ";
                  }
                }
                //if user check only 'CiviCRM_OP_OR' check box
                //of custom checkbox field, then ignore this field.
                if (!empty($sqlValue)) {
                  $this->_where[$grouping][] = ' ( ' . implode($sqlOP, $sqlValue) . ' ) ';
                  $this->_qill[$grouping][] = "{$field['label']} $op $qillValue ( $sqlOPlabel )";
                }
                // for multi select
              }
              else {
                foreach ($value as $k => $v) {
                  if ($v == 'CiviCRM_OP_OR') {
                    $sqlOP = ' OR ';
                    $sqlOPlabel = ts('match ANY');
                    continue;
                  }
                  $v = CRM_Core_DAO::escapeString($v);
                  $sqlValue[] = "( $sql like '%" . CRM_Core_DAO::VALUE_SEPARATOR . $v . CRM_Core_DAO::VALUE_SEPARATOR . "%' ) ";
                }
                //if user select only 'CiviCRM_OP_OR' value
                //of custom multi select field, then ignore this field.
                if (!empty($sqlValue)) {
                  $this->_where[$grouping][] = ' ( ' . implode($sqlOP, $sqlValue) . ' ) ';
                  $this->_qill[$grouping][] = "$field[label] $op $qillValue ( $sqlOPlabel )";
                }
              }
            }
            else {
              if ($field['is_search_range'] && is_array($value)) {
                $this->searchRange($field['id'],
                  $field['label'],
                  $field['data_type'],
                  $fieldName,
                  $value,
                  $grouping
                );
              }
              else {
                if ($field['html_type'] == 'Autocomplete-Select') {
                  $wildcard = FALSE;
                  $val = array_search($value, $this->_options[$field['id']]);
                }
                elseif (in_array($field['html_type'], array(
                  'Select', 'Radio'))) {
                  $wildcard = FALSE;
                  $val = CRM_Utils_Type::escape($value, 'String');
                }
                else {
                  $val = CRM_Utils_Type::escape($strtolower(trim($value)), 'String');
                }

                if ($wildcard) {
                  $val = $strtolower(CRM_Core_DAO::escapeString($val));
                  $val = "%$val%";
                  $op  = 'LIKE';
                }

                //FIX for custom data query fired against no value(NULL/NOT NULL)
                $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($sql, $op, $val, $field['data_type']);
                $this->_qill[$grouping][] = "$field[label] $op $qillValue";
              }
            }
            continue;

          case 'ContactReference':
            $label = $value ? CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $value, 'sort_name') : '';
            $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($fieldName, $op, $value, 'String');
            $this->_qill[$grouping][] = $field['label'] . " $op $label";
            continue;

          case 'Int':
            if ($field['is_search_range'] && is_array($value)) {
              $this->searchRange($field['id'], $field['label'], $field['data_type'], $fieldName, $value, $grouping);
            }
            else {
              $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($fieldName, $op, $value, 'Integer');
              $this->_qill[$grouping][] = $field['label'] . " $op $value";
            }
            continue;

          case 'Boolean':
            if (strtolower($value) == 'yes' || strtolower($value) == strtolower(ts('Yes'))) {
              $value = 1;
            }
            else {
              $value = (int) $value;
            }
            $value = ($value == 1) ? 1 : 0;
            $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($fieldName, $op, $value, 'Integer');
            $value = $value ? ts('Yes') : ts('No');
            $this->_qill[$grouping][] = $field['label'] . " {$op} {$value}";
            continue;

          case 'Link':
            $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($fieldName, $op, $value, 'String');
            $this->_qill[$grouping][] = $field['label'] . " $op $value";
            continue;

          case 'Float':
            if ($field['is_search_range'] && is_array($value)) {
              $this->searchRange($field['id'], $field['label'], $field['data_type'], $fieldName, $value, $grouping);
            }
            else {
              $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($fieldName, $op, $value, 'Float');
              $this->_qill[$grouping][] = $field['label'] . " {$op} {$value}";
            }
            continue;

          case 'Money':
            if ($field['is_search_range'] && is_array($value)) {
              foreach ($value as $key => $val) {
                $moneyFormat = CRM_Utils_Rule::cleanMoney($value[$key]);
                $value[$key] = $moneyFormat;
              }
              $this->searchRange($field['id'], $field['label'], $field['data_type'], $fieldName, $value, $grouping);
            }
            else {
              $moneyFormat = CRM_Utils_Rule::cleanMoney($value);
              $value = $moneyFormat;
              $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($fieldName, $op, $value, 'Float');
              $this->_qill[$grouping][] = $field['label'] . " {$op} {$value}";
            }
            continue;

          case 'Memo':
            $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($fieldName, $op, $value, 'String');
            $this->_qill[$grouping][] = "$field[label] $op $value";
            continue;

          case 'Date':
            $fromValue = CRM_Utils_Array::value('from', $value);
            $toValue = CRM_Utils_Array::value('to', $value);

            if (!$fromValue && !$toValue) {
              if (!CRM_Utils_Date::processDate($value) && $op != 'IS NULL' && $op != 'IS NOT NULL') {
                continue;
              }

              // hack to handle yy format during search
              if (is_numeric($value) && strlen($value) == 4) {
                $value = "01-01-{$value}";
              }

              $date = CRM_Utils_Date::processDate($value);
              $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($fieldName, $op, $date, 'String');
              $this->_qill[$grouping][] = $field['label'] . " {$op} " . CRM_Utils_Date::customFormat($date);
            }
            else {
              if (is_numeric($fromValue) && strlen($fromValue) == 4) {
                $fromValue = "01-01-{$fromValue}";
              }

              if (is_numeric($toValue) && strlen($toValue) == 4) {
                $toValue = "01-01-{$toValue}";
              }

              // TO DO: add / remove time based on date parts
              $fromDate = CRM_Utils_Date::processDate($fromValue);
              $toDate = CRM_Utils_Date::processDate($toValue);
              if (!$fromDate && !$toDate) {
                continue;
              }
              if ($fromDate) {
                $this->_where[$grouping][] = "$fieldName >= $fromDate";
                $this->_qill[$grouping][] = $field['label'] . ' >= ' . CRM_Utils_Date::customFormat($fromDate);
              }
              if ($toDate) {
                $this->_where[$grouping][] = "$fieldName <= $toDate";
                $this->_qill[$grouping][] = $field['label'] . ' <= ' . CRM_Utils_Date::customFormat($toDate);
              }
            }
            continue;

          case 'StateProvince':
          case 'Country':
            if (!is_array($value)) {
              $this->_where[$grouping][] = "$fieldName {$op} " . CRM_Utils_Type::escape($value, 'Int');
              $this->_qill[$grouping][] = $field['label'] . " {$op} {$qillValue}";
            }
            else {
              $sqlOP = ' AND ';
              $sqlOPlabel = ts('match ALL');
              foreach ($value as $k => $v) {
                if ($v == 'CiviCRM_OP_OR') {
                  $sqlOP = ' OR ';
                  $sqlOPlabel = ts('match ANY');
                  continue;
                }
                $sqlValue[] = "( $fieldName like '%" . CRM_Core_DAO::VALUE_SEPARATOR . $v . CRM_Core_DAO::VALUE_SEPARATOR . "%' ) ";
              }

              //if user select only 'CiviCRM_OP_OR' value
              //of custom multi select field, then ignore this field.
              if (!empty($sqlValue)) {
                $this->_where[$grouping][] = " ( " . implode($sqlOP, $sqlValue) . " ) ";
                $this->_qill[$grouping][] = "$field[label] $op $qillValue ( $sqlOPlabel )";
              }
            }
            continue;

          case 'File':
            if ( $op == 'IS NULL' || $op == 'IS NOT NULL' || $op == 'IS EMPTY' || $op == 'IS NOT EMPTY' ) {
              switch ($op) {
                case 'IS EMPTY':
                  $op = 'IS NULL';
                  break;
                case 'IS NOT EMPTY':
                  $op = 'IS NOT NULL';
                  break;
              }
              $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($fieldName, $op);
              $this->_qill[$grouping][] = $field['label'] . " {$op} ";
            }
            continue;
        }
      }
    }
  }

  /**
   * function that does the actual query generation
   * basically ties all the above functions together
   *
   * @param NULL
   *
   * @return  array   array of strings
   * @access public
   */
  function query() {
    $this->select();

    $this->where();

    $whereStr = NULL;
    if (!empty($this->_where)) {
      $clauses = array();
      foreach ($this->_where as $grouping => $values) {
        if (!empty($values)) {
          $clauses[] = ' ( ' . implode(' AND ', $values) . ' ) ';
        }
      }
      if (!empty($clauses)) {
        $whereStr = ' ( ' . implode(' OR ', $clauses) . ' ) ';
      }
    }

    return array(implode(' , ', $this->_select),
      implode(' ', $this->_tables),
      $whereStr,
    );
  }

  function searchRange(&$id, &$label, $type, $fieldName, &$value, &$grouping) {
    $qill = array();

    if (isset($value['from'])) {
      $val = CRM_Utils_Type::escape($value['from'], $type);

      if ($type == 'String') {
        $this->_where[$grouping][] = "$fieldName >= '$val'";
      }
      else {
        $this->_where[$grouping][] = "$fieldName >= $val";
      }
      $qill[] = ts('greater than or equal to \'%1\'', array(1 => $value['from']));
    }

    if (isset($value['to'])) {
      $val = CRM_Utils_Type::escape($value['to'], $type);
      if ($type == 'String') {
        $this->_where[$grouping][] = "$fieldName <= '$val'";
      }
      else {
        $this->_where[$grouping][] = "$fieldName <= $val";
      }
      $qill[] = ts('less than or equal to \'%1\'', array(1 => $value['to']));
    }

    if (!empty($qill)) {
      $this->_qill[$grouping][] = $label . ' - ' . implode(' ' . ts('and') . ' ', $qill);
    }
  }
}

