<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * Class for constructing the payment processor block.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
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
   */
  static public function setPaymentFieldsByProcessor(&$form, $processor, $billing_profile_id = NULL, $isBackOffice = FALSE) {
    $form->billingFieldSets = array();
    // Load the pay-later processor
    // @todo load this right up where the other processors are loaded initially.
    if (empty($processor)) {
      $processor = CRM_Financial_BAO_PaymentProcessor::getPayment(0);
    }

    $processor['object']->setBillingProfile($billing_profile_id);
    $paymentTypeName = self::getPaymentTypeName($processor);
    $paymentTypeLabel = self::getPaymentTypeLabel($processor);
    $form->assign('paymentTypeName', $paymentTypeName);
    $form->assign('paymentTypeLabel', $paymentTypeLabel);
    $form->_paymentFields = $form->billingFieldSets[$paymentTypeName]['fields'] = self::getPaymentFieldMetadata($processor);
    $form->_paymentFields = array_merge($form->_paymentFields, self::getBillingAddressMetadata($processor, $form->_bltID));
    $form->assign('paymentFields', self::getPaymentFields($processor));
    self::setBillingAddressFields($form, $processor);
    // @todo - this may be obsolete - although potentially it could be used to re-order things in the form.
    $form->billingFieldSets['billing_name_address-group']['fields'] = array();
  }

  /**
   * Add general billing fields.
   *
   * @param CRM_Core_Form $form
   * @param CRM_Core_Payment $processor
   */
  static protected function setBillingAddressFields(&$form, $processor) {
    $billingID = $form->_bltID;
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
    $requiredPaymentFields = array();
    foreach ($paymentFields as $name => $field) {
      if (!empty($field['cc_field'])) {
        if ($field['htmlType'] == 'chainSelect') {
          $form->addChainSelect($field['name'], array('required' => FALSE));
        }
        else {
          $form->add($field['htmlType'],
            $field['name'],
            $field['title'],
            $field['attributes'],
            FALSE
          );
        }
      }
      // This will cause the fields to be marked as required - but it is up to the payment processor to
      // validate it.
      $requiredPaymentFields[$field['name']] = $field['is_required'];
    }
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
    $paymentProcessorObject = Civi\Payment\System::singleton()->getByProcessor($paymentProcessor);
    return $paymentProcessorObject->getPaymentFormFields();
  }

  /**
   * @param array $paymentProcessor
   *
   * @return array
   */
  public static function getPaymentFieldMetadata($paymentProcessor) {
    $paymentProcessorObject = Civi\Payment\System::singleton()->getByProcessor($paymentProcessor);
    return array_intersect_key($paymentProcessorObject->getPaymentFormFieldsMetadata(), array_flip(self::getPaymentFields($paymentProcessor)));
  }

  /**
   * Get the billing fields that apply to this processor.
   *
   * @param array $paymentProcessor
   * @param int $billingLocationID
   *   ID of billing location type.
   *
   * @todo sometimes things like the country alter the required fields (e.g postal code). We should possibly
   * set these before calling getPaymentFormFields (as we identify them).
   *
   * @return array
   */
  public static function getBillingAddressFields($paymentProcessor, $billingLocationID) {
    return $paymentProcessor['object']->getBillingAddressFields($billingLocationID);
  }

  /**
   * @param array $paymentProcessor
   *
   * @param int $billingLocationID
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function getBillingAddressMetadata($paymentProcessor, $billingLocationID) {
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
   * @param array $paymentProcessor
   *
   * @return string
   */
  public static function getPaymentTypeLabel($paymentProcessor) {
    $paymentProcessorObject = Civi\Payment\System::singleton()->getByProcessor($paymentProcessor);
    return ts(($paymentProcessorObject->getPaymentTypeLabel()) . ' Information');
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
   *
   * @return bool
   */
  public static function buildPaymentForm(&$form, $processor, $billing_profile_id, $isBackOffice) {
    //if the form has address fields assign to the template so the js can decide what billing fields to show
    $profileAddressFields = $form->get('profileAddressFields');
    if (!empty($profileAddressFields)) {
      $form->assign('profileAddressFields', $profileAddressFields);
    }

    if (!empty($processor['object']) && $processor['object']->buildForm($form)) {
      return NULL;
    }

    self::setPaymentFieldsByProcessor($form, $processor, $billing_profile_id, $isBackOffice);
    self::addCommonFields($form, $form->_paymentFields);
    self::addRules($form, $form->_paymentFields);
    return (!empty($form->_paymentFields));
  }

  /**
   * @param CRM_Core_Form $form
   * @param array $paymentFields
   *   Array of properties including 'object' as loaded from CRM_Financial_BAO_PaymentProcessor::getPaymentProcessors.
   * @param $paymentFields
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
   * The credit card pseudo constant results only the CC label, not the key ID
   * So we normalize the name to use it as a CSS class.
   */
  public static function getCreditCardCSSNames() {
    $creditCardTypes = array();
    foreach (CRM_Contribute_PseudoConstant::creditCard() as $key => $name) {
      // Replace anything not css-friendly by an underscore
      // Non-latin names will not like this, but so many things are wrong with
      // the credit-card type configurations already.
      $key = str_replace(' ', '', $key);
      $key = preg_replace('/[^a-zA-Z0-9]/', '_', $key);
      $key = strtolower($key);
      $creditCardTypes[$key] = $name;
    }
    return $creditCardTypes;
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

    // set default country & state from config if no country set
    // note the effect of this is to set the billing country to default to the site default
    // country if the person has an address but no country (for anonymous country is set above)
    // this could have implications if the billing profile is filled but hidden.
    // this behaviour has been in place for a while but the use of js to hide things has increased
    if (empty($form->_defaults["billing_country_id-{$form->_bltID}"])) {
      $form->_defaults["billing_country_id-{$form->_bltID}"] = CRM_Core_Config::singleton()->defaultContactCountry;
    }
    if (empty($form->_defaults["billing_state_province_id-{$form->_bltID}"])) {
      $form->_defaults["billing_state_province_id-{$form->_bltID}"] = CRM_Core_Config::singleton()
        ->defaultContactStateProvince;
    }
  }

  /**
   * Make sure that credit card number and cvv are valid.
   * Called within the scope of a QF formRule function
   *
   * @param array $values
   * @param array $errors
   */
  public static function validateCreditCard($values, &$errors) {
    if (!empty($values['credit_card_type']) || !empty($values['credit_card_number'])) {
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
   * @param int $id
   * @param array $src
   * @param array $dst
   * @param bool $reverse
   */
  public static function mapParams($id, $src, &$dst, $reverse = FALSE) {
    // Set text version of state & country if present.
    if (isset($src["billing_state_province_id-{$id}"])) {
      $src["billing_state_province-{$id}"] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($src["billing_state_province_id-{$id}"]);
    }
    if (isset($src["billing_country_id-{$id}"])) {
      $src["billing_country-{$id}"] = CRM_Core_PseudoConstant::countryIsoCode($src["billing_country_id-{$id}"]);;
    };
    $map = array(
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
    );

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
  }

  /**
   * Get the credit card expiration month.
   * The date format for this field should typically be "M Y" (ex: Feb 2011) or "m Y" (02 2011)
   * See CRM-9017
   *
   * @param $src
   *
   * @return int
   */
  public static function getCreditCardExpirationMonth($src) {
    if ($month = CRM_Utils_Array::value('M', $src['credit_card_exp_date'])) {
      return $month;
    }

    return CRM_Utils_Array::value('m', $src['credit_card_exp_date']);
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
    return CRM_Utils_Array::value('Y', $src['credit_card_exp_date']);
  }

}
