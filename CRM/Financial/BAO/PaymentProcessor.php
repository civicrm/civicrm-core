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

/**
 * This class contains payment processor related functions.
 */
class CRM_Financial_BAO_PaymentProcessor extends CRM_Financial_DAO_PaymentProcessor
{
  /**
   * static holder for the default payment processor
   */
  static $_defaultPaymentProcessor = NULL;

  /*
   * Create Payment Processor
   *
   * @params array parameters for Processor entity
   */
  /**
   * @param $params
   *
   * @return CRM_Financial_DAO_PaymentProcessor
   * @throws Exception
   */
  static function create($params) {
    // FIXME Reconcile with CRM_Admin_Form_PaymentProcessor::updatePaymentProcessor
    $processor = new CRM_Financial_DAO_PaymentProcessor();
    $processor->copyValues($params);

    $ppTypeDAO = new CRM_Financial_DAO_PaymentProcessorType();
    $ppTypeDAO->id = $params['payment_processor_type_id'];
    if (!$ppTypeDAO->find(TRUE)) {
      CRM_Core_Error::fatal(ts('Could not find payment processor meta information'));
    }

    // also copy meta fields from the info DAO
    $processor->is_recur = $ppTypeDAO->is_recur;
    $processor->billing_mode = $ppTypeDAO->billing_mode;
    $processor->class_name = $ppTypeDAO->class_name;
    $processor->payment_type = $ppTypeDAO->payment_type;

    $processor->save();
    // CRM-11826, add entry in civicrm_entity_financial_account
    // if financial_account_id is not NULL
    if (!empty($params['financial_account_id'])) {
      $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Asset Account is' "));
      $values = array(
        'entity_table' => 'civicrm_payment_processor',
        'entity_id' => $processor->id,
        'account_relationship' => $relationTypeId,
        'financial_account_id' => $params['financial_account_id']
      );
      CRM_Financial_BAO_FinancialTypeAccount::add($values);
    }
    return $processor;
  }

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
     * @return object CRM_Financial_DAO_PaymentProcessor object on success, null otherwise
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $paymentProcessor = new CRM_Financial_DAO_PaymentProcessor();
    $paymentProcessor->copyValues($params);
    if ($paymentProcessor->find(TRUE)) {
      CRM_Core_DAO::storeValues($paymentProcessor, $defaults);
      return $paymentProcessor;
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
    return CRM_Core_DAO::setFieldValue('CRM_Financial_DAO_PaymentProcessor', $id, 'is_active', $is_active);
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
    if (self::$_defaultPaymentProcessor == NULL) {
      $params = array('is_default' => 1);
      $defaults = array();
      self::$_defaultPaymentProcessor = self::retrieve($params, $defaults);
    }
    return self::$_defaultPaymentProcessor;
  }

  /**
   * Function  to delete payment processor
   *
   * @param $paymentProcessorID
   *
   * @return null
   * @internal param int $paymentProcessorId ID of the processor to be deleted.
   *
   * @access public
   * @static
   */
  static function del($paymentProcessorID) {
    if (!$paymentProcessorID) {
      CRM_Core_Error::fatal(ts('Invalid value passed to delete function'));
    }

    $dao = new CRM_Financial_DAO_PaymentProcessor();
    $dao->id = $paymentProcessorID;
    if (!$dao->find(TRUE)) {
      return NULL;
    }

    $testDAO = new CRM_Financial_DAO_PaymentProcessor();
    $testDAO->name = $dao->name;
    $testDAO->is_test = 1;
    $testDAO->delete();

    $dao->delete();
  }

  /**
   * Function to get the payment processor details
   *
   * @param  int    $paymentProcessorID payment processor id
   * @param  string $mode               payment mode ie test or live
   *
   * @return array  associated array with payment processor related fields
   * @static
   * @access public
   */
  static function getPayment($paymentProcessorID, $mode) {
    if (!$paymentProcessorID) {
      CRM_Core_Error::fatal(ts('Invalid value passed to getPayment function'));
    }

    $dao            = new CRM_Financial_DAO_PaymentProcessor( );
    $dao->id        = $paymentProcessorID;
    $dao->is_active = 1;
    if (!$dao->find(TRUE)) {
      return NULL;
    }

    if ($mode == 'test') {
      $testDAO = new CRM_Financial_DAO_PaymentProcessor( );
      $testDAO->name      = $dao->name;
      $testDAO->is_active = 1;
      $testDAO->is_test   = 1;
      if (!$testDAO->find(TRUE)) {
        CRM_Core_Error::fatal(ts('Could not retrieve payment processor details'));
      }
      return self::buildPayment($testDAO, $mode);
    }
    else {
      return self::buildPayment($dao, $mode);
    }
  }

  /**
   * @param $paymentProcessorIDs
   * @param $mode
   *
   * @return array
   * @throws Exception
   */
  static function getPayments($paymentProcessorIDs, $mode) {
    if (!$paymentProcessorIDs) {
      CRM_Core_Error::fatal(ts('Invalid value passed to getPayment function'));
    }

    $payments = array( );
    foreach ($paymentProcessorIDs as $paymentProcessorID) {
      $payment = self::getPayment($paymentProcessorID, $mode);
      $payments[$payment['id']] = $payment;
    }

    uasort($payments, 'self::defaultComparison');
    return $payments;
  }

  /**
   * compare 2 payment processors to see which should go first based on is_default
   * (sort function for sortDefaultFirst)
   * @param array $processor1
   * @param array_type $processor2
   * @return number
   */
    static function defaultComparison($processor1, $processor2){
      $p1 = CRM_Utils_Array::value('is_default', $processor1);
      $p2 = CRM_Utils_Array::value('is_default', $processor2);
      if ($p1 == $p2) {
        return 0;
      }
      return ($p1 > $p2) ? -1 : 1;
    }

  /**
   * Function to build payment processor details
   *
   * @param object $dao   payment processor object
   * @param  string $mode payment mode ie test or live
   *
   * @return array  associated array with payment processor related fields
   * @static
   * @access public
   */
  static function buildPayment($dao, $mode) {
    $fields = array(
      'id', 'name', 'payment_processor_type_id', 'user_name', 'password',
      'signature', 'url_site', 'url_api', 'url_recur', 'url_button',
      'subject', 'class_name', 'is_recur', 'billing_mode',
      'payment_type', 'is_default',
    );
    $result = array();
    foreach ($fields as $name) {
      $result[$name] = $dao->$name;
    }
    $result['payment_processor_type'] = CRM_Core_PseudoConstant::paymentProcessorType(FALSE, $dao->payment_processor_type_id, 'name');

    $result['instance'] =& CRM_Core_Payment::singleton($mode, $result);

    return $result;
  }

  /**
   * Function to retrieve payment processor id / info/ object based on component-id.
   *
   * @param $entityID
   * @param string $component component
   * @param string $type type of payment information to be retrieved
   *
   * @internal param int $componentID id of a component
   * @return id / array / object based on type
   * @static
   * @access public
   */
  static function getProcessorForEntity($entityID, $component = 'contribute', $type = 'id') {
    $result = NULL;
    if (!in_array($component, array(
      'membership', 'contribute', 'recur'))) {
      return $result;
    }
    //FIXME:
    if ($component == 'membership') {
      $sql = "
    SELECT cr.payment_processor_id as ppID1, cp.payment_processor as ppID2, con.is_test
      FROM civicrm_membership mem
INNER JOIN civicrm_membership_payment mp  ON ( mem.id = mp.membership_id )
INNER JOIN civicrm_contribution       con ON ( mp.contribution_id = con.id )
 LEFT JOIN civicrm_contribution_recur cr  ON ( mem.contribution_recur_id = cr.id )
 LEFT JOIN civicrm_contribution_page  cp  ON ( con.contribution_page_id  = cp.id )
     WHERE mp.membership_id = %1";
    }
    elseif ($component == 'contribute') {
      $sql = "
    SELECT cr.payment_processor_id as ppID1, cp.payment_processor as ppID2, con.is_test
      FROM civicrm_contribution       con
 LEFT JOIN civicrm_contribution_recur cr  ON ( con.contribution_recur_id = cr.id )
 LEFT JOIN civicrm_contribution_page  cp  ON ( con.contribution_page_id  = cp.id )
     WHERE con.id = %1";
    }
    elseif ($component == 'recur') {
      $sql = "
    SELECT cr.payment_processor_id as ppID1, NULL as ppID2, cr.is_test
      FROM civicrm_contribution_recur cr
     WHERE cr.id = %1";
    }

    //we are interesting in single record.
    $sql .= ' LIMIT 1';

    $params = array(1 => array($entityID, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    if (!$dao->fetch()) {

      return $result;

    }

    $ppID = (isset($dao->ppID1) && $dao->ppID1) ? $dao->ppID1 : (isset($dao->ppID2) ? $dao->ppID2 : NULL);
    $mode = (isset($dao->is_test) && $dao->is_test) ? 'test' : 'live';
    if (!$ppID || $type == 'id') {
      $result = $ppID;
    }
    elseif ($type == 'info') {
      $result = self::getPayment($ppID, $mode);
    }
    elseif ($type == 'obj') {
      $payment = self::getPayment($ppID, $mode);
      $result = CRM_Core_Payment::singleton($mode, $payment);
    }

    return $result;
  }
}

