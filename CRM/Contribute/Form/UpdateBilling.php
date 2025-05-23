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
 * This class generates form components for processing a contribution.
 */
class CRM_Contribute_Form_UpdateBilling extends CRM_Contribute_Form_ContributionRecur {
  protected $_mode = NULL;
  protected $_bltID = NULL;
  public $_paymentFields;
  public $_fields = [];

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    parent::preProcess();
    if ($this->getContributionRecurID()) {
      // Are we cancelling a recurring contribution that is linked to an auto-renew membership?
      if ($this->getSubscriptionDetails()->membership_id) {
        $this->_mid = $this->getSubscriptionDetails()->membership_id;
      }
    }

    if ($this->_coid) {
      $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($this->_coid, 'contribute', 'info');
      $this->_paymentProcessor['object'] = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($this->_coid, 'contribute', 'obj');
    }

    if ($this->getMembershipID()) {
      $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($this->getMembershipID(), 'membership', 'info');
      $this->_paymentProcessor['object'] = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($this->getMembershipID(), 'membership', 'obj');
      $membershipTypes = CRM_Member_PseudoConstant::membershipType();
      $membershipTypeId = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $this->getMembershipID(), 'membership_type_id');
      $this->assign('membershipType', $membershipTypes[$membershipTypeId] ?? NULL);
      $this->_mode = 'auto_renew';
    }

    if ((!$this->_crid && !$this->_coid && !$this->getMembershipID()) || (!$this->getSubscriptionDetails())) {
      throw new CRM_Core_Exception('Required information missing.');
    }

    if (!$this->getPaymentProcessorObject()->supports('updateSubscriptionBillingInfo')) {
      throw new CRM_Core_Exception(ts("%1 processor doesn't support updating subscription billing details.",
        [1 => $this->_paymentProcessor['title']]
      ));
    }
    $this->assign('paymentProcessor', $this->_paymentProcessor);

    $this->assignBillingType();

    $this->assign('recur_frequency_unit', $this->getSubscriptionDetails()->frequency_unit);
    $this->assign('recur_frequency_interval', $this->getSubscriptionDetails()->frequency_interval);
    $this->assign('amount', $this->getSubscriptionDetails()->amount);
    $this->assign('installments', $this->getSubscriptionDetails()->installments);
    $this->assign('mode', $this->_mode);

    // handle context redirection
    CRM_Contribute_BAO_ContributionRecur::setSubscriptionContext();
  }

  /**
   * Set the default values of various form elements.
   *
   * @return array
   *   Default values
   */
  public function setDefaultValues() {
    $this->_defaults = [];
    $billingLocationID = CRM_Core_BAO_LocationType::getBilling();
    if ($this->getSubscriptionDetails()->contact_id) {
      $fields = [];
      $names = [
        'first_name',
        'middle_name',
        'last_name',
        "street_address-{$billingLocationID}",
        "city-{$billingLocationID}",
        "postal_code-{$billingLocationID}",
        "country_id-{$billingLocationID}",
        "state_province_id-{$billingLocationID}",
      ];
      foreach ($names as $name) {
        $fields[$name] = 1;
      }
      $fields["state_province-{$billingLocationID}"] = 1;
      $fields["country-{$billingLocationID}"] = 1;
      $fields["email-{$billingLocationID}"] = 1;
      $fields['email-Primary'] = 1;

      CRM_Core_BAO_UFGroup::setProfileDefaults($this->getSubscriptionDetails()->contact_id, $fields, $this->_defaults);

      // use primary email address if billing email address is empty
      if (empty($this->_defaults["email-{$billingLocationID}"]) &&
        !empty($this->_defaults['email-Primary'])
      ) {
        $this->_defaults["email-{$billingLocationID}"] = $this->_defaults['email-Primary'];
      }

      foreach ($names as $name) {
        if (!empty($this->_defaults[$name])) {
          $this->_defaults['billing_' . $name] = $this->_defaults[$name];
        }
      }
    }

    $config = CRM_Core_Config::singleton();
    // set default country from config if no country set
    if (empty($this->_defaults["billing_country_id-{$billingLocationID}"])) {
      $this->_defaults["billing_country_id-{$billingLocationID}"] = $config->defaultContactCountry;
    }

    return $this->_defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $type = 'next';
    if ($this->isSelfService()) {
      $type = 'submit';
    }

    $this->addButtons([
      [
        'type' => $type,
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);

    CRM_Core_Payment_Form::buildPaymentForm($this, $this->_paymentProcessor, TRUE, TRUE);
    $this->addFormRule(['CRM_Contribute_Form_UpdateBilling', 'formRule'], $this);
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param CRM_Core_Form $self
   *
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];
    CRM_Core_Form::validateMandatoryFields($self->_fields, $fields, $errors);

    // validate the payment instrument values (e.g. credit card number)
    CRM_Core_Payment_Form::validatePaymentInstrument($self->_paymentProcessor['id'], $fields, $errors, NULL);

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $status = NULL;
    $billingLocationID = CRM_Core_BAO_LocationType::getBilling();
    // now set the values for the billing location.
    foreach ($this->_fields as $name => $value) {
      $fields[$name] = 1;
    }
    $fields["email-{$billingLocationID}"] = 1;

    $processorParams = [];
    foreach ($params as $key => $val) {
      $key = str_replace('billing_', '', $key);
      list($key) = explode('-', $key);
      $processorParams[$key] = $val;
    }
    $processorParams['billingStateProvince'] = $processorParams['state_province'] = CRM_Core_PseudoConstant::stateProvince($params["billing_state_province_id-{$billingLocationID}"], FALSE);
    $processorParams['billingCountry'] = $processorParams['country'] = CRM_Core_PseudoConstant::country($params["billing_country_id-{$billingLocationID}"], FALSE);
    $processorParams['month'] = CRM_Core_Payment_Form::getCreditCardExpirationMonth($processorParams);
    $processorParams['year'] = CRM_Core_Payment_Form::getCreditCardExpirationYear($processorParams);
    $processorParams['recurProcessorID'] = $processorParams['subscriptionId'] = $this->getSubscriptionDetails()->processor_id;
    $processorParams['amount'] = $this->getSubscriptionDetails()->amount;
    $processorParams['contributionRecurID'] = $this->getContributionRecurID();
    $message = '';
    $updateSubscription = $this->getPaymentProcessorObject()->updateSubscriptionBillingInfo($message, $processorParams);
    if (is_a($updateSubscription, 'CRM_Core_Error')) {
      CRM_Core_Error::displaySessionError($updateSubscription);
    }
    elseif ($updateSubscription) {
      $ctype = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->getSubscriptionDetails()->contact_id, 'contact_type');
      CRM_Contact_BAO_Contact::createProfileContact($params,
        $fields,
        $this->getSubscriptionDetails()->contact_id,
        NULL,
        NULL,
        $ctype
      );

      // build tpl params
      if ($this->getSubscriptionDetails()->membership_id) {
        $inputParams = ['id' => $this->getSubscriptionDetails()->membership_id];
        CRM_Member_BAO_Membership::getValues($inputParams, $tplParams);
        $tplParams = $tplParams[$this->getSubscriptionDetails()->membership_id];
        $tplParams['membership_status'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipStatus', $tplParams['status_id']);
        $tplParams['membershipType'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $tplParams['membership_type_id']);
        $status = ts('Billing details for your automatically renewed %1 membership have been updated.',
          [1 => $tplParams['membershipType']]
        );
        $msgTitle = ts('Details Updated');
        $msgType = 'success';
      }
      else {
        $status = ts('Billing details for the recurring contribution of %1, every %2 %3 have been updated.',
          [
            1 => $this->getSubscriptionDetails()->amount,
            2 => $this->getSubscriptionDetails()->frequency_interval,
            3 => $this->getSubscriptionDetails()->frequency_unit,
          ]
        );
        $msgTitle = ts('Details Updated');
        $msgType = 'success';

        $tplParams = [
          'recur_frequency_interval' => $this->getSubscriptionDetails()->frequency_interval,
          'recur_frequency_unit' => $this->getSubscriptionDetails()->frequency_unit,
          'amount' => $this->getSubscriptionDetails()->amount,
        ];
      }

      // format new address for display
      $addressParts = ["street_address", "city", "postal_code", "state_province", "country"];
      foreach ($addressParts as $part) {
        $addressParts[$part] = $processorParams[$part] ?? NULL;
      }
      $tplParams['address'] = CRM_Utils_Address::format($addressParts);

      // format old address to store in activity details
      $this->_defaults["state_province-{$billingLocationID}"] = CRM_Core_PseudoConstant::stateProvince($this->_defaults["state_province-{$billingLocationID}"] ?? NULL, FALSE);
      $this->_defaults["country-{$billingLocationID}"] = CRM_Core_PseudoConstant::country($this->_defaults["country-{$billingLocationID}"] ?? NULL, FALSE);
      $addressParts = ["street_address", "city", "postal_code", "state_province", "country"];
      foreach ($addressParts as $part) {
        $key = "{$part}-{$billingLocationID}";
        $addressParts[$part] = $this->_defaults[$key] ?? NULL;
      }
      $this->_defaults['address'] = CRM_Utils_Address::format($addressParts);

      // format new billing name
      $name = $processorParams['first_name'];
      if (!empty($processorParams['middle_name'])) {
        $name .= " {$processorParams['middle_name']}";
      }
      $name .= ' ' . $processorParams['last_name'];
      $name = trim($name);
      $tplParams['billingName'] = $name;

      // format old billing name
      $name = $this->_defaults['first_name'];
      if (!empty($this->_defaults['middle_name'])) {
        $name .= " {$this->_defaults['middle_name']}";
      }
      $name .= ' ' . $this->_defaults['last_name'];
      $name = trim($name);
      $this->_defaults['billingName'] = $name;

      $message .= "
<br/><br/>New Billing Name and Address
<br/>==============================
<br/>{$tplParams['billingName']}
<br/>{$tplParams['address']}

<br/><br/>Previous Billing Name and Address
<br/>==================================
<br/>{$this->_defaults['billingName']}
<br/>{$this->_defaults['address']}";

      $activityParams = [
        'source_contact_id' => $this->getSubscriptionDetails()->contact_id,
        'activity_type_id' => CRM_Core_PseudoConstant::getKey(
          'CRM_Activity_BAO_Activity',
          'activity_type_id',
          'Update Recurring Contribution Billing Details'
        ),
        'subject' => ts('Recurring Contribution Billing Details Updated'),
        'details' => $message,
        'activity_date_time' => date('YmdHis'),
        'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Completed'),
      ];
      $session = CRM_Core_Session::singleton();
      $cid = $session->get('userID');
      if ($cid) {
        $activityParams['target_contact_id'][] = $activityParams['source_contact_id'];
        $activityParams['source_contact_id'] = $cid;
      }
      CRM_Activity_BAO_Activity::create($activityParams);

      list($donorDisplayName, $donorEmail) = CRM_Contact_BAO_Contact::getContactDetails($this->getSubscriptionDetails()->contact_id);
      $tplParams['contact'] = ['display_name' => $donorDisplayName];

      $tplParams = array_merge($tplParams, CRM_Contribute_Form_AbstractEditPayment::formatCreditCardDetails($processorParams));
      $tplParams['receipt_from_email'] = CRM_Contribute_BAO_ContributionRecur::getRecurFromAddress($this->getContributionRecurID());
      $sendTemplateParams = [
        'groupName' => $this->getSubscriptionDetails()->membership_id ? 'msg_tpl_workflow_membership' : 'msg_tpl_workflow_contribution',
        'workflow' => $this->getSubscriptionDetails()->membership_id ? 'membership_autorenew_billing' : 'contribution_recurring_billing',
        'contactId' => $this->getSubscriptionDetails()->contact_id,
        'tplParams' => $tplParams,
        'isTest' => $this->getSubscriptionDetails()->is_test,
        'PDFFilename' => 'receipt.pdf',
        'from' => $tplParams['receipt_from_email'],
        'toName' => $donorDisplayName,
        'toEmail' => $donorEmail,
      ];
      list($sent) = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
    }
    else {
      $status = ts('There was some problem updating the billing details.');
      $msgTitle = ts('Update Error');
      $msgType = 'error';
    }

    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');
    if ($userID && $status) {
      $session->setStatus($status, $msgTitle, $msgType);
    }
    elseif (!$userID) {
      if ($status) {
        CRM_Utils_System::setUFMessage($status);
      }
      $result = (int) ($updateSubscription && isset($ctype));
      if (isset($tplParams)) {
        $session->set('resultParams', $tplParams);
      }
      return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute/subscriptionstatus',
        "reset=1&task=billing&result={$result}"));
    }
  }

}
