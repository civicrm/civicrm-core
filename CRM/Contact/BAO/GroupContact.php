<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Contact_BAO_GroupContact extends CRM_Contact_DAO_GroupContact {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Takes an associative array and creates a groupContact object.
   *
   * the function extract all the params it needs to initialize the create a
   * group object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return CRM_Contact_BAO_Group
   */
  public static function add($params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'GroupContact', CRM_Utils_Array::value('id', $params), $params);

    if (!self::dataExists($params)) {
      return NULL;
    }

    $groupContact = new CRM_Contact_BAO_GroupContact();
    $groupContact->copyValues($params);
    $groupContact->save();

    // Lookup existing info for the sake of subscription history
    if (!empty($params['id'])) {
      $groupContact->find(TRUE);
      $params = $groupContact->toArray();
    }
    CRM_Contact_BAO_SubscriptionHistory::create($params);

    CRM_Utils_Hook::post($hook, 'GroupContact', $groupContact->id, $groupContact);

    return $groupContact;
  }

  /**
   * Check if there is data to create the object.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return bool
   */
  public static function dataExists(&$params) {
    return (!empty($params['id']) || (!empty($params['group_id']) && !empty($params['contact_id'])));
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array $params
   *   Input parameters to find object.
   * @param array $values
   *   Output values of the object.
   *
   * @return array
   *   (reference)   the values that could be potentially assigned to smarty
   */
  public static function getValues(&$params, &$values) {
    if (empty($params)) {
      return NULL;
    }
    $values['group']['data'] = CRM_Contact_BAO_GroupContact::getContactGroup($params['contact_id'],
      'Added',
      3
    );

    // get the total count of groups
    $values['group']['totalCount'] = CRM_Contact_BAO_GroupContact::getContactGroup($params['contact_id'],
      'Added',
      NULL,
      TRUE
    );

    return NULL;
  }

  /**
   * Given an array of contact ids, add all the contacts to the group
   *
   * @param array $contactIds
   *   The array of contact ids to be added.
   * @param int $groupId
   *   The id of the group.
   * @param string $method
   * @param string $status
   * @param int $tracking
   *
   * @return array
   *   (total, added, notAdded) count of contacts added to group
   */
  public static function addContactsToGroup(
    $contactIds,
    $groupId,
    $method = 'Admin',
    $status = 'Added',
    $tracking = NULL
  ) {
    if (empty($contactIds) || empty($groupId)) {
      return [];
    }

    CRM_Utils_Hook::pre('create', 'GroupContact', $groupId, $contactIds);

    list($numContactsAdded, $numContactsNotAdded)
      = self::bulkAddContactsToGroup($contactIds, $groupId, $method, $status, $tracking);

    CRM_Contact_BAO_Contact_Utils::clearContactCaches();

    CRM_Utils_Hook::post('create', 'GroupContact', $groupId, $contactIds);

    return [count($contactIds), $numContactsAdded, $numContactsNotAdded];
  }

  /**
   * Given an array of contact ids, remove all the contacts from the group
   *
   * @param array $contactIds
   *   (reference ) the array of contact ids to be removed.
   * @param int $groupId
   *   The id of the group.
   *
   * @param string $method
   * @param string $status
   * @param string $tracking
   *
   * @return array
   *   (total, removed, notRemoved) count of contacts removed to group
   */
  public static function removeContactsFromGroup(
    &$contactIds,
    $groupId,
    $method = 'Admin',
    $status = 'Removed',
    $tracking = NULL
  ) {
    if (!is_array($contactIds)) {
      return [0, 0, 0];
    }

    if ($status == 'Removed' || $status == 'Deleted') {
      $op = 'delete';
    }
    else {
      $op = 'edit';
    }

    CRM_Utils_Hook::pre($op, 'GroupContact', $groupId, $contactIds);

    $date = date('YmdHis');
    $numContactsRemoved = 0;
    $numContactsNotRemoved = 0;

    $group = new CRM_Contact_DAO_Group();
    $group->id = $groupId;
    $group->find(TRUE);

    foreach ($contactIds as $contactId) {
      if ($status == 'Deleted') {
        $query = "DELETE FROM civicrm_group_contact WHERE contact_id=$contactId AND group_id=$groupId";
        $dao = CRM_Core_DAO::executeQuery($query);
        $historyParams = [
          'group_id' => $groupId,
          'contact_id' => $contactId,
          'status' => $status,
          'method' => $method,
          'date' => $date,
          'tracking' => $tracking,
        ];
        CRM_Contact_BAO_SubscriptionHistory::create($historyParams);
      }
      else {
        $groupContact = new CRM_Contact_DAO_GroupContact();
        $groupContact->group_id = $groupId;
        $groupContact->contact_id = $contactId;
        // check if the selected contact id already a member, or if this is
        // an opt-out of a smart group.
        // if not a member remove to groupContact else keep the count of contacts that are not removed
        if ($groupContact->find(TRUE) || $group->saved_search_id) {
          // remove the contact from the group
          $numContactsRemoved++;
        }
        else {
          $numContactsNotRemoved++;
        }

        //now we grant the negative membership to contact if not member. CRM-3711
        $historyParams = [
          'group_id' => $groupId,
          'contact_id' => $contactId,
          'status' => $status,
          'method' => $method,
          'date' => $date,
          'tracking' => $tracking,
        ];
        CRM_Contact_BAO_SubscriptionHistory::create($historyParams);
        $groupContact->status = $status;
        $groupContact->save();
        // Remove any rows from the group contact cache so it disappears straight away from smart groups.
        CRM_Contact_BAO_GroupContactCache::removeContact($contactId, $groupId);
      }
    }

    CRM_Contact_BAO_Contact_Utils::clearContactCaches();

    CRM_Utils_Hook::post($op, 'GroupContact', $groupId, $contactIds);

    return [count($contactIds), $numContactsRemoved, $numContactsNotRemoved];
  }

  /**
   * Get list of all the groups and groups for a contact.
   *
   * @param int $contactId
   *   Contact id.
   *
   * @param bool $visibility
   *
   *
   * @return array
   *   this array has key-> group id and value group title
   */
  public static function getGroupList($contactId = 0, $visibility = FALSE) {
    $group = new CRM_Contact_DAO_Group();

    $select = $from = $where = '';

    $select = 'SELECT civicrm_group.id, civicrm_group.title ';
    $from = ' FROM civicrm_group ';
    $where = " WHERE civicrm_group.is_active = 1 ";
    if ($contactId) {
      $from .= ' , civicrm_group_contact ';
      $where .= " AND civicrm_group.id = civicrm_group_contact.group_id
                        AND civicrm_group_contact.contact_id = " . CRM_Utils_Type::escape($contactId, 'Integer');
    }

    if ($visibility) {
      $where .= " AND civicrm_group.visibility != 'User and User Admin Only'";
    }
    $groupBy = " GROUP BY civicrm_group.id";

    $orderby = " ORDER BY civicrm_group.name";
    $sql = $select . $from . $where . $groupBy . $orderby;

    $group->query($sql);

    $values = [];
    while ($group->fetch()) {
      $values[$group->id] = $group->title;
    }

    return $values;
  }

  /**
   * Get the list of groups for contact based on status of group membership.
   *
   * @param int $contactId
   *   Contact id.
   * @param string $status
   *   State of membership.
   * @param int $numGroupContact
   *   Number of groups for a contact that should be shown.
   * @param bool $count
   *   True if we are interested only in the count.
   * @param bool $ignorePermission
   *   True if we should ignore permissions for the current user.
   *                                   useful in profile where permissions are limited for the user. If left
   *                                   at false only groups viewable by the current user are returned
   * @param bool $onlyPublicGroups
   *   True if we want to hide system groups.
   *
   * @param bool $excludeHidden
   *
   * @param int $groupId
   *
   * @param bool $includeSmartGroups
   *   Include or Exclude Smart Group(s)
   *
   * @return array|int
   *   the relevant data object values for the contact or the total count when $count is TRUE
   */
  public static function getContactGroup(
    $contactId,
    $status = NULL,
    $numGroupContact = NULL,
    $count = FALSE,
    $ignorePermission = FALSE,
    $onlyPublicGroups = FALSE,
    $excludeHidden = TRUE,
    $groupId = NULL,
    $includeSmartGroups = FALSE
  ) {
    if ($count) {
      $select = 'SELECT count(DISTINCT civicrm_group_contact.id)';
    }
    else {
      $select = 'SELECT
                    civicrm_group_contact.id as civicrm_group_contact_id,
                    civicrm_group.title as group_title,
                    civicrm_group.visibility as visibility,
                    civicrm_group_contact.status as status,
                    civicrm_group.id as group_id,
                    civicrm_group.is_hidden as is_hidden,
                    civicrm_subscription_history.date as date,
                    civicrm_subscription_history.method as method';
    }

    $where = " WHERE contact_a.id = %1 AND civicrm_group.is_active = 1";
    if (!$includeSmartGroups) {
      $where .= " AND saved_search_id IS NULL";
    }
    if ($excludeHidden) {
      $where .= " AND civicrm_group.is_hidden = 0 ";
    }
    $params = [1 => [$contactId, 'Integer']];
    if (!empty($status)) {
      $where .= ' AND civicrm_group_contact.status = %2';
      $params[2] = [$status, 'String'];
    }
    if (!empty($groupId)) {
      $where .= " AND civicrm_group.id = %3 ";
      $params[3] = [$groupId, 'Integer'];
    }
    $tables = [
      'civicrm_group_contact' => 1,
      'civicrm_group' => 1,
      'civicrm_subscription_history' => 1,
    ];
    $whereTables = [];
    if ($ignorePermission) {
      $permission = ' ( 1 ) ';
    }
    else {
      $permission = CRM_Core_Permission::getPermissionedStaticGroupClause(CRM_Core_Permission::VIEW, $tables, $whereTables);
    }

    $from = CRM_Contact_BAO_Query::fromClause($tables);

    $where .= " AND $permission ";

    if ($onlyPublicGroups) {
      $where .= " AND civicrm_group.visibility != 'User and User Admin Only' ";
    }

    $order = $limit = '';
    if (!$count) {
      $order = ' ORDER BY civicrm_group.title, civicrm_subscription_history.date ASC';

      if ($numGroupContact) {
        $limit = " LIMIT 0, $numGroupContact";
      }
    }

    $sql = $select . $from . $where . $order . $limit;

    if ($count) {
      $result = CRM_Core_DAO::singleValueQuery($sql, $params);
      return $result;
    }
    else {
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      $values = [];
      while ($dao->fetch()) {
        $id = $dao->civicrm_group_contact_id;
        $values[$id]['id'] = $id;
        $values[$id]['group_id'] = $dao->group_id;
        $values[$id]['title'] = $dao->group_title;
        $values[$id]['visibility'] = $dao->visibility;
        $values[$id]['is_hidden'] = $dao->is_hidden;
        switch ($dao->status) {
          case 'Added':
            $prefix = 'in_';
            break;

          case 'Removed':
            $prefix = 'out_';
            break;

          default:
            $prefix = 'pending_';
        }
        $values[$id][$prefix . 'date'] = $dao->date;
        $values[$id][$prefix . 'method'] = $dao->method;
        if ($status == 'Removed') {
          $query = "SELECT `date` as `date_added` FROM civicrm_subscription_history WHERE id = (SELECT max(id) FROM civicrm_subscription_history WHERE contact_id = %1 AND status = \"Added\" AND group_id = $dao->group_id )";
          $dateDAO = CRM_Core_DAO::executeQuery($query, $params);
          if ($dateDAO->fetch()) {
            $values[$id]['date_added'] = $dateDAO->date_added;
          }
        }
      }
      return $values;
    }
  }

  /**
   * Returns membership details of a contact for a group.
   *
   * @param int $contactId
   *   Id of the contact.
   * @param int $groupID
   *   Id of a particular group.
   * @param string $method
   *   If we want the subscription history details for a specific method.
   *
   * @return object
   *   of group contact
   */
  public static function getMembershipDetail($contactId, $groupID, $method = 'Email') {
    $leftJoin = $where = $orderBy = NULL;

    if ($method) {
      //CRM-13341 add group_id clause
      $leftJoin = "
        LEFT JOIN civicrm_subscription_history
          ON ( civicrm_group_contact.contact_id = civicrm_subscription_history.contact_id
          AND civicrm_subscription_history.group_id = {$groupID} )";
      $where = "AND civicrm_subscription_history.method ='Email'";
      $orderBy = "ORDER BY civicrm_subscription_history.id DESC";
    }
    $query = "
SELECT    *
  FROM civicrm_group_contact
          $leftJoin
  WHERE civicrm_group_contact.contact_id = %1
  AND civicrm_group_contact.group_id = %2
          $where
          $orderBy
";

    $params = [
      1 => [$contactId, 'Integer'],
      2 => [$groupID, 'Integer'],
    ];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $dao->fetch();
    return $dao;
  }

  /**
   * Method to get Group Id.
   *
   * @param int $groupContactID
   *   Id of a particular group.
   *
   *
   * @return groupID
   */
  public static function getGroupId($groupContactID) {
    $dao = new CRM_Contact_DAO_GroupContact();
    $dao->id = $groupContactID;
    $dao->find(TRUE);
    return $dao->group_id;
  }

  /**
   * Takes an associative array and creates / removes
   * contacts from the groups
   *
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $contactId
   *   Contact id.
   *
   * @param bool $visibility
   * @param string $method
   */
  public static function create(&$params, $contactId, $visibility = FALSE, $method = 'Admin') {
    $contactIds = [];
    $contactIds[] = $contactId;

    //if $visibility is true we are coming in via profile mean $method = 'Web'
    $ignorePermission = FALSE;
    if ($visibility) {
      $ignorePermission = TRUE;
    }

    if ($contactId) {
      $contactGroupList = CRM_Contact_BAO_GroupContact::getContactGroup($contactId, 'Added',
        NULL, FALSE, $ignorePermission
      );
      if (is_array($contactGroupList)) {
        foreach ($contactGroupList as $key) {
          $groupId = $key['group_id'];
          $contactGroup[$groupId] = $groupId;
        }
      }
    }

    // get the list of all the groups
    $allGroup = CRM_Contact_BAO_GroupContact::getGroupList(0, $visibility);

    // this fix is done to prevent warning generated by array_key_exits incase of empty array is given as input
    if (!is_array($params)) {
      $params = [];
    }

    // this fix is done to prevent warning generated by array_key_exits incase of empty array is given as input
    if (!isset($contactGroup) || !is_array($contactGroup)) {
      $contactGroup = [];
    }

    // check which values has to be add/remove contact from group
    foreach ($allGroup as $key => $varValue) {
      if (!empty($params[$key]) && !array_key_exists($key, $contactGroup)) {
        // add contact to group
        CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIds, $key, $method);
      }
      elseif (empty($params[$key]) && array_key_exists($key, $contactGroup)) {
        // remove contact from group
        CRM_Contact_BAO_GroupContact::removeContactsFromGroup($contactIds, $key, $method);
      }
    }
  }

  /**
   * @param int $contactID
   * @param int $groupID
   *
   * @return bool
   */
  public static function isContactInGroup($contactID, $groupID) {
    if (!CRM_Utils_Rule::positiveInteger($contactID) ||
      !CRM_Utils_Rule::positiveInteger($groupID)
    ) {
      return FALSE;
    }

    $params = [
      ['group', 'IN', [$groupID], 0, 0],
      ['contact_id', '=', $contactID, 0, 0],
    ];
    list($contacts, $_) = CRM_Contact_BAO_Query::apiQuery($params, ['contact_id']);

    if (!empty($contacts)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Function merges the groups from otherContactID to mainContactID.
   * along with subscription history
   *
   * @param int $mainContactId
   *   Contact id of main contact record.
   * @param int $otherContactId
   *   Contact id of record which is going to merge.
   *
   * @see CRM_Dedupe_Merger::cpTables()
   *
   * TODO: use the 3rd $sqls param to append sql statements rather than executing them here
   */
  public static function mergeGroupContact($mainContactId, $otherContactId) {
    $params = [
      1 => [$mainContactId, 'Integer'],
      2 => [$otherContactId, 'Integer'],
    ];

    // find all groups that are in otherContactID but not in mainContactID, copy them over
    $sql = "
SELECT    cOther.group_id
FROM      civicrm_group_contact cOther
LEFT JOIN civicrm_group_contact cMain ON cOther.group_id = cMain.group_id AND cMain.contact_id = %1
WHERE     cOther.contact_id = %2
AND       cMain.contact_id IS NULL
";
    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    $otherGroupIDs = [];
    while ($dao->fetch()) {
      $otherGroupIDs[] = $dao->group_id;
    }

    if (!empty($otherGroupIDs)) {
      $otherGroupIDString = implode(',', $otherGroupIDs);

      $sql = "
UPDATE    civicrm_group_contact
SET       contact_id = %1
WHERE     contact_id = %2
AND       group_id IN ( $otherGroupIDString )
";
      CRM_Core_DAO::executeQuery($sql, $params);

      $sql = "
UPDATE    civicrm_subscription_history
SET       contact_id = %1
WHERE     contact_id = %2
AND       group_id IN ( $otherGroupIDString )
";
      CRM_Core_DAO::executeQuery($sql, $params);
    }

    $sql = "
SELECT     cOther.group_id as group_id,
           cOther.status   as group_status
FROM       civicrm_group_contact cMain
INNER JOIN civicrm_group_contact cOther ON cMain.group_id = cOther.group_id
WHERE      cMain.contact_id = %1
AND        cOther.contact_id = %2
";
    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    $groupIDs = [];
    while ($dao->fetch()) {
      // only copy it over if it has added status and migrate the history
      if ($dao->group_status == 'Added') {
        $groupIDs[] = $dao->group_id;
      }
    }

    if (!empty($groupIDs)) {
      $groupIDString = implode(',', $groupIDs);

      $sql = "
UPDATE    civicrm_group_contact
SET       status = 'Added'
WHERE     contact_id = %1
AND       group_id IN ( $groupIDString )
";
      CRM_Core_DAO::executeQuery($sql, $params);

      $sql = "
UPDATE    civicrm_subscription_history
SET       contact_id = %1
WHERE     contact_id = %2
AND       group_id IN ( $groupIDString )
";
      CRM_Core_DAO::executeQuery($sql, $params);
    }

    // delete all the other group contacts
    $sql = "
  DELETE
  FROM   civicrm_group_contact
  WHERE  contact_id = %2
  ";
    CRM_Core_DAO::executeQuery($sql, $params);

    $sql = "
  DELETE
  FROM   civicrm_subscription_history
  WHERE  contact_id = %2
  ";
    CRM_Core_DAO::executeQuery($sql, $params);
  }

  /**
   * Given an array of contact ids, add all the contacts to the group
   *
   * @param array $contactIDs
   *   The array of contact ids to be added.
   * @param int $groupID
   *   The id of the group.
   * @param string $method
   * @param string $status
   * @param string $tracking
   *
   * @return array
   *   (total, added, notAdded) count of contacts added to group
   */
  public static function bulkAddContactsToGroup(
    $contactIDs,
    $groupID,
    $method = 'Admin',
    $status = 'Added',
    $tracking = NULL
  ) {

    $numContactsAdded = 0;
    $numContactsNotAdded = 0;

    $contactGroupSQL = "
REPLACE INTO civicrm_group_contact ( group_id, contact_id, status )
VALUES
";
    $subscriptioHistorySQL = "
INSERT INTO civicrm_subscription_history( group_id, contact_id, date, method, status, tracking )
VALUES
";

    $date = date('YmdHis');

    // to avoid long strings, lets do BULK_INSERT_HIGH_COUNT values at a time
    while (!empty($contactIDs)) {
      $input = array_splice($contactIDs, 0, CRM_Core_DAO::BULK_INSERT_HIGH_COUNT);
      $contactStr = implode(',', $input);

      // lets check their current status
      $sql = "
SELECT GROUP_CONCAT(contact_id) as contactStr
FROM   civicrm_group_contact
WHERE  group_id = %1
AND    status = %2
AND    contact_id IN ( $contactStr )
";
      $params = [
        1 => [$groupID, 'Integer'],
        2 => [$status, 'String'],
      ];

      $presentIDs = [];
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      if ($dao->fetch()) {
        $presentIDs = explode(',', $dao->contactStr);
        $presentIDs = array_flip($presentIDs);
      }

      $gcValues = $shValues = [];
      foreach ($input as $cid) {
        if (isset($presentIDs[$cid])) {
          $numContactsNotAdded++;
          continue;
        }

        $gcValues[] = "( $groupID, $cid, '$status' )";
        $shValues[] = "( $groupID, $cid, '$date', '$method', '$status', '$tracking' )";
        $numContactsAdded++;
      }

      if (!empty($gcValues)) {
        $cgSQL = $contactGroupSQL . implode(",\n", $gcValues);
        CRM_Core_DAO::executeQuery($cgSQL);

        $shSQL = $subscriptioHistorySQL . implode(",\n", $shValues);
        CRM_Core_DAO::executeQuery($shSQL);
      }
    }

    return [$numContactsAdded, $numContactsNotAdded];
  }

  /**
   * Get options for a given field.
   * @see CRM_Core_DAO::buildOptions
   *
   * @param string $fieldName
   * @param string $context
   * @see CRM_Core_DAO::buildOptionsContext
   * @param array $props
   *   whatever is known about this dao object.
   *
   * @return array|bool
   */
  public static function buildOptions($fieldName, $context = NULL, $props = []) {

    $options = CRM_Core_PseudoConstant::get(__CLASS__, $fieldName, $props, $context);

    // Sort group list by hierarchy
    // TODO: This will only work when api.entity is "group_contact". What about others?
    if (($fieldName == 'group' || $fieldName == 'group_id') && ($context == 'search' || $context == 'create')) {
      $options = CRM_Contact_BAO_Group::getGroupsHierarchy($options, NULL, '- ', TRUE);
    }

    return $options;
  }

}
