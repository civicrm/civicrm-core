<?php

require_once('CRM/Report/Form.php');

/**
 * @file
 */

/**
 *
 * $Id$
 */
class CRM_Iats_Form_Report_JournalFaps extends CRM_Report_Form {

  // protected $_customGroupExtends = array('Contact');

  /* static private $processors = array();
  static private $version = array();
  static private $financial_types = array();
  static private $prefixes = array(); */
  static private $contributionStatus = array(); 
  static private $card_types = array( 
    'Visa' => 'Visa',
    'Mastercard' => 'MasterCard',
    'AMEX' => 'AMEX',
    'Discover' => 'Discover',
  );

  /**
   *
   */
  public function __construct() {
    self::$contributionStatus = CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id');
    $this->_columns = array(
      'civicrm_iats_faps_journal' =>
        array(
          'fields' =>
            array(
              'id' => array('title' => 'CiviCRM Journal Id', 'default' => TRUE),
              'transactionId' => array('title' => '1stPay Transaction Id', 'default' => TRUE),
              'isAch' => array('title' => 'isACH', 'default' => TRUE),
              'processorId' => array('title' => 'Processor Id', 'default' => TRUE),
              'cimRefNumber' => array('title' => 'Customer code', 'default' => TRUE),
              'orderId' => array('title' => 'Invoice Reference', 'default' => TRUE),
              'transDateAndTime' => array('title' => 'Transaction date', 'default' => TRUE),
              'amount' => array('title' => 'Amount', 'default' => TRUE),
              'authResponse' => array('title' => 'Response string', 'default' => TRUE),
              'currency' => array('title' => 'Currency', 'default' => TRUE),
              'status_id' => array('title' => 'Payment Status', 'default' => TRUE),
            ),
          'order_bys' => 
            array(
              'id' => array('title' => ts('CiviCRM Journal Id'), 'default' => TRUE, 'default_order' => 'DESC'),
              'transactionId' => array('title' => ts('1stPay Transaction Id')),
              'transDateAndTime' => array('title' => ts('Transaction Date Time')),
            ),
          'filters' =>
             array(
               'transDateAndTime' => array(
                 'title' => 'Transaction date', 
                 'operatorType' => CRM_Report_Form::OP_DATE,
                 'type' => CRM_Utils_Type::T_DATE,
               ),
               'orderId' => array(
                 'title' => 'Invoice Reference', 
                 'type' => CRM_Utils_Type::T_STRING,
               ),
               'amount' => array(
                 'title' => 'Amount', 
                 'operatorType' => CRM_Report_Form::OP_FLOAT,
                 'type' => CRM_Utils_Type::T_FLOAT
               ),
               /*'isAch' => array(
                 'title' => 'Type', 
                 'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                 'options' => self::$transaction_types,
                 'type' => CRM_Utils_Type::T_STRING,
               ), */
               'processorId' => array(
                 'title' => 'Processor Id',
                 'type' => CRM_Utils_Type::T_STRING,
               ),
               'authResponse' => array(
                 'title' => 'Response string',
                 'type' => CRM_Utils_Type::T_STRING,
               ),
               'status_id' => array(
                 'title' => ts('Payment Status'),
                 'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                 'options' => self::$contributionStatus,
                 'type' => CRM_Utils_Type::T_INT,
               ),
             ),
        ),
    );
    parent::__construct();
  }

  /**
   *
   */
  public function getTemplateName() {
    return 'CRM/Report/Form.tpl';
  }

  /**
   *
   */
  public function from() {
    $this->_from = "FROM civicrm_iats_faps_journal  {$this->_aliases['civicrm_iats_faps_journal']}";
  }

}
