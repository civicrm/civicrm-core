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
 * Field handler to provide amount raised by given Personal Campaign Page
 *
 * @ingroup civicrm_field_handlers
 */
class civicrm_handler_field_pcp_raised_amount extends views_handler_field {
  function construct() {
    parent::construct();
    if (!civicrm_initialize()) {
      return;
    }
    require_once 'CRM/PCP/BAO/PCP.php';
    require_once 'CRM/Utils/Money.php';
  }

  function render($values) {
    // retrieve the value of the "real field"
    $pcp_id = $values->{$this->field_alias};
    // get the total amount raised
    $amount = CRM_PCP_BAO_PCP::thermoMeter($pcp_id);
    if (!$amount) {
      // supply a numeric string if function returns NULL or undesirable result
      $amount = '0';
    }
    return CRM_Utils_Money::format($amount);
  }
}


