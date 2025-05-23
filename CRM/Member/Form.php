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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Base class for offline membership / membership type / membership renewal and membership status forms
 *
 */
class CRM_Member_Form extends CRM_Contribute_Form_AbstractEditPayment {
  use CRM_Custom_Form_CustomDataTrait;
  use CRM_Core_Form_EntityFormTrait;
  use CRM_Member_Form_MembershipFormTrait;

  /**
   * Membership created or edited on this form.
   *
   * If a price set creates multiple this will be the last one created.
   *
   * This 'last' bias reflects historical code - but it's mostly used in the receipt
   * and there is all sorts of weird and wonderful handling that potentially compensates.
   *
   * @var array
   */
  protected $membership = [];

  /**
   * Membership Type ID
   * @var int
   */
  protected $_memType;

  /**
   * IDs of relevant entities.
   *
   * @var array
   */
  protected $ids = [];

  /**
   * Array of from email ids
   * @var array
   */
  protected $_fromEmails = [];

  /**
   * Details of all enabled membership types.
   *
   * @var array
   */
  protected $allMembershipTypeDetails = [];

  /**
   * Array of membership type IDs and whether they permit auto-renewal.
   *
   * @var array
   */
  protected $membershipTypeRenewalStatus = [];

  /**
   * Price set ID configured for the form.
   *
   * @var int
   *
   * @deprecated use getPriceSetID()
   */
  public $_priceSetId;

  /**
   * Price set details as an array.
   *
   * @var array
   */
  public $_priceSet;

  /**
   * The order being processed.
   *
   * @var \CRM_Financial_BAO_Order
   */
  protected $order;

  /**
   * This string is the used for passing to the buildAmount hook.
   *
   * @var string
   */
  protected $formContext = 'membership';

  /**
   * Array of the payment fields to be displayed in the payment fieldset (pane) in billingBlock.tpl
   * this contains all the information to describe these fields from QuickForm. See CRM_Core_Form_Payment getPaymentFormFieldsMetadata
   *
   * @var array
   */
  public $_paymentFields = [];

  /**
   * Display name of the member.
   *
   * @var string
   */
  protected $_memberDisplayName;

  /**
   * email of the person paying for the membership (used for receipts)
   * @var string
   */
  protected $_memberEmail;

  protected $_recurMembershipTypes;

  /**
   * Keep a class variable for ALL membership IDs so
   * postProcess hook function can do something with it
   *
   * @var array
   */
  protected $_membershipIDs = [];

  /**
   * Display name of the person paying for the membership (used for receipts)
   *
   * @var string
   */
  protected $_contributorDisplayName;

  /**
   * Email of the person paying for the membership (used for receipts).
   *
   * @var string
   */
  protected $_contributorEmail;

  /**
   * email of the person paying for the membership (used for receipts)
   *
   * @var int
   */
  protected $_contributorContactID;

  /**
   * ID of the person the receipt is to go to.
   *
   * @var int
   */
  protected $_receiptContactId;

  /**
   * @return string
   */
  public function getFormContext(): string {
    return $this->formContext;
  }

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity(): string {
    return 'Membership';
  }

  /**
   * @var array
   */
  protected $statusMessage = [];

  /**
   * Add to the status message.
   *
   * @param string $message
   */
  protected function addStatusMessage(string $message): void {
    $this->statusMessage[] = $message;
  }

  /**
   * Get the status message.
   *
   * @return string
   */
  protected function getStatusMessage(): string {
    return implode(' ', $this->statusMessage);
  }

  /**
   * Values submitted to the form, processed along the way.
   *
   * @var array
   */
  protected $_params = [];

  public function preProcess() {
    // Check for edit permission.
    if (!CRM_Core_Permission::checkActionPermission('CiviMember', $this->_action)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }
    if (!CRM_Member_BAO_Membership::statusAvailabilty()) {
      // all possible statuses are disabled - redirect back to contact form
      CRM_Core_Error::statusBounce(ts('There are no configured membership statuses. You cannot add this membership until your membership statuses are correctly configured'));
    }
    // This should be overwritten from the contribution....
    $this->assign('currency', \Civi::settings()->get('defaultCurrency'));
    parent::preProcess();
    $params = [];
    $params['context'] = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this, FALSE, 'membership');
    $params['id'] = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $params['mode'] = CRM_Utils_Request::retrieve('mode', 'Alphanumeric', $this);

    $this->setContextVariables($params);

    $this->assign('context', $this->_context);
    $this->assign('membershipMode', $this->_mode);
    $this->assign('newCredit', CRM_Core_Config::isEnabledBackOfficeCreditCardPayments());
    $this->allMembershipTypeDetails = CRM_Member_BAO_Membership::buildMembershipTypeValues($this, [], TRUE);
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
   * @throws \CRM_Core_Exception
   */
  public function setDefaultValues() {
    $defaults = [];
    if (isset($this->_id)) {
      $params = ['id' => $this->_id];
      CRM_Member_BAO_Membership::retrieve($params, $defaults);
      if (isset($defaults['minimum_fee'])) {
        $defaults['minimum_fee'] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($defaults['minimum_fee']);
      }

      if (isset($defaults['status'])) {
        $this->assign('membershipStatus', $defaults['status']);
      }

      if (!empty($defaults['is_override'])) {
        $defaults['is_override'] = CRM_Member_StatusOverrideTypes::PERMANENT;
        if (!empty($defaults['status_override_end_date'])) {
          $defaults['is_override'] = CRM_Member_StatusOverrideTypes::UNTIL_DATE;
        }
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
      $defaults['membership_type_id'] = [];
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
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    $this->assignSalesTaxMetadataToTemplate();

    $this->addPaymentProcessorSelect(TRUE, FALSE, TRUE);
    CRM_Core_Payment_Form::buildPaymentForm($this, $this->_paymentProcessor, FALSE, TRUE, $this->getDefaultPaymentInstrumentId());
    $this->assign('recurProcessor', json_encode($this->_recurPaymentProcessors));
    // Build the form for auto renew. This is displayed when in credit card mode or update mode.
    // The reason for showing it in update mode is not that clear.
    $this->assign('allowAutoRenew', $this->_mode && !empty($this->_recurPaymentProcessors));
    if ($this->_mode || ($this->_action & CRM_Core_Action::UPDATE)) {

      $autoRenewElement = $this->addElement('checkbox', 'auto_renew', ts('Membership renewed automatically'),
        NULL, ['onclick' => "showHideByValue('auto_renew','','send-receipt','table-row','radio',true); showHideNotice( );"]
      );
      if ($this->_action & CRM_Core_Action::UPDATE) {
        $autoRenewElement->freeze();
      }

      $this->addElement('checkbox',
        'auto_renew',
        ts('Membership renewed automatically')
      );

    }
    $this->assign('autoRenewOptions', json_encode($this->membershipTypeRenewalStatus));

    if ($this->_action & CRM_Core_Action::RENEW) {
      $this->addButtons([
        [
          'type' => 'upload',
          'name' => ts('Renew'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
    }
    elseif ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons([
        [
          'type' => 'next',
          'name' => ts('Delete'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
    }
    else {
      $buttons = [
        [
          'type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ],
      ];
      if (!$this->_id) {
        $buttons[] = [
          'type' => 'upload',
          'name' => ts('Save and New'),
          'subName' => 'new',
        ];
      };
      $buttons[] = [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ];
      $this->addButtons($buttons);
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
  public function storeContactFields(array $formValues): void {
    // in a 'standalone form' (contact id not in the url) the contact will be in the form values
    if (!empty($formValues['contact_id'])) {
      $this->_contactID = $formValues['contact_id'];
    }

    [$this->_memberDisplayName, $this->_memberEmail] = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactID);
    $this->_contributorContactID = $this->getContributionContactID();
    //CRM-10375 Where the payer differs to the member the payer should get the email.
    // here we store details in order to do that
    if (!empty($formValues['soft_credit_contact_id'])) {
      $this->_receiptContactId = $formValues['soft_credit_contact_id'];
      [$this->_contributorDisplayName, $this->_contributorEmail] = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contributorContactID);
    }
    else {
      $this->_receiptContactId = $this->_contactID;
      $this->_contributorDisplayName = $this->_memberDisplayName;
      $this->_contributorEmail = $this->_memberEmail;
    }
  }

  /**
   * Get the contact id for the contribution.
   *
   * @return int
   */
  protected function getContributionContactID(): int {
    return (int) ($this->getSubmittedValue('soft_credit_contact_id') ?: $this->getSubmittedValue('contact_id'));
  }

  /**
   * Get the contact id for the contribution.
   *
   * @return int
   */
  protected function getMembershipContactID(): int {
    // It's not clear that $this->_contactID *could* be set outside
    // tests when contact_id is not submitted - so this fallback
    // is precautionary in order to be similar to past behaviour.
    return (int) ($this->getSubmittedValue('contact_id') ?: $this->_contactID);
  }

  /**
   * Get the membership ID.
   *
   * For new memberships this may initially be NULL.
   *
   * @return int
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   */
  public function getMembershipID(): ?int {
    return $this->_id;
  }

  /**
   * Set variables in a way that can be accessed from different places.
   *
   * This is part of refactoring for unit testability on the submit function.
   *
   * @param array $params
   *
   * @throws \CRM_Core_Exception
   */
  protected function setContextVariables(array $params): void {
    $variables = [
      'action' => '_action',
      'context' => '_context',
      'id' => '_id',
      'cid' => '_contactID',
      'mode' => '_mode',
    ];
    foreach ($variables as $paramKey => $classVar) {
      if (isset($params[$paramKey]) && !isset($this->$classVar)) {
        $this->$classVar = $params[$paramKey];
      }
    }

    if ($this->_id) {
      $this->_memType = $this->getMembershipValue('membership_type_id');
      $this->_membershipIDs[] = $this->_id;
    }
    $this->_fromEmails = CRM_Core_BAO_Email::getFromEmail();
  }

  /**
   * Create a recurring contribution record.
   *
   * @param array $contributionRecurParams
   *
   * @param int $membershipTypeID
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function processRecurringContribution(array $contributionRecurParams, int $membershipTypeID): array {

    $mapping = [
      'frequency_interval' => 'duration_interval',
      'frequency_unit' => 'duration_unit',
    ];
    $membershipType = civicrm_api3('MembershipType', 'getsingle', [
      'id' => $membershipTypeID,
      'return' => $mapping,
    ]);

    $returnParams = ['is_recur' => TRUE];
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
  protected function ensurePriceParamsAreSet(array &$formValues): void {
    foreach ($formValues as $key => $value) {
      if ((str_starts_with($key, 'price_')) && is_numeric(substr($key, 6))) {
        return;
      }
    }
    $priceFields = CRM_Member_BAO_Membership::setQuickConfigMembershipParameters(
      $formValues['membership_type_id'][0],
      $formValues['membership_type_id'][1],
      $formValues['total_amount'] ?? NULL,
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
  protected function getPriceSetDetails(array $params): ?array {
    $priceSetID = $params['price_set_id'] ?? NULL;
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
   * Get the price set ID.
   *
   * @api Supported for external use.
   *
   * @return int
   */
  public function getPriceSetID(): int {
    $this->_priceSetId = $this->isAjaxOverLoadMode() ? CRM_Utils_Request::retrieve('priceSetId', 'Integer') : ($this->getSubmittedValue('price_set_id') ?? NULL);
    if (!$this->_priceSetId) {
      $priceSet = CRM_Price_BAO_PriceSet::getDefaultPriceSet('membership');
      $priceSet = reset($priceSet);
      $this->_priceSetId = (int) $priceSet['setID'];
    }
    return (int) $this->_priceSetId;
  }

  /**
   * Is the form being called in ajax overload mode.
   *
   * Ajax overload mode is what the form is called via ajax to render the price
   * form, without the rest of the processing. This is a legacy of a time when
   * we didn't have better ways to do this. We only render the price set form
   * on overload mode, but we need to ensure the fields are added to the
   * form when the form is submitted so QuickForm sees the fields. Over time
   * we have broken this approach for the payment form and custom data form
   * and may do so here one day....
   *
   * @return bool
   */
  protected function isAjaxOverLoadMode(): bool {
    return !empty($_GET['priceSetId']);
  }

  /**
   * Store parameters relating to price sets.
   *
   * @param array $formValues
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function setPriceSetParameters(array $formValues): array {
    // process price set and get total amount and line items.
    $this->getPriceSetID();
    $this->ensurePriceParamsAreSet($formValues);
    $priceSetDetails = $this->getPriceSetDetails($formValues);
    $this->_priceSet = $priceSetDetails[$this->_priceSetId];
    $this->order = new CRM_Financial_BAO_Order();
    $this->order->setForm($this);
    $this->order->setPriceSelectionFromUnfilteredInput($formValues);

    if ($this->getSubmittedValue('total_amount')) {
      $this->order->setOverrideTotalAmount((float) $this->getSubmittedValue('total_amount'));
    }

    if ($this->isQuickConfig() && $this->getSubmittedValue('financial_type_id')) {
      $this->order->setOverrideFinancialTypeID((int) $this->getSubmittedValue('financial_type_id'));
    }

    return $formValues;
  }

  /**
   * Is the price set quick config.
   *
   * @return bool
   */
  protected function isQuickConfig(): bool {
    return $this->getPriceSetID() && CRM_Price_BAO_PriceSet::isQuickConfig($this->getPriceSetID());
  }

  /**
   * Overriding this entity trait function as the function does not
   * at this stage use the CustomDataTrait which works better with php8.2.
   */
  public function addCustomDataToForm() {
    if ($this->isSubmitted()) {
      // The custom data fields are added to the form by an ajax form.
      // However, if they are not present in the element index they will
      // not be available from `$this->getSubmittedValue()` in post process.
      // We do not have to set defaults or otherwise render - just add to the element index.
      $this->addCustomDataFieldsToForm('Membership', array_filter([
        'id' => $this->getMembershipID(),
        'membership_type_id' => $this->getSubmittedValue('membership_type_id'),
      ]));
    }
  }

  /**
   * Wrapper function for unit tests.
   *
   * @param array $formValues
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmit(array $formValues = []): void {
    if (empty($formValues)) {
      // If getForm is used these will be set - this is now
      // preferred.
      $formValues = $this->controller->exportValues($this->_name);
    }
    $this->exportedValues = $formValues;
    $this->setContextVariables($formValues);
    $this->_memType = !empty($formValues['membership_type_id']) ? $formValues['membership_type_id'][1] : NULL;
    $this->_params = $formValues;
    $this->submit();
  }

  /**
   * Get order related params.
   *
   * In practice these are contribution params but later they cann be used with the Order api.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getOrderParams(): array {
    return [
      'lineItems' => [$this->_priceSetId => $this->order->getLineItems()],
      // This is one of those weird & wonderful legacy params we aim to get rid of.
      'processPriceSet' => TRUE,
      'tax_amount' => $this->order->getTotalTaxAmount(),
    ];
  }

  /**
   * Get the relevant payment instrument id.
   *
   * @return int
   */
  protected function getPaymentInstrumentID(): int {
    return (int) $this->getSubmittedValue('payment_instrument_id') ?: $this->_paymentProcessor['object']->getPaymentInstrumentID();
  }

  /**
   * Get the last 4 numbers of the card.
   *
   * @return int|null
   */
  protected function getPanTruncation(): ?int {
    $card = $this->getSubmittedValue('credit_card_number');
    return $card ? (int) substr($card, -4) : NULL;
  }

  /**
   * Get the card_type_id.
   *
   * This value is the integer representing the option value for
   * the credit card type (visa, mastercard). It is stored as part of the
   * payment record in civicrm_financial_trxn.
   *
   * @return int|null
   */
  protected function getCardTypeID(): ?int {
    return CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_FinancialTrxn', 'card_type_id', $this->getSubmittedValue('credit_card_type'));
  }

}
