<?php

/**
 * Implements civicrm_install
 */
function childtest_civicrm_install() {
  CRM_Extension_Manager_SubmoduleTest::logHook(__FUNCTION__);
}

/**
 * Implements civicrm_uninstall
 */
function childtest_civicrm_uninstall() {
  CRM_Extension_Manager_SubmoduleTest::logHook(__FUNCTION__);
}

/**
 * Implements civicrm_enable
 */
function childtest_civicrm_enable() {
  CRM_Extension_Manager_SubmoduleTest::logHook(__FUNCTION__);
}

/**
 * Implements civicrm_disable
 */
function childtest_civicrm_disable() {
  CRM_Extension_Manager_SubmoduleTest::logHook(__FUNCTION__);
}
