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
      'path' => 'civicrm/grant/add',
      'query' => "reset=1&action=add&context=standalone",
      'ref' => 'new-grant',
      'title' => ts('Grant'),
    ];
  }
}

/**
 * Implements hook_civicrm_permission().
 *
 * Define CiviGrant permissions.
 */
function civigrant_civicrm_permission(&$permissions) {
  $permissions['access CiviGrant'] = [
    E::ts('access CiviGrant'),
    E::ts('View all grants'),
  ];
  $permissions['edit grants'] = [
    E::ts('edit grants'),
    E::ts('Create and update grants'),
  ];
  $permissions['delete in CiviGrant'] = [
    E::ts('delete in CiviGrant'),
    E::ts('Delete grants'),
  ];
}

/**
 * Implements hook_civicrm_tabSet().
 *
 * Add grants tab to contact summary screen.
 */
function civigrant_civicrm_tabSet($tabSetName, &$tabs, $context) {
  if ($tabSetName === 'civicrm/contact/view' && !empty($context['contact_id'])) {
    $cid = $context['contact_id'];
    $tabs[] = [
      'id' => 'grant',
      'url' => CRM_Utils_System::url("civicrm/contact/view/grant", ['reset' => 1, 'cid' => $cid]),
      'title' => E::ts('Grants'),
      'weight' => 60,
      'count' => CRM_Grant_BAO_Grant::getContactGrantCount($cid),
      'class' => 'livePage',
      'icon' => 'crm-i fa-money',
    ];
  }
}
