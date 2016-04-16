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
 * Argument handler for a year plus month (CCYYMM)
 */
class views_handler_argument_civicrm_year_month extends views_handler_argument_date {

  /**
   * Constructor implementation
   */
  function construct() {
    parent::construct();
    $this->format     = 'F Y';
    $this->arg_format = 'Ym';
    $this->formula    = views_date_sql_format($this->arg_format, "***table***.$this->real_field", "datetime",0);
  }

  /**
   * Provide a link to the next level of the view
   */
  function summary_name($data) {
    $timestamp = $data->{$this->name_alias};
    $timestamp = strtotime($timestamp . " UTC");
    if ($timestamp !== FALSE) {
      return format_date($timestamp, 'custom', $this->format, 'UTC');
    }
  }

  /**
   * Provide a link to the next level of the view
   */
  function title() {
    $timestamp = strtotime($this->argument . " UTC");
    if ($timestamp !== FALSE) {
      return format_date($timestamp, 'custom', $this->format, 'UTC');
    }
  }
}

