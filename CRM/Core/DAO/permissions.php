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
  $entity = strtolower($entity);
  $action = strtolower($action);
  $permissions = array();

  $permissions['activity'] = array(
    'delete' => array(
      'access CiviCRM',
      'delete activities',
    ),
    'get' => array(
      'access CiviCRM',
      'view all activities',
    ),
  );
  $permissions['address'] = array(
    'create' => array(
      'access CiviCRM',
      'add contacts',
    ),
    'delete' => array(
      'access CiviCRM',
      'delete contacts',
    ),
    'get' => array(
      'access CiviCRM',
      'view all contacts',
    ),
    'update' => array(
      'access CiviCRM',
      'edit all contacts',
    ),
  );
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
    'getquick' => array('access CiviCRM'),
  );
  $permissions['contribution'] = array(
    'create' => array(
      'access CiviCRM',
      'access CiviContribute',
      'edit contributions',
    ),
    'delete' => array(
      'access CiviCRM',
      'access CiviContribute',
      'delete in CiviContribute',
    ),
    'get' => array(
      'access CiviCRM',
      'access CiviContribute',
    ),
    'update' => array(
      'access CiviCRM',
      'access CiviContribute',
      'edit contributions',
    ),
  );
  $permissions['custom_field'] = array(
    'create' => array(
      'administer CiviCRM',
      'access CiviCRM',
      'access all custom data',
    ),
    'delete' => array(
      'administer CiviCRM',
      'access CiviCRM',
      'access all custom data',
    ),
    'get' => array(
      'administer CiviCRM',
      'access CiviCRM',
      'access all custom data',
    ),
    'update' => array(
      'administer CiviCRM',
      'access CiviCRM',
      'access all custom data',
    ),
  );
  $permissions['custom_group'] = array(
    'create' => array(
      'administer CiviCRM',
      'access CiviCRM',
      'access all custom data',
    ),
    'delete' => array(
      'administer CiviCRM',
      'access CiviCRM',
      'access all custom data',
    ),
    'get' => array(
      'administer CiviCRM',
      'access CiviCRM',
      'access all custom data',
    ),
    'update' => array(
      'administer CiviCRM',
      'access CiviCRM',
      'access all custom data',
    ),
  );
  $permissions['email'] = array(
    'create' => array(
      'access CiviCRM',
      'add contacts',
    ),
    'delete' => array(
      'access CiviCRM',
      'delete contacts',
    ),
    'get' => array(
      'access CiviCRM',
      'view all contacts',
    ),
    'update' => array(
      'access CiviCRM',
      'edit all contacts',
    ),
  );
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
  $permissions['file'] = array(
    'create' => array(
      'access CiviCRM',
      'access uploaded files',
    ),
    'delete' => array(
      'access CiviCRM',
      'access uploaded files',
    ),
    'get' => array(
      'access CiviCRM',
      'access uploaded files',
    ),
    'update' => array(
      'access CiviCRM',
      'access uploaded files',
    ),
  );
  $permissions['files_by_entity'] = array(
    'create' => array(
      'access CiviCRM',
      'access uploaded files',
    ),
    'delete' => array(
      'access CiviCRM',
      'access uploaded files',
    ),
    'get' => array(
      'access CiviCRM',
      'access uploaded files',
    ),
    'update' => array(
      'access CiviCRM',
      'access uploaded files',
    ),
  );
  $permissions['group'] = array(
    'create' => array(
      'access CiviCRM',
      'edit groups',
    ),
    'delete' => array(
      'access CiviCRM',
      'edit groups',
    ),
    'update' => array(
      'access CiviCRM',
      'edit groups',
    ),
  );
  $permissions['group_contact'] = array(
    'create' => array(
      'access CiviCRM',
      'edit groups',
    ),
    'delete' => array(
      'access CiviCRM',
      'edit groups',
    ),
    'update' => array(
      'access CiviCRM',
      'edit groups',
    ),
  );
  $permissions['group_nesting'] = array(
    'create' => array(
      'access CiviCRM',
      'edit groups',
    ),
    'delete' => array(
      'access CiviCRM',
      'edit groups',
    ),
    'update' => array(
      'access CiviCRM',
      'edit groups',
    ),
  );
  $permissions['group_organization'] = array(
    'create' => array(
      'access CiviCRM',
      'edit groups',
    ),
    'delete' => array(
      'access CiviCRM',
      'edit groups',
    ),
    'update' => array(
      'access CiviCRM',
      'edit groups',
    ),
  );
  $permissions['location'] = array(
    'create' => array(
      'access CiviCRM',
      'add contacts',
    ),
    'delete' => array(
      'access CiviCRM',
      'delete contacts',
    ),
    'get' => array(
      'access CiviCRM',
      'view all contacts',
    ),
    'update' => array(
      'access CiviCRM',
      'edit all contacts',
    ),
  );
  $permissions['membership'] = array(
    'create' => array(
      'access CiviCRM',
      'access CiviMember',
      'edit memberships',
    ),
    'delete' => array(
      'access CiviCRM',
      'access CiviMember',
      'delete in CiviMember',
    ),
    'get' => array(
      'access CiviCRM',
      'access CiviMember',
    ),
    'update' => array(
      'access CiviCRM',
      'access CiviMember',
      'edit memberships',
    ),
  );
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
  $permissions['membership_status'] = array(
    'create' => array(
      'access CiviCRM',
      'access CiviMember',
      'edit memberships',
    ),
    'delete' => array(
      'access CiviCRM',
      'access CiviMember',
      'delete in CiviMember',
    ),
    'get' => array(
      'access CiviCRM',
      'access CiviMember',
    ),
    'update' => array(
      'access CiviCRM',
      'access CiviMember',
      'edit memberships',
    ),
  );
  $permissions['membership_type'] = array(
    'create' => array(
      'access CiviCRM',
      'access CiviMember',
      'edit memberships'
    ),
    'delete' => array(
      'access CiviCRM',
      'access CiviMember',
      'delete in CiviMember',
    ),
    'get' => array(
      'access CiviCRM',
      'access CiviMember',
    ),
    'update' => array(
      'access CiviCRM',
      'access CiviMember',
      'edit memberships',
    ),
  );
  $permissions['note'] = array(
    'create' => array(
      'access CiviCRM',
      'add contacts'
    ),
    'delete' => array(
      'access CiviCRM',
      'delete contacts',
    ),
    'get' => array(
      'access CiviCRM',
      'view all contacts',
    ),
    'update' => array(
      'access CiviCRM',
      'edit all contacts',
    ),
  );
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
  $permissions['phone'] = array(
    'create' => array(
      'access CiviCRM',
      'add contacts',
    ),
    'delete' => array(
      'access CiviCRM',
      'delete contacts',
    ),
    'get' => array(
      'access CiviCRM',
      'view all contacts',
    ),
    'update' => array(
      'access CiviCRM',
      'edit all contacts',
    ),
  );
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
  $permissions['system'] = array(
    'flush' => array('administer CiviCRM'),
  );
  $permissions['website'] = array(
    'create' => array(
      'access CiviCRM',
      'add contacts',
    ),
    'delete' => array(
      'access CiviCRM',
      'delete contacts',
    ),
    'get' => array(
      'access CiviCRM',
      'view all contacts',
    ),
    'update' => array(
      'access CiviCRM',
      'edit all contacts',
    ),
  );

  // let third parties modify the permissions
  CRM_Utils_Hook::alterAPIPermissions($entity, $action, $params, $permissions);

  return isset($permissions[$entity][$action]) ? $permissions[$entity][$action] : array('administer CiviCRM');
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
