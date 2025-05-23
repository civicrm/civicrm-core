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

use Civi\Api4\PledgePayment;

/**
 * This class generates form components for processing a pledge
 */
class CRM_Pledge_Form_Pledge extends CRM_Core_Form {
  use CRM_Contact_Form_ContactFormTrait;
  use CRM_Custom_Form_CustomDataTrait;
  use CRM_Pledge_Form_PledgeFormTrait;

  public $_action;

  /**
   * The id of the pledge that we are processing.
   *
   * @var int
   *
   * @internal
   */
  public $_id;

  /**
   * The id of the contact associated with this pledge.
   *
   * @var int
   */
  public $_contactID;

  /**
   * The Pledge values if an existing pledge.
   * @var array
   */
  public $_values = [];

  /**
   * The Pledge frequency Units.
   * @var array
   */
  public $_freqUnits;

  /**
   * Is current pledge pending.
   * @var bool
   */
  public $_isPending = FALSE;

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess(): void {
    $this->_contactID = $this->getContactID();
    $this->_action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 'add'
    );
    $this->setContext();
    // check for action permissions.
    if (!CRM_Core_Permission::checkActionPermission('CiviPledge', $this->_action)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }

    $this->assign('action', $this->_action);
    $this->assign('context', $this->getContext());
    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->setPageTitle(ts('Pledge'));

    $this->_values = [];
    // current pledge id
    if ($this->getPledgeID()) {
      $params = ['id' => $this->getPledgeID()];
      CRM_Pledge_BAO_Pledge::getValues($params, $this->_values);
      $this->_isPending = !CRM_Pledge_BAO_Pledge::pledgeHasFinancialTransactions($this->getPledgeID(), $this->getPledgeValue('status_id'));
    }

    // get the pledge frequency units.
    $this->_freqUnits = CRM_Core_OptionGroup::values('recur_frequency_units');
  }

  /**
   * Set default values for the form.
   * The default values are retrieved from the database.
   *
   * @throws \CRM_Core_Exception
   */
  public function setDefaultValues(): array {
    $defaults = $this->_values;

    if ($this->_action & CRM_Core_Action::DELETE) {
      return $defaults;
    }

    if (!empty($defaults['is_test'])) {
      $this->assign('is_test', TRUE);
    }

    if ($this->getPledgeID()) {
      // check is this pledge pending.
      // fix the display of the monetary value, CRM-4038.
      if ($this->_isPending) {
        $defaults['eachPaymentAmount'] = $this->_values['amount'] / $this->_values['installments'];
        $defaults['eachPaymentAmount'] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($defaults['eachPaymentAmount']);
      }

      // fix the display of the monetary value, CRM-4038
      if (isset($this->_values['amount'])) {
        $defaults['amount'] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($this->_values['amount']);
      }
      $this->assign('amount', $this->_values['amount']);
      $this->assign('installments', $defaults['installments']);
    }
    else {
      if ($this->_contactID) {
        $defaults['contact_id'] = $this->_contactID;
      }
      // default values.
      $defaults['create_date'] = date('Y-m-d');
      $defaults['start_date'] = date('Y-m-d');
      $defaults['installments'] = 12;
      $defaults['frequency_interval'] = 1;
      $defaults['frequency_day'] = 1;
      $defaults['initial_reminder_day'] = 5;
      $defaults['max_reminders'] = 1;
      $defaults['additional_reminder_day'] = 5;
      $defaults['frequency_unit'] = array_search('month', $this->_freqUnits);
      $defaults['financial_type_id'] = array_search('Donation', CRM_Contribute_PseudoConstant::financialType());
    }

    $pledgeStatus = CRM_Pledge_BAO_Pledge::buildOptions('status_id');
    $pledgeStatusNames = CRM_Core_OptionGroup::values('pledge_status',
      FALSE, FALSE, FALSE, NULL, 'name', TRUE
    );
    // get default status label (pending)
    $defaultPledgeStatus = CRM_Utils_Array::value(array_search('Pending', $pledgeStatusNames),
      $pledgeStatus
    );

    // assign status.
    $this->assign('status', $pledgeStatus[$this->_values['status_id'] ?? 0] ?? $defaultPledgeStatus);

    $this->assign('email', $this->getContactValue('email_primary.email'));
    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
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

    $contactField = $this->addEntityRef('contact_id', ts('Pledge by'), ['create' => TRUE, 'api' => ['extra' => ['email']]], TRUE);
    if ($this->getContext() !== 'standalone') {
      $contactField->freeze();
    }
    $this->assign('pledgeID', $this->getPledgeID());
    $formType = CRM_Utils_Request::retrieveValue('formType', 'String');

    $allPanes[ts('Payment Reminders')] = [
      'open' => 'false',
      'id' => 'PaymentReminders',
    ];
    // see if we need to include this paneName in the current form
    if ($formType === 'PaymentReminders' || !empty($_POST['hidden_PaymentReminders'])
    ) {
      $allPanes[ts('Payment Reminders')]['open'] = 'true';
    }

    $this->add('hidden', 'hidden_PaymentReminders', 1);
    $this->add('text', 'initial_reminder_day', ts('Send Initial Reminder'), ['size' => 3]);
    $this->addRule('initial_reminder_day', ts('Please enter a valid reminder day.'), 'positiveInteger');
    $this->add('text', 'max_reminders', ts('Send up to'), ['size' => 3]);
    $this->addRule('max_reminders', ts('Please enter a valid No. of reminders.'), 'positiveInteger');
    $this->add('text', 'additional_reminder_day', ts('Send additional reminders'), ['size' => 3]);
    $this->addRule('additional_reminder_day', ts('Please enter a valid additional reminder day.'), 'positiveInteger');

    $this->assign('allPanes', $allPanes);
    $this->assign('showAdditionalInfo', TRUE);

    $this->assign('formType', $formType);
    if ($formType) {
      return;
    }

    $this->applyFilter('__ALL__', 'trim');

    // pledge fields.
    $attributes = CRM_Core_DAO::getAttribute('CRM_Pledge_DAO_Pledge');

    $this->assign('isPending', $this->_isPending);

    $js = [
      'onblur' => 'calculatedPaymentAmount( );',
      'onkeyup' => 'calculatedPaymentAmount( );',
    ];

    $amount = $this->addMoney('amount', ts('Total Pledge Amount'), TRUE,
      array_merge($attributes['pledge_amount'], $js), TRUE,
      'currency', NULL, $this->_id && !$this->_isPending
    );

    $installments = &$this->add('text', 'installments', ts('To be paid in'),
      array_merge($attributes['installments'], $js), TRUE
    );
    $this->addRule('installments', ts('Please enter a valid number of installments.'), 'positiveInteger');

    $frequencyInterval = $this->add('number', 'frequency_interval', ts('every'),
      $attributes['pledge_frequency_interval'], TRUE
    );
    $this->addRule('frequency_interval', ts('Please enter a number for frequency (e.g. every "3" months).'), 'positiveInteger');

    // Fix frequency unit display for use with frequency_interval
    $freqUnitsDisplay = [];
    foreach ($this->_freqUnits as $val => $label) {
      $freqUnitsDisplay[$val] = ts('%1(s)', [1 => $label]);
    }
    $frequencyUnit = $this->add('select', 'frequency_unit',
      ts('Frequency'),
      ['' => ts('- select -')] + $freqUnitsDisplay,
      TRUE,
      ['class' => 'crm-select2 eight']
    );

    $frequencyDay = $this->add('number', 'frequency_day', ts('Payments are due on the'), $attributes['frequency_day'], TRUE);
    $this->addRule('frequency_day', ts('Please enter a valid payment due day.'), 'positiveInteger');

    $this->add('text', 'eachPaymentAmount', ts('each'), [
      'size' => 10,
      'style' => 'background-color:#EBECE4',
      // WTF, preserved because its inexplicable
      0 => 'READONLY',
    ]);

    // add various dates
    $createDate = $this->add('datepicker', 'create_date', ts('Pledge Made'), [], TRUE, ['time' => FALSE]);
    $startDate = $this->add('datepicker', 'start_date', ts('Payments Start'), [], TRUE, ['time' => FALSE]);

    if (!empty($this->_values['currency'])) {
      $this->assign('currency', $this->_values['currency']);
    }
    elseif (!empty($this->_submitValues['currency'])) {
      $this->assign('currency', $this->_submitValues['currency']);
    }

    if ($this->_id && !$this->_isPending) {
      $amount->freeze();
      $installments->freeze();
      $createDate->freeze();
      $startDate->freeze();
      $frequencyInterval->freeze();
      $frequencyUnit->freeze();
      $frequencyDay->freeze();
      $eachPaymentAmount = $this->_values['original_installment_amount'];
    }
    $this->assign('eachPaymentAmount', $eachPaymentAmount ?? NULL);

    if (($this->_values['status_id'] ?? NULL) !=
      CRM_Core_PseudoConstant::getKey('CRM_Pledge_BAO_Pledge', 'status_id', 'Cancelled')
    ) {

      $this->addElement('checkbox', 'is_acknowledge', ts('Send Acknowledgment?'), NULL,
        ['onclick' => "showHideByValue( 'is_acknowledge', '', 'acknowledgeDate', 'table-row', 'radio', true); showHideByValue( 'is_acknowledge', '', 'fromEmail', 'table-row', 'radio', false );"]
      );
      $this->add('select', 'from_email_address', ts('Receipt From'), CRM_Core_BAO_Email::getFromEmail(), FALSE, ['class' => 'crm-select2 huge']);
    }

    $this->add('datepicker', 'acknowledge_date', ts('Acknowledgment Date'), [], FALSE, ['time' => FALSE]);

    $this->add('select', 'financial_type_id',
      ts('Financial Type'),
      ['' => ts('- select -')] + CRM_Contribute_PseudoConstant::financialType(),
      TRUE,
      ['class' => 'crm-select2']
    );

    // CRM-7362 --add campaigns.
    CRM_Campaign_BAO_Campaign::addCampaign($this, $this->_values['campaign_id'] ?? NULL);

    $pageIds = [];
    CRM_Core_DAO::commonRetrieveAll('CRM_Pledge_DAO_PledgeBlock', 'entity_table',
      'civicrm_contribution_page', $pageIds, ['entity_id']
    );
    $pages = CRM_Contribute_PseudoConstant::contributionPage();
    $pledgePages = [];
    foreach ($pageIds as $key => $value) {
      $pledgePages[$value['entity_id']] = $pages[$value['entity_id']];
    }
    $this->add('select', 'contribution_page_id', ts('Self-service Payments Page'),
      ['' => ts('- select -')] + $pledgePages,
      FALSE,
      ['class' => 'crm-select2']
    );

    $mailingInfo = Civi::settings()->get('mailing_backend');
    $this->assign('outBound_option', $mailingInfo['outBound_option']);

    // build custom data
    if ($this->isSubmitted()) {
      // The custom data fields are added to the form by an ajax form.
      // However, if they are not present in the element index they will
      // not be available from `$this->getSubmittedValue()` in post process.
      // We do not have to set defaults or otherwise render - just add to the element index.
      $this->addCustomDataFieldsToForm('Pledge', array_filter([
        'id' => $this->getPledgeID(),
      ]));
    }

    // make this form an upload since we dont know if the custom data injected dynamically
    // is of type file etc $uploadNames = $this->get( 'uploadNames' );
    $buttons = [
      [
        'type' => 'upload',
        'name' => ts('Save'),
        'js' => ['onclick' => 'return verify();'],
        'isDefault' => TRUE,
      ],
    ];
    if (!$this->_id) {
      $buttons[] = [
        'type' => 'upload',
        'name' => ts('Save and New'),
        'js' => ['onclick' => 'return verify();'],
        'subName' => 'new',
      ];
    }
    $buttons[] = [
      'type' => 'cancel',
      'name' => ts('Cancel'),
    ];

    $this->addButtons($buttons);
    $this->addFormRule(['CRM_Pledge_Form_Pledge', 'formRule'], $this);

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
    }
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param self $self
   *
   *
   * @return array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];

    if ($fields['amount'] <= 0) {
      $errors['amount'] = ts('Total Pledge Amount should be greater than zero.');
    }
    if ($fields['installments'] <= 0) {
      $errors['installments'] = ts('Installments should be greater than zero.');
    }

    if ($fields['frequency_unit'] !== 'week') {
      if ($fields['frequency_day'] > 31 || $fields['frequency_day'] == 0) {
        $errors['frequency_day'] = ts('Please enter a valid frequency day ie. 1 through 31.');
      }
    }
    elseif ($fields['frequency_unit'] === 'week') {
      if ($fields['frequency_day'] > 7 || $fields['frequency_day'] == 0) {
        $errors['frequency_day'] = ts('Please enter a valid frequency day ie. 1 through 7.');
      }
    }
    return $errors;
  }

  /**
   * Process the form submission.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess(): void {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Pledge_BAO_Pledge::deletePledge($this->getPledgeID());
      return;
    }

    // get the submitted form values.
    $formValues = $this->controller->exportValues($this->_name);

    // set the contact, when contact is selected
    if (!empty($formValues['contact_id'])) {
      $this->_contactID = $formValues['contact_id'];
    }

    $session = CRM_Core_Session::singleton();

    $fields = [
      'frequency_unit',
      'frequency_interval',
      'frequency_day',
      'installments',
      'financial_type_id',
      'initial_reminder_day',
      'max_reminders',
      'additional_reminder_day',
      'contribution_page_id',
      'campaign_id',
    ];
    foreach ($fields as $f) {
      $params[$f] = $formValues[$f] ?? NULL;
    }

    // format amount
    $params['amount'] = $this->getSubmittedValue('amount');
    $params['currency'] = $formValues['currency'] ?? NULL;
    $params['original_installment_amount'] = ($params['amount'] / $params['installments']);

    $dates = ['create_date', 'start_date', 'acknowledge_date', 'cancel_date'];
    foreach ($dates as $d) {
      if ($this->getPledgeID() && (!$this->_isPending) && !empty($this->_values[$d])) {
        if ($d === 'start_date') {
          $params['scheduled_date'] = CRM_Utils_Date::processDate($this->_values[$d]);
        }
        $params[$d] = CRM_Utils_Date::processDate($this->_values[$d]);
      }
      elseif (!empty($formValues[$d]) && !CRM_Utils_System::isNull($formValues[$d])) {
        if ($d === 'start_date') {
          $params['scheduled_date'] = CRM_Utils_Date::processDate($formValues[$d]);
        }
        $params[$d] = CRM_Utils_Date::processDate($formValues[$d]);
      }
      else {
        $params[$d] = 'null';
      }
    }

    if (!empty($formValues['is_acknowledge'])) {
      $params['acknowledge_date'] = date('Y-m-d');
    }

    // assign id only in update mode
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->getPledgeID();
    }

    $params['contact_id'] = $this->_contactID;

    // format custom data
    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($this->getSubmittedValues(),
      $this->getPledgeID(),
      'Pledge'
    );

    // handle pending pledge.
    $params['is_pledge_pending'] = $this->_isPending;

    // create pledge record.
    $pledge = CRM_Pledge_BAO_Pledge::create($params);
    $pledgeID = $pledge->id;

    $statusMsg = NULL;

    if ($pledgeID) {
      // set the status msg.
      if ($this->_action & CRM_Core_Action::ADD) {
        $statusMsg = ts('Pledge has been recorded and the payment schedule has been created.<br />');
      }
      elseif ($this->_action & CRM_Core_Action::UPDATE) {
        $statusMsg = ts('Pledge has been updated.<br />');
      }
    }

    // handle Acknowledgment.
    if (!empty($formValues['is_acknowledge'])) {

      // calculate scheduled amount.
      $params['scheduled_amount'] = round($params['amount'] / $params['installments']);
      $params['total_pledge_amount'] = $params['amount'];
      // get some required pledge values in params.
      $params['id'] = $pledgeID;
      $params['acknowledge_date'] = $pledge->acknowledge_date;
      $params['is_test'] = $pledge->is_test;
      $params['currency'] = $pledge->currency;
      // retrieve 'from email id' for acknowledgement
      $params['from_email_id'] = $formValues['from_email_address'];

      // send Acknowledgment mail.
      CRM_Pledge_BAO_Pledge::sendAcknowledgment($this, $params);

      $statusMsg .= ' ' . ts('An acknowledgment email has been sent to %1.<br />', [1 => $this->getContactValue('email_primary.email')]);
      // get the first valid payment id.
      $nextPaymentID = PledgePayment::get()
        ->addWhere('pledge_id', '=', $pledgeID)
        ->addWhere('status_id:name', 'IN', ['Pending', 'Overdue'])
        ->addOrderBy('scheduled_date')->setLimit(1)->execute()->first()['id'];
      // build the payment urls.
      if ($nextPaymentID) {
        $urlParams = "reset=1&action=add&cid={$this->_contactID}&ppid={$nextPaymentID}&context=pledge";
        $contribURL = CRM_Utils_System::url('civicrm/contact/view/contribution', $urlParams);
        $urlParams .= '&mode=live';
        $creditURL = CRM_Utils_System::url('civicrm/contact/view/contribution', $urlParams);

        // check if we can process credit card payment.
        $processors = CRM_Core_PseudoConstant::paymentProcessor(FALSE, FALSE,
          'billing_mode IN ( 1, 3 )'
        );
        if (count($processors) > 0) {
          $statusMsg .= ' ' . ts("If a payment is due now, you can record <a href='%1'>a check, EFT, or cash payment for this pledge</a> OR <a href='%2'>submit a credit card payment</a>.", [
            1 => $contribURL,
            2 => $creditURL,
          ]);
        }
        else {
          $statusMsg .= ' ' . ts("If a payment is due now, you can record <a href='%1'>a check, EFT, or cash payment for this pledge</a>.", [1 => $contribURL]);
        }
      }
    }
    CRM_Core_Session::setStatus($statusMsg, ts('Payment Due'), 'info');

    $buttonName = $this->controller->getButtonName();
    if ($this->getContext() === 'standalone') {
      if ($buttonName === $this->getButtonName('upload', 'new')) {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/pledge/add',
          'reset=1&action=add&context=standalone'
        ));
      }
      else {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view',
          "reset=1&cid={$this->_contactID}&selectedChild=pledge"
        ));
        // Refresh other tabs with related data
        $this->ajaxResponse['updateTabs'] = [
          '#tab_activity' => TRUE,
        ];
        if (CRM_Core_Permission::access('CiviContribute')) {
          $this->ajaxResponse['updateTabs']['#tab_contribute'] = CRM_Contact_BAO_Contact::getCountComponent('contribution', $this->_contactID);
        }
      }
    }
    elseif ($buttonName === $this->getButtonName('upload', 'new')) {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view/pledge',
        "reset=1&action=add&context=pledge&cid={$this->_contactID}"
      ));
    }
  }

  /**
   * Get the pledge ID.
   *
   * @api supported for external use.
   *
   * @return int|null
   * @throws \CRM_Core_Exception
   */
  public function getPledgeID(): ?int {
    if (!isset($this->_id)) {
      $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    }
    return $this->_id ? (int) $this->_id : NULL;
  }

}
