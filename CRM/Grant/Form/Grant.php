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
 * $Id$
 *
 */

/**
 * This class generates form components for processing a case
 *
 */
class CRM_Grant_Form_Grant extends CRM_Core_Form {

  /**
   * The id of the case that we are proceessing.
   *
   * @var int
   */
  protected $_id;

  /**
   * The id of the contact associated with this contribution.
   *
   * @var int
   */
  protected $_contactID;

  protected $_context;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Grant';
  }

  /**
   * Set variables up before form is built.
   *
   * @return void
   */
  public function preProcess() {

    $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->_grantType = NULL;
    if ($this->_id) {
      $this->_grantType = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_Grant', $this->_id, 'grant_type_id');
    }
    $this->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);

    $this->assign('action', $this->_action);
    $this->assign('context', $this->_context);

    //check permission for action.
    if (!CRM_Core_Permission::checkActionPermission('CiviGrant', $this->_action)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
    }

    $this->setPageTitle(ts('Grant'));

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->_noteId = NULL;
    if ($this->_id) {
      $noteDAO = new CRM_Core_BAO_Note();
      $noteDAO->entity_table = 'civicrm_grant';
      $noteDAO->entity_id = $this->_id;
      if ($noteDAO->find(TRUE)) {
        $this->_noteId = $noteDAO->id;
      }
    }

    // when custom data is included in this page
    if (!empty($_POST['hidden_custom'])) {
      $grantTypeId = empty($_POST['grant_type_id']) ? NULL : $_POST['grant_type_id'];
      $this->set('type', 'Grant');
      $this->set('subType', $grantTypeId);
      $this->set('entityId', $this->_id);
      CRM_Custom_Form_CustomData::preProcess($this, NULL, $grantTypeId, 1, 'Grant', $this->_id);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
    }
  }

  /**
   * @return array
   */
  public function setDefaultValues() {

    $defaults = parent::setDefaultValues();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return $defaults;
    }

    $params['id'] = $this->_id;
    if ($this->_noteId) {
      $defaults['note'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Note', $this->_noteId, 'note');
    }
    if ($this->_id) {
      CRM_Grant_BAO_Grant::retrieve($params, $defaults);

      // fix the display of the monetary value, CRM-4038
      if (isset($defaults['amount_total'])) {
        $defaults['amount_total'] = CRM_Utils_Money::format($defaults['amount_total'], NULL, '%a');
      }
      if (isset($defaults['amount_requested'])) {
        $defaults['amount_requested'] = CRM_Utils_Money::format($defaults['amount_requested'], NULL, '%a');
      }
      if (isset($defaults['amount_granted'])) {
        $defaults['amount_granted'] = CRM_Utils_Money::format($defaults['amount_granted'], NULL, '%a');
      }
    }

    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {

    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons([
          [
            'type' => 'next',
            'name' => ts('Delete'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'isDefault' => TRUE,
          ],
          [
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ],
        ]
      );
      return;
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_Grant_DAO_Grant');
    $this->addSelect('grant_type_id', ['onChange' => "CRM.buildCustomData( 'Grant', this.value );"], TRUE);

    //need to assign custom data type and subtype to the template
    $this->assign('customDataType', 'Grant');
    $this->assign('customDataSubType', $this->_grantType);
    $this->assign('entityID', $this->_id);

    $this->addSelect('status_id', [], TRUE);

    $this->add('datepicker', 'application_received_date', ts('Application Received'), [], FALSE, ['time' => FALSE]);
    $this->add('datepicker', 'decision_date', ts('Grant Decision'), [], FALSE, ['time' => FALSE]);
    $this->add('datepicker', 'money_transfer_date', ts('Money Transferred'), [], FALSE, ['time' => FALSE]);
    $this->add('datepicker', 'grant_due_date', ts('Grant Report Due'), [], FALSE, ['time' => FALSE]);

    $this->addElement('checkbox', 'grant_report_received', ts('Grant Report Received?'), NULL);
    $this->add('textarea', 'rationale', ts('Rationale'), $attributes['rationale']);
    $this->add('text', 'amount_total', ts('Amount Requested'), NULL, TRUE);
    $this->addRule('amount_total', ts('Please enter a valid amount.'), 'money');

    $this->add('text', 'amount_granted', ts('Amount Granted'));
    $this->addRule('amount_granted', ts('Please enter a valid amount.'), 'money');

    $this->add('text', 'amount_requested', ts('Amount Requested<br />(original currency)'));
    $this->addRule('amount_requested', ts('Please enter a valid amount.'), 'money');

    $noteAttrib = CRM_Core_DAO::getAttribute('CRM_Core_DAO_Note');
    $this->add('textarea', 'note', ts('Notes'), $noteAttrib['note']);

    // add attachments part
    CRM_Core_BAO_File::buildAttachment($this,
      'civicrm_grant',
      $this->_id
    );

    // make this form an upload since we dont know if the custom data injected dynamically
    // is of type file etc $uploadNames = $this->get( 'uploadNames' );
    $this->addButtons([
        [
          'type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'upload',
          'name' => ts('Save and New'),
          'js' => ['onclick' => "return verify( );"],
          'subName' => 'new',
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );

    if ($this->_context == 'standalone') {
      $this->addEntityRef('contact_id', ts('Applicant'), ['create' => TRUE], TRUE);
    }
  }

  /**
   * Process the form submission.
   *
   *
   * @return void
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Grant_BAO_Grant::del($this->_id);
      return;
    }

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $ids['grant_id'] = $this->_id;
    }

    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    if (empty($params['grant_report_received'])) {
      $params['grant_report_received'] = "null";
    }

    // set the contact, when contact is selected
    if ($this->_context == 'standalone') {
      $this->_contactID = $params['contact_id'];
    }

    $params['contact_id'] = $this->_contactID;
    $ids['note'] = [];
    if ($this->_noteId) {
      $ids['note']['id'] = $this->_noteId;
    }

    // build custom data getFields array
    $customFieldsGrantType = CRM_Core_BAO_CustomField::getFields('Grant', FALSE, FALSE,
      CRM_Utils_Array::value('grant_type_id', $params)
    );
    $customFields = CRM_Utils_Array::crmArrayMerge($customFieldsGrantType,
      CRM_Core_BAO_CustomField::getFields('Grant', FALSE, FALSE, NULL, NULL, TRUE)
    );
    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
      $this->_id,
      'Grant'
    );

    // add attachments as needed
    CRM_Core_BAO_File::formatAttachment($params,
      $params,
      'civicrm_grant',
      $this->_id
    );

    $grant = CRM_Grant_BAO_Grant::create($params, $ids);

    $buttonName = $this->controller->getButtonName();
    $session = CRM_Core_Session::singleton();
    if ($this->_context == 'standalone') {
      if ($buttonName == $this->getButtonName('upload', 'new')) {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/grant/add',
          'reset=1&action=add&context=standalone'
        ));
      }
      else {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view',
          "reset=1&cid={$this->_contactID}&selectedChild=grant"
        ));
      }
    }
    elseif ($buttonName == $this->getButtonName('upload', 'new')) {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view/grant',
        "reset=1&action=add&context=grant&cid={$this->_contactID}"
      ));
    }
  }

}
