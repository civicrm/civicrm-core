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

use Civi\Api4\Event\AuthorizeRecordEvent;
use Civi\Api4\Group;
use Civi\Core\HookInterface;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contact_BAO_Group extends CRM_Contact_DAO_Group implements HookInterface {

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * Delete the group and all the object that connect to this group.
   *
   * Incredibly destructive.
   *
   * @param int $id Group id.
   */
  public static function discard($id) {
    if (!$id || !is_numeric($id)) {
      throw new CRM_Core_Exception('Invalid group request attempted');
    }
    CRM_Utils_Hook::pre('delete', 'Group', $id);

    $transaction = new CRM_Core_Transaction();

    // added for CRM-1631 and CRM-1794
    // delete all subscribed mails with the selected group id
    $subscribe = new CRM_Mailing_Event_DAO_MailingEventSubscribe();
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
    $params = [1 => [$id, 'Integer']];
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

    //check whether this group contains  any saved searches and check if that saved search is appropriate to delete.
    $groupDetails = Group::get(FALSE)->addWhere('id', '=', $id)->execute();
    if (!empty($groupDetails[0]['saved_search_id'])) {
      $savedSearch = new CRM_Contact_DAO_SavedSearch();
      $savedSearch->id = $groupDetails[0]['saved_search_id'];
      $savedSearch->find(TRUE);
      // If it is a traditional saved search i.e has form values and there is no linked api_entity then delete the saved search as well.
      if (!empty($savedSearch->form_values) && empty($savedSearch->api_entity) && empty($savedSearch->api_params)) {
        $savedSearch->delete();
      }
    }

    // delete from group table
    $group = new CRM_Contact_DAO_Group();
    $group->id = $id;
    $group->delete();

    $transaction->commit();

    CRM_Utils_Hook::post('delete', 'Group', $id, $group);
  }

  /**
   * Returns an array of the contacts in the given group.
   *
   * @param int $id
   */
  public static function getGroupContacts($id) {
    $params = [['group', 'IN', [1 => $id], 0, 0]];
    [$contacts] = CRM_Contact_BAO_Query::apiQuery($params, ['contact_id']);
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
    $groupIds = [$id];
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
   * @param int $limit
   *   Number to limit to (or 0 for unlimited).
   *
   * @return array
   *   this array contains the list of members for this group id
   */
  public static function getMember($groupID, $useCache = TRUE, $limit = 0) {
    $params = [['group', '=', $groupID, 0, 0]];
    $returnProperties = ['contact_id'];
    [$contacts] = CRM_Contact_BAO_Query::apiQuery($params, $returnProperties, NULL, NULL, 0, $limit, $useCache);

    $aMembers = [];
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

    $groups = [];
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
        'civicrm_group', $allGroups
      )
    ) {
      $permissions[] = CRM_Core_Permission::EDIT;
    }

    if (CRM_Core_Permission::check('view all contacts') ||
      CRM_ACL_API::groupPermission(CRM_ACL_API::VIEW, $id, NULL,
        'civicrm_group', $allGroups
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
   * @param string|null $entityName
   * @param int|null $userId
   * @param array $conditions
   * @inheritDoc
   */
  public function addSelectWhereClause(?string $entityName = NULL, ?int $userId = NULL, array $conditions = []): array {
    $clauses = [];
    if (!CRM_Core_Permission::check([['edit all contacts', 'view all contacts']])) {
      $allowedGroups = CRM_Core_Permission::group(NULL, FALSE);
      $groupsIn = $allowedGroups ? implode(',', array_keys($allowedGroups)) : '0';
      $clauses['id'][] = "IN ($groupsIn)";
    }
    CRM_Utils_Hook::selectWhereClause($this, $clauses, $userId, $conditions);
    return $clauses;
  }

  /**
   * @deprecated
   * Create a new group.
   *
   * @param array $params
   *
   * @return CRM_Contact_BAO_Group|NULL
   *   The new group BAO (if created)
   */
  public static function create(&$params) {
    return self::writeRecord($params);
  }

  /**
   * Takes a sloppy mismash of params and creates two entities: a Group and a SavedSearch
   * Currently only used by unit tests.
   *
   * @param array $params
   * @return CRM_Contact_BAO_Group|NULL
   * @deprecated
   */
  public static function createSmartGroup($params) {
    if (!empty($params['formValues'])) {
      $ssParams = $params;
      // Remove group parameters from sloppy mismash
      unset($ssParams['id'], $ssParams['name'], $ssParams['title'], $ssParams['formValues'], $ssParams['saved_search_id']);
      if (isset($params['saved_search_id'])) {
        $ssParams['id'] = $params['saved_search_id'];
      }
      $ssParams['form_values'] = $params['formValues'];
      $savedSearch = CRM_Contact_BAO_SavedSearch::create($ssParams);

      $params['saved_search_id'] = $savedSearch->id;
    }
    else {
      return NULL;
    }

    return self::create($params);
  }

  /**
   * @deprecated - this bypasses hooks.
   * @param int $id
   * @param bool $isActive
   * @return bool
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
   *
   * @return array
   */
  public static function getPermissionClause() {
    if (!isset(Civi::$statics[__CLASS__]['permission_clause'])) {
      if (CRM_Core_Permission::check('view all contacts') || CRM_Core_Permission::check('edit all contacts')) {
        $clause = 1;
      }
      else {
        //get the allowed groups for the current user
        $groups = CRM_ACL_API::group(CRM_ACL_API::VIEW);
        if (!empty($groups)) {
          $groupList = implode(', ', array_values($groups));
          $clause = "`groups`.id IN ( $groupList ) ";
        }
        else {
          $clause = '1 = 0';
        }
      }
      Civi::$statics[__CLASS__]['permission_clause'] = $clause;
    }
    return Civi::$statics[__CLASS__]['permission_clause'];
  }

  /**
   * Flush caches that hold group data.
   *
   * (Actually probably some overkill at the moment.)
   */
  protected static function flushCaches() {
    CRM_Utils_System::flushCache();
    unset(Civi::$statics['CRM_Core_PseudoConstant']['groups']);
    unset(Civi::$statics['CRM_ACL_API']);
    unset(Civi::$statics['CRM_ACL_BAO_ACL']['permissioned_groups']);
    unset(Civi::$statics['CRM_Contact_BAO_Group']['permission_clause']);
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
    $ssId = $params['saved_search_id'] ?? NULL;

    //add mapping record only for search builder saved search
    $mappingId = NULL;
    if ($params['search_context'] == 'builder') {
      //save the mapping for search builder
      if (!$ssId) {
        //save record in mapping table
        $mappingParams = [
          'name' => 'search_builder_' . $ssId,
          'mapping_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Mapping', 'mapping_type_id', 'Search Builder'),
        ];
        $mapping = CRM_Core_BAO_Mapping::writeRecord($mappingParams);
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
    $formValues = $params['search_context'] === 'builder' ? $params['form_values'] : CRM_Contact_BAO_Query::convertFormValues($params['form_values']);
    $savedSearch->form_values = serialize($formValues);
    $savedSearch->mapping_id = $mappingId;
    $savedSearch->search_custom_id = $params['search_custom_id'] ?? NULL;
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
      $groupParams = [
        'title' => "Hidden Smart Group {$ssId}",
        'is_active' => $params['is_active'] ?? 1,
        'is_hidden' => $params['is_hidden'] ?? 1,
        'group_type' => $params['group_type'] ?? NULL,
        'visibility' => $params['visibility'] ?? NULL,
        'saved_search_id' => $ssId,
      ];

      $smartGroup = self::create($groupParams);
      $smartGroupId = $smartGroup->id;
    }

    // Update mapping with the name and description of the hidden smart group.
    if ($mappingId) {
      $mappingParams = [
        'id' => $mappingId,
        'name' => CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $smartGroupId, 'name', 'id'),
        'description' => CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $smartGroupId, 'description', 'id'),
        'mapping_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Mapping', 'mapping_type_id', 'Search Builder'),
      ];
      CRM_Core_BAO_Mapping::writeRecord($mappingParams);
    }

    return [$smartGroupId, $ssId];
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
  public static function getGroupListSelector(&$params) {
    // format the params
    $params['offset'] = ($params['page'] - 1) * $params['rp'];
    $params['rowCount'] = $params['rp'];
    $params['sort'] = $params['sortBy'] ?? NULL;

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
    $groupList = [];
    foreach ($groups as $id => $value) {
      $group = [];
      $group['group_id'] = $value['id'];
      $group['count'] = $value['count'];
      $group['title'] = $value['title'];

      // append parent names if in search mode
      if (empty($params['parent_id']) && !empty($value['parents'])) {
        $group['parent_id'] = $value['parents'];
        $groupIds = explode(',', $value['parents']);
        $title = [];
        foreach ($groupIds as $gId) {
          $title[] = $allGroups[$gId];
        }
        $group['title'] .= '<div class="crm-row-parent-name"><em>' . ts('Child of') . '</em>: ' . implode(', ', $title) . '</div>';
        $value['class'] = array_diff($value['class'], ['crm-row-parent']);
      }
      $group['DT_RowId'] = 'row_' . $value['id'];
      if (empty($params['parentsOnly'])) {
        foreach ($value['class'] as $id => $class) {
          if ($class == 'crm-group-parent') {
            unset($value['class'][$id]);
          }
        }
      }
      $group['DT_RowClass'] = 'crm-entity ' . implode(' ', $value['class']);
      $group['DT_RowAttr'] = [];
      $group['DT_RowAttr']['data-id'] = $value['id'];
      $group['DT_RowAttr']['data-entity'] = 'group';

      $group['description'] = $value['description'] ?? NULL;

      if (!empty($value['group_type'])) {
        $group['group_type'] = $value['group_type'];
      }
      else {
        $group['group_type'] = '';
      }

      $group['visibility'] = $value['visibility'];
      $group['links'] = $value['action'];
      $group['org_info'] = $value['org_info'] ?? NULL;
      $group['created_by'] = $value['created_by'] ?? NULL;

      $group['is_parent'] = $value['is_parent'];

      array_push($groupList, $group);
    }

    $groupsDT = [];
    $groupsDT['data'] = $groupList;
    $groupsDT['recordsTotal'] = !empty($params['total']) ? $params['total'] : 0;
    $groupsDT['recordsFiltered'] = !empty($params['total']) ? $params['total'] : 0;

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
    $whereClause = self::whereClause($params, FALSE);

    $limit = "";
    if (!empty($params['rowCount']) &&
      $params['rowCount'] > 0
    ) {
      $limit = " LIMIT {$params['offset']}, {$params['rowCount']} ";
    }

    $orderBy = ' ORDER BY `groups`.title asc';
    if (!empty($params['sort'])) {
      $orderBy = ' ORDER BY ' . CRM_Utils_Type::escape($params['sort'], 'String');

      // CRM-16905 - Sort by count cannot be done with sql
      if (str_starts_with($params['sort'], 'count')) {
        $orderBy = $limit = '';
      }
    }

    $select = $from = $where = "";
    $groupOrg = FALSE;
    if (CRM_Core_Permission::check('administer Multiple Organizations') &&
      CRM_Core_Permission::isMultisiteEnabled()
    ) {
      $select = ', contact.display_name as org_name, contact.id as org_id';
      $from = " LEFT JOIN civicrm_group_organization gOrg
                               ON gOrg.group_id = `groups`.id
                        LEFT JOIN civicrm_contact contact
                               ON contact.id = gOrg.organization_id ";

      //get the Organization ID
      $orgID = CRM_Utils_Request::retrieve('oid', 'Positive');
      if ($orgID) {
        $where = " AND gOrg.organization_id = {$orgID}";
      }

      $groupOrg = TRUE;
    }

    $query = "
        SELECT `groups`.*, createdBy.sort_name as created_by {$select}
        FROM  civicrm_group `groups`
        LEFT JOIN civicrm_contact createdBy
          ON createdBy.id = `groups`.created_id
        {$from}
        WHERE $whereClause {$where}
        {$orderBy}
        {$limit}";

    $object = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Contact_DAO_Group');

    //FIXME CRM-4418, now we are handling delete separately
    //if we introduce 'delete for group' make sure to handle here.
    $groupPermissions = [CRM_Core_Permission::VIEW];
    if (CRM_Core_Permission::check('edit groups')) {
      $groupPermissions[] = CRM_Core_Permission::EDIT;
      $groupPermissions[] = CRM_Core_Permission::DELETE;
    }

    // CRM-9936
    $reservedPermission = CRM_Core_Permission::check('administer reserved groups');

    $links = self::actionLinks($params);

    $allTypes = CRM_Core_OptionGroup::values('group_type');
    $values = [];

    $visibility = CRM_Core_SelectValues::ufVisibility();

    while ($object->fetch()) {
      $newLinks = $links;
      $values[$object->id] = [
        'class' => [],
        'count' => '0',
      ];
      CRM_Core_DAO::storeValues($object, $values[$object->id]);

      if ($object->saved_search_id) {
        $values[$object->id]['class'][] = "crm-smart-group";
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
      if (property_exists($object, 'is_reserved')) {
        //if group is reserved and I don't have reserved permission, suppress delete/edit
        if ($object->is_reserved && !$reservedPermission) {
          $action -= CRM_Core_Action::DELETE;
          $action -= CRM_Core_Action::UPDATE;
          $action -= CRM_Core_Action::DISABLE;
        }
      }

      if (property_exists($object, 'is_active')) {
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

      if (isset($values[$object->id]['group_type'])) {
        $groupTypes = explode(CRM_Core_DAO::VALUE_SEPARATOR,
          substr($values[$object->id]['group_type'], 1, -1)
        );
        $types = [];
        foreach ($groupTypes as $type) {
          $types[] = $allTypes[$type] ?? NULL;
        }
        $values[$object->id]['group_type'] = implode(', ', $types);
      }
      if ($action) {
        $values[$object->id]['action'] = CRM_Core_Action::formLink($newLinks,
          $action,
          [
            'id' => $object->id,
            'ssid' => $object->saved_search_id,
          ],
          ts('more'),
          FALSE,
          'group.selector.row',
          'Group',
          $object->id
        );
      }

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
          // Empty cell
          $values[$object->id]['org_info'] = '';
        }
      }
      else {
        // Collapsed column if all cells are NULL
        $values[$object->id]['org_info'] = NULL;
      }
      if ($object->created_id) {
        $contactUrl = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$object->created_id}");
        $values[$object->id]['created_by'] = "<a href='{$contactUrl}'>{$object->created_by}</a>";
      }

      // By default, we try to get a count of the contacts in each group
      // to display to the user on the Manage Group page. However, if
      // that will result in the cache being regenerated, then dipslay
      // "unknown" instead to avoid a long wait for the user.
      if (CRM_Contact_BAO_GroupContactCache::shouldGroupBeRefreshed($object->id)) {
        $values[$object->id]['count'] = ts('unknown');
      }
      else {
        $values[$object->id]['count'] = civicrm_api3('Contact', 'getcount', ['group' => $object->id]);
      }
    }

    // CRM-16905 - Sort by count cannot be done with sql
    if (!empty($params['sort']) && str_starts_with($params['sort'], 'count')) {
      usort($values, function($a, $b) {
        if ($a['count'] === 'unknown') {
          return -1;
        }
        if ($b['count'] === 'unknown') {
          return 1;
        }
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
   * @param string $parents
   * @param string $spacer
   * @param bool $titleOnly
   * @param bool $public
   *
   * @return array
   */
  public static function getGroupsHierarchy(
    $groupIDs,
    $parents = NULL,
    $spacer = '<span class="child-indent"></span>',
    $titleOnly = FALSE,
    $public = FALSE
  ) {
    if (empty($groupIDs)) {
      return [];
    }

    $groupIdString = '(' . implode(',', array_keys($groupIDs)) . ')';
    // <span class="child-icon"></span>
    // need to return id, title (w/ spacer), description, visibility

    // We need to build a list of tags ordered by hierarchy and sorted by
    // name. The hierarchy will be communicated by an accumulation of
    // separators in front of the name to give it a visual offset.
    // Instead of recursively making mysql queries, we'll make one big
    // query and build the hierarchy with the algorithm below.
    $groups = [];
    $args = [1 => [$groupIdString, 'String']];
    $query = "
SELECT id, title, frontend_title, description, frontend_description, visibility, parents, saved_search_id
FROM   civicrm_group
WHERE  id IN $groupIdString
";
    if ($parents) {
      // group can have > 1 parent so parents may be comma separated list (eg. '1,2,5').
      $parentArray = explode(',', $parents);
      $parent = self::filterActiveGroups($parentArray);
      $args[2] = [$parent, 'Integer'];
      $query .= " AND SUBSTRING_INDEX(parents, ',', 1) = %2";
    }
    $query .= " ORDER BY title";
    $dao = CRM_Core_DAO::executeQuery($query, $args);

    // Sort the groups into the correct storage by the parent
    // $roots represent the current leaf nodes that need to be checked for
    // children. $rows represent the unplaced nodes
    // $tree contains the child nodes based on their parent_id.
    $roots = [];
    $tree = [];
    while ($dao->fetch()) {
      $title = $dao->title;
      $description = $dao->description;
      if ($public) {
        $title = $dao->frontend_title;
        $description = $dao->frontend_description;
      }
      if ($dao->parents) {
        $parentArray = explode(',', $dao->parents);
        $parent = self::filterActiveGroups($parentArray);
        $tree[$parent][] = [
          'id' => $dao->id,
          'title' => empty($dao->saved_search_id) ? $title : '* ' . $title,
          'visibility' => $dao->visibility,
          'description' => $description,
        ];
      }
      else {
        $roots[] = [
          'id' => $dao->id,
          'title' => empty($dao->saved_search_id) ? $title : '* ' . $title,
          'visibility' => $dao->visibility,
          'description' => $description,
        ];
      }
    }

    $hierarchy = [];
    for ($i = 0; $i < count($roots); $i++) {
      self::buildGroupHierarchy($hierarchy, $roots[$i], $tree, $titleOnly, $spacer, 0);
    }

    return $hierarchy;
  }

  /**
   * Build a list with groups on alphabetical order and child groups after the parent group.
   *
   * This is a recursive function filling the $hierarchy parameter.
   *
   * @param array $hierarchy
   * @param array $group
   * @param array $tree
   * @param bool $titleOnly
   * @param string $spacer
   * @param int $level
   */
  private static function buildGroupHierarchy(&$hierarchy, $group, $tree, $titleOnly, $spacer, $level) {
    $spaces = str_repeat($spacer, $level);

    if ($titleOnly) {
      $hierarchy[$group['id']] = $spaces . $group['title'];
    }
    else {
      $hierarchy[] = [
        'id' => $group['id'],
        'text' => $spaces . $group['title'],
        'description' => $group['description'],
        'visibility' => $group['visibility'],
      ];
    }

    // For performance reasons we use a for loop rather than a foreach.
    // Metrics for performance in an installation with 2867 groups a foreach
    // caused the function getGroupsHierarchy with a foreach execution takes
    // around 2.2 seconds (2,200 ms).
    // Changing to a for loop execustion takes around 0.02 seconds (20 ms).
    if (isset($tree[$group['id']]) && is_array($tree[$group['id']])) {
      for ($i = 0; $i < count($tree[$group['id']]); $i++) {
        self::buildGroupHierarchy($hierarchy, $tree[$group['id']][$i], $tree, $titleOnly, $spacer, $level + 1);
      }
    }
  }

  /**
   * @param array $params
   *
   * @return NULL|string
   */
  public static function getGroupCount(&$params) {
    $whereClause = self::whereClause($params, FALSE);
    $query = "SELECT COUNT(*) FROM civicrm_group `groups`";

    if (!empty($params['created_by'])) {
      $query .= "
INNER JOIN civicrm_contact createdBy
       ON createdBy.id = `groups`.created_id";
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
    $values = [];
    $title = $params['title'] ?? NULL;
    if ($title) {
      $clauses[] = "`groups`.title LIKE %1";
      if (str_contains($title, '%')) {
        $params[1] = [$title, 'String', FALSE];
      }
      else {
        $params[1] = [$title, 'String', TRUE];
      }
    }

    $groupType = $params['group_type'] ?? NULL;
    if ($groupType) {
      $types = explode(',', $groupType);
      if (!empty($types)) {
        $clauses[] = '`groups`.group_type LIKE %2';
        $typeString = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR, $types) . CRM_Core_DAO::VALUE_SEPARATOR;
        $params[2] = [$typeString, 'String', TRUE];
      }
    }

    $visibility = $params['visibility'] ?? NULL;
    if ($visibility) {
      $clauses[] = '`groups`.visibility = %3';
      $params[3] = [$visibility, 'String'];
    }

    $groupStatus = $params['status'] ?? NULL;
    if ($groupStatus) {
      switch ($groupStatus) {
        case 1:
          $clauses[] = '`groups`.is_active = 1';
          $params[4] = [$groupStatus, 'Integer'];
          break;

        case 2:
          $clauses[] = '`groups`.is_active = 0';
          $params[4] = [$groupStatus, 'Integer'];
          break;

        case 3:
          $clauses[] = '(`groups`.is_active = 0 OR `groups`.is_active = 1 )';
          break;
      }
    }

    $parentsOnly = $params['parentsOnly'] ?? NULL;
    if ($parentsOnly) {
      $clauses[] = '`groups`.parents IS NULL';
    }

    $savedSearch = $params['savedSearch'] ?? NULL;
    if ($savedSearch == 1) {
      $clauses[] = '`groups`.saved_search_id IS NOT NULL';
    }
    elseif ($savedSearch == 2) {
      $clauses[] = '`groups`.saved_search_id IS NULL';
    }

    // only show child groups of a specific parent group
    $parent_id = $params['parent_id'] ?? NULL;
    if ($parent_id) {
      $clauses[] = '`groups`.id IN (SELECT child_group_id FROM civicrm_group_nesting WHERE parent_group_id = %5)';
      $params[5] = [$parent_id, 'Integer'];
    }

    $createdBy = $params['created_by'] ?? NULL;
    if ($createdBy) {
      $clauses[] = "createdBy.sort_name LIKE %6";
      if (str_contains($createdBy, '%')) {
        $params[6] = [$createdBy, 'String', FALSE];
      }
      else {
        $params[6] = [$createdBy, 'String', TRUE];
      }
    }

    if (empty($clauses)) {
      $clauses[] = '`groups`.is_active = 1';
    }

    if ($excludeHidden) {
      $clauses[] = '`groups`.is_hidden = 0';
    }

    $clauses[] = self::getPermissionClause();

    return implode(' AND ', $clauses);
  }

  /**
   * Define action links.
   *
   * @param array $params
   *
   * @return array
   *   array of action links
   * @throws \CRM_Core_Exception
   */
  public static function actionLinks(array $params): array {
    // If component_mode is set we change the "View" link to match the requested component type
    if (!isset($params['component_mode'])) {
      $params['component_mode'] = CRM_Contact_BAO_Query::MODE_CONTACTS;
    }
    $modeValue = CRM_Contact_Form_Search::getModeValue($params['component_mode']);
    return [
      CRM_Core_Action::VIEW => [
        'name' => $modeValue['selectorLabel'],
        'url' => 'civicrm/group/search',
        'qs' => 'reset=1&force=1&context=smog&gid=%%id%%&component_mode=' . $params['component_mode'],
        'title' => ts('Group Contacts'),
        'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::VIEW),
      ],
      CRM_Core_Action::UPDATE => [
        'name' => ts('Settings'),
        'url' => 'civicrm/group/edit',
        'qs' => 'reset=1&action=update&id=%%id%%',
        'title' => ts('Edit Group'),
        'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
      ],
      CRM_Core_Action::DISABLE => [
        'name' => ts('Disable'),
        'ref' => 'crm-enable-disable',
        'title' => ts('Disable Group'),
        'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DISABLE),
      ],
      CRM_Core_Action::ENABLE => [
        'name' => ts('Enable'),
        'ref' => 'crm-enable-disable',
        'title' => ts('Enable Group'),
        'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::ENABLE),
      ],
      CRM_Core_Action::DELETE => [
        'name' => ts('Delete'),
        'url' => 'civicrm/group/edit',
        'qs' => 'reset=1&action=delete&id=%%id%%',
        'title' => ts('Delete Group'),
        'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
      ],
    ];
  }

  /**
   * @param $whereClause
   * @param array $whereParams
   *
   * @return string
   */
  public function pagerAtoZ($whereClause, $whereParams) {
    $query = "
        SELECT DISTINCT UPPER(LEFT(`groups`.title, 1)) as sort_name
        FROM  civicrm_group `groups`
        WHERE $whereClause
        ORDER BY LEFT(`groups`.title, 1)
            ";
    $dao = CRM_Core_DAO::executeQuery($query, $whereParams);

    return CRM_Utils_PagerAToZ::getAToZBar($dao, $this->_sortByCharacter, TRUE);
  }

  /**
   * Assign Test Value.
   *
   * @param string $fieldName
   * @param array $fieldDef
   * @param int $counter
   */
  protected function assignTestValue($fieldName, &$fieldDef, $counter) {
    if ($fieldName == 'children' || $fieldName == 'parents') {
      $this->{$fieldName} = "NULL";
    }
    else {
      parent::assignTestValue($fieldName, $fieldDef, $counter);
    }
  }

  /**
   * Get child group ids
   *
   * @param array $regularGroupIDs
   *    Parent Group IDs
   *
   * @return array
   */
  public static function getChildGroupIds($regularGroupIDs) {
    $childGroupIDs = [];

    foreach ((array) $regularGroupIDs as $regularGroupID) {
      // temporary store the child group ID(s) of regular group identified by $id,
      //   later merge with main child group array
      $tempChildGroupIDs = [];
      // check that the regular group has any child group, if not then continue
      if ($childrenFound = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $regularGroupID, 'children')) {
        $tempChildGroupIDs[] = $childrenFound;
      }
      else {
        continue;
      }
      // as civicrm_group.children stores multiple group IDs in comma imploded string format,
      //   so we need to convert it into array of child group IDs first
      $tempChildGroupIDs = explode(',', implode(',', $tempChildGroupIDs));
      $childGroupIDs = array_merge($childGroupIDs, $tempChildGroupIDs);
      // recursively fetch the child group IDs
      while (count($tempChildGroupIDs)) {
        $tempChildGroupIDs = self::getChildGroupIds($tempChildGroupIDs);
        if (count($tempChildGroupIDs)) {
          $childGroupIDs = array_merge($childGroupIDs, $tempChildGroupIDs);
        }
      }
    }

    return $childGroupIDs;
  }

  /**
   * Check parent groups and filter out the disabled ones.
   *
   * @param array $parentArray
   *   Array of group Ids.
   *
   * @return int
   */
  public static function filterActiveGroups($parentArray) {
    if (count($parentArray) >= 1) {
      $result = civicrm_api3('Group', 'get', [
        'id' => ['IN' => $parentArray],
        'is_active' => TRUE,
        'return' => 'id',
      ]);
      $activeParentGroupIDs = CRM_Utils_Array::collect('id', $result['values']);
      foreach ($parentArray as $key => $groupID) {
        if (!array_key_exists($groupID, $activeParentGroupIDs)) {
          unset($parentArray[$key]);
        }
      }
    }

    return reset($parentArray);
  }

  /**
   * Check write access.
   * @see \Civi\Api4\Utils\CoreUtil::checkAccessRecord
   */
  public static function self_civi_api4_authorizeRecord(AuthorizeRecordEvent $e): void {
    $record = $e->getRecord();
    $userID = $e->getUserID();
    // Check create permission (all other actions just rely on gatekeeper permissions)
    if ($e->getActionName() === 'create') {
      $groupType = (array) ($record['group_type:name'] ?? []);
      // If not already in :name format, transform to name
      foreach ((array) ($record['group_type'] ?? []) as $typeId) {
        $groupType[] = CRM_Core_PseudoConstant::getName(self::class, 'group_type', $typeId);
      }
      if ($groupType === ['Mailing List']) {
        // If it's only a Mailing List, edit groups OR create mailings will work
        $e->setAuthorized(CRM_Core_Permission::check(['access CiviCRM', ['edit groups', 'access CiviMail', 'create mailings']], $userID));
      }
      else {
        $e->setAuthorized(CRM_Core_Permission::check(['access CiviCRM', 'edit groups'], $userID));
      }
    }
  }

  /**
   * Callback for hook_civicrm_post().
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function self_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    /** @var CRM_Contact_DAO_Group $group */
    $group = $event->object;
    if (in_array($event->action, ['create', 'edit'])) {
      $params = $event->params;
      if ($params['name_empty']) {
        $group->name = substr($group->name, 0, -4) . "_{$group->id}";

        // in order to avoid race condition passing $hook = FALSE
        $group->save(FALSE);
      }

      // Process group nesting
      // first deal with removed parents
      if ($params['parents_param_provided'] && !empty($params['current_parents'])) {
        foreach ($params['current_parents'] as $parentGroupId) {
          // no more parents or not in the new list, let's remove
          if (empty($params['parents']) || !in_array($parentGroupId, $params['parents'])) {
            CRM_Contact_BAO_GroupNesting::remove($parentGroupId, $params['id']);
          }
        }
      }

      // then add missing parents
      if (array_key_exists('parents', $params) && !CRM_Utils_System::isNull($params['parents'])) {
        foreach ((array) $params['parents'] as $parentId) {
          if ($parentId && !CRM_Contact_BAO_GroupNesting::isParentChild($parentId, $group->id)) {
            CRM_Contact_BAO_GroupNesting::add($parentId, $group->id);
          }
        }
      }

      // refresh cache if parents param was provided
      if ($params['parents_param_provided'] || !empty($params['parents'])) {
        CRM_Contact_BAO_GroupNestingCache::update();
      }

      // update group contact cache for all parent groups
      $parentIds = CRM_Contact_BAO_GroupNesting::getParentGroupIds($group->id);
      foreach ($parentIds as $parentId) {
        CRM_Contact_BAO_GroupContactCache::invalidateGroupContactCache($parentId);
      }

      if (!empty($params['organization_id'])) {
        if ($params['organization_id'] == 'null') {
          $groupOrganization = [];
          CRM_Contact_BAO_GroupOrganization::retrieve($group->id, $groupOrganization);
          if (!empty($groupOrganization['group_organization'])) {
            CRM_Contact_BAO_GroupOrganization::deleteGroupOrganization($groupOrganization['group_organization']);
          }
        }
        else {
          // dev/core#382 Keeping the id here can cause db errors as it tries to update the wrong record in the Organization table
          $groupOrg = [
            'group_id' => $group->id,
            'organization_id' => $params['organization_id'],
          ];
          CRM_Contact_BAO_GroupOrganization::add($groupOrg);
        }
      }

      self::flushCaches();
      CRM_Contact_BAO_GroupContactCache::invalidateGroupContactCache($group->id);

      $recentOther = [];
      if (CRM_Core_Permission::check('edit groups')) {
        $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/group/edit', 'reset=1&action=update&id=' . $group->id);
        // currently same permission we are using for delete a group
        $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/group/edit', 'reset=1&action=delete&id=' . $group->id);
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
    }
  }

  /**
   * Callback for hook_civicrm_pre().
   * @param \Civi\Core\Event\PreEvent $event
   * @throws CRM_Core_Exception
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event): void {
    if (in_array($event->action, ['create', 'edit'])) {
      $event->params += [
        'parents_param_provided' => array_key_exists('parents', $event->params),
        'name_empty' => ($event->action == 'create' && empty($event->params['name'])),
        'group_type' => NULL,
        'parents' => NULL,
      ];

      // convert params if array type
      if (!CRM_Utils_System::isNull($event->params['group_type']) || is_array($event->params['group_type'])) {
        $event->params['group_type'] = CRM_Utils_Array::convertCheckboxFormatToArray((array) $event->params['group_type']);
      }

      // CRM-19068.
      // Validate parents parameter when creating group.
      if (!CRM_Utils_System::isNull($event->params['parents'])) {
        $parents = is_array($event->params['parents']) ? array_keys($event->params['parents']) : (array) $event->params['parents'];
        foreach ($parents as $parent) {
          CRM_Utils_Type::validate($parent, 'Integer');
        }
      }
    }

    if ($event->action === 'edit') {
      $event->params['modified_id'] ??= CRM_Core_Session::getLoggedInContactID();

      // If title isn't specified, retrieve it because we use it later, e.g.
      // for RecentItems. But note we use array_key_exists not isset or empty
      // since otherwise there would be no way to blank out an existing title.
      // I'm not sure what the use-case is for that, but you're allowed to do it
      // currently.
      if (!array_key_exists('title', $event->params)) {
        try {
          $event->params['title'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $event->params['id'], 'title', 'id');
        }
        catch (CRM_Core_Exception $groupTitleException) {
          // don't set title
        }
      }

      // dev/core#287 Disable child groups if all parents are disabled.
      $allChildGroupIds = self::getChildGroupIds($event->params['id']);
      foreach ($allChildGroupIds as $childKey => $childValue) {
        $parentIds = CRM_Contact_BAO_GroupNesting::getParentGroupIds($childValue);
        $activeParentsCount = civicrm_api3('Group', 'getcount', [
          'id' => ['IN' => $parentIds],
          'is_active' => 1,
        ]);
        if (count($parentIds) >= 1 && $activeParentsCount <= 1) {
          self::setIsActive($childValue, (int) ($event->params['is_active'] ?? 1));
        }
      }

      // get current parents for removal if not in the list anymore
      $parents = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $event->params['id'], 'parents');
      if (!empty($parents)) {
        $event->params['current_parents'] = $parents ? explode(',', $parents) : [];
      }

      if ($event->params['parents_param_provided']) {
        $event->params['parents'] = CRM_Utils_Array::convertCheckboxFormatToArray((array) $event->params['parents']);
        // failsafe: forbid adding itself as parent
        if (($key = array_search($event->params['id'], $event->params['parents'])) !== FALSE) {
          unset($event->params['parents'][$key]);
        }
      }
    }
  }

}
