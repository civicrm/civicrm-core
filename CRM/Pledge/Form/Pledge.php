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
 * This class generates form components for processing a pledge
 */
class CRM_Pledge_Form_Pledge extends CRM_Core_Form {
  public $_action;

  /**
   * The id of the pledge that we are proceessing.
   *
   * @var int
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
  public $_values;

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
   */
  public function preProcess() {
    $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    $this->_action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 'add'
    );
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);

    // check for action permissions.
    if (!CRM_Core_Permission::checkActionPermission('CiviPledge', $this->_action)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
    }

    $this->assign('action', $this->_action);
    $this->assign('context', $this->_context);
    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->userDisplayName = $this->userEmail = NULL;
    if ($this->_contactID) {
      list($this->userDisplayName,
        $this->userEmail
        ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactID);
      $this->assign('displayName', $this->userDisplayName);
    }

    $this->setPageTitle(ts('Pledge'));

    // build custom data
    CRM_Custom_Form_CustomData::preProcess($this, NULL, NULL, 1, 'Pledge', $this->_id);
    $this->_values = [];
    // current pledge id
    if ($this->_id) {
      // get the contribution id
      $this->_contributionID = CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_PledgePayment',
        $this->_id, 'contribution_id', 'pledge_id'
      );
      $params = ['id' => $this->_id];
      CRM_Pledge_BAO_Pledge::getValues($params, $this->_values);

      $this->_isPending = (CRM_Pledge_BAO_Pledge::pledgeHasFinancialTransactions($this->_id, CRM_Utils_Array::value('status_id', $this->_values))) ? FALSE : TRUE;
    }

    // get the pledge frequency units.
    $this->_freqUnits = CRM_Core_OptionGroup::values('recur_frequency_units');

    $this->_fromEmails = CRM_Core_BAO_Email::getFromEmail();
  }

  /**
   * Set default values for the form.
   * The default values are retrieved from the database.
   */
  public function setDefaultValues() {
    $defaults = $this->_values;

    if ($this->_action & CRM_Core_Action::DELETE) {
      return $defaults;
    }

    if (!empty($defaults['is_test'])) {
      $this->assign('is_test', TRUE);
    }

    if ($this->_id) {
      // check is this pledge pending.
      // fix the display of the monetary value, CRM-4038.
      if ($this->_isPending) {
        $defaults['eachPaymentAmount'] = $this->_values['amount'] / $this->_values['installments'];
        $defaults['eachPaymentAmount'] = CRM_Utils_Money::format($defaults['eachPaymentAmount'], NULL, '%a');
      }

      // fix the display of the monetary value, CRM-4038
      if (isset($this->_values['amount'])) {
        $defaults['amount'] = CRM_Utils_Money::format($this->_values['amount'], NULL, '%a');
      }
      $this->assign('amount', $this->_values['amount']);
      $this->assign('installments', $defaults['installments']);
    }
    else {
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
    $this->assign('status', CRM_Utils_Array::value(CRM_Utils_Array::value('status_id', $this->_values),
      $pledgeStatus,
      $defaultPledgeStatus
    ));

    if (isset($this->userEmail)) {
      $this->assign('email', $this->userEmail);
    }

    // custom data set defaults
    $defaults += CRM_Custom_Form_CustomData::setDefaultValues($this);

    return $defaults;
  }

  /**
   * Build the form object.
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

    if ($this->_context == 'standalone') {
      $this->addEntityRef('contact_id', ts('Contact'), [
        'create' => TRUE,
        'api' => ['extra' => ['email']],
      ], TRUE);
    }

    $showAdditionalInfo = FALSE;
    $this->_formType = CRM_Utils_Array::value('formType', $_GET);

    $defaults = [];

    $paneNames = [
      'Payment Reminders' => 'PaymentReminders',
    ];
    foreach ($paneNames as $name => $type) {
      $urlParams = "snippet=4&formType={$type}";
      $allPanes[$name] = [
        'url' => CRM_Utils_System::url('civicrm/contact/view/pledge', $urlParams),
        'open' => 'false',
        'id' => $type,
      ];
      // see if we need to include this paneName in the current form
      if ($this->_formType == $type || !empty($_POST["hidden_{$type}"]) ||
        CRM_Utils_Array::value("hidden_{$type}", $defaults)
      ) {
        $showAdditionalInfo = TRUE;
        $allPanes[$name]['open'] = 'true';
      }
      $fnName = "build{$type}";
      CRM_Contribute_Form_AdditionalInfo::$fnName($this);
    }

    $this->assign('allPanes', $allPanes);
    $this->assign('showAdditionalInfo', $showAdditionalInfo);

    if ($this->_formType) {
      $this->assign('formType', $this->_formType);
      return;
    }

    $this->applyFilter('__ALL__', 'trim');

    // pledge fields.
    $attributes = CRM_Core_DAO::getAttribute('CRM_Pledge_DAO_Pledge');

    $this->assign('isPending', $this->_isPending);

    $js = [
      'onblur' => "calculatedPaymentAmount( );",
      'onkeyup' => "calculatedPaymentAmount( );",
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
      TRUE
    );

    $frequencyDay = $this->add('number', 'frequency_day', ts('Payments are due on the'), $attributes['frequency_day'], TRUE);
    $this->addRule('frequency_day', ts('Please enter a valid payment due day.'), 'positiveInteger');

    $this->add('text', 'eachPaymentAmount', ts('each'), [
      'size' => 10,
      'style' => "background-color:#EBECE4",
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
      $this->assign('eachPaymentAmount', $eachPaymentAmount);
    }

    if (CRM_Utils_Array::value('status_id', $this->_values) !=
      CRM_Core_PseudoConstant::getKey('CRM_Pledge_BAO_Pledge', 'status_id', 'Cancelled')
    ) {

      $this->addElement('checkbox', 'is_acknowledge', ts('Send Acknowledgment?'), NULL,
        ['onclick' => "showHideByValue( 'is_acknowledge', '', 'acknowledgeDate', 'table-row', 'radio', true); showHideByValue( 'is_acknowledge', '', 'fromEmail', 'table-row', 'radio', false );"]
      );

      $this->add('select', 'from_email_address', ts('Receipt From'), $this->_fromEmails);
    }

    $this->add('datepicker', 'acknowledge_date', ts('Acknowledgment Date'), [], FALSE, ['time' => FALSE]);

    $this->add('select', 'financial_type_id',
      ts('Financial Type'),
      ['' => ts('- select -')] + CRM_Contribute_PseudoConstant::financialType(),
      TRUE
    );

    // CRM-7362 --add campaigns.
    CRM_Campaign_BAO_Campaign::addCampaign($this, CRM_Utils_Array::value('campaign_id', $this->_values));

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
      ['' => ts('- select -')] + $pledgePages
    );

    $mailingInfo = Civi::settings()->get('mailing_backend');
    $this->assign('outBound_option', $mailingInfo['outBound_option']);

    // build custom data
    CRM_Custom_Form_CustomData::buildQuickForm($this);

    // make this form an upload since we dont know if the custom data injected dynamically
    // is of type file etc $uploadNames = $this->get( 'uploadNames' );
    $this->addButtons([
        [
          'type' => 'upload',
          'name' => ts('Save'),
          'js' => ['onclick' => "return verify( );"],
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
    ]);

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
   * @param $self
   *
   *
   * @return bool|array
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

    if ($fields['frequency_unit'] != 'week') {
      if ($fields['frequency_day'] > 31 || $fields['frequency_day'] == 0) {
        $errors['frequency_day'] = ts('Please enter a valid frequency day ie. 1 through 31.');
      }
    }
    elseif ($fields['frequency_unit'] == 'week') {
      if ($fields['frequency_day'] > 7 || $fields['frequency_day'] == 0) {
        $errors['frequency_day'] = ts('Please enter a valid frequency day ie. 1 through 7.');
      }
    }
    return $errors;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Pledge_BAO_Pledge::deletePledge($this->_id);
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
      $params[$f] = CRM_Utils_Array::value($f, $formValues);
    }

    // format amount
    $params['amount'] = CRM_Utils_Rule::cleanMoney(CRM_Utils_Array::value('amount', $formValues));
    $params['currency'] = CRM_Utils_Array::value('currency', $formValues);
    $params['original_installment_amount'] = ($params['amount'] / $params['installments']);

    $dates = ['create_date', 'start_date', 'acknowledge_date', 'cancel_date'];
    foreach ($dates as $d) {
      if ($this->_id && (!$this->_isPending) && !empty($this->_values[$d])) {
        if ($d == 'start_date') {
          $params['scheduled_date'] = CRM_Utils_Date::processDate($this->_values[$d]);
        }
        $params[$d] = CRM_Utils_Date::processDate($this->_values[$d]);
      }
      elseif (!empty($formValues[$d]) && !CRM_Utils_System::isNull($formValues[$d])) {
        if ($d == 'start_date') {
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
      $params['id'] = $this->_id;
    }

    $params['contact_id'] = $this->_contactID;

    // format custom data
    if (!empty($formValues['hidden_custom'])) {
      $params['hidden_custom'] = 1;

      $customFields = CRM_Core_BAO_CustomField::getFields('Pledge');
      $params['custom'] = CRM_Core_BAO_CustomField::postProcess($formValues,
        $this->_id,
        'Pledge'
      );
    }

    // handle pending pledge.
    $params['is_pledge_pending'] = $this->_isPending;

    // create pledge record.
    $pledge = CRM_Pledge_BAO_Pledge::create($params);

    $statusMsg = NULL;

    if ($pledge->id) {
      // set the status msg.
      if ($this->_action & CRM_Core_Action::ADD) {
        $statusMsg = ts('Pledge has been recorded and the payment schedule has been created.<br />');
      }
      elseif ($this->_action & CRM_Core_Action::UPDATE) {
        $statusMsg = ts('Pledge has been updated.<br />');
      }
    }

    // handle Acknowledgment.
    if (!empty($formValues['is_acknowledge']) && $pledge->id) {

      // calculate scheduled amount.
      $params['scheduled_amount'] = round($params['amount'] / $params['installments']);
      $params['total_pledge_amount'] = $params['amount'];
      // get some required pledge values in params.
      $params['id'] = $pledge->id;
      $params['acknowledge_date'] = $pledge->acknowledge_date;
      $params['is_test'] = $pledge->is_test;
      $params['currency'] = $pledge->currency;
      // retrieve 'from email id' for acknowledgement
      $params['from_email_id'] = $formValues['from_email_address'];

      $this->paymentId = NULL;
      // send Acknowledgment mail.
      CRM_Pledge_BAO_Pledge::sendAcknowledgment($this, $params);

      if (!isset($this->userEmail)) {
        list($this->userDisplayName,
          $this->userEmail
          ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactID);
      }

      $statusMsg .= ' ' . ts("An acknowledgment email has been sent to %1.<br />", [1 => $this->userEmail]);

      // build the payment urls.
      if ($this->paymentId) {
        $urlParams = "reset=1&action=add&cid={$this->_contactID}&ppid={$this->paymentId}&context=pledge";
        $contribURL = CRM_Utils_System::url('civicrm/contact/view/contribution', $urlParams);
        $urlParams .= "&mode=live";
        $creditURL = CRM_Utils_System::url('civicrm/contact/view/contribution', $urlParams);

        // check if we can process credit card payment.
        $processors = CRM_Core_PseudoConstant::paymentProcessor(FALSE, FALSE,
          "billing_mode IN ( 1, 3 )"
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
    if ($this->_context == 'standalone') {
      if ($buttonName == $this->getButtonName('upload', 'new')) {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/pledge/add',
          'reset=1&action=add&context=standalone'
        ));
      }
      else {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view',
          "reset=1&cid={$this->_contactID}&selectedChild=pledge"
        ));
      }
    }
    elseif ($buttonName == $this->getButtonName('upload', 'new')) {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view/pledge',
        "reset=1&action=add&context=pledge&cid={$this->_contactID}"
      ));
    }
  }

}
