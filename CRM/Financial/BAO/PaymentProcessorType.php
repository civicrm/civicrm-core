<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Financial_BAO_PaymentProcessorType extends CRM_Financial_DAO_PaymentProcessorType {

  /**
   * static holder for the default payment processor
   */
  static $_defaultPaymentProcessorType = NULL;

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Core_BAO_LocaationType object on success, null otherwise
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $paymentProcessorType = new CRM_Financial_DAO_PaymentProcessorType();
    $paymentProcessorType->copyValues($params);
    if ($paymentProcessorType->find(TRUE)) {
      CRM_Core_DAO::storeValues($paymentProcessorType, $defaults);
      return $paymentProcessorType;
    }
    return NULL;
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on sucess, null otherwise
   *
   * @access public
   * @static
   */
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Financial_DAO_PaymentProcessorType', $id, 'is_active', $is_active);
  }

  /**
   * retrieve the default payment processor
   *
   * @param NULL
   *
   * @return object           The default payment processor object on success,
   *                          null otherwise
   * @static
   * @access public
   */
  static function &getDefault() {
    if (self::$_defaultPaymentProcessorType == NULL) {
      $params = array('is_default' => 1);
      $defaults = array();
      self::$_defaultPaymentProcessorType = self::retrieve($params, $defaults);
    }
    return self::$_defaultPaymentProcessorType;
  }

  /**
   * Function to add the payment-processor type in the db
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   *
   * @throws Exception
   * @internal param array $ids the array that holds all the db ids
   *
   * @return object CRM_Financial_DAO_PaymentProcessorType
   * @access public
   * @static
   */
  static function create(&$params) {
    $paymentProcessorType = new CRM_Financial_DAO_PaymentProcessorType();
    $paymentProcessorType->copyValues($params);

    /*
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
    */

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
   * Function to delete payment processor
   *
   * @param  int $paymentProcessorTypeId ID of the processor to be deleted.
   *
   * @return bool
   * @access public
   * @static
   */
  static function del($paymentProcessorTypeId) {
    $query = "
SELECT pp.id processor_id
FROM civicrm_payment_processor pp, civicrm_payment_processor_type ppt
WHERE pp.payment_processor_type_id = ppt.id AND ppt.id = %1";

    $params = array(1 => array($paymentProcessorTypeId, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);

    if ($dao->fetch()) {
      CRM_Core_Session::setStatus(ts('There is a Payment Processor associated with selected Payment Processor type, hence it can not be deleted.'), ts('Deletion Error'), 'error');
      return;
    }

    $paymentProcessorType = new CRM_Financial_DAO_PaymentProcessorType();
    $paymentProcessorType->id = $paymentProcessorTypeId;
    if ($paymentProcessorType->delete()) {
      CRM_Core_Session::setStatus(ts('Selected Payment Processor type has been deleted.<br>'), '', 'success');
      return TRUE;
    }
  }

  /**
   * @param $attr
   *
   * @return array
   */
  static private function getAllPaymentProcessorTypes($attr) {
    $ppt = array();
    $dao = new CRM_Financial_DAO_PaymentProcessorType();
    $dao->find();
    while ($dao->fetch()) {
      $ppt[$dao->$attr] = $dao->id;
    }
    return $ppt;
  }

}

