<?php
/*
   +--------------------------------------------------------------------+
   | CiviCRM version 4.7                                                |
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

   **
   * @file Provides Views integration for CiviCRM Multi-Select Country custom fields
   *
   * @author DaveJ
   * based on civicrm_handler_filter_custom_option and civicrm_handler_filter_country
   */
class civicrm_handler_filter_country_multi extends views_handler_filter_in_operator {
  function construct() {
    parent::construct();
    if (!civicrm_initialize()) {
      return;
    }
  }

  function get_value_options() {
    if (!isset($this->value_options)) {
      $countries = CRM_Core_PseudoConstant::country();
      $options = array();
      if (is_array($countries)) {
        foreach ($countries as $id => $name) {
          $options[$id] = $name;
        }
      }
      $this->value_options = $options;
    }
  }

  function operators() {
    $operators = parent::operators();
    $operators += array(
      'all' => array(
        'title' => t('Is all of'),
        'short' => t('all'),
        'method' => 'op_simple',
        'values' => 1,
        ),
      );

    return $operators;
  }

  function op_simple() {
    if (empty($this->value)) {
      return;
    }

    $this->ensure_my_table();

    // negated operator uses AND, positive uses OR
    $op = $glue = null;
    switch ($this->operator) {
      case 'in':
        $op = ' = ';
        $glue = ' OR ';
        break;
      case 'not in':
        $op = ' != ';
        $glue = ' AND ';
        break;
      case 'all' :
        $clause = "$this->table_alias.$this->real_field IN (" . implode(',', $this->value) . ")";
        break;
    }

    if ($op) {
      foreach ($this->value as $value) {
        $clauses[] = "$this->table_alias.$this->real_field " . $op . $value;
      }
      $clause = implode($glue, $clauses);
    }

    $this->query->add_where_expression($this->options['group'], $clause);
  }
}

