<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Financial_BAO_PaymentProcessorType extends CRM_Financial_DAO_PaymentProcessorType implements \Civi\Core\HookInterface {

  /**
   * Static holder for the default payment processor.
   * @var object
   */
  public static $_defaultPaymentProcessorType = NULL;

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * @deprecated - this bypasses hooks.
   * @param int $id
   * @param bool $is_active
   * @return bool
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Financial_DAO_PaymentProcessorType', $id, 'is_active', $is_active);
  }

  /**
   * Retrieve the default payment processor.
   *
   * @return object
   *   The default payment processor object on success,
   *                          null otherwise
   */
  public static function &getDefault() {
    if (self::$_defaultPaymentProcessorType == NULL) {
      $params = ['is_default' => 1];
      $defaults = [];
      self::$_defaultPaymentProcessorType = self::retrieve($params, $defaults);
    }
    return self::$_defaultPaymentProcessorType;
  }

  /**
   * Add the payment-processor type in the db
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @throws Exception
   * @return CRM_Financial_DAO_PaymentProcessorType
   */
  public static function create(&$params) {
    $paymentProcessorType = new CRM_Financial_DAO_PaymentProcessorType();
    $paymentProcessorType->copyValues($params);

    /* @codingStandardsIgnoreStart
    // adapted from CRM_Core_Extensions_Payment::install
    foreach (array(
      'class_name',
      'title',
      'name',
      'description',
      'user_name_label',
      'password_label',
      'signature_label',
      'subject_label',
      'url_site_default',
      'url_api_default',
      'url_recur_default',
      'url_site_test_default',
      'url_api_test_default',
      'url_recur_test_default',
      'url_button_default',
      'url_button_test_default',
      'billing_mode',
      'is_recur',
      'payment_type'
      ) as $trimmable) {
      if (isset($paymentProcessorType->{$trimmable})) {
        $paymentProcessorType->{$trimmable} = trim($paymentProcessorType->{$trimmable});
      }
    }
    @codingStandardsIgnoreEnd */

    if (isset($paymentProcessorType->billing_mode)) {
      // ugh unidirectional manipulation
      if (!is_numeric($paymentProcessorType->billing_mode)) {
        $billingModes = array_flip(self::buildOptions('billing_mode'));
        if (array_key_exists($paymentProcessorType->billing_mode, $billingModes)) {
          $paymentProcessorType->billing_mode = $billingModes[$paymentProcessorType->billing_mode];
        }
      }
      if (!array_key_exists($paymentProcessorType->billing_mode, self::buildOptions('billing_mode'))) {
        throw new Exception("Unrecognized billing_mode");
      }
    }

    // FIXME handle is_default
    if (!empty($paymentProcessorType->id)) {
      $ppByName = self::getAllPaymentProcessorTypes('name');
      if (array_key_exists($paymentProcessorType->name, $ppByName)) {
        if ($ppByName[$paymentProcessorType->name] != $paymentProcessorType->id) {
          throw new CRM_Core_Exception('This payment processor type already exists.');
        }
      }
    }

    return $paymentProcessorType->save();
  }

  /**
   * Delete payment processor.
   *
   * @param int $paymentProcessorTypeId
   * @deprecated
   * @return bool|NULL
   */
  public static function del($paymentProcessorTypeId) {
    try {
      static::deleteRecord(['id' => $paymentProcessorTypeId]);
      // This message is bad on so many levels
      CRM_Core_Session::setStatus(ts('Selected Payment Processor type has been deleted.<br/>'), '', 'success');
      return TRUE;
    }
    catch (CRM_Core_Exception $e) {
      CRM_Core_Session::setStatus($e->getMessage(), ts('Deletion Error'), 'error');
      return NULL;
    }
  }

  /**
   * Callback for hook_civicrm_pre().
   * @param \Civi\Core\Event\PreEvent $event
   * @throws CRM_Core_Exception
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if ($event->action === 'delete') {
      $query = "
SELECT pp.id processor_id
FROM civicrm_payment_processor pp, civicrm_payment_processor_type ppt
WHERE pp.payment_processor_type_id = ppt.id AND ppt.id = %1";

      $params = [1 => [$event->id, 'Integer']];
      $dao = CRM_Core_DAO::executeQuery($query, $params);

      if ($dao->fetch()) {
        throw new CRM_Core_Exception(ts('There is a Payment Processor associated with selected Payment Processor type, hence it can not be deleted.'));
      }
    }
  }

  /**
   * @param string $attr
   *
   * @return array
   */
  private static function getAllPaymentProcessorTypes($attr) {
    $ppt = [];
    $dao = new CRM_Financial_DAO_PaymentProcessorType();
    $dao->find();
    while ($dao->fetch()) {
      $ppt[$dao->$attr] = $dao->id;
    }
    return $ppt;
  }

}
