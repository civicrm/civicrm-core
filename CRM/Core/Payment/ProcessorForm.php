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

use Civi\Payment\System;
/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 */


/**
 * Base class for building payment block for online contribution / event pages.
 */
class CRM_Core_Payment_ProcessorForm {

  /**
   * @param CRM_Contribute_Form_Contribution_Main|CRM_Event_Form_Registration_Register|CRM_Financial_Form_Payment $form
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

    if (empty($form->_paymentProcessor)) {
      // This would happen when hitting the back-button on a multi-page form with a $0 selection in play.
      return;
    }
    $form->set('paymentProcessor', $form->_paymentProcessor);
    $form->_paymentObject = System::singleton()->getByProcessor($form->_paymentProcessor);

    $form->assign('suppressSubmitButton', $form->_paymentObject->isSuppressSubmitButtons());

    // also set cancel subscription url
    if (!empty($form->_paymentProcessor['is_recur']) && !empty($form->_values['is_recur'])) {
      $form->_values['cancelSubscriptionUrl'] = $form->_paymentObject->subscriptionURL(NULL, NULL, 'cancel');
    }

    //checks after setting $form->_paymentProcessor
    // we do this outside of the above conditional to avoid
    // saving the country/state list in the session (which could be huge)
    CRM_Core_Payment_Form::setPaymentFieldsByProcessor(
      $form,
      $form->_paymentProcessor,
      CRM_Utils_Request::retrieve('billing_profile_id', 'String')
    );

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
        !$form->_paymentObject->supports('MultipleConcurrentPayments')
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
    $billing_profile_id = CRM_Utils_Request::retrieve('billing_profile_id', 'String');
    if (!empty($form->_values) && !empty($form->_values['is_billing_required'])) {
      $billing_profile_id = 'billing';
    }
    if (!empty($processorId)) {
      $form->addElement('hidden', 'hidden_processor', 1);
    }
    CRM_Core_Payment_Form::buildPaymentForm($form, $form->_paymentProcessor, $billing_profile_id, FALSE);
  }

}
