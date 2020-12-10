<?php

require_once 'search.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function search_civicrm_config(&$config) {
  _search_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function search_civicrm_xmlMenu(&$files) {
  _search_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function search_civicrm_install() {
  _search_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function search_civicrm_postInstall() {
  _search_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function search_civicrm_uninstall() {
  _search_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function search_civicrm_enable() {
  _search_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function search_civicrm_disable() {
  _search_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function search_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _search_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function search_civicrm_managed(&$entities) {
  _search_civix_civicrm_managed($entities);
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
function search_civicrm_angularModules(&$angularModules) {
  _search_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function search_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _search_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function search_civicrm_entityTypes(&$entityTypes) {
  _search_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_themes().
 */
function search_civicrm_themes(&$themes) {
  _search_civix_civicrm_themes($themes);
}

/**
 * Implements hook_civicrm_pre().
 */
function search_civicrm_pre($op, $entity, $id, &$params) {
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

/**
 * Injects settings data to search displays embedded in afforms
 *
 * @param \Civi\Angular\Manager $angular
 * @see CRM_Utils_Hook::alterAngular()
 */
function search_civicrm_alterAngular($angular) {
  $changeSet = \Civi\Angular\ChangeSet::create('searchSettings')
    ->alterHtml(';\\.aff\\.html$;', function($doc, $path) {
      $displayTypes = array_column(\Civi\Search\Display::getDisplayTypes(['name']), 'name');

      if ($displayTypes) {
        $componentNames = 'crm-search-display-' . implode(', crm-search-display-', $displayTypes);
        foreach (pq($componentNames, $doc) as $component) {
          $searchName = pq($component)->attr('search-name');
          $displayName = pq($component)->attr('display-name');
          if ($searchName && $displayName) {
            $display = \Civi\Api4\SearchDisplay::get(FALSE)
              ->addWhere('name', '=', $displayName)
              ->addWhere('saved_search.name', '=', $searchName)
              ->addSelect('settings', 'saved_search.api_entity', 'saved_search.api_params')
              ->execute()->first();
            if ($display) {
              pq($component)->attr('settings', CRM_Utils_JS::encode($display['settings'] ?? []));
              pq($component)->attr('api-entity', CRM_Utils_JS::encode($display['saved_search.api_entity']));
              pq($component)->attr('api-params', CRM_Utils_JS::encode($display['saved_search.api_params']));
            }
          }
        }
      }
    });
  $angular->add($changeSet);

}
