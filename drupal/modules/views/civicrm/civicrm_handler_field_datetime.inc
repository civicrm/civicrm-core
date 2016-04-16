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


/*
 * Unchanged anonymous code contribution. No claim.
 *
 * civicrm_handler_field_datetime.inc
 * Converts and displays text to something views friendly.
 *
 */
class civicrm_handler_field_datetime extends views_handler_field_date {

  /*
     * Convert the DATETIME from the database into unixtime then allow
     * views_handler_field_date to render as usual.
     * Also trick php into thinking the time is in the same timezone, no
     * matter the default timezone
     */
  function render($values) {
    $value = $values->{$this->field_alias};

    if (is_string($value) && strpos($value, "-")) {
      $value = strtotime($value);

      if ($value) {
        $values->{$this->field_alias} = $value;
      }
    }

    return parent::render($values);
  }
}



