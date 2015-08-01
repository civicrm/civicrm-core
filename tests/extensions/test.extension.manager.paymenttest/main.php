<<<<<<< HEAD
<?php

/**
 * Class test_extension_manager_paymenttest
 */
class test_extension_manager_paymenttest extends CRM_Core_Payment {
  static private $_singleton = NULL;

  /**
   * singleton function used to manage this object
   *
   * @param string  $mode the mode of operation: live or test
   * @param array  $paymentProcessor the details of the payment processor being invoked
   * @param CRM_Core_Form  $paymentForm      reference to the form object if available
   * @param boolean $force            should we force a reload of this payment object
   *
   * @return object
   * @static
   *
   */
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

  /**
   * This function collects all the information from a web/api form and invokes
   * the relevant payment processor specific functions to perform the transaction
   *
   * @param  array $params assoc array of input parameters for this transaction
   *
   * @return array the result in an nice formatted array (or an error object)
   * @abstract
   */
  function doDirectPayment(&$params) {
  }

  function checkConfig() {
  }
}
=======
<?php

/**
 * Class test_extension_manager_paymenttest
 */
class test_extension_manager_paymenttest extends CRM_Core_Payment {

  static $counts = array();

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
>>>>>>> 650ff6351383992ec77abface9b7f121f16ae07e
