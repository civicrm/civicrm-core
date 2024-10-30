<?php
// phpcs:disable
use CRM_CivicrmSearchUi_ExtensionUtil as E;
// phpcs:enable

/**
 * Collection of upgrade steps.
 */
class CRM_CivicrmSearchUi_Upgrader extends CRM_Extension_Upgrader_Base {

  protected function replaceFindContactMenuPath($path) {
    // point Find Contacts menu to the FB/SK version or back to the original path
    // this is temporary until everything is in FB/SK and we can use the original path
    $results = \Civi\Api4\Navigation::update(FALSE)
      ->addValue('url', $path)
      ->addWhere('name', '=', 'Find Contacts')
      ->execute();
  }

  /**
   * See https://github.com/civicrm/civicrm-core/pull/26669
   */
  public function enable(): void {
    $this->replaceFindContactMenuPath('civicrm/searchui/contact/search');
  }

  public function disable(): void {
    $this->replaceFindContactMenuPath('civicrm/contact/search');
  }

  public function upgrade_1000(): bool {
    $this->replaceFindContactMenuPath('civicrm/searchui/contact/search');
    return TRUE;
  }

}
