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

use Civi\Api4\Contact;
use Civi\Api4\SubscriptionHistory;
use Civi\Core\Event\PostEvent;
use Civi\Core\HookInterface;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contact_BAO_GroupContact extends CRM_Contact_DAO_GroupContact implements HookInterface {
  use CRM_Contact_AccessTrait;

  /**
   * Deprecated add function
   *
   * @param array $params
   *
   * @return CRM_Contact_DAO_GroupContact
   * @throws \CRM_Core_Exception
   *
   * @deprecated
   */
  public static function add(array $params): CRM_Contact_DAO_GroupContact {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return self::writeRecord($params);
  }

  /**
   * Callback for hook_civicrm_post().
   *
   * @param \Civi\Core\Event\PostEvent $event
   *
   * @noinspection PhpUnused
   * @noinspection UnknownInspectionInspection
   */
  public static function self_hook_civicrm_post(PostEvent $event): void {
    if (is_object($event->object) && in_array($event->action, ['create', 'edit', 'delete'], TRUE)) {
      // Lookup existing info for the sake of subscription history
      if ($event->action === 'edit') {
        $event->object->find(TRUE);
      }
      if ($event->action === 'delete') {
        $event->object->status = 'Deleted';
      }

      try {
        if (empty($event->object->group_id) || empty($event->object->contact_id) || empty($event->object->status)) {
          $event->object->find(TRUE);
        }
        SubscriptionHistory::save(FALSE)->setRecords([
          [
            'group_id' => $event->object->group_id,
            'contact_id' => $event->object->contact_id,
            'status' => $event->object->status,
            'method' => $event->params['method'] ?? 'API',
            'tracking' => $event->params['tracking'] ?? NULL,
          ],
        ])->execute();
      }
      catch (CRM_Core_Exception $e) {
        // A failure to create the history might be a deadlock or similar
        // This record is not important enough to trigger a larger fail.
        Civi::log()->warning('Failed to add civicrm_subscription_history record with error :error', ['error' => $e->getMessage()]);
      }
    }
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
  public static function getValues($params, &$values) {
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
    $result = self::bulkAddContactsToGroup($contactIds, $groupId, $method, $status, $tracking);
    CRM_Contact_BAO_GroupContactCache::invalidateGroupContactCache($groupId);
    CRM_Contact_BAO_Contact_Utils::clearContactCaches();

    return [count($contactIds), $result['count_added'], $result['count_not_added']];
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
        $query = "DELETE FROM civicrm_group_contact WHERE contact_id = %1 AND group_id = %2";
        $dao = CRM_Core_DAO::executeQuery($query, [
          1 => [$contactId, 'Positive'],
          2 => [$groupId, 'Positive'],
        ]);
        $historyParams = [
          'group_id' => $groupId,
          'contact_id' => $contactId,
          'status' => $status,
          'method' => $method,
          'date' => $date,
          'tracking' => $tracking,
        ];
        CRM_Contact_BAO_SubscriptionHistory::create($historyParams);
        // Removing a row from civicrm_group_contact for a smart group may mean a contact
        // Is now back in a group based on criteria so we will invalidate the cache if it is there
        // So that accurate group cache is created next time it is needed.
        CRM_Contact_BAO_GroupContactCache::invalidateGroupContactCache($groupId);
      }
      else {
        // 'Removed' means we add a history record and ensure the GroupContact record exists with a 'Removed' status.
        $groupContact = new CRM_Contact_DAO_GroupContact();
        $groupContact->group_id = $groupId;
        $groupContact->contact_id = $contactId;
        // check if the selected contact is already listed as Removed
        // an opt-out of a smart group.
        // if not a member remove to groupContact else keep the count of contacts that are not removed
        if (($groupContact->find(TRUE) || $group->saved_search_id) && $groupContact->status !== $status) {
          // remove the contact from the group.
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

    $group = CRM_Core_DAO::executeQuery($sql);

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
   * @param bool $public
   *   Are we returning groups for use on a public page.
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
    $includeSmartGroups = FALSE,
    $public = FALSE
  ) {
    if ($count) {
      $select = 'SELECT count(DISTINCT civicrm_group_contact.id)';
    }
    else {
      $select = 'SELECT
                    civicrm_group_contact.id as civicrm_group_contact_id,
                    civicrm_group.title as group_title,
                    civicrm_group.frontend_title as group_public_title,
                    civicrm_group.visibility as visibility,
                    civicrm_group_contact.status as status,
                    civicrm_group.id as group_id,
                    civicrm_group.is_hidden as is_hidden,
                    civicrm_subscription_history.date as date,
                    civicrm_subscription_history.method as method,
                    civicrm_group.saved_search_id as saved_search_id';

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
        $values[$id]['title'] = ($public && !empty($group->group_public_title) ? $group->group_public_title : $dao->group_title);
        $values[$id]['visibility'] = $dao->visibility;
        $values[$id]['is_hidden'] = $dao->is_hidden;
        $values[$id]['saved_search_id'] = $dao->saved_search_id;
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
          $subscriptionHistory = \Civi\Api4\SubscriptionHistory::get()
            ->addSelect('date', 'status')
            ->addWhere('contact_id', '=', $contactId)
            ->addWhere('group_id', '=', $values[$id]['group_id'])
            ->addWhere('status', 'IN', ['Added', 'Deleted'])
            ->addOrderBy('date', 'DESC')
            ->setLimit(1)
            ->execute()->first();
          $values[$id]['date_added'] = ($subscriptionHistory && $subscriptionHistory['status'] === 'Added') ? $subscriptionHistory['date'] : NULL;

          $subscriptionRemovedHistory = \Civi\Api4\SubscriptionHistory::get()
            ->addSelect('date')
            ->addWhere('date', '>', ($subscriptionHistory) ? $subscriptionHistory['date'] : NULL)
            ->addWhere('contact_id', '=', $contactId)
            ->addWhere('group_id', '=', $values[$id]['group_id'])
            ->addWhere('status', '=', 'Removed')
            ->addOrderBy('date', 'ASC')
            ->setLimit(1)
            ->execute()->first();
          $values[$id]['out_date'] = ($subscriptionRemovedHistory) ? $subscriptionRemovedHistory['date'] : $values[$id]['out_date'];
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
      $orderBy = "ORDER BY civicrm_subscription_history.id DESC LIMIT 1";
    }
    $query = "
SELECT    *
  FROM civicrm_group_contact
          $leftJoin
  WHERE civicrm_group_contact.contact_id = %1
  AND civicrm_group_contact.group_id = %2
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
   * @return int groupID
   */
  public static function getGroupId($groupContactID) {
    $dao = new CRM_Contact_DAO_GroupContact();
    $dao->id = $groupContactID;
    $dao->find(TRUE);
    return $dao->group_id;
  }

  /**
   * Deprecated create function.
   *
   * @deprecated
   *
   * @param array $params
   *
   * @return CRM_Contact_DAO_GroupContact
   */
  public static function create(array $params) {
    // @fixme create was only called from CRM_Contact_BAO_Contact::createProfileContact
    // As of Aug 2020 it's not called from anywhere so we can remove the below code after some time

    CRM_Core_Error::deprecatedFunctionWarning('Use the GroupContact API');
    return self::writeRecord($params);
  }

  /**
   * Function that doesn't do much.
   *
   * @param int $contactID
   * @param int $groupID
   *
   * @deprecated
   * @return bool
   */
  public static function isContactInGroup(int $contactID, int $groupID) {
    return (bool) Contact::get(FALSE)
      ->addWhere('id', '=', $contactID)
      ->addWhere('groups', 'IN', [$groupID])
      ->selectRowCount()->execute()->count();
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
        $presentIDs = explode(',', ($dao->contactStr ?? ''));
        $presentIDs = array_flip($presentIDs);
      }

      $gcValues = $shValues = [];
      foreach ($input as $key => $cid) {
        if (isset($presentIDs[$cid])) {
          unset($input[$key]);
          $numContactsNotAdded++;
        }
        else {
          $gcValues[] = "( $groupID, $cid, '$status' )";
          $shValues[] = "( $groupID, $cid, '$date', '$method', '$status', '$tracking' )";
          $numContactsAdded++;
        }
      }

      if (!empty($gcValues)) {
        CRM_Utils_Hook::pre('create', 'GroupContact', $groupID, $input);

        $cgSQL = $contactGroupSQL . implode(",\n", $gcValues);
        CRM_Core_DAO::executeQuery($cgSQL);

        $shSQL = $subscriptioHistorySQL . implode(",\n", $shValues);
        CRM_Core_DAO::executeQuery($shSQL);

        CRM_Utils_Hook::post('create', 'GroupContact', $groupID, $input);
      }
    }

    return ['count_added' => $numContactsAdded, 'count_not_added' => $numContactsNotAdded];
  }

  /**
   * Legacy option getter
   *
   * @deprecated
   *
   * @param string $fieldName
   * @param string $context
   * @param array $props
   *
   * @return array|bool
   */
  public static function buildOptions($fieldName, $context = NULL, $props = []) {
    // Legacy formatting used by some forms
    // TODO: Do any forms still use this? If not, remove this function.
    if (($fieldName == 'group' || $fieldName == 'group_id') && ($context == 'search' || $context == 'create')) {
      $options = CRM_Core_PseudoConstant::get(__CLASS__, $fieldName, [], $context);
      return CRM_Contact_BAO_Group::getGroupsHierarchy($options, NULL, '- ', TRUE);
    }

    return parent::buildOptions($fieldName, $context, $props);
  }

}
