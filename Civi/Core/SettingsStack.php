<?php
namespace Civi\Core;

/**
 * Class SettingsStack
 *
 * The settings stack allows you to temporarily change (then restore) settings. It's intended
 * primarily for use in testing.
 *
 * Like the global `$civicrm_setting` variable, it works best with typical inert settings that
 * do not trigger extra activation logic. A handful of settings (such as `enable_components`
 * and ~5 others) should be avoided, but most settings should work.
 *
 * @package Civi\Core
 */
class SettingsStack {

  /**
   * @var array
   *   Ex: $stack[0] == ['settingName', 'oldSettingValue'];
   */
  protected $stack = [];

  /**
   * Temporarily apply a setting.
   *
   * @param $settingValue
   * @param $setting
   */
  public function push($setting, $settingValue) {
    if (isset($GLOBALS['civicrm_setting']['domain'][$setting])) {
      $this->stack[] = [$setting, $GLOBALS['civicrm_setting']['domain'][$setting]];
    }
    else {
      $this->stack[] = [$setting, NULL];
    }
    $GLOBALS['civicrm_setting']['domain'][$setting] = $settingValue;
    \Civi::service('settings_manager')->useMandatory();
  }

  /**
   * Restore original settings.
   */
  public function popAll() {
    while ($frame = array_pop($this->stack)) {
      [$setting, $value] = $frame;
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
