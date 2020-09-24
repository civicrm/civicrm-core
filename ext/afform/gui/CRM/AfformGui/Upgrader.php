<?php
use CRM_AfformGui_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_AfformGui_Upgrader extends CRM_AfformGui_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Setup navigation item on new installs.
   *
   * Note: this path is not in the menu.xml because it is handled by afform
   */
  public function install() {
    try {
      $existing = civicrm_api3('Navigation', 'getcount', [
        'name' => 'afform_gui',
        'domain_id' => CRM_Core_Config::domainID(),
      ]);
      if (!$existing) {
        civicrm_api3('Navigation', 'create', [
          'parent_id' => 'Customize Data and Screens',
          'label' => ts('Forms'),
          'weight' => 1,
          'name' => 'afform_gui',
          'permission' => 'administer CiviCRM',
          'url' => 'civicrm/admin/afform',
          'is_active' => 1,
        ]);
      }
    }
    catch (Exception $e) {
      // Couldn't create menu item.
    }
  }

  /**
   * Cleanup navigation upon removal
   */
  public function uninstall() {
    civicrm_api3('Navigation', 'get', [
      'name' => 'afform_gui',
      'return' => ['id'],
      'api.Navigation.delete' => [],
    ]);
  }

}
