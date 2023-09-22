<?php

class CRM_Moduleupgtest_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Implements civicrm_install
   */
  public function install() {
    CRM_Extension_Manager_ModuleUpgTest::incHookCount('moduleupgtest', 'install');
  }

  /**
   * Implements civicrm_postInstall
   */
  public function postInstall() {
    CRM_Extension_Manager_ModuleUpgTest::incHookCount('moduleupgtest', 'postInstall');
  }

  /**
   * Implements civicrm_uninstall
   */
  public function uninstall() {
    CRM_Extension_Manager_ModuleUpgTest::incHookCount('moduleupgtest', 'uninstall');
  }

  /**
   * Implements civicrm_enable
   */
  public function enable() {
    CRM_Extension_Manager_ModuleUpgTest::incHookCount('moduleupgtest', 'enable');
  }

  /**
   * Implements civicrm_disable
   */
  public function disable() {
    CRM_Extension_Manager_ModuleUpgTest::incHookCount('moduleupgtest', 'disable');
  }

}
