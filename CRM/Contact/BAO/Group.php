<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.7                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */
class CRM_Contact_BAO_Group extends CRM_Contact_DAO_Group {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Retrieve DB object based on input parameters.
   *
   * It also stores all the retrieved values in the default array.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Contact_BAO_Group
   */
  public static function retrieve(&$params, &$defaults) {
    $group = new CRM_Contact_DAO_Group();
    $group->copyValues($params);
    if ($group->find(TRUE)) {
      CRM_Core_DAO::storeValues($group, $defaults);
      return $group;
    }
  }

  /**
   * Delete the group and all the object that connect to this group.
   *
   * Incredibly destructive.
   *
   * @param int $id Group id.
   */
  public static function discard($id) {
    CRM_Utils_Hook::pre('delete', 'Group', $id, CRM_Core_DAO::$_nullArray);

    $transaction = new CRM_Core_Transaction();

    // added for CRM-1631 and CRM-1794
    // delete all subscribed mails with the selected group id
    $subscribe = new CRM_Mailing_Event_DAO_Subscribe();
    $subscribe->group_id = $id;
    $subscribe->delete();

    // delete all Subscription  records with the selected group id
    $subHistory = new CRM_Contact_DAO_SubscriptionHistory();
    $subHistory->group_id = $id;
    $subHistory->delete();

    // delete all crm_group_contact records with the selected group id
    $groupContact = new CRM_Contact_DAO_GroupContact();
    $groupContact->group_id = $id;
    $groupContact->delete();

    // make all the 'add_to_group_id' field of 'civicrm_uf_group table', pointing to this group, as null
    $params = array(1 => array($id, 'Integer'));
    $query = "UPDATE civicrm_uf_group SET `add_to_group_id`= NULL WHERE `add_to_group_id` = %1";
    CRM_Core_DAO::executeQuery($query, $params);

    $query = "UPDATE civicrm_uf_group SET `limit_listings_group_id`= NULL WHERE `limit_listings_group_id` = %1";
    CRM_Core_DAO::executeQuery($query, $params);

    // make sure u delete all the entries from civicrm_mailing_group and civicrm_campaign_group
    // CRM-6186
    $query = "DELETE FROM civicrm_mailing_group where entity_table = 'civicrm_group' AND entity_id = %1";
    CRM_Core_DAO::executeQuery($query, $params);

    $query = "DELETE FROM civicrm_campaign_group where entity_table = 'civicrm_group' AND entity_id = %1";
    CRM_Core_DAO::executeQuery($query, $params);

    $query = "DELETE FROM civicrm_acl_entity_role where entity_table = 'civicrm_group' AND entity_id = %1";
    CRM_Core_DAO::executeQuery($query, $params);

    if (Civi::settings()->get('is_enabled')) {
      // clear any descendant groups cache if exists
      CRM_Core_BAO_Cache::deleteGroup('descendant groups for an org');
    }

    // delete from group table
    $group = new CRM_Contact_DAO_Group();
    $group->id = $id;
    $group->delete();

    $transaction->commit();

    CRM_Utils_Hook::post('delete', 'Group', $id, $group);

    // delete the recently created Group
    $groupRecent = array(
      'id' => $id,
      'type' => 'Group',
    );
    CRM_Utils_Recent::del($groupRecent);
  }

  /**
   * Returns an array of the contacts in the given group.
   *
   * @param int $id
   */
  public static function getGroupContacts($id) {
    $params = array(array('group', 'IN', array($id => 1), 0, 0));
    list($contacts, $_) = CRM_Contact_BAO_Query::apiQuery($params, array('contact_id'));
    return $contacts;
  }

  /**
   * Get the count of a members in a group with the specific status.
   *
   * @param int $id
   *   Group id.
   * @param string $status
   *   status of members in group
   * @param bool $countChildGroups
   *
   * @return int
   *   count of members in the group with above status
   */
  public static function memberCount($id, $status = 'Added', $countChildGroups = FALSE) {
    $groupContact = new CRM_Contact_DAO_GroupContact();
    $groupIds = array($id);
    if ($countChildGroups) {
      $groupIds = CRM_Contact_BAO_GroupNesting::getDescendentGroupIds($groupIds);
    }
    $count = 0;

    $contacts = self::getGroupContacts($id);

    foreach ($groupIds as $groupId) {

      $groupContacts = self::getGroupContacts($groupId);
      foreach ($groupContacts as $gcontact) {
        if ($groupId != $id) {
          // Loop through main group's contacts
          // and subtract from the count for each contact which
          // matches one in the present group, if it is not the
          // main group
          foreach ($contacts as $contact) {
            if ($contact['contact_id'] == $gcontact['contact_id']) {
              $count--;
            }
          }
        }
      }
      $groupContact->group_id = $groupId;
      if (isset($status)) {
        $groupContact->status = $status;
      }
      $groupContact->_query['condition'] = 'WHERE contact_id NOT IN (SELECT id FROM civicrm_contact WHERE is_deleted = 1)';
      $count += $groupContact->count();
    }
    return $count;
  }

  /**
   * Get the list of member for a group id.
   *
   * @param int $groupID
   * @param bool $useCache
   *
   * @return array
   *   this array contains the list of members for this group id
   */
  public static function &getMember($groupID, $useCache = TRUE) {
    $params = array(array('group', '=', $groupID, 0, 0));
    $returnProperties = array('contact_id');
    list($contacts, $_) = CRM_Contact_BAO_Query::apiQuery($params, $returnProperties, NULL, NULL, 0, 0, $useCache);

    $aMembers = array();
    foreach ($contacts as $contact) {
      $aMembers[$contact['contact_id']] = 1;
    }

    return $aMembers;
  }

  /**
   * Returns array of group object(s) matching a set of one or Group properties.
   *
   * @param array $params
   *   Limits the set of groups returned.
   * @param array $returnProperties
   *   Which properties should be included in the returned group objects.
   *   (member_count should be last element.)
   * @param string $sort
   * @param int $offset
   * @param int $rowCount
   *
   * @return array
   *   Array of group objects.
   *
   *
   * @todo other BAO functions that use returnProperties (e.g. Query Objects) receive the array flipped & filled with 1s and
   * add in essential fields (e.g. id). This should follow a regular pattern like the others
   */
  public static function getGroups(
    $params = NULL,
    $returnProperties = NULL,
    $sort = NULL,
    $offset = NULL,
    $rowCount = NULL
  ) {
    $dao = new CRM_Contact_DAO_Group();
    if (!isset($params['is_active'])) {
      $dao->is_active = 1;
    }
    if ($params) {
      foreach ($params as $k => $v) {
        if ($k == 'name' || $k == 'title') {
          $dao->whereAdd($k . ' LIKE "' . CRM_Core_DAO::escapeString($v) . '"');
        }
        elseif ($k == 'group_type') {
          foreach ((array) $v as $type) {
            $dao->whereAdd($k . " LIKE '%" . CRM_Core_DAO::VALUE_SEPARATOR . (int) $type . CRM_Core_DAO::VALUE_SEPARATOR . "%'");
          }
        }
        elseif (is_array($v)) {
          foreach ($v as &$num) {
            $num = (int) $num;
          }
          $dao->whereAdd($k . ' IN (' . implode(',', $v) . ')');
        }
        else {
          $dao->$k = $v;
        }
      }
    }

    if ($offset || $rowCount) {
      $offset = ($offset > 0) ? $offset : 0;
      $rowCount = ($rowCount > 0) ? $rowCount : 25;
      $dao->limit($offset, $rowCount);
    }

    if ($sort) {
      $dao->orderBy($sort);
    }

    // return only specific fields if returnproperties are sent
    if (!empty($returnProperties)) {
      $dao->selectAdd();
      $dao->selectAdd(implode(',', $returnProperties));
    }
    $dao->find();

    $flag = $returnProperties && in_array('member_count', $returnProperties) ? 1 : 0;

    $groups = array();
    while ($dao->fetch()) {
      $group = new CRM_Contact_DAO_Group();
      if ($flag) {
        $dao->member_count = CRM_Contact_BAO_Group::memberCount($dao->id);
      }
      $groups[] = clone($dao);
    }
    return $groups;
  }

  /**
   * Make sure that the user has permission to access this group.
   *
   * @param int $id
   *   The id of the object.
   * @param bool $excludeHidden
   *   Should hidden groups be excluded.
   *   Logically this is the wrong place to filter hidden groups out as that is
   *   not a permission issue. However, as other functions may rely on that defaulting to
   *   FALSE for now & only the api call is calling with true.
   *
   * @return array
   *   The permission that the user has (or NULL)
   */
  public static function checkPermission($id, $excludeHidden = FALSE) {
    $allGroups = CRM_Core_PseudoConstant::allGroup(NULL, $excludeHidden);

    $permissions = NULL;
    if (CRM_Core_Permission::check('edit all contacts') ||
      CRM_ACL_API::groupPermission(CRM_ACL_API::EDIT, $id, NULL,
        'civicrm_saved_search', $allGroups
      )
    ) {
      $permissions[] = CRM_Core_Permission::EDIT;
    }

    if (CRM_Core_Permission::check('view all contacts') ||
      CRM_ACL_API::groupPermission(CRM_ACL_API::VIEW, $id, NULL,
        'civicrm_saved_search', $allGroups
      )
    ) {
      $permissions[] = CRM_Core_Permission::VIEW;
    }

    if (!empty($permissions) && CRM_Core_Permission::check('delete contacts')) {
      // Note: using !empty() in if condition, restricts the scope of delete
      // permission to groups/contacts that are editable/viewable.
      // We can remove this !empty condition once we have ACL support for delete functionality.
      $permissions[] = CRM_Core_Permission::DELETE;
    }

    return $permissions;
  }

  /**
   * Create a new group.
   *
   * @param array $params
   *
   * @return CRM_Contact_BAO_Group|NULL
   *   The new group BAO (if created)
   */
  public static function &create(&$params) {

    if (!empty($params['id'])) {
      CRM_Utils_Hook::pre('edit', 'Group', $params['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'Group', NULL, $params);
    }

    // form the name only if missing: CRM-627
    $nameParam = CRM_Utils_Array::value('name', $params, NULL);
    if (!$nameParam && empty($params['id'])) {
      $params['name'] = CRM_Utils_String::titleToVar($params['title']);
    }

    // convert params if array type
    if (isset($params['group_type'])) {
      if (is_array($params['group_type'])) {
        $params['group_type'] = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR,
            array_keys($params['group_type'])
          ) . CRM_Core_DAO::VALUE_SEPARATOR;
      }
    }
    else {
      $params['group_type'] = '';
    }

    $session = CRM_Core_Session::singleton();
    $cid = $session->get('userID');
    // this action is add
    if ($cid && empty($params['id'])) {
      $params['created_id'] = $cid;
    }
    // this action is update
    if ($cid && !empty($params['id'])) {
      $params['modified_id'] = $cid;
    }

    $group = new CRM_Contact_BAO_Group();
    $group->copyValues($params);
    //@todo very hacky fix for the fact this function wants to receive 'parents' as an array further down but
    // needs it as a separated string for the DB. Preferred approaches are having the copyParams or save fn
    // use metadata to translate the array to the appropriate DB type or altering the param in the api layer,
    // or at least altering the param in same section as 'group_type' rather than repeating here. However, further down
    // we need the $params one to be in it's original form & we are not sure what test coverage we have on that
    if (isset($group->parents) && is_array($group->parents)) {
      $group->parents = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR,
          array_keys($group->parents)
        ) . CRM_Core_DAO::VALUE_SEPARATOR;
    }
    if (empty($params['id']) &&
      !$nameParam
    ) {
      $group->name .= "_tmp";
    }
    $group->save();

    if (!$group->id) {
      return NULL;
    }

    if (empty($params['id']) &&
      !$nameParam
    ) {
      $group->name = substr($group->name, 0, -4) . "_{$group->id}";
    }

    $group->buildClause();
    $group->save();

    // add custom field values
    if (!empty($params['custom'])) {
      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_group', $group->id);
    }

    // make the group, child of domain/site group by default.
    $domainGroupID = CRM_Core_BAO_Domain::getGroupId();
    if (CRM_Utils_Array::value('no_parent', $params) !== 1) {
      if (empty($params['parents']) &&
        $domainGroupID != $group->id &&
        Civi::settings()->get('is_enabled') &&
        !CRM_Contact_BAO_GroupNesting::hasParentGroups($group->id)
      ) {
        // if no parent present and the group doesn't already have any parents,
        // make sure site group goes as parent
        $params['parents'] = array($domainGroupID => 1);
      }
      elseif (array_key_exists('parents', $params) && !is_array($params['parents'])) {
        $params['parents'] = array($params['parents'] => 1);
      }

      if (!empty($params['parents'])) {
        foreach ($params['parents'] as $parentId => $dnc) {
          if ($parentId && !CRM_Contact_BAO_GroupNesting::isParentChild($parentId, $group->id)) {
            CRM_Contact_BAO_GroupNesting::add($parentId, $group->id);
          }
        }
      }

      // clear any descendant groups cache if exists
      $finalGroups = CRM_Core_BAO_Cache::deleteGroup('descendant groups for an org');

      // this is always required, since we don't know when a
      // parent group is removed
      CRM_Contact_BAO_GroupNestingCache::update();

      // update group contact cache for all parent groups
      $parentIds = CRM_Contact_BAO_GroupNesting::getParentGroupIds($group->id);
      foreach ($parentIds as $parentId) {
        CRM_Contact_BAO_GroupContactCache::add($parentId);
      }
    }

    if (!empty($params['organization_id'])) {
      $groupOrg = array();
      $groupOrg = $params;
      $groupOrg['group_id'] = $group->id;
      CRM_Contact_BAO_GroupOrganization::add($groupOrg);
    }

    CRM_Contact_BAO_GroupContactCache::add($group->id);

    if (!empty($params['id'])) {
      CRM_Utils_Hook::post('edit', 'Group', $group->id, $group);
    }
    else {
      CRM_Utils_Hook::post('create', 'Group', $group->id, $group);
    }

    $recentOther = array();
    if (CRM_Core_Permission::check('edit groups')) {
      $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/group', 'reset=1&action=update&id=' . $group->id);
      // currently same permission we are using for delete a group
      $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/group', 'reset=1&action=delete&id=' . $group->id);
    }

    // add the recently added group (unless hidden: CRM-6432)
    if (!$group->is_hidden) {
      CRM_Utils_Recent::add($group->title,
        CRM_Utils_System::url('civicrm/group/search', 'reset=1&force=1&context=smog&gid=' . $group->id),
        $group->id,
        'Group',
        NULL,
        NULL,
        $recentOther
      );
    }
    return $group;
  }

  /**
   * Given a saved search compute the clause and the tables
   * and store it for future use
   */
  public function buildClause() {
    $params = array(array('group', 'IN', array($this->id), 0, 0));

    if (!empty($params)) {
      $tables = $whereTables = array();
      $this->where_clause = CRM_Contact_BAO_Query::getWhereClause($params, NULL, $tables, $whereTables);
      if (!empty($tables)) {
        $this->select_tables = serialize($tables);
      }
      if (!empty($whereTables)) {
        $this->where_tables = serialize($whereTables);
      }
    }
  }

  /**
   * Defines a new smart group.
   *
   * @param array $params
   *   Associative array of parameters.
   *
   * @return CRM_Contact_BAO_Group|NULL
   *   The new group BAO (if created)
   */
  public static function createSmartGroup(&$params) {
    if (!empty($params['formValues'])) {
      $ssParams = $params;
      unset($ssParams['id']);
      if (isset($ssParams['saved_search_id'])) {
        $ssParams['id'] = $ssParams['saved_search_id'];
      }

      $savedSearch = CRM_Contact_BAO_SavedSearch::create($params);

      $params['saved_search_id'] = $savedSearch->id;
    }
    else {
      return NULL;
    }

    return self::create($params);
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $isActive
   *   Value we want to set the is_active field.
   *
   * @return CRM_Core_DAO|null
   *   DAO object on success, NULL otherwise
   */
  public static function setIsActive($id, $isActive) {
    return CRM_Core_DAO::setFieldValue('CRM_Contact_DAO_Group', $id, 'is_active', $isActive);
  }

  /**
   * Build the condition to retrieve groups.
   *
   * @param string $groupType
   *   Type of group(Access/Mailing) OR the key of the group.
   * @param bool $excludeHidden exclude hidden groups.
   *
   * @return string
   */
  public static function groupTypeCondition($groupType = NULL, $excludeHidden = TRUE) {
    $value = NULL;
    if ($groupType == 'Mailing') {
      $value = CRM_Core_DAO::VALUE_SEPARATOR . '2' . CRM_Core_DAO::VALUE_SEPARATOR;
    }
    elseif ($groupType == 'Access') {
      $value = CRM_Core_DAO::VALUE_SEPARATOR . '1' . CRM_Core_DAO::VALUE_SEPARATOR;
    }
    elseif (!empty($groupType)) {
      // ie we have been given the group key
      $value = CRM_Core_DAO::VALUE_SEPARATOR . $groupType . CRM_Core_DAO::VALUE_SEPARATOR;
    }

    $condition = NULL;
    if ($excludeHidden) {
      $condition = "is_hidden = 0";
    }

    if ($value) {
      if ($condition) {
        $condition .= " AND group_type LIKE '%$value%'";
      }
      else {
        $condition = "group_type LIKE '%$value%'";
      }
    }

    return $condition;
  }

  /**
   * Get permission relevant clauses.
   * CRM-12209
   *
   * @param bool $force
   *
   * @return array
   */
  public static function getPermissionClause($force = FALSE) {
    static $clause = 1;
    static $retrieved = FALSE;
    if ((!$retrieved || $force) && !CRM_Core_Permission::check('view all contacts') && !CRM_Core_Permission::check('edit all contacts')) {
      //get the allowed groups for the current user
      $groups = CRM_ACL_API::group(CRM_ACL_API::VIEW);
      if (!empty($groups)) {
        $groupList = implode(', ', array_values($groups));
        $clause = "groups.id IN ( $groupList ) ";
      }
      else {
        $clause = '1 = 0';
      }
    }
    $retrieved = TRUE;
    return $clause;
  }

  /**
   * @return string
   */
  public function __toString() {
    return $this->title;
  }

  /**
   * This function create the hidden smart group when user perform
   * contact search and want to send mailing to search contacts.
   *
   * @param array $params
   *   ( reference ) an assoc array of name/value pairs.
   *
   * @return array
   *   ( smartGroupId, ssId ) smart group id and saved search id
   */
  public static function createHiddenSmartGroup($params) {
    $ssId = CRM_Utils_Array::value('saved_search_id', $params);

    //add mapping record only for search builder saved search
    $mappingId = NULL;
    if ($params['search_context'] == 'builder') {
      //save the mapping for search builder
      if (!$ssId) {
        //save record in mapping table
        $temp = array();
        $mappingParams = array('mapping_type' => 'Search Builder');
        $mapping = CRM_Core_BAO_Mapping::add($mappingParams, $temp);
        $mappingId = $mapping->id;
      }
      else {
        //get the mapping id from saved search
        $savedSearch = new CRM_Contact_BAO_SavedSearch();
        $savedSearch->id = $ssId;
        $savedSearch->find(TRUE);
        $mappingId = $savedSearch->mapping_id;
      }

      //save mapping fields
      CRM_Core_BAO_Mapping::saveMappingFields($params['form_values'], $mappingId);
    }

    //create/update saved search record.
    $savedSearch = new CRM_Contact_BAO_SavedSearch();
    $savedSearch->id = $ssId;
    $savedSearch->form_values = serialize($params['form_values']);
    $savedSearch->mapping_id = $mappingId;
    $savedSearch->search_custom_id = CRM_Utils_Array::value('search_custom_id', $params);
    $savedSearch->save();

    $ssId = $savedSearch->id;
    if (!$ssId) {
      return NULL;
    }

    $smartGroupId = NULL;
    if (!empty($params['saved_search_id'])) {
      $smartGroupId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $ssId, 'id', 'saved_search_id');
    }
    else {
      //create group only when new saved search.
      $groupParams = array(
        'title' => "Hidden Smart Group {$ssId}",
        'is_active' => CRM_Utils_Array::value('is_active', $params, 1),
        'is_hidden' => CRM_Utils_Array::value('is_hidden', $params, 1),
        'group_type' => CRM_Utils_Array::value('group_type', $params),
        'visibility' => CRM_Utils_Array::value('visibility', $params),
        'saved_search_id' => $ssId,
      );

      $smartGroup = self::create($groupParams);
      $smartGroupId = $smartGroup->id;
    }

    return array($smartGroupId, $ssId);
  }

  /**
   * wrapper for ajax group selector.
   *
   * @param array $params
   *   Associated array for params record id.
   *
   * @return array
   *   associated array of group list
   *   -rp = rowcount
   *   -page= offset
   * @todo there seems little reason for the small number of functions that call this to pass in
   * params that then need to be translated in this function since they are coding them when calling
   */
  static public function getGroupListSelector(&$params) {
    // format the params
    $params['offset'] = ($params['page'] - 1) * $params['rp'];
    $params['rowCount'] = $params['rp'];
    $params['sort'] = CRM_Utils_Array::value('sortBy', $params);

    // get groups
    $groups = CRM_Contact_BAO_Group::getGroupList($params);

    //skip total if we are making call to show only children
    if (empty($params['parent_id'])) {
      // add total
      $params['total'] = CRM_Contact_BAO_Group::getGroupCount($params);

      // get all the groups
      $allGroups = CRM_Core_PseudoConstant::allGroup();
    }

    // format params and add links
    $groupList = array();
    foreach ($groups as $id => $value) {
      $group = array();
      $group['group_id'] = $value['id'];
      $group['count'] = $value['count'];
      $group['title'] = $value['title'];

      // append parent names if in search mode
      if (empty($params['parent_id']) && !empty($value['parents'])) {
        $group['parent_id'] = $value['parents'];
        $groupIds = explode(',', $value['parents']);
        $title = array();
        foreach ($groupIds as $gId) {
          $title[] = $allGroups[$gId];
        }
        $group['title'] .= '<div class="crm-row-parent-name"><em>' . ts('Child of') . '</em>: ' . implode(', ', $title) . '</div>';
        $value['class'] = array_diff($value['class'], array('crm-row-parent'));
      }
      $group['DT_RowId'] = 'row_' . $value['id'];
      if (!$params['parentsOnly']) {
        foreach ($value['class'] as $id => $class) {
          if ($class = 'crm-group-parent') {
            unset($value['class'][$id]);
          }
        }
      }
      $group['DT_RowClass'] = 'crm-entity ' . implode(' ', $value['class']);
      $group['DT_RowAttr'] = array();
      $group['DT_RowAttr']['data-id'] = $value['id'];
      $group['DT_RowAttr']['data-entity'] = 'group';

      $group['description'] = CRM_Utils_Array::value('description', $value);

      if (!empty($value['group_type'])) {
        $group['group_type'] = $value['group_type'];
      }
      else {
        $group['group_type'] = '';
      }

      $group['visibility'] = $value['visibility'];
      $group['links'] = $value['action'];
      $group['org_info'] = CRM_Utils_Array::value('org_info', $value);
      $group['created_by'] = CRM_Utils_Array::value('created_by', $value);

      $group['is_parent'] = $value['is_parent'];

      array_push($groupList, $group);
    }

    $groupsDT = array();
    $groupsDT['data'] = $groupList;
    $groupsDT['recordsTotal'] = $params['total'];
    $groupsDT['recordsFiltered'] = $params['total'];

    return $groupsDT;
  }

  /**
   * This function to get list of groups.
   *
   * @param array $params
   *   Associated array for params.
   *
   * @return array
   */
  public static function getGroupList(&$params) {
    $config = CRM_Core_Config::singleton();

    $whereClause = self::whereClause($params, FALSE);

    //$this->pagerAToZ( $whereClause, $params );

    if (!empty($params['rowCount']) &&
      $params['rowCount'] > 0
    ) {
      $limit = " LIMIT {$params['offset']}, {$params['rowCount']} ";
    }

    $orderBy = ' ORDER BY groups.title asc';
    if (!empty($params['sort'])) {
      $orderBy = ' ORDER BY ' . CRM_Utils_Type::escape($params['sort'], 'String');

      // CRM-16905 - Sort by count cannot be done with sql
      if (strpos($params['sort'], 'count') === 0) {
        $orderBy = $limit = '';
      }
    }

    $select = $from = $where = "";
    $groupOrg = FALSE;
    if (CRM_Core_Permission::check('administer Multiple Organizations') &&
      CRM_Core_Permission::isMultisiteEnabled()
    ) {
      $select = ", contact.display_name as org_name, contact.id as org_id";
      $from = " LEFT JOIN civicrm_group_organization gOrg
                               ON gOrg.group_id = groups.id
                        LEFT JOIN civicrm_contact contact
                               ON contact.id = gOrg.organization_id ";

      //get the Organization ID
      $orgID = CRM_Utils_Request::retrieve('oid', 'Positive', CRM_Core_DAO::$_nullObject);
      if ($orgID) {
        $where = " AND gOrg.organization_id = {$orgID}";
      }

      $groupOrg = TRUE;
    }

    $query = "
        SELECT groups.*, createdBy.sort_name as created_by {$select}
        FROM  civicrm_group groups
        LEFT JOIN civicrm_contact createdBy
          ON createdBy.id = groups.created_id
        {$from}
        WHERE $whereClause {$where}
        GROUP BY groups.id
        {$orderBy}
        {$limit}";

    $object = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Contact_DAO_Group');

    //FIXME CRM-4418, now we are handling delete separately
    //if we introduce 'delete for group' make sure to handle here.
    $groupPermissions = array(CRM_Core_Permission::VIEW);
    if (CRM_Core_Permission::check('edit groups')) {
      $groupPermissions[] = CRM_Core_Permission::EDIT;
      $groupPermissions[] = CRM_Core_Permission::DELETE;
    }

    // CRM-9936
    $reservedPermission = CRM_Core_Permission::check('administer reserved groups');

    $links = self::actionLinks();

    $allTypes = CRM_Core_OptionGroup::values('group_type');
    $values = $groupsToCount = array();

    $visibility = CRM_Core_SelectValues::ufVisibility();

    while ($object->fetch()) {
      $permission = CRM_Contact_BAO_Group::checkPermission($object->id, $object->title);
      //@todo CRM-12209 introduced an ACL check in the whereClause function
      // it may be that this checking is now obsolete - or that what remains
      // should be removed to the whereClause (which is also accessed by getCount)

      if ($permission) {
        $newLinks = $links;
        $values[$object->id] = array(
          'class' => array(),
          'count' => '0',
        );
        CRM_Core_DAO::storeValues($object, $values[$object->id]);

        if ($object->saved_search_id) {
          $values[$object->id]['title'] .= ' (' . ts('Smart Group') . ')';
          // check if custom search, if so fix view link
          $customSearchID = CRM_Core_DAO::getFieldValue(
            'CRM_Contact_DAO_SavedSearch',
            $object->saved_search_id,
            'search_custom_id'
          );

          if ($customSearchID) {
            $newLinks[CRM_Core_Action::VIEW]['url'] = 'civicrm/contact/search/custom';
            $newLinks[CRM_Core_Action::VIEW]['qs'] = "reset=1&force=1&ssID={$object->saved_search_id}";
          }
        }

        $action = array_sum(array_keys($newLinks));

        // CRM-9936
        if (array_key_exists('is_reserved', $object)) {
          //if group is reserved and I don't have reserved permission, suppress delete/edit
          if ($object->is_reserved && !$reservedPermission) {
            $action -= CRM_Core_Action::DELETE;
            $action -= CRM_Core_Action::UPDATE;
            $action -= CRM_Core_Action::DISABLE;
          }
        }

        if (array_key_exists('is_active', $object)) {
          if ($object->is_active) {
            $action -= CRM_Core_Action::ENABLE;
          }
          else {
            $values[$object->id]['class'][] = 'disabled';
            $action -= CRM_Core_Action::VIEW;
            $action -= CRM_Core_Action::DISABLE;
          }
        }

        $action = $action & CRM_Core_Action::mask($groupPermissions);

        $values[$object->id]['visibility'] = $visibility[$values[$object->id]['visibility']];

        $groupsToCount[$object->saved_search_id ? 'civicrm_group_contact_cache' : 'civicrm_group_contact'][] = $object->id;

        if (isset($values[$object->id]['group_type'])) {
          $groupTypes = explode(CRM_Core_DAO::VALUE_SEPARATOR,
            substr($values[$object->id]['group_type'], 1, -1)
          );
          $types = array();
          foreach ($groupTypes as $type) {
            $types[] = CRM_Utils_Array::value($type, $allTypes);
          }
          $values[$object->id]['group_type'] = implode(', ', $types);
        }
        $values[$object->id]['action'] = CRM_Core_Action::formLink($newLinks,
          $action,
          array(
            'id' => $object->id,
            'ssid' => $object->saved_search_id,
          ),
          ts('more'),
          FALSE,
          'group.selector.row',
          'Group',
          $object->id
        );

        // If group has children, add class for link to view children
        $values[$object->id]['is_parent'] = FALSE;
        if (array_key_exists('children', $values[$object->id])) {
          $values[$object->id]['class'][] = "crm-group-parent";
          $values[$object->id]['is_parent'] = TRUE;
        }

        // If group is a child, add child class
        if (array_key_exists('parents', $values[$object->id])) {
          $values[$object->id]['class'][] = "crm-group-child";
        }

        if ($groupOrg) {
          if ($object->org_id) {
            $contactUrl = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$object->org_id}");
            $values[$object->id]['org_info'] = "<a href='{$contactUrl}'>{$object->org_name}</a>";
          }
          else {
            $values[$object->id]['org_info'] = ''; // Empty cell
          }
        }
        else {
          $values[$object->id]['org_info'] = NULL; // Collapsed column if all cells are NULL
        }
        if ($object->created_id) {
          $contactUrl = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$object->created_id}");
          $values[$object->id]['created_by'] = "<a href='{$contactUrl}'>{$object->created_by}</a>";
        }
      }
    }

    // Get group counts - executes one query for regular groups and another for smart groups
    foreach ($groupsToCount as $table => $groups) {
      $where = "g.group_id IN (" . implode(',', $groups) . ")";
      if ($table == 'civicrm_group_contact') {
        $where .= " AND g.status = 'Added'";
      }
      // Exclude deleted contacts
      $where .= " and c.id = g.contact_id AND c.is_deleted = 0";
      $dao = CRM_Core_DAO::executeQuery("SELECT g.group_id, COUNT(g.id) as `count` FROM $table g, civicrm_contact c WHERE $where GROUP BY g.group_id");
      while ($dao->fetch()) {
        $values[$dao->group_id]['count'] = $dao->count;
      }
    }

    // CRM-16905 - Sort by count cannot be done with sql
    if (!empty($params['sort']) && strpos($params['sort'], 'count') === 0) {
      usort($values, function($a, $b) {
        return $a['count'] - $b['count'];
      });
      if (strpos($params['sort'], 'desc')) {
        $values = array_reverse($values, TRUE);
      }
      return array_slice($values, $params['offset'], $params['rowCount']);
    }

    return $values;
  }

  /**
   * This function to get hierarchical list of groups (parent followed by children)
   *
   * @param array $groupIDs
   *   Array of group ids.
   *
   * @param NULL $parents
   * @param string $spacer
   * @param bool $titleOnly
   *
   * @return array
   */
  public static function getGroupsHierarchy(
    $groupIDs,
    $parents = NULL,
    $spacer = '<span class="child-indent"></span>',
    $titleOnly = FALSE
  ) {
    if (empty($groupIDs)) {
      return array();
    }

    $groupIdString = '(' . implode(',', array_keys($groupIDs)) . ')';
    // <span class="child-icon"></span>
    // need to return id, title (w/ spacer), description, visibility

    // We need to build a list of tags ordered by hierarchy and sorted by
    // name. The hierarchy will be communicated by an accumulation of
    // separators in front of the name to give it a visual offset.
    // Instead of recursively making mysql queries, we'll make one big
    // query and build the hierarchy with the algorithm below.
    $groups = array();
    $args = array(1 => array($groupIdString, 'String'));
    $query = "
SELECT id, title, description, visibility, parents
FROM   civicrm_group
WHERE  id IN $groupIdString
";
    if ($parents) {
      // group can have > 1 parent so parents may be comma separated list (eg. '1,2,5'). We just grab and match on 1st parent.
      $parentArray = explode(',', $parents);
      $parent = $parentArray[0];
      $args[2] = array($parent, 'Integer');
      $query .= " AND SUBSTRING_INDEX(parents, ',', 1) = %2";
    }
    $query .= " ORDER BY title";
    $dao = CRM_Core_DAO::executeQuery($query, $args);

    // Sort the groups into the correct storage by the parent
    // $roots represent the current leaf nodes that need to be checked for
    // children. $rows represent the unplaced nodes
    $roots = $rows = $allGroups = array();
    while ($dao->fetch()) {
      $allGroups[$dao->id] = array(
        'title' => $dao->title,
        'visibility' => $dao->visibility,
        'description' => $dao->description,
      );

      if ($dao->parents == $parents) {
        $roots[] = array(
          'id' => $dao->id,
          'prefix' => '',
          'title' => $dao->title,
        );
      }
      else {
        // group can have > 1 parent so $dao->parents may be comma separated list (eg. '1,2,5'). Grab and match on 1st parent.
        $parentArray = explode(',', $dao->parents);
        $parent = $parentArray[0];
        $rows[] = array(
          'id' => $dao->id,
          'prefix' => '',
          'title' => $dao->title,
          'parents' => $parent,
        );
      }
    }
    $dao->free();
    // While we have nodes left to build, shift the first (alphabetically)
    // node of the list, place it in our groups list and loop through the
    // list of unplaced nodes to find its children. We make a copy to
    // iterate through because we must modify the unplaced nodes list
    // during the loop.
    while (count($roots)) {
      $new_roots = array();
      $current_rows = $rows;
      $root = array_shift($roots);
      $groups[$root['id']] = array($root['prefix'], $root['title']);

      // As you find the children, append them to the end of the new set
      // of roots (maintain alphabetical ordering). Also remove the node
      // from the set of unplaced nodes.
      if (is_array($current_rows)) {
        foreach ($current_rows as $key => $row) {
          if ($row['parents'] == $root['id']) {
            $new_roots[] = array(
              'id' => $row['id'],
              'prefix' => $groups[$root['id']][0] . $spacer,
              'title' => $row['title'],
            );
            unset($rows[$key]);
          }
        }
      }

      //As a group, insert the new roots into the beginning of the roots
      //list. This maintains the hierarchical ordering of the tags.
      $roots = array_merge($new_roots, $roots);
    }

    // below is the redundant looping to ensure child groups are populated in the case where user does not have
    // access to parent groups ( esp. using ACL permissions and logged in user can assess only child groups )
    foreach ($rows as $value) {
      $groups[$value['id']] = array($value['prefix'], $value['title']);
    }
    // Prefix titles with the calcuated spacing to give the visual
    // appearance of ordering when transformed into HTML in the form layer. Add description and visibility.
    $groupsReturn = array();
    foreach ($groups as $key => $value) {
      if ($titleOnly) {
        $groupsReturn[$key] = $value[0] . $value[1];
      }
      else {
        $groupsReturn[$key] = array(
          'title' => $value[0] . $value[1],
          'description' => $allGroups[$key]['description'],
          'visibility' => $allGroups[$key]['visibility'],
        );
      }
    }

    return $groupsReturn;
  }

  /**
   * @param array $params
   *
   * @return NULL|string
   */
  public static function getGroupCount(&$params) {
    $whereClause = self::whereClause($params, FALSE);
    $query = "SELECT COUNT(*) FROM civicrm_group groups";

    if (!empty($params['created_by'])) {
      $query .= "
INNER JOIN civicrm_contact createdBy
       ON createdBy.id = groups.created_id";
    }
    $query .= "
WHERE {$whereClause}";
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * Generate permissioned where clause for group search.
   * @param array $params
   * @param bool $sortBy
   * @param bool $excludeHidden
   *
   * @return string
   */
  public static function whereClause(&$params, $sortBy = TRUE, $excludeHidden = TRUE) {
    $values = array();
    $title = CRM_Utils_Array::value('title', $params);
    if ($title) {
      $clauses[] = "groups.title LIKE %1";
      if (strpos($title, '%') !== FALSE) {
        $params[1] = array($title, 'String', FALSE);
      }
      else {
        $params[1] = array($title, 'String', TRUE);
      }
    }

    $groupType = CRM_Utils_Array::value('group_type', $params);
    if ($groupType) {
      $types = explode(',', $groupType);
      if (!empty($types)) {
        $clauses[] = 'groups.group_type LIKE %2';
        $typeString = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR, $types) . CRM_Core_DAO::VALUE_SEPARATOR;
        $params[2] = array($typeString, 'String', TRUE);
      }
    }

    $visibility = CRM_Utils_Array::value('visibility', $params);
    if ($visibility) {
      $clauses[] = 'groups.visibility = %3';
      $params[3] = array($visibility, 'String');
    }

    $groupStatus = CRM_Utils_Array::value('status', $params);
    if ($groupStatus) {
      switch ($groupStatus) {
        case 1:
          $clauses[] = 'groups.is_active = 1';
          $params[4] = array($groupStatus, 'Integer');
          break;

        case 2:
          $clauses[] = 'groups.is_active = 0';
          $params[4] = array($groupStatus, 'Integer');
          break;

        case 3:
          $clauses[] = '(groups.is_active = 0 OR groups.is_active = 1 )';
          break;
      }
    }

    $parentsOnly = CRM_Utils_Array::value('parentsOnly', $params);
    if ($parentsOnly) {
      $clauses[] = 'groups.parents IS NULL';
    }

    // only show child groups of a specific parent group
    $parent_id = CRM_Utils_Array::value('parent_id', $params);
    if ($parent_id) {
      $clauses[] = 'groups.id IN (SELECT child_group_id FROM civicrm_group_nesting WHERE parent_group_id = %5)';
      $params[5] = array($parent_id, 'Integer');
    }

    if ($createdBy = CRM_Utils_Array::value('created_by', $params)) {
      $clauses[] = "createdBy.sort_name LIKE %6";
      if (strpos($createdBy, '%') !== FALSE) {
        $params[6] = array($createdBy, 'String', FALSE);
      }
      else {
        $params[6] = array($createdBy, 'String', TRUE);
      }
    }

    if (empty($clauses)) {
      $clauses[] = 'groups.is_active = 1';
    }

    if ($excludeHidden) {
      $clauses[] = 'groups.is_hidden = 0';
    }

    $clauses[] = self::getPermissionClause();

    return implode(' AND ', $clauses);
  }

  /**
   * Define action links.
   *
   * @return array
   *   array of action links
   */
  public static function actionLinks() {
    $links = array(
      CRM_Core_Action::VIEW => array(
        'name' => ts('Contacts'),
        'url' => 'civicrm/group/search',
        'qs' => 'reset=1&force=1&context=smog&gid=%%id%%',
        'title' => ts('Group Contacts'),
      ),
      CRM_Core_Action::UPDATE => array(
        'name' => ts('Settings'),
        'url' => 'civicrm/group',
        'qs' => 'reset=1&action=update&id=%%id%%',
        'title' => ts('Edit Group'),
      ),
      CRM_Core_Action::DISABLE => array(
        'name' => ts('Disable'),
        'ref' => 'crm-enable-disable',
        'title' => ts('Disable Group'),
      ),
      CRM_Core_Action::ENABLE => array(
        'name' => ts('Enable'),
        'ref' => 'crm-enable-disable',
        'title' => ts('Enable Group'),
      ),
      CRM_Core_Action::DELETE => array(
        'name' => ts('Delete'),
        'url' => 'civicrm/group',
        'qs' => 'reset=1&action=delete&id=%%id%%',
        'title' => ts('Delete Group'),
      ),
    );

    return $links;
  }

  /**
   * @param $whereClause
   * @param array $whereParams
   *
   * @return string
   */
  public function pagerAtoZ($whereClause, $whereParams) {
    $query = "
        SELECT DISTINCT UPPER(LEFT(groups.title, 1)) as sort_name
        FROM  civicrm_group groups
        WHERE $whereClause
        ORDER BY LEFT(groups.title, 1)
            ";
    $dao = CRM_Core_DAO::executeQuery($query, $whereParams);

    return CRM_Utils_PagerAToZ::getAToZBar($dao, $this->_sortByCharacter, TRUE);
  }

}
