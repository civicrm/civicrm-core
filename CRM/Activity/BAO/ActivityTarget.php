<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
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
    $targetArray = [];
    if (!CRM_Utils_Rule::positiveInteger($activity_id)) {
      return $targetArray;
    }

    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    $sql = "
SELECT     contact_id
FROM       civicrm_activity_contact
INNER JOIN civicrm_contact ON contact_id = civicrm_contact.id
WHERE      activity_id = %1
AND        record_type_id = $targetID
AND        civicrm_contact.is_deleted = 0
";
    $target = CRM_Core_DAO::executeQuery($sql, [
      1 => [
        $activity_id,
        'Integer',
      ],
    ]);
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
    $targetNames = [];

    if (empty($activityID)) {
      return $targetNames;
    }
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    $query = "
SELECT     contact_a.id, contact_a.sort_name
FROM       civicrm_contact contact_a
INNER JOIN civicrm_activity_contact ON civicrm_activity_contact.contact_id = contact_a.id
WHERE      civicrm_activity_contact.activity_id = %1
AND        civicrm_activity_contact.record_type_id = $targetID
AND        contact_a.is_deleted = 0
";
    $queryParam = [1 => [$activityID, 'Integer']];

    $dao = CRM_Core_DAO::executeQuery($query, $queryParam);
    while ($dao->fetch()) {
      $targetNames[$dao->id] = $dao->sort_name;
    }

    return $targetNames;
  }

}
