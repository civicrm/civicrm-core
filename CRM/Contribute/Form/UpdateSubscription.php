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

  use CRM_Financial_Form_PaymentProcessorFormTrait;

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
   * The ContributionRecur as returned by API4
   *
   * @var array
   */
  protected array $contributionRecur;

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

    $this->contributionRecur = \Civi\Api4\ContributionRecur::get(FALSE)
      ->addWhere('id', '=', $this->contributionRecurID)
      ->execute()
      ->first();

    $membership = \Civi\Api4\Membership::get(FALSE)
      ->addSelect('id', 'membership_type_id:name')
      ->addWhere('contribution_recur_id', '=', $this->contributionRecurID)
      ->execute()
      ->first();
    if (!empty($membership)) {
      $membershipValues['membership_id'] = $membership['id'];
      $membershipValues['membership_name'] = $membership['membership_type_id:name'];
    }
    $this->assign('recurMembership', $membershipValues ?? []);
    $this->assign('contactId', $this->contributionRecur['contact_id']);

    $this->assign('self_service', $this->isSelfService());
    $this->assign('recur_frequency_interval', $this->contributionRecur['frequency_interval']);
    $this->assign('recur_frequency_unit', $this->contributionRecur['frequency_unit']);

    $this->editableScheduleFields = $this->getPaymentProcessorObject()->getEditableRecurringScheduleFields();

    $changeHelpText = $this->getPaymentProcessorObject()->getRecurringScheduleUpdateHelpText();
    if (!in_array('amount', $this->editableScheduleFields)) {
      // Not sure if this is good behaviour - maintaining this existing behaviour for now.
      CRM_Core_Session::setStatus($changeHelpText, ts('Warning'), 'alert');
    }
    else {
      $this->assign('changeHelpText', $changeHelpText);
    }

    // when custom data is included in this page
    if (!empty($_POST['hidden_custom']) && !$this->isSelfService()) {
      CRM_Custom_Form_CustomData::preProcess($this, NULL, NULL, 1, 'ContributionRecur', $this->contributionRecurID);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
    }

    // amount, installments are added to the form as separate elements
    // everything that is in the smarty variable editableScheduleFields will be added (again)
    $editableScheduleFieldsToBeAddedToForm = [];
    foreach ($this->editableScheduleFields as $field) {
      if (in_array($field, ['amount', 'installments'])) {
        continue;
      }
      $editableScheduleFieldsToBeAddedToForm[] = $field;
    }
    $this->assign('editableScheduleFields', $editableScheduleFieldsToBeAddedToForm);

    $email = \Civi\Api4\Email::get(FALSE)
      ->addWhere('contact_id', '=', $this->contributionRecur['contact_id'])
      ->addOrderBy('is_primary', 'DESC')
      ->execute()
      ->first();
    $this->_donorEmail = $email['email'] ?? '';

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
    $this->_defaults['amount'] = $this->contributionRecur['amount'];
    $this->_defaults['installments'] = $this->contributionRecur['installments'];
    $this->_defaults['campaign_id'] = $this->_subscriptionDetails->campaign_id;
    $this->_defaults['financial_type_id'] = $this->_subscriptionDetails->financial_type_id;
    foreach ($this->editableScheduleFields as $field) {
      $this->_defaults[$field] = $this->contributionRecur[$field] ?? NULL;
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

    $this->addMoney('amount', ts('Recurring Contribution Amount'), TRUE, $amtAttr,
      TRUE, 'currency', $this->contributionRecur['currency'], TRUE
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

    if (in_array('installments', $this->editableScheduleFields)) {
      $this->add('text', 'installments', ts('Number of Installments'), ['size' => 20], FALSE);
    }

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
    // @todo Stop using $params/exportValues()
    $params = $this->exportValues();

    $isNotify = (bool) $this->getSubmittedValue('is_notify');

    if ($this->isSelfService() && $this->_donorEmail) {
      // for self service force notify
      $isNotify = TRUE;
    }

    $message = '';

    $updateSubscription = TRUE;
    if ($this->getPaymentProcessorObject()->supports('changeSubscriptionAmount')) {
      try {
        $changeSubscriptionAmountParams['contributionRecurID'] = $this->contributionRecur['id'];
        $changeSubscriptionAmountParams['recurProcessorID'] = $this->contributionRecur['processor_id'];
        foreach ($this->editableScheduleFields as $field) {
          if ($this->getSubmittedValue($field)) {
            $changeSubscriptionAmountParams[$field] = $this->getSubmittedValue($field);
          }
          if ((int) $this->getsubmittedValue('financial_type_id') !== $this->contributionRecur['financial_type_id']) {
            $recurParams['financial_type_id'] = $this->getSubmittedValue('financial_type_id');
          }
        }
        // @todo Legacy parameters that we want to remove!
        $changeSubscriptionAmountParams['subscriptionId'] = $changeSubscriptionAmountParams['recurProcessorID'];

        $updateSubscription = $this->getPaymentProcessorObject()->changeSubscriptionAmount($message, $changeSubscriptionAmountParams);
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
      // @todo convert to API4 ContributionRecur::Update (need to handle custom fields)
      $recurParams = [
        'id' => $this->contributionRecur['id'],
        'custom' => CRM_Core_BAO_CustomField::postProcess($params, $this->contributionRecurID, 'ContributionRecur'),
      ];
      foreach ($this->editableScheduleFields as $field) {
        if ($this->getSubmittedValue($field)) {
          $recurParams[$field] = $this->getSubmittedValue($field);
        }
      }
      if ($this->getsubmittedValue('financial_type_id')) {
        $recurParams['financial_type_id'] = $this->getSubmittedValue('financial_type_id');
      }
      // Handle custom data
      // save the changes
      CRM_Contribute_BAO_ContributionRecur::add($recurParams);
      $status = ts('Recurring contribution has been updated to: %1 every %2 %3(s)',
        [
          1 => CRM_Utils_Money::format($this->getSubmittedValue('amount'), $this->contributionRecur['currency']),
          2 => $this->contributionRecur['frequency_interval'],
          3 => $this->contributionRecur['frequency_unit'],
        ]
      );
      if ($this->getSubmittedValue('installments')) {
        $status .= ts(' for %1 installments.', [4 => $this->getSubmittedValue('installments')]);
      }

      $msgTitle = ts('Update Success');
      $msgType = 'success';
      $msg = ts('Recurring Contribution Updated');
      $contactID = $this->contributionRecur['contact_id'];

      if ($this->contributionRecur['amount'] != $this->getSubmittedValue('amount')) {
        $message .= "<br /> " . ts("Recurring contribution amount has been updated from %1 to %2 for this subscription.",
            [
              1 => CRM_Utils_Money::format($this->getSubmittedValue('amount'), $this->contributionRecur['currency']),
              2 => CRM_Utils_Money::format($this->getSubmittedValue('amount'), $this->contributionRecur['currency']),
            ]) . ' ';
        if ($this->contributionRecur['amount'] < $this->getSubmittedValue('amount')) {
          $msg = ts('Recurring Contribution Updated - increased installment amount');
        }
        else {
          $msg = ts('Recurring Contribution Updated - decreased installment amount');
        }
      }

      if ($this->contributionRecur['installments'] !== $this->getSubmittedValue('installments')) {
        $message .= "<br /> " . ts("Recurring contribution installments have been updated from %1 to %2 for this subscription.", [
          1 => $this->contributionRecur['installments'],
          2 => $this->getSubmittedValue('installments'),
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

      if ($isNotify) {
        $receiptFrom = CRM_Contribute_BAO_ContributionRecur::getRecurFromAddress($this->contributionRecur['id']);

        [$donorDisplayName, $donorEmail] = CRM_Contact_BAO_Contact::getContactDetails($contactID);

        $sendTemplateParams = [
          'groupName' => 'msg_tpl_workflow_contribution',
          'workflow' => 'contribution_recurring_edit',
          'contactId' => $contactID,
          'tplParams' => ['receipt_from_email' => $receiptFrom],
          'isTest' => $this->contributionRecur['is_test'],
          'PDFFilename' => 'receipt.pdf',
          'from' => $receiptFrom,
          'toName' => $donorDisplayName,
          'toEmail' => $donorEmail,
          'tokenContext' => ['contribution_recurId' => $this->contributionRecur['id']],
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
