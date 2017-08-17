<?php
namespace Civi\Core;

/**
 * Class SettingsStack
 *
 * The settings stack allows you to temporarily apply settings.
 *
 * @package Civi\Core
 */
class SettingsStack {

  /**
   * @var array
   *   Ex: $stack[0] == ['settingName', 'oldSettingValue'];
   */
  protected $stack = array();

  /**
   * Temporarily apply a setting.
   *
   * @param $settingValue
   * @param $setting
   */
  public function push($setting, $settingValue) {
    if (isset($GLOBALS['civicrm_setting']['domain'][$setting])) {
      $this->stack[] = array($setting, $GLOBALS['civicrm_setting']['domain'][$setting]);
    }
    else {
      $this->stack[] = array($setting, NULL);
    }
    $GLOBALS['civicrm_setting']['domain'][$setting] = $settingValue;
    \Civi::service('settings_manager')->useMandatory();
  }

  /**
   * Restore original settings.
   */
  public function popAll() {
    while ($frame = array_pop($this->stack)) {
      list($setting, $value) = $frame;
      if ($value === NULL) {
        unset($GLOBALS['civicrm_setting']['domain'][$setting]);
      }
      else {
        $GLOBALS['civicrm_setting']['domain'][$setting] = $value;
      }
    }
    \Civi::service('settings_manager')->useMandatory();
  }

}
