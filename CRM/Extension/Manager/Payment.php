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
 * This class stores logic for managing CiviCRM extensions.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Extension_Manager_Payment extends CRM_Extension_Manager_Base {

  /**
   @var CRM_Extension_Mapper
   */
  protected $mapper;

  /**
   * @param CRM_Extension_Mapper $mapper
   */
  public function __construct(CRM_Extension_Mapper $mapper) {
    parent::__construct(TRUE);
    $this->mapper = $mapper;
  }

  /**
   * {@inheritdoc}
   */
  public function onPreInstall(CRM_Extension_Info $info) {
    $paymentProcessorTypes = $this->_getAllPaymentProcessorTypes('class_name');

    if (array_key_exists($info->key, $paymentProcessorTypes)) {
      CRM_Core_Error::fatal('This payment processor type is already installed.');
    }

    $ppByName = $this->_getAllPaymentProcessorTypes('name');
    if (array_key_exists($info->name, $ppByName)) {
      CRM_Core_Error::fatal('This payment processor type already exists.');
    }

    $dao = new CRM_Financial_DAO_PaymentProcessorType();

    $dao->is_active   = 1;
    $dao->class_name  = trim($info->key);
    $dao->title       = trim($info->name) . ' (' . trim($info->key) . ')';
    $dao->name        = trim($info->name);
    $dao->description = trim($info->description);

    $dao->user_name_label = trim($info->typeInfo['userNameLabel']);
    $dao->password_label = trim($info->typeInfo['passwordLabel']);
    $dao->signature_label = trim($info->typeInfo['signatureLabel']);
    $dao->subject_label = trim($info->typeInfo['subjectLabel']);
    $dao->url_site_default = trim($info->typeInfo['urlSiteDefault']);
    $dao->url_api_default = trim($info->typeInfo['urlApiDefault']);
    $dao->url_recur_default = trim($info->typeInfo['urlRecurDefault']);
    $dao->url_site_test_default = trim($info->typeInfo['urlSiteTestDefault']);
    $dao->url_api_test_default = trim($info->typeInfo['urlApiTestDefault']);
    $dao->url_recur_test_default = trim($info->typeInfo['urlRecurTestDefault']);
    $dao->url_button_default = trim($info->typeInfo['urlButtonDefault']);
    $dao->url_button_test_default = trim($info->typeInfo['urlButtonTestDefault']);

    switch (trim($info->typeInfo['billingMode'])) {
      case 'form':
        $dao->billing_mode = CRM_Core_Payment::BILLING_MODE_FORM;
        break;

      case 'button':
        $dao->billing_mode = CRM_Core_Payment::BILLING_MODE_BUTTON;
        break;

      case 'notify':
        $dao->billing_mode = CRM_Core_Payment::BILLING_MODE_NOTIFY;
        break;

      default:
        CRM_Core_Error::fatal('Billing mode in info file has wrong value.');
    }

    $dao->is_recur = trim($info->typeInfo['isRecur']);
    $dao->payment_type = trim($info->typeInfo['paymentType']);

    $dao->save();
  }

  /**
   * {@inheritdoc}
   */
  public function onPostInstall(CRM_Extension_Info $info) {
    $this->_runPaymentHook($info, 'install');
  }

  /**
   * {@inheritdoc}
   */
  public function onPreUninstall(CRM_Extension_Info $info) {
    $paymentProcessorTypes = $this->_getAllPaymentProcessorTypes('class_name');
    if (!array_key_exists($info->key, $paymentProcessorTypes)) {
      CRM_Core_Error::fatal('This payment processor type is not registered.');
    }

    $dao = new CRM_Financial_DAO_PaymentProcessor();
    $dao->payment_processor_type_id = $paymentProcessorTypes[$info->key];
    $dao->find();
    while ($dao->fetch()) {
      throw new CRM_Extension_Exception_DependencyException('payment');
    }

    $this->_runPaymentHook($info, 'uninstall');
    return CRM_Financial_BAO_PaymentProcessorType::del($paymentProcessorTypes[$info->key]);
  }

  /**
   * {@inheritdoc}
   */
  public function onPreDisable(CRM_Extension_Info $info) {
    // HMM? // if ($this->type == 'payment' && $this->status != 'missing') {
    $this->_runPaymentHook($info, 'disable');

    $paymentProcessorTypes = $this->_getAllPaymentProcessorTypes('class_name');
    CRM_Financial_BAO_PaymentProcessorType::setIsActive($paymentProcessorTypes[$info->key], 0);
  }

  /**
   * {@inheritdoc}
   */
  public function onPreEnable(CRM_Extension_Info $info) {
    $paymentProcessorTypes = $this->_getAllPaymentProcessorTypes('class_name');
    CRM_Financial_BAO_PaymentProcessorType::setIsActive($paymentProcessorTypes[$info->key], 1);
  }

  /**
   * {@inheritdoc}
   */
  public function onPostEnable(CRM_Extension_Info $info) {
    // HMM? // if ($this->type == 'payment' && $this->status != 'missing') {
    $this->_runPaymentHook($info, 'enable');
  }

  /**
   * @param string $attr the attribute used to key the array
   * @return array ($$attr => $id)
   */
  private function _getAllPaymentProcessorTypes($attr) {
    $ppt = array();
    $dao = new CRM_Financial_DAO_PaymentProcessorType();
    $dao->find();
    while ($dao->fetch()) {
      $ppt[$dao->$attr] = $dao->id;
    }
    return $ppt;
  }

  /**
   * Function to run hooks in the payment processor class
   * Load requested payment processor and call the method specified.
   *
   * @param CRM_Extension_Info $info
   * @param string $method - the method to call in the payment processor class
   *
   * @private
   */
  private function _runPaymentHook(CRM_Extension_Info $info, $method) {
    // Not concerned about performance at this stage, as these are seldomly performed tasks
    // (payment processor enable/disable/install/uninstall). May wish to implement some
    // kind of registry/caching system if more hooks are added.

    try {
      $paymentClass = $this->mapper->keyToClass($info->key, 'payment');
      $file = $this->mapper->classToPath($paymentClass);
      if (! file_exists($file)) {
        CRM_Core_Session::setStatus(ts('Failed to load file (%3) for payment processor (%1) while running "%2"', array(1 => $info->key, 2 => $method, 3 => $file)), '', 'error');
        return;
      } else {
        require_once $file;
      }
    } catch (CRM_Extension_Exception $e) {
      CRM_Core_Session::setStatus(ts('Failed to determine file path for payment processor (%1) while running "%2"', array(1 => $info->key, 2 => $method)), '', 'error');
      return;
    }

    // See if we have any instances of this PP defined ..
    if ($processor_id = CRM_Core_DAO::singleValueQuery("
                SELECT pp.id
                  FROM civicrm_extension ext
            INNER JOIN civicrm_payment_processor_type ppt
                    ON ext.name = ppt.name
            INNER JOIN civicrm_payment_processor pp
                    ON ppt.id = pp.payment_processor_type_id
                 WHERE ext.type = 'payment'
                   AND ext.full_name = %1
        ",
        array(
          1 => array($info->key, 'String'),
        )
      )) {
      // If so, load params in the usual way ..
      $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($processor_id, NULL);
    }
    else {
      // Otherwise, do the best we can to construct some ..
      $dao = CRM_Core_DAO::executeQuery("
                    SELECT ppt.*
                      FROM civicrm_extension ext
                INNER JOIN civicrm_payment_processor_type ppt
                        ON ppt.name = ext.name
                     WHERE ext.name = %1
                       AND ext.type = 'payment'
            ",
        array(
          1 => array($info->name, 'String'),
        )
      );
      if ($dao->fetch()) $paymentProcessor = array(
        'id' => -1,
        'name' => $dao->title,
        'payment_processor_type_id' => $dao->id,
        'user_name' => 'nothing',
        'password' => 'nothing',
        'signature' => '',
        'url_site' => $dao->url_site_default,
        'url_api' => $dao->url_api_default,
        'url_recur' => $dao->url_recur_default,
        'url_button' => $dao->url_button_default,
        'subject' => '',
        'class_name' => $dao->class_name,
        'is_recur' => $dao->is_recur,
        'billing_mode' => $dao->billing_mode,
        'payment_type' => $dao->payment_type,
      );
      else CRM_Core_Error::fatal("Unable to find payment processor in " . __CLASS__ . '::' . __METHOD__);
    }

    // In the case of uninstall, check for instances of PP first.
    // Don't run hook if any are found.
    if ($method == 'uninstall' && $paymentProcessor['id'] > 0) {
      return;
    }

    switch ($method) {
      case 'install':
      case 'uninstall':
      case 'enable':
      case 'disable':

        // Instantiate PP
        $processorInstance = $paymentClass::singleton(NULL, $paymentProcessor);

        // Does PP implement this method, and can we call it?
        if (method_exists($processorInstance, $method) && is_callable(array(
          $processorInstance, $method))) {
          // If so, call it ...
          $processorInstance->$method();
        }
        break;

      default:
        CRM_Core_Session::setStatus(ts( "Unrecognized payment hook (%1) in %2::%3",
                        array(1 => $method, 2 =>  __CLASS__ , 3 => __METHOD__) ),
                        '', 'error');
    }
  }
}
