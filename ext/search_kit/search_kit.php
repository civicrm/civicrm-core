<?php

require_once 'search_kit.civix.php';
use CRM_Search_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function search_kit_civicrm_config(&$config) {
  _search_kit_civix_civicrm_config($config);
  Civi::dispatcher()->addListener('hook_civicrm_alterAngular', ['\Civi\Search\AfformSearchMetadataInjector', 'preprocess'], 1000);
  Civi::dispatcher()->addSubscriber(new Civi\Api4\Event\Subscriber\SearchKitSubscriber());
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
 * Implements hook_civicrm_permission().
 *
 * Define SearchKit permissions.
 */
function search_kit_civicrm_permission(&$permissions) {
  $permissions['administer search_kit'] = [
    E::ts('Search Kit: edit and delete searches'),
    E::ts('Gives non-admin users access to the Search Kit UI to create, update and delete searches and displays'),
  ];
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
 * Implements hook_civicrm_pre().
 */
function search_kit_civicrm_pre($op, $entity, $id, &$params) {
  // When deleting a saved search, also delete the displays
  // This would happen anyway in sql because of the ON DELETE CASCADE foreign key,
  // But this ensures that pre and post hooks are called
  if ($entity === 'SavedSearch' && $op === 'delete') {
    \Civi\Api4\SearchDisplay::delete(FALSE)
      ->addWhere('saved_search_id', '=', $id)
      ->execute();
  }
}

/**
 * Implements hook_civicrm_post().
 */
function search_kit_civicrm_post($op, $entity, $id, $object) {
  // Flush fieldSpec cache when saving a SearchSegment
  if ($entity === 'SearchSegment') {
    \Civi::$statics['all_search_segments'] = NULL;
    \Civi::cache('metadata')->clear();
  }
}
