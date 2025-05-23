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
 * This class generates form components for Financial Type
 */
class CRM_Financial_Form_BatchTransaction extends CRM_Contribute_Form_Search {
  public static $_links = NULL;
  public static $_entityID;

  /**
   * Batch status.
   * @var int
   */
  protected $_batchStatusId;

  /**
   * Batch status name.
   *
   * @var string
   */
  protected $_batchStatus = 'open';

  /**
   * Batch values
   * @var array
   */
  public $_values;

  public function preProcess() {
    // This reuses some styles from search forms
    CRM_Core_Resources::singleton()->addStyleFile('civicrm', 'css/searchForm.css', 1, 'html-header');
    self::$_entityID = CRM_Utils_Request::retrieve('bid', 'Positive') ?:  $_POST['batch_id'] ?? NULL;
    $this->assign('entityID', self::$_entityID);
    if (isset(self::$_entityID)) {
      $this->_batchStatusId = CRM_Core_DAO::getFieldValue('CRM_Batch_BAO_Batch', self::$_entityID, 'status_id');
      $this->_batchStatus = CRM_Core_PseudoConstant::getName('CRM_Batch_DAO_Batch', 'status_id', $this->_batchStatusId);
      $this->assign('statusID', $this->_batchStatusId);
      $validStatus = FALSE;
      if (in_array($this->_batchStatus, ['Open', 'Reopened'])) {
        $validStatus = TRUE;
      }
      $this->assign('validStatus', $validStatus);
      $this->_values = civicrm_api3('Batch', 'getSingle', ['id' => self::$_entityID]);
      $batchTitle = CRM_Core_DAO::getFieldValue('CRM_Batch_BAO_Batch', self::$_entityID, 'title');
      $this->setTitle(ts('Accounting Batch - %1', [1 => $batchTitle]));

      $columnHeaders = [
        'created_by' => ts('Created By'),
        'status' => ts('Status'),
        'description' => ts('Description'),
        'payment_instrument' => ts('Payment Method'),
        'item_count' => ts('Expected Number of Items'),
        'assigned_item_count' => ts('Actual Number of Items'),
        'total' => ts('Expected Total Amount'),
        'assigned_total' => ts('Actual Total Amount'),
        'opened_date' => ts('Opened'),
      ];
      $this->assign('columnHeaders', $columnHeaders);
    }
    $this->assign('batchStatus', $this->_batchStatus);
    $this->assign('financialAJAXQFKey', CRM_Core_key::get('CRM_Financial_Page_AJAX'));
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    if ($this->_batchStatus === 'Closed') {
      $this->add('xbutton', 'export_batch', ts('Export Batch'), ['type' => 'submit']);
    }

    // do not build rest of form unless it is open/reopened batch
    if (!in_array($this->_batchStatus, ['Open', 'Reopened'])) {
      return;
    }

    parent::buildQuickForm();
    if (CRM_Batch_BAO_Batch::checkBatchPermission('close', $this->_values['created_id'])) {
      $this->add('xbutton', 'close_batch', ts('Close Batch'), ['type' => 'submit']);
      if (CRM_Batch_BAO_Batch::checkBatchPermission('export', $this->_values['created_id'])) {
        $this->add('xbutton', 'export_batch', ts('Close and Export Batch'), ['type' => 'submit']);
      }
    }

    CRM_Contribute_BAO_Query::buildSearchForm($this);
    $this->addElement('checkbox', 'toggleSelects', NULL, NULL);

    $this->add('select',
      'trans_remove',
      ts('Task'),
      ['' => ts('- actions -')] + ['Remove' => ts('Remove from Batch')]);

    $this->add('xbutton', 'rSubmit', ts('Go'),
      [
        'type' => 'submit',
        'class' => 'crm-form-submit',
        'id' => 'GoRemove',
      ]);

    self::$_entityID = CRM_Utils_Request::retrieve('bid', 'Positive');

    $this->addButtons(
      [
        [
          'type' => 'submit',
          'name' => ts('Search'),
          'isDefault' => TRUE,
        ],
      ]
    );

    $this->addElement('checkbox', 'toggleSelect', NULL, NULL);
    $this->add('select',
      'trans_assign',
      ts('Task'),
      ['' => ts('- actions -')] + ['Assign' => ts('Assign to Batch')]);

    $this->add('xbutton', 'submit', ts('Go'),
      [
        'type' => 'submit',
        'class' => 'crm-form-submit',
        'id' => 'Go',
      ]);
    $this->applyFilter('__ALL__', 'trim');

    $this->addElement('hidden', 'batch_id', self::$_entityID);

    $this->add('text', 'name', ts('Batch Name'));
  }

  /**
   * Set the default values for the form.
   */
  public function setDefaultValues() {
    // do not setdefault unless it is open/reopened batch
    if (!in_array($this->_batchStatus, ['Open', 'Reopened'])) {
      return;
    }
    if (isset(self::$_entityID)) {
      $paymentInstrumentID = CRM_Core_DAO::getFieldValue('CRM_Batch_BAO_Batch', self::$_entityID, 'payment_instrument_id');
      $defaults['contribution_payment_instrument_id'] = $paymentInstrumentID;
      $this->assign('paymentInstrumentID', $paymentInstrumentID);
    }
    return $defaults;
  }

  /**
   * Get action links.
   *
   * @return array
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        'view' => [
          'name' => ts('View'),
          'url' => 'civicrm/contact/view/contribution',
          'qs' => 'reset=1&id=%%contid%%&cid=%%cid%%&action=view&context=contribution&selectedChild=contribute',
          'title' => ts('View Contribution'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::VIEW),
        ],
        'assign' => [
          'name' => ts('Assign'),
          'ref' => 'disable-action',
          'title' => ts('Assign Transaction'),
          'extra' => 'onclick = "assignRemove( %%id%%,\'' . 'assign' . '\' );"',
          'weight' => 50,
        ],
      ];
    }
    return self::$_links;
  }

}
