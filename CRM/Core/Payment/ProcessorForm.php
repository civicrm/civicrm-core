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

/**
 * base class for building payment block for online contribution / event pages
 */
class CRM_Core_Payment_ProcessorForm {

  /**
   * @param CRM_Core_Form $form
   * @param null $type
   * @param null $mode
   *
   * @throws Exception
   */
  public static function preProcess(&$form, $type = NULL, $mode = NULL) {
    if ($type) {
      $form->_type = $type;
    }
    else {
      $form->_type = CRM_Utils_Request::retrieve('type', 'String', $form);
    }

    if ($form->_type) {
      $form->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($form->_type, $form->_mode);
    }

    $form->set('paymentProcessor', $form->_paymentProcessor);

    // also set cancel subscription url
    if (!empty($form->_paymentProcessor['is_recur']) && !empty($form->_values['is_recur'])) {
      $form->_paymentObject = CRM_Core_Payment::singleton($mode, $form->_paymentProcessor, $form);
      $form->_values['cancelSubscriptionUrl'] = $form->_paymentObject->subscriptionURL();
    }

    //checks after setting $form->_paymentProcessor
    // we do this outside of the above conditional to avoid
    // saving the country/state list in the session (which could be huge)
    CRM_Core_Payment_Form::setPaymentFieldsByProcessor($form, $form->_paymentProcessor);

    $form->assign_by_ref('paymentProcessor', $form->_paymentProcessor);

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
      CRM_Core_Error::fatal(ts('Payment processor is not set for this page'));
    }

    if (!empty($form->_membershipBlock) && !empty($form->_membershipBlock['is_separate_payment']) &&
      (!empty($form->_paymentProcessor['class_name']) &&
        !(CRM_Utils_Array::value('billing_mode', $form->_paymentProcessor) & CRM_Core_Payment::BILLING_MODE_FORM)
      )
    ) {

      CRM_Core_Error::fatal(ts('This contribution page is configured to support separate contribution and membership payments. This %1 plugin does not currently support multiple simultaneous payments, or the option to "Execute real-time monetary transactions" is disabled. Please contact the site administrator and notify them of this error',
          array(1 => $form->_paymentProcessor['payment_processor_type'])
        )
      );
    }
  }

  /**
   * Build the payment processor form.
   *
   * @param CRM_Core_Form $form
   */
  public static function buildQuickform(&$form) {
    //@todo document why this addHidden is here
    //CRM-15743 - we should not set/create hidden element for pay later
    // because payment processor is not selected
    $processorId = $form->getVar('_paymentProcessorID');
    $isBillingAddressRequiredForPayLater = $form->_isBillingAddressRequiredForPayLater;
    if (!empty($processorId)) {
      $isBillingAddressRequiredForPayLater = FALSE;
      $form->addElement('hidden', 'hidden_processor', 1);
    }
    CRM_Core_Payment_Form::buildPaymentForm($form, $form->_paymentProcessor, empty($isBillingAddressRequiredForPayLater), FALSE);
  }

}
