<?php

/**
 * Implements hook_civicrm_install
 */
function moduletest_civicrm_install() {
  CRM_Extension_Manager_ModuleTest::incHookCount('moduletest', 'install');
}

/**
 * Implements hook_civicrm_postInstall
 */
function moduletest_civicrm_postInstall() {
  CRM_Extension_Manager_ModuleTest::incHookCount('moduletest', 'postInstall');
}

/**
 * Implements hook_civicrm_uninstall
 */
function moduletest_civicrm_uninstall() {
  CRM_Extension_Manager_ModuleTest::incHookCount('moduletest', 'uninstall');
}

/**
 * Implements hook_civicrm_enable
 */
function moduletest_civicrm_enable() {
  CRM_Extension_Manager_ModuleTest::incHookCount('moduletest', 'enable');
}

/**
 * Implements hook_civicrm_disable
 */
function moduletest_civicrm_disable() {
  CRM_Extension_Manager_ModuleTest::incHookCount('moduletest', 'disable');
}
