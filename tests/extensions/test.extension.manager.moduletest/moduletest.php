<<<<<<< HEAD
<?php

/**
 * Implemenation of hook_civicrm_install
 */
function moduletest_civicrm_install() {
  CRM_Extension_Manager_ModuleTest::incHookCount('moduletest', 'install');
}

/**
 * Implementation of hook_civicrm_postInstall
 */
function moduletest_civicrm_postInstall() {
  CRM_Extension_Manager_ModuleTest::incHookCount('moduletest', 'postInstall');
}

/**
 * Implementation of hook_civicrm_uninstall
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
=======
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
>>>>>>> 650ff6351383992ec77abface9b7f121f16ae07e
