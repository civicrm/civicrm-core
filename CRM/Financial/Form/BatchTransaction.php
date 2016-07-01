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
 * This class generates form components for Financial Type
 */
class CRM_Financial_Form_BatchTransaction extends CRM_Contribute_Form {
  static $_links = NULL;
  static $_entityID;

  /**
   * Batch status.
   * @var
   */
  protected $_batchStatusId;

  /**
   * Batch status name.
   * @string
   */
  protected $_batchStatus;

  public function preProcess() {
    // This reuses some styles from search forms
    CRM_Core_Resources::singleton()->addStyleFile('civicrm', 'css/searchForm.css', 1, 'html-header');

    self::$_entityID = CRM_Utils_Request::retrieve('bid', 'Positive') ? CRM_Utils_Request::retrieve('bid', 'Positive') : $_POST['batch_id'];
    $this->assign('entityID', self::$_entityID);
    if (isset(self::$_entityID)) {
      $this->_batchStatusId = CRM_Core_DAO::getFieldValue('CRM_Batch_BAO_Batch', self::$_entityID, 'status_id');
      $batchStatuses = CRM_Core_PseudoConstant::get('CRM_Batch_DAO_Batch', 'status_id', array('labelColumn' => 'name', 'condition' => " v.value={$this->_batchStatusId}"));
      $this->_batchStatus = $batchStatuses[$this->_batchStatusId];
      $this->assign('statusID', $this->_batchStatusId);
      $this->assign('batchStatus', $this->_batchStatus);
      $validStatus = FALSE;
      if (in_array($this->_batchStatus, array('Open', 'Reopened'))) {
        $validStatus = TRUE;
      }
      $this->assign('validStatus', $validStatus);

      $batchTitle = CRM_Core_DAO::getFieldValue('CRM_Batch_BAO_Batch', self::$_entityID, 'title');
      CRM_Utils_System::setTitle(ts('Accounting Batch - %1', array(1 => $batchTitle)));

      $columnHeaders = array(
        'created_by' => ts('Created By'),
        'status' => ts('Status'),
        'description' => ts('Description'),
        'payment_instrument' => ts('Payment Method'),
        'item_count' => ts('Entered Transactions'),
        'assigned_item_count' => ts('Assigned Transactions'),
        'total' => ts('Entered Total'),
        'assigned_total' => ts('Assigned Total'),
        'opened_date' => ts('Opened'),
      );
      $this->assign('columnHeaders', $columnHeaders);
    }
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    if ($this->_batchStatus == 'Closed') {
      $this->add('submit', 'export_batch', ts('Export Batch'));
    }

    // do not build rest of form unless it is open/reopened batch
    if (!in_array($this->_batchStatus, array('Open', 'Reopened'))) {
      return;
    }

    parent::buildQuickForm();
    $this->add('submit', 'close_batch', ts('Close Batch'));
    $this->add('submit', 'export_batch', ts('Close & Export Batch'));

    // text for sort_name
    $this->addElement('text',
      'sort_name',
      ts('Contributor Name or Email'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact',
        'sort_name'
      )
    );

    $this->_group = CRM_Core_PseudoConstant::nestedGroup();

    // multiselect for groups
    if ($this->_group) {
      $this->add('select', 'group', ts('Groups'), $this->_group, FALSE,
        array('id' => 'group', 'multiple' => 'multiple', 'class' => 'crm-select2')
      );
    }
    $contactTags = CRM_Core_BAO_Tag::getTags();

    if ($contactTags) {
      $this->add('select', 'contact_tags', ts('Tags'), $contactTags, FALSE,
        array('id' => 'contact_tags', 'multiple' => 'multiple', 'class' => 'crm-select2')
      );
    }
    CRM_Contribute_BAO_Query::buildSearchForm($this);
    $this->addElement('checkbox', 'toggleSelects', NULL, NULL);

    $this->add('select',
      'trans_remove',
      ts('Task'),
      array('' => ts('- actions -')) + array('Remove' => ts('Remove from Batch')));

    $this->add('submit', 'rSubmit', ts('Go'),
      array(
        'class' => 'crm-form-submit',
        'id' => 'GoRemove',
      ));

    self::$_entityID = CRM_Utils_Request::retrieve('bid', 'Positive');

    $this->addButtons(
      array(
        array(
          'type' => 'submit',
          'name' => ts('Search'),
          'isDefault' => TRUE,
        ),
      )
    );

    $this->addElement('checkbox', 'toggleSelect', NULL, NULL);
    $this->add('select',
      'trans_assign',
      ts('Task'),
      array('' => ts('- actions -')) + array('Assign' => ts('Assign to Batch')));

    $this->add('submit', 'submit', ts('Go'),
      array(
        'class' => 'crm-form-submit',
        'id' => 'Go',
      ));
    $this->applyFilter('__ALL__', 'trim');

    $this->addElement('hidden', 'batch_id', self::$_entityID);

    $this->add('text', 'name', ts('Batch Name'));
  }

  public function setDefaultValues() {
    // do not setdefault unless it is open/reopened batch
    if (!in_array($this->_batchStatus, array('Open', 'Reopened'))) {
      return;
    }
    if (isset(self::$_entityID)) {
      $paymentInstrumentID = CRM_Core_DAO::getFieldValue('CRM_Batch_BAO_Batch', self::$_entityID, 'payment_instrument_id');
      $defaults['payment_instrument_id'] = $paymentInstrumentID;
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
      self::$_links = array(
        'view' => array(
          'name' => ts('View'),
          'url' => 'civicrm/contact/view/contribution',
          'qs' => 'reset=1&id=%%contid%%&cid=%%cid%%&action=view&context=contribution&selectedChild=contribute',
          'title' => ts('View Contribution'),
        ),
        'assign' => array(
          'name' => ts('Assign'),
          'ref' => 'disable-action',
          'title' => ts('Assign Transaction'),
          'extra' => 'onclick = "assignRemove( %%id%%,\'' . 'assign' . '\' );"',
        ),
      );
    }
    return self::$_links;
  }

}
