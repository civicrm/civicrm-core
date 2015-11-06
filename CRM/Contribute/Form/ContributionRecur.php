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
 | CiviCRM is distributed in the hope that it will be useful, but   |
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

require_once 'CRM/Contribute/DAO/ContributionRecur.php';

/**
 * This class generates form components for processing a recurring contribution record.
 */
class CRM_Contribute_Form_ContributionRecur extends CRM_Contribute_Form_AbstractEditPayment {

  /**
   * The id of the contact associated with this recurring contribution record.
   *
   * @var int
   */
  public $_contactID;

  /**
   * form defaults
   * @todo can we define this a as protected? can we define higher up the chain
   * @var array
   */
  public $_defaults;

  /**
   * Values of existing recurring contribution record
   */
  public $_values;

  /**
   * The id of the related membership
   */
  public $_membershipID;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    // Check permission for action.
    if (!CRM_Core_Permission::checkActionPermission('CiviContribute', $this->_action)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
    }

    $this->setPageTitle(ts('Recurring Contribution record'));

    // Get the contact id
    $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    $this->assign('contactID', $this->_contactID);

    // Get the action.
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add');
    $this->assign('action', $this->_action);

    // Get the contribution recur id if update
    $this->_id = CRM_Utils_Request::retrieve('crid', 'Positive', $this);
    if (!empty($this->_id)) {
      $this->assign('contribRecurID', $this->_id);
    }

    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);
    $this->assign('context', $this->_context);

    if ($this->_contactID) {
      list($this->userDisplayName, $this->userEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactID);
      $this->assign('displayName', $this->userDisplayName);
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    // Get backoffice payment processors
    $this->assignPaymentRelatedVariables();
    $this->_paymentProcessors = $this->getValidProcessors();

    // Get recurring processors
    $this->_mode = 'live';
    $this->assignProcessors();

    $this->_values = array();

    $ids = array();
    $params = array('id' => $this->_id);
    if (!empty($this->_id)) {
      $recurring = new CRM_Contribute_BAO_ContributionRecur();
      $recurring->copyValues($params);
      $recurring->find(TRUE);
      $ids['contributionrecur'] = $recurring->id;
      CRM_Core_DAO::storeValues($recurring, $this->_values);

      $membership = new CRM_Member_DAO_Membership();
      $membership->contribution_recur_id = $this->_id;
      $membership->is_test = 0;
      if ($membership->find(TRUE)) {
        $this->_membershipID = $membership->id;
        $this->assign('membershipID', $this->_membershipID);
      }

      // Done allow Edit, if no back office support
      if (!array_key_exists($this->_values['payment_processor_id'], $this->_paymentProcessors)) {
        CRM_Core_Error::fatal(ts('You are not allowed to edit this recurring record, as back office edit is not supported by the related payment processor.'));
      }
    }

    // when custom data is included in this page
    if (!empty($_POST['hidden_custom'])) {
      $this->assign('type', 'ContributionRecur');
      $this->assign('entityId', $this->_id);

      CRM_Custom_Form_CustomData::preProcess($this);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
    }

    parent::preProcess();
  }

  public function setDefaultValues() {
    $defaults = $this->_values;

    if ($this->_id) {
      $this->_contactID = $defaults['contact_id'];
    }

    if (isset($defaults['total_amount'])) {
      $defaults['amount'] = CRM_Utils_Money::format($defaults['amount'], NULL, '%a');
    }

    $dates = array('start_date', 'end_date', 'cancel_date', 'next_sched_contribution_date');
    foreach ($dates as $key) {
      if (!empty($defaults[$key])) {
        list($defaults[$key],
          $defaults[$key . '_time']
          ) = CRM_Utils_Date::setDateDefaults(CRM_Utils_Array::value($key, $defaults),
          'activityDateTime'
          );
      }
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      return $defaults;
    }

    // Set move existing contributions to TRUE as default
    $defaults['move_existing_contributions'] = 1;
    $defaults['contact_id'] = $this->_contactID;
    $defaults['selected_cid'] = $this->_contactID;

    $this->_defaults = $defaults;
    return $defaults;
  }

  /**
   * Build the form
   *
   * @access public
   * @return void
   */
  public function buildQuickForm() {

    if ($this->_action & CRM_Core_Action::DELETE) {
      // Check if any contributions created for the recurring record
      $recurringIds = array($this->_id);
      $contributionCount = CRM_Contribute_BAO_ContributionRecur::getCount($recurringIds);
      if (isset($contributionCount[$this->_id]) && $contributionCount[$this->_id] > 0) {
        $this->assign('contributionCount', $contributionCount[$this->_id]);
      }
      $this->addButtons(array(
          array(
            'type' => 'next',
            'name' => ts('Delete'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
      return;
    }

    //need to assign custom data type to the template
    $this->assign('customDataType', 'ContributionRecur');
    $this->assign('entityID', $this->_id);

    $attributes = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionRecur');

    $cid        = CRM_Utils_Request::retrieve('cid', 'Integer', $this);
    $id         = CRM_Utils_Request::retrieve('crid', 'Integer', $this);

    $this->_paymentProcessors = $this->getValidProcessors();
    $offlineRecurPaymentProcessors = array();
    foreach ($this->_paymentProcessors as $processor) {
      if (!empty($processor['is_recur']) && !empty($processor['object']) && $processor['object']->supports('EditRecurringContribution')
          && $processor['is_test'] == 0) {
        $offlineRecurPaymentProcessors[$processor['id']] = $processor['name'];
      }
    }
    $paymentProcessor = $this->add('select', 'payment_processor_id',
      ts('Payment Processor'),
      array('' => ts('- select -')) + $offlineRecurPaymentProcessors,
      TRUE,
      NULL
    );

    $trxnId = $this->add('text', 'trxn_id', ts('Transaction ID'), array('class' => 'twelve'));
    $processorid = $this->add('text', 'processor_id', ts('Processor ID'), array('class' => 'twelve'));

    $financialType = $this->add('select', 'financial_type_id',
      ts('Financial Type'),
      array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::financialType(),
      TRUE,
      NULL
    );

    $contributionStatus = $this->add('select', 'contribution_status_id',
      ts('Status'),
      array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::contributionStatus(),
      TRUE,
      NULL
    );

    // Get contact memberships
    $contactMembershipsList = $contactMemberships = array();
    $memParams = array('contact_id' => $this->_contactID);
    CRM_Member_BAO_Membership::getValues($memParams, $contactMembershipsList, TRUE);
    if (count($contactMembershipsList) > 0) {
      foreach ($contactMembershipsList as $memid => $mem) {
        $statusANDType = CRM_Member_BAO_Membership::getStatusANDTypeValues($memid);
        $contactMemberships[$memid] = $statusANDType[$memid]['membership_type']
                                 . ' / ' . $statusANDType[$memid]['status']
                                 . ' / ' . $mem['start_date']
                                 . ' / ' . $mem['end_date'];
      }
    }

    if ($this->_action == 1) {
      $memberships = $this->add('select', 'membership_id',
        ts('Membership'),
        array('' => ts('- select -')) + $contactMemberships,
        FALSE,
        NULL
      );
    }

    $totalAmount = $this->addMoney('amount',
      ts('Amount'),
      TRUE,
      NULL,
      TRUE, 'currency', NULL, FALSE
    );

    $paymentInstrument = $this->add('select', 'payment_instrument_id',
      ts('Paid By'),
      array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::paymentInstrument(),
      TRUE, NULL
    );

    $frequencyUnit = $this->add('select', 'frequency_unit',
      NULL,
      array('' => ts('- select -')) + CRM_Core_OptionGroup::values('recur_frequency_units', FALSE, FALSE, FALSE, NULL, 'name'),
      TRUE, NULL
    );

    $frequencyInterval = $this->add('text', 'frequency_interval', ts('Every'), array('maxlength' => 2, 'size' => 2), TRUE);

    // add dates
    $this->addDateTime('start_date', ts('Start Date'), TRUE, array('formatType' => 'activityDateTime'));
    $this->addDateTime('end_date', ts('End Date'), FALSE, array('formatType' => 'activityDateTime'));
    $this->addDateTime('cancel_date', ts('Cancel Date'), FALSE, array('formatType' => 'activityDateTime'));
    $this->addDateTime('next_sched_contribution_date', ts('Next Scheduled Contribution Date'), FALSE, array('formatType' => 'activityDateTime'));

    $cycleDay = $this->add('text', 'cycle_day', ts('Cycle day'), array('maxlength' => 2, 'size' => 2), TRUE);

    // Move recurring record to another contact/membership
    // Field for moving contribution to another contact/membership
    if (!empty($this->_id)) {
      $this->addEntityRef('contact_id', ts('Contact'), array('create' => TRUE, 'api' => array('extra' => array('email'))), TRUE);
      $this->addElement('text', 'contact_name', 'Contact', array('size' => 50, 'maxlength' => 255));
      $this->addElement('hidden', 'selected_cid', 'selected_cid');
      $this->addElement('checkbox', 'move_recurring_record', ts('Move Recurring Record?'));
      $this->addElement('checkbox', 'move_existing_contributions', ts('Move Existing Contributions?'));

      // Get memberships of the contact
      // This will allow the recur record to be attached to a different membership of the same contact
      $existingMemberships = array('' => ts('- select -')) + $contactMemberships;
      // Remove current membership during move
      if ($existingMemberships[$this->_membershipID]) {
        unset($existingMemberships[$this->_membershipID]);
      }
      $this->add('select', 'membership_record', ts('Membership'), $existingMemberships, FALSE);
      $this->assign('show_move_membership_field', 1);
    }

    // build associated contributions
    $associatedContributions = array();
    $contributions = new CRM_Contribute_DAO_Contribution();
    $contributions->contribution_recur_id = $this->_id;
    while ($contributions->find(TRUE)) {
      $associatedContributions[$contributions->id]['total_amount'] = $contributions->total_amount;
      $associatedContributions[$contributions->id]['financial_type'] = CRM_Contribute_PseudoConstant::financialType($contributions->financial_type_id);
      $associatedContributions[$contributions->id]['contribution_source'] = $contributions->source;
      $associatedContributions[$contributions->id]['receive_date'] = $contributions->receive_date;
      $associatedContributions[$contributions->id]['contribution_status'] = CRM_Contribute_PseudoConstant::contributionStatus($contributions->contribution_status_id);
    }
    $this->assign('associatedContributions', $associatedContributions);

    $this->addButtons(array(
        array(
          'type' => 'upload',
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
   * global validation rules for the form
   *
   * @param array $values posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  public static function formRule($values) {
    $errors = array();

    if (!empty($values['start_date']) && !empty($values['end_date'])) {
      $start = CRM_Utils_Date::processDate($values['start_date']);
      $end   = CRM_Utils_Date::processDate($values['end_date']);
      if (($end < $start) && ($end != 0)) {
        $errors['end_date'] = ts('End date should be greater than Start date');
      }
    }
    return $errors;
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   * @return None
   */
  public function postProcess() {

    $session = CRM_Core_Session::singleton();
    if ($this->_action & CRM_Core_Action::DELETE) {
      // Delete the linked contributions
      $contribution = new CRM_Contribute_DAO_Contribution();
      $contribution->contribution_recur_id = $this->_id;
      $contribution->find();
      while ($contribution->fetch()) {
        CRM_Contribute_BAO_Contribution::deleteContribution($contribution->id);
      }

      // Delete recurring contribution record
      CRM_Contribute_BAO_ContributionRecur::deleteRecurContribution($this->_id);
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view',
        "reset=1&cid={$this->_contactID}&selectedChild=contribute-recur"
      ));
      return NULL;
    }

    // get the submitted form values.
    $submittedValues = $this->controller->exportValues($this->_name);

    // get the required field value only.
    $formValues = $submittedValues;
    $ids = array();

    if ($this->_contactID) {
      $params['contact_id'] = $this->_contactID;
    }

    $params['currency'] = CRM_Contribute_Form_AbstractEditPayment::getCurrency($submittedValues);

    $dates = array(
      'start_date',
      'end_date',
      'cancel_date',
      'next_sched_contribution_date',
    );

    foreach ($dates as $d) {
      $params[$d] = CRM_Utils_Date::processDate($formValues[$d], $formValues[$d . '_time'], TRUE);
    }

    if (empty($this->_id)) {
      $params['create_date'] = CRM_Utils_Date::processDate(date('Y-m-d'));
    }

    if ($this->_action & CRM_Core_Action::ADD) {
      $params['next_sched_contribution_date'] = $params['start_date'];
    }
    $fields = array(
      'payment_processor_id',
      'processor_id',
      'trxn_id',
      'financial_type_id',
      'amount',
      'payment_instrument_id',
      'frequency_interval',
      'frequency_unit',
      'cycle_day',
      'contribution_status_id',
    );
    foreach ($fields as $f) {
      $params[$f] = CRM_Utils_Array::value($f, $formValues);
    }

    $params['id'] = $this->_id;

    // build custom data getFields array
    $customFields = CRM_Core_BAO_CustomField::getFields('ContributionRecur', FALSE, FALSE, NULL, NULL, TRUE);
    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($_POST,
      $this->_id,
      'ContributionRecur'
    );

    $contributionRecur = CRM_Contribute_BAO_ContributionRecur::create($params);

    // Link the recurring contribution with membership record, if selected
    if ($this->_action == 1 && !empty($formValues['membership_id'])) {
      civicrm_api3('Membership', 'create', array(
        'id' => $formValues['membership_id'],
        'contribution_recur_id' => $contributionRecur->id,
      ));
    }

    // Move the recurring record
    if (isset($submittedValues['move_recurring_record']) && $submittedValues['move_recurring_record'] == 1) {
      self::moveRecurringRecord($submittedValues);
    }

    $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view',
      "reset=1&cid={$this->_contactID}&selectedChild=contribute-recur"
    ));
  }

  public function displayStatusMessage($messageTitle, $message) {
    $out = array(
      'status' => 'fatal',
      'content' => '<div class="messages status no-popup"><div class="icon inform-icon"></div>' . ts($message) . '</div>',
    );
    CRM_Core_Session::setStatus($message, ts($messageTitle), 'error');
    CRM_Core_Page_AJAX::returnJsonResponse($out);
  }

  public function moveRecurringRecord($submittedValues) {
    // Move recurring record to another contact
    if (!empty($submittedValues['contact_id']) && $submittedValues['contact_id'] != $this->_contactID) {

      $selected_cid = $submittedValues['contact_id'];

      // FIXME: Not getting the below value in $submittedValues
      // So taking the value from $_POST
      if (isset($_POST['membership_record'])) {
        $membership_record = $_POST['membership_record'];
      }

      // Update contact id in civicrm_contribution_recur table
      $recurring = new CRM_Contribute_BAO_ContributionRecur();
      $recurring->id = $this->_id;
      if ($recurring->find(TRUE)) {
        $recurParams = (array) $recurring;
        $recurParams['contact_id'] = $selected_cid;
        CRM_Contribute_BAO_ContributionRecur::create($recurParams);
      }

      // Update contact id in civicrm_contribution table, if 'Move Existing Contributions?' is ticked
      if (isset($submittedValues['move_existing_contributions']) && $submittedValues['move_existing_contributions'] == 1) {
        $contribution = new CRM_Contribute_DAO_Contribution();
        $contribution->contribution_recur_id = $this->_id;
        $contribution->find();
        while ($contribution->fetch()) {
          $contributionParams = (array) $contribution;
          $contributionParams['contact_id'] = $selected_cid;
          // Update contact_id of contributions
          // related to the recurring contribution
          CRM_Contribute_BAO_Contribution::create($contributionParams);
        }
      }
    }

    if (!empty($membership_record)) {
      // Remove the contribution_recur_id from existing membership
      if (!empty($this->_membershipID)) {
        $membership = new CRM_Member_DAO_Membership();
        $membership->id = $this->_membershipID;
        if ($membership->find(TRUE)) {
          $membershipParams = (array) $membership;
          $membershipParams['contribution_recur_id'] = 'NULL';
          CRM_Member_BAO_Membership::add($membershipParams);
        }
      }

      // Update contribution_recur_id to the new membership
      $membership = new CRM_Member_DAO_Membership();
      $membership->id = $membership_record;
      if ($membership->find(TRUE)) {
        $membershipParams = (array) $membership;
        $membershipParams['contribution_recur_id'] = $this->_id;
        CRM_Member_BAO_Membership::add($membershipParams);
      }
    }
  }

}
