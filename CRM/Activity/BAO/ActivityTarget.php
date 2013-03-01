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
 * This class is for activity assignment functions
 *
 */
class CRM_Activity_BAO_ActivityTarget extends CRM_Activity_DAO_ActivityTarget {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * funtion to add activity target
   *
   * @param array  $activity_id           (reference ) an assoc array of name/value pairs
   * @param array  $target_contact_id     (reference ) the array that holds all the db ids
   *
   * @return object activity type of object that is added
   * @access public
   *
   */
  public static function create(&$params) {
    $target = new CRM_Activity_BAO_ActivityTarget();

    $target->copyValues($params);
    return $target->save();
  }

  /**
   * function to retrieve id of target contact by activity_id
   *
   * @param int    $id  ID of the activity
   *
   * @return mixed
   *
   * @access public
   *
   */
  static function retrieveTargetIdsByActivityId($activity_id) {
    $targetArray = array();
    if (!CRM_Utils_Rule::positiveInteger($activity_id)) {
      return $targetArray;
    }

    $sql = '
            SELECT target_contact_id
            FROM civicrm_activity_target
            JOIN civicrm_contact ON target_contact_id = civicrm_contact.id
            WHERE activity_id = %1 AND civicrm_contact.is_deleted = 0
        ';
    $target = CRM_Core_DAO::executeQuery($sql, array(1 => array($activity_id, 'Integer')));
    while ($target->fetch()) {
      $targetArray[] = $target->target_contact_id;
    }
    return $targetArray;
  }

  /**
   * function to retrieve names of target contact by activity_id
   *
   * @param int    $id  ID of the activity
   *
   * @return array
   *
   * @access public
   *
   */
  static function getTargetNames($activityID) {
    $targetNames = array();

    if (empty($activityID)) {
      return $targetNames;
    }

    $query = "SELECT contact_a.id, contact_a.sort_name 
                  FROM civicrm_contact contact_a 
                  LEFT JOIN civicrm_activity_target 
                         ON civicrm_activity_target.target_contact_id = contact_a.id
                  WHERE civicrm_activity_target.activity_id = %1 AND contact_a.is_deleted = 0";
    $queryParam = array(1 => array($activityID, 'Integer'));

    $dao = CRM_Core_DAO::executeQuery($query, $queryParam);
    while ($dao->fetch()) {
      $targetNames[$dao->id] = $dao->sort_name;
    }

    return $targetNames;
  }
}

