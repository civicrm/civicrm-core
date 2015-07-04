<?php

namespace Civi\Payment;

/**
 * Class System
 * @package Civi\Payment
 */
class System {

  /**
   * @var System
   */
  private static $singleton;

  /**
   * @var array cache
   */
  private $cache = array();

  /**
   * @return \Civi\Payment\System
   */
  public static function singleton() {
    if (!self::$singleton) {
      self::$singleton = new self();
    }
    return self::$singleton;
  }

  /**
   * Starting from the processor as an array retrieve the processor as an object.
   *
   * If there is no valid configuration it will not be retrieved.
   *
   * @param array $processor
   *
   * @return CRM_Core_Payment|NULL
   *
   * @throws \CRM_Core_Exception
   */
  public function getByProcessor($processor) {
    $id = $processor['id'];

    if (!isset($this->cache[$id])) {
      if (!isset($this->cache[$id])) {
        //does this config need to be called?
        $config = \CRM_Core_Config::singleton();
        $ext = \CRM_Extension_System::singleton()->getMapper();
        if ($ext->isExtensionKey($processor['class_name'])) {
          $paymentClass = $ext->keyToClass($processor['class_name'], 'payment');
          require_once $ext->classToPath($paymentClass);
        }
        else {
          $paymentClass = 'CRM_Core_' . $processor['class_name'];
          if (empty($paymentClass)) {
            throw new \CRM_Core_Exception('no class provided');
          }
          require_once str_replace('_', DIRECTORY_SEPARATOR, $paymentClass) . '.php';
        }

        $processorObject = new $paymentClass(!empty($processor['is_test']) ? 'test' : 'live', $processor);
        if ($processorObject->checkConfig()) {
          $processorObject = NULL;
        }
        else {
          $processorObject->setPaymentProcessor($processor);
        }
      }
      $this->cache[$id] = $processorObject;
    }
    return $this->cache[$id];
  }

  /**
   * @param int $id
   *
   * @return \Civi\Payment\CRM_Core_Payment|NULL
   * @throws \CiviCRM_API3_Exception
   */
  public function getById($id) {
    $processor = civicrm_api3('payment_processor', 'getsingle', array('id' => $id));
    return self::getByProcessor($processor);
  }

  /**
   * @param string $name
   * @param bool $is_test
   *
   * @return \Civi\Payment\CRM_Core_Payment|NULL
   * @throws \CiviCRM_API3_Exception
   */
  public function getByName($name, $is_test) {
    $processor = civicrm_api3('payment_processor', 'getsingle', array('name' => $name, 'is_test' => $is_test));
    return self::getByProcessor($processor);
  }

  /**
   * Flush processors from static cache.
   *
   * This is particularly used for tests.
   *
   */
  public function flushProcessors() {
    $this->cache = array();
  }

}
