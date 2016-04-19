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
 * Decide what permissions to check for an api call
 *
 * @param $entity : (str) api entity
 * @param $action : (str) api action
 * @param $params : (array) api params
 *
 * @return array
 *   Array of permissions to check for this entity-action combo
 */
function _civicrm_api3_permissions($entity, $action, &$params) {
  // FIXME: Lowercase entity_names are nonstandard but difficult to fix here
  // because this function invokes hook_civicrm_alterAPIPermissions
  $entity = _civicrm_api_get_entity_name_from_camel($entity);

  /**
   * @var array of permissions
   *
   * For each entity, we declare an array of permissions required for each action
   * The action is the array key, possible values:
   *  * create: applies to create (with no id in params)
   *  * update: applies to update, setvalue, create (with id in params)
   *  * get: applies to getcount, getsingle, getvalue and other gets
   *  * delete: applies to delete, replace
   *  * meta: applies to getfields, getoptions, getspec
   *  * default: catch-all for anything not declared
   *
   *  Note: some APIs declare other actions as well
   *
   * Permissions should use arrays for AND and arrays of arrays for OR
   * @see CRM_Core_Permission::check for more documentation
   */
  $permissions = array();

  // These are the default permissions - if any entity does not declare permissions for a given action,
  // (or the entity does not declare permissions at all) - then the action will be used from here
  $permissions['default'] = array(
    // applies to getfields, getoptions, etc.
    'meta' => array('access CiviCRM'),
    // catch-all, applies to create, get, delete, etc.
    // If an entity declares it's own 'default' action it will override this one
    'default' => array('administer CiviCRM'),
  );

  // Note: Additional permissions in DynamicFKAuthorization
  $permissions['attachment'] = array(
    'default' => array(
      array('access CiviCRM', 'access AJAX API'),
    ),
  );

  // Contact permissions
  $permissions['contact'] = array(
    'create' => array(
      'access CiviCRM',
      'add contacts',
    ),
    'delete' => array(
      'access CiviCRM',
      'delete contacts',
    ),
    // managed by query object
    'get' => array(),
    // managed by _civicrm_api3_check_edit_permissions
    'update' => array(),
    'getquick' => array(
      array('access CiviCRM', 'access AJAX API'),
    ),
  );

  // CRM-16963 - Permissions for country.
  $permissions['country'] = array(
    'get' => array(
      'access CiviCRM',
    ),
    'default' => array(
      'administer CiviCRM',
    ),
  );

  // Contact-related data permissions.
  $permissions['address'] = array(
    // get is managed by BAO::addSelectWhereClause
    // create/delete are managed by _civicrm_api3_check_edit_permissions
    'default' => array(),
  );
  $permissions['email'] = $permissions['address'];
  $permissions['phone'] = $permissions['address'];
  $permissions['website'] = $permissions['address'];
  $permissions['im'] = $permissions['address'];

  // @todo - implement CRM_Core_BAO_EntityTag::addSelectWhereClause and remove this heavy-handed restriction
  $permissions['entity_tag'] = array(
    'get' => array('access CiviCRM', 'view all contacts'),
    'default' => array('access CiviCRM', 'edit all contacts'),
  );
  // @todo - ditto
  $permissions['note'] = $permissions['entity_tag'];

  // CRM-17350 - entity_tag ACL permissions are checked at the BAO level
  $permissions['entity_tag'] = array(
    'get' => array(
      'access CiviCRM',
      'view all contacts',
    ),
    'default' => array(
      'access CiviCRM',
    ),
  );

  // Allow non-admins to get and create tags to support tagset widget
  // Delete is still reserved for admins
  $permissions['tag'] = array(
    'get' => array('access CiviCRM'),
    'create' => array('access CiviCRM'),
    'update' => array('access CiviCRM'),
  );

  //relationship permissions
  $permissions['relationship'] = array(
    // get is managed by BAO::addSelectWhereClause
    'get' => array(),
    'delete' => array(
      'access CiviCRM',
      'edit all contacts',
    ),
    'default' => array(
      'access CiviCRM',
      'edit all contacts',
    ),
  );

  // CRM-17741 - Permissions for RelationshipType.
  $permissions['relationship_type'] = array(
    'get' => array(
      'access CiviCRM',
    ),
    'default' => array(
      'administer CiviCRM',
    ),
  );

  // Activity permissions
  $permissions['activity'] = array(
    'delete' => array(
      'access CiviCRM',
      'delete activities',
    ),
    'get' => array(
      'access CiviCRM',
      // Note that view all activities is also required within the api
      // if the id is not passed in. Where the id is passed in the activity
      // specific check functions are used and tested.
    ),
    'default' => array(
      'access CiviCRM',
      'view all activities',
    ),
  );

  // Case permissions
  $permissions['case'] = array(
    'create' => array(
      'access CiviCRM',
      'add cases',
    ),
    'delete' => array(
      'access CiviCRM',
      'delete in CiviCase',
    ),
    'default' => array(
      // At minimum the user needs one of the following. Finer-grained access is controlled by CRM_Case_BAO_Case::addSelectWhereClause
      array('access my cases and activities', 'access all cases and activities'),
    ),
  );
  $permissions['case_contact'] = $permissions['case'];

  $permissions['case_type'] = array(
    'default' => array('administer CiviCase'),
    'get' => array(
      // nested array = OR
      array('access my cases and activities', 'access all cases and activities'),
    ),
  );

  // Campaign permissions
  $permissions['campaign'] = array(
    'get' => array('access CiviCRM'),
    'default' => array(
      // nested array = OR
      array('administer CiviCampaign', 'manage campaign')
    ),
  );
  $permissions['survey'] = $permissions['campaign'];

  // Financial permissions
  $permissions['contribution'] = array(
    'get' => array(
      'access CiviCRM',
      'access CiviContribute',
    ),
    'delete' => array(
      'access CiviCRM',
      'access CiviContribute',
      'delete in CiviContribute',
    ),
    'completetransaction' => array(
      'edit contributions',
    ),
    'default' => array(
      'access CiviCRM',
      'access CiviContribute',
      'edit contributions',
    ),
  );
  $permissions['line_item'] = $permissions['contribution'];

  // Payment permissions
  $permissions['payment'] = array(
    'get' => array(
      'access CiviCRM',
      'access CiviContribute',
    ),
    'delete' => array(
      'access CiviCRM',
      'access CiviContribute',
      'delete in CiviContribute',
    ),
    'cancel' => array(
      'access CiviCRM',
      'access CiviContribute',
      'edit contributions',
    ),
    'create' => array(
      'access CiviCRM',
      'access CiviContribute',
      'edit contributions',
    ),
    'default' => array(
      'access CiviCRM',
      'access CiviContribute',
      'edit contributions',
    ),
  );

  // Custom field permissions
  $permissions['custom_field'] = array(
    'default' => array(
      'administer CiviCRM',
      'access all custom data',
    ),
  );
  $permissions['custom_group'] = $permissions['custom_field'];

  // Event permissions
  $permissions['event'] = array(
    'create' => array(
      'access CiviCRM',
      'access CiviEvent',
      'edit all events',
    ),
    'delete' => array(
      'access CiviCRM',
      'access CiviEvent',
      'delete in CiviEvent',
    ),
    'get' => array(
      'access CiviCRM',
      'access CiviEvent',
      'view event info',
    ),
    'update' => array(
      'access CiviCRM',
      'access CiviEvent',
      'edit all events',
    ),
  );
  // Loc block is only used for events
  $permissions['loc_block'] = $permissions['event'];

  // File permissions
  $permissions['file'] = array(
    'default' => array(
      'access CiviCRM',
      'access uploaded files',
    ),
  );
  $permissions['files_by_entity'] = $permissions['file'];

  // Group permissions
  $permissions['group'] = array(
    'get' => array(
      'access CiviCRM',
    ),
    'default' => array(
      'access CiviCRM',
      'edit groups',
    ),
  );

  $permissions['group_nesting'] = $permissions['group'];
  $permissions['group_organization'] = $permissions['group'];

  //Group Contact permission
  $permissions['group_contact'] = array(
    'get' => array(
      'access CiviCRM',
    ),
    'default' => array(
      'access CiviCRM',
      'edit all contacts',
    ),
  );

  // CiviMail Permissions
  $civiMailBasePerms = array(
    // To get/preview/update, one must have least one of these perms:
    // Mailing API implementations enforce nuances of create/approve/schedule permissions.
    'access CiviMail',
    'create mailings',
    'schedule mailings',
    'approve mailings',
  );
  $permissions['mailing'] = array(
    'get' => array(
      'access CiviCRM',
      $civiMailBasePerms,
    ),
    'delete' => array(
      'access CiviCRM',
      $civiMailBasePerms,
      'delete in CiviMail',
    ),
    'submit' => array(
      'access CiviCRM',
      array('access CiviMail', 'schedule mailings'),
    ),
    'default' => array(
      'access CiviCRM',
      $civiMailBasePerms,
    ),
  );
  $permissions['mailing_group'] = $permissions['mailing'];
  $permissions['mailing_job'] = $permissions['mailing'];
  $permissions['mailing_recipients'] = $permissions['mailing'];

  $permissions['mailing_a_b'] = array(
    'get' => array(
      'access CiviCRM',
      'access CiviMail',
    ),
    'delete' => array(
      'access CiviCRM',
      'access CiviMail',
      'delete in CiviMail',
    ),
    'submit' => array(
      'access CiviCRM',
      array('access CiviMail', 'schedule mailings'),
    ),
    'default' => array(
      'access CiviCRM',
      'access CiviMail',
    ),
  );

  // Membership permissions
  $permissions['membership'] = array(
    'get' => array(
      'access CiviCRM',
      'access CiviMember',
    ),
    'delete' => array(
      'access CiviCRM',
      'access CiviMember',
      'delete in CiviMember',
    ),
    'default' => array(
      'access CiviCRM',
      'access CiviMember',
      'edit memberships',
    ),
  );
  $permissions['membership_status'] = $permissions['membership'];
  $permissions['membership_type'] = $permissions['membership'];
  $permissions['membership_payment'] = array(
    'create' => array(
      'access CiviCRM',
      'access CiviMember',
      'edit memberships',
      'access CiviContribute',
      'edit contributions',
    ),
    'delete' => array(
      'access CiviCRM',
      'access CiviMember',
      'delete in CiviMember',
      'access CiviContribute',
      'delete in CiviContribute',
    ),
    'get' => array(
      'access CiviCRM',
      'access CiviMember',
      'access CiviContribute',
    ),
    'update' => array(
      'access CiviCRM',
      'access CiviMember',
      'edit memberships',
      'access CiviContribute',
      'edit contributions',
    ),
  );

  // Participant permissions
  $permissions['participant'] = array(
    'create' => array(
      'access CiviCRM',
      'access CiviEvent',
      'register for events',
    ),
    'delete' => array(
      'access CiviCRM',
      'access CiviEvent',
      'edit event participants',
    ),
    'get' => array(
      'access CiviCRM',
      'access CiviEvent',
      'view event participants',
    ),
    'update' => array(
      'access CiviCRM',
      'access CiviEvent',
      'edit event participants',
    ),
  );
  $permissions['participant_payment'] = array(
    'create' => array(
      'access CiviCRM',
      'access CiviEvent',
      'register for events',
      'access CiviContribute',
      'edit contributions',
    ),
    'delete' => array(
      'access CiviCRM',
      'access CiviEvent',
      'edit event participants',
      'access CiviContribute',
      'delete in CiviContribute',
    ),
    'get' => array(
      'access CiviCRM',
      'access CiviEvent',
      'view event participants',
      'access CiviContribute',
    ),
    'update' => array(
      'access CiviCRM',
      'access CiviEvent',
      'edit event participants',
      'access CiviContribute',
      'edit contributions',
    ),
  );

  // Pledge permissions
  $permissions['pledge'] = array(
    'create' => array(
      'access CiviCRM',
      'access CiviPledge',
      'edit pledges',
    ),
    'delete' => array(
      'access CiviCRM',
      'access CiviPledge',
      'delete in CiviPledge',
    ),
    'get' => array(
      'access CiviCRM',
      'access CiviPledge',
    ),
    'update' => array(
      'access CiviCRM',
      'access CiviPledge',
      'edit pledges',
    ),
  );

  //CRM-16777: Disable schedule reminder for user that have 'edit all events' and 'administer CiviCRM' permission.
  $permissions['action_schedule'] = array(
    'update' => array(
      array(
        'access CiviCRM',
        'edit all events',
      ),
    ),
  );

  $permissions['pledge_payment'] = array(
    'create' => array(
      'access CiviCRM',
      'access CiviPledge',
      'edit pledges',
      'access CiviContribute',
      'edit contributions',
    ),
    'delete' => array(
      'access CiviCRM',
      'access CiviPledge',
      'delete in CiviPledge',
      'access CiviContribute',
      'delete in CiviContribute',
    ),
    'get' => array(
      'access CiviCRM',
      'access CiviPledge',
      'access CiviContribute',
    ),
    'update' => array(
      'access CiviCRM',
      'access CiviPledge',
      'edit pledges',
      'access CiviContribute',
      'edit contributions',
    ),
  );

  // Profile permissions
  $permissions['profile'] = array(
    'get' => array(), // the profile will take care of this
  );

  $permissions['uf_group'] = array(
    'create' => array(
      'access CiviCRM',
      array(
        'administer CiviCRM',
        'manage event profiles',
      ),
    ),
    'get' => array(
      'access CiviCRM',
    ),
    'update' => array(
      'access CiviCRM',
      array(
        'administer CiviCRM',
        'manage event profiles',
      ),
    ),
  );
  $permissions['uf_field'] = $permissions['uf_join'] = $permissions['uf_group'];
  $permissions['uf_field']['delete'] = array(
    'access CiviCRM',
    array(
      'administer CiviCRM',
      'manage event profiles',
    ),
  );
  $permissions['option_value'] = $permissions['uf_group'];
  $permissions['option_group'] = $permissions['option_value'];

  $permissions['message_template'] = array(
    'get' => array('access CiviCRM'),
    'create' => array('edit message templates'),
    'update' => array('edit message templates'),
  );

  // Translate 'create' action to 'update' if id is set
  if ($action == 'create' && (!empty($params['id']) || !empty($params[$entity . '_id']))) {
    $action = 'update';
  }

  // let third parties modify the permissions
  CRM_Utils_Hook::alterAPIPermissions($entity, $action, $params, $permissions);

  // Merge permissions for this entity with the defaults
  $perm = CRM_Utils_Array::value($entity, $permissions, array()) + $permissions['default'];

  // Return exact match if permission for this action has been declared
  if (isset($perm[$action])) {
    return $perm[$action];
  }

  // Translate specific actions into their generic equivalents
  $snippet = substr($action, 0, 3);
  if ($action == 'replace' || $snippet == 'del') {
    // 'Replace' is a combination of get+create+update+delete; however, the permissions
    // on each of those will be tested separately at runtime. This is just a sniff-test
    // based on the heuristic that 'delete' tends to be the most closely guarded
    // of the necessary permissions.
    $action = 'delete';
  }
  elseif ($action == 'setvalue' || $snippet == 'upd') {
    $action = 'update';
  }
  elseif ($action == 'getfields' || $action == 'getfield' || $action == 'getspec' || $action == 'getoptions') {
    $action = 'meta';
  }
  elseif ($snippet == 'get') {
    $action = 'get';
  }
  return isset($perm[$action]) ? $perm[$action] : $perm['default'];
}

# FIXME: not sure how to permission the following API 3 calls:
# contribution_transact (make online contributions)
# entity_tag_display
# group_contact_pending
# group_contact_update_status
# mailing_event_bounce
# mailing_event_click
# mailing_event_confirm
# mailing_event_forward
# mailing_event_open
# mailing_event_reply
# mailing_group_event_domain_unsubscribe
# mailing_group_event_resubscribe
# mailing_group_event_subscribe
# mailing_group_event_unsubscribe
# membership_status_calc
# survey_respondant_count
