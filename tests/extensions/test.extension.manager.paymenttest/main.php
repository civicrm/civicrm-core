<?php

/**
 * Class test_extension_manager_paymenttest
 */
class test_extension_manager_paymenttest extends CRM_Core_Payment {

  public static $counts = array();

  public function install() {
    self::$counts['install'] = isset(self::$counts['install']) ? self::$counts['install'] : 0;
    self::$counts['install'] = 1 + (int) self::$counts['install'];
  }

  public function uninstall() {
    self::$counts['uninstall'] = isset(self::$counts['uninstall']) ? self::$counts['uninstall'] : 0;
    self::$counts['uninstall'] = 1 + (int) self::$counts['uninstall'];
  }

  public function disable() {
    self::$counts['disable'] = isset(self::$counts['disable']) ? self::$counts['disable'] : 0;
    self::$counts['disable'] = 1 + (int) self::$counts['disable'];
  }

  public function enable() {
    self::$counts['enable'] = isset(self::$counts['enable']) ? self::$counts['enable'] : 0;
    self::$counts['enable'] = 1 + (int) self::$counts['enable'];
  }

  public function checkConfig() {
  }

  /**
   * Get the desired value from $counts.
   *
   * @param string $type
   *
   * @return int
   */
  public static function getCount($type) {
    return isset(self::$counts[$type]) ? self::$counts[$type] : 0;
  }

}
