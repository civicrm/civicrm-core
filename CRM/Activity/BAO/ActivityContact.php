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
class CRM_Activity_BAO_ActivityContact extends CRM_Activity_DAO_ActivityContact {

  /**
   * Function to add activity contact.
   *
   * @param array $params
   *   The values for this table: activity id, contact id, record type.
   *
   * @return CRM_Activity_DAO_ActivityContact
   *   activity_contact object
   *
   * @throws \CRM_Core_Exception
   * @throws \PEAR_Exception
   */
  public static function create(array $params): CRM_Activity_DAO_ActivityContact {
    try {
      return self::writeRecord($params);
    }
    catch (PEAR_Exception $e) {
      // This check used to be done first, creating an extra query before each insert.
      // However, in none of the core tests was this ever called with values that already
      // existed, meaning that this line would never or almost never be hit.
      // hence it's better to save the select query here.
      $activityContact = new CRM_Activity_DAO_ActivityContact();
      $activityContact->copyValues($params);
      if ($activityContact->find(TRUE)) {
        return $activityContact;
      }
      throw $e;
    }
  }

  /**
   * Retrieve names of contact by activity_id.
   *
   * @param int $activityID
   * @param int $recordTypeID
   * @param bool $alsoIDs
   *
   * @return array
   */
  public static function getNames($activityID, $recordTypeID, $alsoIDs = FALSE) {
    $names = [];
    $ids = [];

    if (empty($activityID)) {
      return $alsoIDs ? [$names, $ids] : $names;
    }

    $query = "
SELECT     contact_a.id, contact_a.sort_name
FROM       civicrm_contact contact_a
INNER JOIN civicrm_activity_contact ON civicrm_activity_contact.contact_id = contact_a.id
WHERE      civicrm_activity_contact.activity_id = %1
AND        civicrm_activity_contact.record_type_id = %2
AND        contact_a.is_deleted = 0
";
    $params = [
      1 => [$activityID, 'Integer'],
      2 => [$recordTypeID, 'Integer'],
    ];

    $dao = CRM_Core_DAO::executeQuery($query, $params);
    while ($dao->fetch()) {
      $names[(int) $dao->id] = htmlentities((string) $dao->sort_name);
      $ids[] = (int) $dao->id;
    }

    return $alsoIDs ? [$names, $ids] : $names;
  }

  /**
   * Retrieve id of target contact by activity_id.
   *
   * @param int $activityID
   * @param int $recordTypeID
   *
   * @return mixed
   */
  public static function retrieveContactIdsByActivityId($activityID, $recordTypeID) {
    $activityContact = [];
    if (!CRM_Utils_Rule::positiveInteger($activityID) ||
      !CRM_Utils_Rule::positiveInteger($recordTypeID)
    ) {
      return $activityContact;
    }

    $sql = "                                                                                                                                                                                             SELECT     contact_id
FROM       civicrm_activity_contact
INNER JOIN civicrm_contact ON contact_id = civicrm_contact.id
WHERE      activity_id = %1
AND        record_type_id = %2
AND        civicrm_contact.is_deleted = 0
";
    $params = [
      1 => [$activityID, 'Integer'],
      2 => [$recordTypeID, 'Integer'],
    ];

    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      $activityContact[] = $dao->contact_id;
    }
    return $activityContact;
  }

  /**
   * Get the links associate array  as defined by the links.ini file.
   *
   * Experimental... -
   * Should look a bit like
   *       [local_col_name] => "related_tablename:related_col_name"
   *
   * @see DB_DataObject::getLinks()
   * @see DB_DataObject::getLink()
   *
   * @return array|null
   *   array if there are links defined for this table.
   *   empty array - if there is a links.ini file, but no links on this table
   *   null - if no links.ini exists for this database (hence try auto_links).
   */
  public function links() {
    $link = ['activity_id' => 'civicrm_activity:id'];
    return $link;
  }

}
