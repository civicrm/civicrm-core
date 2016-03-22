<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * @todo Add comments if possible.
 */
class CRM_Financial_Form_Search extends CRM_Core_Form {

  public $_batchStatus;

  public function preProcess() {
    $this->_batchStatus = CRM_Utils_Request::retrieve('batchStatus', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, NULL);
    $this->assign('batchStatus', $this->_batchStatus);
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    $defaults = array();
    $status = CRM_Utils_Request::retrieve('status', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, 1);
    $defaults['batch_update'] = $status;
    if ($this->_batchStatus) {
      $defaults['status_id'] = $this->_batchStatus;
    }
    return $defaults;
  }

  public function buildQuickForm() {
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'packages/jquery/plugins/jquery.redirect.min.js', 0, 'html-header');
    $attributes = CRM_Core_DAO::getAttribute('CRM_Batch_DAO_Batch');
    $attributes['total']['class'] = $attributes['item_count']['class'] = 'number';
    $this->add('text', 'title', ts('Batch Name'), $attributes['title']);

    $batchStatus = CRM_Core_PseudoConstant::get('CRM_Batch_DAO_Batch', 'status_id', array('labelColumn' => 'name'));
    $this->add(
      'select',
      'status_id',
      ts('Batch Status'),
      array(
        '' => ts('- any -'),
        array_search('Open', $batchStatus) => ts('Open'),
        array_search('Closed', $batchStatus) => ts('Closed'),
        array_search('Exported', $batchStatus) => ts('Exported'),
      ),
      FALSE
    );

    $this->add(
      'select',
      'payment_instrument_id',
      ts('Payment Method'),
      array('' => ts('- any -')) + CRM_Contribute_PseudoConstant::paymentInstrument(),
      FALSE
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
      ts('Task'),
      array('' => ts('- actions -')) + $batchAction);

    $this->add('submit', 'submit', ts('Go'),
      array(
        'class' => 'crm-form-submit',
        'id' => 'Go',
      ));

    $this->addButtons(
      array(
        array(
          'type' => 'refresh',
          'name' => ts('Search'),
          'isDefault' => TRUE,
        ),
      )
    );
    parent::buildQuickForm();
  }

  public function postProcess() {
    $batchIds = array();
    foreach ($_POST as $key => $value) {
      if (substr($key, 0, 6) == "check_") {
        $batch = explode("_", $key);
        $batchIds[] = $batch[1];
      }
    }
    if (!empty($_POST['batch_update'])) {
      CRM_Batch_BAO_Batch::closeReOpen($batchIds, $_POST['batch_update']);
    }
  }

}
