<?php
// phpcs:disable
use CRM_CivicrmAdminUi_ExtensionUtil as E;
// phpcs:enable

/**
 * Collection of upgrade steps.
 */
class CRM_CivicrmAdminUi_Upgrader extends CRM_Extension_Upgrader_Base {

  protected function replaceFindContactMenuPath($path) {
    // point Find Contacts menu to the FB/SK version or back to the original path
    // this is temporary until everything is in FB/SK and we can use the original path
    $results = \Civi\Api4\Navigation::update(FALSE)
      ->addValue('url', $path)
      ->addWhere('name', '=', 'Find Contacts')
      ->execute();
  }

  /**
   * @todo "install" and "uninstall" may not be needed if enable and disable are present. See https://github.com/civicrm/civicrm-core/pull/26669
   */
  public function install(): void {
    $this->replaceFindContactMenuPath('civicrm/adminui/contact/search');
  }

  public function uninstall(): void {
    $this->replaceFindContactMenuPath('civicrm/contact/search');
  }

  public function enable(): void {
    $this->replaceFindContactMenuPath('civicrm/adminui/contact/search');
  }

  public function disable(): void {
    $this->replaceFindContactMenuPath('civicrm/contact/search');
  }

  public function upgrade_1000(): bool {
    $this->replaceFindContactMenuPath('civicrm/adminui/contact/search');
    return TRUE;
  }

}
