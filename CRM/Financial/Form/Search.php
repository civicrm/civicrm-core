<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
    $defaults = [];
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

    $batchStatus = CRM_Batch_DAO_Batch::buildOptions('status_id', 'validate');
    $this->add(
      'select',
      'status_id',
      ts('Batch Status'),
      [
        '' => ts('- any -'),
        array_search('Open', $batchStatus) => ts('Open'),
        array_search('Closed', $batchStatus) => ts('Closed'),
        array_search('Exported', $batchStatus) => ts('Exported'),
      ],
      FALSE
    );

    $this->add(
      'select',
      'payment_instrument_id',
      ts('Payment Method'),
      ['' => ts('- any -')] + CRM_Contribute_PseudoConstant::paymentInstrument(),
      FALSE
    );

    $this->add('text', 'total', ts('Total Amount'), $attributes['total']);

    $this->add('text', 'item_count', ts('Number of Items'), $attributes['item_count']);
    $this->add('text', 'sort_name', ts('Created By'), CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name'));

    $this->assign('elements', ['status_id', 'title', 'sort_name', 'payment_instrument_id', 'item_count', 'total']);
    $this->addElement('checkbox', 'toggleSelect', NULL, NULL, ['class' => 'select-rows']);
    $batchAction = [
      'reopen' => ts('Re-open'),
      'close' => ts('Close'),
      'export' => ts('Export'),
      'delete' => ts('Delete'),
    ];

    foreach ($batchAction as $action => $ignore) {
      if (!CRM_Batch_BAO_Batch::checkBatchPermission($action)) {
        unset($batchAction[$action]);
      }
    }
    $this->add('select',
      'batch_update',
      ts('Task'),
      ['' => ts('- actions -')] + $batchAction);

    $this->add('xbutton', 'submit', ts('Go'),
      [
        'type' => 'submit',
        'class' => 'crm-form-submit',
        'id' => 'Go',
      ]);

    $this->addButtons(
      [
        [
          'type' => 'refresh',
          'name' => ts('Search'),
          'isDefault' => TRUE,
        ],
      ]
    );
    parent::buildQuickForm();
  }

  public function postProcess() {
    $batchIds = [];
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
