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
*/

/**
 * sort handler to properly pass the field type for date fields.
 *
 */
class civicrm_handler_sort_date extends views_handler_sort_date {

  /**
   * Called to add the sort to a query.
   * Overridden to pass the field type
   */
  function query() {
    $this->ensure_my_table();
    switch ($this->options['granularity']) {
      case 'second':
      default:
        $this->query->add_orderby($this->table_alias, $this->real_field, $this->options['order']);
        return;

      case 'minute':
        $formula = views_date_sql_format('YmdHi', "$this->table_alias.$this->real_field", 'datetime');
        break;

      case 'hour':
        $formula = views_date_sql_format('YmdH', "$this->table_alias.$this->real_field", 'datetime');
        break;

      case 'day':
        $formula = views_date_sql_format('Ymd', "$this->table_alias.$this->real_field", 'datetime');
        break;

      case 'month':
        $formula = views_date_sql_format('Ym', "$this->table_alias.$this->real_field", 'datetime');
        break;

      case 'year':
        $formula = views_date_sql_format('Y', "$this->table_alias.$this->real_field", 'datetime');
        break;
    }
    // Add the field.
    $this->query->add_orderby(NULL, $formula, $this->options['order'], $this->table_alias . '_' . $this->field . '_' . $this->options['granularity']);
  }
}

