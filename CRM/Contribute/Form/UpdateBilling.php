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
 * This class generates form components for processing a contribution.
 */
class CRM_Contribute_Form_UpdateBilling extends CRM_Contribute_Form_ContributionRecur {
  protected $_mode = NULL;

  protected $_subscriptionDetails = NULL;

  public $_bltID = NULL;

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    parent::preProcess();
    if ($this->_crid) {
      // Are we cancelling a recurring contribution that is linked to an auto-renew membership?
      if ($this->_subscriptionDetails->membership_id) {
        $this->_mid = $this->_subscriptionDetails->membership_id;
      }
    }

    if ($this->_coid) {
      $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($this->_coid, 'contribute', 'info');
      $this->_paymentProcessor['object'] = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($this->_coid, 'contribute', 'obj');
    }

    if ($this->_mid) {
      $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($this->_mid, 'membership', 'info');
      $this->_paymentProcessor['object'] = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($this->_mid, 'membership', 'obj');
      $this->_subscriptionDetails = CRM_Contribute_BAO_ContributionRecur::getSubscriptionDetails($this->_mid, 'membership');
      $membershipTypes = CRM_Member_PseudoConstant::membershipType();
      $membershipTypeId = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $this->_mid, 'membership_type_id');
      $this->assign('membershipType', CRM_Utils_Array::value($membershipTypeId, $membershipTypes));
      $this->_mode = 'auto_renew';
    }

    if ((!$this->_crid && !$this->_coid && !$this->_mid) || (!$this->_subscriptionDetails)) {
      CRM_Core_Error::fatal('Required information missing.');
    }

    if (!$this->_paymentProcessor['object']->supports('updateSubscriptionBillingInfo')) {
      CRM_Core_Error::fatal(ts("%1 processor doesn't support updating subscription billing details.",
        array(1 => $this->_paymentProcessor['object']->_processorName)
      ));
    }
    $this->assign('paymentProcessor', $this->_paymentProcessor);

    $this->assignBillingType();

    $this->assign('frequency_unit', $this->_subscriptionDetails->frequency_unit);
    $this->assign('frequency_interval', $this->_subscriptionDetails->frequency_interval);
    $this->assign('amount', $this->_subscriptionDetails->amount);
    $this->assign('installments', $this->_subscriptionDetails->installments);
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
    $this->_defaults = array();

    if ($this->_subscriptionDetails->contact_id) {
      $fields = array();
      $names = array(
        'first_name',
        'middle_name',
        'last_name',
        "street_address-{$this->_bltID}",
        "city-{$this->_bltID}",
        "postal_code-{$this->_bltID}",
        "country_id-{$this->_bltID}",
        "state_province_id-{$this->_bltID}",
      );
      foreach ($names as $name) {
        $fields[$name] = 1;
      }
      $fields["state_province-{$this->_bltID}"] = 1;
      $fields["country-{$this->_bltID}"] = 1;
      $fields["email-{$this->_bltID}"] = 1;
      $fields['email-Primary'] = 1;

      CRM_Core_BAO_UFGroup::setProfileDefaults($this->_subscriptionDetails->contact_id, $fields, $this->_defaults);

      // use primary email address if billing email address is empty
      if (empty($this->_defaults["email-{$this->_bltID}"]) &&
        !empty($this->_defaults['email-Primary'])
      ) {
        $this->_defaults["email-{$this->_bltID}"] = $this->_defaults['email-Primary'];
      }

      foreach ($names as $name) {
        if (!empty($this->_defaults[$name])) {
          $this->_defaults['billing_' . $name] = $this->_defaults[$name];
        }
      }
    }

    $config = CRM_Core_Config::singleton();
    // set default country from config if no country set
    if (empty($this->_defaults["billing_country_id-{$this->_bltID}"])) {
      $this->_defaults["billing_country_id-{$this->_bltID}"] = $config->defaultContactCountry;
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

    $this->addButtons(array(
      array(
        'type' => $type,
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ),
    ));

    CRM_Core_Payment_Form::buildPaymentForm($this, $this->_paymentProcessor, TRUE, TRUE);
    $this->addFormRule(array('CRM_Contribute_Form_UpdateBilling', 'formRule'), $this);
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
    $errors = array();
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

    // now set the values for the billing location.
    foreach ($this->_fields as $name => $value) {
      $fields[$name] = 1;
    }
    $fields["email-{$this->_bltID}"] = 1;

    $processorParams = array();
    foreach ($params as $key => $val) {
      $key = str_replace('billing_', '', $key);
      list($key) = explode('-', $key);
      $processorParams[$key] = $val;
    }
    $processorParams['state_province'] = CRM_Core_PseudoConstant::stateProvince($params["billing_state_province_id-{$this->_bltID}"], FALSE);
    $processorParams['country'] = CRM_Core_PseudoConstant::country($params["billing_country_id-{$this->_bltID}"], FALSE);
    $processorParams['month'] = $processorParams['credit_card_exp_date']['M'];
    $processorParams['year'] = $processorParams['credit_card_exp_date']['Y'];
    $processorParams['subscriptionId'] = $this->_subscriptionDetails->subscription_id;
    $processorParams['amount'] = $this->_subscriptionDetails->amount;
    $updateSubscription = $this->_paymentProcessor['object']->updateSubscriptionBillingInfo($message, $processorParams);
    if (is_a($updateSubscription, 'CRM_Core_Error')) {
      CRM_Core_Error::displaySessionError($updateSubscription);
    }
    elseif ($updateSubscription) {
      $ctype = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_subscriptionDetails->contact_id, 'contact_type');
      $contact = &CRM_Contact_BAO_Contact::createProfileContact($params,
        $fields,
        $this->_subscriptionDetails->contact_id,
        NULL,
        NULL,
        $ctype
      );

      // build tpl params
      if ($this->_subscriptionDetails->membership_id) {
        $inputParams = array('id' => $this->_subscriptionDetails->membership_id);
        CRM_Member_BAO_Membership::getValues($inputParams, $tplParams);
        $tplParams = $tplParams[$this->_subscriptionDetails->membership_id];
        $tplParams['membership_status'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipStatus', $tplParams['status_id']);
        $tplParams['membershipType'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $tplParams['membership_type_id']);
        $status = ts('Billing details for your automatically renewed %1 membership have been updated.',
          array(1 => $tplParams['membershipType'])
        );
        $msgTitle = ts('Details Updated');
        $msgType = 'success';
      }
      else {
        $status = ts('Billing details for the recurring contribution of %1, every %2 %3 have been updated.',
          array(
            1 => $this->_subscriptionDetails->amount,
            2 => $this->_subscriptionDetails->frequency_interval,
            3 => $this->_subscriptionDetails->frequency_unit,
          )
        );
        $msgTitle = ts('Details Updated');
        $msgType = 'success';

        $tplParams = array(
          'recur_frequency_interval' => $this->_subscriptionDetails->frequency_interval,
          'recur_frequency_unit' => $this->_subscriptionDetails->frequency_unit,
          'amount' => $this->_subscriptionDetails->amount,
        );
      }

      // format new address for display
      $addressParts = array("street_address", "city", "postal_code", "state_province", "country");
      foreach ($addressParts as $part) {
        $addressParts[$part] = CRM_Utils_Array::value($part, $processorParams);
      }
      $tplParams['address'] = CRM_Utils_Address::format($addressParts);

      // format old address to store in activity details
      $this->_defaults["state_province-{$this->_bltID}"] = CRM_Core_PseudoConstant::stateProvince($this->_defaults["state_province-{$this->_bltID}"], FALSE);
      $this->_defaults["country-{$this->_bltID}"] = CRM_Core_PseudoConstant::country($this->_defaults["country-{$this->_bltID}"], FALSE);
      $addressParts = array("street_address", "city", "postal_code", "state_province", "country");
      foreach ($addressParts as $part) {
        $key = "{$part}-{$this->_bltID}";
        $addressParts[$part] = CRM_Utils_Array::value($key, $this->_defaults);
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

      $activityParams = array(
        'source_contact_id' => $this->_subscriptionDetails->contact_id,
        'activity_type_id' => CRM_Core_PseudoConstant::getKey(
          'CRM_Activity_BAO_Activity',
          'activity_type_id',
          'Update Recurring Contribution Billing Details'
        ),
        'subject' => ts('Recurring Contribution Billing Details Updated'),
        'details' => $message,
        'activity_date_time' => date('YmdHis'),
        'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Completed'),
      );
      $session = CRM_Core_Session::singleton();
      $cid = $session->get('userID');
      if ($cid) {
        $activityParams['target_contact_id'][] = $activityParams['source_contact_id'];
        $activityParams['source_contact_id'] = $cid;
      }
      CRM_Activity_BAO_Activity::create($activityParams);

      // send notification
      if ($this->_subscriptionDetails->contribution_page_id) {
        CRM_Core_DAO::commonRetrieveAll('CRM_Contribute_DAO_ContributionPage', 'id',
          $this->_subscriptionDetails->contribution_page_id, $value, array(
            'title',
            'receipt_from_name',
            'receipt_from_email',
          )
        );
        $receiptFrom = '"' . CRM_Utils_Array::value('receipt_from_name', $value[$this->_subscriptionDetails->contribution_page_id]) . '" <' . $value[$this->_subscriptionDetails->contribution_page_id]['receipt_from_email'] . '>';
      }
      else {
        $domainValues = CRM_Core_BAO_Domain::getNameAndEmail();
        $receiptFrom = "$domainValues[0] <$domainValues[1]>";
      }
      list($donorDisplayName, $donorEmail) = CRM_Contact_BAO_Contact::getContactDetails($this->_subscriptionDetails->contact_id);
      $tplParams['contact'] = array('display_name' => $donorDisplayName);

      $date = CRM_Utils_Date::format($processorParams['credit_card_exp_date']);
      $tplParams['credit_card_exp_date'] = CRM_Utils_Date::mysqlToIso($date);
      $tplParams['credit_card_number'] = CRM_Utils_System::mungeCreditCard($processorParams['credit_card_number']);
      $tplParams['credit_card_type'] = $processorParams['credit_card_type'];

      $sendTemplateParams = array(
        'groupName' => $this->_subscriptionDetails->membership_id ? 'msg_tpl_workflow_membership' : 'msg_tpl_workflow_contribution',
        'valueName' => $this->_subscriptionDetails->membership_id ? 'membership_autorenew_billing' : 'contribution_recurring_billing',
        'contactId' => $this->_subscriptionDetails->contact_id,
        'tplParams' => $tplParams,
        'isTest' => $this->_subscriptionDetails->is_test,
        'PDFFilename' => 'receipt.pdf',
        'from' => $receiptFrom,
        'toName' => $donorDisplayName,
        'toEmail' => $donorEmail,
      );
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
