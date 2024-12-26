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
  use CRM_Custom_Form_CustomDataTrait;

  public $_paymentProcessor = NULL;

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

    if (!$this->getSubscriptionDetails()) {
      CRM_Core_Error::statusBounce(ts('Required information missing.'));
    }

    $this->assign('contactId', $this->getSubscriptionContactID());
    $this->assign('membershipID', $this->getMembershipID());
    $this->assign('membershipName', $this->getMembershipValue('membership_type_id.name'));

    $this->assign('self_service', $this->isSelfService());
    $this->assign('recur_frequency_interval', $this->getContributionRecurValue('frequency_interval'));
    $this->assign('recur_frequency_unit', $this->getContributionRecurValue('frequency_unit'));

    $this->editableScheduleFields = $this->getPaymentProcessorObject()->getEditableRecurringScheduleFields();

    $changeHelpText = $this->getPaymentProcessorObject()->getRecurringScheduleUpdateHelpText();
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

    if ($this->isSubmitted() && !$this->isSelfService()) {
      // The custom data fields are added to the form by an ajax form.
      // However, if they are not present in the element index they will
      // not be available from `$this->getSubmittedValue()` in post process.
      // We do not have to set defaults or otherwise render - just add to the element index.
      $this->addCustomDataFieldsToForm('ContributionRecur', array_filter([
        'id' => $this->getContributionRecurID(),
      ]));
    }

    $this->assign('editableScheduleFields', array_diff($this->editableScheduleFields, $alreadyHardCodedFields));

    if ($this->getSubscriptionContactID()) {
      $contactDetails = CRM_Contact_BAO_Contact::getContactDetails($this->getSubscriptionContactID());
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
    $this->_defaults['amount'] = $this->getSubscriptionDetails()->amount;
    $this->_defaults['installments'] = $this->getSubscriptionDetails()->installments;
    $this->_defaults['campaign_id'] = $this->getSubscriptionDetails()->campaign_id;
    $this->_defaults['financial_type_id'] = $this->getSubscriptionDetails()->financial_type_id;
    foreach ($this->editableScheduleFields as $field) {
      $this->_defaults[$field] = $this->getSubscriptionDetails()->$field ?? NULL;
    }

    return $this->_defaults;
  }

  /**
   * Actually build the components of the form.
   */
  public function buildQuickForm() {
    // CRM-16398: If current recurring contribution got > 1 lineitems then make amount field readonly
    $amtAttr = ['size' => 20];
    $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($this->getContributionID());
    if (count($lineItems) > 1) {
      $amtAttr += ['readonly' => TRUE];
    }
    $amountField = $this->addMoney('amount', ts('Recurring Contribution Amount'), TRUE, $amtAttr,
      TRUE, 'currency', $this->getSubscriptionDetails()->currency, TRUE
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
      CRM_Campaign_BAO_Campaign::addCampaign($this, $this->getSubscriptionDetails()->campaign_id);
    }

    if (CRM_Contribute_BAO_ContributionRecur::supportsFinancialTypeChange($this->getContributionRecurID())) {
      $this->addEntityRef('financial_type_id', ts('Financial Type'), ['entity' => 'FinancialType'], !$this->isSelfService());
    }

    $this->assign('contributionRecurID', $this->getContributionRecurID());

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
    $activityDetails = '';

    $params['recurProcessorID'] = $params['subscriptionId'] = $this->getSubscriptionDetails()->processor_id;

    $updateSubscription = TRUE;
    if ($this->getPaymentProcessorObject()->supports('changeSubscriptionAmount')) {
      try {
        $updateSubscription = $this->getPaymentProcessorObject()->changeSubscriptionAmount($activityDetails, $params);
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
      $params['custom'] = CRM_Core_BAO_CustomField::postProcess($this->getSubmittedValues(), $this->getContributionRecurID(), 'ContributionRecur');
      // save the changes
      CRM_Contribute_BAO_ContributionRecur::add($params);
      $status = ts('Recurring contribution has been updated to: %1, every %2 %3(s) for %4 installments.',
        [
          1 => CRM_Utils_Money::format($params['amount'], $this->getSubscriptionDetails()->currency),
          2 => $this->getSubscriptionDetails()->frequency_interval,
          3 => $this->getSubscriptionDetails()->frequency_unit,
          4 => $params['installments'],
        ]
      );

      $msgTitle = ts('Update Success');
      $msgType = 'success';
      $activitySubject = ts('Recurring Contribution Updated');
      $contactID = $this->getSubscriptionContactID();

      $this->updateActivitySubjectAndDetails($params, $activitySubject, $activityDetails);

      $activityParams = [
        'source_contact_id' => $contactID,
        'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Update Recurring Contribution'),
        'subject' => $activitySubject,
        'details' => $activityDetails,
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
          'isTest' => $this->getSubscriptionDetails()->is_test,
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

  private function updateActivitySubjectAndDetails(array $params, string &$activitySubject, string &$activityDetails): void {
    if ($this->getSubscriptionDetails()->amount != $params['amount']) {
      $activityDetails .= "<br /> " . ts("Recurring contribution amount has been updated from %1 to %2 for this subscription.",
          [
            1 => CRM_Utils_Money::format($this->getSubscriptionDetails()->amount, $this->getSubscriptionDetails()->currency),
            2 => CRM_Utils_Money::format($params['amount'], $this->getSubscriptionDetails()->currency),
          ]) . ' ';
      if ($this->getSubscriptionDetails()->amount < $params['amount']) {
        $activitySubject = ts('Recurring Contribution Updated - increased installment amount');
      }
      else {
        $activitySubject = ts('Recurring Contribution Updated - decreased installment amount');
      }
    }

    if ($this->getSubscriptionDetails()->installments != $params['installments']) {
      $activityDetails .= "<br /> " . ts("Recurring contribution installments have been updated from %1 to %2 for this subscription.", [
        1 => $this->getSubscriptionDetails()->installments,
        2 => $params['installments'],
      ]) . ' ';
    }

    if (!empty($params['cycle_day']) && $this->getSubscriptionDetails()->cycle_day != $params['cycle_day']) {
      $activityDetails .= "<br /> " . ts("Cycle day has been updated from %1 to %2 for this subscription.", [
        1 => $this->getSubscriptionDetails()->cycle_day,
        2 => $params['cycle_day'],
      ]) . ' ';
    }

    if (
      !empty($params['next_sched_contribution_date']) &&
      $this->getSubscriptionDetails()->next_sched_contribution_date != $params['next_sched_contribution_date']
    ) {
      $activityDetails .= "<br /> " . ts("Next scheduled contribution date has been updated from %1 to %2 for this subscription.", [
        1 => $this->getSubscriptionDetails()->next_sched_contribution_date,
        2 => $params['next_sched_contribution_date'],
      ]) . ' ';
    }
  }

}
