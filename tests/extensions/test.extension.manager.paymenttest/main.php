<?php
class test_extension_manager_paymenttest extends CRM_Core_Payment {
  static private $_singleton = NULL;

  static function &singleton($mode = 'test', &$paymentProcessor, &$paymentForm = NULL, $force = FALSE) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new test_extension_manager_paymenttest();
    }
    return self::$_singleton[$processorName];
  }

  static $counts = array();

  function install() {
    self::$counts['install'] = 1 + (int) self::$counts['install'];
  }

  function uninstall() {
    self::$counts['uninstall'] = 1 + (int) self::$counts['uninstall'];
  }

  function disable() {
    self::$counts['disable'] = 1 + (int) self::$counts['disable'];
  }

  function enable() {
    self::$counts['enable'] = 1 + (int) self::$counts['enable'];
  }

  function doDirectPayment(&$params) {
  }

  function checkConfig() {
  }
}