<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Financial_BAO_PaymentProcessorType extends CRM_Financial_DAO_PaymentProcessorType {

  /**
   * Static holder for the default payment processor.
   */
  static $_defaultPaymentProcessorType = NULL;

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Core_BAO_LocationType|null
   *   object on success, null otherwise
   */
  public static function retrieve(&$params, &$defaults) {
    $paymentProcessorType = new CRM_Financial_DAO_PaymentProcessorType();
    $paymentProcessorType->copyValues($params);
    if ($paymentProcessorType->find(TRUE)) {
      CRM_Core_DAO::storeValues($paymentProcessorType, $defaults);
      return $paymentProcessorType;
    }
    return NULL;
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return bool
   *   true if we found and updated the object, else false
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
          CRM_Core_Error::fatal('This payment processor type already exists.');
        }
      }
    }

    return $paymentProcessorType->save();
  }

  /**
   * Delete payment processor.
   *
   * @param int $paymentProcessorTypeId
   *   ID of the processor to be deleted.
   *
   * @return bool|NULL
   */
  public static function del($paymentProcessorTypeId) {
    $query = "
SELECT pp.id processor_id
FROM civicrm_payment_processor pp, civicrm_payment_processor_type ppt
WHERE pp.payment_processor_type_id = ppt.id AND ppt.id = %1";

    $params = [1 => [$paymentProcessorTypeId, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($query, $params);

    if ($dao->fetch()) {
      CRM_Core_Session::setStatus(ts('There is a Payment Processor associated with selected Payment Processor type, hence it can not be deleted.'), ts('Deletion Error'), 'error');
      return NULL;
    }

    $paymentProcessorType = new CRM_Financial_DAO_PaymentProcessorType();
    $paymentProcessorType->id = $paymentProcessorTypeId;
    if ($paymentProcessorType->delete()) {
      CRM_Core_Session::setStatus(ts('Selected Payment Processor type has been deleted.<br/>'), '', 'success');
      return TRUE;
    }
  }

  /**
   * @param $attr
   *
   * @return array
   */
  static private function getAllPaymentProcessorTypes($attr) {
    $ppt = [];
    $dao = new CRM_Financial_DAO_PaymentProcessorType();
    $dao->find();
    while ($dao->fetch()) {
      $ppt[$dao->$attr] = $dao->id;
    }
    return $ppt;
  }

}
