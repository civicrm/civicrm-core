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

use Civi\Payment\PropertyBag;
use Civi\Payment\Exception\PaymentProcessorException;

/**
 * This class provides support for canceling recurring subscriptions.
 */
class CRM_Contribute_Form_CancelSubscription extends CRM_Contribute_Form_ContributionRecur {

  protected $_userContext;

  protected $_mode;

  /**
   * The contributor email
   *
   * @var string
   */
  protected $_donorEmail = '';

  /**
   * The contributor display name (for emails)
   *
   * @var string
   */
  protected $_donorDisplayName = '';

  /**
   * Should custom data be suppressed on this form.
   *
   * We override to suppress custom data because historically it has not been
   * shown on this form & we don't want to expose it as a by-product of
   * other change without establishing that it would be good on this form.
   *
   * @return bool
   */
  protected function isSuppressCustomData() {
    return TRUE;
  }

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    parent::preProcess();

    $cancelRecurTextParams = [
      'mode' => $this->_mode,
      'amount' => $this->getSubscriptionDetails()->amount,
      'currency' => $this->getSubscriptionDetails()->currency,
      'frequency_interval' => $this->getSubscriptionDetails()->frequency_interval,
      'frequency_unit' => $this->getSubscriptionDetails()->frequency_unit,
      'installments' => $this->getSubscriptionDetails()->installments,
      'selfService' => $this->isSelfService(),
    ];

    if ($this->getMembershipID()) {
      $this->_mode = 'auto_renew';
      // CRM-18468: crid is more accurate than mid for getting
      // subscriptionDetails, so don't get them again.

      $membershipTypes = CRM_Member_PseudoConstant::membershipType();
      $membershipTypeId = $this->getMembershipValue('membership_type_id');
      $membershipType = $membershipTypes[$membershipTypeId] ?? '';
      $this->assign('membershipType', $membershipType);
      $cancelRecurTextParams['membershipType'] = $membershipType;
    }

    if ($this->_coid) {
      if (CRM_Contribute_BAO_Contribution::isSubscriptionCancelled($this->_coid)) {
        CRM_Core_Error::statusBounce(ts('The recurring contribution looks to have been cancelled already.'));
      }
      $this->_paymentProcessorObj = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($this->_coid, 'contribute', 'obj');
    }

    if (!$this->getSubscriptionDetails()) {
      CRM_Core_Error::statusBounce(ts('Required information missing.'));
    }

    $this->assign('cancelRecurDetailText', $this->_paymentProcessorObj->getText('cancelRecurDetailText', $cancelRecurTextParams));

    // handle context redirection
    CRM_Contribute_BAO_ContributionRecur::setSubscriptionContext();

    $this->setTitle($this->getMembershipID() ? ts('Cancel Auto-renewal') : ts('Cancel Recurring Contribution'));
    $this->assign('mode', $this->_mode);

    if ($this->isSelfService() || !$this->_paymentProcessorObj->supports('cancelRecurring')) {
      // If we are self service (contact is cancelling for themselves via a cancel link)
      // or the processor does not support cancellation then remove the fields
      // specifying whether to notify the processor.
      unset($this->entityFields['send_cancel_request']);
    }
    if ($this->isSelfService()) {
      // Arguably the is_notify field should be removed in self-service mode.
      // Historically this has been the case...
      unset($this->entityFields['is_notify']);
    }

    if ($this->getSubscriptionDetails()->contact_id) {
      list($this->_donorDisplayName, $this->_donorEmail)
        = CRM_Contact_BAO_Contact::getContactDetails($this->getSubscriptionDetails()->contact_id);
    }
  }

  /**
   * Set entity fields for this cancellation.
   */
  public function setEntityFields() {
    $this->entityFields = [
      'cancel_reason' => ['name' => 'cancel_reason'],
    ];
    $this->entityFields['send_cancel_request'] = [
      'title' => ts('Send cancellation request?'),
      'name' => 'send_cancel_request',
      'not-auto-addable' => TRUE,
    ];
    $this->entityFields['is_notify'] = [
      'title' => ts('Notify Contributor?'),
      'name' => 'is_notify',
      'not-auto-addable' => TRUE,
    ];
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->buildQuickEntityForm();
    // Determine if we can cancel recurring contribution via API with this processor
    if ($this->_paymentProcessorObj->supports('CancelRecurringNotifyOptional')) {
      $this->addRadio('send_cancel_request', ts('Send cancellation request to %1 ?', [1 => $this->_paymentProcessorObj->getTitle()]), [ts('No'), ts('Yes')]);
    }
    else {
      $this->assign('cancelRecurNotSupportedText', $this->_paymentProcessorObj->getText('cancelRecurNotSupportedText', []));
    }

    if (!empty($this->_donorEmail)) {
      $this->add('checkbox', 'is_notify', ts('Notify Contributor?') . " ({$this->_donorEmail})");
    }
    if ($this->getMembershipID()) {
      $cancelButton = ts('Cancel Automatic Membership Renewal');
    }
    else {
      $cancelButton = ts('Cancel Recurring Contribution');
    }

    $type = 'next';
    if ($this->isSelfService()) {
      $type = 'submit';
    }

    $this->addButtons([
      [
        'type' => $type,
        'name' => $cancelButton,
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Not Now'),
      ],
    ]);
  }

  /**
   * Set default values for the form.
   *
   * @return array
   *   array of default values
   */
  public function setDefaultValues() {
    return [
      'send_cancel_request' => 1,
    ];
  }

  /**
   * Process the form submission.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    $message = NULL;
    $params = $this->controller->exportValues($this->_name);

    if ($this->isSelfService()) {
      // for self service force sending-request & notify
      if ($this->_paymentProcessorObj->supports('cancelRecurring')) {
        $params['send_cancel_request'] = 1;
      }

      if (!empty($this->_donorEmail)) {
        $params['is_notify'] = 1;
      }
    }

    try {
      $propertyBag = new PropertyBag();
      if (isset($params['send_cancel_request'])) {
        $propertyBag->setIsNotifyProcessorOnCancelRecur(!empty($params['send_cancel_request']));
      }
      $propertyBag->setContributionRecurID($this->getSubscriptionDetails()->recur_id);
      $propertyBag->setRecurProcessorID($this->getSubscriptionDetails()->processor_id);
      $message = $this->_paymentProcessorObj->doCancelRecurring($propertyBag)['message'];
    }
    catch (PaymentProcessorException $e) {
      CRM_Core_Error::statusBounce($e->getMessage());
    }

    try {
      civicrm_api3('ContributionRecur', 'cancel', [
        'id' => $this->getSubscriptionDetails()->recur_id,
        'membership_id' => $this->getMembershipID(),
        'processor_message' => $message,
        'cancel_reason' => $this->getSubmittedValue('cancel_reason'),
      ]);

      $tplParams = [];
      if ($this->getMembershipID()) {
        $inputParams = ['id' => $this->getMembershipID()];
        CRM_Member_BAO_Membership::getValues($inputParams, $tplParams);
        $tplParams = $tplParams[$this->getMembershipID()];
        $tplParams['membership_status']
          = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipStatus', $tplParams['status_id']);
        $tplParams['membershipType']
          = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $tplParams['membership_type_id']);
        $status = ts('The automatic renewal of your %1 membership has been cancelled as requested. This does not affect the status of your membership - you will receive a separate notification when your membership is up for renewal.', [1 => $tplParams['membershipType']]);
        $msgTitle = 'Membership Renewal Cancelled';
        $msgType = 'info';
      }
      else {
        $status = ts('The recurring contribution of %1, every %2 %3 has been cancelled.',
          [
            1 => CRM_Utils_Money::format($this->getSubscriptionDetails()->amount, $this->getSubscriptionDetails()->currency),
            2 => $this->getSubscriptionDetails()->frequency_interval,
            3 => $this->getSubscriptionDetails()->frequency_unit,
          ]
        );
        $msgTitle = 'Contribution Cancelled';
        $msgType = 'success';
      }

      if (($params['is_notify'] ?? NULL) == 1) {
        // send notification
        $sendTemplateParams
          = [
            'groupName' => $this->_mode == 'auto_renew' ? 'msg_tpl_workflow_membership' : 'msg_tpl_workflow_contribution',
            'workflow' => $this->_mode == 'auto_renew' ? 'membership_autorenew_cancelled' : 'contribution_recurring_cancelled',
            'contactId' => $this->getSubscriptionDetails()->contact_id,
            'tplParams' => $tplParams,
            'tokenContext' => ['contribution_recurId' => $this->getContributionRecurID()],
            //'isTest'    => $isTest, set this from _objects
            'PDFFilename' => 'receipt.pdf',
            'from' => CRM_Contribute_BAO_ContributionRecur::getRecurFromAddress($this->getContributionRecurID()),
            'toName' => $this->_donorDisplayName,
            'toEmail' => $this->_donorEmail,
          ];
        list($sent) = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
      }
    }
    catch (CRM_Core_Exception $e) {
      $msgType = 'error';
      $msgTitle = ts('Error');
      if ($params['send_cancel_request'] == 1) {
        $status = ts('Recurring contribution was cancelled successfully by the processor, but could not be marked as cancelled in the database.');
      }
      else {
        $status = ts('Recurring contribution could not be cancelled in the database.');
      }
    }

    $userID = CRM_Core_Session::getLoggedInContactID();
    if ($userID && $status) {
      CRM_Core_Session::singleton()->setStatus($status, $msgTitle, $msgType);
    }
    elseif (!$userID) {
      if ($status) {
        CRM_Utils_System::setUFMessage($status);
        // keep result as 1, since we not displaying anything on the redirected page anyway
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute/subscriptionstatus',
          'reset=1&task=cancel&result=1'));
      }
    }
  }

}
