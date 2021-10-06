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
 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
 */
function search_kit_civicrm_container($container) {
  $container->getDefinition('dispatcher')
    ->addMethodCall('addListener', [
      'civi.api4.authorizeRecord::SavedSearch',
      ['CRM_Search_BAO_SearchDisplay', 'savedSearchCheckAccessByDisplay'],
    ]);
}

/**
 * Implements hook_civicrm_alterApiRoutePermissions().
 *
 * Allow anonymous users to run a search display. Permissions are checked internally.
 *
 * @see CRM_Utils_Hook::alterApiRoutePermissions
 */
function search_kit_civicrm_alterApiRoutePermissions(&$permissions, $entity, $action) {
  if ($entity === 'SearchDisplay') {
    if ($action === 'run' || $action === 'download' || $action === 'getSearchTasks') {
      $permissions = CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION;
    }
  }
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
  // When deleting a saved search, also delete the displays
  // This would happen anyway in sql because of the ON DELETE CASCADE foreign key,
  // But this ensures that pre and post hooks are called
  if ($entity === 'SavedSearch' && $op === 'delete') {
    \Civi\Api4\SearchDisplay::delete(FALSE)
      ->addWhere('saved_search_id', '=', $id)
      ->execute();
  }
}
