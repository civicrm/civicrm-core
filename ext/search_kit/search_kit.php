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
    E::ts('SearchKit: edit and delete searches'),
    E::ts('Gives non-admin users access to the SearchKit UI to create, update and delete searches and displays'),
  ];
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
  \CRM_Utils_Hook::singleton()->invoke(['tasks', 'checkPermissions', 'userId', 'search', 'display'],
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

/**
 * Implements hook_civicrm_entityTypes().
 */
function search_kit_civicrm_entityTypes(array &$entityTypes): void {
  foreach (_getSearchKitEntityDisplays() as $display) {
    $entityTypes[$display['entityName']] = [
      'name' => $display['entityName'],
      'class' => \Civi\BAO\SK_Entity::class,
      'table' => $display['tableName'],
    ];
  }
}

/**
 * Returns a SQL-safe table name for a display (for use with displays of type "entity")
 *
 * @param string $displayName
 * @return string
 */
function _getSearchKitDisplayTableName(string $displayName): string {
  return CRM_Utils_String::munge('civicrm_sk_' . CRM_Utils_String::convertStringToSnakeCase($displayName), '_', 64);
}

/**
 * Uncached function to fetch displays of type "entity" to be used by boot-level code
 *
 * @return array
 * @throws CRM_Core_Exception
 */
function _getSearchKitEntityDisplays(): array {
  $displays = [];
  // Can't use the API to fetch search displays because this is called by pre-boot hooks
  $select = CRM_Utils_SQL_Select::from('civicrm_search_display')
    ->where('type = "entity"')
    ->select(['id', 'name', 'label', 'settings']);
  try {
    $display = CRM_Core_DAO::executeQuery($select->toSQL());
    while ($display->fetch()) {
      $displays[] = [
        'id' => $display->id,
        'label' => $display->label,
        'name' => $display->name,
        'entityName' => 'SK_' . $display->name,
        'tableName' => _getSearchKitDisplayTableName($display->name),
        'settings' => CRM_Core_DAO::unSerializeField($display->settings, \CRM_Core_DAO::SERIALIZE_JSON),
      ];
    }
  }
  // If the extension hasn't fully installed and the table doesn't exist yet, suppress errors
  catch (CRM_Core_Exception $e) {
    return [];
  }
  return $displays;
}
