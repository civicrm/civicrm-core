<?php

/**
 * Implements civicrm_install
 */
function uncletest_civicrm_install() {
  CRM_Extension_Manager_SubmoduleTest::logHook(__FUNCTION__);
}

/**
 * Implements civicrm_uninstall
 */
function uncletest_civicrm_uninstall() {
  CRM_Extension_Manager_SubmoduleTest::logHook(__FUNCTION__);
}

/**
 * Implements civicrm_enable
 */
function uncletest_civicrm_enable() {
  CRM_Extension_Manager_SubmoduleTest::logHook(__FUNCTION__);
}

/**
 * Implements civicrm_disable
 */
function uncletest_civicrm_disable() {
  CRM_Extension_Manager_SubmoduleTest::logHook(__FUNCTION__);
}
