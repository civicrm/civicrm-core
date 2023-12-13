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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_BAO_CustomQuery {
  const PREFIX = 'custom_value_';

  /**
   * The set of custom field ids.
   *
   * @var array
   */
  protected $_ids;

  /**
   * The select clause.
   *
   * @var array
   */
  public $_select;

  /**
   * The name of the elements that are in the select clause.
   * used to extract the values
   *
   * @var array
   */
  public $_element;

  /**
   * The tables involved in the query.
   *
   * @var array
   */
  public $_tables;
  public $_whereTables;

  /**
   * The where clause.
   *
   * @var array
   */
  public $_where;

  /**
   * The english language version of the query.
   *
   * @var array
   */
  public $_qill;

  /**
   * The custom fields information.
   *
   * @var array
   */
  public $_fields;

  /**
   * @return array
   */
  public function getFields() {
    return $this->_fields;
  }

  /**
   * Searching for contacts?
   *
   * @var bool
   */
  protected $_contactSearch;

  protected $_locationSpecificCustomFields;

  /**
   * This stores custom data group types and tables that it extends.
   *
   * @var array
   */
  public static $extendsMap = [
    'Contact' => 'civicrm_contact',
    'Individual' => 'civicrm_contact',
    'Household' => 'civicrm_contact',
    'Organization' => 'civicrm_contact',
    'Contribution' => 'civicrm_contribution',
    'ContributionRecur' => 'civicrm_contribution_recur',
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
    'Campaign' => 'civicrm_campaign',
    'Survey' => 'civicrm_survey',
  ];

  /**
   * Class constructor.
   *
   * Takes in a set of custom field ids andsets up the data structures to
   * generate a query
   *
   * @param array $ids
   *   The set of custom field ids.
   *
   * @param bool $contactSearch
   * @param array $locationSpecificFields
   */
  public function __construct($ids, $contactSearch = FALSE, $locationSpecificFields = []) {
    $this->_ids = $ids;
    $this->_locationSpecificCustomFields = $locationSpecificFields;

    $this->_select = [];
    $this->_element = [];
    $this->_tables = [];
    $this->_whereTables = [];
    $this->_where = [];
    $this->_qill = [];

    $this->_contactSearch = $contactSearch;
    $this->_fields = CRM_Core_BAO_CustomField::getFields('ANY', FALSE, FALSE, NULL, NULL, FALSE, FALSE, FALSE);
  }

  /**
   * Generate the select clause and the associated tables.
   */
  public function select() {
    if (empty($this->_fields)) {
      return;
    }

    foreach (array_keys($this->_ids) as $id) {
      // Ignore any custom field ids within the ids array that are not present in the fields array.
      if (empty($this->_fields[$id])) {
        continue;
      }
      $field = $this->_fields[$id];

      if ($this->_contactSearch && $field['search_table'] === 'contact_a') {
        CRM_Contact_BAO_Query::$_openedPanes[ts('Custom Fields')] = TRUE;
      }

      $name = $field['table_name'];
      $fieldName = 'custom_' . $field['id'];
      $this->_select["{$name}_id"] = "{$name}.id as {$name}_id";
      $this->_element["{$name}_id"] = 1;
      $this->_select[$fieldName] = "{$field['table_name']}.{$field['column_name']} as $fieldName";
      $this->_element[$fieldName] = 1;

      $this->joinCustomTableForField($field);
    }
  }

  /**
   * Generate the where clause and also the english language equivalent.
   *
   * @throws \CRM_Core_Exception
   */
  public function where() {
    foreach ($this->_ids as $id => $values) {

      // Fixed for Issue CRM 607
      if (!isset($this->_fields[$id]) || !$values) {
        continue;
      }

      foreach ($values as $tuple) {
        [$name, $op, $value, $grouping, $wildcard] = $tuple;

        $field = $this->_fields[$id];

        $fieldName = "{$field['table_name']}.{$field['column_name']}";

        $isSerialized = CRM_Core_BAO_CustomField::isSerialized($field);

        // fix $value here to escape sql injection attacks
        $qillValue = NULL;
        if (!is_array($value)) {
          $escapedValue = CRM_Core_DAO::escapeString(trim($value));
          $qillValue = CRM_Core_BAO_CustomField::displayValue($escapedValue, $id);
        }
        elseif (count($value) && in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
          $op = key($value);
          $qillValue = strstr($op, 'NULL') ? NULL : CRM_Core_BAO_CustomField::displayValue($value[$op], $id);
        }
        else {
          $op = strstr($op, 'IN') ? $op : 'IN';
          $qillValue = CRM_Core_BAO_CustomField::displayValue($value, $id);
        }

        $qillOp = CRM_Core_SelectValues::getSearchBuilderOperators()[$op] ?? $op;

        // Ensure the table is joined in (eg if in where but not select).
        $this->joinCustomTableForField($field);
        switch ($field['data_type']) {
          case 'String':
          case 'StateProvince':
          case 'Country':
          case 'ContactReference':

            if ($field['is_search_range'] && is_array($value)) {
              //didn't found any field under any of these three data-types as searchable by range
            }
            else {
              // fix $value here to escape sql injection attacks
              if (!is_array($value)) {
                if ($field['data_type'] === 'String') {
                  $value = CRM_Utils_Type::escape($value, 'String');
                }
                elseif ($value) {
                  $value = CRM_Utils_Type::escape($value, 'Integer');
                }
                $value = str_replace(['[', ']', ','], ['\[', '\]', '[:comma:]'], $value);
                $value = str_replace('|', '[:separator:]', $value);
              }
              elseif ($isSerialized) {
                if (in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
                  $op = key($value);
                  $value = $value[$op];
                }
                // CRM-19006: escape characters like comma, | before building regex pattern
                $value = (array) $value;
                foreach ($value as $key => $val) {
                  $value[$key] = str_replace(['[', ']', ','], ['\[', '\]', '[:comma:]'], $val);
                  $value[$key] = str_replace('|', '[:separator:]', $value[$key]);
                  if ($field['data_type'] === 'String') {
                    $value[$key] = CRM_Utils_Type::escape($value[$key], 'String');
                  }
                  elseif ($value) {
                    $value[$key] = CRM_Utils_Type::escape($value[$key], 'Integer');
                  }
                }
                $value = implode(',', $value);
              }

              // CRM-14563,CRM-16575 : Special handling of multi-select custom fields
              if ($isSerialized && !CRM_Utils_System::isNull($value) && !strstr($op, 'NULL') && !strstr($op, 'LIKE')) {
                $sp = CRM_Core_DAO::VALUE_SEPARATOR;
                $value = str_replace(",", "$sp|$sp", $value);
                $value = str_replace(['[:comma:]', '(', ')'], [',', '[(]', '[)]'], $value);

                $op = (str_contains($op, '!') || str_contains($op, 'NOT')) ? 'NOT RLIKE' : 'RLIKE';
                $value = $sp . $value . $sp;
                if (!$wildcard) {
                  foreach (explode("|", $value) as $val) {
                    $val = str_replace('[:separator:]', '\|', $val);
                    $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($fieldName, $op, $val, 'String');
                  }
                }
                else {
                  $value = str_replace('[:separator:]', '\|', $value);
                  $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($fieldName, $op, $value, 'String');
                }
              }
              else {
                //FIX for custom data query fired against no value(NULL/NOT NULL)
                $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($fieldName, $op, $value, 'String', TRUE);
              }
              $this->_qill[$grouping][] = $field['label'] . " $qillOp $qillValue";
            }
            break;

          case 'Int':
            $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($fieldName, $op, $value, 'Integer');
            $this->_qill[$grouping][] = ts('%1 %2 %3', [1 => $field['label'], 2 => $qillOp, 3 => $qillValue]);
            break;

          case 'Boolean':
            if (!is_array($value)) {
              if (mb_strtolower($value) === 'yes' || mb_strtolower($value) === mb_strtolower(ts('Yes'))) {
                $value = 1;
              }
              else {
                $value = (int) $value;
              }
              $value = ($value == 1) ? 1 : 0;
              $qillValue = $value ? 'Yes' : 'No';
            }
            $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($fieldName, $op, $value, 'Integer');
            $this->_qill[$grouping][] = ts("%1 %2 %3", [1 => $field['label'], 2 => $qillOp, 3 => $qillValue]);
            break;

          case 'Link':
          case 'Memo':
            $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($fieldName, $op, $value, 'String');
            $this->_qill[$grouping][] = ts("%1 %2 %3", [1 => $field['label'], 2 => $qillOp, 3 => $qillValue]);
            break;

          case 'Money':
            $value = CRM_Utils_Array::value($op, (array) $value, $value);
            if (is_array($value)) {
              foreach ($value as $key => $val) {
                // @todo - this clean money should be in the form layer - it's highly likely to be doing more harm than good here
                // Note the only place I can find that this code is reached by is searching a custom money field in advanced search.
                // with euro style comma separators this doesn't work - with or without this cleanMoney.
                // So this should be removed but is not increasing the brokeness IMHO
                $value[$op][$key] = CRM_Utils_Rule::cleanMoney($value[$key]);
              }
            }
            else {
              // @todo - this clean money should be in the form layer - it's highly likely to be doing more harm than good here
              // comments per above apply. cleanMoney
              $value = CRM_Utils_Rule::cleanMoney($value);
            }

          case 'Float':
            $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($fieldName, $op, $value, 'Float');
            $this->_qill[$grouping][] = ts("%1 %2 %3", [1 => $field['label'], 2 => $qillOp, 3 => $qillValue]);
            break;

          case 'Date':
            if (substr($name, -9, 9) !== '_relative'
              && substr($name, -4, 4) !== '_low'
              && substr($name, -5, 5) !== '_high') {
              // Relative dates are handled in the buildRelativeDateQuery function.
              $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($fieldName, $op, $value, 'Date');
              [$qillOp, $qillVal] = CRM_Contact_BAO_Query::buildQillForFieldValue(NULL, $field['label'], $value, $op, [], CRM_Utils_Type::T_DATE);
              $this->_qill[$grouping][] = "{$field['label']} $qillOp '$qillVal'";
            }
            break;

          case 'File':
            if ($op == 'IS NULL' || $op == 'IS NOT NULL' || $op == 'IS EMPTY' || $op == 'IS NOT EMPTY') {
              switch ($op) {
                case 'IS EMPTY':
                  $op = 'IS NULL';
                  break;

                case 'IS NOT EMPTY':
                  $op = 'IS NOT NULL';
                  break;
              }
              $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause($fieldName, $op);
              $this->_qill[$grouping][] = $field['label'] . " {$qillOp} ";
            }
            break;
        }
      }
    }
  }

  /**
   * Function that does the actual query generation.
   * basically ties all the above functions together
   *
   * @return array
   *   array of strings
   */
  public function query() {
    $this->select();

    $this->where();

    $whereStr = NULL;
    if (!empty($this->_where)) {
      $clauses = [];
      foreach ($this->_where as $grouping => $values) {
        if (!empty($values)) {
          $clauses[] = ' ( ' . implode(' AND ', $values) . ' ) ';
        }
      }
      if (!empty($clauses)) {
        $whereStr = ' ( ' . implode(' OR ', $clauses) . ' ) ';
      }
    }

    return [
      implode(' , ', $this->_select),
      implode(' ', $this->_tables),
      $whereStr,
    ];
  }

  /**
   * Join the custom table for the field in (if not already in the query).
   *
   * @param array $field
   */
  protected function joinCustomTableForField($field) {
    $name = $field['table_name'];
    $join = "\nLEFT JOIN $name ON $name.entity_id = {$field['search_table']}.id";
    $this->_tables[$name] ??= $join;
    $this->_whereTables[$name] ??= $join;

    $joinTable = $field['search_table'];
    if ($joinTable) {
      $joinClause = 1;
      $joinTableAlias = $joinTable;
      // Set location-specific query
      if (isset($this->_locationSpecificCustomFields[$field['id']])) {
        [$locationType, $locationTypeId] = $this->_locationSpecificCustomFields[$field['id']];
        $joinTableAlias = "$locationType-address";
        $joinClause = "\nLEFT JOIN $joinTable `$locationType-address` ON (`$locationType-address`.contact_id = contact_a.id AND `$locationType-address`.location_type_id = $locationTypeId)";
      }
      $this->_tables[$name] = "\nLEFT JOIN $name ON $name.entity_id = `$joinTableAlias`.id";
      if (!empty($this->_ids[$field['id']])) {
        $this->_whereTables[$name] = $this->_tables[$name];
      }
      if ($joinTable !== 'contact_a') {
        $this->_whereTables[$joinTableAlias] = $this->_tables[$joinTableAlias] = $joinClause;
      }
    }
  }

}
