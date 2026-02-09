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

use Civi\Api4\Contribution;
use Civi\Api4\Pledge;
use Civi\Api4\PledgeBlock;
use Civi\Api4\PremiumsProduct;
use Civi\Api4\PriceSet;

/**
 * This class generates form components for processing a contribution.
 */
class CRM_Contribute_Form_ContributionBase extends CRM_Core_Form {
  use CRM_Financial_Form_FrontEndPaymentFormTrait;
  use CRM_Contribute_Form_ContributeFormTrait;
  use CRM_Financial_Form_PaymentProcessorFormTrait;

  /**
   * The id of the contribution page that we are processing.
   *
   * @var int
   */
  public $_id;

  /**
   * The mode that we are in
   *
   * @var string
   * @protect
   */
  public $_mode;

  /**
   * The contact id related to a membership
   *
   * @var int
   */
  public $_membershipContactID;

  /**
   * The values for the contribution db object
   *
   * @var array
   *
   * @internal - avoid accessing from outside core.
   */
  public $_values;

  /**
   * The paymentProcessor attributes for this page
   *
   * @var array
   */
  public $_paymentProcessor;

  /**
   * Order object, used to calculate amounts, line items etc.
   *
   * @var \CRM_Financial_BAO_Order
   */
  protected $order;

  /**
   * The membership block for this page
   *
   * @var array
   */
  public $_membershipBlock = NULL;

  /**
   * Does this form support a separate membership payment
   *
   * @var bool
   *
   * @deprecated use $this->isSeparateMembershipPayment() function.
   */
  protected $_separateMembershipPayment;

  /**
   * The params submitted by the form and computed by the app
   *
   * @var array
   */
  public $_params = [];

  /**
   * The fields involved in this contribution page
   *
   * @var array
   */
  public $_fields = [];

  /**
   * The billing location id for this contribution page.
   *
   * @var int
   */
  public $_bltID;

  /**
   * Cache the amount to make things easier
   *
   * @var float
   */
  public $_amount;

  /**
   * Pcp id
   *
   * @var int
   *
   * @internal use getPcpID().
   */
  public $_pcpId;

  /**
   * Pcp block
   *
   * @var array
   */
  public $_pcpBlock;

  /**
   * Pcp info
   *
   * @var array
   */
  public $_pcpInfo;

  /**
   * The contact id of the person for whom membership is being added or renewed based on the cid in the url,
   * checksum, or session
   * @var int
   */
  public $_contactID;

  /**
   * Price Set ID, if the new price set method is used
   *
   * @var int
   */
  public $_priceSetId;

  /**
   * Array of fields for the price set
   *
   * @var array
   */
  public $_priceSet;

  public $_action;

  /**
   * Contribution page supports memberships
   * @var bool
   */
  public $_useForMember;

  /**
   * @var bool
   * @deprecated
   */
  public $_isBillingAddressRequiredForPayLater;

  /**
   * Flag if email field exists in embedded profile
   *
   * @var bool
   */
  public $_emailExists = FALSE;

  /**
   * Is this a backoffice form.
   *
   * Processors may display different options to backoffice users.
   *
   * @var bool
   */
  public $isBackOffice = FALSE;

  /**
   * Payment instrument if for the transaction.
   *
   * This will generally be drawn from the payment processor and is ignored for
   * front end forms.
   *
   * @var int
   */
  public $paymentInstrumentID;

  /**
   * The contribution ID - is an option in the URL if you are making a payment against an existing contribution (an
   * "invoice payment").
   *
   * @var int
   */
  public $_ccid;

  /**
   * ID of a membership to be renewed (pass in by url)
   *
   * @var int
   */
  protected $renewalMembershipID;
  private array $membershipTypes;

  /**
   * Entities otherwise accessed through getters.
   *
   * These can't be tracked by the Lookup Trait because it expects them to exist
   * but they might not.
   *
   * @var array
   */
  private array $entities;

  /**
   * Is the price set quick config.
   *
   * @return bool
   */
  public function isQuickConfig(): bool {
    return $this->getPriceSetID() && CRM_Price_BAO_PriceSet::isQuickConfig($this->getPriceSetID());
  }

  /**
   * @return bool
   */
  protected function isEmailReceipt(): bool {
    return (bool) $this->getContributionPageValue('is_email_receipt');
  }

  /**
   * Provide support for extensions that are used to being able to retrieve _lineItem
   *
   * Note extension should call getPriceSetID() and getLineItems() directly.
   * They are supported for external use per the api annotation.
   *
   * @param string $name
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function __get($name) {
    if ($name === '_lineItem') {
      CRM_Core_Error::deprecatedWarning('attempt to access undefined property _lineItem - use externally supported function getLineItems()');
      return [$this->getPriceSetID() => $this->getLineItems()];
    }
    CRM_Core_Error::deprecatedWarning('attempt to access invalid property :' . $name);
  }

  /**
   * Provide support for extensions that are used to being able to retrieve _lineItem
   *
   * Note extension should call getPriceSetID() and getLineItems() directly.
   * They are supported for external use per the api annotation.
   *
   * @param string $name
   * @param mixed $value
   */
  public function __set($name, $value) {
    if ($name === '_lineItem') {
      CRM_Core_Error::deprecatedWarning('attempt to access undefined property _lineItem - use externally supported function setLineItems()');
      $this->order->setLineItems($value[$this->getPriceSetID()]);
      return;
    }
    CRM_Core_Error::deprecatedWarning('attempt to set invalid property :' . $name);
  }

  /**
   * Is the form being submitted in test mode.
   *
   * @api this function is supported for external use.
   *
   * @return bool
   */
  public function isTest(): bool {
    return (bool) ($this->getAction() & CRM_Core_Action::PREVIEW);
  }

  /**
   * Get the price set for the contribution page.
   *
   * Note that we use the `get` from the form as a legacy method but
   * ideally we would just load from the BAO method & not pass using the
   * form. It does not confer meaningful performance benefits & adds confusion.
   *
   * Out of caution we still allow `get`, `set` to take precedence.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @return int|null
   * @throws \CRM_Core_Exception
   */
  public function getPriceSetID(): ?int {
    if ($this->_priceSetId === NULL) {
      if ($this->get('priceSetId')) {
        $this->_priceSetId = $this->get('priceSetId');
      }
      elseif ($this->getExistingContributionID()) {
        $lineItems = $this->getExistingContributionLineItems();
        $firstLineItem = reset($lineItems);
        // If this IF is not true the contribution is messed up! Hopefully this
        // could never happen.
        if ($firstLineItem && !empty($firstLineItem['price_field_id'])) {
          $this->_priceSetId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', $firstLineItem['price_field_id'], 'price_set_id');
        }
      }
      else {
        $this->_priceSetId = CRM_Price_BAO_PriceSet::getFor('civicrm_contribution_page', $this->_id);
      }
      if (!$this->_priceSetId) {
        if ($this->isShowMembershipBlock()) {
          $this->_priceSetId = PriceSet::get(FALSE)
            ->addWhere('name', '=', 'default_membership_type_amount')
            ->execute()
            ->first()['id'];
        }
      }
      $this->set('priceSetId', $this->_priceSetId);
    }
    return $this->_priceSetId ?: NULL;
  }

  /**
   * Get id of contribution page being acted on.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @return int
   */
  public function getContributionPageID(): int {
    if (!$this->_id) {
      /** @noinspection PhpUnhandledExceptionInspection */
      $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
      if (!$this->_id) {
        // seems like the session is corrupted and/or we lost the id trail
        // lets just bump this to a regular session error and redirect user to main page
        $this->controller->invalidKeyRedirect();
      }
    }
    return $this->_id;
  }

  /**
   * Get the selected Pledge Block ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   */
  public function getPledgeBlockID(): ?int {
    if (isset($this->entities['PledgeBlock'])) {
      return $this->entities['PledgeBlock'];
    }
    if ($this->getContributionPageID() && $this->isEntityEnabled('PledgeBlock')) {
      $pledgeBlock = PledgeBlock::get(FALSE)
        ->addWhere('entity_id', '=', $this->getContributionPageID())
        ->addWhere('entity_table', '=', 'civicrm_contribution_page')
        ->execute()->first();
      $this->entities['PledgeBlock'] = $pledgeBlock['id'] ?? FALSE;
      if ($pledgeBlock) {
        $this->define('PledgeBlock', 'PledgeBlock', $pledgeBlock);
      }
    }
    return $this->entities['PledgeBlock'] ?? NULL;
  }

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Contribute_Exception_InactiveContributionPageException
   * @throws \Exception
   */
  public function preProcess() {

    // current contribution page id
    $this->getContributionPageID();
    $this->_ccid = $this->getExistingContributionID();
    $this->_emailExists = $this->get('emailExists') ?? FALSE;
    $this->assign('isShowAdminVisibilityFields', CRM_Core_Permission::check('administer CiviCRM'));

    $this->_contactID = $this->_membershipContactID = $this->getContactID();

    if ($this->getRenewalMembershipID()) {
      $this->defineRenewalMembership();
    }

    // we do not want to display recently viewed items, so turn off
    $this->assign('displayRecent', FALSE);

    // action
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add');
    $this->assign('action', $this->_action);

    // current mode
    $this->_mode = ($this->_action == 1024) ? 'test' : 'live';

    $this->_values = $this->get('values');
    $this->_fields = $this->get('fields');
    $this->_bltID = $this->get('bltID');
    $this->_paymentProcessor = $this->get('paymentProcessor');

    // In tests price set id is not always set - it is unclear if this is just
    // poor test set up or it is possible in 'the real world'
    if ($this->getPriceSetID()) {
      $this->initializeOrder();
    }
    else {
      CRM_Core_Error::deprecatedFunctionWarning('forms require a price set ID');
    }
    $this->_priceSet = $this->get('priceSet');
    $this->assign('quickConfig', $this->isQuickConfig());
    if (!$this->_values) {
      // get all the values from the dao object
      $this->_values = [];
      $this->_fields = [];

      $this->loadContributionPageValues($this->_values);
      if (!$this->getContributionPageValue('is_active')) {
        if ($this->isTest() && CRM_Core_Permission::check('administer CiviCRM')) {
          CRM_Core_Session::setStatus(ts('This page is disabled. It is accessible in test mode to administrators only.'), '', 'alert', ['expires' => 0]);
        }
        else {
          throw new CRM_Contribute_Exception_InactiveContributionPageException(ts('The page you requested is currently unavailable.'), $this->_id);
        }
      }

      $endDate = CRM_Utils_Date::processDate($this->_values['end_date'] ?? NULL);
      $now = date('YmdHis');
      if ($endDate && $endDate < $now) {
        throw new CRM_Contribute_Exception_PastContributionPageException(ts('The page you requested has past its end date on %1', [1 => CRM_Utils_Date::customFormat($endDate)]), $this->_id);
      }

      $startDate = CRM_Utils_Date::processDate($this->_values['start_date'] ?? NULL);
      if ($startDate && $startDate > $now) {
        throw new CRM_Contribute_Exception_FutureContributionPageException(ts('The page you requested will be active from %1', [1 => CRM_Utils_Date::customFormat($startDate)]), $this->_id);
      }

      $this->assignBillingType();

      // check for is_monetary status
      $isPayLater = $this->_values['is_pay_later'] ?? NULL;
      if ($this->getExistingContributionID()) {
        if ($isPayLater) {
          $isPayLater = FALSE;
          $this->_values['is_pay_later'] = FALSE;
        }
      }
      if ($isPayLater) {
        $this->setPayLaterLabel($this->getContributionValue('pay_later_text') ?? '');
      }

      $this->_paymentProcessorIDs = array_filter(explode(
        CRM_Core_DAO::VALUE_SEPARATOR,
        ($this->_values['payment_processor'] ?? '')
      ));

      $this->assignPaymentProcessor($isPayLater);
      // get price info
      // CRM-5095
      $this->initSet($this);

      // this avoids getting E_NOTICE errors in php
      $setNullFields = [
        'is_allow_other_amount',
        'footer_text',
      ];
      foreach ($setNullFields as $f) {
        if (!isset($this->_values[$f])) {
          $this->_values[$f] = NULL;
        }
      }

      if ($this->getPledgeBlockValue('id')) {
        $this->_values['pledge_block_id'] = $this->getPledgeBlockValue('id');
        $this->_values['max_reminders'] = $this->getPledgeBlockValue('max_reminders');
        $this->_values['initial_reminder_day'] = $this->getPledgeBlockValue('initial_reminder_day');
        $this->_values['additional_reminder_day'] = $this->getPledgeBlockValue('additional_reminder_day');

        //authenticate pledge user for pledge payment.
        if ($this->getPledgeID()) {

          //lets override w/ pledge campaign.
          $this->_values['campaign_id'] = CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_Pledge',
            $this->getPledgeID(),
            'campaign_id'
          );
          $this->authenticatePledgeUser();
        }
      }
      $this->set('values', $this->_values);
      $this->set('fields', $this->_fields);
    }
    $this->assign('isShowMembershipBlock', $this->isShowMembershipBlock());
    $this->set('membershipBlock', $this->getMembershipBlock());

    // Handle PCP
    $pcpId = $this->getPcpID();
    if ($this->getPcpID()) {
      $pcp = CRM_PCP_BAO_PCP::handlePcp($pcpId, 'contribute', $this->_values);
      $this->_pcpBlock = $pcp['pcpBlock'];
      $this->_pcpInfo = $pcp['pcpInfo'];
    }

    $this->assign('pledgeBlock', !empty($this->_values['pledge_block_id']));

    // @todo - move this check to `getMembershipBlock`
    if (!$this->isFormSupportsNonMembershipContributions() &&
      !$this->_membershipBlock['is_active'] &&
      !$this->getPriceSetID()
    ) {
      CRM_Core_Error::statusBounce(ts('The requested online contribution page is missing a required Contribution Amount section or Membership section or Price Set. Please check with the site administrator for assistance.'));
    }
    // This can probably go as nothing it 'getting it' anymore since the values data is loaded
    // on every form, rather than being passed from form to form.
    $this->set('amount_block_is_active', $this->isFormSupportsNonMembershipContributions());

    //assigning is_monetary and is_email_receipt to template
    $this->assign('is_monetary', $this->_values['is_monetary']);
    $this->assign('is_email_receipt', $this->isEmailReceipt());
    $this->assign('bltID', $this->_bltID);

    //assign cancelSubscription URL to templates
    $this->assign('cancelSubscriptionUrl',
      $this->_values['cancelSubscriptionUrl'] ?? NULL
    );

    $title = $this->_values['frontend_title'];

    $this->setTitle(($this->_pcpId ? $this->_pcpInfo['title'] : $title));
    $this->_defaults = [];

    $this->_amount = $this->get('amount');
    // Assigning this to the template means it will be passed through to the payment form.
    // This can, for example, by used by payment processors using client side encryption
    $this->assign('currency', $this->getCurrency());

    CRM_Contribute_BAO_Contribution_Utils::overrideDefaultCurrency($this->_values);

    //lets allow user to override campaign.
    $campID = CRM_Utils_Request::retrieve('campID', 'Positive', $this);
    if ($campID && CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Campaign', $campID)) {
      $this->_values['campaign_id'] = $campID;
    }

    // check if billing block is required for pay later
    if (!empty($this->_values['is_pay_later'])) {
      $this->_isBillingAddressRequiredForPayLater = $this->_values['is_billing_required'] ?? NULL;
      $this->assign('isBillingAddressRequiredForPayLater', $this->_isBillingAddressRequiredForPayLater);
    }
  }

  /**
   * Load values for a contribution page.
   *
   * @param array $values
   */
  protected function loadContributionPageValues(&$values) {
    $modules = ['CiviContribute', 'soft_credit', 'on_behalf'];
    $values['custom_pre_id'] = $values['custom_post_id'] = NULL;
    $id = $this->getContributionPageID();

    $params = ['id' => $id];
    CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_ContributionPage', $params, $values);

    // get the profile ids
    $ufJoinParams = [
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $id,
    ];

    // retrieve profile id as also unserialize module_data corresponding to each $module
    foreach ($modules as $module) {
      $ufJoinParams['module'] = $module;
      $ufJoin = new CRM_Core_DAO_UFJoin();
      $ufJoin->copyValues($ufJoinParams);
      if ($module == 'CiviContribute') {
        $ufJoin->orderBy('weight asc');
        $ufJoin->find();
        while ($ufJoin->fetch()) {
          if ($ufJoin->weight == 1) {
            $values['custom_pre_id'] = $ufJoin->uf_group_id;
          }
          else {
            $values['custom_post_id'] = $ufJoin->uf_group_id;
          }
        }
      }
      else {
        $ufJoin->find(TRUE);
        if (!$ufJoin->is_active) {
          continue;
        }
        $params = CRM_Contribute_BAO_ContributionPage::formatModuleData($ufJoin->module_data, TRUE, $module);
        $values = array_merge($params, $values);
        if ($module == 'soft_credit') {
          $values['honoree_profile_id'] = $ufJoin->uf_group_id;
          $values['honor_block_is_active'] = $ufJoin->is_active;
        }
        else {
          $values['onbehalf_profile_id'] = $ufJoin->uf_group_id;
        }
      }
    }
  }

  /**
   * Set the selected line items.
   *
   * This returns all selected line items, even if they will
   * be split to a secondary contribution.
   *
   * @api Supported for external use.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public function getLineItems(): array {
    return $this->order->getLineItems();
  }

  /**
   * Set the selected line items.
   *
   * This returns all selected line items, even if they will
   * be split to a secondary contribution.
   *
   * @api Supported for external use.
   */
  public function setLineItems($lineItems): void {
    $this->order->setLineItems($lineItems);
    $this->set('_lineItem', $lineItems);
  }

  /**
   * Set the selected line items.
   *
   * @internal
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getMainContributionLineItems(): array {
    $membershipLineItems = $this->getSecondaryMembershipContributionLineItems();
    $allLineItems = $this->getOrder()->getLineItems();
    if (!$membershipLineItems || $allLineItems === $membershipLineItems) {
      return $allLineItems;
    }
    $mainContributionLineItems = [];
    foreach ($allLineItems as $index => $lineItem) {
      if (empty($lineItem['membership_type_id'])) {
        $mainContributionLineItems[$index] = $lineItem;
      }
    }
    return $mainContributionLineItems;
  }

  /**
   * @return int|null
   * @throws CRM_Core_Exception
   */
  public function getPledgeID(): ?int {
    if (!$this->getPledgeBlockValue('id')) {
      // Pledges not configured for page.
      return NULL;
    }
    $pledgeID = CRM_Utils_Request::retrieve('pledgeId', 'Positive', $this) ?: $this->_values['pledge_id'] ?? NULL;
    if ($pledgeID) {
      $this->setPledgeID($pledgeID);
    }
    return $pledgeID;
  }

  protected function setPledgeID(?int $pledgeID) {
    $this->_values['pledge_id'] = $pledgeID;
    $this->set('pledgeId', $pledgeID);
  }

  /**
   * @return array
   * @throws CRM_Core_Exception
   */
  protected function getMembershipTypes(): array {
    if (!isset($this->membershipTypes)) {
      $this->membershipTypes = CRM_Member_BAO_Membership::buildMembershipTypeValues($this, $this->getAvailableMembershipTypeIDs()) ?? [];
    }
    return $this->membershipTypes;
  }

  /**
   * Get the tope level financial_type_id.
   *
   * @return int
   * @throws CRM_Core_Exception
   */
  protected function getFinancialTypeID(): int {
    if ($this->getContributionValue('financial_type_id')) {
      return (int) $this->getContributionValue('financial_type_id');
    }
    if ($this->isFormSupportsNonMembershipContributions()) {
      return (int) $this->getContributionPageValue('financial_type_id');
    }
    return (int) $this->getFirstSelectedMembershipType()['financial_type_id'];
  }

  /**
   * Is the form separate payment AND has the user selected 2 options,
   * resulting in 2 payments.
   *
   * @throws \CRM_Core_Exception
   */
  protected function isSeparatePaymentSelected(): bool {
    return (bool) $this->getSecondaryMembershipContributionLineItems();
  }

  /**
   * Set the line items for the secondary membership contribution.
   *
   * Return false if the page is not configured for separate contributions,
   * or if only the membership or the contribution has been selected.
   *
   * @internal
   *
   * @return array|false
   *
   * @throws \CRM_Core_Exception
   */
  protected function getSecondaryMembershipContributionLineItems() {
    if (!$this->isSeparateMembershipPayment()) {
      return FALSE;
    }
    $lineItems = [];
    foreach ($this->getLineItems() as $index => $lineItem) {
      if (!empty($lineItem['membership_type_id'])) {
        $lineItems[$index] = $lineItem;
      }
    }
    if (empty($lineItems) || count($lineItems) === count($this->getLineItems())) {
      return FALSE;
    }
    return $lineItems;
  }

  /**
   * Get membership line items.
   *
   * Get all line items relating to membership, regardless of primary or secondary membership.
   *
   * @internal
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getMembershipLineItems(): array {
    $lineItems = [];
    foreach ($this->getLineItems() as $index => $lineItem) {
      if (!empty($lineItem['membership_type_id'])) {
        $lineItems[$index] = $lineItem;
      }
    }
    return $lineItems;
  }

  /**
   * Initiate price set such that various non-BAO things are set on the form.
   *
   * This function is not really a BAO function so the location is misleading.
   *
   * @param CRM_Core_Form $form
   *   Form entity id.
   *
   * @todo - removed unneeded code from previously-shared function
   */
  private function initSet($form) {
    $priceSetId = $this->getPriceSetID();
    // get price info
    if ($priceSetId) {
      if ($form->_action & CRM_Core_Action::UPDATE) {
        $form->_values['line_items'] = CRM_Price_BAO_LineItem::getLineItems($form->_id, 'contribution');
      }
      $form->_priceSet = $this->order->getPriceSetMetadata();
      $this->setPriceFieldMetaData($this->order->getPriceFieldsMetadata());
      $form->set('priceSet', $form->_priceSet);
    }
  }

  /**
   * Set price field metadata.
   *
   * @param array $metadata
   */
  public function setPriceFieldMetaData(array $metadata): void {
    $this->_values['fee'] = $this->_priceSet['fields'] = $metadata;
  }

  /**
   * Get price field metadata.
   *
   * The returned value is an array of arrays where each array
   * is an id-keyed price field and an 'options' key has been added to that
   * arry for any options.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @return array
   */
  public function getPriceFieldMetaData(): array {
    if (!empty($this->_values['fee'])) {
      return $this->_values['fee'];
    }
    if (!empty($this->_priceSet['fields'])) {
      return $this->_priceSet['fields'];
    }
    return $this->order->getPriceFieldsMetadata();
  }

  /**
   * Set the default values.
   */
  public function setDefaultValues() {
    return $this->_defaults;
  }

  /**
   * Assign the minimal set of variables to the template.
   */
  public function assignToTemplate() {
    $this->set('name', $this->assignBillingName($this->_params));
    $this->assign('currencyID', $this->_params['currencyID'] ?? NULL);
    $this->assign('credit_card_type', $this->_params['credit_card_type'] ?? NULL);
    $this->assign('trxn_id', $this->_params['trxn_id'] ?? NULL);
    $this->assign('amount_level', str_replace(CRM_Core_DAO::VALUE_SEPARATOR, ' ', $this->order->getAmountLevel()));
    $this->assign('amount', $this->getMainContributionAmount() > 0 ? CRM_Utils_Money::format($this->getMainContributionAmount(), NULL, NULL, TRUE) : NULL);

    $isRecurEnabled = isset($this->_values['is_recur']) && !empty($this->_paymentProcessor['is_recur']);
    $this->assign('is_recur_enabled', $isRecurEnabled);
    $this->assign('is_recur', $isRecurEnabled ? ($this->_params['is_recur'] ?? NULL) : NULL);
    $this->assign('frequency_interval', $isRecurEnabled ? ($this->_params['frequency_interval'] ?? NULL) : NULL);
    $this->assign('frequency_unit', $isRecurEnabled ? ($this->_params['frequency_unit'] ?? NULL) : NULL);
    $this->assign('installments', $isRecurEnabled ? ($this->_params['installments'] ?? NULL) : NULL);
    $isPledgeEnabled = CRM_Core_Component::isEnabled('CiviPledge') && !empty($this->_params['is_pledge']);
    // @todo Assigned pledge_enabled variable appears to be unused
    $this->assign('pledge_enabled', $isPledgeEnabled);
    $this->assign('is_pledge', $isPledgeEnabled ? ($this->_params['is_pledge'] ?? NULL) : NULL);
    $this->assign('pledge_frequency_interval', $isPledgeEnabled ? ($this->_params['pledge_frequency_interval'] ?? NULL) : NULL);
    $this->assign('pledge_frequency_unit', $isPledgeEnabled ? ($this->_params['pledge_frequency_unit'] ?? NULL) : NULL);
    $this->assign('pledge_installments', $isPledgeEnabled ? ($this->_params['pledge_installments'] ?? NULL) : NULL);
    $this->assign('address', CRM_Utils_Address::getFormattedBillingAddressFieldsFromParameters($this->_params));

    $isDisplayOnBehalf = !empty($this->_params['onbehalf_profile_id']) && !empty($this->_params['onbehalf']);
    if ($isDisplayOnBehalf) {
      $this->assign('onBehalfName', $this->_params['organization_name']);
      $locTypeId = array_keys($this->_params['onbehalf_location']['email']);
      $onBehalfEmail = $this->_params['onbehalf_location']['email'][$locTypeId[0]]['email'] ?? NULL;
    }
    $this->assign('onBehalfEmail', $onBehalfEmail ?? NULL);
    $this->assignPaymentFields();
    $this->assignEmailField();
    $this->assign('emailExists', $this->_emailExists);

    // also assign the receipt_text
    if (isset($this->_values['receipt_text'])) {
      $this->assign('receipt_text', $this->_values['receipt_text']);
    }
  }

  /**
   * Assign email variable in the template.
   */
  public function assignEmailField() {
    //If email exist in a profile, the default billing email field is not loaded on the page.
    //Hence, assign the existing location type email by iterating through the params.
    if ($this->_emailExists && empty($this->_params["email-{$this->_bltID}"])) {
      foreach ($this->_params as $key => $val) {
        if (substr($key, 0, 6) === 'email-') {
          $this->assign('email', $this->_params[$key]);
          break;
        }
      }
    }
    else {
      $this->assign('email', $this->_params["email-{$this->_bltID}"] ?? NULL);
    }
  }

  /**
   * Add the custom fields.
   *
   * @param int $id
   * @param string $name
   * @param bool $viewOnly
   * @param null $profileContactType
   * @param array $fieldTypes
   */
  public function buildCustom($id, $name, $viewOnly = FALSE, $profileContactType = NULL, $fieldTypes = NULL) {
    if ($id) {
      $contactID = $this->getContactID();

      // we don't allow conflicting fields to be
      // configured via profile - CRM 2100
      $fieldsToIgnore = [
        'receive_date' => 1,
        'trxn_id' => 1,
        'invoice_id' => 1,
        'net_amount' => 1,
        'fee_amount' => 1,
        'non_deductible_amount' => 1,
        'total_amount' => 1,
        'amount_level' => 1,
        'contribution_status_id' => 1,
        // @todo replace payment_instrument with payment instrument id.
        // both are available now but the id field is the most consistent.
        'payment_instrument' => 1,
        'payment_instrument_id' => 1,
        'contribution_check_number' => 1,
        'financial_type' => 1,
      ];

      $fields = CRM_Core_BAO_UFGroup::getFields($id, FALSE, CRM_Core_Action::ADD, NULL, NULL, FALSE,
        NULL, FALSE, NULL, CRM_Core_Permission::CREATE, NULL
      );

      if ($fields) {
        // determine if email exists in profile so we know if we need to manually insert CRM-2888, CRM-15067
        foreach ($fields as $key => $field) {
          if (substr($key, 0, 6) == 'email-' &&
              !in_array($profileContactType, ['honor', 'onbehalf'])
          ) {
            $this->_emailExists = TRUE;
            $this->set('emailExists', TRUE);
          }
        }

        if (array_intersect_key($fields, $fieldsToIgnore)) {
          $fields = array_diff_key($fields, $fieldsToIgnore);
          CRM_Core_Session::setStatus(ts('Some of the profile fields cannot be configured for this page.'), ts('Warning'), 'alert');
        }

        //remove common fields only if profile is not configured for onbehalf/honor
        if (!in_array($profileContactType, ['honor', 'onbehalf'])) {
          $fields = array_diff_key($fields, $this->_fields);
        }

        CRM_Core_BAO_Address::checkContactSharedAddressFields($fields, $contactID);
        // fetch file preview when not submitted yet, like in online contribution Confirm and ThankYou page
        $viewOnlyFileValues = empty($profileContactType) ? [] : [$profileContactType => []];
        foreach ($fields as $key => $field) {
          if ($viewOnly &&
            isset($field['data_type']) &&
            $field['data_type'] == 'File' || ($viewOnly && $field['name'] == 'image_URL')
          ) {
            //retrieve file value from submitted values on basis of $profileContactType
            $fileValue = $this->_params[$key] ?? NULL;
            if (!empty($profileContactType) && !empty($this->_params[$profileContactType])) {
              $fileValue = $this->_params[$profileContactType][$key] ?? NULL;
            }

            if ($fileValue) {
              $path = $fileValue['name'] ?? NULL;
              $fileType = $fileValue['type'] ?? NULL;
              $fileValue = CRM_Utils_File::getFileURL($path, $fileType);
            }

            // format custom file value fetched from submitted value
            if ($profileContactType) {
              $viewOnlyFileValues[$profileContactType][$key] = $fileValue;
            }
            else {
              $viewOnlyFileValues[$key] = $fileValue;
            }

            // On viewOnly use-case (as in online contribution Confirm page) we no longer need to set
            // required property because being required file is already uploaded while registration
            $field['is_required'] = FALSE;
          }
          if ($profileContactType) {
            //Since we are showing honoree name separately so we are removing it from honoree profile just for display
            if ($profileContactType == 'honor') {
              $honoreeNamefields = [
                'prefix_id',
                'first_name',
                'last_name',
                'suffix_id',
                'organization_name',
                'household_name',
              ];
              if (in_array($field['name'], $honoreeNamefields)) {
                unset($fields[$field['name']]);
                continue;
              }
            }
            if (!empty($fieldTypes) && in_array($field['field_type'], $fieldTypes)) {
              CRM_Core_BAO_UFGroup::buildProfile(
                $this,
                $field,
                CRM_Profile_Form::MODE_CREATE,
                $contactID,
                TRUE,
                $profileContactType
              );
              $this->_fields[$profileContactType][$key] = $field;
            }
            else {
              unset($fields[$key]);
            }
          }
          else {
            CRM_Core_BAO_UFGroup::buildProfile(
              $this,
              $field,
              CRM_Profile_Form::MODE_CREATE,
              $contactID,
              TRUE
            );
            $this->_fields[$key] = $field;
          }
        }

        if ($profileContactType && count($viewOnlyFileValues[$profileContactType])) {
          $this->assign('viewOnlyPrefixFileValues', $viewOnlyFileValues);
        }
        elseif (count($viewOnlyFileValues)) {
          $this->assign('viewOnlyFileValues', $viewOnlyFileValues);
        }
      }
    }
    $this->assign($name, $fields ?? NULL);
  }

  /**
   * Build Premium Block im Contribution Pages.
   *
   * @param bool $formItems
   * @param string $selectedOption
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  protected function buildPremiumsBlock(bool $formItems = FALSE, $selectedOption = NULL): void {
    $selectedProductID = $this->getProductID();
    $this->add('hidden', 'selectProduct', $selectedProductID, ['id' => 'selectProduct']);
    $premiumProducts = PremiumsProduct::get()
      ->addSelect('product_id.*')
      ->addSelect('premiums_id.*')
      ->addWhere('product_id.is_active', '=', TRUE)
      ->addWhere('premiums_id.premiums_active', '=', TRUE)
      ->addWhere('premiums_id.entity_id', '=', $this->getContributionPageID())
      ->addWhere('premiums_id.entity_table', '=', 'civicrm_contribution_page')
      ->addOrderBy('weight')
      ->execute();
    $products = [];
    $premium = [];
    foreach ($premiumProducts as $premiumProduct) {
      $product = CRM_Utils_Array::filterByPrefix($premiumProduct, 'product_id.');
      $premium = CRM_Utils_Array::filterByPrefix($premiumProduct, 'premiums_id.');
      if ($selectedProductID === $product['id'] && $selectedOption) {
        // In this case we are on the thank you or confirm page so assign
        // the selected option to the page for display.
        $product['options'] = ts('Selected Option') . ': ' . $selectedOption;
      }
      elseif ($selectedOption) {
        // We are on the thank you or confirm page, but this option wasn't selected.
        continue;
      }
      $options = array_filter((array) $product['options']);
      $productOptions = [];
      foreach ($options as $option) {
        $optionValue = trim($option);
        if ($optionValue) {
          $productOptions[$optionValue] = $optionValue;
        }
      }
      if (!empty($options)) {
        $this->addElement('select', 'options_' . $product['id'], NULL, $productOptions);
      }
      $products[$product['id']] = $product;
    }
    $this->assign('premiumBlock', $premium);
    $this->assign('products', $products);
  }

  /**
   * Assign payment field information to the template.
   *
   * @throws \CRM_Core_Exception
   */
  public function assignPaymentFields() {
    //fix for CRM-3767
    $isMonetary = FALSE;
    if ($this->order->getTotalAmount() > 0.0) {
      $isMonetary = TRUE;
    }
    elseif (!empty($this->_params['selectMembership'])) {
      $memFee = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $this->_params['selectMembership'], 'minimum_fee');
      if ($memFee > 0.0) {
        $isMonetary = TRUE;
      }
    }

    // The concept of contributeMode is deprecated.
    // The payment processor object can provide info about the fields it shows.
    if ($isMonetary) {
      $paymentProcessorObject = $this->getPaymentProcessorObject();
      $this->assign('paymentAgreementTitle', $paymentProcessorObject->getText('agreementTitle', []));
      $this->assign('paymentAgreementText', $paymentProcessorObject->getText('agreementText', []));
      $paymentFields = $paymentProcessorObject->getPaymentFormFields();
      foreach ($paymentFields as $index => $paymentField) {
        if (!isset($this->_params[$paymentField])) {
          unset($paymentFields[$index]);
          continue;
        }
        if ($paymentField === 'credit_card_exp_date') {
          $date = CRM_Utils_Date::format($this->_params['credit_card_exp_date'] ?? NULL);
          $date = CRM_Utils_Date::mysqlToIso($date);
          $this->assign('credit_card_exp_date', $date);
        }
        elseif ($paymentField === 'credit_card_number') {
          $this->assign('credit_card_number',
            CRM_Utils_System::mungeCreditCard($this->_params['credit_card_number'] ?? NULL)
          );
        }
        elseif ($paymentField === 'credit_card_type') {
          $this->assign('credit_card_type', CRM_Core_PseudoConstant::getLabel(
            'CRM_Core_BAO_FinancialTrxn',
            'card_type_id',
            CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_FinancialTrxn', 'card_type_id', $this->_params['credit_card_type'])
          ));
        }
        else {
          $this->assign($paymentField, $this->_params[$paymentField]);
        }
      }
      $this->assign('paymentFieldsetLabel', CRM_Core_Payment_Form::getPaymentLabel($paymentProcessorObject));
    }
    $this->assign('paymentFields', $paymentFields ?? []);
  }

  /**
   * Check template file exists.
   *
   * @param string|null $suffix
   *
   * @return string|null
   *   Template file path, else null
   */
  public function checkTemplateFileExists($suffix = NULL) {
    if ($this->_id) {
      $templateFile = "CRM/Contribute/Form/Contribution/{$this->_id}/{$this->_name}.{$suffix}tpl";
      $template = CRM_Core_Form::getTemplate();
      if ($template->template_exists($templateFile)) {
        return $templateFile;
      }
    }
    return NULL;
  }

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */
  public function getTemplateFileName() {
    $fileName = $this->checkTemplateFileExists();
    return $fileName ?: parent::getTemplateFileName();
  }

  /**
   * Add the extra.tpl in.
   *
   * Default extra tpl file basically just replaces .tpl with .extra.tpl
   * i.e. we do not override - why isn't this done at the CRM_Core_Form level?
   *
   * @return string
   */
  public function overrideExtraTemplateFileName() {
    $fileName = $this->checkTemplateFileExists('extra.');
    return $fileName ?: parent::overrideExtraTemplateFileName();
  }

  /**
   * Authenticate pledge user during online payment.
   *
   * @throws \CRM_Core_Exception
   */
  private function authenticatePledgeUser(): void {
    //get the userChecksum and contact id
    $userChecksum = CRM_Utils_Request::retrieve('cs', 'String', $this);
    $contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    //get pledge status and contact id
    $pledgeValues = Pledge::get(FALSE)
      ->addWhere('id', '=', $this->getPledgeID())
      ->addSelect('contact_id', 'status_id:name')
      ->execute()->first();

    $validUser = FALSE;
    // @todo - override getRequestedContactID to add in checking pledge values, then
    // getContactID will do all this.
    $userID = CRM_Core_Session::getLoggedInContactID();
    if ($userID &&
      $userID == $pledgeValues['contact_id']
    ) {
      //check for authenticated  user.
      $validUser = TRUE;
    }
    elseif ($userChecksum && $pledgeValues['contact_id']) {
      //check for anonymous user.
      $validUser = CRM_Contact_BAO_Contact_Utils::validChecksum($pledgeValues['contact_id'], $userChecksum);

      //make sure cid is same as pledge contact id
      if ($validUser && ($pledgeValues['contact_id'] != $contactID)) {
        $validUser = FALSE;
      }
    }

    if (!$validUser) {
      CRM_Core_Error::statusBounce(ts("Oops. It looks like you have an incorrect or incomplete link (URL). Please make sure you've copied the entire link, and try again. Contact the site administrator if this error persists."));
    }

    //check for valid pledge status.
    if (!in_array($pledgeValues['status_id:name'], ['Pending', 'In Progress', 'Overdue'])) {
      CRM_Core_Error::statusBounce(ts('Oops. You cannot make a payment for this pledge - pledge status is %1.', [1 => $pledgeValues['status_id:name']]));
    }
  }

  /**
   * Determine if recurring parameters need to be added to the form parameters.
   *
   *  - is_recur
   *  - frequency_interval
   *  - frequency_unit
   *
   * For membership this is based on the membership type.
   *
   * This needs to be done before processing the pre-approval redirect where relevant on the main page or before any payment processing.
   *
   * Arguably the form should start to build $this->_params in the pre-process main page & use that array consistently throughout.
   */
  protected function setRecurringMembershipParams() {
    $priceFieldId = array_key_first($this->_values['fee']);
    // Why is this an array in CRM_Contribute_Form_Contribution_Main::submit and a string in CRM_Contribute_Form_Contribution_Confirm::preProcess()?
    if (is_array($this->_params["price_{$priceFieldId}"])) {
      $priceFieldValue = array_key_first($this->_params["price_{$priceFieldId}"]);
    }
    else {
      $priceFieldValue = $this->_params["price_{$priceFieldId}"];
    }
    $selectedMembershipTypeID = $this->_values['fee'][$priceFieldId]['options'][$priceFieldValue]['membership_type_id'] ?? NULL;
    if (!$selectedMembershipTypeID || !$this->getPaymentProcessorValue('is_recur')) {
      return;
    }

    // Check if membership the selected membership is automatically opted into auto renew or give user the option.
    // In the 2nd case we check that the user has in deed opted in (auto renew as at June 22 is the field name for the membership auto renew checkbox)
    // Also check that the payment Processor used can support recurring contributions.
    $membershipTypeDetails = $this->getMembershipType($selectedMembershipTypeID);
    if (
      // 2 means required
      $membershipTypeDetails['auto_renew'] === 2
      // 1 means optional - so they must also select the check box on the form
      || ($membershipTypeDetails['auto_renew'] === 1 && !empty($this->_params['auto_renew']))
    ) {
      $this->_params['auto_renew'] = TRUE;
      $this->_params['is_recur'] = $this->_values['is_recur'] = 1;
      // If membership_num_terms is not specified on the the price field value (which seems not uncommon
      // in default config) then the membership type provides the values.
      // @todo - access the line item value from $this->getLineItems() rather than _values['fee']
      $membershipNumTerms = $this->_values['fee'][$priceFieldId]['options'][$priceFieldValue]['membership_num_terms'] ?? 1;
      $membershipDurationInterval = $membershipTypeDetails['duration_interval'] ?? 1;
      $this->_params['frequency_interval'] = $this->getSubmittedValue('frequency_interval') ?? ($membershipNumTerms * $membershipDurationInterval);
      $this->_params['frequency_unit'] = $this->getSubmittedValue('frequency_unit') ?? $membershipTypeDetails['duration_unit'];
    }
    // This seems like it repeats the above with less care...
    elseif (!$this->_separateMembershipPayment && ($membershipTypeDetails['auto_renew'] === 2
      || $membershipTypeDetails['auto_renew'] === 1)) {
      // otherwise check if we have a separate membership payment setting as that will allow people to independently opt into recurring contributions and memberships
      // If we don't have that and the membership type is auto recur or opt into recur set is_recur to 0.
      $this->_params['is_recur'] = $this->_values['is_recur'] = 0;
    }
  }

  /**
   * Get the amount for the main contribution.
   *
   * If there is a separate membership contribution this is the 'other one'. Otherwise there
   * is only one.
   *
   * @return float
   *
   * @throws \CRM_Core_Exception
   */
  protected function getMainContributionAmount(): float {
    $amount = 0;
    foreach ($this->getMainContributionLineItems() as $lineItem) {
      // Line total inclusive should really be always set but this is a safe fall back.
      $amount += $lineItem['line_total_inclusive'] ?? ($lineItem['line_total'] + $lineItem['tax_amount']);
    }
    return $amount;
  }

  /**
   * Get the campaign ID.
   *
   * @return ?int
   *
   * @api This function will not change in a minor release and is supported for
   *  use outside of core. This annotation / external support for properties
   *  is only given where there is specific test cover.
   */
  public function getCampaignID(): ?int {
    if ($this->getSubmittedValue('campaign_id')) {
      return $this->getSubmittedValue('campaign_id');
    }
    if ($this->getSubmittedValue('contribution_campaign_id')) {
      return $this->getSubmittedValue('contribution_campaign_id');
    }
    return $this->getContributionPageValue('campaign_id');
  }

  /**
   * Get the amount level description for the main contribution.
   *
   * If there is a separate membership contribution this is the 'other one'. Otherwise there
   * is only one.
   *
   * @return string
   *
   * @throws \CRM_Core_Exception
   */
  protected function getMainContributionAmountLevel(): string {
    $amountLevel = [];
    if ($this->getSecondaryMembershipContributionLineItems()) {
      // This is really only needed transitionally because the
      // test ConfirmTest::testSeparatePaymentConfirm has some set up configuration
      // issues that will take a bit longer to work through (the labels
      // should be Contribution Amount or Other Amount but in that test set up they are not.
      return '';
    }
    foreach ($this->getMainContributionLineItems() as $lineItem) {
      if ($lineItem['label'] !== ts('Contribution Amount') && $lineItem['label'] !== ts('Other Amount')) {
        $amountLevel[] = $lineItem['label'] . ' - ' . (float) $lineItem['qty'];
      }
    }
    return empty($amountLevel) ? '' : CRM_Utils_Array::implodePadded($amountLevel);
  }

  /**
   * Wrapper for processAmount that also sets autorenew.
   *
   * @param array $params
   *   Params reflecting form input e.g with fields 'price_5' => 7, 'price_8' => array(7, 8)
   */
  public function processAmountAndGetAutoRenew(&$params) {
    $autoRenew = [];
    $autoRenew[0] = $autoRenew[1] = $autoRenew[2] = 0;
    foreach ($this->getLineItems() as $lineItem) {
      if (!empty($lineItem['auto_renew']) &&
        is_numeric($lineItem['auto_renew'])
      ) {
        $autoRenew[$lineItem['auto_renew']] += $lineItem['line_total'];
      }
    }
    if (count($autoRenew) > 1) {
      $params['autoRenew'] = $autoRenew;
    }
  }

  /**
   * Is payment for (non membership) contributions enabled on this form.
   *
   * This would be true in a case of contributions only or where both
   * memberships and non-membership contributions are enabled (whether they
   * are using quick config price sets or explicit price sets).
   *
   * The value is a database value in the config for the contribution page. It
   * is loaded into values in ContributionBase::preProcess (called by this).
   *
   * @internal function is public to support validate but is for core use only.
   *
   * @return bool
   */
  public function isFormSupportsNonMembershipContributions(): bool {
    return (bool) ($this->_values['amount_block_is_active'] ?? FALSE);
  }

  /**
   * Get the membership block configured for the page, fetching if needed.
   *
   * The membership block is configured memberships are available to purchase via
   * a quick-config price set.
   *
   * @return array|false
   */
  protected function getMembershipBlock() {
    if (!isset($this->_membershipBlock)) {
      //check if Membership Block is enabled, if Membership Fields are included in profile
      //get membership section for this contribution page
      $this->_membershipBlock = CRM_Member_BAO_Membership::getMembershipBlock($this->_id) ?? FALSE;
      $preProfileType = empty($this->_values['custom_pre_id']) ? NULL : CRM_Core_BAO_UFField::getProfileType($this->_values['custom_pre_id']);
      $postProfileType = empty($this->_values['custom_post_id']) ? NULL : CRM_Core_BAO_UFField::getProfileType($this->_values['custom_post_id']);

      if ((($postProfileType === 'Membership') || ($preProfileType === 'Membership')) &&
        !$this->_membershipBlock['is_active']
      ) {
        CRM_Core_Error::statusBounce(ts('This page includes a Profile with Membership fields - but the Membership Block is NOT enabled. Please notify the site administrator.'));
      }
    }
    return $this->_membershipBlock;
  }

  /**
   * Is a (non-quick-config) membership price set in use.
   *
   * @return bool
   */
  protected function isMembershipPriceSet(): bool {
    if ($this->_useForMember === NULL) {
      if ($this->getFormContext() === 'membership' &&
        !$this->isQuickConfig()) {
        $this->_useForMember = 1;
      }
      else {
        $this->_useForMember = 0;
      }
      $this->set('useForMember', $this->_useForMember);
    }
    return (bool) $this->_useForMember;
  }

  /**
   * Get the form context.
   *
   * This is important for passing to the buildAmount hook as CiviDiscount checks it.
   *
   * @return string
   */
  public function getFormContext(): string {
    return $this->order->isMembershipPriceSet() ? 'membership' : 'contribution';
  }

  /**
   * Should the membership block be displayed.
   *
   * This should be shown when a membership is available to purchase.
   *
   * It could be a quick config price set or a standard price set that extends
   * CiviMember.
   *
   * @return bool
   */
  protected function isShowMembershipBlock(): bool {
    return CRM_Core_Component::isEnabled('CiviMember') && $this->getMembershipBlock();
  }

  /**
   * Is the contribution page configured for 2 payments, one being membership & one not.
   *
   * @return bool
   */
  protected function isSeparateMembershipPayment(): bool {
    return $this->getMembershipBlock() && $this->getMembershipBlock()['is_separate_payment'];
  }

  /**
   * Get the id of the membership the contact is trying to renew.
   *
   * @return bool|int
   * @throws \CRM_Core_Exception
   */
  protected function getRenewalMembershipID() {
    if ($this->renewalMembershipID === NULL) {
      if (!$this->getContactID()) {
        $this->renewalMembershipID = FALSE;
      }
      else {
        $this->renewalMembershipID = CRM_Utils_Request::retrieve('mid', 'Positive', $this) ?: FALSE;
      }
    }
    return $this->renewalMembershipID ?: FALSE;
  }

  /**
   * Get the id of an existing contribution the submitter is attempting to pay.
   *
   * @return int|null
   */
  protected function getExistingContributionID(): ?int {
    return $this->_ccid ?: CRM_Utils_Request::retrieve('ccid', 'Positive', $this);
  }

  /**
   * @return int|bool
   */
  protected function getProductID() {
    $productID = $this->getSubmittedValue('selectProduct') ? (int) $this->getSubmittedValue('selectProduct') : FALSE;
    $this->set('productID', $productID);
    return $productID;
  }

  /**
   * Get the submitted value, accessing it from whatever form in the flow it is
   * submitted on.
   *
   * @param string $fieldName
   *
   * @return mixed|null
   */
  public function getSubmittedValue(string $fieldName) {
    $value = $this->controller->exportValue('Main', $fieldName);
    if (in_array($fieldName, $this->submittableMoneyFields, TRUE)) {
      return CRM_Utils_Rule::cleanMoney($value);
    }

    // Numeric fields are not in submittableMoneyFields (for now)
    $fieldRules = $this->_rules[$fieldName] ?? [];
    foreach ($fieldRules as $rule) {
      if ('money' === $rule['type']) {
        return CRM_Utils_Rule::cleanMoney($value);
      }
    }
    return $value;
  }

  /**
   * Get the fields that can be submitted in this form flow.
   *
   * This is overridden to make the fields submitted on the first
   * form (Contribution_Main) available from the others in the same flow
   * (Contribution_Confirm, Contribution_ThankYou).
   *
   * @return string[]
   */
  protected function getSubmittableFields(): array {
    $fieldNames = array_keys($this->controller->exportValues('Main'));
    return array_fill_keys($fieldNames, $this->_name);
  }

  /**
   * @return array
   */
  protected function getExistingContributionLineItems(): array {
    $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($this->getExistingContributionID());
    foreach (array_keys($lineItems) as $id) {
      $lineItems[$id]['id'] = $id;
    }
    return $lineItems;
  }

  /**
   * Get the PCP ID being contributed to.
   *
   * @return int|null
   */
  protected function getPcpID(): ?int {
    if ($this->_pcpId === NULL) {
      $this->_pcpId = CRM_Utils_Request::retrieve('pcpId', 'Positive', $this);
    }
    return $this->_pcpId ? (int) $this->_pcpId : NULL;
  }

  /**
   * Get the selected Contribution ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getContributionID(): ?int {
    if ($this->getExistingContributionID()) {
      return $this->getExistingContributionID();
    }
    if (property_exists($this, '_contributionID')) {
      // Available on Confirm form (which is tested), so this avoids
      // accessing that directly & will work for ThankYou in time.
      return $this->_contributionID;
    }
    return NULL;
  }

  protected function getOrder(): CRM_Financial_BAO_Order {
    if (!$this->order) {
      $this->initializeOrder();
    }
    return $this->order;
  }

  protected function initializeOrder(): void {
    $this->order = new CRM_Financial_BAO_Order();
    $this->order->setPriceSetID($this->getPriceSetID());
    $this->order->setIsExcludeExpiredFields(TRUE);
    if ($this->get('lineItem')) {
      $this->order->setLineItems($this->get('lineItem')[$this->getPriceSetID()]);
    }
    if ($this->getExistingContributionID()) {
      $this->order->setTemplateContributionID($this->getExistingContributionID());
    }
    $this->order->setForm($this);
    foreach ($this->getPriceFieldMetaData() as $priceField) {
      if ($priceField['html_type'] === 'Text') {
        $this->submittableMoneyFields[] = 'price_' . $priceField['id'];
      }
    }
    $this->order->setPriceSelectionFromUnfilteredInput($this->getSubmittedValues());
  }

  protected function defineRenewalMembership(): void {
    $membership = new CRM_Member_DAO_Membership();
    $membership->id = $this->getRenewalMembershipID();

    if ($membership->find(TRUE)) {
      if ($membership->contact_id != $this->_contactID) {
        $validMembership = FALSE;
        $organizations = CRM_Contact_BAO_Relationship::getPermissionedContacts($this->getAuthenticatedContactID(), NULL, NULL, 'Organization');
        if (!empty($organizations) && array_key_exists($membership->contact_id, $organizations)) {
          $this->_membershipContactID = $membership->contact_id;
          $this->assign('membershipContactID', $this->_membershipContactID);
          $this->assign('membershipContactName', $organizations[$this->_membershipContactID]['name']);
          $validMembership = TRUE;
        }
        else {
          $membershipType = new CRM_Member_BAO_MembershipType();
          $membershipType->id = $membership->membership_type_id;
          if ($membershipType->find(TRUE)) {
            // CRM-14051 - membership_type.relationship_type_id is a CTRL-A padded string w one or more ID values.
            // Convert to comma separated list.
            $inheritedRelTypes = implode(',', CRM_Utils_Array::explodePadded($membershipType->relationship_type_id));
            $permContacts = CRM_Contact_BAO_Relationship::getPermissionedContacts($this->getAuthenticatedContactID(), $membershipType->relationship_type_id);
            if (array_key_exists($membership->contact_id, $permContacts)) {
              $this->_membershipContactID = $membership->contact_id;
              $validMembership = TRUE;
            }
          }
        }
        if (!$validMembership) {
          CRM_Core_Session::setStatus(ts("Oops. The membership you're trying to renew appears to be invalid. Contact your site administrator if you need assistance. If you continue, you will be issued a new membership."), ts('Membership Invalid'), 'alert');
        }
      }
    }
    else {
      CRM_Core_Session::setStatus(ts("Oops. The membership you're trying to renew appears to be invalid. Contact your site administrator if you need assistance. If you continue, you will be issued a new membership."), ts('Membership Invalid'), 'alert');
    }
  }

  /**
   * Assign the total amounts for display on Confirm and ThankYou pages.
   *
   * These values are used in the separate payments section.
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  protected function assignTotalAmounts(): void {
    // orderTotal includes both payments, if separate.
    $orderTotal = $this->getOrder() ? $this->order->getTotalAmount() : 0;
    $membershipTotalAmount = $this->getOrder() ? $this->order->getMembershipTotalAmount() : 0;
    $this->assign('orderTotal', $orderTotal);
    $this->assign('membershipTotalAmount', $membershipTotalAmount);
    $this->assign('nonMembershipTotalAmount', $orderTotal - $membershipTotalAmount);
  }

  /**
   * Get the currency for the form.
   *
   * Rather historic - might have unneeded stuff
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  public function getCurrency(): string {
    $existingContributionID = $this->getExistingContributionID();
    if ($existingContributionID) {
      $currency = Contribution::get(FALSE)
        ->addSelect('currency')
        ->addWhere('id', '=', $existingContributionID)
        ->execute()
        ->first()['currency'];
    }
    else {
      $currency = $this->getContributionPageValue('currency');
      if (empty($currency)) {
        $currency = CRM_Utils_Request::retrieveValue('currency', 'String');
      }
      $currency = (string) ($currency ?? \Civi::settings()->get('currency'));
    }
    return $currency;
  }

  /**
   * @return int[]
   * @throws CRM_Core_Exception
   */
  protected function getSelectedMembershipTypeIDs(): array {
    return array_keys($this->order->getMembershipTypes());
  }

  /**
   * @return array
   * @throws CRM_Core_Exception
   */
  protected function getMembershipType($membershipTypeID): array {
    return $this->getMembershipTypes()[$membershipTypeID];
  }

  /**
   * @return array
   * @throws CRM_Core_Exception
   */
  protected function getFirstSelectedMembershipType(): array {
    foreach ($this->getMembershipTypes() as $type) {
      if (in_array($type['id'], $this->getSelectedMembershipTypeIDs(), TRUE)) {
        return $type;
      }
    }
    return [];
  }

  /**
   * Get the membership type IDs available in the price set.
   *
   * @return array
   */
  protected function getAvailableMembershipTypeIDs(): array {
    $membershipTypeIDs = [];
    foreach ($this->getPriceFieldMetaData() as $priceField) {
      foreach ($priceField['options'] ?? [] as $option) {
        if (!empty($option['membership_type_id'])) {
          $membershipTypeIDs[$option['membership_type_id']] = $option['membership_type_id'];
        }
      }
    }
    return $membershipTypeIDs;
  }

}
