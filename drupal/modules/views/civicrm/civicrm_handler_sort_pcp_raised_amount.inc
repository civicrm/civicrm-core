<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
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
 * sorting handler to calcuate the total amount raised for PCPs.
 *
 */
class civicrm_handler_sort_pcp_raised_amount extends views_handler_sort {
  public function query() {
    $this->ensure_my_table();

    // @see CRM_PCP_BAO_PCP::thermoMeter for the source of this SQL
    $formula = " 
(SELECT SUM(cc.total_amount) as total
FROM {civicrm_pcp} pcp2
LEFT JOIN {civicrm_contribution_soft} cs ON ( pcp2.id = cs.pcp_id )
LEFT JOIN {civicrm_contribution} cc ON ( cs.contribution_id = cc.id)
WHERE pcp2.id = " . $this->table_alias . "." . $this->real_field . " AND cc.contribution_status_id =1 AND cc.is_test = 0)";

    $this->query->add_orderby(NULL, $formula, $this->options['order'], $this->table_alias . '_' . $this->field);
  }

}
