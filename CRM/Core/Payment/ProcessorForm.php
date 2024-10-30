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

use Civi\Payment\System;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


/**
 * Base class for building payment block for online contribution / event pages.
 */
class CRM_Core_Payment_ProcessorForm {

  /**
   * @param CRM_Contribute_Form_Contribution_Main|CRM_Event_Form_Registration_Register|CRM_Financial_Form_Payment $form
   *
   * @throws Exception
   */
  public static function preProcess($form) {
    $type = CRM_Utils_Request::retrieve('type', 'String', $form);
    if ($type) {
      // @todo not sure when this would be true. Never passed in.
      $form->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($type, $form->_mode);
    }

    if (empty($form->_paymentProcessor)) {
      // This would happen when hitting the back-button on a multi-page form with a $0 selection in play.
      return;
    }
    $form->set('paymentProcessor', $form->_paymentProcessor);
    $paymentObject = System::singleton()->getByProcessor($form->_paymentProcessor);
    if ($form->paymentInstrumentID) {
      $paymentObject->setPaymentInstrumentID($form->paymentInstrumentID);
    }
    $paymentObject->setBackOffice($form->isBackOffice);
    $form->assign('isBackOffice', $form->isBackOffice);

    $form->assign('suppressSubmitButton', $paymentObject->isSuppressSubmitButtons());

    CRM_Financial_Form_Payment::addCreditCardJs($form->getPaymentProcessorID());
    $form->assign('paymentProcessorID', $form->getPaymentProcessorID());

    $form->assign('currency', $form->getCurrency());

    $form->assign('paymentAgreementTitle', $form->_paymentProcessor['object']->getText('agreementTitle', []));
    $form->assign('paymentAgreementText', $form->_paymentProcessor['object']->getText('agreementText', []));

    // also set cancel subscription url
    if (!empty($form->_paymentProcessor['is_recur']) && !empty($form->_values['is_recur'])) {
      $form->_values['cancelSubscriptionUrl'] = $paymentObject->subscriptionURL(NULL, NULL, 'cancel');
    }

    $paymentProcessorBillingFields = array_keys($form->_paymentProcessor['object']->getBillingAddressFields());

    if (!empty($form->_values['custom_pre_id'])) {
      $profileAddressFields = [];
      $fields = CRM_Core_BAO_UFGroup::getFields($form->_values['custom_pre_id'], FALSE, CRM_Core_Action::ADD, NULL, NULL, FALSE,
        NULL, FALSE, NULL, CRM_Core_Permission::CREATE, NULL);

      foreach ((array) $fields as $key => $value) {
        CRM_Core_BAO_UFField::assignAddressField($key, $profileAddressFields, ['uf_group_id' => $form->_values['custom_pre_id']], $paymentProcessorBillingFields);
      }
      if (count($profileAddressFields)) {
        $form->set('profileAddressFields', $profileAddressFields);
      }
    }

    //checks after setting $form->_paymentProcessor
    // we do this outside of the above conditional to avoid
    // saving the country/state list in the session (which could be huge)
    CRM_Core_Payment_Form::setPaymentFieldsByProcessor(
      $form,
      $form->_paymentProcessor,
      CRM_Utils_Request::retrieve('billing_profile_id', 'String'),
      $form->isBackOffice,
      $form->paymentInstrumentID
    );

    $form->assign('paymentProcessor', $form->_paymentProcessor);

    // check if this is a paypal auto return and redirect accordingly
    //@todo - determine if this is legacy and remove
    if (CRM_Core_Payment::paypalRedirect($form->_paymentProcessor)) {
      $url = CRM_Utils_System::url('civicrm/contribute/transact',
        "_qf_ThankYou_display=1&qfKey={$form->controller->_key}"
      );
      CRM_Utils_System::redirect($url);
    }

    // make sure we have a valid payment class, else abort
    if (!empty($form->_values['is_monetary']) &&
      !$form->_paymentProcessor['class_name'] && empty($form->_values['is_pay_later'])
    ) {
      CRM_Core_Error::statusBounce(ts('Payment processor is not set for this page'));
    }

    if (!empty($form->_membershipBlock) && !empty($form->_membershipBlock['is_separate_payment']) &&
      (!empty($form->_paymentProcessor['class_name']) &&
        !$paymentObject->supports('MultipleConcurrentPayments')
      )
    ) {

      CRM_Core_Error::statusBounce(ts('This contribution page is configured to support separate contribution and membership payments. This %1 plugin does not currently support multiple simultaneous payments, or the option to "Execute real-time monetary transactions" is disabled. Please contact the site administrator and notify them of this error',
          [1 => $form->_paymentProcessor['payment_processor_type']]
        )
      );
    }
  }

  /**
   * Build the payment processor form.
   *
   * @param \CRM_Event_Form_Registration_Register|\CRM_Contribute_Form_Contribution_Main|CRM_Event_Form_Registration_Confirm|CRM_Financial_Form_Payment $form
   */
  public static function buildQuickForm($form): void {
    //@todo document why this addHidden is here
    //CRM-15743 - we should not set/create hidden element for pay later
    // because payment processor is not selected
    $processorId = $form->getPaymentProcessorID();
    $billing_profile_id = CRM_Utils_Request::retrieve('billing_profile_id', 'String');
    if (!empty($form->_values) && !empty($form->_values['is_billing_required'])) {
      $billing_profile_id = 'billing';
    }
    if (!empty($processorId)) {
      $form->addElement('hidden', 'hidden_processor', 1);
    }
    CRM_Core_Payment_Form::buildPaymentForm($form, $form->_paymentProcessor, $billing_profile_id, $form->isBackOffice, $form->paymentInstrumentID ?? NULL);
  }

}
