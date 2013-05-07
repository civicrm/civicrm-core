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
 *
 */
class CRM_Core_Permission_DrupalBase extends CRM_Core_Permission_Base {

  /**
   * is this user someone with access for the entire system
   *
   * @var boolean
   */
  protected $_viewAdminUser = FALSE;
  protected $_editAdminUser = FALSE;

  /**
   * am in in view permission or edit permission?
   * @var boolean
   */
  protected $_viewPermission = FALSE;
  protected $_editPermission = FALSE;

  /**
   * the current set of permissioned groups for the user
   *
   * @var array
   */
  protected $_viewPermissionedGroups;
  protected $_editPermissionedGroups;

  /**
   * Get all groups from database, filtered by permissions
   * for this user
   *
   * @param string $groupType     type of group(Access/Mailing)
   * @param boolen $excludeHidden exclude hidden groups.
   *
   * @access public
   *
   * @return array - array reference of all groups.
   *
   */
  public function group($groupType = NULL, $excludeHidden = TRUE) {
    if (!isset($this->_viewPermissionedGroups)) {
      $this->_viewPermissionedGroups = $this->_editPermissionedGroups = array();
    }

    $groupKey = $groupType ? $groupType : 'all';

    if (!isset($this->_viewPermissionedGroups[$groupKey])) {
      $this->_viewPermissionedGroups[$groupKey] = $this->_editPermissionedGroups[$groupKey] = array();

      $groups = CRM_Core_PseudoConstant::allGroup($groupType, $excludeHidden);

      if ($this->check('edit all contacts')) {
        // this is the most powerful permission, so we return
        // immediately rather than dilute it further
        $this->_editAdminUser = $this->_viewAdminUser = TRUE;
        $this->_editPermission = $this->_viewPermission = TRUE;
        $this->_editPermissionedGroups[$groupKey] = $groups;
        $this->_viewPermissionedGroups[$groupKey] = $groups;
        return $this->_viewPermissionedGroups[$groupKey];
      }
      elseif ($this->check('view all contacts')) {
        $this->_viewAdminUser = TRUE;
        $this->_viewPermission = TRUE;
        $this->_viewPermissionedGroups[$groupKey] = $groups;
      }


      $ids = CRM_ACL_API::group(CRM_Core_Permission::VIEW, NULL, 'civicrm_saved_search', $groups);
      foreach (array_values($ids) as $id) {
        $title = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $id, 'title');
        $this->_viewPermissionedGroups[$groupKey][$id] = $title;
        $this->_viewPermission = TRUE;
      }

      $ids = CRM_ACL_API::group(CRM_Core_Permission::EDIT, NULL, 'civicrm_saved_search', $groups);
      foreach (array_values($ids) as $id) {
        $title = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $id, 'title');
        $this->_editPermissionedGroups[$groupKey][$id] = $title;
        $this->_viewPermissionedGroups[$groupKey][$id] = $title;
        $this->_editPermission = TRUE;
        $this->_viewPermission = TRUE;
      }
    }

    return $this->_viewPermissionedGroups[$groupKey];
  }

  /**
   * Get group clause for this user. The group Clause filters the
   * list of groups that the user is permitted to see in a group listing.
   * For example it will filter both the list on the 'Manage Groups' page
   * and on the contact 'Groups' tab
   *
   * the aclGroup hook & configured ACLs contribute to this data.
   * If the contact is allowed to see all contacts the function will return  ( 1 )
   *
   * @todo the history of this function is that there was some confusion as to
   * whether it was filtering contacts or groups & some cruft may remain
   *
   * @param int $type the type of permission needed
   * @param  array $tables (reference) add the tables that are needed for the select clause
   * @param  array $whereTables (reference) add the tables that are needed for the where clause
   *
   * @return string the clause to add to the query retrieving viewable groups
   * @access public
   */
  public function groupClause($type, &$tables, &$whereTables) {
    if (!isset($this->_viewPermissionedGroups)) {
      $this->group();
    }

    // we basically get all the groups here
    $groupKey = 'all';
    if ($type == CRM_Core_Permission::EDIT) {
      if ($this->_editAdminUser) {
        $clause = ' ( 1 ) ';
      }
      elseif (empty($this->_editPermissionedGroups[$groupKey])) {
        $clause = ' ( 0 ) ';
      }
      else {
        $clauses = array();
        $groups = implode(', ', $this->_editPermissionedGroups[$groupKey]);
        $clauses[] = ' ( civicrm_group_contact.group_id IN ( ' . implode(', ', array_keys($this->_editPermissionedGroups[$groupKey])) . " ) AND civicrm_group_contact.status = 'Added' ) ";
        $tables['civicrm_group_contact'] = 1;
        $whereTables['civicrm_group_contact'] = 1;

        // foreach group that is potentially a saved search, add the saved search clause
        foreach (array_keys($this->_editPermissionedGroups[$groupKey]) as $id) {
          $group = new CRM_Contact_DAO_Group();
          $group->id = $id;
          if ($group->find(TRUE) && $group->saved_search_id) {
            $clause = CRM_Contact_BAO_SavedSearch::whereClause($group->saved_search_id,
                $tables,
                $whereTables
            );
            if (trim($clause)) {
              $clauses[] = $clause;
            }
          }
        }
        $clause = ' ( ' . implode(' OR ', $clauses) . ' ) ';
      }
    }
    else {
      if ($this->_viewAdminUser) {
        $clause = ' ( 1 ) ';
      }
      elseif (empty($this->_viewPermissionedGroups[$groupKey])) {
        $clause = ' ( 0 ) ';
      }
      else {
        $clauses = array();
        $groups = implode(', ', $this->_viewPermissionedGroups[$groupKey]);
        $clauses[] = ' civicrm_group.id IN (' . implode(', ', array_keys($this->_viewPermissionedGroups[$groupKey])) . " )  ";
        $tables['civicrm_group'] = 1;
        $whereTables['civicrm_group'] = 1;
        $clause = ' ( ' . implode(' OR ', $clauses) . ' ) ';
      }
    }

    return $clause;
  }

  /**
   * get the current permission of this user
   *
   * @return string the permission of the user (edit or view or null)
   */
  public function getPermission() {
    $this->group();

    if ($this->_editPermission) {
      return CRM_Core_Permission::EDIT;
    }
    elseif ($this->_viewPermission) {
      return CRM_Core_Permission::VIEW;
    }
    return NULL;
  }

  /**
   * given a permission string, check for access requirements
   *
   * @param string $str the permission to check
   *
   * @return boolean true if yes, else false
   * @access public
   */
  function check($str, $contactID = NULL) {
    if (function_exists('user_access')) {
      return user_access($str) ? TRUE : FALSE;
    }
    return TRUE;
  }

  function getContactEmails($uids) {
    if (empty($uids)) {
      return '';
    }
    $uidString = implode(',', $uids);
    $sql = "
    SELECT     e.email
    FROM       civicrm_contact c
    INNER JOIN civicrm_email e     ON ( c.id = e.contact_id AND e.is_primary = 1 )
    INNER JOIN civicrm_uf_match uf ON ( c.id = uf.contact_id )
    WHERE      c.is_deceased = 0
    AND        c.is_deleted  = 0
    AND        uf.uf_id IN ( $uidString )
    ";

    $dao = CRM_Core_DAO::executeQuery($sql);

    $emails = array();
    while ($dao->fetch()) {
    $emails[] = $dao->email;
    }

    return implode(', ', $emails);
  }
}