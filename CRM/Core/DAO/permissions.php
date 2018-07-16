<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
  $permissions = CRM_Core_Permission::getEntityActionPermissions();

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
  $action = CRM_Core_Permission::getGenericAction($action);

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
