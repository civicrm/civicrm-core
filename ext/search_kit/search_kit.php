<?php

require_once 'search_kit.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function search_kit_civicrm_config(&$config) {
  _search_kit_civix_civicrm_config($config);
  Civi::dispatcher()->addListener('hook_civicrm_alterAngular', ['\Civi\Search\AfformSearchMetadataInjector', 'preprocess'], 1000);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function search_kit_civicrm_xmlMenu(&$files) {
  _search_kit_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function search_kit_civicrm_managed(&$entities) {
  _search_kit_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function search_kit_civicrm_angularModules(&$angularModules) {
  _search_kit_civix_civicrm_angularModules($angularModules);
  // Fetch all search tasks provided by extensions and add their Angular modules as crmSearchTasks dependencies
  $tasks = [];
  $null = NULL;
  $checkPermissions = FALSE;
  \CRM_Utils_Hook::singleton()->invoke(['tasks', 'checkPermissions', 'userId'],
    $tasks, $checkPermissions, $null,
    $null, $null, $null, 'civicrm_searchKitTasks'
  );
  foreach ($tasks as $entityTasks) {
    foreach ($entityTasks as $task) {
      if (isset($task['module']) && $task['module'] !== 'crmSearchTasks' &&
        !in_array($task['module'], $angularModules['crmSearchTasks']['requires'], TRUE)
      ) {
        $angularModules['crmSearchTasks']['requires'][] = $task['module'];
      }
    }
  }
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function search_kit_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _search_kit_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function search_kit_civicrm_entityTypes(&$entityTypes) {
  _search_kit_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_themes().
 */
function search_kit_civicrm_themes(&$themes) {
  _search_kit_civix_civicrm_themes($themes);
}

/**
 * Implements hook_civicrm_pre().
 */
function search_kit_civicrm_pre($op, $entity, $id, &$params) {
  // Supply default name/label when creating new SearchDisplay
  if ($entity === 'SearchDisplay' && $op === 'create') {
    if (empty($params['label'])) {
      $params['label'] = $params['name'];
    }
    elseif (empty($params['name'])) {
      $params['name'] = \CRM_Utils_String::munge($params['label']);
    }
  }
}
