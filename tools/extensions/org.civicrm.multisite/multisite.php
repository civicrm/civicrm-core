<?php

require_once 'multisite.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function multisite_civicrm_config(&$config) {
  _multisite_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function multisite_civicrm_xmlMenu(&$files) {
  _multisite_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function multisite_civicrm_install() {
  return _multisite_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function multisite_civicrm_uninstall() {
  return _multisite_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function multisite_civicrm_enable() {
  return _multisite_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function multisite_civicrm_disable() {
  return _multisite_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function multisite_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _multisite_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function multisite_civicrm_managed(&$entities) {
  return _multisite_civix_civicrm_managed($entities);
}

/**
 *  * Implementation of hook_civicrm_post
 *
 * Current implemtation assumes shared user table for all sites -
 * a more sophisticated version will be able to cope with a combination of shared user tables
 * and separate user tables
 *
 * @param string $op
 * @param string $objectName
 * @param integer $objectId
 * @param object $objectRef
 */
function multisite_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  if ($op == 'edit' && $objectName == 'UFMatch') {
    static $updating = FALSE;
    if ($updating) {
      return; // prevent recursion
    }
    $updating = TRUE;
    $ufs = civicrm_api('uf_match', 'get', array(
      'version' => 3,
      'contact_id' => $objectRef->contact_id,
      'uf_id' => $objectRef->uf_id,
      'id' => array(
        '!=' => $objectRef->id
      )
    ));
    foreach ($ufs['values'] as $ufMatch) {
      civicrm_api('UFMatch', 'create', array(
        'version' => 3,
        'id' => $ufMatch['id'],
        'uf_name' => $objectRef->uf_name
      ));
    }
  }
}
/**
 *
 * @param unknown_type $type
 * @param unknown_type $contactID
 * @param unknown_type $tableName
 * @param unknown_type $allGroups
 * @param unknown_type $currentGroups
 */
function multisite_civicrm_aclGroup($type, $contactID, $tableName, &$allGroups, &$currentGroups) {
  // only process saved search
  if ($tableName != 'civicrm_saved_search') {
    return;
  }
  $groupID = _multisite_get_domain_group();
  if(!$groupID){
    return;
  }
  $currentGroups = _multisite_get_all_child_groups($groupID, FALSE);
}

/**
 *
 * @param string $type
 * @param array $tables tables to be included in query
 * @param array $whereTables tables required for where portion of query
 * @param integer $contactID contact for whom where clause is being composed
 * @param string $where Where clause The completed clause will look like
 *   (multisiteGroupTable.group_id IN ("1,2,3,4,5") AND multisiteGroupTable.status IN ('Added') AND contact_a.is_deleted = 0)
 *   where the child groups are groups the contact is potentially a member of
 *
 */
function multisite_civicrm_aclWhereClause($type, &$tables, &$whereTables, &$contactID, &$where) {
  if (! $contactID) {
    return;
  }
  if(!_multisite_add_permissions($type)){
    return;
  }
  $groupID = _multisite_get_domain_group();
  if(!$groupID){
    return;
  }
  $childOrgs = _multisite_get_all_child_groups($groupID);

  if (! empty($childOrgs)) {
    $groupTable = 'civicrm_group_contact';
    $tables[$groupTable] = $whereTables[$groupTable] = "LEFT JOIN {$groupTable} multisiteGroupTable ON contact_a.id = multisiteGroupTable.contact_id";

    $where = "(multisiteGroupTable.group_id IN (" . implode(',', $childOrgs) . ") AND multisiteGroupTable.status IN ('Added') AND contact_a.is_deleted = 0)";
  }
}
/**
 *
 * @param array $permissions
 */
function multisite_civicrm_permissions(&$permissions){
  $prefix = ts('CiviCRM Multisite') . ': ';
  $permissions = $permissions + array(
    'view all contacts in domain' => $prefix . ts('view all contacts in domain'),
    'edit all contacts in domain' => $prefix . ts('edit all contacts in domain'),
  );
}

/**
 * Implementation of hook_civicrm_config
 */
function multisite_civicrm_alterSettingsFolders(&$metaDataFolders = NULL){
  _multisite_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Get all groups that are children of the parent group
 * (iterate through all levels)
 *
 * @param integer $groupID
 * @param boolean $includeParent
 * @return array:child groups
 */
function _multisite_get_all_child_groups($groupID, $includeParent = TRUE) {
  static $_cache = array();

  if (! array_key_exists($groupID, $_cache)) {
    $childGroups = &CRM_Core_BAO_Cache::getItem('descendant groups for an org', $groupID);

    if (empty($childGroups)) {
      $childGroups = array();

      $query = "
SELECT children
FROM   civicrm_group
WHERE  children IS NOT NULL
AND    id IN ";

      if (! is_array($groupID)) {
        $groupIDs = array(
          $groupID
        );
      }

      while (! empty($groupIDs)) {
        $groupIDString = implode(',', $groupIDs);

        $realQuery = $query . " ( $groupIDString )";
        $dao = CRM_Core_DAO::executeQuery($realQuery);
        $groupIDs = array();
        while ($dao->fetch()) {
          if ($dao->children) {
            $childIDs = explode(',', $dao->children);
            foreach ($childIDs as $childID) {
              if (! array_key_exists($childID, $childGroups)) {
                $childGroups[$childID] = 1;
                $groupIDs[] = $childID;
              }
            }
          }
        }
      }

      CRM_Core_BAO_Cache::setItem($childGroups, 'descendant groups for an org', $groupID);
    }
    $_cache[$groupID] = $childGroups;
  }

  if ($includeParent || CRM_Core_Permission::check('administer Multiple Organizations')) {
    return array_keys(array(
      $groupID => 1
    ) + $_cache[$groupID]);
  }
  else {
    return array_keys($_cache[$groupID]);
  }
}

/**
 *
 * @param int $permission
 *
 * @return NULL|integer $groupID
 */
function _multisite_get_domain_group($permission = 1) {
    $groupID = CRM_Core_BAO_Domain::getGroupId();
    if(empty($groupID) || !is_numeric($groupID)){
      /* domain group not defined - we could let people know but
       * it is acceptable for some domains not to be in the multisite
      * so should probably check enabled before we spring an error
      */
      return NULL;
    }
    // We will check for the possiblility of the acl_enabled setting being deliberately set to 0
    if($permission){
     $aclsEnabled = civicrm_api('setting', 'getvalue', array(
      'version' => 3,
      'name' => 'multisite_acl_enabled',
      'group' => 'Multi Site Preferences')
     );
     if(is_numeric($aclsEnabled) && !$aclsEnabled){
       return NULL;
     }
    }

    return $groupID;
  }

/**
 * Should we be adding ACLs in this instance. If we don't add them the user
 * will not be able to see anything. We check if the install has the permissions
 * hook implemented correctly & if so only allow view & edit based on those.
 *
 * Otherwise all users get these permissions added (4.2 vs 4.3 / other CMS issues)
 *
 * @param integer $type type of operation
 *
 * @return bool
 */
  function _multisite_add_permissions($type){
    $hookclass = 'CRM_Utils_Hook';
    if(!method_exists($hookclass, 'permissions')){
      // ie. unpatched 4.2 so we can't check for extra declared permissions
      // & default to applying this to all
      return TRUE;
    }
    // extra check to make sure that hook is properly implemented
    // if not we won't check for it. NB view all contacts in domain is enough checking
    $declaredPermissions = CRM_Core_Permission::getCorePermissions();
    if(!array_key_exists('view all contacts in domain', $declaredPermissions)){
      drupal_set_message('here');
      return TRUE;
    }

    if(CRM_ACL_BAO_ACL::matchType($type, 'View') &&
      CRM_Core_Permission::check('view all contacts in domain')) {
      return TRUE;
    }

    if(CRM_ACL_BAO_ACL::matchType($type, 'Edit') &&
      CRM_Core_Permission::check('edit all contacts in domain')) {
      return TRUE;
    }
    return FALSE;
  }

