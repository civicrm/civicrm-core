<?php
// $Id$

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
class CRM_Report_BAO_Instance extends CRM_Report_DAO_Instance {

  /**
   * Delete the instance of the Report
   *
   * @return $results no of deleted Instance on success, false otherwise
   * @access public
   *
   */
  function delete($id = NULL) {
    $dao = new CRM_Report_DAO_Instance();
    $dao->id = $id;
    return $dao->delete();
  }

  static function retrieve($params, &$defaults) {
    $instance = new CRM_Report_DAO_Instance();
    $instance->copyValues($params);

    if ($instance->find(TRUE)) {
      CRM_Core_DAO::storeValues($instance, $defaults);
      $instance->free();
      return $instance;
    }
    return NULL;
  }
}

