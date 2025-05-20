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

/**
 * This class generates form components for processing Event.
 */
class CRM_Event_Form_Registration extends CRM_Core_Form {

  use CRM_Financial_Form_FrontEndPaymentFormTrait;
  use CRM_Event_Form_EventFormTrait;
  use CRM_Financial_Form_PaymentProcessorFormTrait;

  /**
   * The id of the event we are processing.
   *
   * @var int
   *
   * @deprecated access via `getEventID`
   */
  public $_eventId;

  private CRM_Financial_BAO_Order $order;

  private array $optionsCount;

  /**
   * Array of payment related fields to potentially display on this form (generally credit card or debit card fields).
   *
   * This is rendered via billingBlock.tpl.
   *
   * @var array
   */
  public $_paymentFields = [];

  protected function getOrder(): CRM_Financial_BAO_Order {
    if (!isset($this->order)) {
      $this->initializeOrder();
    }
    return $this->order;
  }

  /**
   * Get the selected Event ID.
   *
   * @return int|null
   *
   * @throws \CRM_Core_Exception
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   */
  public function getEventID(): int {
    if (!$this->_eventId) {
      try {
        $this->_eventId = (int) CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
      }
      catch (CRM_Core_Exception $e) {
        CRM_Utils_System::sendInvalidRequestResponse(ts('Missing Event ID'));
      }
      // this is the first time we are hitting this, so check for permissions here
      if (!CRM_Core_Permission::event(CRM_Core_Permission::EDIT, $this->_eventId, 'register for events')) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to register for this event'), $this->getInfoPageUrl());
      }
    }
    return $this->_eventId;
  }

  /**
   * Set price field metadata.
   *
   * @param array $metadata
   */
  protected function setPriceFieldMetaData(array $metadata): void {
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
   * The array of ids of all the participant we are processing.
   *
   * @var int
   */
  protected $_participantIDS = NULL;

  /**
   * The id of the participant we are processing.
   *
   * @var int
   */
  protected $_participantId;

  /**
   * Is participant able to walk registration wizard.
   *
   * @var bool
   */
  public $_allowConfirmation;

  /**
   * Is participant requires approval.
   *
   * @var bool
   */
  public $_requireApproval;

  /**
   * Is event configured for waitlist.
   *
   * @var bool
   */
  public $_allowWaitlist;

  /**
   * Store additional participant ids.
   * when there are pre-registered.
   *
   * @var array
   */
  public $_additionalParticipantIds;

  /**
   * The values for the contribution db object.
   *
   * @var array
   */
  public $_values;

  /**
   * The paymentProcessor attributes for this page.
   *
   * @var array
   */
  public $_paymentProcessor;

  /**
   * The params submitted by the form and computed by the app.
   *
   * @var array
   */
  protected $_params;

  /**
   * The fields involved in this contribution page.
   *
   * @var array
   */
  public $_fields;

  /**
   * The billing location id for this contribution page.
   *
   * @var int
   */
  public $_bltID;

  /**
   * Price Set ID, if the new price set method is used
   *
   * @var int
   */
  public $_priceSetId = NULL;

  /**
   * Array of fields for the price set.
   *
   * @var array
   */
  public $_priceSet;

  public $_action;

  public $_pcpId;

  /**
   * Is event already full.
   *
   * @var bool
   *
   */
  public $_isEventFull;

  public $_lineItem;

  public $_lineItemParticipantsCount;

  public $_availableRegistrations;

  /**
   * @var bool
   * @deprecated
   */
  public $_isBillingAddressRequiredForPayLater;

  /**
   * Is this a back office form
   *
   * @var bool
   */
  public $isBackOffice = FALSE;

  /**
   * Payment instrument iD for the transaction.
   *
   * This will generally be drawn from the payment processor and is ignored for
   * front end forms.
   *
   * @var int
   */
  public $paymentInstrumentID;

  /**
   * Should the payment element be shown on the confirm page instead of the first page?
   *
   * @var bool
   */
  protected $showPaymentOnConfirm = FALSE;

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    $this->setTitle($this->getEventValue('title'));
    $this->_action = CRM_Utils_Request::retrieve('action', 'Alphanumeric', $this, FALSE, CRM_Core_Action::ADD);
    //CRM-4320
    $this->_participantId = CRM_Utils_Request::retrieve('participantId', 'Positive', $this);
    $this->setPaymentMode();
    $this->assign('isShowAdminVisibilityFields', CRM_Core_Permission::check('administer CiviCRM'));
    $this->_values = $this->get('values');
    $this->_fields = $this->get('fields');
    $this->_bltID = $this->get('bltID');
    $this->_paymentProcessor = $this->get('paymentProcessor');
    $this->_priceSetId = $this->get('priceSetId');
    $this->_priceSet = $this->get('priceSet');
    $this->_lineItem = $this->get('lineItem');
    $this->_isEventFull = $this->get('isEventFull');
    $this->_lineItemParticipantsCount = $this->get('lineItemParticipants');
    if (!is_array($this->_lineItem)) {
      $this->_lineItem = [];
    }
    if (!is_array($this->_lineItemParticipantsCount)) {
      $this->_lineItemParticipantsCount = [];
    }
    $this->_availableRegistrations = $this->get('availableRegistrations');
    $this->_participantIDS = $this->get('participantIDs');

    // Required for currency formatting in the JS layer
    // this is a temporary fix intended to resolve a regression quickly
    // And assigning moneyFormat for js layer formatting
    // will only work until that is done.
    // https://github.com/civicrm/civicrm-core/pull/19151
    $this->assign('moneyFormat', CRM_Utils_Money::format(1234.56, $this->getCurrency()));

    //check if participant allow to walk registration wizard.
    $this->_allowConfirmation = $this->get('allowConfirmation');
    $this->assign('currency', $this->getCurrency());
    // check for Approval
    $this->_requireApproval = $this->get('requireApproval');

    // check for waitlisting.
    $this->_allowWaitlist = $this->get('allowWaitlist');

    //get the additional participant ids.
    $this->_additionalParticipantIds = $this->get('additionalParticipantIds');

    $this->showPaymentOnConfirm = $this->isShowPaymentOnConfirm();
    $this->assign('showPaymentOnConfirm', $this->showPaymentOnConfirm);
    $priceSetID = $this->getPriceSetID();
    if ($priceSetID) {
      $this->_priceSet = $this->getOrder()->getPriceSetMetadata();
      $this->setPriceFieldMetaData($this->getOrder()->getPriceFieldsMetadata());
      $this->assign('quickConfig', $this->isQuickConfig());
    }

    // If there is money involved the call to setPriceFieldMetaData will have set the key 'fee'.
    // 'fee' is a duplicate of other properties but some places still refer to it
    // $this->getPriceFieldsMetadata() is the recommended interaction.
    if (!$this->_values || count($this->_values) === 1) {

      // get all the values from the dao object
      $this->_values = $this->_fields = [];
      // ensure 'fee' is set since it just got wiped out
      if ($this->getPriceSetID()) {
        $this->setPriceFieldMetaData($this->getOrder()->getPriceFieldsMetadata());
      }
      //retrieve event information
      $params = ['id' => $this->getEventID()];
      CRM_Event_BAO_Event::retrieve($params, $this->_values['event']);

      // check for is_monetary status
      $isMonetary = $this->getEventValue('is_monetary');

      $this->checkValidEvent();
      // get the participant values, CRM-4320
      $this->_allowConfirmation = FALSE;
      if ($this->_participantId) {
        $this->processFirstParticipant($this->_participantId);
      }
      //check for additional participants.
      if ($this->_allowConfirmation && $this->_values['event']['is_multiple_registrations']) {
        $additionalParticipantIds = CRM_Event_BAO_Participant::getAdditionalParticipantIds($this->_participantId);
        $cnt = 1;
        foreach ($additionalParticipantIds as $additionalParticipantId) {
          $this->_additionalParticipantIds[$cnt] = $additionalParticipantId;
          $cnt++;
        }
        $this->set('additionalParticipantIds', $this->_additionalParticipantIds);
      }

      $eventFull = CRM_Event_BAO_Participant::eventFull($this->getEventID(), FALSE,
        $this->_values['event']['has_waitlist'] ?? NULL
      );

      $this->_allowWaitlist = $this->_isEventFull = FALSE;
      if ($eventFull && !$this->_allowConfirmation) {
        $this->_isEventFull = TRUE;
        //lets redirecting to info only when to waiting list.
        $this->_allowWaitlist = $this->_values['event']['has_waitlist'] ?? NULL;
        if (!$this->_allowWaitlist) {
          CRM_Utils_System::redirect($this->getInfoPageUrl());
        }
      }
      $this->set('isEventFull', $this->_isEventFull);
      $this->set('allowWaitlist', $this->_allowWaitlist);

      //check for require requires approval.
      $this->_requireApproval = FALSE;
      if (!empty($this->_values['event']['requires_approval']) && !$this->_allowConfirmation) {
        $this->_requireApproval = TRUE;
      }
      $this->set('requireApproval', $this->_requireApproval);

      if (isset($this->_values['event']['default_role_id'])) {
        $participant_role = CRM_Core_OptionGroup::values('participant_role');
        $this->_values['event']['participant_role'] = $participant_role["{$this->_values['event']['default_role_id']}"];
      }
      $isPayLater = $this->getEventValue('is_pay_later');
      $this->setPayLaterLabel($isPayLater ? $this->_values['event']['pay_later_text'] : '');
      //check for various combinations for paylater, payment
      //process with paid event.
      if ($isMonetary && (!$isPayLater || !empty($this->_values['event']['payment_processor']))) {
        $this->_paymentProcessorIDs = explode(CRM_Core_DAO::VALUE_SEPARATOR, CRM_Utils_Array::value('payment_processor',
          $this->_values['event']
        ));
        $this->assignPaymentProcessor($isPayLater);
      }

      $priceSetID = $this->getPriceSetID();
      if ($priceSetID) {
        $this->_values['line_items'] = CRM_Price_BAO_LineItem::getLineItems($this->_participantId, 'participant');
        $this->initEventFee();

        //fix for non-upgraded price sets.CRM-4256.
        if (isset($this->_isPaidEvent)) {
          $isPaidEvent = $this->_isPaidEvent;
        }
        else {
          $isPaidEvent = $this->_values['event']['is_monetary'] ?? NULL;
        }
        if ($isPaidEvent && empty($this->getPriceFieldMetaData())) {
          CRM_Core_Error::statusBounce(ts('Click <a href=\'%1\'>CiviEvent >> Manage Event >> Configure >> Event Fees</a> to configure the Fee Level(s) or Price Set for this event.', [1 => CRM_Utils_System::url('civicrm/event/manage/fee', 'reset=1&action=update&id=' . $this->_eventId)]), $this->getInfoPageUrl(), ts('No Fee Level(s) or Price Set is configured for this event.'));
        }
      }

      // get the profile ids
      $ufJoinParams = [
        'entity_table' => 'civicrm_event',
        // CRM-4377: CiviEvent for the main participant, CiviEvent_Additional for additional participants
        'module' => 'CiviEvent',
        'entity_id' => $this->_eventId,
      ];
      [$this->_values['custom_pre_id'], $this->_values['custom_post_id']] = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);

      // set profiles for additional participants
      if ($this->_values['event']['is_multiple_registrations']) {
        // CRM-4377: CiviEvent for the main participant, CiviEvent_Additional for additional participants
        $ufJoinParams['module'] = 'CiviEvent_Additional';

        [$this->_values['additional_custom_pre_id'], $this->_values['additional_custom_post_id'], $preActive, $postActive] = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);

        // CRM-4377: we need to maintain backward compatibility, hence if there is profile for main contact
        // set same profile for additional contacts.
        if ($this->_values['custom_pre_id'] && !$this->_values['additional_custom_pre_id']) {
          $this->_values['additional_custom_pre_id'] = $this->_values['custom_pre_id'];
        }

        if ($this->_values['custom_post_id'] && !$this->_values['additional_custom_post_id']) {
          $this->_values['additional_custom_post_id'] = $this->_values['custom_post_id'];
        }
        // now check for no profile condition, in that case is_active = 0
        if (isset($preActive) && !$preActive) {
          unset($this->_values['additional_custom_pre_id']);
        }
        if (isset($postActive) && !$postActive) {
          unset($this->_values['additional_custom_post_id']);
        }
      }

      $this->assignBillingType();

      if ($this->_values['event']['is_monetary']) {
        CRM_Core_Payment_Form::setPaymentFieldsByProcessor($this, $this->_paymentProcessor);
      }
      $params = ['entity_id' => $this->_eventId, 'entity_table' => 'civicrm_event'];
      $this->_values['location'] = CRM_Core_BAO_Location::getValues($params, TRUE);

      $this->set('values', $this->_values);
      $this->set('fields', $this->_fields);

      $this->_availableRegistrations
        = CRM_Event_BAO_Participant::eventFull(
        $this->_values['event']['id'], TRUE,
        $this->_values['event']['has_waitlist'] ?? NULL
      );
      $this->set('availableRegistrations', $this->_availableRegistrations);
    }
    $this->assign('paymentProcessor', $this->_paymentProcessor);

    // check if this is a paypal auto return and redirect accordingly
    if (CRM_Core_Payment::paypalRedirect($this->_paymentProcessor)) {
      $url = CRM_Utils_System::url('civicrm/event/register',
        "_qf_ThankYou_display=1&qfKey={$this->controller->_key}"
      );
      CRM_Utils_System::redirect($url);
    }

    $this->assign('paidEvent', $this->getEventValue('is_monetary'));
    // we do not want to display recently viewed items on Registration pages
    $this->assign('displayRecent', FALSE);

    $isShowLocation = $this->_values['event']['is_show_location'] ?? NULL;
    $this->assign('isShowLocation', $isShowLocation);
    // Handle PCP
    $pcpId = CRM_Utils_Request::retrieve('pcpId', 'Positive', $this);
    if ($pcpId) {
      $pcp = CRM_PCP_BAO_PCP::handlePcp($pcpId, 'event', $this->_values['event']);
      $this->_pcpId = $pcp['pcpId'];
      $this->_values['event']['intro_text'] = $pcp['pcpInfo']['intro_text'] ?? NULL;
    }

    // assign all event properties so wizard templates can display event info.
    $this->assign('event', $this->_values['event']);
    $this->assign('location', $this->_values['location']);
    $this->assign('bltID', $this->_bltID);
    $isShowLocation = $this->_values['event']['is_show_location'] ?? NULL;
    $this->assign('isShowLocation', $isShowLocation);
    CRM_Contribute_BAO_Contribution_Utils::overrideDefaultCurrency($this->_values['event']);

    //lets allow user to override campaign.
    $campID = CRM_Utils_Request::retrieve('campID', 'Positive', $this);
    if ($campID && CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Campaign', $campID)) {
      $this->_values['event']['campaign_id'] = $campID;
    }

    // Set the same value for is_billing_required as contribution page so code can be shared.
    $this->_values['is_billing_required'] = $this->_values['event']['is_billing_required'] ?? NULL;
    // check if billing block is required for pay later
    // note that I have started removing the use of isBillingAddressRequiredForPayLater in favour of letting
    // the CRM_Core_Payment_Manual class handle it - but there are ~300 references to it in the code base so only
    // removing in very limited cases.
    if (!empty($this->_values['event']['is_pay_later'])) {
      $this->_isBillingAddressRequiredForPayLater = $this->_values['event']['is_billing_required'] ?? NULL;
      $this->assign('isBillingAddressRequiredForPayLater', $this->_isBillingAddressRequiredForPayLater);
    }

    // set the noindex metatag for non-public events
    if (!$this->getEventValue('is_public')) {
      CRM_Utils_System::setNoRobotsFlag();
    }

  }

  /**
   * Assign the minimal set of variables to the template.
   */
  public function assignToTemplate() {
    //process only primary participant params
    $this->_params = $this->get('params');
    if (isset($this->_params[0])) {
      $params = $this->_params[0];
    }
    $name = '';
    if (!empty($params['billing_first_name'])) {
      $name = $params['billing_first_name'];
    }

    if (!empty($params['billing_middle_name'])) {
      $name .= " {$params['billing_middle_name']}";
    }

    if (!empty($params['billing_last_name'])) {
      $name .= " {$params['billing_last_name']}";
    }
    $this->assign('billingName', $name);
    $this->set('name', $name);

    $vars = [
      'amount',
      'currencyID',
      'credit_card_type',
      'trxn_id',
      'amount_level',
      'receive_date',
    ];

    foreach ($vars as $v) {
      if (!empty($params[$v])) {
        if ($v === 'receive_date') {
          $this->assign($v, CRM_Utils_Date::mysqlToIso($params[$v]));
        }
        else {
          $this->assign($v, $params[$v]);
        }
      }
      elseif (empty($params['amount'])) {
        $this->assign($v, $params[$v] ?? NULL);
      }
    }

    $this->assign('address', CRM_Utils_Address::getFormattedBillingAddressFieldsFromParameters($params));

    $this->assign('credit_card_type', $this->getSubmittedValue('credit_card_type'));
    if ($this->getSubmittedValue('credit_card_exp_date')) {
      $date = CRM_Utils_Date::format($this->getSubmittedValue('credit_card_exp_date'));
      $date = CRM_Utils_Date::mysqlToIso($date);
    }
    $this->assign('credit_card_exp_date', $date ?? NULL);
    $this->assign('credit_card_number',
      CRM_Utils_System::mungeCreditCard($this->getSubmittedValue('credit_card_number') ?? '')
    );

    $this->assign('is_email_confirm', $this->_values['event']['is_email_confirm'] ?? NULL);
    // assign pay later stuff
    $isPayLater = empty($this->getSubmittedValue('payment_processor_id'));
    $this->assign('is_pay_later', $isPayLater);
    $this->assign('pay_later_text', $isPayLater ? $this->getPayLaterLabel() : FALSE);
    $this->assign('pay_later_receipt', $isPayLater ? $this->_values['event']['pay_later_receipt'] : NULL);

    // also assign all participantIDs to the template
    // useful in generating confirmation numbers if needed
    $this->assign('participantIDs', $this->_participantIDS);
  }

  /**
   * Add the custom fields.
   *
   * @param int $id
   * @param string $name
   */
  public function buildCustom($id, $name) {
    if ($name === 'customPost' || $name === 'additionalCustomPost') {
      $this->assign('postPageProfiles', []);
    }
    $this->assign($name, []);
    if (!$id) {
      return;
    }

    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    $contactID = CRM_Core_Session::getLoggedInContactID();
    $fields = [];

    // we don't allow conflicting fields to be
    // configured via profile
    $fieldsToIgnore = [
      'participant_fee_amount' => 1,
      'participant_fee_level' => 1,
    ];
    if ($contactID) {
      //FIX CRM-9653
      if (is_array($id)) {
        $fields = [];
        foreach ($id as $profileID) {
          $field = CRM_Core_BAO_UFGroup::getFields($profileID, FALSE, CRM_Core_Action::ADD,
            NULL, NULL, FALSE, NULL,
            FALSE, NULL, CRM_Core_Permission::CREATE,
            'field_name', TRUE
          );
          $fields = array_merge($fields, $field);
        }
      }
      else {
        if (CRM_Core_BAO_UFGroup::filterUFGroups($id, $contactID)) {
          $fields = CRM_Core_BAO_UFGroup::getFields($id, FALSE, CRM_Core_Action::ADD,
            NULL, NULL, FALSE, NULL,
            FALSE, NULL, CRM_Core_Permission::CREATE,
            'field_name', TRUE
          );
        }
      }
    }
    else {
      $fields = CRM_Core_BAO_UFGroup::getFields($id, FALSE, CRM_Core_Action::ADD,
        NULL, NULL, FALSE, NULL,
        FALSE, NULL, CRM_Core_Permission::CREATE,
        'field_name', TRUE
      );
    }

    if (array_intersect_key($fields, $fieldsToIgnore)) {
      $fields = array_diff_key($fields, $fieldsToIgnore);
      CRM_Core_Session::setStatus(ts('Some of the profile fields cannot be configured for this page.'));
    }

    if (!empty($this->_fields)) {
      $fields = @array_diff_assoc($fields, $this->_fields);
    }

    if (empty($this->_params[0]['additional_participants']) &&
      is_null($cid)
    ) {
      CRM_Core_BAO_Address::checkContactSharedAddressFields($fields, $contactID);
    }
    if ($name === 'customPost' || $name === 'additionalCustomPost') {
      $postPageProfiles = [];
      foreach ($fields as $fieldName => $field) {
        $postPageProfiles[$field['groupName']][$fieldName] = $field;
      }
      $this->assign('postPageProfiles', $postPageProfiles);
    }
    // We still assign the customPost in the way we used to because we haven't ruled out being
    // used after the register form - but in the register form it is overwritten by a for-each
    // with the smarty code.
    $this->assign($name, $fields);
    if (is_array($fields)) {
      $button = substr($this->controller->getButtonName(), -4);
      foreach ($fields as $key => $field) {
        //make the field optional if primary participant
        //have been skip the additional participant.
        if ($button == 'skip') {
          $field['is_required'] = FALSE;
        }
        CRM_Core_BAO_UFGroup::buildProfile($this, $field, CRM_Profile_Form::MODE_CREATE, $contactID, TRUE);

        $this->_fields[$key] = $field;
      }
    }
  }

  /**
   * Initiate event fee.
   *
   * @internal function has had several recent signature changes & is expected to be eventually removed.
   */
  private function initEventFee(): void {
    //get the price set fields participant count.
    //get option count info.
    if ($this->getOrder()->isUseParticipantCount()) {
      $optionsCountDetails = [];
      if (!empty($this->_priceSet['fields'])) {
        foreach ($this->_priceSet['fields'] as $field) {
          foreach ($field['options'] as $option) {
            $count = $option['count'] ?? 0;
            $optionsCountDetails['fields'][$field['id']]['options'][$option['id']] = $count;
          }
        }
      }
      $this->_priceSet['optionsCountDetails'] = $optionsCountDetails;
    }

    //get option max value info.
    $optionsMaxValueTotal = 0;
    $optionsMaxValueDetails = [];

    if ($this->isMaxValueValidationRequired()) {
      foreach ($this->getPriceFieldMetaData() as $field) {
        foreach ($field['options'] as $option) {
          $maxVal = $option['max_value'] ?? 0;
          $optionsMaxValueDetails['fields'][$field['id']]['options'][$option['id']] = $maxVal;
          $optionsMaxValueTotal += $maxVal;
        }
      }
      $this->_priceSet['optionsMaxValueDetails'] = $optionsMaxValueDetails;
    }
    $this->set('priceSet', $this->_priceSet);
  }

  protected function initializeOrder(): void {
    $this->order = new CRM_Financial_BAO_Order();
    $this->order->setPriceSetID($this->getPriceSetID());
    $this->order->setIsExcludeExpiredFields(TRUE);
    $this->order->setForm($this);
    foreach ($this->getPriceFieldMetaData() as $priceField) {
      if ($priceField['html_type'] === 'Text') {
        $this->submittableMoneyFields[] = 'price_' . $priceField['id'];
      }
    }
  }

  /**
   * Get the form context.
   *
   * This is important for passing to the buildAmount hook as CiviDiscount checks it.
   *
   * @return string
   */
  public function getFormContext(): string {
    return 'event';
  }

  /**
   * Handle process after the confirmation of payment by User.
   *
   * @param int $contactID
   * @param \CRM_Contribute_BAO_Contribution $contribution
   *
   * @throws \CRM_Core_Exception
   */
  public function confirmPostProcess($contactID = NULL, $contribution = NULL) {
    //to avoid conflict overwrite $this->_params
    $this->_params = $this->get('value');

    //get the amount of primary participant
    if (!empty($this->_params['is_primary'])) {
      $this->_params['fee_amount'] = $this->get('primaryParticipantAmount');
    }

    // add participant record
    $participant = $this->addParticipant($this, $contactID);
    $this->_participantIDS[] = $participant->id;

    //setting register_by_id field and primaryContactId
    if (!empty($this->_params['is_primary'])) {
      $this->set('registerByID', $participant->id);
      $this->set('primaryContactId', $contactID);

      // CRM-10032
      $this->processFirstParticipant($participant->id);
    }

    if (!empty($this->_params['is_primary'])) {
      $this->_params['participantID'] = $participant->id;
      $this->set('primaryParticipant', $this->_params);
    }

    $createPayment = ($this->_params['amount'] ?? 0) != 0;

    // force to create zero amount payment, CRM-5095
    // we know the amout is zero since createPayment is false
    if (!$createPayment &&
      (isset($contribution) && $contribution->id) &&
      $this->_priceSetId &&
      $this->_lineItem
    ) {
      $createPayment = TRUE;
    }

    if ($createPayment && $this->_values['event']['is_monetary'] && !empty($this->_params['contributionID'])) {
      $paymentParams = [
        'participant_id' => $participant->id,
        'contribution_id' => $contribution->id,
      ];
      civicrm_api3('ParticipantPayment', 'create', $paymentParams);
    }

    $this->assign('action', $this->_action);

    // create CMS user
    if (!empty($this->_params['cms_create_account'])) {
      $this->_params['contactID'] = $contactID;

      if (array_key_exists('email-5', $this->_params)) {
        $mail = 'email-5';
      }
      else {
        foreach ($this->_params as $name => $dontCare) {
          if (substr($name, 0, 5) == 'email') {
            $mail = $name;
            break;
          }
        }
      }

      // we should use primary email for
      // 1. pay later participant.
      // 2. waiting list participant.
      // 3. require approval participant.
      if (!empty($this->_params['is_pay_later']) ||
        $this->_allowWaitlist || $this->_requireApproval
      ) {
        $mail = 'email-Primary';
      }

      if (!CRM_Core_BAO_CMSUser::create($this->_params, $mail)) {
        CRM_Core_Error::statusBounce(ts('Your profile is not saved and Account is not created.'));
      }
    }
  }

  /**
   * Process the participant.
   *
   * @param CRM_Core_Form $form
   * @param int $contactID
   *
   * @return \CRM_Event_BAO_Participant
   * @throws \CRM_Core_Exception
   */
  protected function addParticipant(&$form, $contactID) {
    if (empty($form->_params)) {
      return NULL;
    }
    // Note this used to be shared with the backoffice form & no longer is, some code may no longer be required.
    $params = $form->_params;
    $transaction = new CRM_Core_Transaction();

    // handle register date CRM-4320
    $registerDate = NULL;
    if (!empty($form->_allowConfirmation) && $form->_participantId) {
      $registerDate = $params['participant_register_date'];
    }
    elseif (!empty($params['participant_register_date']) &&
      is_array($params['participant_register_date']) &&
      !empty($params['participant_register_date'])
    ) {
      $registerDate = CRM_Utils_Date::format($params['participant_register_date']);
    }

    $participantFields = CRM_Event_DAO_Participant::fields();
    $participantParams = [
      'id' => $params['participant_id'] ?? NULL,
      'contact_id' => $contactID,
      'event_id' => $form->_eventId ?: $params['event_id'],
      'status_id' => $params['participant_status'] ?? 1,
      'role_id' => $params['participant_role_id'] ?? CRM_Event_BAO_Participant::getDefaultRoleID(),
      'register_date' => ($registerDate) ? $registerDate : date('YmdHis'),
      'source' => CRM_Utils_String::ellipsify($params['participant_source'] ?? $params['description'] ?? '',
        $participantFields['participant_source']['maxlength']
      ),
      'fee_level' => $params['amount_level'] ?? NULL,
      'is_pay_later' => $params['is_pay_later'] ?? 0,
      'fee_amount' => $params['fee_amount'] ?? NULL,
      'registered_by_id' => $params['registered_by_id'] ?? NULL,
      'discount_id' => $params['discount_id'] ?? NULL,
      'fee_currency' => $params['currencyID'] ?? NULL,
      'campaign_id' => $params['campaign_id'] ?? NULL,
    ];

    if ($form->_action & CRM_Core_Action::PREVIEW || (($params['mode'] ?? NULL) === 'test')) {
      $participantParams['is_test'] = 1;
    }
    else {
      $participantParams['is_test'] = 0;
    }

    if (!empty($form->_params['note'])) {
      $participantParams['note'] = $form->_params['note'];
    }
    elseif (!empty($form->_params['participant_note'])) {
      $participantParams['note'] = $form->_params['participant_note'];
    }

    // reuse id if one already exists for this one (can happen
    // with back button being hit etc)
    if (!$participantParams['id'] && !empty($params['contributionID'])) {
      $pID = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment',
        $params['contributionID'],
        'participant_id',
        'contribution_id'
      );
      $participantParams['id'] = $pID;
    }
    $participantParams['discount_id'] = CRM_Core_BAO_Discount::findSet($form->_eventId, 'civicrm_event');

    if (!$participantParams['discount_id']) {
      $participantParams['discount_id'] = "null";
    }

    $participantParams['custom'] = [];
    foreach ($form->_params as $paramName => $paramValue) {
      if (str_starts_with($paramName, 'custom_')) {
        [$customFieldID, $customValueID] = CRM_Core_BAO_CustomField::getKeyID($paramName, TRUE);
        CRM_Core_BAO_CustomField::formatCustomField($customFieldID, $participantParams['custom'], $paramValue, 'Participant', $customValueID);

      }
    }

    $participant = CRM_Event_BAO_Participant::create($participantParams);

    $transaction->commit();

    return $participant;
  }

  /**
   * Get the array of price field value IDs on the form that 'count' as
   * full.
   *
   * The criteria for full is slightly confusing as it has an exclusion around
   * select fields if they are the default - or something...
   *
   * @param array $field
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getOptionFullPriceFieldValues(array $field): array {
    $optionFullIds = [];
    foreach ($field['options'] ?? [] as &$option) {
      if ($this->isOptionFullID($option, $field)) {
        $optionFullIds[$option['id']] = $option['id'];
      }
    }
    return $optionFullIds;
  }

  /**
   * Is the option a 'full ID'.
   *
   * It is not clear why this is different to the is_full calculation
   * but it is used in a less narrow context, around validation.
   *
   * Ideally figure it out & update this doc block.
   *
   * @param array $option
   * @param array $field
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  private function isOptionFullID(array $option, array $field) : bool {
    $fieldId = $option['price_field_id'];
    $currentParticipantNo = (int) substr($this->_name, 12);
    $defaultPricefieldIds = [];
    if (!empty($this->_values['line_items'])) {
      foreach ($this->_values['line_items'] as $lineItem) {
        $defaultPricefieldIds[] = $lineItem['price_field_value_id'];
      }
    }
    $formattedPriceSetDefaults = [];
    if (!empty($this->_allowConfirmation) && (isset($this->_pId) || isset($this->_additionalParticipantId))) {
      $participantId = $this->_pId ?? $this->_additionalParticipantId;
      $pricesetDefaults = CRM_Event_Form_EventFees::setDefaultPriceSet($participantId,
        $this->getEventID()
      );
      // modify options full to respect the selected fields
      // options on confirmation.
      $formattedPriceSetDefaults = self::formatPriceSetParams($this, $pricesetDefaults);
    }

    //get the current price event price set options count.
    $currentOptionsCount = $this->getPriceSetOptionCount();
    $optId = $option['id'];
    $count = $option['count'] ?? 0;
    $currentTotalCount = $currentOptionsCount[$optId] ?? 0;
    $isOptionFull = FALSE;
    $totalCount = $currentTotalCount + $this->getUsedSeatsCount($optId);
    if ($option['max_value'] &&
      (($totalCount >= $option['max_value']) &&
        (empty($this->_lineItem[$currentParticipantNo][$optId]['price_field_id']) || $this->getUsedSeatsCount($optId) >= $option['max_value']))
    ) {
      $isOptionFull = TRUE;
      if ($field['html_type'] === 'Select') {
        if (!empty($defaultPricefieldIds) && in_array($optId, $defaultPricefieldIds)) {
          $isOptionFull = FALSE;
        }
      }
    }
    //here option is not full,
    //but we don't want to allow participant to increase
    //seats at the time of re-walking registration.
    if ($count &&
      !empty($this->_allowConfirmation) &&
      !empty($formattedPriceSetDefaults)
    ) {
      if (empty($formattedPriceSetDefaults["price_{$fieldId}"]) || empty($formattedPriceSetDefaults["price_{$fieldId}"][$optId])) {
        $isOptionFull = TRUE;
      }
    }
    return $isOptionFull;
  }

  /**
   * Should this option be disabled on the basis of being full.
   *
   * Note there is another full calculation that is slightly different for
   * ... reasons? When we figure out what those are we can update this.
   *
   * @param array $option
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  protected function getIsOptionFull(array $option): bool {
    $isFull = FALSE;
    $currentParticipantNo = (int) substr($this->_name, 12);
    $formattedPriceSetDefaults = [];
    $maxValue = $option['max_value'] ?? 0;
    $priceFieldValueID = $option['id'];
    //get the current price event price set options count.
    $currentOptionsCount = $this->getPriceSetOptionCount();
    $currentTotalCount = $currentOptionsCount[$priceFieldValueID] ?? 0;

    $totalCount = $currentTotalCount + $this->getUsedSeatsCount($priceFieldValueID);
    if (!empty($form->_allowConfirmation) && (isset($form->_pId) || isset($form->_additionalParticipantId))) {
      $participantId = $form->_pId ?? $form->_additionalParticipantId;
      $pricesetDefaults = CRM_Event_Form_EventFees::setDefaultPriceSet($participantId,
        $this->getEventID()
      );
      // modify options full to respect the selected fields
      // options on confirmation.
      $formattedPriceSetDefaults = self::formatPriceSetParams($form, $pricesetDefaults);
    }
    $count = $option['count'] ?? 0;
    if ($maxValue &&
      (($totalCount >= $maxValue) &&
        (empty($this->_lineItem[$currentParticipantNo][$priceFieldValueID]['price_field_id']) || $this->getUsedSeatsCount($priceFieldValueID) >= $maxValue))
    ) {
      $isFull = TRUE;
    }
    //here option is not full,
    //but we don't want to allow participant to increase
    //seats at the time of re-walking registration.
    if ($count &&
      !empty($this->_allowConfirmation) &&
      !empty($formattedPriceSetDefaults)
    ) {
      if (empty($formattedPriceSetDefaults["price_{$option['price_field_id']}"]) || empty($formattedPriceSetDefaults["price_{$option['price_field_id']}"][$priceFieldValueID])) {
        $isFull = TRUE;
      }
    }
    return $isFull;
  }

  /**
   * Get the used seat count for the price value option
   *
   * @return int
   * @throws \CRM_Core_Exception
   */
  protected function getUsedSeatsCount(int $priceFieldValueID) : int {
    $skipParticipants = [];
    if (!empty($this->_allowConfirmation) && (isset($this->_pId) || isset($this->_additionalParticipantId))) {
      // to skip current registered participants fields option count on confirmation.
      $skipParticipants[] = $this->_participantId;
      if (!empty($this->_additionalParticipantIds)) {
        $skipParticipants = array_merge($skipParticipants, $this->_additionalParticipantIds);
      }
    }
    $this->optionsCount = CRM_Event_BAO_Participant::priceSetOptionsCount($this->getEventID(), $skipParticipants);
    return $this->optionsCount[$priceFieldValueID] ?? 0;
  }

  /**
   * Calculate the total participant count as per params.
   *
   * @param array $params
   *   User params.
   * @param bool $skipCurrent
   *
   * @return int
   */
  protected function getParticipantCount($params, $skipCurrent = FALSE) {
    $totalCount = 0;
    $form = $this;
    if (!is_array($params) || empty($params)) {
      return $totalCount;
    }

    $priceSetId = $form->get('priceSetId');
    $addParticipantNum = substr($form->_name, 12);
    $priceSetFields = [];
    $hasPriceFieldsCount = FALSE;
    if ($priceSetId) {
      $priceSetDetails = $form->get('priceSet');
      if ($form->getOrder()->isUseParticipantCount()) {
        $hasPriceFieldsCount = TRUE;
        $priceSetFields = $priceSetDetails['optionsCountDetails']['fields'];
      }
    }

    $singleFormParams = FALSE;
    foreach ($params as $key => $val) {
      if (!is_numeric($key)) {
        $singleFormParams = TRUE;
        break;
      }
    }

    //first format the params.
    if ($singleFormParams) {
      $params = self::formatPriceSetParams($form, $params);
      $params = [$params];
    }

    foreach ($params as $key => $values) {
      if (!is_numeric($key) ||
        $values == 'skip' ||
        ($skipCurrent && ($addParticipantNum == $key))
      ) {
        continue;
      }
      $count = 1;

      $usedCache = FALSE;
      $cacheCount = $form->_lineItemParticipantsCount[$key] ?? NULL;
      if ($cacheCount && is_numeric($cacheCount)) {
        $count = $cacheCount;
        $usedCache = TRUE;
      }

      if (!$usedCache && $hasPriceFieldsCount) {
        $count = 0;
        foreach ($values as $valKey => $value) {
          if (!str_contains($valKey, 'price_')) {
            continue;
          }
          $priceFieldId = substr($valKey, 6);
          if (!$priceFieldId ||
            !is_array($value) ||
            !array_key_exists($priceFieldId, $priceSetFields)
          ) {
            continue;
          }
          foreach ($value as $optId => $optVal) {
            $currentCount = $priceSetFields[$priceFieldId]['options'][$optId] * $optVal;
            if ($currentCount) {
              $count += $currentCount;
            }
          }
        }
        if (!$count) {
          $count = 1;
        }
      }
      $totalCount += $count;
    }
    if (!$totalCount) {
      $totalCount = 1;
    }

    return $totalCount;
  }

  /**
   * Get id of participant being acted on.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   */
  public function getParticipantID(): ?int {
    return $this->_participantId;
  }

  /**
   * Format user submitted price set params.
   *
   * Convert price set each param as an array.
   *
   * @param self $form
   * @param array $params
   *   An array of user submitted params.
   *
   * @return array
   *   Formatted price set params.
   */
  public static function formatPriceSetParams(&$form, $params) {
    if (!is_array($params) || empty($params)) {
      return $params;
    }

    $priceSetId = $form->get('priceSetId');
    if (!$priceSetId) {
      return $params;
    }
    $priceSetDetails = $form->get('priceSet');

    foreach ($params as $key => & $value) {
      $vals = [];
      if (str_contains($key, 'price_')) {
        $fieldId = substr($key, 6);
        if (!array_key_exists($fieldId, $priceSetDetails['fields']) ||
          is_array($value) ||
          !$value
        ) {
          continue;
        }
        $field = $priceSetDetails['fields'][$fieldId];
        if ($field['html_type'] == 'Text') {
          $fieldOption = current($field['options']);
          $value = [$fieldOption['id'] => $value];
        }
        else {
          $value = [$value => TRUE];
        }
      }
    }

    return $params;
  }

  /**
   * Calculate total count for each price set options.
   *
   * - currently selected by user.
   *
   * @return array
   *   array of each option w/ count total.
   */
  protected function getPriceSetOptionCount() {
    $form = $this;
    $params = $form->get('params');
    $priceSet = $form->get('priceSet');
    $priceSetId = $form->get('priceSetId');

    $optionsCount = [];
    if (!$priceSetId ||
      !is_array($priceSet) ||
      empty($priceSet) ||
      !is_array($params) ||
      empty($params)
    ) {
      return $optionsCount;
    }

    $priceSetFields = $priceMaxFieldDetails = [];
    if ($form->getOrder()->isUseParticipantCount()) {
      $priceSetFields = $priceSet['optionsCountDetails']['fields'];
    }

    if ($this->isMaxValueValidationRequired()) {
      $priceMaxFieldDetails = $priceSet['optionsMaxValueDetails']['fields'];
    }

    $addParticipantNum = substr($form->_name, 12);
    foreach ($params as $pCnt => $values) {
      if ($values == 'skip' ||
        $pCnt === $addParticipantNum
      ) {
        continue;
      }

      foreach ($values as $valKey => $value) {
        if (!str_contains($valKey, 'price_')) {
          continue;
        }

        $priceFieldId = substr($valKey, 6);
        if (!$priceFieldId ||
          !is_array($value) ||
          !(array_key_exists($priceFieldId, $priceSetFields) || array_key_exists($priceFieldId, $priceMaxFieldDetails))
        ) {
          continue;
        }

        foreach ($value as $optId => $optVal) {
          if (($priceSet['fields'][$priceFieldId]['html_type'] ?? NULL) === 'Text') {
            $currentCount = $optVal;
          }
          else {
            $currentCount = 1;
          }

          if (isset($priceSetFields[$priceFieldId]) && isset($priceSetFields[$priceFieldId]['options'][$optId])) {
            $currentCount = $priceSetFields[$priceFieldId]['options'][$optId] * $optVal;
          }

          $optionsCount[$optId] = $currentCount + ($optionsCount[$optId] ?? 0);
        }
      }
    }

    return $optionsCount;
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
    if ($this->_eventId) {
      $templateName = $this->_name;
      if (substr($templateName, 0, 12) == 'Participant_') {
        $templateName = 'AdditionalParticipant';
      }

      $templateFile = "CRM/Event/Form/Registration/{$this->_eventId}/{$templateName}.{$suffix}tpl";
      $template = CRM_Core_Form::getTemplate();
      if ($template->template_exists($templateFile)) {
        return $templateFile;
      }
    }
    return NULL;
  }

  /**
   * Get template file name.
   *
   * @return null|string
   */
  public function getTemplateFileName() {
    $fileName = $this->checkTemplateFileExists();
    return $fileName ?: parent::getTemplateFileName();
  }

  /**
   * Override extra template name.
   *
   * @return null|string
   */
  public function overrideExtraTemplateFileName() {
    $fileName = $this->checkTemplateFileExists('extra.');
    return $fileName ?: parent::overrideExtraTemplateFileName();
  }

  /**
   * Reset values for all options those are full.
   *
   * @param array $optionFullIds
   * @param CRM_Core_Form $form
   */
  public static function resetElementValue($optionFullIds, &$form) {
    if (!is_array($optionFullIds) ||
      empty($optionFullIds) ||
      !$form->isSubmitted()
    ) {
      return;
    }

    foreach ($optionFullIds as $fldId => $optIds) {
      $name = "price_$fldId";
      if (!$form->elementExists($name)) {
        continue;
      }

      $element = $form->getElement($name);
      $eleType = $element->getType();

      $resetSubmitted = FALSE;
      switch ($eleType) {
        case 'text':
          if ($element->getValue() && $element->isFrozen()) {
            $label = "{$element->getLabel()}<tt>(x)</tt>";
            $element->setLabel($label);
            $element->setPersistantFreeze();
            $resetSubmitted = TRUE;
          }
          break;

        case 'group':
          if (is_array($element->_elements)) {
            foreach ($element->_elements as $child) {
              $childType = $child->getType();
              $methodName = 'getName';
              if ($childType) {
                $methodName = 'getValue';
              }
              if (in_array($child->{$methodName}(), $optIds) && $child->isFrozen()) {
                $resetSubmitted = TRUE;
                $child->setPersistantFreeze();
              }
            }
          }
          break;

        case 'select':
          $value = $element->getValue();
          if (in_array($value[0], $optIds)) {
            foreach ($element->_options as $option) {
              if ($option['attr']['value'] === "crm_disabled_opt-{$value[0]}") {
                $placeholder = html_entity_decode($option['text'], ENT_QUOTES, "UTF-8");
                $element->updateAttributes(['placeholder' => $placeholder]);
                break;
              }
            }
            $resetSubmitted = TRUE;
          }
          break;
      }

      //finally unset values from submitted.
      if ($resetSubmitted) {
        self::resetSubmittedValue($name, $optIds, $form);
      }
    }
  }

  /**
   * Reset submitted value.
   *
   * @param string $elementName
   * @param array $optionIds
   * @param CRM_Core_Form $form
   */
  public static function resetSubmittedValue($elementName, $optionIds, &$form) {
    if (empty($elementName) ||
      !$form->elementExists($elementName) ||
      !$form->getSubmitValue($elementName)
    ) {
      return;
    }
    foreach (['constantValues', 'submitValues', 'defaultValues'] as $val) {
      $values = $form->{"_$val"};
      if (!is_array($values) || empty($values)) {
        continue;
      }
      $eleVal = $values[$elementName] ?? NULL;
      if (empty($eleVal)) {
        continue;
      }
      if (is_array($eleVal)) {
        $found = FALSE;
        foreach ($eleVal as $keyId => $ignore) {
          if (in_array($keyId, $optionIds)) {
            $found = TRUE;
            unset($values[$elementName][$keyId]);
          }
        }
        if ($found && empty($values[$elementName][$keyId])) {
          $values[$elementName][$keyId] = NULL;
        }
      }
      else {
        if (!empty($keyId)) {
          $values[$elementName][$keyId] = NULL;
        }
      }
    }
  }

  /**
   * Get the number of available spaces in the given event.
   *
   * @internal this is a transitional function to handle this form's
   * odd behaviour whereby sometimes the fetched value is text. We need to wean
   * the places that access it off this...
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
   */
  protected function getAvailableSpaces(): int {
    // non numeric would be 'event full text'....
    return is_numeric($this->_availableRegistrations) ? (int) $this->_availableRegistrations : 0;
  }

  /**
   * Validate price set submitted params for price option limit.
   *
   * User should select at least one price field option.
   *
   * @param array $params
   * @param int $priceSetId
   * @param array $priceSetDetails
   *
   * @return array
   */
  protected function validatePriceSet(array $params, $priceSetId, $priceSetDetails) {
    $errors = [];
    $hasOptMaxValue = FALSE;
    if (!is_array($params) || empty($params)) {
      return $errors;
    }
    if (
      !$priceSetId ||
      !is_array($priceSetDetails) ||
      empty($priceSetDetails)
    ) {
      return $errors;
    }

    $optionsCountDetails = $optionsMaxValueDetails = [];
    if (
      $this->isMaxValueValidationRequired()
    ) {
      $hasOptMaxValue = TRUE;
      $optionsMaxValueDetails = $priceSetDetails['optionsMaxValueDetails']['fields'];
    }

    if ($this->getOrder()->isUseParticipantCount()) {
      $hasOptCount = TRUE;
      $optionsCountDetails = $priceSetDetails['optionsCountDetails']['fields'];
    }

    $optionMaxValues = $fieldSelected = [];
    foreach ($params as $pNum => $values) {
      if (!is_array($values) || $values == 'skip') {
        continue;
      }

      foreach ($values as $valKey => $value) {
        if (!str_contains($valKey, 'price_')) {
          continue;
        }
        $priceFieldId = substr($valKey, 6);
        $noneOptionValueSelected = FALSE;
        if (!$this->getPriceFieldMetaData()[$priceFieldId]['is_required'] && $value == 0) {
          $noneOptionValueSelected = TRUE;
        }

        if (
          !$priceFieldId ||
          (!$noneOptionValueSelected && !is_array($value))
        ) {
          continue;
        }

        $fieldSelected[$pNum] = TRUE;

        if (!$hasOptMaxValue || !is_array($value)) {
          continue;
        }

        foreach ($value as $optId => $optVal) {
          if (($this->getPriceFieldMetaData()[$priceFieldId]['html_type'] ?? NULL) === 'Text') {
            $currentMaxValue = $optVal;
          }
          else {
            $currentMaxValue = 1;
          }

          if (isset($optionsCountDetails[$priceFieldId]) && isset($optionsCountDetails[$priceFieldId]['options'][$optId])) {
            $currentMaxValue = $optionsCountDetails[$priceFieldId]['options'][$optId] * $optVal;
          }
          if (empty($optionMaxValues)) {
            $optionMaxValues[$priceFieldId][$optId] = $currentMaxValue;
          }
          else {
            $optionMaxValues[$priceFieldId][$optId] = $currentMaxValue + ($optionMaxValues[$priceFieldId][$optId] ?? 0);
          }
          $soldOutPnum[$optId] = $pNum;
        }
      }

      //validate for price field selection.
      if (empty($fieldSelected[$pNum])) {
        $errors[$pNum]['_qf_default'] = ts('SELECT at least one OPTION FROM EVENT Fee(s).');
      }
    }

    //validate for option max value.
    foreach ($optionMaxValues as $fieldId => $values) {
      foreach ($values as $optId => $total) {
        $optMax = $optionsMaxValueDetails[$fieldId]['options'][$optId];
        $opDbCount = $this->getUsedSeatsCount($optId);
        $total += $opDbCount;
        if ($optMax && ($total > $optMax)) {
          if ($opDbCount && ($opDbCount >= $optMax)) {
            $errors[$soldOutPnum[$optId]]["price_{$fieldId}"]
              = ts('Sorry, this option is currently sold out.');
          }
          elseif (($optMax - $opDbCount) == 1) {
            $errors[$soldOutPnum[$optId]]["price_{$fieldId}"]
              = ts('Sorry, currently only a single space is available for this option.', [1 => ($optMax - $opDbCount)]);
          }
          else {
            $errors[$soldOutPnum[$optId]]["price_{$fieldId}"]
              = ts('Sorry, currently only %1 spaces are available for this option.', [1 => ($optMax - $opDbCount)]);
          }
        }
      }
    }
    return $errors;
  }

  /**
   * Get the submitted value, accessing it from whatever form in the flow it is
   * submitted on.
   *
   * @todo support AdditionalParticipant forms too.
   *
   * @param string $fieldName
   *
   * @return mixed|null
   */
  public function getSubmittedValue(string $fieldName) {
    if ($this->isShowPaymentOnConfirm() && in_array($this->getName(), ['Confirm', 'ThankYou'], TRUE)) {
      $value = $this->controller->exportValue('Confirm', $fieldName);
    }
    else {
      // If we are on the Confirm or ThankYou page then the submitted values
      // were on the Register Page so we return them
      $value = $this->controller->exportValue('Register', $fieldName);
    }
    if (!isset($value)) {
      $value = parent::getSubmittedValue($fieldName);
    }
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
   * Set the first participant ID if not set.
   *
   * CRM-10032.
   *
   * @param int $participantID
   */
  public function processFirstParticipant($participantID) {
    $this->_participantId = $participantID;
    $this->set('participantId', $this->_participantId);

    $ids = $participantValues = [];
    $participantParams = ['id' => $this->_participantId];
    CRM_Event_BAO_Participant::getValues($participantParams, $participantValues, $ids);
    $this->_values['participant'] = $participantValues[$this->_participantId];
    $this->set('values', $this->_values);

    // also set the allow confirmation stuff
    if (array_key_exists(
      $this->_values['participant']['status_id'],
      CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Pending'")
    )) {
      $this->_allowConfirmation = TRUE;
      $this->set('allowConfirmation', TRUE);
    }
  }

  /**
   * Check if event is valid.
   *
   * @todo - combine this with CRM_Event_BAO_Event::validRegistrationRequest
   * (probably extract relevant values here & call that with them & handle bounces & redirects here -as
   * those belong in the form layer)
   *
   */
  protected function checkValidEvent(): void {
    // is the event active (enabled)?
    if (!$this->_values['event']['is_active']) {
      // Form is inactive, redirect to the list of events
      $urlList = CRM_Utils_System::url('civicrm/event/list', FALSE, NULL, FALSE, TRUE);
      CRM_Core_Error::statusBounce(ts('The event you requested is currently unavailable (contact the site administrator for assistance).'), $urlList);
    }

    // is online registration is enabled?
    if (!$this->_values['event']['is_online_registration']) {
      CRM_Core_Error::statusBounce(ts('Online registration is not currently available for this event (contact the site administrator for assistance).'), $this->getInfoPageUrl());
    }

    // is this an event template ?
    if (!empty($this->_values['event']['is_template'])) {
      CRM_Core_Error::statusBounce(ts('Event templates are not meant to be registered.'), $this->getInfoPageUrl());
    }

    $now = date('YmdHis');
    $startDate = CRM_Utils_Date::processDate($this->_values['event']['registration_start_date'] ?? NULL);

    if ($startDate && ($startDate >= $now)) {
      CRM_Core_Error::statusBounce(ts('Registration for this event begins on %1',
        [1 => CRM_Utils_Date::customFormat($this->_values['event']['registration_start_date'] ?? NULL)]),
        $this->getInfoPageUrl(),
        ts('Sorry'));
    }

    $regEndDate = CRM_Utils_Date::processDate($this->_values['event']['registration_end_date'] ?? NULL);
    $eventEndDate = CRM_Utils_Date::processDate($this->_values['event']['event_end_date'] ?? NULL);
    if (($regEndDate && ($regEndDate < $now)) || (empty($regEndDate) && !empty($eventEndDate) && ($eventEndDate < $now))) {
      $endDate = CRM_Utils_Date::customFormat($this->_values['event']['registration_end_date'] ?? NULL);
      if (empty($regEndDate)) {
        $endDate = CRM_Utils_Date::customFormat($this->_values['event']['event_end_date'] ?? NULL);
      }
      CRM_Core_Error::statusBounce(ts('Registration for this event ended on %1', [1 => $endDate]), $this->getInfoPageUrl(), ts('Sorry'));
    }
  }

  /**
   * Get the amount level for the event payment.
   *
   * The amount level is the string stored on the contribution record that describes the purchase.
   *
   * @param array $params
   * @param int|null $discountID
   *
   * @return string
   */
  protected function getAmountLevel($params, $discountID) {
    // @todo move handling of discount ID to the BAO function - preferably by converting it to a price_set with
    // time settings.
    if (!empty($this->_values['discount'][$discountID])) {
      return $this->_values['discount'][$discountID][$params['amount']]['label'];
    }
    if (empty($params['priceSetId'])) {
      // CRM-17509 An example of this is where the person is being waitlisted & there is no payment.
      // ideally we would have calculated amount first & only call this is there is an
      // amount but the flow needs more changes for that.
      return '';
    }
    return CRM_Price_BAO_PriceSet::getAmountLevelText($params);
  }

  /**
   * Process Registration of free event.
   *
   * @param array $params
   *   Form values.
   * @param int $contactID
   *
   * @throws \CRM_Core_Exception
   */
  public function processRegistration($params, $contactID = NULL) {
    $session = CRM_Core_Session::singleton();
    $participantInfo = [];

    // CRM-4320, lets build array of cancelled additional participant ids
    // those are drop or skip by primary at the time of confirmation.
    // get all in and then unset those are confirmed.
    $cancelledIds = $this->_additionalParticipantIds;

    $participantCount = [];
    foreach ($params as $participantNum => $record) {
      if ($record == 'skip') {
        $participantCount[$participantNum] = 'skip';
      }
      elseif ($participantNum) {
        $participantCount[$participantNum] = 'participant';
      }
    }

    $registerByID = NULL;
    foreach ($params as $key => $value) {
      if ($value != 'skip') {
        $fields = NULL;

        // setting register by Id and unset contactId.
        if (empty($value['is_primary'])) {
          $contactID = NULL;
          $registerByID = $this->get('registerByID');
          if ($registerByID) {
            $value['registered_by_id'] = $registerByID;
          }
          // get an email if one exists for the participant
          $participantEmail = '';
          foreach (array_keys($value) as $valueName) {
            if (substr($valueName, 0, 6) == 'email-') {
              $participantEmail = $value[$valueName];
            }
          }
          if ($participantEmail) {
            $participantInfo[] = $participantEmail;
          }
          else {
            $participantInfo[] = $value['first_name'] . ' ' . $value['last_name'];
          }
        }
        elseif (!empty($value['contact_id'])) {
          $contactID = $value['contact_id'];
        }
        else {
          $contactID = $this->getContactID();
        }

        CRM_Event_Form_Registration_Confirm::fixLocationFields($value, $fields, $this);
        //for free event or additional participant, dont create billing email address.
        if (empty($value['is_primary'])) {
          unset($value["email-{$this->_bltID}"]);
        }

        $contactID = CRM_Event_Form_Registration_Confirm::updateContactFields($contactID, $value, $fields, $this);

        // lets store the contactID in the session
        // we dont store in userID in case the user is doing multiple
        // transactions etc
        // for things like tell a friend
        if (!$this->getContactID() && !empty($value['is_primary'])) {
          $session->set('transaction.userID', $contactID);
        }

        //lets get the status if require approval or waiting.

        $waitingStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Waiting'");
        if ($this->_allowWaitlist && !$this->_allowConfirmation) {
          $value['participant_status_id'] = $value['participant_status'] = array_search('On waitlist', $waitingStatuses);
        }
        elseif ($this->_requireApproval && !$this->_allowConfirmation) {
          $value['participant_status_id'] = $value['participant_status'] = array_search('Awaiting approval', $waitingStatuses);
        }

        $this->set('value', $value);
        $this->confirmPostProcess($contactID, NULL);

        //lets get additional participant id to cancel.
        if ($this->_allowConfirmation && is_array($cancelledIds)) {
          $additionalId = $value['participant_id'] ?? NULL;
          if ($additionalId && $key = array_search($additionalId, $cancelledIds)) {
            unset($cancelledIds[$key]);
          }
        }
      }
    }

    // update status and send mail to cancelled additional participants, CRM-4320
    if ($this->_allowConfirmation && is_array($cancelledIds) && !empty($cancelledIds)) {
      $cancelledId = array_search('Cancelled',
        CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Negative'")
      );
      CRM_Event_BAO_Participant::transitionParticipants($cancelledIds, $cancelledId);
    }

    //set information about additional participants if exists
    if (count($participantInfo)) {
      $this->set('participantInfo', $participantInfo);
    }

    if (!$this->getEventValue('is_monetary') || $this->getPaymentProcessorObject()->supports('noReturn')
    ) {
      // Send mail Confirmation/Receipt.
      $this->sendMails($params, $registerByID, $participantCount);
    }
  }

  /**
   * Send Mail to participants.
   *
   * @param $params
   * @param $registerByID
   * @param array $participantCount
   *
   * @throws \CRM_Core_Exception
   */
  private function sendMails($params, $registerByID, array $participantCount) {
    $isTest = FALSE;
    if ($this->_action & CRM_Core_Action::PREVIEW) {
      $isTest = TRUE;
    }

    //handle if no additional participant.
    if (!$registerByID) {
      $registerByID = $this->get('registerByID');
    }
    $primaryContactId = $this->get('primaryContactId');

    //build an array of custom profile and assigning it to template.
    // @todo - don't call buildCustomProfile to get additionalParticipants.
    // CRM_Event_BAO_Participant::getAdditionalParticipantIds is a better fit.
    $additionalIDs = CRM_Event_BAO_Event::buildCustomProfile($registerByID, NULL,
      $primaryContactId, $isTest, TRUE
    );

    //lets carry all participant params w/ values.
    foreach ($additionalIDs as $participantID => $contactId) {
      $participantNum = NULL;
      if ($participantID == $registerByID) {
        $participantNum = 0;
      }
      else {
        if ($participantNum = array_search('participant', $participantCount)) {
          unset($participantCount[$participantNum]);
        }
      }

      if ($participantNum === NULL) {
        break;
      }

      //carry the participant submitted values.
      $this->_values['params'][$participantID] = $params[$participantNum];
    }

    //lets send  mails to all with meanigful text, CRM-4320.
    $this->assign('isOnWaitlist', $this->_allowWaitlist);
    $this->assign('isRequireApproval', $this->_requireApproval);

    foreach ($additionalIDs as $participantID => $contactId) {
      if ($participantID == $registerByID) {
        $customProfile = CRM_Event_BAO_Event::buildCustomProfile($participantID, $this->_values, NULL, $isTest);

        if (count($customProfile)) {
          $this->assign('customProfile', $customProfile);
          $this->set('customProfile', $customProfile);
        }
      }
      else {
        $this->assign('customProfile', NULL);
      }

      //send Confirmation mail to Primary & additional Participants if exists
      CRM_Event_BAO_Event::sendMail($contactId, $this->_values, $participantID, $isTest);
    }
  }

  /**
   * Get redirect URL to send folks back to event info page is registration not available.
   *
   * @return string
   */
  private function getInfoPageUrl(): string {
    return CRM_Utils_System::url('civicrm/event/info', 'reset=1&id=' . $this->getEventID(),
      FALSE, NULL, FALSE, TRUE
    );
  }

  /**
   * Get the discount ID.
   *
   * @return int|null
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  protected function getDiscountID(): ?int {
    $id = CRM_Core_BAO_Discount::findSet($this->getEventID(), 'civicrm_event');
    return $id ?: NULL;
  }

  /**
   * Get the price set ID for the event.
   *
   * @return int|null
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  public function getPriceSetID(): ?int {
    if ($this->_priceSetId === NULL) {
      if ($this->getDiscountID()) {
        $this->_priceSetId = (int) CRM_Core_DAO::getFieldValue('CRM_Core_BAO_Discount', $this->getDiscountID(), 'price_set_id');
      }
      else {
        $this->_priceSetId = (int) CRM_Price_BAO_PriceSet::getFor('civicrm_event', $this->getEventID());
      }
      $this->set('priceSetId', $this->_priceSetId);
    }
    return $this->_priceSetId ?: NULL;
  }

  /**
   * Is the price set quick config.
   *
   * @return bool
   */
  public function isQuickConfig(): bool {
    return $this->getPriceSetID() && CRM_Price_BAO_PriceSet::isQuickConfig($this->getPriceSetID());
  }

  /**
   * Get the currency for the form.
   *
   * Rather historic - might have unneeded stuff
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  public function getCurrency() {
    $currency = $this->getEventValue('currency');
    if (empty($currency)) {
      // Is this valid? It comes from previously shared code.
      $currency = CRM_Utils_Request::retrieveValue('currency', 'String');
    }
    // @todo If empty there is a problem - we should probably put in a deprecation notice
    // to warn if that seems to be happening.
    return $currency;
  }

  /**
   * Build the radio/text form elements for the amount field
   *
   * @internal function is not currently called by any extentions in our civi
   * 'universe' and is not supported for such use. Signature has changed & will
   * change again.
   *
   * @throws \CRM_Core_Exception
   */
  protected function buildAmount() {
    $form = $this;
    $priceSetID = $this->_priceSetId;
    $required = TRUE;
    $discountId = NULL;
    $feeFields = $this->getPriceFieldMetaData();

    //check for discount.
    $discountedFee = $form->_values['discount'] ?? NULL;
    if (is_array($discountedFee) && !empty($discountedFee)) {
      CRM_Core_Error::deprecatedWarning('code believed to be unreachable.');
      if (!$discountId) {
        $form->_discountId = $discountId = CRM_Core_BAO_Discount::findSet($form->_eventId, 'civicrm_event');
      }
      if ($discountId) {
        $feeFields = &$form->_values['discount'][$discountId];
      }
    }

    //reset required if participant is skipped.
    $button = substr($form->controller->getButtonName(), -4);
    if ($required && $button === 'skip') {
      $required = FALSE;
    }

    //build the priceset fields.
    if ($priceSetID) {

      // This is probably not required now - normally loaded from event ....
      $form->add('hidden', 'priceSetId', $priceSetID);

      // CRM-14492 Admin price fields should show up on event registration if user has 'administer CiviCRM' permissions
      $adminFieldVisible = CRM_Core_Permission::check('administer CiviCRM data');
      $hideAdminValues = !CRM_Core_Permission::check('edit event participants');

      foreach ($feeFields as $field) {
        // public AND admin visibility fields are included for back-office registration and back-office change selections
        if (($field['visibility'] ?? NULL) === 'public' ||
          (($field['visibility'] ?? NULL) === 'admin' && $adminFieldVisible == TRUE)
        ) {
          $fieldId = $field['id'];
          $elementName = 'price_' . $fieldId;

          $isRequire = $field['is_required'] ?? NULL;
          if ($button === 'skip') {
            $isRequire = FALSE;
          }

          //user might modified w/ hook.
          $options = $field['options'] ?? NULL;

          if (!is_array($options)) {
            continue;
          }
          if ($hideAdminValues) {
            $publicVisibilityID = CRM_Price_BAO_PriceField::getVisibilityOptionID('public');
            $adminVisibilityID = CRM_Price_BAO_PriceField::getVisibilityOptionID('admin');

            foreach ($options as $key => $currentOption) {
              $optionVisibility = $currentOption['visibility_id'] ?? $publicVisibilityID;
              if ($optionVisibility == $adminVisibilityID) {
                unset($options[$key]);
              }
            }
          }

          $optionFullIds = $this->getOptionFullPriceFieldValues($field);

          //soft suppress required rule when option is full.
          if (!empty($optionFullIds) && (count($options) == count($optionFullIds))) {
            $isRequire = FALSE;
          }
          foreach ($options as $option) {
            $options[$option['id']]['is_full'] = $this->getIsOptionFull($option);
          }
          if (!empty($options)) {
            //build the element.
            CRM_Price_BAO_PriceField::addQuickFormElement($form,
              $elementName,
              $fieldId,
              FALSE,
              $isRequire,
              NULL,
              $options,
              $optionFullIds
            );
          }
        }
      }
    }
    else {
      // Is this reachable?
      // Noisy deprecation notice added in Sep 2023 (in previous code location).
      CRM_Core_Error::deprecatedWarning('code believed to be unreachable');
      $eventFeeBlockValues = $elements = $elementJS = [];
      foreach ($feeFields as $fee) {
        if (is_array($fee)) {

          //CRM-7632, CRM-6201
          $totalAmountJs = NULL;
          $eventFeeBlockValues['amount_id_' . $fee['amount_id']] = $fee['value'];
          $elements[$fee['amount_id']] = CRM_Utils_Money::format($fee['value']) . ' ' . $fee['label'];
          $elementJS[$fee['amount_id']] = $totalAmountJs;
        }
      }
      $form->assign('eventFeeBlockValues', json_encode($eventFeeBlockValues));

      $form->_defaults['amount'] = $form->_values['event']['default_fee_id'] ?? NULL;
      $element = &$form->addRadio('amount', ts('Event Fee(s)'), $elements, [], '<br />', FALSE, $elementJS);
      if (isset($form->_online) && $form->_online) {
        $element->freeze();
      }
      if ($required) {
        $form->addRule('amount', ts('Fee Level is a required field.'), 'required');
      }
    }
  }

  /**
   * Is there a price field value configured with a maximum value.
   *
   * If so there will need to be a check to ensure the number used does not
   * exceed it.
   *
   * @return bool
   */
  protected function isMaxValueValidationRequired(): bool {
    foreach ($this->getPriceFieldMetaData() as $field) {
      foreach ($field['options'] as $priceValueOption) {
        if ($priceValueOption['max_value']) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Is this event configured to show the payment processors on the confirmation form?
   *
   * @return bool
   */
  protected function isShowPaymentOnConfirm(): bool {
    $showPaymentOnConfirm = (
      in_array($this->getEventID(), \Civi::settings()->get('event_show_payment_on_confirm')) ||
      in_array('all', \Civi::settings()->get('event_show_payment_on_confirm')) ||
      (in_array('multiparticipant', \Civi::settings()->get('event_show_payment_on_confirm')) && $this->getEventValue('is_multiple_registrations'))
    );

    return $showPaymentOnConfirm;
  }

}
