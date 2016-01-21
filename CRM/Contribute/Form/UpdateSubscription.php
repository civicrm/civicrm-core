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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * This class generates form components generic to recurring contributions.
 *
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 */
class CRM_Contribute_Form_UpdateSubscription extends CRM_Core_Form {

  /**
   * The recurring contribution id, used when editing the recurring contribution.
   *
   * @var int
   */
  protected $contributionRecurID = NULL;

  protected $_coid = NULL;

  protected $_subscriptionDetails = NULL;

  protected $_selfService = FALSE;

  public $_paymentProcessor = NULL;

  public $_paymentProcessorObj = NULL;

  /**
   * Fields that affect the schedule and are defined as editable by the processor.
   *
   * @var array
   */
  protected $editableScheduleFields = array();

  /**
   * The id of the contact associated with this recurring contribution.
   *
   * @var int
   */
  public $_contactID;

  /**
   * Pre-processing for the form.
   *
   * @throws \Exception
   */
  public function preProcess() {

    $this->contributionRecurID = CRM_Utils_Request::retrieve('crid', 'Integer', $this, FALSE);
    if ($this->contributionRecurID) {
      $this->_paymentProcessor = CRM_Contribute_BAO_ContributionRecur::getPaymentProcessor($this->contributionRecurID);
      if (!$this->_paymentProcessor) {
        CRM_Core_Error::statusBounce(ts('There is no valid processor for this subscription so it cannot be edited.'));
      }
      $this->_paymentProcessorObj = $this->_paymentProcessor['object'];
      $this->_subscriptionDetails = CRM_Contribute_BAO_ContributionRecur::getSubscriptionDetails($this->contributionRecurID);
    }

    $this->_coid = CRM_Utils_Request::retrieve('coid', 'Integer', $this, FALSE);
    if ($this->_coid) {
      $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($this->_coid, 'contribute', 'info');
      $this->_paymentProcessorObj = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($this->_coid, 'contribute', 'obj');
      $this->_subscriptionDetails = CRM_Contribute_BAO_ContributionRecur::getSubscriptionDetails($this->_coid, 'contribution');
      $this->contributionRecurID = $this->_subscriptionDetails->recur_id;
    }
    elseif ($this->contributionRecurID) {
      $this->_coid = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $this->contributionRecurID, 'id', 'contribution_recur_id');
    }

    if ((!$this->contributionRecurID) ||
      ($this->_subscriptionDetails == CRM_Core_DAO::$_nullObject)
    ) {
      CRM_Core_Error::fatal('Required information missing.');
    }

    if ($this->_subscriptionDetails->membership_id && $this->_subscriptionDetails->auto_renew) {
      CRM_Core_Error::fatal(ts('You cannot update the subscription.'));
    }

    if (!CRM_Core_Permission::check('edit contributions')) {
      $userChecksum = CRM_Utils_Request::retrieve('cs', 'String', $this, FALSE);
      if (!CRM_Contact_BAO_Contact_Utils::validChecksum($this->_subscriptionDetails->contact_id, $userChecksum)) {
        CRM_Core_Error::fatal(ts('You do not have permission to update subscription.'));
      }
      $this->_selfService = TRUE;
    }
    $this->assign('self_service', $this->_selfService);

    $this->editableScheduleFields = $this->_paymentProcessorObj->getEditableRecurringScheduleFields();

    $changeHelpText = $this->_paymentProcessorObj->getRecurringScheduleUpdateHelpText();
    if (!in_array('amount', $this->editableScheduleFields)) {
      // Not sure if this is good behaviour - maintaining this existing behaviour for now.
      CRM_Core_Session::setStatus($changeHelpText, ts('Warning'), 'alert');
    }
    else {
      $this->assign('changeHelpText', $changeHelpText);
    }
    $alreadyHardCodedFields = array('amount', 'installments');
    foreach ($this->editableScheduleFields as $editableScheduleField) {
      if (!in_array($editableScheduleField, $alreadyHardCodedFields)) {
        $this->addField($editableScheduleField, array('entity' => 'ContributionRecur'));
      }
    }

    $this->assign('editableScheduleFields', array_diff($this->editableScheduleFields, $alreadyHardCodedFields));
    $this->assign('paymentProcessor', $this->_paymentProcessor);
    $this->assign('frequency_unit', $this->_subscriptionDetails->frequency_unit);
    $this->assign('frequency_interval', $this->_subscriptionDetails->frequency_interval);

    if ($this->_subscriptionDetails->contact_id) {
      list($this->_donorDisplayName, $this->_donorEmail) = CRM_Contact_BAO_Contact::getContactDetails($this->_subscriptionDetails->contact_id);
    }

    CRM_Utils_System::setTitle(ts('Update Recurring Contribution'));

    // Handle context redirection.
    CRM_Contribute_BAO_ContributionRecur::setSubscriptionContext();
  }

  /**
   * Set default values for the form.
   *
   * Note that in edit/view mode the default values are retrieved from the database.
   */
  public function setDefaultValues() {
    $this->_defaults = array();
    $this->_defaults['amount'] = $this->_subscriptionDetails->amount;
    $this->_defaults['installments'] = $this->_subscriptionDetails->installments;
    $this->_defaults['campaign_id'] = $this->_subscriptionDetails->campaign_id;
    $this->_defaults['financial_type_id'] = $this->_subscriptionDetails->financial_type_id;
    $this->_defaults['is_notify'] = 1;
    foreach ($this->editableScheduleFields as $field) {
      $this->_defaults[$field] = $this->_subscriptionDetails->$field;
    }

    return $this->_defaults;
  }

  /**
   * Actually build the components of the form.
   */
  public function buildQuickForm() {
    // CRM-16398: If current recurring contribution got > 1 lineitems then make amount field readonly
    $amtAttr = array('size' => 20);
    $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($this->_coid);
    if (count($lineItems) > 1) {
      $amtAttr += array('readonly' => TRUE);
    }
    $this->addMoney('amount', ts('Recurring Contribution Amount'), TRUE, $amtAttr,
      TRUE, 'currency', $this->_subscriptionDetails->currency, TRUE
    );

    $this->add('text', 'installments', ts('Number of Installments'), array('size' => 20), FALSE);

    if ($this->_donorEmail) {
      $this->add('checkbox', 'is_notify', ts('Notify Contributor?'));
    }

    if (CRM_Core_Permission::check('edit contributions')) {
      CRM_Campaign_BAO_Campaign::addCampaign($this);
    }

    if (CRM_Contribute_BAO_ContributionRecur::supportsFinancialTypeChange($this->contributionRecurID)) {
      $this->addEntityRef('financial_type_id', ts('Financial Type'), array('entity' => 'FinancialType'), TRUE);
    }

    $type = 'next';
    if ($this->_selfService) {
      $type = 'submit';
    }

    // define the buttons
    $this->addButtons(array(
        array(
          'type' => $type,
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  /**
   * Called after the user submits the form.
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->exportValues();

    if ($this->_selfService && $this->_donorEmail) {
      // for self service force notify
      $params['is_notify'] = 1;
    }

    // if this is an update of an existing recurring contribution, pass the ID
    $params['id'] = $this->_subscriptionDetails->recur_id;
    $message = '';

    $params['subscriptionId'] = $this->_subscriptionDetails->subscription_id;
    $updateSubscription = TRUE;
    if ($this->_paymentProcessorObj->isSupported('changeSubscriptionAmount')) {
      $updateSubscription = $this->_paymentProcessorObj->changeSubscriptionAmount($message, $params);
    }
    if (is_a($updateSubscription, 'CRM_Core_Error')) {
      CRM_Core_Error::displaySessionError($updateSubscription);
      $status = ts('Could not update the Recurring contribution details');
      $msgTitle = ts('Update Error');
      $msgType = 'error';
    }
    elseif ($updateSubscription) {
      // save the changes
      $result = CRM_Contribute_BAO_ContributionRecur::add($params);
      $status = ts('Recurring contribution has been updated to: %1, every %2 %3(s) for %4 installments.',
        array(
          1 => CRM_Utils_Money::format($params['amount'], $this->_subscriptionDetails->currency),
          2 => $this->_subscriptionDetails->frequency_interval,
          3 => $this->_subscriptionDetails->frequency_unit,
          4 => $params['installments'],
        )
      );

      $msgTitle = ts('Update Success');
      $msgType = 'success';
      $msg = ts('Recurring Contribution Updated');
      $contactID = $this->_subscriptionDetails->contact_id;

      if ($this->_subscriptionDetails->amount != $params['amount']) {
        $message .= "<br /> " . ts("Recurring contribution amount has been updated from %1 to %2 for this subscription.",
            array(
              1 => CRM_Utils_Money::format($this->_subscriptionDetails->amount, $this->_subscriptionDetails->currency),
              2 => CRM_Utils_Money::format($params['amount'], $this->_subscriptionDetails->currency),
            )) . ' ';
        if ($this->_subscriptionDetails->amount < $params['amount']) {
          $msg = ts('Recurring Contribution Updated - increased installment amount');
        }
        else {
          $msg = ts('Recurring Contribution Updated - decreased installment amount');
        }
      }

      if ($this->_subscriptionDetails->installments != $params['installments']) {
        $message .= "<br /> " . ts("Recurring contribution installments have been updated from %1 to %2 for this subscription.", array(
              1 => $this->_subscriptionDetails->installments,
              2 => $params['installments'],
            )) . ' ';
      }

      $activityParams = array(
        'source_contact_id' => $contactID,
        'activity_type_id' => CRM_Core_OptionGroup::getValue('activity_type',
          'Update Recurring Contribution',
          'name'
        ),
        'subject' => $msg,
        'details' => $message,
        'activity_date_time' => date('YmdHis'),
        'status_id' => CRM_Core_OptionGroup::getValue('activity_status',
          'Completed',
          'name'
        ),
      );
      $session = CRM_Core_Session::singleton();
      $cid = $session->get('userID');

      if ($cid) {
        $activityParams['target_contact_id'][] = $activityParams['source_contact_id'];
        $activityParams['source_contact_id'] = $cid;
      }
      CRM_Activity_BAO_Activity::create($activityParams);

      if (!empty($params['is_notify'])) {
        // send notification
        if ($this->_subscriptionDetails->contribution_page_id) {
          CRM_Core_DAO::commonRetrieveAll('CRM_Contribute_DAO_ContributionPage', 'id',
            $this->_subscriptionDetails->contribution_page_id, $value, array(
              'title',
              'receipt_from_name',
              'receipt_from_email',
            )
          );
          $receiptFrom = '"' . CRM_Utils_Array::value('receipt_from_name', $value[$this->_subscriptionDetails->contribution_page_id]) . '" <' . $value[$this->_subscriptionDetails->contribution_page_id]['receipt_from_email'] . '>';
        }
        else {
          $domainValues = CRM_Core_BAO_Domain::getNameAndEmail();
          $receiptFrom = "$domainValues[0] <$domainValues[1]>";
        }

        list($donorDisplayName, $donorEmail) = CRM_Contact_BAO_Contact::getContactDetails($contactID);

        $tplParams = array(
          'recur_frequency_interval' => $this->_subscriptionDetails->frequency_interval,
          'recur_frequency_unit' => $this->_subscriptionDetails->frequency_unit,
          'amount' => CRM_Utils_Money::format($params['amount']),
          'installments' => $params['installments'],
        );

        $tplParams['contact'] = array('display_name' => $donorDisplayName);
        $tplParams['receipt_from_email'] = $receiptFrom;

        $sendTemplateParams = array(
          'groupName' => 'msg_tpl_workflow_contribution',
          'valueName' => 'contribution_recurring_edit',
          'contactId' => $contactID,
          'tplParams' => $tplParams,
          'isTest' => $this->_subscriptionDetails->is_test,
          'PDFFilename' => 'receipt.pdf',
          'from' => $receiptFrom,
          'toName' => $donorDisplayName,
          'toEmail' => $donorEmail,
        );
        list($sent) = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
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
      return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute/subscriptionstatus',
        "reset=1&task=update&result=1"));
    }
  }

  /**
   * Explicitly declare the form context.
   */
  public function getDefaultContext() {
    return 'create';
  }

}
