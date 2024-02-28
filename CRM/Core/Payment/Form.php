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
 * Class for constructing the payment processor block.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_Payment_Form {

  /**
   * Add payment fields depending on payment processor.
   *
   * The payment processor can implement the following functions to override the built in fields.
   *
   *  - getPaymentFormFields()
   *  - getPaymentFormFieldsMetadata()
   *  (planned - getBillingDetailsFormFields(), getBillingDetailsFormFieldsMetadata()
   *
   *  Note that this code is written to accommodate the possibility CiviCRM will switch to implementing pay later as a manual processor in future
   *
   * @param CRM_Contribute_Form_AbstractEditPayment|CRM_Contribute_Form_Contribution_Main $form
   * @param array $processor
   *   Array of properties including 'object' as loaded from CRM_Financial_BAO_PaymentProcessor::getPaymentProcessors.
   * @param int $billing_profile_id
   *   Display billing fields even for pay later.
   * @param bool $isBackOffice
   *   Is this a back office function? If so the option to suppress the cvn needs to be evaluated.
   * @param int $paymentInstrumentID
   *   ID of the payment processor.
   */
  public static function setPaymentFieldsByProcessor(&$form, $processor, $billing_profile_id = NULL, $isBackOffice = FALSE, $paymentInstrumentID = NULL) {
    // Load the pay-later processor
    // @todo load this right up where the other processors are loaded initially.
    if (empty($processor)) {
      $processor = CRM_Financial_BAO_PaymentProcessor::getPayment(0);
    }

    $processor['object']->setBillingProfile($billing_profile_id);
    $processor['object']->setBackOffice($isBackOffice);
    if (isset($paymentInstrumentID)) {
      $processor['object']->setPaymentInstrumentID($paymentInstrumentID);
    }
    $paymentTypeName = self::getPaymentTypeName($processor);
    $form->assign('paymentTypeName', $paymentTypeName);
    $form->assign('paymentTypeLabel', self::getPaymentLabel($processor['object']));
    $form->assign('isBackOffice', $isBackOffice);
    $form->_paymentFields = self::getPaymentFieldMetadata($processor);
    $form->_paymentFields = array_merge($form->_paymentFields, self::getBillingAddressMetadata($processor));
    $form->assign('paymentFields', self::getPaymentFields($processor));
    self::setBillingAddressFields($form, $processor);
  }

  /**
   * Add general billing fields.
   *
   * @param CRM_Core_Form $form
   * @param CRM_Core_Payment $processor
   */
  protected static function setBillingAddressFields(&$form, $processor) {
    $billingID = CRM_Core_BAO_LocationType::getBilling();
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('billingDetailsFields', self::getBillingAddressFields($processor, $billingID));
  }

  /**
   * Add the payment fields to the template.
   *
   * Generally this is the payment processor fields & the billing fields required
   * for the payment processor. However, this has been complicated by adding
   * pay later billing fields into this mix
   *
   * We now have the situation where the required fields cannot be set as required
   * on the form level if they are required for the payment processor, as another
   * processor might be selected and the validation will then be incorrect.
   *
   * However, if they are required for pay later we DO set them on the form level,
   * presumably assuming they will be required whatever happens.
   *
   * As a side-note this seems to re-enforce the argument for making pay later
   * operate as a payment processor rather than as a 'special thing on its own'.
   *
   * @param CRM_Core_Form $form
   *   Form that the payment fields are to be added to.
   * @param array $paymentFields
   *   Fields that are to be shown on the payment form.
   */
  protected static function addCommonFields(&$form, $paymentFields) {
    $requiredPaymentFields = $paymentFieldsMetadata = [];
    foreach ($paymentFields as $name => $field) {
      $field['extra'] ??= NULL;
      if ($field['htmlType'] == 'chainSelect') {
        $form->addChainSelect($field['name'], ['required' => FALSE]);
      }
      else {
        $form->add($field['htmlType'],
          $field['name'],
          $field['title'],
          $field['attributes'],
          FALSE,
          $field['extra']
        );
      }
      // This will cause the fields to be marked as required - but it is up to the payment processor to
      // validate it.
      $requiredPaymentFields[$field['name']] = $field['is_required'];
      $paymentFieldsMetadata[$field['name']] = array_merge(['description' => ''], $field);
    }

    $form->assign('paymentFieldsMetadata', $paymentFieldsMetadata);
    $form->assign('requiredPaymentFields', $requiredPaymentFields);
  }

  /**
   * Get the payment fields that apply to this processor.
   *
   * @param array $paymentProcessor
   *
   * @todo sometimes things like the country alter the required fields (e.g direct debit fields). We should possibly
   * set these before calling getPaymentFormFields (as we identify them).
   *
   * @return array
   */
  public static function getPaymentFields($paymentProcessor) {
    return $paymentProcessor['object']->getPaymentFormFields();
  }

  /**
   * @param array $paymentProcessor
   *
   * @return array
   */
  public static function getPaymentFieldMetadata($paymentProcessor) {
    return array_intersect_key($paymentProcessor['object']->getPaymentFormFieldsMetadata(), array_flip(self::getPaymentFields($paymentProcessor)));
  }

  /**
   * Get the billing fields that apply to this processor.
   *
   * @param array $paymentProcessor
   *
   * @todo sometimes things like the country alter the required fields (e.g postal code). We should possibly
   * set these before calling getPaymentFormFields (as we identify them).
   *
   * @return array
   */
  public static function getBillingAddressFields($paymentProcessor) {
    $billingLocationID = CRM_Core_BAO_LocationType::getBilling();
    return $paymentProcessor['object']->getBillingAddressFields($billingLocationID);
  }

  /**
   * @param array $paymentProcessor
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function getBillingAddressMetadata($paymentProcessor) {
    $billingLocationID = CRM_Core_BAO_LocationType::getBilling();
    $paymentProcessorObject = Civi\Payment\System::singleton()->getByProcessor($paymentProcessor);
    return array_intersect_key(
      $paymentProcessorObject->getBillingAddressFieldsMetadata($billingLocationID),
      array_flip(self::getBillingAddressFields($paymentProcessor, $billingLocationID))
    );
  }

  /**
   * @param array $paymentProcessor
   *
   * @return string
   */
  public static function getPaymentTypeName($paymentProcessor) {
    return $paymentProcessor['object']->getPaymentTypeName();
  }

  /**
   * @param CRM_Core_Payment $paymentProcessor
   *
   * @return string
   */
  public static function getPaymentTypeLabel($paymentProcessor) {
    return $paymentProcessor->getPaymentTypeLabel();
  }

  /**
   * @param CRM_Contribute_Form_AbstractEditPayment|CRM_Contribute_Form_Contribution_Main|CRM_Core_Payment_ProcessorForm|CRM_Contribute_Form_UpdateBilling $form
   * @param array $processor
   *   Array of properties including 'object' as loaded from CRM_Financial_BAO_PaymentProcessor::getPaymentProcessors.
   * @param int|string $billing_profile_id
   *   Id of a profile to be passed to the processor for the processor to merge with it's required fields.
   *   (currently only implemented by manual/ pay-later processor)
   *
   * @param bool $isBackOffice
   *   Is this a backoffice form. This could affect the display of the cvn or whether some processors show,
   *   although the distinction is losing it's meaning as front end forms are used for back office and a permission
   *   for the 'enter without cvn' is probably more appropriate. Paypal std does not support another user
   *   entering details but once again the issue is not back office but 'another user'.
   * @param int $paymentInstrumentID
   *   Payment instrument ID.
   */
  public static function buildPaymentForm(&$form, $processor, $billing_profile_id, $isBackOffice, $paymentInstrumentID = NULL) {
    //if the form has address fields assign to the template so the js can decide what billing fields to show
    $form->assign('profileAddressFields', $form->get('profileAddressFields'));
    $form->addExpectedSmartyVariable('suppressSubmitButton');
    if (!empty($processor['object']) && $processor['object']->buildForm($form)) {
      return;
    }

    self::setPaymentFieldsByProcessor($form, $processor, $billing_profile_id, $isBackOffice, $paymentInstrumentID);
    self::addCommonFields($form, $form->_paymentFields);
    self::addRules($form, $form->_paymentFields);
  }

  /**
   * @param CRM_Core_Form $form
   * @param array $paymentFields
   *   Array of properties including 'object' as loaded from CRM_Financial_BAO_PaymentProcessor::getPaymentProcessors.
   * @return void
   */
  protected static function addRules(&$form, $paymentFields) {
    foreach ($paymentFields as $paymentField => $fieldSpecs) {
      if (!empty($fieldSpecs['rules'])) {
        foreach ($fieldSpecs['rules'] as $rule) {
          $form->addRule($paymentField,
            $rule['rule_message'],
            $rule['rule_name'],
            $rule['rule_parameters']
          );
        }
      }
    }
  }

  /**
   * Validate the payment instrument values before passing it to the payment processor.
   *
   * We want this to be able to be overridden by the payment processor, and default to using
   * this object's validCreditCard for credit cards (implemented as the default in the Payment class).
   *
   * @param int $payment_processor_id
   * @param array $values
   * @param array $errors
   * @param int $billing_profile_id
   */
  public static function validatePaymentInstrument($payment_processor_id, $values, &$errors, $billing_profile_id) {
    $payment = Civi\Payment\System::singleton()->getById($payment_processor_id);
    $payment->setBillingProfile($billing_profile_id);
    $payment->validatePaymentInstrument($values, $errors);
  }

  /**
   * Set default values for the form.
   *
   * @param CRM_Core_Form $form
   * @param int $contactID
   */
  public static function setDefaultValues(&$form, $contactID) {
    $billingDefaults = $form->getProfileDefaults('Billing', $contactID);
    $form->_defaults = array_merge($form->_defaults, $billingDefaults);
    $billingLocationID = CRM_Core_BAO_LocationType::getBilling();
    // set default country & state from config if no country set
    // note the effect of this is to set the billing country to default to the site default
    // country if the person has an address but no country (for anonymous country is set above)
    // this could have implications if the billing profile is filled but hidden.
    // this behaviour has been in place for a while but the use of js to hide things has increased
    if (empty($form->_defaults["billing_country_id-{$billingLocationID}"])) {
      $form->_defaults["billing_country_id-{$billingLocationID}"] = CRM_Core_Config::singleton()->defaultContactCountry;
    }
    if (empty($form->_defaults["billing_state_province_id-{$billingLocationID}"])) {
      $form->_defaults["billing_state_province_id-{$billingLocationID}"] = CRM_Core_Config::singleton()
        ->defaultContactStateProvince;
    }
  }

  /**
   * Make sure that credit card number and cvv are valid.
   * Called within the scope of a QF formRule function
   *
   * @param array $values
   * @param array $errors
   * @param int $processorID
   */
  public static function validateCreditCard($values, &$errors, $processorID = NULL) {
    if (!empty($values['credit_card_type']) || !empty($values['credit_card_number'])) {
      if (!empty($values['credit_card_type'])) {
        $processorCards = CRM_Financial_BAO_PaymentProcessor::getCreditCards($processorID);
        if (!empty($processorCards) && !in_array($values['credit_card_type'], $processorCards)) {
          $errors['credit_card_type'] = ts('This processor does not support credit card type %1', [1 => $values['credit_card_type']]);
        }
      }
      if (!empty($values['credit_card_number']) &&
        !CRM_Utils_Rule::creditCardNumber($values['credit_card_number'], $values['credit_card_type'])
      ) {
        $errors['credit_card_number'] = ts('Please enter a valid Card Number');
      }
      if (!empty($values['cvv2']) &&
        !CRM_Utils_Rule::cvv($values['cvv2'], $values['credit_card_type'])
      ) {
        $errors['cvv2'] = ts('Please enter a valid Card Verification Number');
      }
    }
  }

  /**
   * Map address fields.
   *
   * @param null $id unused
   * @param array $src
   * @param array $dst
   * @param bool $reverse
   */
  public static function mapParams($id, $src, &$dst, $reverse = FALSE) {
    $id = CRM_Core_BAO_LocationType::getBilling();
    $map = [
      'first_name' => 'billing_first_name',
      'middle_name' => 'billing_middle_name',
      'last_name' => 'billing_last_name',
      'email' => "email-$id",
      'street_address' => "billing_street_address-$id",
      'supplemental_address_1' => "billing_supplemental_address_1-$id",
      'city' => "billing_city-$id",
      'state_province' => "billing_state_province-$id",
      'postal_code' => "billing_postal_code-$id",
      'country' => "billing_country-$id",
      'contactID' => 'contact_id',
    ];

    foreach ($map as $n => $v) {
      if (!$reverse) {
        if (isset($src[$n])) {
          $dst[$v] = $src[$n];
        }
      }
      else {
        if (isset($src[$v])) {
          $dst[$n] = $src[$v];
        }
      }
    }

    //CRM-19469 provide option for returning modified params
    return $dst;
  }

  /**
   * Get the credit card expiration month.
   * The date format for this field should typically be "M Y" (ex: Feb 2011) or "m Y" (02 2011)
   * See CRM-9017
   *
   * @param array $src
   *
   * @return int
   */
  public static function getCreditCardExpirationMonth($src) {
    $month = $src['credit_card_exp_date']['M'] ?? NULL;
    if ($month) {
      return $month;
    }

    return $src['credit_card_exp_date']['m'] ?? NULL;
  }

  /**
   * Get the credit card expiration year.
   * The date format for this field should typically be "M Y" (ex: Feb 2011) or "m Y" (02 2011)
   * This function exists only to make it consistent with getCreditCardExpirationMonth
   *
   * @param $src
   *
   * @return int
   */
  public static function getCreditCardExpirationYear($src) {
    return $src['credit_card_exp_date']['Y'] ?? NULL;
  }

  /**
   * Get the label for the processor.
   *
   * We do not use a label if there are no enterable fields.
   *
   * @param \CRM_Core_Payment $processor
   *
   * @return string
   */
  public static function getPaymentLabel($processor) {
    $isVisible = FALSE;
    $paymentTypeLabel = self::getPaymentTypeLabel($processor);
    foreach (self::getPaymentFieldMetadata(['object' => $processor]) as $paymentField) {
      if ($paymentField['htmlType'] !== 'hidden') {
        $isVisible = TRUE;
      }
    }
    return $isVisible ? $paymentTypeLabel : '';

  }

}
