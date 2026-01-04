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

/**
 * Decide what permissions to check for an api call
 *
 * @param string $entity api entity
 * @param string $action api action
 * @param array $params api params
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
  $perm = ($permissions[$entity] ?? []) + $permissions['default'];

  // Return exact match if permission for this action has been declared
  if (isset($perm[$action])) {
    return $perm[$action];
  }

  // Translate specific actions into their generic equivalents
  $action = CRM_Core_Permission::getGenericAction($action);

  return $perm[$action] ?? $perm['default'];
}
