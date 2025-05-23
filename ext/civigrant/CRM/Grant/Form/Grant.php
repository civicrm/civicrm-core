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
 * This class generates form components for processing a case
 *
 */
class CRM_Grant_Form_Grant extends CRM_Core_Form {
  use CRM_Custom_Form_CustomDataTrait;

  /**
   * The id of the grant when ACTION is update or delete.
   *
   * @var int|null
   */
  protected ?int $_id;

  /**
   * The id of the contact associated with this contribution.
   *
   * @var int
   */
  protected $_contactID;

  protected $_context;

  public $_noteId;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Grant';
  }

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {

    if ($this->getGrantID()) {
      $this->_contactID = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_Grant', $this->_id, 'contact_id');
    }
    else {
      $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    }
    // Almost-useless _context variable just tells if we have a contact id
    $this->_context = $this->_contactID ? 'contact' : 'standalone';

    $this->assign('action', $this->_action);

    // check permission for action.
    $perm = $this->_action & CRM_Core_Action::DELETE ? 'delete in CiviGrant' : 'edit grants';
    if (!CRM_Core_Permission::check($perm)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }

    $this->setPageTitle();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    if ($this->_id) {
      $noteDAO = new CRM_Core_BAO_Note();
      $noteDAO->entity_table = 'civicrm_grant';
      $noteDAO->entity_id = $this->_id;
      if ($noteDAO->find(TRUE)) {
        $this->_noteId = $noteDAO->id;
      }
    }

    if ($this->isSubmitted()) {
      // The custom data fields are added to the form by an ajax form.
      // However, if they are not present in the element index they will
      // not be available from `$this->getSubmittedValue()` in post process.
      // We do not have to set defaults or otherwise render - just add to the element index.
      $this->addCustomDataFieldsToForm('Grant', array_filter([
        'id' => $this->getGrantID(),
        'grant_type_id' => $this->getSubmittedValue('grant_type_id'),
      ]));
    }
  }

  /**
   * @api supported for external use.
   *
   * @return int|null
   * @throws \CRM_Core_Exception
   */
  public function getGrantID(): ?int {
    if (!isset($this->_id)) {
      $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    }
    return $this->_id;
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
        $defaults['amount_total'] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($defaults['amount_total']);
      }
      if (isset($defaults['amount_requested'])) {
        $defaults['amount_requested'] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($defaults['amount_requested']);
      }
      if (isset($defaults['amount_granted'])) {
        $defaults['amount_granted'] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($defaults['amount_granted']);
      }
    }
    else {
      if ($this->_contactID) {
        $defaults['contact_id'] = $this->_contactID;
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
      ]);
      return;
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_Grant_DAO_Grant');
    $this->addSelect('grant_type_id', ['placeholder' => ts('- select type -'), 'onChange' => "CRM.buildCustomData( 'Grant', this.value );"], TRUE);

    //need to assign custom data subtype to the template
    $grantType = $this->getGrantID() ? CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_Grant', $this->_id, 'grant_type_id') : NULL;
    $this->assign('customDataSubType', $grantType);
    $this->assign('entityID', $this->_id);

    $this->addSelect('status_id', ['placeholder' => ts('- select status -')], TRUE);

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
    if ($this->_action !== CRM_Core_Action::VIEW) {
      $this->addButtons([
        [
          'type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'upload',
          'name' => ts('Save and New'),
          'subName' => 'new',
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
    }

    $contactField = $this->addEntityRef('contact_id', ts('Applicant'), ['create' => TRUE], TRUE);
    if ($this->_context != 'standalone') {
      $contactField->freeze();
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
      CRM_Grant_BAO_Grant::deleteRecord(['id' => $this->_id]);
      return;
    }

    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);
    $params['id'] = $this->_id;

    if (empty($params['grant_report_received'])) {
      $params['grant_report_received'] = 0;
    }

    // set the contact, when contact is selected
    if ($this->_context == 'standalone') {
      $this->_contactID = $params['contact_id'];
    }

    $params['contact_id'] = $this->_contactID;

    // build custom data array
    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($this->getSubmittedValues(),
      $this->_id,
      'Grant'
    );

    // add attachments as needed
    CRM_Core_BAO_File::formatAttachment($params,
      $params,
      'civicrm_grant',
      $this->_id
    );
    $moneyFields = [
      'amount_total',
      'amount_granted',
      'amount_requested',
    ];
    foreach ($moneyFields as $field) {
      if (isset($params[$field])) {
        $params[$field] = CRM_Utils_Rule::cleanMoney($params[$field]);
      }
    }

    $grant = CRM_Grant_BAO_Grant::writeRecord($params);

    if (!empty($params['note']) || $this->_noteId) {
      $noteParams = [
        'id' => $this->_noteId,
        'entity_table' => 'civicrm_grant',
        'note' => $params['note'],
        'entity_id' => $grant->id,
        'contact_id' => $grant->contact_id,
      ];

      CRM_Core_BAO_Note::add($noteParams);
    }

    // check and attach and files as needed
    CRM_Core_BAO_File::processAttachment($params,
      'civicrm_grant',
      $grant->id
    );

    $buttonName = $this->controller->getButtonName();
    $session = CRM_Core_Session::singleton();
    if ($this->_context == 'standalone') {
      if ($buttonName == $this->getButtonName('upload', 'new')) {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/grant/add',
          'reset=1&action=add'
        ));
      }
      else {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view',
          "reset=1&cid={$this->_contactID}&selectedChild=grant"
        ));
      }
    }
    elseif ($buttonName == $this->getButtonName('upload', 'new')) {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/grant/add',
        "reset=1&action=add&cid={$this->_contactID}"
      ));
    }
  }

}
