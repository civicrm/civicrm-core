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
class CRM_Financial_Form_Search extends CRM_Core_Form {

  public $_batchStatus;

  function preProcess() {
    $this->_batchStatus = CRM_Utils_Request::retrieve('batchStatus', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, NULL);
    $this->assign('batchStatus', $this->_batchStatus);
  }

  function setDefaultValues() {
    $defaults = array();
    $status = CRM_Utils_Request::retrieve('status', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, 1);
    $defaults['batch_update'] = $status;
    if ($this->_batchStatus) {
      $defaults['status_id'] = $this->_batchStatus;
    }
    return $defaults;
  }

  public function buildQuickForm() {
    $attributes = CRM_Core_DAO::getAttribute('CRM_Batch_DAO_Batch');
    $attributes['total']['class'] = $attributes['item_count']['class'] = 'number';
    $this->add('text', 'title', ts('Batch Name'), $attributes['title']);

    $this->add(
      'select',
      'status_id',
      ts('Batch Status'),
      array(
        '' => ts('- any -' ),
        1 => ts('Open'),
        2 => ts('Closed'),
        5 => ts('Exported'),
      ),
      false
    );

    $this->add(
      'select',
      'payment_instrument_id',
      ts('Payment Instrument'),
      array('' => ts('- any -' )) + CRM_Contribute_PseudoConstant::paymentInstrument(),
      false
    );

    $this->add('text', 'total', ts('Total Amount'), $attributes['total']);

    $this->add('text', 'item_count', ts('Number of Items'), $attributes['item_count']);
    $this->add('text', 'sort_name', ts('Created By'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

    $this->assign('elements', array('status_id', 'title', 'sort_name', 'payment_instrument_id', 'item_count', 'total'));
    $this->addElement('checkbox', 'toggleSelect', NULL, NULL, array('class' => 'select-rows'));
    $batchAction = array(
      'reopen' => ts('Re-open'),
      'close' => ts('Close'),
      'export' => ts('Export'),
      'delete' => ts('Delete'),
    );

    $this->add('select',
      'batch_update',
      ts('Task' ),
      array('' => ts('- actions -')) + $batchAction);

    $this->add('submit','submit', ts('Go'),
      array(
        'class' => 'form-submit',
        'id' => 'Go',
      ));

    $this->addButtons(
      array(
        array(
          'type' => 'refresh',
          'name' => ts('Search'),
          'isDefault' => TRUE,
        )
      )
    );
    parent::buildQuickForm();
  }

  function postProcess() {
    $batchIds = array();
    foreach ($_POST as $key => $value) {
      if (substr($key,0,6) == "check_") {
        $batch = explode("_",$key);
        $batchIds[] = $batch[1];
      }
    }
    if (CRM_Utils_Array::value('batch_update', $_POST)) {
      CRM_Batch_BAO_Batch::closeReOpen($batchIds, $_POST['batch_update']);
    }
  }
}

