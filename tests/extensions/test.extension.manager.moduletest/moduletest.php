<?php

/**
 * Implements civicrm_install
 */
function moduletest_civicrm_install() {
  CRM_Extension_Manager_ModuleTest::incHookCount('moduletest', 'install');
}

/**
 * Implements civicrm_postInstall
 */
function moduletest_civicrm_postInstall() {
  CRM_Extension_Manager_ModuleTest::incHookCount('moduletest', 'postInstall');
}

/**
 * Implements civicrm_uninstall
 */
function moduletest_civicrm_uninstall() {
  CRM_Extension_Manager_ModuleTest::incHookCount('moduletest', 'uninstall');
}

/**
 * Implements civicrm_enable
 */
function moduletest_civicrm_enable() {
  CRM_Extension_Manager_ModuleTest::incHookCount('moduletest', 'enable');
}

/**
 * Implements civicrm_disable
 */
function moduletest_civicrm_disable() {
  CRM_Extension_Manager_ModuleTest::incHookCount('moduletest', 'disable');
}
