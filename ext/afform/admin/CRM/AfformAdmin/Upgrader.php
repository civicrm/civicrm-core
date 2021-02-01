<?php
use CRM_AfformAdmin_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_AfformAdmin_Upgrader extends CRM_AfformAdmin_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Setup navigation item on new installs.
   *
   * Note: this path is not in the menu.xml because routing is handled by afform
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
          'label' => E::ts('Form Builder'),
          'weight' => 1,
          'name' => 'afform_admin',
          'permission' => 'administer CiviCRM',
          'url' => 'civicrm/admin/afform',
          'is_active' => 1,
          'icon' => 'crm-i fa-list-alt',
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

  /**
   * Update menu item
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0001() {
    $this->ctx->log->info('Applying update 0001');
    \Civi\Api4\Navigation::update(FALSE)
      ->addValue('icon', 'crm-i fa-list-alt')
      ->addValue('label', E::ts('Form Builder'))
      ->addValue('name', 'afform_admin')
      ->addWhere('name', '=', 'afform_gui')
      ->execute();
    return TRUE;
  }

}
