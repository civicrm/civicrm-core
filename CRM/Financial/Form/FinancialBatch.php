<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This class generates form components for Accounting Batch
 */
class CRM_Financial_Form_FinancialBatch extends CRM_Contribute_Form {

  /**
   * The financial batch id, used when editing the field
   *
   * @var int
   */
  protected $_id;

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);
    $this->set("context", $context);
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    parent::preProcess();
    $session = CRM_Core_Session::singleton();
    if ($this->_id) {
      $permissions = [
        CRM_Core_Action::UPDATE => [
          'permission' => [
            'edit own manual batches',
            'edit all manual batches',
          ],
          'actionName' => 'edit',
        ],
        CRM_Core_Action::DELETE => [
          'permission' => [
            'delete own manual batches',
            'delete all manual batches',
          ],
          'actionName' => 'delete',
        ],
      ];

      $createdID = CRM_Core_DAO::getFieldValue('CRM_Batch_DAO_Batch', $this->_id, 'created_id');
      if (!empty($permissions[$this->_action])) {
        $this->checkPermissions($this->_action, $permissions[$this->_action]['permission'], $createdID, $session->get('userID'), $permissions[$this->_action]['actionName']);
      }
    }
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->setPageTitle(ts('Financial Batch'));
    if (!empty($this->_id)) {
      $this->_title = CRM_Core_DAO::getFieldValue('CRM_Batch_DAO_Batch', $this->_id, 'title');
      CRM_Utils_System::setTitle($this->_title . ' - ' . ts('Accounting Batch'));
      $this->assign('batchTitle', $this->_title);
      $contactID = CRM_Core_DAO::getFieldValue('CRM_Batch_DAO_Batch', $this->_id, 'created_id');
      $contactName = CRM_Contact_BAO_Contact::displayName($contactID);
      $this->assign('contactName', $contactName);
    }

    $this->applyFilter('__ALL__', 'trim');

    $this->addButtons(
      [
        [
          'type' => 'next',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'next',
          'name' => ts('Save and New'),
          'subName' => 'new',
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );

    if ($this->_action & CRM_Core_Action::UPDATE && $this->_id) {
      $batchStatus = CRM_Core_PseudoConstant::get('CRM_Batch_BAO_Batch', 'status_id');

      // unset exported status
      $exportedStatusId = CRM_Core_PseudoConstant::getKey('CRM_Batch_BAO_Batch', 'status_id', 'Exported');
      unset($batchStatus[$exportedStatusId]);
      $this->add('select', 'status_id', ts('Batch Status'), ['' => ts('- select -')] + $batchStatus, TRUE);
      $this->freeze(['status_id']);
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_Batch_DAO_Batch');

    $this->add('text', 'title', ts('Batch Name'), $attributes['name'], TRUE);

    $this->add('textarea', 'description', ts('Description'), $attributes['description']);

    $this->add('select', 'payment_instrument_id', ts('Payment Method'),
      ['' => ts('- select -')] + CRM_Contribute_PseudoConstant::paymentInstrument(),
      FALSE
    );

    $this->add('text', 'total', ts('Total Amount'), $attributes['total']);

    $this->add('text', 'item_count', ts('Number of Transactions'), $attributes['item_count']);
    $this->addFormRule(['CRM_Financial_Form_FinancialBatch', 'formRule'], $this);
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database.
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    if ($this->_id) {
      $this->assign('modified_date', $defaults['modified_date']);
      $this->assign('created_date', $defaults['created_date']);
    }
    else {
      // set batch name default
      $defaults['title'] = CRM_Batch_BAO_Batch::generateBatchName();
    }

    return $defaults;
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $values
   * @param $files
   * @param $self
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($values, $files, $self) {
    $errors = [];
    if (!empty($values['contact_name']) && !is_numeric($values['created_id'])) {
      $errors['contact_name'] = ts('Please select a valid contact.');
    }
    if ($values['item_count'] && (!is_numeric($values['item_count']) || $values['item_count'] < 1)) {
      $errors['item_count'] = ts('Number of Transactions should a positive number');
    }
    if ($values['total'] && (!is_numeric($values['total']) || $values['total'] <= 0)) {
      $errors['total'] = ts('Total Amount should be a positive number');
    }
    if (!empty($values['created_date']) && date('Y-m-d') < date('Y-m-d', strtotime($values['created_date']))) {
      $errors['created_date'] = ts('Created date cannot be greater than current date');
    }
    $batchName = $values['title'];
    if (!CRM_Core_DAO::objectExists($batchName, 'CRM_Batch_DAO_Batch', $self->_id)) {
      $errors['title'] = ts('This name already exists in database. Batch names must be unique.');
    }
    return CRM_Utils_Array::crmIsEmptyArray($errors) ? TRUE : $errors;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $session = CRM_Core_Session::singleton();
    $params = $this->exportValues();
    $closedStatusId = CRM_Core_PseudoConstant::getKey('CRM_Batch_BAO_Batch', 'status_id', 'Closed');
    if ($this->_id) {
      $params['id'] = $this->_id;
    }

    // store the submitted values in an array
    $params['modified_date'] = date('YmdHis');
    $params['modified_id'] = $session->get('userID');
    if (!empty($params['created_date'])) {
      $params['created_date'] = CRM_Utils_Date::processDate($params['created_date']);
    }

    if ($this->_action & CRM_Core_Action::ADD) {
      $params['mode_id'] = CRM_Core_PseudoConstant::getKey('CRM_Batch_BAO_Batch', 'mode_id', 'Manual Batch');
      $params['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Batch_BAO_Batch', 'status_id', 'Open');
      $params['created_date'] = date('YmdHis');
      if (empty($params['created_id'])) {
        $params['created_id'] = $session->get('userID');
      }
      $details = "{$params['title']} batch has been created by this contact.";
      $activityTypeName = 'Create Batch';
    }
    elseif ($this->_action & CRM_Core_Action::UPDATE && $this->_id) {
      $details = "{$params['title']} batch has been edited by this contact.";
      if ($params['status_id'] === $closedStatusId) {
        $details = "{$params['title']} batch has been closed by this contact.";
      }
      $activityTypeName = 'Edit Batch';
    }

    // FIXME: What happens if we get to here and no activityType is defined?

    $batch = CRM_Batch_BAO_Batch::create($params);

    //set batch id
    $this->_id = $batch->id;

    // create activity.
    $activityParams = [
      'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_DAO_Activity', 'activity_type_id', $activityTypeName),
      'subject' => $batch->title . "- Batch",
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_DAO_Activity', 'activity_status_id', 'Completed'),
      'priority_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_DAO_Activity', 'priority_id', 'Normal'),
      'activity_date_time' => date('YmdHis'),
      'source_contact_id' => $session->get('userID'),
      'source_contact_qid' => $session->get('userID'),
      'details' => $details,
    ];

    CRM_Activity_BAO_Activity::create($activityParams);

    $buttonName = $this->controller->getButtonName();

    $context = $this->get("context");
    if ($batch->title) {
      CRM_Core_Session::setStatus(ts("'%1' batch has been saved.", [1 => $batch->title]), ts('Saved'), 'success');
    }
    if ($buttonName == $this->getButtonName('next', 'new') & $this->_action == CRM_Core_Action::UPDATE) {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/financial/batch',
        "reset=1&action=add&context=1"));
    }
    elseif ($buttonName == $this->getButtonName('next', 'new')) {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/financial/batch',
        "reset=1&action=add"));
    }
    elseif ($batch->status_id === $closedStatusId) {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm', 'reset=1'));
    }
    elseif (($buttonName == $this->getButtonName('next') & $this->_action == CRM_Core_Action::UPDATE) ||
      ($buttonName == $this->getButtonName('next') & $this->_action == CRM_Core_Action::ADD & $context == 1)
    ) {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/financial/financialbatches',
        "reset=1&batchStatus=1"));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/batchtransaction',
        "reset=1&bid={$batch->id}"));
    }
  }

  /**
   * Global validation rules for the form.
   *
   * @param $action
   * @param $permissions
   * @param int $createdID
   * @param int $userContactID
   * @param string $actionName
   *
   *   list of errors to be posted back to the form
   */
  public function checkPermissions($action, $permissions, $createdID, $userContactID, $actionName) {
    if ((CRM_Core_Permission::check($permissions[0]) || CRM_Core_Permission::check($permissions[1]))) {
      if (CRM_Core_Permission::check($permissions[0]) && $userContactID != $createdID && !CRM_Core_Permission::check($permissions[1])) {
        CRM_Core_Error::statusBounce(ts('You dont have permission to %1 this batch'), [1 => $actionName]);
      }
    }
    else {
      CRM_Core_Error::statusBounce(ts('You dont have permission to %1 this batch'), [1 => $actionName]);
    }
  }

}
