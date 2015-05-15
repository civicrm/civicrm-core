<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Core_Payment_Form {


  /**
   * Add payment fields depending on payment processor. The payment processor can implement the following functions to override the built in fields.
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
   * @param bool $forceBillingFieldsForPayLater
   *   Display billing fields even for pay later.
   */
  static public function setPaymentFieldsByProcessor(&$form, $processor, $forceBillingFieldsForPayLater = FALSE) {
    $form->billingFieldSets = array();
    if ($processor != NULL) {
      // ie it is pay later
      $paymentFields = self::getPaymentFields($processor);
      $paymentTypeName = self::getPaymentTypeName($processor);
      $paymentTypeLabel = self::getPaymentTypeLabel($processor);
      //@todo if we switch to iterating through $form->billingFieldSets we won't need to assign these directly
      $form->assign('paymentTypeName', $paymentTypeName);
      $form->assign('paymentTypeLabel', $paymentTypeLabel);

      $form->billingFieldSets[$paymentTypeName]['fields'] = $form->_paymentFields = array_intersect_key(self::getPaymentFieldMetadata($processor), array_flip($paymentFields));
      $form->billingPane = array($paymentTypeName => $paymentTypeLabel);
      $form->assign('paymentFields', $paymentFields);
    }

    // @todo - replace this section with one similar to above per discussion - probably use a manual processor shell class to stand in for that capability
    //return without adding billing fields if billing_mode = 4 (@todo - more the ability to set that to the payment processor)
    // or payment processor is NULL (pay later)
    if (($processor == NULL && !$forceBillingFieldsForPayLater) || CRM_Utils_Array::value('billing_mode', $processor) == 4) {
      return;
    }
    //@todo setPaymentFields defines the billing fields - this should be moved to the processor class & renamed getBillingFields
    // potentially pay later would also be a payment processor
    //also set the billingFieldSet to hold all the details required to render the fieldset so we can iterate through the fieldset - making
    // it easier to re-order in hooks etc. The billingFieldSets param is used to determine whether to show the billing pane
    CRM_Core_Payment_Form::setBillingDetailsFields($form);
    $form->billingFieldSets['billing_name_address-group']['fields'] = array();
  }

  /**
   * Add general billing fields.
   * @todo set these like processor fields & let payment processors alter them
   *
   * @param CRM_Core_Form $form
   *
   * @return void
   */
  static protected function setBillingDetailsFields(&$form) {
    $bltID = $form->_bltID;

    $form->_paymentFields['billing_first_name'] = array(
      'htmlType' => 'text',
      'name' => 'billing_first_name',
      'title' => ts('Billing First Name'),
      'cc_field' => TRUE,
      'attributes' => array('size' => 30, 'maxlength' => 60, 'autocomplete' => 'off'),
      'is_required' => TRUE,
    );

    $form->_paymentFields['billing_middle_name'] = array(
      'htmlType' => 'text',
      'name' => 'billing_middle_name',
      'title' => ts('Billing Middle Name'),
      'cc_field' => TRUE,
      'attributes' => array('size' => 30, 'maxlength' => 60, 'autocomplete' => 'off'),
      'is_required' => FALSE,
    );

    $form->_paymentFields['billing_last_name'] = array(
      'htmlType' => 'text',
      'name' => 'billing_last_name',
      'title' => ts('Billing Last Name'),
      'cc_field' => TRUE,
      'attributes' => array('size' => 30, 'maxlength' => 60, 'autocomplete' => 'off'),
      'is_required' => TRUE,
    );

    $form->_paymentFields["billing_street_address-{$bltID}"] = array(
      'htmlType' => 'text',
      'name' => "billing_street_address-{$bltID}",
      'title' => ts('Street Address'),
      'cc_field' => TRUE,
      'attributes' => array('size' => 30, 'maxlength' => 60, 'autocomplete' => 'off'),
      'is_required' => TRUE,
    );

    $form->_paymentFields["billing_city-{$bltID}"] = array(
      'htmlType' => 'text',
      'name' => "billing_city-{$bltID}",
      'title' => ts('City'),
      'cc_field' => TRUE,
      'attributes' => array('size' => 30, 'maxlength' => 60, 'autocomplete' => 'off'),
      'is_required' => TRUE,
    );

    $form->_paymentFields["billing_state_province_id-{$bltID}"] = array(
      'htmlType' => 'chainSelect',
      'title' => ts('State/Province'),
      'name' => "billing_state_province_id-{$bltID}",
      'cc_field' => TRUE,
      'is_required' => TRUE,
    );

    $form->_paymentFields["billing_postal_code-{$bltID}"] = array(
      'htmlType' => 'text',
      'name' => "billing_postal_code-{$bltID}",
      'title' => ts('Postal Code'),
      'cc_field' => TRUE,
      'attributes' => array('size' => 30, 'maxlength' => 60, 'autocomplete' => 'off'),
      'is_required' => TRUE,
    );

    $form->_paymentFields["billing_country_id-{$bltID}"] = array(
      'htmlType' => 'select',
      'name' => "billing_country_id-{$bltID}",
      'title' => ts('Country'),
      'cc_field' => TRUE,
      'attributes' => array(
        '' => ts('- select -'),
      ) +
      CRM_Core_PseudoConstant::country(),
      'is_required' => TRUE,
    );
    //CRM-15509 working towards giving control over billing fields to payment processors. For now removing tpl hard-coding
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('billingDetailsFields', array(
      'billing_first_name',
      'billing_middle_name',
      'billing_last_name',
      "billing_street_address-{$bltID}",
      "billing_city-{$bltID}",
      "billing_country_id-{$bltID}",
      "billing_state_province_id-{$bltID}",
      "billing_postal_code-{$bltID}",
    ));
  }

  /**
   * @param CRM_Core_Form $form
   * @param bool $useRequired
   * @param array $paymentFields
   */
  protected static function addCommonFields(&$form, $useRequired, $paymentFields) {
    foreach ($paymentFields as $name => $field) {
      if (!empty($field['cc_field'])) {
        if ($field['htmlType'] == 'chainSelect') {
          $form->addChainSelect($field['name'], array('required' => $useRequired && $field['is_required']));
        }
        else {
          $form->add($field['htmlType'],
            $field['name'],
            $field['title'],
            $field['attributes'],
            $useRequired ? $field['is_required'] : FALSE
          );
        }
      }
    }
  }

  /**
   * @param array $paymentProcessor
   * @todo it will be necessary to set details that affect it - mostly likely take Country as a param. Should we add generic
   * setParams on processor class or just setCountry which we know we need?
   *
   * @return array
   */
  public static function getPaymentFields($paymentProcessor) {
    $paymentProcessorObject = CRM_Core_Payment::singleton(($paymentProcessor['is_test'] ? 'test' : 'live'), $paymentProcessor);
    return $paymentProcessorObject->getPaymentFormFields();
  }

  /**
   * @param array $paymentProcessor
   *
   * @return array
   */
  public static function getPaymentFieldMetadata($paymentProcessor) {
    $paymentProcessorObject = CRM_Core_Payment::singleton(($paymentProcessor['is_test'] ? 'test' : 'live'), $paymentProcessor);
    return $paymentProcessorObject->getPaymentFormFieldsMetadata();
  }

  /**
   * @param array $paymentProcessor
   *
   * @return string
   */
  public static function getPaymentTypeName($paymentProcessor) {
    $paymentProcessorObject = CRM_Core_Payment::singleton(($paymentProcessor['is_test'] ? 'test' : 'live'), $paymentProcessor);
    return $paymentProcessorObject->getPaymentTypeName();
  }

  /**
   * @param array $paymentProcessor
   *
   * @return string
   */
  public static function getPaymentTypeLabel($paymentProcessor) {
    $paymentProcessorObject = CRM_Core_Payment::singleton(($paymentProcessor['is_test'] ? 'test' : 'live'), $paymentProcessor);
    return ts(($paymentProcessorObject->getPaymentTypeLabel()) . ' Information');
  }

  /**
   * @param CRM_Contribute_Form_AbstractEditPayment|CRM_Contribute_Form_Contribution_Main|CRM_Core_Payment_ProcessorForm|CRM_Contribute_Form_UpdateBilling $form
   * @param array $processor
   *   Array of properties including 'object' as loaded from CRM_Financial_BAO_PaymentProcessor::getPaymentProcessors.
   * @param bool $isBillingDataOptional
   *   This manifests for 'NULL' (pay later) payment processor as the addition of billing fields to the form and.
   *   for payment processors that gather payment data on site as rendering the fields as not being required. (not entirely sure why but this
   *   is implemented for back office forms)
   *
   * @return bool
   */
  public static function buildPaymentForm(&$form, $processor, $isBillingDataOptional) {
    //if the form has address fields assign to the template so the js can decide what billing fields to show
    $profileAddressFields = $form->get('profileAddressFields');
    if (!empty($profileAddressFields)) {
      $form->assign('profileAddressFields', $profileAddressFields);
    }

    // $processor->buildForm appears to be an undocumented (possibly unused) option for payment processors
    // which was previously available only in some form flows
    if (!empty($form->_paymentProcessor) && !empty($form->_paymentProcessor['object']) && $form->_paymentProcessor['object']->isSupported('buildForm')) {
      $form->_paymentProcessor['object']->buildForm($form);
      return NULL;
    }

    self::setPaymentFieldsByProcessor($form, $processor, empty($isBillingDataOptional));
    self::addCommonFields($form, !$isBillingDataOptional, $form->_paymentFields);
    self::addRules($form, $form->_paymentFields);
    self::addPaypalExpressCode($form);
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
   * Billing mode button is basically synonymous with paypal express  - this is probably a good example of 'odds & sods' code we
   * need to find a way for the payment processor to assign. A tricky aspect is that the payment processor may need to set the order
   *
   * @param $form
   */
  protected static function addPaypalExpressCode(&$form) {
    if (empty($form->isBackOffice)) {
      if (CRM_Utils_Array::value('billing_mode', $form->_paymentProcessor) == 3
      ) {
        $form->_expressButtonName = $form->getButtonName('upload', 'express');
        $form->assign('expressButtonName', $form->_expressButtonName);
        $form->add('image',
          $form->_expressButtonName,
          $form->_paymentProcessor['url_button'],
          array('class' => 'crm-form-submit')
        );
      }
    }
  }

  /**
   * Validate the payment instrument values before passing it to the payment processor
   * We want this to be overrideable by the payment processor, and default to using
   * this object's validCreditCard for credit cards (implemented as the default in the Payment class).
   */
  public static function validatePaymentInstrument($payment_processor_id, $values, &$errors, $form) {
    // ignore if we don't have a payment instrument to validate (e.g. backend payments)
    if ($payment_processor_id > 0) {
      $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($payment_processor_id, 'live');
      $payment = CRM_Core_Payment::singleton('live', $paymentProcessor, $form);
      $payment->validatePaymentInstrument($values, $errors);
    }
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
    static $map = NULL;
    if (!$map) {
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
    }

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
