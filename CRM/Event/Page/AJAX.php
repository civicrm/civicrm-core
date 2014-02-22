<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class contains all the function that are called using AJAX
 */
class CRM_Event_Page_AJAX {

  /**
   * Function for building EventFee combo box
   */
  function eventFee() {
    $name = trim(CRM_Utils_Type::escape($_GET['s'], 'String'));

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
      $results[$dao->id] = $dao->label;
    }
    CRM_Core_Page_AJAX::autocompleteResults($results);
  }

  function eventList() {
    $listparams = CRM_Utils_Array::value('listall', $_REQUEST, 1);
    $events = CRM_Event_BAO_Event::getEvents($listparams);

    $elements = array(array('name' => ts('- select -'),
        'value' => '',
      ));
    foreach ($events as $id => $name) {
      $elements[] = array(
        'name' => $name,
        'value' => $id,
      );
    }

    echo json_encode($elements);
    CRM_Utils_System::civiExit();
  }

  /**
   * Function to get default participant role
   */
  function participantRole() {
    $eventID = $_GET['eventId'];

    $defaultRoleId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event',
      $eventID,
      'default_role_id',
      'id'
    );
    $participantRole = array('role' => $defaultRoleId);
    echo json_encode($participantRole);
    CRM_Utils_System::civiExit();
  }
}

