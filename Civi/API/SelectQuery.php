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
namespace Civi\API;

/**
 * Query builder for civicrm_api_basic_get.
 *
 * Fetches an entity based on specified params for the "where" clause,
 * return properties for the "select" clause,
 * as well as limit and order.
 *
 * Automatically joins on custom fields to return or filter by them.
 *
 * Supports an additional sql fragment which the calling api can provide.
 *
 * @package Civi\API
 */
class SelectQuery {

  /**
   * @var string
   */
  protected $entity;
  /**
   * @var array
   */
  protected $params;
  /**
   * @var array
   */
  protected $options;
  /**
   * @var bool
   */
  protected $isFillUniqueFields;
  /**
   * @var \CRM_Utils_SQL_Select
   */
  protected $query;
  /**
   * @var array
   */
  protected $apiFieldSpec;
  /**
   * @var array
   */
  protected $entityFieldNames;
  /**
   * @var array
   */
  protected $uniqueAliases = array();

  /**
   * @param string $dao_name
   *   Name of DAO
   * @param array $params
   *   As passed into api get function.
   * @param bool $isFillUniqueFields
   *   Do we need to ensure unique fields continue to be populated for this api? (backward compatibility).
   */
  public function __construct($dao_name, $params, $isFillUniqueFields) {
    /* @var \CRM_Core_DAO $dao */
    $dao = new $dao_name();
    $this->entity = _civicrm_api_get_entity_name_from_dao($dao);
    $this->params = $params;
    $this->isFillUniqueFields = $isFillUniqueFields;
    $this->options = _civicrm_api3_get_options_from_params($this->params);


    $this->entityFieldNames = _civicrm_api3_field_names(_civicrm_api3_build_fields_array($dao));
    $this->apiFieldSpec = \CRM_Utils_Array::value('values', civicrm_api3($this->entity, 'getfields', array('action' => 'get')));
    foreach ($this->apiFieldSpec as $getFieldKey => $getFieldSpec) {
      if (in_array($getFieldSpec['name'], $this->entityFieldNames)) {
        $this->uniqueAliases[$getFieldKey] = $getFieldSpec['name'];
        $this->uniqueAliases[$getFieldSpec['name']] = $getFieldSpec['name'];
        foreach (\CRM_Utils_Array::value('api.aliases', $getFieldSpec, array()) as $alias) {
          $this->uniqueAliases[$alias] = $getFieldSpec['name'];
        }
      }
    }

    // Unset $this->params['options'] if they are api options (not options as a fieldname).
    if (isset($this->params['options']) && !in_array('options', $this->uniqueAliases)) {
      unset($this->params['options']);
    }

    $this->query = \CRM_Utils_SQL_Select::from($dao->tableName() . " a");
    $dao->free();
  }

  public function run() {

    // $select_fields maps column names to the field names of the result
    // values.
    $select_fields = array();

    // array with elements array('column', 'operator', 'value');
    $where_clauses = array();

    // Tables we need to join with to retrieve the custom values.
    $custom_value_tables = array();

    // ID's of custom fields that refer to a contact.
    $contact_reference_field_ids = array();

    // populate $select_fields
    $return_all_fields = (empty($this->options['return']) || !is_array($this->options['return']));
    $return = $return_all_fields ? array_fill_keys($this->entityFieldNames, 1) : $this->options['return'];

    // default fields
    foreach ($return as $field_name => $include) {
      if ($include && !empty($this->uniqueAliases[$field_name])) {
        // 'a.' is an alias for the entity table.
        $select_fields["a.{$this->uniqueAliases[$field_name]}"] = $this->uniqueAliases[$field_name];
      }
    }

    // process custom fields IF the params contain the word "custom"
    if ($return_all_fields || strpos(json_encode($this->params), 'custom')) {
      $custom_fields = _civicrm_api3_custom_fields_for_entity($this->entity);
      foreach ($custom_fields as $cf_id => $custom_field) {
        $field_name = "custom_$cf_id";
        if ($return_all_fields || !empty($this->options['return'][$field_name])
          ||
          // This is a tested format so we support it.
          !empty($this->options['return']['custom'])
        ) {
          $table_name = $custom_field["table_name"];
          $column_name = $custom_field["column_name"];
          // remember that we will need to join the correct table.
          if (!in_array($table_name, $custom_value_tables)) {
            $custom_value_tables[] = $table_name;
          }
          if ($custom_field["data_type"] != "ContactReference") {
            // 'ordinary' custom field. We will select the value as custom_XX.
            $select_fields["$table_name.$column_name"] = $field_name;
          }
          else {
            // contact reference custom field. The ID will be stored in
            // custom_XX_id. custom_XX will contain the sort name of the
            // contact.
            $contact_reference_field_ids[] = $cf_id;
            $select_fields["$table_name.$column_name"] = $field_name . "_id";
            // We will call the contact table for the join c_XX.
            $select_fields["c_$cf_id.sort_name"] = $field_name;
          }
        }
      }
    }
    if (!in_array("a.id", $select_fields)) {
      // Always select the ID.
      $select_fields["a.id"] = "id";
    }

    // populate $where_clauses
    foreach ($this->params as $key => $value) {
      $table_name = NULL;
      $column_name = NULL;

      if (substr($key, 0, 7) == 'filter.') {
        // Legacy support for old filter syntax per the test contract.
        // (Convert the style to the later one & then deal with them).
        $filterArray = explode('.', $key);
        $value = array($filterArray[1] => $value);
        $key = 'filters';
      }

      // Legacy support for 'filter's construct.
      if ($key == 'filters') {
        foreach ($value as $filterKey => $filterValue) {
          if (substr($filterKey, -4, 4) == 'high') {
            $key = substr($filterKey, 0, -5);
            $value = array('<=' => $filterValue);
          }

          if (substr($filterKey, -3, 3) == 'low') {
            $key = substr($filterKey, 0, -4);
            $value = array('>=' => $filterValue);
          }

          if ($filterKey == 'is_current' || $filterKey == 'isCurrent') {
            // Is current is almost worth creating as a 'sql filter' in the DAO function since several entities have the
            // concept.
            $todayStart = date('Ymd000000', strtotime('now'));
            $todayEnd = date('Ymd235959', strtotime('now'));
            $this->query->where(array("(a.start_date <= '$todayStart' OR a.start_date IS NULL) AND (a.end_date >= '$todayEnd' OR
          a.end_date IS NULL)
          AND a.is_active = 1
        "));
          }
        }
      }

      if (isset($this->apiFieldSpec[$key])) {
        $key = $this->apiFieldSpec[$key]['name'];
      }
      if ($key == _civicrm_api_get_entity_name_from_camel($this->entity) . '_id') {
        // The test contract enforces support of (eg) mailing_group_id if the entity is MailingGroup.
        $key = 'id';
      }
      if (in_array($key, $this->entityFieldNames)) {
        $table_name = 'a';
        $column_name = $key;
      }
      elseif (($cf_id = \CRM_Core_BAO_CustomField::getKeyID($key)) != FALSE) {
        $table_name = $custom_fields[$cf_id]["table_name"];
        $column_name = $custom_fields[$cf_id]["column_name"];

        if (!in_array($table_name, $custom_value_tables)) {
          $custom_value_tables[] = $table_name;
        }
      }
      // I don't know why I had to specifically exclude 0 as a key - wouldn't the others have caught it?
      // We normally silently ignore null values passed in - if people want IS_NULL they can use acceptedSqlOperator syntax.
      if ((!$table_name) || empty($key) || is_null($value)) {
        // No valid filter field. This might be a chained call or something.
        // Just ignore this for the $where_clause.
        continue;
      }
      if (!is_array($value)) {
        $this->query->where(array("{$table_name}.{$column_name} = @value"), array(
          "@value" => $value,
        ));
      }
      else {
        // We expect only one element in the array, of the form
        // "operator" => "rhs".
        $operator = \CRM_Utils_Array::first(array_keys($value));
        if (!in_array($operator, \CRM_Core_DAO::acceptedSQLOperators())) {
          $this->query->where(array(
            "{$table_name}.{$column_name} = @value"), array("@value" => $value)
          );
        }
        else {
          $this->query->where(\CRM_Core_DAO::createSQLFilter("{$table_name}.{$column_name}", $value));
        }
      }
    }

    $i = 0;
    if (!$this->options['is_count']) {
      foreach ($select_fields as $column => $alias) {
        ++$i;
        $this->query = $this->query->select("!column_$i as !alias_$i", array(
          "!column_$i" => $column,
          "!alias_$i" => $alias,
        ));
      }
    }
    else {
      $this->query->select("count(*) as c");
    }

    // join with custom value tables
    foreach ($custom_value_tables as $table_name) {
      ++$i;
      $this->query = $this->query->join(
        "!table_name_$i",
        "LEFT OUTER JOIN !table_name_$i ON !table_name_$i.entity_id = a.id",
        array("!table_name_$i" => $table_name)
      );
    }

    // join with contact for contact reference fields
    foreach ($contact_reference_field_ids as $field_id) {
      ++$i;
      $this->query = $this->query->join(
        "!contact_table_name$i",
        "LEFT OUTER JOIN civicrm_contact !contact_table_name_$i ON !contact_table_name_$i.id = !values_table_name_$i.!column_name_$i",
        array(
          "!contact_table_name_$i" => "c_$field_id",
          "!values_table_name_$i" => $custom_fields[$field_id]["table_name"],
          "!column_name_$i" => $custom_fields[$field_id]["column_name"],
        ));
    };

    foreach ($where_clauses as $clause) {
      ++$i;
      if (substr($clause[1], -4) == "NULL") {
        $this->query->where("!columnName_$i !nullThing_$i", array(
          "!columnName_$i" => $clause[0],
          "!nullThing_$i" => $clause[1],
        ));
      }
      else {
        $this->query->where("!columnName_$i !operator_$i @value_$i", array(
          "!columnName_$i" => $clause[0],
          "!operator_$i" => $clause[1],
          "@value_$i" => $clause[2],
        ));
      }
    };

    // order by
    if (!empty($this->options['sort'])) {
      $sort_fields = array();
      foreach (explode(',', $this->options['sort']) as $sort_option) {
        $words = preg_split("/[\s]+/", $sort_option);
        if (count($words) > 0 && in_array($words[0], array_values($select_fields))) {
          $tmp = $words[0];
          if (!empty($words[1]) && strtoupper($words[1]) == 'DESC') {
            $tmp .= " DESC";
          }
          $sort_fields[] = $tmp;
        }
      }
      if (count($sort_fields) > 0) {
        $this->query->orderBy(implode(",", $sort_fields));
      }
    }

    // limit
    if (!empty($this->options['limit']) || !empty($this->options['offset'])) {
      $this->query->limit($this->options['limit'], $this->options['offset']);
    }

    $result_entities = array();
    $result_dao = \CRM_Core_DAO::executeQuery($this->query->toSQL());

    while ($result_dao->fetch()) {
      if ($this->options['is_count']) {
        $result_dao->free();
        return (int) $result_dao->c;
      }
      $result_entities[$result_dao->id] = array();
      foreach ($select_fields as $column => $alias) {
        if (property_exists($result_dao, $alias) && $result_dao->$alias != NULL) {
          $result_entities[$result_dao->id][$alias] = $result_dao->$alias;
        }
        // Backward compatibility on fields names.
        if ($this->isFillUniqueFields && !empty($this->apiFieldSpec['values'][$column]['uniqueName'])) {
          $result_entities[$result_dao->id][$this->apiFieldSpec['values'][$column]['uniqueName']] = $result_dao->$alias;
        }
        foreach ($this->apiFieldSpec as $returnName => $spec) {
          if (empty($result_entities[$result_dao->id][$returnName]) && !empty($result_entities[$result_dao->id][$spec['name']])) {
            $result_entities[$result_dao->id][$returnName] = $result_entities[$result_dao->id][$spec['name']];
          }
        }
      };
    }
    $result_dao->free();
    return $result_entities;
  }

  /**
   * @param \CRM_Utils_SQL_Select $sqlFragment
   * @return $this
   */
  public function merge($sqlFragment) {
    $this->query->merge($sqlFragment);
    return $this;
  }
}
