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
   * @file Provides Views integration for custom CiviCRM custom field option groups
   *
   * @author Matt Chapman <Matt@NinjitsuWeb.com>
   */
class civicrm_handler_filter_custom_option extends views_handler_filter_in_operator {
  function construct() {
    parent::construct();
    if (!civicrm_initialize()) {
      return;
    }
  }

  function get_value_options() {
    if (!isset($this->value_options)) {
      // extract the field id from the name
      if (preg_match('/_(\d+)$/', $this->real_field, $match)) {
        require_once 'CRM/Core/BAO/CustomOption.php';
        $options = CRM_Core_BAO_CustomOption::getCustomOption($match[1]);
      }
      if (is_array($options)) {
        foreach ($options as $id => $opt) {
          $this->value_options[$opt['value']] = strip_tags($opt['label']);
        }
      }
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

    $sep = CRM_Core_DAO::VALUE_SEPARATOR;

    // negated operator uses AND, positive uses OR
    $op = ($this->operator == 'in' || $this->operator == 'all') ? 'LIKE' : 'NOT LIKE';
    $glue = ($this->operator == 'in') ? 'OR ' : 'AND ';
    foreach ($this->value as $value) {
      $clauses[] = "$this->table_alias.$this->real_field " . $op . " '%" . $sep . $value . $sep . "%' ";
    }
    $clause = implode($glue, $clauses);
    $this->query->add_where_expression($this->options['group'], $clause);
  }
}

