<?php

require_once 'civigrant.civix.php';
use CRM_Grant_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function civigrant_civicrm_config(&$config) {
  _civigrant_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function civigrant_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _civigrant_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function civigrant_civicrm_entityTypes(&$entityTypes) {
  _civigrant_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_links().
 *
 * Add shortcut link to create new grant.
 */
function civigrant_civicrm_links($context, $name, $id, &$links) {
  if ($context === 'create.new.shortcuts' && CRM_Core_Permission::check(['access CiviGrant', 'edit grants'])) {
    $links[] = [
      'ref' => 'new-grant',
      'name' => 'Grant',
      'title' => ts('Grant'),
      'url' => CRM_Utils_System::url('civicrm/grant/add', 'reset=1&action=add&context=standalone'),
    ];
  }
}

/**
 * Implements hook_civicrm_summaryActions().
 *
 * Add contact summary link to create new grant.
 */
function civigrant_civicrm_summaryActions(&$menu, $cid) {
  $menu['grant'] = [
    'title' => ts('Add Grant'),
    'weight' => 26,
    'ref' => 'new-grant',
    'key' => 'grant',
    'tab' => 'afsearchGrants',
    'href' => CRM_Utils_System::url('civicrm/contact/view/grant',
      'reset=1&action=add&context=grant'
    ),
    'permissions' => ['edit grants'],
  ];
}

/**
 * Implements hook_civicrm_permission().
 *
 * Define CiviGrant permissions.
 */
function civigrant_civicrm_permission(&$permissions) {
  $permissions['access CiviGrant'] = [
    E::ts('CiviGrant:') . ' ' . E::ts('access CiviGrant'),
    E::ts('View all grants'),
  ];
  $permissions['edit grants'] = [
    E::ts('CiviGrant:') . ' ' . E::ts('edit grants'),
    E::ts('Create and update grants'),
  ];
  $permissions['delete in CiviGrant'] = [
    E::ts('CiviGrant:') . ' ' . E::ts('delete in CiviGrant'),
    E::ts('Delete grants'),
  ];
}

/**
 * Implements hook_civicrm_alterAPIPermissions().
 *
 * Set CiviGrant permissions for APIv3.
 */
function civigrant_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions) {
  $permissions['grant'] = [
    'get' => [
      'access CiviGrant',
    ],
    'delete' => [
      'delete in CiviGrant',
    ],
    'create' => [
      'edit grants',
    ],
    'update' => [
      'edit grants',
    ],
  ];
}

/**
 * Implements hook_civicrm_queryObjects().
 *
 * Adds query object for legacy screens like advanced search, search builder, etc.
 */
function civigrant_civicrm_queryObjects(&$queryObjects, $type) {
  if ($type == 'Contact') {
    $queryObjects[] = new CRM_Grant_BAO_Query();
  }
  elseif ($type == 'Report') {
    // Do we need to do something here?
  }
}
