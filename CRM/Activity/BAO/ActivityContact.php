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
 * This class is for activity assignment functions
 *
 */
class CRM_Activity_BAO_ActivityContact extends CRM_Activity_DAO_ActivityContact {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * funtion to add activity contact
   *
   * @param array  $params      the values for this table: activity id, contact id, record type
   *
   * @return object activity_contact object
   * @access public
   *
   */
  public static function create(&$params) {
    $activityContact = new CRM_Activity_DAO_ActivityContact();

    $activityContact->copyValues($params);
    if (!$activityContact->find(TRUE)) {
      return $activityContact->save();
    }
    return $activityContact;
  }

  /**
   * function to retrieve names of contact by activity_id
   *
   * @param $activityID
   * @param $recordTypeID
   * @param bool $alsoIDs
   *
   * @internal param int $id ID of the activity
   * @internal param string $type type of interaction
   *
   * @return array
   *
   * @access public
   */
  static function getNames($activityID, $recordTypeID, $alsoIDs = FALSE) {
    $names = array();
    $ids   = array();

    if (empty($activityID)) {
      return $alsoIDs ? array($names, $ids) : $names;
    }

    $query = "
SELECT     contact_a.id, contact_a.sort_name
FROM       civicrm_contact contact_a
INNER JOIN civicrm_activity_contact ON civicrm_activity_contact.contact_id = contact_a.id
WHERE      civicrm_activity_contact.activity_id = %1
AND        civicrm_activity_contact.record_type_id = %2
AND        contact_a.is_deleted = 0
";
    $params = array(
      1 => array($activityID, 'Integer'),
      2 => array($recordTypeID, 'Integer')
    );

    $dao = CRM_Core_DAO::executeQuery($query, $params);
    while ($dao->fetch()) {
      $names[$dao->id] = $dao->sort_name;
      $ids[] = $dao->id;
    }

    return $alsoIDs ? array($names, $ids) : $names;
  }

  /**
   * function to retrieve id of target contact by activity_id
   *
   * @param $activityID
   * @param $recordTypeID
   *
   * @internal param int $id ID of the activity
   *
   * @return mixed
   *
   * @access public
   */
  static function retrieveContactIdsByActivityId($activityID, $recordTypeID) {
    $activityContact = array();
    if (!CRM_Utils_Rule::positiveInteger($activityID) ||
        !CRM_Utils_Rule::positiveInteger($recordTypeID)) {
      return $activityContact;
    }

    $sql = "                                                                                                                                                                                             SELECT     contact_id
FROM       civicrm_activity_contact
INNER JOIN civicrm_contact ON contact_id = civicrm_contact.id
WHERE      activity_id = %1
AND        record_type_id = %2
AND        civicrm_contact.is_deleted = 0
";
    $params = array(
      1 => array($activityID, 'Integer'),
      2 => array($recordTypeID, 'Integer')
    );

    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      $activityContact[] = $dao->contact_id;
    }
    return $activityContact;
  }

  /**
   * Get the links associate array  as defined by the links.ini file.
   *
   *
   * Experimental... -
   * Should look a bit like
   *       [local_col_name] => "related_tablename:related_col_name"
   *
   *
   * @return   array|null
   *           array       = if there are links defined for this table.
   *           empty array - if there is a links.ini file, but no links on this table
   *           null        - if no links.ini exists for this database (hence try auto_links).
   * @access   public
   * @see      DB_DataObject::getLinks(), DB_DataObject::getLink()
   */
  /**
   * @return array|null
   */
  function links() {
    $link = array('activity_id' => 'civicrm_activity:id');
    return $link;
  }
}
