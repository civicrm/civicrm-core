<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be usefusul, but   |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class generates form components for processing a contribution
 *
 */
class CRM_Contribute_Form_AbstractEditPayment extends CRM_Core_Form {
  public $_mode;

  public $_action;

  public $_bltID;

  public $_fields;

  public $_paymentProcessor;
  public $_recurPaymentProcessors;

  public $_processors;

  /**
   * the id of the contribution that we are proceessing
   *
   * @var int
   * @public
   */
  public $_id;

  /**
   * the id of the premium that we are proceessing
   *
   * @var int
   * @public
   */
  public $_premiumID = NULL;
  public $_productDAO = NULL;

  /**
   * the id of the note
   *
   * @var int
   * @public
   */
  public $_noteID;

  /**
   * the id of the contact associated with this contribution
   *
   * @var int
   * @public
   */
  public $_contactID;

  /**
   * the id of the pledge payment that we are processing
   *
   * @var int
   * @public
   */
  public $_ppID;

  /**
   * the id of the pledge that we are processing
   *
   * @var int
   * @public
   */
  public $_pledgeID;

  /**
   * is this contribution associated with an online
   * financial transaction
   *
   * @var boolean
   * @public
   */
  public $_online = FALSE;

  /**
   * Stores all product option
   *
   * @var array
   * @public
   */
  public $_options;

  /**
   * stores the honor id
   *
   * @var int
   * @public
   */
  public $_honorID = NULL;

  /**
   * Store the contribution Type ID
   *
   * @var array
   */
  public $_contributionType;

  /**
   * The contribution values if an existing contribution
   */
  public $_values;

  /**
   * The pledge values if this contribution is associated with pledge
   */
  public $_pledgeValues;

  public $_contributeMode = 'direct';

  public $_context;

  public $_compId;

  /*
   * Store the line items if price set used.
   */
  public $_lineItems;

  protected $_formType;
  protected $_cdType;

  public function buildValuesAndAssignOnline_Note_Type($id, &$values) {
    $ids = array();
    $params = array('id' => $id);
    CRM_Contribute_BAO_Contribution::getValues($params, $values, $ids);

    //Check if this is an online transaction (financial_trxn.payment_processor_id NOT NULL)
    $this->_online = FALSE;
    $fids = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($id);
    if (CRM_Utils_Array::value('financialTrxnId', $fids)) {
      $this->_online = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialTrxn', $fids['financialTrxnId'], 'payment_processor_id');
    }
    
    // Also don't allow user to update some fields for recurring contributions.
    if (!$this->_online) {
      $this->_online = CRM_Utils_Array::value('contribution_recur_id', $values);
    }
    
    $this->assign('isOnline', $this->_online ? TRUE : FALSE);

    //unset the honor type id:when delete the honor_contact_id
    //and edit the contribution, honoree infomation pane open
    //since honor_type_id is present
    if (!CRM_Utils_Array::value('honor_contact_id', $values)) {
      unset($values['honor_type_id']);
    }
    //to get note id
    $daoNote = new CRM_Core_BAO_Note();
    $daoNote->entity_table = 'civicrm_contribution';
    $daoNote->entity_id = $id;
    if ($daoNote->find(TRUE)) {
      $this->_noteID = $daoNote->id;
      $values['note'] = $daoNote->note;
    }
    $this->_contributionType = $values['financial_type_id'];

    $csParams = array('contribution_id' => $id);
    $softCredit = CRM_Contribute_BAO_Contribution::getSoftContribution($csParams, TRUE);

    if (CRM_Utils_Array::value('soft_credit_to', $softCredit)) {
      $softCredit['sort_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
        $softCredit['soft_credit_to'], 'sort_name'
      );
    }
    $values['soft_credit_to'] = CRM_Utils_Array::value('sort_name', $softCredit);
    $values['softID'] = CRM_Utils_Array::value('soft_credit_id', $softCredit);
    $values['soft_contact_id'] = CRM_Utils_Array::value('soft_credit_to', $softCredit);

    if (CRM_Utils_Array::value('pcp_id', $softCredit)) {
      $pcpId = CRM_Utils_Array::value('pcp_id', $softCredit);
      $pcpTitle = CRM_Core_DAO::getFieldValue('CRM_PCP_DAO_PCP', $pcpId, 'title');
      $contributionPageTitle = CRM_PCP_BAO_PCP::getPcpPageTitle($pcpId, 'contribute');
      $values['pcp_made_through'] = CRM_Utils_Array::value('sort_name', $softCredit) . " :: " . $pcpTitle . " :: " . $contributionPageTitle;
      $values['pcp_made_through_id'] = CRM_Utils_Array::value('pcp_id', $softCredit);
      $values['pcp_display_in_roll'] = CRM_Utils_Array::value('pcp_display_in_roll', $softCredit);
      $values['pcp_roll_nickname'] = CRM_Utils_Array::value('pcp_roll_nickname', $softCredit);
      $values['pcp_personal_note'] = CRM_Utils_Array::value('pcp_personal_note', $softCredit);
    }
  }

  /**
   * @param string $type eg 'Contribution'
   * @param string $subType
   * @param int $entityId
   */
  public function applyCustomData($type, $subType, $entityId) {
    $this->set('type', $type);
    $this->set('subType', $subType);
    $this->set('entityId', $entityId);

    CRM_Custom_Form_CustomData::preProcess($this, NULL, $subType, 1, $type, $entityId);
    CRM_Custom_Form_CustomData::buildQuickForm($this);
    CRM_Custom_Form_CustomData::setDefaultValues($this);
  }

  public function assignPremiumProduct($id) { //to get Premium id
    $sql = "
SELECT *
FROM   civicrm_contribution_product
WHERE  contribution_id = {$id}
";
    $dao = CRM_Core_DAO::executeQuery($sql,
      CRM_Core_DAO::$_nullArray
    );
    if ($dao->fetch()) {
      $this->_premiumID = $dao->id;
      $this->_productDAO = $dao;
    }
    $dao->free();
  }

  /**
   * This function process contribution related objects.
   */
  protected function updateRelatedComponent($contributionId, $statusId, $previousStatusId = NULL) {
    $statusMsg = NULL;
    if (!$contributionId || !$statusId) {
      return $statusMsg;
    }

    $params = array(
      'contribution_id' => $contributionId,
      'contribution_status_id' => $statusId,
      'previous_contribution_status_id' => $previousStatusId,
    );

    $updateResult = CRM_Contribute_BAO_Contribution::transitionComponents($params);

    if (!is_array($updateResult) ||
      !($updatedComponents = CRM_Utils_Array::value('updatedComponents', $updateResult)) ||
      !is_array($updatedComponents) ||
      empty($updatedComponents)
    ) {
      return $statusMsg;
    }

    // get the user display name.
    $sql = "
   SELECT  display_name as displayName
     FROM  civicrm_contact
LEFT JOIN  civicrm_contribution on (civicrm_contribution.contact_id = civicrm_contact.id )
    WHERE  civicrm_contribution.id = {$contributionId}";
    $userDisplayName = CRM_Core_DAO::singleValueQuery($sql);

    // get the status message for user.
    foreach ($updatedComponents as $componentName => $updatedStatusId) {

      if ($componentName == 'CiviMember') {
        $updatedStatusName = CRM_Utils_Array::value($updatedStatusId,
          CRM_Member_PseudoConstant::membershipStatus()
        );
        if ($updatedStatusName == 'Cancelled') {
          $statusMsg .= "<br />" . ts("Membership for %1 has been Cancelled.", array(1 => $userDisplayName));
        }
        elseif ($updatedStatusName == 'Expired') {
          $statusMsg .= "<br />" . ts("Membership for %1 has been Expired.", array(1 => $userDisplayName));
        }
        elseif ($endDate = CRM_Utils_Array::value('membership_end_date', $updateResult)) {
          $statusMsg .= "<br />" . ts("Membership for %1 has been updated. The membership End Date is %2.",
            array(
              1 => $userDisplayName,
              2 => $endDate
            )
          );
        }
      }

      if ($componentName == 'CiviEvent') {
        $updatedStatusName = CRM_Utils_Array::value($updatedStatusId,
          CRM_Event_PseudoConstant::participantStatus()
        );
        if ($updatedStatusName == 'Cancelled') {
          $statusMsg .= "<br />" . ts("Event Registration for %1 has been Cancelled.", array(1 => $userDisplayName));
        }
        elseif ($updatedStatusName == 'Registered') {
          $statusMsg .= "<br />" . ts("Event Registration for %1 has been updated.", array(1 => $userDisplayName));
        }
      }

      if ($componentName == 'CiviPledge') {
        $updatedStatusName = CRM_Utils_Array::value($updatedStatusId,
          CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name')
        );
        if ($updatedStatusName == 'Cancelled') {
          $statusMsg .= "<br />" . ts("Pledge Payment for %1 has been Cancelled.", array(1 => $userDisplayName));
        }
        elseif ($updatedStatusName == 'Failed') {
          $statusMsg .= "<br />" . ts("Pledge Payment for %1 has been Failed.", array(1 => $userDisplayName));
        }
        elseif ($updatedStatusName == 'Completed') {
          $statusMsg .= "<br />" . ts("Pledge Payment for %1 has been updated.", array(1 => $userDisplayName));
        }
      }
    }

    return $statusMsg;
  }

  /**
   * @return array (0 => array(int $ppId => string $label), 1 => array(...payproc details...))
   */
  public function getValidProcessorsAndAssignFutureStartDate() {
    $validProcessors = array();
    $processors = CRM_Core_PseudoConstant::paymentProcessor(FALSE, FALSE, "billing_mode IN ( 1, 3 )");

    foreach ($processors as $ppID => $label) {
      $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($ppID, $this->_mode);
      // at this stage only Authorize.net has been tested to support future start dates so if it's enabled let the template know
      // to show receive date
      $processorsSupportingFutureStartDate = array('AuthNet');
      if (in_array($paymentProcessor['payment_processor_type'], $processorsSupportingFutureStartDate)) {
        $this->assign('processorSupportsFutureStartDate', TRUE);
      }
      if ($paymentProcessor['payment_processor_type'] == 'PayPal' && !$paymentProcessor['user_name']) {
        continue;
      }
      elseif ($paymentProcessor['payment_processor_type'] == 'Dummy' && $this->_mode == 'live') {
        continue;
      }
      else {
        $paymentObject = CRM_Core_Payment::singleton($this->_mode, $paymentProcessor, $this);
        $error = $paymentObject->checkConfig();
        if (empty($error)) {
          $validProcessors[$ppID] = $label;
        }
        $paymentObject = NULL;
      }
    }
    if (empty($validProcessors)) {
      CRM_Core_Error::fatal(ts('You will need to configure the %1 settings for your Payment Processor before you can submit credit card transactions.', array(1 => $this->_mode)));
    }
    else {
      return array($validProcessors, $paymentProcessor);
    }
  }

  /**
   * Assign billing type id to bltID
   *
   * @return void
   */
  public function assignBillingType() {
    $locationTypes = CRM_Core_PseudoConstant::locationType();
    $this->_bltID = array_search('Billing', $locationTypes);
    if (!$this->_bltID) {
      CRM_Core_Error::fatal(ts('Please set a location type of %1', array(1 => 'Billing')));
    }
    $this->set('bltID', $this->_bltID);
    $this->assign('bltID', $this->_bltID);
  }

  /**
   * Assign $this->processors, $this->recurPaymentProcessors, and related Smarty variables
   */
  public function assignProcessors() {
    //ensure that processor has a valid config
    //only valid processors get display to user
    if ($this->_mode) {
      list($this->_processors, $paymentProcessor) = $this->getValidProcessorsAndAssignFutureStartDate();

      //get the valid recurring processors.
      $recurring = CRM_Core_PseudoConstant::paymentProcessor(FALSE, FALSE, 'is_recur = 1');
      $this->_recurPaymentProcessors = array_intersect_assoc($this->_processors, $recurring);
    }
    $this->assign('recurringPaymentProcessorIds',
      empty($this->_recurPaymentProcessors) ? '' : implode(',', array_keys($this->_recurPaymentProcessors))
    );

    // this required to show billing block
    $this->assign_by_ref('paymentProcessor', $paymentProcessor);
    $this->assign('hidePayPalExpress', TRUE);
  }

  public function getCurrency($submittedValues) { // get current currency from DB or use default currency
    $config = CRM_Core_Config::singleton();

    $currentCurrency = CRM_Utils_Array::value('currency',
      $this->_values,
      $config->defaultCurrency
    );

    // use submitted currency if present else use current currency
    $result = CRM_Utils_Array::value('currency',
      $submittedValues,
      $currentCurrency
    );
    return $result;
  }

  public function getFinancialAccounts($financialTypeId) {
    $financialAccounts = array();
    CRM_Core_PseudoConstant::populate($financialAccounts,
      'CRM_Financial_DAO_EntityFinancialAccount',
      $all = TRUE,
      $retrieve = 'financial_account_id',
      $filter = NULL,
      " entity_id = {$financialTypeId} ", NULL, 'account_relationship');
    return $financialAccounts;
  }

  public function getFinancialAccount($financialTypeId, $relationTypeId) {
    $financialAccounts = $this->getFinancialAccounts($financialTypeId);
    return CRM_Utils_Array::value($relationTypeId, $financialAccounts);
  }

  public function preProcessPledge() {
    //get the payment values associated with given pledge payment id OR check for payments due.
    $this->_pledgeValues = array();
    if ($this->_ppID) {
      $payParams = array('id' => $this->_ppID);

      CRM_Pledge_BAO_PledgePayment::retrieve($payParams, $this->_pledgeValues['pledgePayment']);
      $this->_pledgeID = CRM_Utils_Array::value('pledge_id', $this->_pledgeValues['pledgePayment']);
      $paymentStatusID = CRM_Utils_Array::value('status_id', $this->_pledgeValues['pledgePayment']);
      $this->_id = CRM_Utils_Array::value('contribution_id', $this->_pledgeValues['pledgePayment']);

      //get all status
      $allStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
      if (!($paymentStatusID == array_search('Pending', $allStatus) || $paymentStatusID == array_search('Overdue', $allStatus))) {
        CRM_Core_Error::fatal(ts("Pledge payment status should be 'Pending' or  'Overdue'."));
      }

      //get the pledge values associated with given pledge payment.

      $ids = array();
      $pledgeParams = array('id' => $this->_pledgeID);
      CRM_Pledge_BAO_Pledge::getValues($pledgeParams, $this->_pledgeValues, $ids);
      $this->assign('ppID', $this->_ppID);
    }
    else {
      // Not making a pledge payment, so if adding a new contribution we should check if pledge payment(s) are due for this contact so we can alert the user. CRM-5206
      if (isset($this->_contactID)) {
        $contactPledges = CRM_Pledge_BAO_Pledge::getContactPledges($this->_contactID);

        if (!empty($contactPledges)) {
          $payments = $paymentsDue = NULL;
          $multipleDue = FALSE;
          foreach ($contactPledges as $key => $pledgeId) {
            $payments = CRM_Pledge_BAO_PledgePayment::getOldestPledgePayment($pledgeId);
            if ($payments) {
              if ($paymentsDue) {
                $multipleDue = TRUE;
                break;
              }
              else {
                $paymentsDue = $payments;
              }
            }
          }
          if ($multipleDue) {
            // Show link to pledge tab since more than one pledge has a payment due
            $pledgeTab = CRM_Utils_System::url('civicrm/contact/view',
              "reset=1&force=1&cid={$this->_contactID}&selectedChild=pledge"
            );
            CRM_Core_Session::setStatus(ts('This contact has pending or overdue pledge payments. <a href="%1">Click here to view their Pledges tab</a> and verify whether this contribution should be applied as a pledge payment.', array(1 => $pledgeTab)), ts('Notice'), 'alert');
          }
          elseif ($paymentsDue) {
            // Show user link to oldest Pending or Overdue pledge payment
            $ppAmountDue = CRM_Utils_Money::format($payments['amount'], $payments['currency']);
            $ppSchedDate = CRM_Utils_Date::customFormat(CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_PledgePayment', $payments['id'], 'scheduled_date'));
            if ($this->_mode) {
              $ppUrl = CRM_Utils_System::url('civicrm/contact/view/contribution',
                "reset=1&action=add&cid={$this->_contactID}&ppid={$payments['id']}&context=pledge&mode=live"
              );
            }
            else {
              $ppUrl = CRM_Utils_System::url('civicrm/contact/view/contribution',
                "reset=1&action=add&cid={$this->_contactID}&ppid={$payments['id']}&context=pledge"
              );
            }
            CRM_Core_Session::setStatus(ts('This contact has a pending or overdue pledge payment of %2 which is scheduled for %3. <a href="%1">Click here to enter a pledge payment</a>.', array(
              1 => $ppUrl,
              2 => $ppAmountDue,
              3 => $ppSchedDate
            )), ts('Notice'), 'alert');
          }
        }
      }
    }
  }

  public function unsetCreditCardFields($submittedValues) {
    //Offline Contribution.
    $unsetParams = array(
      'payment_processor_id',
      "email-{$this->_bltID}",
      'hidden_buildCreditCard',
      'hidden_buildDirectDebit',
      'billing_first_name',
      'billing_middle_name',
      'billing_last_name',
      'street_address-5',
      "city-{$this->_bltID}",
      "state_province_id-{$this->_bltID}",
      "postal_code-{$this->_bltID}",
      "country_id-{$this->_bltID}",
      'credit_card_number',
      'cvv2',
      'credit_card_exp_date',
      'credit_card_type',
    );
    foreach ($unsetParams as $key) {
      if (isset($submittedValues[$key])) {
        unset($submittedValues[$key]);
      }
    }
    return $submittedValues;
  }

}
