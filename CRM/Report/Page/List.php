<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * Page for displaying list of Reprot templates available
 */
class CRM_Report_Page_List extends CRM_Core_Page {

  /**
   * @return array
   */
  public static function &info() {
    $sql = "
SELECT  v.id, v.value, v.label, v.description, v.component_id,
        inst.id as instance_id, ifnull( SUBSTRING(comp.name, 5), 'Contact' ) as component_name
FROM    civicrm_option_value v
INNER JOIN civicrm_option_group g
        ON (v.option_group_id = g.id AND g.name = 'report_list')
LEFT  JOIN civicrm_report_instance inst
        ON v.value = inst.report_id
LEFT  JOIN civicrm_component comp
        ON v.component_id = comp.id
WHERE     v.is_active = 1
ORDER BY  v.weight";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $rows = array();
    while ($dao->fetch()) {
      $url = 'civicrm/report/';
      $rows[$dao->component_name][$dao->value]['title'] = $dao->label;
      $rows[$dao->component_name][$dao->value]['description'] = $dao->description;
      $rows[$dao->component_name][$dao->value]['url'] = CRM_Utils_System::url('civicrm/report/' . trim($dao->value, '/'), 'reset=1');
      if ($dao->instance_id) {
        $rows[$dao->component_name][$dao->value]['instanceUrl'] = CRM_Utils_System::url('civicrm/report/instance/list',
          "reset=1&ovid={$dao->id}"
        );
      }
    }

    return $rows;
  }

  /**
   * run this page (figure out the action needed and perform it).
   *
   * @return void
   */
  function run() {
    $rows = self::info();
    $this->assign('list', $rows);

    return parent::run();
  }
}

