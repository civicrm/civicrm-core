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

/**
 * This class generates form components for processing a pledge
 *
 */
class CRM_Pledge_Form_Pledge extends CRM_Core_Form {
  public $_action;

  /**
   * the id of the pledge that we are proceessing
   *
   * @var int
   * @public
   */
  public $_id;

  /**
   * the id of the contact associated with this pledge
   *
   * @var int
   * @public
   */
  public $_contactID;

  /**
   * The Pledge values if an existing pledge
   * @public
   */
  public $_values;

  /**
   * stores the honor id
   *
   * @var int
   * @public
   */
  public $_honorID = NULL;

  /**
   * The Pledge frequency Units
   * @public
   */
  public $_freqUnits;

  /**
   * is current pledge pending.
   * @public
   */
  public $_isPending = FALSE;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    $this->_action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 'add'
    );
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);

    // check for action permissions.
    if (!CRM_Core_Permission::checkActionPermission('CiviPledge', $this->_action)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page'));
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

      // set title to "Pledge - "+Contact Name
      $displayName = $this->userDisplayName;
      $pageTitle = ts('Pledge by'). ' ' . $displayName;
      $this->assign('pageTitle', $pageTitle);
      CRM_Utils_System::setTitle($pageTitle);
    }

    //build custom data
    CRM_Custom_Form_CustomData::preProcess($this, NULL, NULL, 1, 'Pledge', $this->_id);

    $this->_values = array();
    // current pledge id
    if ($this->_id) {
      //get the contribution id
      $this->_contributionID = CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_PledgePayment',
        $this->_id, 'contribution_id', 'pledge_id'
      );
      $params = array('id' => $this->_id);
      CRM_Pledge_BAO_Pledge::getValues($params, $this->_values);

      //get the honorID
      $this->_honorID = CRM_Utils_Array::value('honor_contact_id', $this->_values);

      $paymentStatusTypes = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

      //check for pending pledge.
      if (CRM_Utils_Array::value('status_id', $this->_values) ==
        array_search('Pending', $paymentStatusTypes)
      ) {
        $this->_isPending = TRUE;
      }
      elseif (CRM_Utils_Array::value('status_id', $this->_values) ==
        array_search('Overdue', $paymentStatusTypes)
      ) {

        $allPledgePayments = array();
        CRM_Core_DAO::commonRetrieveAll('CRM_Pledge_DAO_PledgePayment',
          'pledge_id',
          $this->_id,
          $allPledgePayments,
          array('status_id')
        );

        foreach ($allPledgePayments as $key => $value) {
          $allStatus[$value['id']] = $paymentStatusTypes[$value['status_id']];
        }

        if (count(array_count_values($allStatus)) <= 2) {
          if (CRM_Utils_Array::value('Pending', array_count_values($allStatus))) {
            $this->_isPending = TRUE;
          }
        }
      }
    }

    //get the pledge frequency units.
    $this->_freqUnits = CRM_Core_OptionGroup::values('recur_frequency_units');

    $this->_fromEmails = CRM_Core_BAO_Email::getFromEmail();
  }

  /**
   * This function sets the default values for the form.
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $defaults = $this->_values;

    $fields = array();
    if ($this->_action & CRM_Core_Action::DELETE) {
      return $defaults;
    }

    if (CRM_Utils_Array::value('is_test', $defaults)) {
      $this->assign('is_test', TRUE);
    }

    if ($this->_id) {
      $startDate = CRM_Utils_Array::value('start_date', $this->_values);
      $createDate = CRM_Utils_Array::value('create_date', $this->_values);
      list($defaults['start_date']) = CRM_Utils_Date::setDateDefaults($startDate);
      list($defaults['create_date']) = CRM_Utils_Date::setDateDefaults($createDate);

      if ($ackDate = CRM_Utils_Array::value('acknowledge_date', $this->_values)) {
        list($defaults['acknowledge_date']) = CRM_Utils_Date::setDateDefaults($ackDate);
      }

      //check is this pledge pending
      // fix the display of the monetary value, CRM-4038
      if ($this->_isPending) {
        $defaults['eachPaymentAmount'] = $this->_values['amount'] / $this->_values['installments'];
        $defaults['eachPaymentAmount'] = CRM_Utils_Money::format($defaults['eachPaymentAmount'], NULL, '%a');
      }
      else {
        $this->assign('start_date', $startDate);
        $this->assign('create_date', $createDate);
      }
      // fix the display of the monetary value, CRM-4038
      if (isset($this->_values['amount'])) {
        $defaults['amount'] = CRM_Utils_Money::format($this->_values['amount'], NULL, '%a');
      }
      $this->assign('amount', $this->_values['amount']);
      $this->assign('installments', $defaults['installments']);
    }
    else {
      //default values.
      list($now) = CRM_Utils_Date::setDateDefaults();
      $defaults['create_date'] = $now;
      $defaults['start_date'] = $now;
      $defaults['installments'] = 12;
      $defaults['frequency_interval'] = 1;
      $defaults['frequency_day'] = 1;
      $defaults['initial_reminder_day'] = 5;
      $defaults['max_reminders'] = 1;
      $defaults['additional_reminder_day'] = 5;
      $defaults['frequency_unit'] = array_search('month', $this->_freqUnits);
            $defaults['financial_type_id']    = array_search( 'Donation', CRM_Contribute_PseudoConstant::financialType() );
    }

    $pledgeStatus = CRM_Contribute_PseudoConstant::contributionStatus();
    $pledgeStatusNames = CRM_Core_OptionGroup::values('contribution_status',
      FALSE, FALSE, FALSE, NULL, 'name', TRUE
    );
    // get default status label (pending)
    $defaultPledgeStatus = CRM_Utils_Array::value(array_search('Pending', $pledgeStatusNames),
      $pledgeStatus
    );

    //assign status.
    $this->assign('status', CRM_Utils_Array::value(CRM_Utils_Array::value('status_id', $this->_values),
        $pledgeStatus,
        $defaultPledgeStatus
      ));

    //honoree contact.
    if ($this->_honorID) {
      $honorDefault = array();
      $idParams = array('contact_id' => $this->_honorID);
      CRM_Contact_BAO_Contact::retrieve($idParams, $honorDefault);
      $honorType = CRM_Core_PseudoConstant::honor();
      $defaults['honor_prefix_id'] = $honorDefault['prefix_id'];
      $defaults['honor_first_name'] = CRM_Utils_Array::value('first_name', $honorDefault);
      $defaults['honor_last_name'] = CRM_Utils_Array::value('last_name', $honorDefault);
      $defaults['honor_email'] = CRM_Utils_Array::value('email', $honorDefault['email'][1]);
      $defaults['honor_type'] = $honorType[$defaults['honor_type_id']];
    }

    if (isset($this->userEmail)) {
      $this->assign('email', $this->userEmail);
    }

    // custom data set defaults
    $defaults += CRM_Custom_Form_CustomData::setDefaultValues($this);

    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons(array(
          array(
            'type' => 'next',
            'name' => ts('Delete'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
      return;
    }

    if ($this->_context == 'standalone') {
      CRM_Contact_Form_NewContact::buildQuickForm($this);
    }

    $showAdditionalInfo = FALSE;
    $this->_formType = CRM_Utils_Array::value('formType', $_GET);

    //fix to load honoree pane on edit.
    $defaults = array();
    if ($this->_honorID) {
      $defaults['hidden_Honoree'] = 1;
    }

    $paneNames = array(
      'Honoree Information' => 'Honoree',
      'Payment Reminders' => 'PaymentReminders',
    );
    foreach ($paneNames as $name => $type) {
      $urlParams = "snippet=4&formType={$type}";
      $allPanes[$name] = array('url' => CRM_Utils_System::url('civicrm/contact/view/pledge', $urlParams),
        'open' => 'false',
        'id' => $type,
      );
      //see if we need to include this paneName in the current form
      if ($this->_formType == $type ||
        CRM_Utils_Array::value("hidden_{$type}", $_POST) ||
        CRM_Utils_Array::value("hidden_{$type}", $defaults)
      ) {
        $showAdditionalInfo = TRUE;
        $allPanes[$name]['open'] = 'true';
      }
      eval('CRM_Contribute_Form_AdditionalInfo::build' . $type . '( $this );');
    }

    $this->assign('allPanes', $allPanes);
    $this->assign('showAdditionalInfo', $showAdditionalInfo);

    if ($this->_formType) {
      $this->assign('formType', $this->_formType);
      return;
    }

    $this->applyFilter('__ALL__', 'trim');

    //pledge fields.
    $attributes = CRM_Core_DAO::getAttribute('CRM_Pledge_DAO_Pledge');

    $this->assign('isPending', $this->_isPending);

    $js = array(
      'onblur' => "calculatedPaymentAmount( );",
      'onkeyup' => "calculatedPaymentAmount( );",
    );

    $currencyFreeze = FALSE;
    if ($this->_id &&
      !$this->_isPending
    ) {
      $currencyFreeze = TRUE;
    }

    $element = $this->addMoney('amount', ts('Total Pledge Amount'), TRUE,
      array_merge($attributes['pledge_amount'], $js), TRUE,
      'currency', NULL, $currencyFreeze
    );

    if ($this->_id &&
      !$this->_isPending
    ) {
      $element->freeze();
    }

    $element = &$this->add('text', 'installments', ts('To be paid in'),
      array_merge($attributes['installments'], $js), TRUE
    );
    $this->addRule('installments', ts('Please enter a valid number of installments.'), 'positiveInteger');
    if ($this->_id &&
      !$this->_isPending
    ) {
      $element->freeze();
    }

    $element = &$this->add('text', 'frequency_interval', ts('every'),
      $attributes['pledge_frequency_interval'], TRUE
    );
    $this->addRule('frequency_interval', ts('Please enter a number for frequency (e.g. every "3" months).'), 'positiveInteger');
    if ($this->_id &&
      !$this->_isPending
    ) {
      $element->freeze();
    }

    // Fix frequency unit display for use with frequency_interval
    $freqUnitsDisplay = array();
    foreach ($this->_freqUnits as $val => $label) {
      $freqUnitsDisplay[$val] = ts('%1(s)', array(1 => $label));
    }
    $element = &$this->add('select', 'frequency_unit',
      ts('Frequency'),
      array(
        '' => ts('- select -')) + $freqUnitsDisplay,
      TRUE
    );

    if ($this->_id &&
      !$this->_isPending
    ) {
      $element->freeze();
    }

    $element = &$this->add('text', 'frequency_day', ts('Payments are due on the'), $attributes['frequency_day'], TRUE);
    $this->addRule('frequency_day', ts('Please enter a valid payment due day.'), 'positiveInteger');
    if ($this->_id &&
      !$this->_isPending
    ) {
      $element->freeze();
    }

    $this->add('text', 'eachPaymentAmount', ts('each'), array('size' => 10, 'style' => "background-color:#EBECE4", 'READONLY'));

    //add various dates
    if (!$this->_id || $this->_isPending) {
      $this->addDate('create_date', ts('Pledge Made'), TRUE);
      $this->addDate('start_date', ts('Payments Start'), TRUE);
    }

    if ($this->_id &&
      !$this->_isPending
    ) {
      $eachPaymentAmount = $this->_values['original_installment_amount'];
      $this->assign('currency', $this->_values['currency']);
      $this->assign('eachPaymentAmount', $eachPaymentAmount);
      $this->assign('hideCalender', TRUE);
    }

    if (CRM_Utils_Array::value('status_id', $this->_values) !=
      array_search('Cancelled', CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name'))
    ) {

      $this->addElement('checkbox', 'is_acknowledge', ts('Send Acknowledgment?'), NULL,
        array('onclick' => "showHideByValue( 'is_acknowledge', '', 'acknowledgeDate', 'table-row', 'radio', true); showHideByValue( 'is_acknowledge', '', 'fromEmail', 'table-row', 'radio', false );")
      );

      $this->add('select', 'from_email_address', ts('Receipt From'), $this->_fromEmails);
    }

    $this->addDate('acknowledge_date', ts('Acknowledgment Date'));

        $this->add('select', 'financial_type_id', 
                   ts( 'Financial Type' ), 
                   array(''=>ts( '- select -' )) + CRM_Contribute_PseudoConstant::financialType( ),
      TRUE
    );

    //CRM-7362 --add campaigns.
    CRM_Campaign_BAO_Campaign::addCampaign($this, CRM_Utils_Array::value('campaign_id', $this->_values));

    $pageIds = array();
    CRM_Core_DAO::commonRetrieveAll('CRM_Pledge_DAO_PledgeBlock', 'entity_table',
      'civicrm_contribution_page', $pageIds, array('entity_id')
    );
    $pages = CRM_Contribute_PseudoConstant::contributionPage();
    $pledgePages = array();
    foreach ($pageIds as $key => $value) {
      $pledgePages[$value['entity_id']] = $pages[$value['entity_id']];
    }
    $ele = $this->add('select', 'contribution_page_id', ts('Self-service Payments Page'),
      array(
        '' => ts('- select -')) + $pledgePages
    );

    $mailingInfo = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
      'mailing_backend'
    );
    $this->assign('outBound_option', $mailingInfo['outBound_option']);

    //build custom data
    CRM_Custom_Form_CustomData::buildQuickForm($this);

    // make this form an upload since we dont know if the custom data injected dynamically
    // is of type file etc $uploadNames = $this->get( 'uploadNames' );
    $this->addButtons(array(
        array(
          'type' => 'upload',
          'name' => ts('Save'),
          'js' => array('onclick' => "return verify( );"),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'upload',
          'name' => ts('Save and New'),
          'js' => array('onclick' => "return verify( );"),
          'subName' => 'new',
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );

    $this->addFormRule(array('CRM_Pledge_Form_Pledge', 'formRule'), $this);

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
    }
  }

  /**
   * global form rule
   *
   * @param array $fields  the input form values
   * @param array $files   the uploaded files if any
   * @param array $options additional user data
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $self) {
    $errors = array();

    //check if contact is selected in standalone mode
    if (isset($fields['contact_select_id'][1]) && !$fields['contact_select_id'][1]) {
      $errors['contact[1]'] = ts('Please select a contact or create new contact');
    }

    if (isset($fields['honor_type_id'])) {
      if (!((CRM_Utils_Array::value('honor_first_name', $fields) &&
            CRM_Utils_Array::value('honor_last_name', $fields)
          ) ||
          CRM_Utils_Array::value('honor_email', $fields)
        )) {
        $errors['honor_first_name'] = ts('Honor First Name and Last Name OR an email should be set.');
      }
    }
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
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Pledge_BAO_Pledge::deletePledge($this->_id);
      return;
    }

    //get the submitted form values.
    $formValues = $this->controller->exportValues($this->_name);

    // set the contact, when contact is selected
    if (CRM_Utils_Array::value('contact_select_id', $formValues)) {
      $this->_contactID = $formValues['contact_select_id'][1];
    }

    $config = CRM_Core_Config::singleton();
    $session = CRM_Core_Session::singleton();

    //get All Payments status types.
    $paymentStatusTypes = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

    $fields = array(
      'frequency_unit',
      'frequency_interval',
      'frequency_day',
      'installments',
                         'financial_type_id',
      'initial_reminder_day',
      'max_reminders',
      'additional_reminder_day',
      'honor_type_id',
      'honor_prefix_id',
      'honor_first_name',
      'honor_last_name',
      'honor_email',
      'contribution_page_id',
      'campaign_id',
    );
    foreach ($fields as $f) {
      $params[$f] = CRM_Utils_Array::value($f, $formValues);
    }

    //defaults status is "Pending".
    //if update get status.
    if ($this->_id) {
      $params['pledge_status_id'] = $params['status_id'] = $this->_values['status_id'];
    }
    else {
      $params['pledge_status_id'] = $params['status_id'] = array_search('Pending', $paymentStatusTypes);
    }
    //format amount
    $params['amount'] = CRM_Utils_Rule::cleanMoney(CRM_Utils_Array::value('amount', $formValues));
    $params['currency'] = CRM_Utils_Array::value('currency', $formValues);
    $params['original_installment_amount'] = ($params['amount'] / $params['installments']);

    $dates = array('create_date', 'start_date', 'acknowledge_date', 'cancel_date');
    foreach ($dates as $d) {
      if ($this->_id && (!$this->_isPending) && CRM_Utils_Array::value($d, $this->_values)) {
        if ($d == 'start_date') {
          $params['scheduled_date'] = CRM_Utils_Date::processDate($this->_values[$d]);
        }
        $params[$d] = CRM_Utils_Date::processDate($this->_values[$d]);
      }
      elseif (CRM_Utils_Array::value($d, $formValues) && !CRM_Utils_System::isNull($formValues[$d])) {
        if ($d == 'start_date') {
          $params['scheduled_date'] = CRM_Utils_Date::processDate($formValues[$d]);
        }
        $params[$d] = CRM_Utils_Date::processDate($formValues[$d]);
      }
      else {
        $params[$d] = 'null';
      }
    }

    if (CRM_Utils_Array::value('is_acknowledge', $formValues)) {
      $params['acknowledge_date'] = date('Y-m-d');
    }

    // assign id only in update mode
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
    }

    $params['contact_id'] = $this->_contactID;

    //handle Honoree contact.
    if (CRM_Utils_Array::value('honor_type_id', $params)) {
      if ($this->_honorID) {
        $honorID = CRM_Contribute_BAO_Contribution::createHonorContact($params, $this->_honorID);
      }
      else {
        $honorID = CRM_Contribute_BAO_Contribution::createHonorContact($params);
      }
      $params['honor_contact_id'] = $honorID;
    }
    else {
      $params['honor_contact_id'] = 'null';
    }

    //format custom data
    if (CRM_Utils_Array::value('hidden_custom', $formValues)) {
      $params['hidden_custom'] = 1;

      $customFields = CRM_Core_BAO_CustomField::getFields('Pledge');
      $params['custom'] = CRM_Core_BAO_CustomField::postProcess($formValues,
        $customFields,
        $this->_id,
        'Pledge'
      );
    }

    //handle pending pledge.
    $params['is_pledge_pending'] = $this->_isPending;

    //create pledge record.
    $pledge = CRM_Pledge_BAO_Pledge::create($params);

    $statusMsg = NULL;

    if ($pledge->id) {
      //set the status msg.
      if ($this->_action & CRM_Core_Action::ADD) {
        $statusMsg = ts('Pledge has been recorded and the payment schedule has been created.<br />');
      }
      elseif ($this->_action & CRM_Core_Action::UPDATE) {
        $statusMsg = ts('Pledge has been updated.<br />');
      }
    }

    //handle Acknowledgment.
    if (CRM_Utils_Array::value('is_acknowledge', $formValues) && $pledge->id) {

      //calculate scheduled amount.
      $params['scheduled_amount'] = round($params['amount'] / $params['installments']);
      $params['total_pledge_amount'] = $params['amount'];
      //get some required pledge values in params.
      $params['id'] = $pledge->id;
      $params['acknowledge_date'] = $pledge->acknowledge_date;
      $params['is_test'] = $pledge->is_test;
      $params['currency'] = $pledge->currency;
      // retrieve 'from email id' for acknowledgement
      $params['from_email_id'] = $formValues['from_email_address'];

      $this->paymentId = NULL;
      //send Acknowledgment mail.
      CRM_Pledge_BAO_Pledge::sendAcknowledgment($this, $params);

      if (!isset($this->userEmail)) {
        list($this->userDisplayName,
          $this->userEmail
        ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactID);
      }

      $statusMsg .= ' ' . ts("An acknowledgment email has been sent to %1.<br />", array(1 => $this->userEmail));

      //build the payment urls.
      if ($this->paymentId) {
        $urlParams = "reset=1&action=add&cid={$this->_contactID}&ppid={$this->paymentId}&context=pledge";
        $contribURL = CRM_Utils_System::url('civicrm/contact/view/contribution', $urlParams);
        $urlParams .= "&mode=live";
        $creditURL = CRM_Utils_System::url('civicrm/contact/view/contribution', $urlParams);

        //check if we can process credit card payment.
        $processors = CRM_Core_PseudoConstant::paymentProcessor(FALSE, FALSE,
          "billing_mode IN ( 1, 3 )"
        );
        if (count($processors) > 0) {
          $statusMsg .= ' ' . ts("If a payment is due now, you can record <a href='%1'>a check, EFT, or cash payment for this pledge</a> OR <a href='%2'>submit a credit card payment</a>.", array(1 => $contribURL, 2 => $creditURL));
        }
        else {
          $statusMsg .= ' ' . ts("If a payment is due now, you can record <a href='%1'>a check, EFT, or cash payment for this pledge</a>.", array(1 => $contribURL));
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

