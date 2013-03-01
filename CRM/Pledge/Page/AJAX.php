<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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
class CRM_Pledge_Page_AJAX {

  /**
   * Function for building Pledge Name combo box
   */
  function pledgeName(&$config) {

    $getRecords = FALSE;
    if (isset($_GET['name']) && $_GET['name']) {
      $name        = CRM_Utils_Type::escape($_GET['name'], 'String');
      $name        = str_replace('*', '%', $name);
      $whereClause = "p.creator_pledge_desc LIKE '%$name%' ";
      $getRecords  = TRUE;
    }

    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
      $pledgeId    = CRM_Utils_Type::escape($_GET['id'], 'Integer');
      $whereClause = "p.id = {$pledgeId} ";
      $getRecords  = TRUE;
    }

    if ($getRecords) {
      $query = "
SELECT p.creator_pledge_desc, p.id
FROM civicrm_pb_pledge p
WHERE {$whereClause}
";
      $dao = CRM_Core_DAO::executeQuery($query);
      $elements = array();
      while ($dao->fetch()) {
        $elements[] = array(
          'name' => $dao->creator_pledge_desc,
          'value' => $dao->id,
        );
      }
    }

    if (empty($elements)) {
      $name = $_GET['name'];
      if (!$name && isset($_GET['id'])) {
        $name = $_GET['id'];
      }
      $elements[] = array('name' => trim($name, '*'),
        'value' => trim($name, '*'),
      );
    }

    echo CRM_Utils_JSON::encode($elements, 'value');
    CRM_Utils_System::civiExit();
  }
}

