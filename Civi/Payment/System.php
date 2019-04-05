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
  private $cache = [];

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
   * @param bool $force
   *   Override the config check. This is required in uninstall as no valid instances exist
   *   but will deliberately not work with any valid processors.
   *
   * @return \CRM_Core_Payment|NULL
   *
   * @throws \CRM_Core_Exception
   */
  public function getByProcessor($processor, $force = FALSE) {
    $id = $force ? 0 : $processor['id'];

    if (!isset($this->cache[$id]) || $force) {
      $ext = \CRM_Extension_System::singleton()->getMapper();
      if ($ext->isExtensionKey($processor['class_name'])) {
        $paymentClass = $ext->keyToClass($processor['class_name'], 'payment');
        require_once $ext->classToPath($paymentClass);
      }
      else {
        $paymentClass = 'CRM_Core_' . $processor['class_name'];
        if (empty($processor['class_name'])) {
          throw new \CRM_Core_Exception('no class provided');
        }
      }

      $processorObject = NULL;
      if (class_exists($paymentClass)) {
        $processorObject = new $paymentClass(!empty($processor['is_test']) ? 'test' : 'live', $processor);
        if ($force || !$processorObject->checkConfig()) {
          $processorObject->setPaymentProcessor($processor);
        }
      }
      $this->cache[$id] = $processorObject;
    }

    return $this->cache[$id];
  }

  /**
   * Execute checkConfig() on the payment processor Object.
   * This function creates a new instance of the processor object and returns the output of checkConfig
   *
   * @param array $processor
   *
   * @return string|NULL
   *
   * @throws \CRM_Core_Exception
   */
  public function checkProcessorConfig($processor) {
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
    return $processorObject->checkConfig();
  }

  /**
   * Get payment processor by it's ID.
   *
   * @param int $id
   *
   * @return \CRM_Core_Payment|NULL
   * @throws \CiviCRM_API3_Exception
   */
  public function getById($id) {
    if ($id == 0) {
      return new \CRM_Core_Payment_Manual();
    }
    $processor = civicrm_api3('payment_processor', 'getsingle', ['id' => $id, 'is_test' => NULL]);
    return self::getByProcessor($processor);
  }

  /**
   * @param string $name
   * @param bool $is_test
   *
   * @return \CRM_Core_Payment|NULL
   * @throws \CiviCRM_API3_Exception
   */
  public function getByName($name, $is_test) {
    $processor = civicrm_api3('payment_processor', 'getsingle', ['name' => $name, 'is_test' => $is_test]);
    return self::getByProcessor($processor);
  }

  /**
   * Flush processors from static cache.
   *
   * This is particularly used for tests.
   */
  public function flushProcessors() {
    $this->cache = [];
    if (isset(\Civi::$statics['CRM_Contribute_BAO_ContributionRecur'])) {
      unset(\Civi::$statics['CRM_Contribute_BAO_ContributionRecur']);
    }
    \CRM_Financial_BAO_PaymentProcessor::getAllPaymentProcessors('all', TRUE);
    \CRM_Financial_BAO_PaymentProcessor::getAllPaymentProcessors('live', TRUE);
    \CRM_Financial_BAO_PaymentProcessor::getAllPaymentProcessors('test', TRUE);
  }

  /**
   * Sometimes we want to instantiate a processor object when no valid instance exists (eg. when uninstalling a
   * processor).
   *
   * This function does not load instance specific details for the processor.
   *
   * @param string $className
   *
   * @return \Civi\Payment\CRM_Core_Payment|NULL
   * @throws \CiviCRM_API3_Exception
   */
  public function getByClass($className) {
    return $this->getByProcessor([
      'class_name' => $className,
      'id' => 0,
      'is_test' => 0,
    ],
    TRUE);
  }

}
