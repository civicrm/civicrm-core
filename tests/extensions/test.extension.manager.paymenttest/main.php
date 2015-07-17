<?php

/**
 * Class test_extension_manager_paymenttest
 */
class test_extension_manager_paymenttest extends CRM_Core_Payment {

  static $counts = array();

  public function install() {
    self::$counts['install'] = 1 + (int) self::$counts['install'];
  }

  public function uninstall() {
    self::$counts['uninstall'] = 1 + (int) self::$counts['uninstall'];
  }

  public function disable() {
    self::$counts['disable'] = 1 + (int) self::$counts['disable'];
  }

  public function enable() {
    self::$counts['enable'] = 1 + (int) self::$counts['enable'];
  }

  public function checkConfig() {
  }

}
