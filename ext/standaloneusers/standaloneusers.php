<?php

require_once 'standaloneusers.civix.php';
// phpcs:disable
use CRM_Standaloneusers_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function standaloneusers_civicrm_config(&$config) {
  _standaloneusers_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function standaloneusers_civicrm_install() {
  _standaloneusers_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function standaloneusers_civicrm_enable() {
  _standaloneusers_civix_civicrm_enable();
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
//function standaloneusers_civicrm_preProcess($formName, &$form) {
//
//}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
// function standaloneusers_civicrm_navigationMenu(&$menu) {
//   _standalineusers_addUserMenus($menu);
// }

// function _standaloneusers_addUserMenus(&$menu) {
//   _standaloneusers_civix_insert_navigation_menu($menu, 'Administer/Users and Permissions', [
//     'label' => E::ts('Users'),
//     'name' => 'admin_users',
//     'url' => 'civicrm/search#/display/Users/Users',
//     'permission' => 'cms:administer users',
//     'operator' => 'OR',
//     'separator' => 0,
//     'weight' => 0,
//   ]);
//   _standaloneusers_civix_navigationMenu($menu);
// }
