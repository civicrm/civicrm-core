<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
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

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Membership';
  }

  /**
   * Values submitted to the form, processed along the way.
   *
   * @var array
   */
  protected $_params = array();

  public function preProcess() {
    // Check for edit permission.
    if (!CRM_Core_Permission::checkActionPermission('CiviMember', $this->_action)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
    }
    if (!CRM_Member_BAO_Membership::statusAvailabilty()) {
      // all possible statuses are disabled - redirect back to contact form
      CRM_Core_Error::statusBounce(ts('There are no configured membership statuses. You cannot add this membership until your membership statuses are correctly configured'));
    }

    parent::preProcess();
    $params = array();
    $params['context'] = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this, FALSE, 'membership');
    $params['id'] = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $params['mode'] = CRM_Utils_Request::retrieve('mode', 'Alphanumeric', $this);

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

      if (!empty($defaults['is_override'])) {
        $defaults['is_override'] = CRM_Member_StatusOverrideTypes::PERMANENT;
      }
      if (!empty($defaults['status_override_end_date'])) {
        $defaults['is_override'] = CRM_Member_StatusOverrideTypes::UNTIL_DATE;
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
    if (!empty($defaults['membership_type_id'])) {
      $this->_memType = $defaults['membership_type_id'];
    }
    if (is_numeric($this->_memType)) {
      $defaults['membership_type_id'] = array();
      $defaults['membership_type_id'][0] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
        $this->_memType,
        'member_of_contact_id',
        'id'
      );
      $defaults['membership_type_id'][1] = $this->_memType;
    }
    else {
      $defaults['membership_type_id'] = $this->_memType;
    }
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    $this->addPaymentProcessorSelect(TRUE, FALSE, TRUE);
    CRM_Core_Payment_Form::buildPaymentForm($this, $this->_paymentProcessor, FALSE, TRUE, $this->getDefaultPaymentInstrumentId());
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
        ts('Membership renewed automatically')
      );

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
      ));
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
      ));
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
      ));
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

  /**
   * Set variables in a way that can be accessed from different places.
   *
   * This is part of refactoring for unit testability on the submit function.
   *
   * @param array $params
   */
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
      'contribution_status_id' => 'Pending',
      'payment_processor_id' => $paymentParams['payment_processor_id'],
      'campaign_id' => $paymentParams['campaign_id'],
      'financial_type_id' => $paymentParams['financial_type_id'],
      'is_email_receipt' => $paymentParams['is_email_receipt'],
      'payment_instrument_id' => $paymentParams['payment_instrument_id'],
      'invoice_id' => $paymentParams['invoice_id'],
    );

    $mapping = array(
      'frequency_interval' => 'duration_interval',
      'frequency_unit' => 'duration_unit',
    );
    $membershipType = civicrm_api3('MembershipType', 'getsingle', array(
      'id' => $membershipID,
      'return' => $mapping,
    ));

    $returnParams = array('is_recur' => TRUE);
    foreach ($mapping as $recurringFieldName => $membershipTypeFieldName) {
      $contributionRecurParams[$recurringFieldName] = $membershipType[$membershipTypeFieldName];
      $returnParams[$recurringFieldName] = $membershipType[$membershipTypeFieldName];
    }

    $contributionRecur = civicrm_api3('ContributionRecur', 'create', $contributionRecurParams);
    $returnParams['contributionRecurID'] = $contributionRecur['id'];
    return $returnParams;
  }

  /**
   * Ensure price parameters are set.
   *
   * If they are not set it means a quick config option has been chosen so we
   * fill them in here to make the two flows the same. They look like 'price_2' => 2 etc.
   *
   * @param array $formValues
   */
  protected function ensurePriceParamsAreSet(&$formValues) {
    foreach ($formValues as $key => $value) {
      if ((substr($key, 0, 6) == 'price_') && is_numeric(substr($key, 6))) {
        return;
      }
    }
    $priceFields = CRM_Member_BAO_Membership::setQuickConfigMembershipParameters(
      $formValues['membership_type_id'][0],
      $formValues['membership_type_id'][1],
      CRM_Utils_Array::value('total_amount', $formValues),
      $this->_priceSetId
    );
    $formValues = array_merge($formValues, $priceFields['price_fields']);
  }

  /**
   * Get the details for the selected price set.
   *
   * @param array $params
   *   Parameters submitted to the form.
   *
   * @return array
   */
  protected static function getPriceSetDetails($params) {
    $priceSetID = CRM_Utils_Array::value('price_set_id', $params);
    if ($priceSetID) {
      return CRM_Price_BAO_PriceSet::getSetDetail($priceSetID);
    }
    else {
      $priceSet = CRM_Price_BAO_PriceSet::getDefaultPriceSet('membership');
      $priceSet = reset($priceSet);
      return CRM_Price_BAO_PriceSet::getSetDetail($priceSet['setID']);
    }
  }

  /**
   * Get the selected price set id.
   *
   * @param array $params
   *   Parameters submitted to the form.
   *
   * @return int
   */
  protected static function getPriceSetID($params) {
    $priceSetID = CRM_Utils_Array::value('price_set_id', $params);
    if (!$priceSetID) {
      $priceSetDetails = self::getPriceSetDetails($params);
      return key($priceSetDetails);
    }
    return $priceSetID;
  }

  /**
   * Store parameters relating to price sets.
   *
   * @param array $formValues
   *
   * @return array
   */
  protected function setPriceSetParameters($formValues) {
    $this->_priceSetId = self::getPriceSetID($formValues);
    $priceSetDetails = self::getPriceSetDetails($formValues);
    $this->_priceSet = $priceSetDetails[$this->_priceSetId];
    // process price set and get total amount and line items.
    $this->ensurePriceParamsAreSet($formValues);
    return $formValues;
  }

  /**
   * Wrapper function for unit tests.
   *
   * @param array $formValues
   */
  public function testSubmit($formValues) {
    $this->setContextVariables($formValues);
    $this->_memType = $formValues['membership_type_id'][1];
    $this->_params = $formValues;
    $this->submit();
  }

}
