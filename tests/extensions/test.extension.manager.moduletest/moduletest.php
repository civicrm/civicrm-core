<?php

/**
 * Implemenation of hook_civicrm_install
 */
function moduletest_civicrm_install() {
  CRM_Extension_Manager_ModuleTest::incHookCount('moduletest', 'install');
}

/**
 * Implemenation of hook_civicrm_uninstall
 */
function moduletest_civicrm_uninstall() {
  CRM_Extension_Manager_ModuleTest::incHookCount('moduletest', 'uninstall');
}

/**
 * Implemenation of hook_civicrm_enable
 */
function moduletest_civicrm_enable() {
  CRM_Extension_Manager_ModuleTest::incHookCount('moduletest', 'enable');
}

/**
 * Implemenation of hook_civicrm_disable
 */
function moduletest_civicrm_disable() {
  CRM_Extension_Manager_ModuleTest::incHookCount('moduletest', 'disable');
}
