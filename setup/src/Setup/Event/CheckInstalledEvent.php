<?php
namespace Civi\Setup\Event;

/**
 * Check if CiviCRM is already installed.
 *
 * Event Name: 'civi.setup.checkInstalled'
 */
class CheckInstalledEvent extends BaseSetupEvent {

  /**
   * @var bool
   */
  private $settingInstalled = NULL;

  /**
   * @var bool
   */
  private $databaseInstalled = NULL;

  /**
   * @return bool
   */
  public function isSettingInstalled() {
    return $this->settingInstalled;
  }

  /**
   * @param bool $settingInstalled
   */
  public function setSettingInstalled($settingInstalled) {
    $this->settingInstalled = $settingInstalled;
  }

  /**
   * @return bool
   */
  public function isDatabaseInstalled() {
    return $this->databaseInstalled;
  }

  /**
   * @param bool $databaseInstalled
   */
  public function setDatabaseInstalled($databaseInstalled) {
    $this->databaseInstalled = $databaseInstalled;
  }

}
