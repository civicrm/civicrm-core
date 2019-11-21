<?php

require_once('CRM/Report/Form.php');

/**
 * @file
 */

/**
 *
 * $Id$
 */
class CRM_Iats_Form_Report_Verify extends CRM_Report_Form {

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
      'civicrm_iats_verify' =>
        array(
          'fields' =>
            array(
              'id' => array('title' => 'CiviCRM Verify Id', 'default' => TRUE),
              'customer_code' => array('title' => 'Customer code', 'default' => TRUE),
              'cid' => array('title' => 'Contact', 'default' => TRUE),
              'contribution_id' => array('title' => 'Contribution', 'default' => TRUE),
              'recur_id' => array('title' => 'Recurring Contribution Id', 'default' => TRUE),
              'contribution_status_id' => array('title' => 'Payment Status', 'default' => TRUE),
              'verify_datetime' => array('title' => 'Verification date time', 'default' => TRUE),
            ),
          'order_bys' => 
            array(
              'id' => array('title' => ts('CiviCRM Verify Id'), 'default' => TRUE, 'default_order' => 'DESC'),
              'verify_datetime' => array('title' => ts('Verification Date Time')),
            ),
          'filters' =>
             array(
               'verify_datetime' => array(
                 'title' => 'Verification date time', 
                 'operatorType' => CRM_Report_Form::OP_DATE,
                 'type' => CRM_Utils_Type::T_DATE,
               ),
               'contribution_status_id' => array(
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
    $this->_from = "FROM civicrm_iats_verify  {$this->_aliases['civicrm_iats_verify']}";
  }

  /**
   *
   */
  public function alterDisplay(&$rows) {
    foreach ($rows as $rowNum => $row) {
      // Link to contact.
      if (
        ($value = CRM_Utils_Array::value('civicrm_iats_verify_cid', $row)) 
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $row['civicrm_iats_verify_cid'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_iats_verify_cid_link'] = $url;
        $rows[$rowNum]['civicrm_iats_verify_cid_hover'] = ts('View this contact.');
      }
      // Link to contribution.
      if (
        ($value = CRM_Utils_Array::value('civicrm_iats_verify_cid', $row)) 
        && ($value = CRM_Utils_Array::value('civicrm_iats_verify_contribution_id', $row)) 
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view/contribution',
          'reset=1&action=view&context=contribution&selectedChild=contribute&cid=' . $row['civicrm_iats_verify_cid'] . '&id=' . $row['civicrm_iats_verify_contribution_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_iats_verify_contribution_id_link'] = $url;
        $rows[$rowNum]['civicrm_iats_verify_contribution_id_hover'] = ts('View details of the verified contribution.');
      }

      // Link to recurring series.
      if (
        ($value = CRM_Utils_Array::value('civicrm_iats_verify_recur_id', $row)) 
        && ($value = CRM_Utils_Array::value('civicrm_iats_verify_cid', $row)) 
        && CRM_Core_Permission::check('access CiviContribute')
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view/contributionrecur",
          "reset=1&id=" . $row['civicrm_iats_verify_recur_id'] .
          "&cid=" . $row['civicrm_iats_verify_cid'] .
          "&action=view&context=contribution&selectedChild=contribute",
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_iats_verify_recur_id_link'] = $url;
        $rows[$rowNum]['civicrm_iats_verify_recur_id_hover'] = ts("View Details of this Recurring Series.");
        $entryFound = TRUE;
      }

      // Handle contribution status id.
      if ($value = CRM_Utils_Array::value('civicrm_iats_verify_contribution_status_id', $row)) {
        $rows[$rowNum]['civicrm_iats_verify_contribution_status_id'] = self::$contributionStatus[$value];
      }
      // Handle processor id.
      if ($value = CRM_Utils_Array::value('civicrm_iats_verify_recur_payment_processor_id', $row)) {
        $rows[$rowNum]['civicrm_iats_verify_recur_payment_processor_id'] = self::$processors[$value];
      }
    }
  }
}
