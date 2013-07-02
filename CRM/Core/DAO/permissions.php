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
 * Decide what permissions to check for an api call
 * The contact must have all of the returned permissions for the api call to be allowed
 *
 * @param $entity: (str) api entity
 * @param $action: (str) api action
 * @param $params: (array) api params
 *
 * @return array of permissions to check for this entity-action combo
 */
function _civicrm_api3_permissions($entity, $action, &$params) {
  $entity = _civicrm_api_get_entity_name_from_camel($entity);
  $action = strtolower($action);

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
    'update' => array(
      'access CiviCRM',
      'edit all contacts',
    ),
    'getquick' => array(
      'access CiviCRM',
    ),
  );

  // Contact-related data permissions
  $permissions['address'] = array(
    'get' => array(
      'access CiviCRM',
      'view all contacts',
    ),
    'delete' => array(
      'access CiviCRM',
      'delete contacts',
    ),
    'default' => array(
      'access CiviCRM',
      'edit all contacts',
    ),
  );
  $permissions['email'] = $permissions['address'];
  $permissions['phone'] = $permissions['address'];
  $permissions['website'] = $permissions['address'];
  $permissions['im'] = $permissions['address'];
  $permissions['loc_block'] = $permissions['address'];
  $permissions['entity_tag'] = $permissions['address'];
  $permissions['note'] = $permissions['address'];

  // Activity permissions
  $permissions['activity'] = array(
    'delete' => array(
      'access CiviCRM',
      'delete activities',
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
      'access CiviCRM',
      'access all cases and activities',
    ),
  );

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
  $permissions['group_contact'] = $permissions['group'];
  $permissions['group_nesting'] = $permissions['group'];
  $permissions['group_organization'] = $permissions['group'];

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
  $permissions['uf_group'] = array(
    'get' => array(
      'access CiviCRM',
    ),
  );
  $permissions['uf_field'] = $permissions['uf_group'];

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
    // based on the heuristic that 'delete' tends to be the most closesly guarded
    // of the necessary permissions.
    $action = 'delete';
  }
  elseif ($action == 'setvalue' || $snippet == 'upd') {
    $action = 'update';
  }
  elseif ($action == 'getfields' || $action == 'getspec' || $action == 'getoptions') {
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
