<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */

/**
 * This class contains all the function that are called using AJAX
 */
class CRM_Event_Page_AJAX {

  /**
   * Building EventFee combo box.
   * FIXME: This ajax callback could be eliminated in favor of an entityRef field but the priceFieldValue api doesn't currently support filtering on entity_table
   */
  public function eventFee() {
    $name = trim(CRM_Utils_Type::escape($_GET['term'], 'String'));

    if (!$name) {
      $name = '%';
    }

    $whereClause = "cv.label LIKE '$name%' ";

    $query = "SELECT DISTINCT (
cv.label
), cv.id
FROM civicrm_price_field_value cv
LEFT JOIN civicrm_price_field cf ON cv.price_field_id = cf.id
LEFT JOIN civicrm_price_set_entity ce ON ce.price_set_id = cf.price_set_id
WHERE ce.entity_table = 'civicrm_event' AND {$whereClause}
GROUP BY cv.label";
    $dao = CRM_Core_DAO::executeQuery($query);
    $results = array();
    while ($dao->fetch()) {
      $results[] = array('id' => $dao->id, 'text' => $dao->label);
    }
    CRM_Utils_JSON::output($results);
  }

}
