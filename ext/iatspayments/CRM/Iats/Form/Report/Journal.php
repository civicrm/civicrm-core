<?php

require_once('CRM/Report/Form.php');

/**
 * @file
 */

/**
 *
 * $Id$
 */
class CRM_Iats_Form_Report_Journal extends CRM_Report_Form {

  // protected $_customGroupExtends = array('Contact');

  /* static private $processors = array();
  static private $version = array();
  static private $financial_types = array();
  static private $prefixes = array(); */
  static private $contributionStatus = array(); 
  static private $transaction_types = array(
    'VISA' => 'Visa',
    'ACHEFT' => 'ACH/EFT',
    'UNKNOW' => 'Uknown',
    'MC' => 'MasterCard',
    'AMX' => 'AMEX',
    'DSC' => 'Discover',
  );

  /**
   *
   */
  public function __construct() {
    self::$contributionStatus = CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id');
    $this->_columns = array(
      'civicrm_iats_journal' =>
        array(
          'fields' =>
            array(
              'id' => array('title' => 'CiviCRM Journal Id', 'default' => TRUE),
              'iats_id' => array('title' => 'iATS Journal Id', 'default' => TRUE),
              'tnid' => array('title' => 'Transaction ID', 'default' => TRUE),
              'tntyp' => array('title' => 'Transaction type', 'default' => TRUE),
              'agt' => array('title' => 'Client/Agent code', 'default' => TRUE),
              'cstc' => array('title' => 'Customer code', 'default' => TRUE),
              'inv' => array('title' => 'Invoice Reference', 'default' => TRUE),
              'dtm' => array('title' => 'Transaction date', 'default' => TRUE),
              'amt' => array('title' => 'Amount', 'default' => TRUE),
              'rst' => array('title' => 'Result string', 'default' => TRUE),
              'dtm' => array('title' => 'Transaction Date Time', 'default' => TRUE),
              'status_id' => array('title' => 'Payment Status', 'default' => TRUE),
            ),
          'order_bys' => 
            array(
              'id' => array('title' => ts('CiviCRM Journal Id'), 'default' => TRUE, 'default_order' => 'DESC'),
              'iats_id' => array('title' => ts('iATS Journal Id')),
              'dtm' => array('title' => ts('Transaction Date Time')),
            ),
          'filters' =>
             array(
               'dtm' => array(
                 'title' => 'Transaction date', 
                 'operatorType' => CRM_Report_Form::OP_DATE,
                 'type' => CRM_Utils_Type::T_DATE,
               ),
               'inv' => array(
                 'title' => 'Invoice Reference', 
                 'type' => CRM_Utils_Type::T_STRING,
               ),
               'amt' => array(
                 'title' => 'Amount', 
                 'operatorType' => CRM_Report_Form::OP_FLOAT,
                 'type' => CRM_Utils_Type::T_FLOAT
               ),
               'tntyp' => array(
                 'title' => 'Type', 
                 'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                 'options' => self::$transaction_types,
                 'type' => CRM_Utils_Type::T_STRING,
               ),
               'agt' => array(
                 'title' => 'Client/Agent code',
                 'type' => CRM_Utils_Type::T_STRING,
               ),
               'rst' => array(
                 'title' => 'Result string',
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
    $this->_from = "FROM civicrm_iats_journal  {$this->_aliases['civicrm_iats_journal']}";
  }

}
