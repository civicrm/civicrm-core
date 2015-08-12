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
 */

/**
 * Base class for offline membership / membership type / membership renewal and membership status forms
 *
 */
class CRM_Member_Form extends CRM_Contribute_Form_AbstractEditPayment {

  /**
   * The id of the object being edited / created
   *
   * @var int
   */
  public $_id;

  /**
   * Membership Type ID
   * @var
   */
  protected $_memType;

  /**
   * Array of from email ids
   * @var array
   */
  protected $_fromEmails = array();

  /**
   * Details of all enabled membership types.
   *
   * @var array
   */
  protected $allMembershipTypeDetails = array();

  /**
   * Array of membership type IDs and whether they permit autorenewal.
   *
   * @var array
   */
  protected $membershipTypeRenewalStatus = array();

  /**
   * Price set ID configured for the form.
   *
   * @var int
   */
  public $_priceSetId;

  /**
   * Price set details as an array.
   *
   * @var array
   */
  public $_priceSet;

  public function preProcess() {
    // Check for edit permission.
    if (!CRM_Core_Permission::checkActionPermission('CiviMember', $this->_action)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
    }
    parent::preProcess();
    $params = array();
    $params['context'] = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'membership');
    $params['id'] = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $params['mode'] = CRM_Utils_Request::retrieve('mode', 'String', $this);

    $this->setContextVariables($params);

    $this->assign('context', $this->_context);
    $this->assign('membershipMode', $this->_mode);
    $this->allMembershipTypeDetails = CRM_Member_BAO_Membership::buildMembershipTypeValues($this, array(), TRUE);
    foreach ($this->allMembershipTypeDetails as $index => $membershipType) {
      if ($membershipType['auto_renew']) {
        $this->_recurMembershipTypes[$index] = $membershipType;
        $this->membershipTypeRenewalStatus[$index] = $membershipType['auto_renew'];
      }
    }
  }

  /**
   * Set default values for the form. MobileProvider that in edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @return array
   *   defaults
   */
  public function setDefaultValues() {
    $defaults = array();
    if (isset($this->_id)) {
      $params = array('id' => $this->_id);
      CRM_Member_BAO_Membership::retrieve($params, $defaults);
      if (isset($defaults['minimum_fee'])) {
        $defaults['minimum_fee'] = CRM_Utils_Money::format($defaults['minimum_fee'], NULL, '%a');
      }

      if (isset($defaults['status'])) {
        $this->assign('membershipStatus', $defaults['status']);
      }
    }

    if ($this->_action & CRM_Core_Action::ADD) {
      $defaults['is_active'] = 1;
    }

    if (isset($defaults['member_of_contact_id']) &&
      $defaults['member_of_contact_id']
    ) {
      $defaults['member_org'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
        $defaults['member_of_contact_id'], 'display_name'
      );
    }
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    if ($this->_mode) {
      $this->add('select', 'payment_processor_id',
        ts('Payment Processor'),
        $this->_processors, TRUE,
        array('onChange' => "buildAutoRenew( null, this.value );")
      );
      CRM_Core_Payment_Form::buildPaymentForm($this, $this->_paymentProcessor, FALSE, TRUE);
    }
    // Build the form for auto renew. This is displayed when in credit card mode or update mode.
    // The reason for showing it in update mode is not that clear.
    if ($this->_mode || ($this->_action & CRM_Core_Action::UPDATE)) {
      if (!empty($this->_recurPaymentProcessors)) {
        $this->assign('allowAutoRenew', TRUE);
      }

      $autoRenewElement = $this->addElement('checkbox', 'auto_renew', ts('Membership renewed automatically'),
        NULL, array('onclick' => "showHideByValue('auto_renew','','send-receipt','table-row','radio',true); showHideNotice( );")
      );
      if ($this->_action & CRM_Core_Action::UPDATE) {
        $autoRenewElement->freeze();
      }

      $this->assign('recurProcessor', json_encode($this->_recurPaymentProcessors));
      $this->addElement('checkbox',
        'auto_renew',
        ts('Membership renewed automatically'),
        NULL,
        array('onclick' => "buildReceiptANDNotice( );")
      );

      $this->assignPaymentRelatedVariables();
    }
    $this->assign('autoRenewOptions', json_encode($this->membershipTypeRenewalStatus));

    if ($this->_action & CRM_Core_Action::RENEW) {
      $this->addButtons(array(
          array(
            'type' => 'upload',
            'name' => ts('Renew'),
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
    }
    elseif ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons(array(
          array(
            'type' => 'next',
            'name' => ts('Delete'),
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
    }
    else {
      $this->addButtons(array(
          array(
            'type' => 'upload',
            'name' => ts('Save'),
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'upload',
            'name' => ts('Save and New'),
            'subName' => 'new',
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
    }
  }

  /**
   * Extract values from the contact create boxes on the form and assign appropriately  to
   *
   *  - $this->_contributorEmail,
   *  - $this->_memberEmail &
   *  - $this->_contributionName
   *  - $this->_memberName
   *  - $this->_contactID (effectively memberContactId but changing might have spin-off effects)
   *  - $this->_contributorContactId - id of the contributor
   *  - $this->_receiptContactId
   *
   * If the member & contributor are the same then the values will be the same. But if different people paid
   * then they weill differ
   *
   * @param array $formValues
   *   values from form. The important values we are looking for are.
   *  - contact_id
   *  - soft_credit_contact_id
   */
  public function storeContactFields($formValues) {
    // in a 'standalone form' (contact id not in the url) the contact will be in the form values
    if (!empty($formValues['contact_id'])) {
      $this->_contactID = $formValues['contact_id'];
    }

    list($this->_memberDisplayName,
      $this->_memberEmail
      ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactID);

    //CRM-10375 Where the payer differs to the member the payer should get the email.
    // here we store details in order to do that
    if (!empty($formValues['soft_credit_contact_id'])) {
      $this->_receiptContactId = $this->_contributorContactID = $formValues['soft_credit_contact_id'];
      list($this->_contributorDisplayName,
        $this->_contributorEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contributorContactID);
    }
    else {
      $this->_receiptContactId = $this->_contributorContactID = $this->_contactID;
      $this->_contributorDisplayName = $this->_memberDisplayName;
      $this->_contributorEmail = $this->_memberEmail;
    }
  }

  protected function setContextVariables($params) {
    $variables = array(
      'action' => '_action',
      'context' => '_context',
      'id' => '_id',
      'cid' => '_contactID',
      'mode' => '_mode',
    );
    foreach ($variables as $paramKey => $classVar) {
      if (isset($params[$paramKey]) && !isset($this->$classVar)) {
        $this->$classVar = $params[$paramKey];
      }
    }

    if ($this->_mode) {
      $this->assignPaymentRelatedVariables();
    }

    if ($this->_id) {
      $this->_memType = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $this->_id, 'membership_type_id');
      $this->_membershipIDs[] = $this->_id;
    }
    $this->_fromEmails = CRM_Core_BAO_Email::getFromEmail();
  }

  /**
   * Create a recurring contribution record.
   *
   * Recurring contribution parameters are set explicitly rather than merging paymentParams because it's hard
   * to know the downstream impacts if we keep passing around the same array.
   *
   * @param $paymentParams
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  protected function processRecurringContribution($paymentParams) {
    $membershipID = $paymentParams['membership_type_id'][1];
    $contributionRecurParams = array(
      'contact_id' => $paymentParams['contactID'],
      'amount' => $paymentParams['total_amount'],
      'payment_processor_id' => $paymentParams['payment_processor_id'],
      'campaign_id' => CRM_Utils_Array::value('campaign_id', $paymentParams),
      'financial_type_id' => $paymentParams['financial_type_id'],
      'is_email_receipt' => CRM_Utils_Array::value('is_email_receipt', $paymentParams),
      // This is not great as it could also be direct debit - but is consistent with elsewhere & all need fixing.
      'payment_instrument_id' => 1,
      'invoice_id' => CRM_Utils_Array::value('invoiceID ', $paymentParams),
    );

    $mapping = array(
      'frequency_interval' => 'duration_interval',
      'frequency_unit' => 'duration_unit',
    );
    $membershipType = civicrm_api3('MembershipType', 'getsingle', array(
      'id' => $membershipID,
      'return' => $mapping,
    ));

    foreach ($mapping as $recurringFieldName => $membershipTypeFieldName) {
      $contributionRecurParams[$recurringFieldName] = $membershipType[$membershipTypeFieldName];
    }

    $contributionRecur = civicrm_api3('ContributionRecur', 'create', $contributionRecurParams);
    $returnParams = array(
      'contributionRecurID' => $contributionRecur['id'],
      'is_recur' => TRUE,
    );
    return $returnParams;
  }

}
