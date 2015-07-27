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
   * @param array $processor
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

        $this->cache[$id] = new $paymentClass($processor['is_test'] ? 'test' : 'live', $processor);
      }
    }
    return $this->cache[$id];
  }

  /**
   * @param int $id
   * @throws \CiviCRM_API3_Exception
   */
  public function getById($id) {
    $processor = civicrm_api3('payment_processor', 'getsingle', array('id' => $id));
    return self::getByProcessor($processor);
  }

  /**
   * @param string $name
   * @param bool $is_test
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
