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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\Payment\Exception\PaymentProcessorException;

/**
 * This class generates form components generic to recurring contributions.
 *
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 */
class CRM_Contribute_Form_UpdateSubscription extends CRM_Contribute_Form_ContributionRecur {

  public $_paymentProcessor = NULL;

  public $_paymentProcessorObj = NULL;

  /**
   * Fields that affect the schedule and are defined as editable by the processor.
   *
   * @var array
   */
  protected $editableScheduleFields = [];

  /**
   * The id of the contact associated with this recurring contribution.
   *
   * @var int
   */
  public $_contactID;

  /**
   * The contributor email
   *
   * @var string
   */
  protected $_donorEmail = '';

  /**
   * Pre-processing for the form.
   *
   * @throws \Exception
   */
  public function preProcess() {

    parent::preProcess();
    $this->setAction(CRM_Core_Action::UPDATE);

    if ($this->_coid) {
      $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($this->_coid, 'contribute', 'info');
      // @todo test & replace with $this->_paymentProcessorObj =  Civi\Payment\System::singleton()->getById($this->_paymentProcessor['id']);
      $this->_paymentProcessorObj = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($this->_coid, 'contribute', 'obj');
      $this->contributionRecurID = $this->_subscriptionDetails->recur_id;
    }
    elseif ($this->contributionRecurID) {
      $this->_coid = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $this->contributionRecurID, 'id', 'contribution_recur_id');
    }

    if (!$this->contributionRecurID || !$this->_subscriptionDetails) {
      CRM_Core_Error::statusBounce(ts('Required information missing.'));
    }

    if ($this->_subscriptionDetails->membership_id && $this->_subscriptionDetails->auto_renew) {
      // Add Membership details to form
      $membership = civicrm_api3('Membership', 'get', [
        'contribution_recur_id' => $this->contributionRecurID,
      ]);
      if (!empty($membership['count'])) {
        $membershipDetails = reset($membership['values']);
        $values['membership_id'] = $membershipDetails['id'];
        $values['membership_name'] = $membershipDetails['membership_name'];
      }
      $this->assign('recurMembership', $values);
      $this->assign('contactId', $this->_subscriptionDetails->contact_id);
    }

    $this->assign('self_service', $this->isSelfService());
    $this->assign('recur_frequency_interval', $this->_subscriptionDetails->frequency_interval);
    $this->assign('recur_frequency_unit', $this->_subscriptionDetails->frequency_unit);

    $this->editableScheduleFields = $this->_paymentProcessorObj->getEditableRecurringScheduleFields();

    $changeHelpText = $this->_paymentProcessorObj->getRecurringScheduleUpdateHelpText();
    if (!in_array('amount', $this->editableScheduleFields)) {
      // Not sure if this is good behaviour - maintaining this existing behaviour for now.
      CRM_Core_Session::setStatus($changeHelpText, ts('Warning'), 'alert');
    }
    else {
      $this->assign('changeHelpText', $changeHelpText);
    }
    $alreadyHardCodedFields = ['amount', 'installments'];
    foreach ($this->editableScheduleFields as $editableScheduleField) {
      if (!in_array($editableScheduleField, $alreadyHardCodedFields)) {
        $this->addField($editableScheduleField, ['entity' => 'ContributionRecur'], FALSE, FALSE);
      }
    }

    // when custom data is included in this page
    if (!empty($_POST['hidden_custom']) && !$this->isSelfService()) {
      CRM_Custom_Form_CustomData::preProcess($this, NULL, NULL, 1, 'ContributionRecur', $this->contributionRecurID);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
    }

    $this->assign('editableScheduleFields', array_diff($this->editableScheduleFields, $alreadyHardCodedFields));

    if ($this->_subscriptionDetails->contact_id) {
      $contactDetails = CRM_Contact_BAO_Contact::getContactDetails($this->_subscriptionDetails->contact_id);
      $this->_donorEmail = $contactDetails[1];
    }

    $this->setTitle(ts('Update Recurring Contribution'));

    // Handle context redirection.
    CRM_Contribute_BAO_ContributionRecur::setSubscriptionContext();
  }

  /**
   * Set default values for the form.
   *
   * Note that in edit/view mode the default values are retrieved from the database.
   */
  public function setDefaultValues() {
    $this->_defaults = [];
    $this->_defaults['amount'] = $this->_subscriptionDetails->amount;
    $this->_defaults['installments'] = $this->_subscriptionDetails->installments;
    $this->_defaults['campaign_id'] = $this->_subscriptionDetails->campaign_id;
    $this->_defaults['financial_type_id'] = $this->_subscriptionDetails->financial_type_id;
    foreach ($this->editableScheduleFields as $field) {
      $this->_defaults[$field] = $this->_subscriptionDetails->$field ?? NULL;
    }

    return $this->_defaults;
  }

  /**
   * Actually build the components of the form.
   */
  public function buildQuickForm() {
    // CRM-16398: If current recurring contribution got > 1 lineitems then make amount field readonly
    $amtAttr = ['size' => 20];
    $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($this->_coid);
    if (count($lineItems) > 1) {
      $amtAttr += ['readonly' => TRUE];
    }
    $amountField = $this->addMoney('amount', ts('Recurring Contribution Amount'), TRUE, $amtAttr,
      TRUE, 'currency', $this->_subscriptionDetails->currency, TRUE
    );

    // https://lab.civicrm.org/dev/financial/-/issues/197 https://github.com/civicrm/civicrm-core/pull/23796
    // Revert freezing on total_amount field on recurring form - particularly affects IATs
    // This will need revisiting in the future as updating amount on recur does not work for multiple lineitems.
    // Also there are "point of truth" issues ie. is the amount on template contribution or recur the current one?
    // The amount on the recurring contribution should not be updated directly. If we update the amount using a template contribution the recurring contribution
    //   will be updated automatically.
    // $paymentProcessorObj = Civi\Payment\System::singleton()->getById(CRM_Contribute_BAO_ContributionRecur::getPaymentProcessorID($this->contributionRecurID));
    // $templateContribution = CRM_Contribute_BAO_ContributionRecur::getTemplateContribution($this->contributionRecurID);
    // if (!empty($templateContribution['id']) && $paymentProcessorObj->supportsEditRecurringContribution()) {
    //   $amountField->freeze();
    // }

    $this->add('text', 'installments', ts('Number of Installments'), ['size' => 20], FALSE);

    if ($this->_donorEmail) {
      $this->add('checkbox', 'is_notify', ts('Notify Contributor?'));
    }

    if (CRM_Core_Permission::check('edit contributions')) {
      CRM_Campaign_BAO_Campaign::addCampaign($this, $this->_subscriptionDetails->campaign_id);
    }

    if (CRM_Contribute_BAO_ContributionRecur::supportsFinancialTypeChange($this->contributionRecurID)) {
      $this->addEntityRef('financial_type_id', ts('Financial Type'), ['entity' => 'FinancialType'], !$this->isSelfService());
    }

    // Add custom data
    $this->assign('customDataType', 'ContributionRecur');
    $this->assign('entityID', $this->contributionRecurID);

    $type = 'next';
    if ($this->isSelfService()) {
      $type = 'submit';
    }

    // define the buttons
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
  }

  /**
   * Called after the user submits the form.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->exportValues();

    if ($this->isSelfService() && $this->_donorEmail) {
      // for self service force notify
      $params['is_notify'] = 1;
    }

    // if this is an update of an existing recurring contribution, pass the ID
    $params['contributionRecurID'] = $params['id'] = $this->getContributionRecurID();
    $message = '';

    $params['recurProcessorID'] = $params['subscriptionId'] = $this->getSubscriptionDetails()->processor_id;

    $updateSubscription = TRUE;
    if ($this->_paymentProcessorObj->supports('changeSubscriptionAmount')) {
      try {
        $updateSubscription = $this->_paymentProcessorObj->changeSubscriptionAmount($message, $params);
        if ($updateSubscription instanceof CRM_Core_Error) {
          CRM_Core_Error::deprecatedWarning('An exception should be thrown');
          throw new PaymentProcessorException(ts('Could not update the Recurring contribution details'));
        }
      }
      catch (PaymentProcessorException $e) {
        CRM_Core_Error::statusBounce($e->getMessage());
      }
    }
    if ($updateSubscription) {
      // Handle custom data
      $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params, $this->contributionRecurID, 'ContributionRecur');
      // save the changes
      CRM_Contribute_BAO_ContributionRecur::add($params);
      $status = ts('Recurring contribution has been updated to: %1, every %2 %3(s) for %4 installments.',
        [
          1 => CRM_Utils_Money::format($params['amount'], $this->_subscriptionDetails->currency),
          2 => $this->_subscriptionDetails->frequency_interval,
          3 => $this->_subscriptionDetails->frequency_unit,
          4 => $params['installments'],
        ]
      );

      $msgTitle = ts('Update Success');
      $msgType = 'success';
      $msg = ts('Recurring Contribution Updated');
      $contactID = $this->_subscriptionDetails->contact_id;

      if ($this->_subscriptionDetails->amount != $params['amount']) {
        $message .= "<br /> " . ts("Recurring contribution amount has been updated from %1 to %2 for this subscription.",
            [
              1 => CRM_Utils_Money::format($this->_subscriptionDetails->amount, $this->_subscriptionDetails->currency),
              2 => CRM_Utils_Money::format($params['amount'], $this->_subscriptionDetails->currency),
            ]) . ' ';
        if ($this->_subscriptionDetails->amount < $params['amount']) {
          $msg = ts('Recurring Contribution Updated - increased installment amount');
        }
        else {
          $msg = ts('Recurring Contribution Updated - decreased installment amount');
        }
      }

      if ($this->_subscriptionDetails->installments != $params['installments']) {
        $message .= "<br /> " . ts("Recurring contribution installments have been updated from %1 to %2 for this subscription.", [
          1 => $this->_subscriptionDetails->installments,
          2 => $params['installments'],
        ]) . ' ';
      }

      $activityParams = [
        'source_contact_id' => $contactID,
        'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Update Recurring Contribution'),
        'subject' => $msg,
        'details' => $message,
        'activity_date_time' => date('YmdHis'),
        'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Completed'),
      ];

      $session = CRM_Core_Session::singleton();
      $cid = $session->get('userID');

      if ($cid) {
        $activityParams['target_contact_id'][] = $activityParams['source_contact_id'];
        $activityParams['source_contact_id'] = $cid;
      }
      CRM_Activity_BAO_Activity::create($activityParams);

      if (!empty($params['is_notify'])) {
        $receiptFrom = CRM_Contribute_BAO_ContributionRecur::getRecurFromAddress($this->getContributionRecurID());

        [$donorDisplayName, $donorEmail] = CRM_Contact_BAO_Contact::getContactDetails($contactID);

        $sendTemplateParams = [
          'groupName' => 'msg_tpl_workflow_contribution',
          'workflow' => 'contribution_recurring_edit',
          'contactId' => $contactID,
          'tplParams' => ['receipt_from_email' => $receiptFrom],
          'isTest' => $this->_subscriptionDetails->is_test,
          'PDFFilename' => 'receipt.pdf',
          'from' => $receiptFrom,
          'toName' => $donorDisplayName,
          'toEmail' => $donorEmail,
          'tokenContext' => ['contribution_recurId' => $this->getContributionRecurID()],
        ];
        CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
      }
    }

    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');
    if ($userID && $status) {
      CRM_Core_Session::setStatus($status, $msgTitle, $msgType);
    }
    elseif (!$userID) {
      if ($status) {
        CRM_Utils_System::setUFMessage($status);
      }
      // keep result as 1, since we not displaying anything on the redirected page anyway
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute/subscriptionstatus',
        "reset=1&task=update&result=1"));
    }
  }

}
