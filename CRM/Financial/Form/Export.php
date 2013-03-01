<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class provides the functionality to delete a group of
 * contributions. This class provides functionality for the actual
 * deletion.
 */
class CRM_Financial_Form_Export extends CRM_Core_Form {

  /**
   * The financial batch id, used when editing the field
   *
   * @var int
   * @access protected
   */
  protected $_id;

  /**
   * Financial batch ids
   */
  protected $_batchIds = array();

  /**
   * Export status id
   */
  protected $_exportStatusId;

  /**
   * Export format
   */
  protected $_exportFormat;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    // this mean it's a batch action
    if (!$this->_id ) {
      if (!empty($_GET['batch_id'])) {
        //validate batch ids
        $batchIds = explode(',', $_GET['batch_id']);
        foreach($batchIds as $batchId) {
          CRM_Utils_Type::validate($batchId,'Positive');
        }

        $this->_batchIds = $_GET['batch_id'];
        $this->set('batchIds', $this->_batchIds);
      }
      else {
        $this->_batchIds = $this->get('batchIds');
      }
      if (!empty($_GET['export_format']) && in_array($_GET['export_format'], array('IIF', 'CSV'))) {
        $this->_exportFormat = $_GET['export_format'];
      }
    }
    else {
      $this->_batchIds = $this->_id;
    }

    $allBatchStatus = CRM_Core_PseudoConstant::accountOptionValues('batch_status');
    $this->_exportStatusId = CRM_Utils_Array::key('Exported', $allBatchStatus);

    //check if batch status is valid, do not allow exported batches to export again
    $batchStatus = CRM_Batch_BAO_Batch::getBatchStatuses($this->_batchIds);

    foreach( $batchStatus as $batchStatusId ) {
      if ($batchStatusId == $this->_exportStatusId) {
       CRM_Core_Error::fatal(ts('You cannot exported the batches which were exported earlier.'));
      }
    }

    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/financial/financialbatches',
      "reset=1&batchStatus=5"));
  }
  
  /**
   * Build the form
   *
   * @access public
   * @return void
   */
  function buildQuickForm() {
    // this mean it's a batch action
    if (!empty($this->_batchIds)) {
      $batchNames = CRM_Batch_BAO_Batch::getBatchNames($this->_batchIds);
      $this->assign('batchNames', $batchNames);
      // Skip building the form if we already have batches and an export format
      if ($this->_exportFormat) {
        $this->postProcess();
      }
    }

    $optionTypes = array(
      'IIF' => ts('Export to IIF'),
      'CSV' => ts('Export to CSV'),
    );

    $this->addRadio('export_format', NULL, $optionTypes, NULL, '<br/>', TRUE);

    $this->addButtons(
      array(
        array(
          'type' => 'next',
          'name' => ts('Export Batch'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }
  
  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   * @return None
   */
  public function postProcess( ) {
    if (!$this->_exportFormat) {
      $params = $this->exportValues();
      $this->_exportFormat = $params['export_format'];
    }

    if ($this->_id) {
      $batchIds = array($this->_id);
    }
    else if (!empty($this->_batchIds)) {
      $batchIds = explode(',', $this->_batchIds);
    }
    // Recalculate totals
    $totals = CRM_Batch_BAO_Batch::batchTotals($batchIds);

    // build batch params
    $session = CRM_Core_Session::singleton();
    $batchParams['modified_date'] = date('YmdHis');
    $batchParams['modified_id'] = $session->get('userID');
    $batchParams['status_id'] = $this->_exportStatusId;

    $ids = array();
    foreach($batchIds as $batchId) {
      $batchParams['id'] = $ids['batchID'] = $batchId;
      // Update totals
      $batchParams = array_merge($batchParams, $totals[$batchId]);
      CRM_Batch_BAO_Batch::create($batchParams, $ids, 'financialBatch');
    }

    CRM_Batch_BAO_Batch::exportFinancialBatch($batchIds, $this->_exportFormat);
  }
}
