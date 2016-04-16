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
 */

/**
 * This class is for activity assignment functions.
 */
class CRM_Activity_BAO_ActivityTarget extends CRM_Activity_DAO_ActivityContact {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Add activity target.
   *
   * @param array $params
   *
   * @return object
   *   activity type of object that is added
   */
  public static function create(&$params) {
    $target = new CRM_Activity_BAO_ActivityContact();
    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    $target->copyValues($params);
    $target->record_type_id = $targetID;
    return $target->save();
  }

  /**
   * Retrieve id of target contact by activity_id.
   *
   * @param int $activity_id
   *
   * @return mixed
   */
  public static function retrieveTargetIdsByActivityId($activity_id) {
    $targetArray = array();
    if (!CRM_Utils_Rule::positiveInteger($activity_id)) {
      return $targetArray;
    }

    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    $sql = "
SELECT     contact_id
FROM       civicrm_activity_contact
INNER JOIN civicrm_contact ON contact_id = civicrm_contact.id
WHERE      activity_id = %1
AND        record_type_id = $targetID
AND        civicrm_contact.is_deleted = 0
";
    $target = CRM_Core_DAO::executeQuery($sql, array(1 => array($activity_id, 'Integer')));
    while ($target->fetch()) {
      $targetArray[] = $target->contact_id;
    }
    return $targetArray;
  }

  /**
   * Retrieve names of target contact by activity_id.
   *
   * @param int $activityID
   *
   * @return array
   */
  public static function getTargetNames($activityID) {
    $targetNames = array();

    if (empty($activityID)) {
      return $targetNames;
    }
    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    $query = "
SELECT     contact_a.id, contact_a.sort_name
FROM       civicrm_contact contact_a
INNER JOIN civicrm_activity_contact ON civicrm_activity_contact.contact_id = contact_a.id
WHERE      civicrm_activity_contact.activity_id = %1
AND        civicrm_activity_contact.record_type_id = $targetID
AND        contact_a.is_deleted = 0
";
    $queryParam = array(1 => array($activityID, 'Integer'));

    $dao = CRM_Core_DAO::executeQuery($query, $queryParam);
    while ($dao->fetch()) {
      $targetNames[$dao->id] = $dao->sort_name;
    }

    return $targetNames;
  }

}
