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
 * This class generates form components for processing Event.
 */
class CRM_Event_Form_Registration extends CRM_Core_Form {

  /**
   * How many locationBlocks should we display?
   *
   * @var int
   * @const
   */
  const LOCATION_BLOCKS = 1;

  /**
   * The id of the event we are proceessing.
   *
   * @var int
   */
  public $_eventId;

  /**
   * The array of ids of all the participant we are proceessing.
   *
   * @var int
   */
  protected $_participantIDS = NULL;

  /**
   * The id of the participant we are proceessing.
   *
   * @var int
   */
  protected $_participantId;

  /**
   * Is participant able to walk registration wizard.
   *
   * @var Boolean
   */
  public $_allowConfirmation;

  /**
   * Is participant requires approval.
   *
   * @var Boolean
   */
  public $_requireApproval;

  /**
   * Is event configured for waitlist.
   *
   * @var Boolean
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
   * The mode that we are in.
   *
   * @var string
   * @protect
   */
  public $_mode;

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
   * The billing location id for this contribiution page.
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

  /* Is event already full.
   *
   * @var boolean
   */

  public $_isEventFull;

  public $_lineItem;
  public $_lineItemParticipantsCount;
  public $_availableRegistrations;

  public $_forcePayement;

  public $_isBillingAddressRequiredForPayLater;

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    $this->_eventId = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE);

    //CRM-4320
    $this->_participantId = CRM_Utils_Request::retrieve('participantId', 'Positive', $this);

    // current mode
    $this->_mode = ($this->_action == 1024) ? 'test' : 'live';

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
      $this->_lineItem = array();
    }
    if (!is_array($this->_lineItemParticipantsCount)) {
      $this->_lineItemParticipantsCount = array();
    }
    $this->_availableRegistrations = $this->get('availableRegistrations');
    $this->_participantIDS = $this->get('participantIDs');

    //check if participant allow to walk registration wizard.
    $this->_allowConfirmation = $this->get('allowConfirmation');

    // check for Approval
    $this->_requireApproval = $this->get('requireApproval');

    // check for waitlisting.
    $this->_allowWaitlist = $this->get('allowWaitlist');

    $this->_forcePayement = $this->get('forcePayement');

    //get the additional participant ids.
    $this->_additionalParticipantIds = $this->get('additionalParticipantIds');
    $config = CRM_Core_Config::singleton();

    if (!$this->_values) {
      // create redirect URL to send folks back to event info page is registration not available
      $infoUrl = CRM_Utils_System::url('civicrm/event/info', "reset=1&id={$this->_eventId}",
        FALSE, NULL, FALSE, TRUE
      );

      // this is the first time we are hitting this, so check for permissions here
      if (!CRM_Core_Permission::event(CRM_Core_Permission::EDIT, $this->_eventId, 'register for events')) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to register for this event'), $infoUrl);
      }

      // get all the values from the dao object
      $this->_values = $this->_fields = array();
      $this->_forcePayement = FALSE;

      //retrieve event information
      $params = array('id' => $this->_eventId);
      CRM_Event_BAO_Event::retrieve($params, $this->_values['event']);
      // check for is_monetary status
      $isMonetary = CRM_Utils_Array::value('is_monetary', $this->_values['event']);
      // check for ability to add contributions of type
      if ($isMonetary
        && CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()
        && !CRM_Core_Permission::check('add contributions of type ' . CRM_Contribute_PseudoConstant::financialType($this->_values['event']['financial_type_id']))
      ) {
        CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
      }

      $this->checkValidEvent($infoUrl);
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

      $eventFull = CRM_Event_BAO_Participant::eventFull($this->_eventId, FALSE,
        CRM_Utils_Array::value('has_waitlist', $this->_values['event'])
      );

      $this->_allowWaitlist = $this->_isEventFull = FALSE;
      if ($eventFull && !$this->_allowConfirmation) {
        $this->_isEventFull = TRUE;
        //lets redirecting to info only when to waiting list.
        $this->_allowWaitlist = CRM_Utils_Array::value('has_waitlist', $this->_values['event']);
        if (!$this->_allowWaitlist) {
          CRM_Utils_System::redirect($infoUrl);
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
      $isPayLater = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $this->_eventId, 'is_pay_later');
      //check for various combinations for paylater, payment
      //process with paid event.
      if ($isMonetary && (!$isPayLater || !empty($this->_values['event']['payment_processor']))) {
        $this->_paymentProcessorIDs = explode(CRM_Core_DAO::VALUE_SEPARATOR, CRM_Utils_Array::value('payment_processor',
          $this->_values['event']
        ));
        $this->assignPaymentProcessor($isPayLater);

      }
      //init event fee.
      self::initEventFee($this, $this->_eventId);

      // get the profile ids
      $ufJoinParams = array(
        'entity_table' => 'civicrm_event',
        // CRM-4377: CiviEvent for the main participant, CiviEvent_Additional for additional participants
        'module' => 'CiviEvent',
        'entity_id' => $this->_eventId,
      );
      list($this->_values['custom_pre_id'],
        $this->_values['custom_post_id']
        ) = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);

      // set profiles for additional participants
      if ($this->_values['event']['is_multiple_registrations']) {
        // CRM-4377: CiviEvent for the main participant, CiviEvent_Additional for additional participants
        $ufJoinParams['module'] = 'CiviEvent_Additional';

        list($this->_values['additional_custom_pre_id'],
          $this->_values['additional_custom_post_id'], $preActive, $postActive
          ) = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);

        // CRM-4377: we need to maintain backward compatibility, hence if there is profile for main contact
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
      $params = array('entity_id' => $this->_eventId, 'entity_table' => 'civicrm_event');
      $this->_values['location'] = CRM_Core_BAO_Location::getValues($params, TRUE);

      $this->set('values', $this->_values);
      $this->set('fields', $this->_fields);

      $this->_availableRegistrations
        = CRM_Event_BAO_Participant::eventFull(
          $this->_values['event']['id'], TRUE,
          CRM_Utils_Array::value('has_waitlist', $this->_values['event'])
        );
      $this->set('availableRegistrations', $this->_availableRegistrations);
    }
    $this->assign_by_ref('paymentProcessor', $this->_paymentProcessor);

    // check if this is a paypal auto return and redirect accordingly
    if (CRM_Core_Payment::paypalRedirect($this->_paymentProcessor)) {
      $url = CRM_Utils_System::url('civicrm/event/register',
        "_qf_ThankYou_display=1&qfKey={$this->controller->_key}"
      );
      CRM_Utils_System::redirect($url);
    }
    // The concept of contributeMode is deprecated.
    $this->_contributeMode = $this->get('contributeMode');
    $this->assign('contributeMode', $this->_contributeMode);

    // setting CMS page title
    CRM_Utils_System::setTitle($this->_values['event']['title']);
    $this->assign('title', $this->_values['event']['title']);

    $this->assign('paidEvent', $this->_values['event']['is_monetary']);

    // we do not want to display recently viewed items on Registration pages
    $this->assign('displayRecent', FALSE);
    // Registration page values are cleared from session, so can't use normal Printer Friendly view.
    // Use Browser Print instead.
    $this->assign('browserPrint', TRUE);

    $isShowLocation = CRM_Utils_Array::value('is_show_location', $this->_values['event']);
    $this->assign('isShowLocation', $isShowLocation);
    // Handle PCP
    $pcpId = CRM_Utils_Request::retrieve('pcpId', 'Positive', $this);
    if ($pcpId) {
      $pcp = CRM_PCP_BAO_PCP::handlePcp($pcpId, 'event', $this->_values['event']);
      $this->_pcpId = $pcp['pcpId'];
      $this->_values['event']['intro_text'] = CRM_Utils_Array::value('intro_text', $pcp['pcpInfo']);
    }

    // assign all event properties so wizard templates can display event info.
    $this->assign('event', $this->_values['event']);
    $this->assign('location', $this->_values['location']);
    $this->assign('bltID', $this->_bltID);
    $isShowLocation = CRM_Utils_Array::value('is_show_location', $this->_values['event']);
    $this->assign('isShowLocation', $isShowLocation);
    //CRM-6907
    $config->defaultCurrency = CRM_Utils_Array::value('currency', $this->_values['event'],
      $config->defaultCurrency
    );

    //lets allow user to override campaign.
    $campID = CRM_Utils_Request::retrieve('campID', 'Positive', $this);
    if ($campID && CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Campaign', $campID)) {
      $this->_values['event']['campaign_id'] = $campID;
    }

    // Set the same value for is_billing_required as contribution page so code can be shared.
    $this->_values['is_billing_required'] = CRM_Utils_Array::value('is_billing_required', $this->_values['event']);
    // check if billing block is required for pay later
    // note that I have started removing the use of isBillingAddressRequiredForPayLater in favour of letting
    // the CRM_Core_Payment_Manual class handle it - but there are ~300 references to it in the code base so only
    // removing in very limited cases.
    if (CRM_Utils_Array::value('is_pay_later', $this->_values['event'])) {
      $this->_isBillingAddressRequiredForPayLater = CRM_Utils_Array::value('is_billing_required', $this->_values['event']);
      $this->assign('isBillingAddressRequiredForPayLater', $this->_isBillingAddressRequiredForPayLater);
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

    $vars = array(
      'amount',
      'currencyID',
      'credit_card_type',
      'trxn_id',
      'amount_level',
      'receive_date',
    );

    foreach ($vars as $v) {
      if (!empty($params[$v])) {
        if ($v == 'receive_date') {
          $this->assign($v, CRM_Utils_Date::mysqlToIso($params[$v]));
        }
        else {
          $this->assign($v, $params[$v]);
        }
      }
      elseif (CRM_Utils_Array::value('amount', $params) == 0) {
        $this->assign($v, CRM_Utils_Array::value($v, $params));
      }
    }

    // assign the address formatted up for display
    $addressParts = array(
      "street_address-{$this->_bltID}",
      "city-{$this->_bltID}",
      "postal_code-{$this->_bltID}",
      "state_province-{$this->_bltID}",
      "country-{$this->_bltID}",
    );
    $addressFields = array();
    foreach ($addressParts as $part) {
      list($n, $id) = explode('-', $part);
      if (isset($params['billing_' . $part])) {
        $addressFields[$n] = CRM_Utils_Array::value('billing_' . $part, $params);
      }
    }

    $this->assign('address', CRM_Utils_Address::format($addressFields));

    // The concept of contributeMode is deprecated.
    if ($this->_contributeMode == 'direct' && empty($params['is_pay_later'])) {
      $date = CRM_Utils_Date::format(CRM_Utils_Array::value('credit_card_exp_date', $params));
      $date = CRM_Utils_Date::mysqlToIso($date);
      $this->assign('credit_card_exp_date', $date);
      $this->assign('credit_card_number',
        CRM_Utils_System::mungeCreditCard(CRM_Utils_Array::value('credit_card_number', $params))
      );
    }

    // get the email that the confirmation would have been sent to
    $session = CRM_Core_Session::singleton();

    // assign is_email_confirm to templates
    if (isset($this->_values['event']['is_email_confirm'])) {
      $this->assign('is_email_confirm', $this->_values['event']['is_email_confirm']);
    }

    // assign pay later stuff
    $params['is_pay_later'] = CRM_Utils_Array::value('is_pay_later', $params, FALSE);
    $this->assign('is_pay_later', $params['is_pay_later']);
    if ($params['is_pay_later']) {
      $this->assign('pay_later_text', $this->_values['event']['pay_later_text']);
      $this->assign('pay_later_receipt', $this->_values['event']['pay_later_receipt']);
    }

    // also assign all participantIDs to the template
    // useful in generating confirmation numbers if needed
    $this->assign('participantIDs',
      $this->_participantIDS
    );
  }

  /**
   * Add the custom fields.
   *
   * @param int $id
   * @param string $name
   * @param bool $viewOnly
   */
  public function buildCustom($id, $name, $viewOnly = FALSE) {
    if ($id) {
      $button = substr($this->controller->getButtonName(), -4);
      $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
      $session = CRM_Core_Session::singleton();
      $contactID = $session->get('userID');

      // we don't allow conflicting fields to be
      // configured via profile
      $fieldsToIgnore = array(
        'participant_fee_amount' => 1,
        'participant_fee_level' => 1,
      );
      if ($contactID) {
        //FIX CRM-9653
        if (is_array($id)) {
          $fields = array();
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
      $addCaptcha = FALSE;

      if (!empty($this->_fields)) {
        $fields = @array_diff_assoc($fields, $this->_fields);
      }

      if (empty($this->_params[0]['additional_participants']) &&
        is_null($cid)
      ) {
        CRM_Core_BAO_Address::checkContactSharedAddressFields($fields, $contactID);
      }
      $this->assign($name, $fields);
      if (is_array($fields)) {
        foreach ($fields as $key => $field) {
          if ($viewOnly &&
            isset($field['data_type']) &&
            $field['data_type'] == 'File' || ($viewOnly && $field['name'] == 'image_URL')
          ) {
            // ignore file upload fields
            continue;
          }
          //make the field optional if primary participant
          //have been skip the additional participant.
          if ($button == 'skip') {
            $field['is_required'] = FALSE;
          }
          // CRM-11316 Is ReCAPTCHA enabled for this profile AND is this an anonymous visitor
          elseif ($field['add_captcha'] && !$contactID) {
            // only add captcha for first page
            $addCaptcha = TRUE;
          }
          list($prefixName, $index) = CRM_Utils_System::explode('-', $key, 2);
          CRM_Core_BAO_UFGroup::buildProfile($this, $field, CRM_Profile_Form::MODE_CREATE, $contactID, TRUE);

          $this->_fields[$key] = $field;
        }
      }

      if ($addCaptcha && !$viewOnly) {
        $captcha = CRM_Utils_ReCAPTCHA::singleton();
        $captcha->add($this);
        $this->assign('isCaptcha', TRUE);
      }
    }
  }

  /**
   * Initiate event fee.
   *
   * @param CRM_Core_Form $form
   * @param int $eventID
   *
   * @throws Exception
   */
  public static function initEventFee(&$form, $eventID) {
    // get price info

    // retrive all active price set fields.
    $discountId = CRM_Core_BAO_Discount::findSet($eventID, 'civicrm_event');
    if (property_exists($form, '_discountId') && $form->_discountId) {
      $discountId = $form->_discountId;
    }

    //CRM-16456 get all price field including expired one.
    $getAllPriceField = TRUE;
    $className = CRM_Utils_System::getClassName($form);
    if ($className == 'CRM_Event_Form_ParticipantFeeSelection' && $form->_action == CRM_Core_Action::UPDATE) {
      $getAllPriceField = FALSE;
    }

    if ($discountId) {
      $priceSetId = CRM_Core_DAO::getFieldValue('CRM_Core_BAO_Discount', $discountId, 'price_set_id');
      $price = CRM_Price_BAO_PriceSet::initSet($form, $eventID, 'civicrm_event', $getAllPriceField, $priceSetId);
    }
    else {
      $price = CRM_Price_BAO_PriceSet::initSet($form, $eventID, 'civicrm_event', $getAllPriceField);
    }

    if (property_exists($form, '_context') && ($form->_context == 'standalone'
        || $form->_context == 'participant')
    ) {
      $discountedEvent = CRM_Core_BAO_Discount::getOptionGroup($eventID, 'civicrm_event');
      if (is_array($discountedEvent)) {
        foreach ($discountedEvent as $key => $priceSetId) {
          $priceSet = CRM_Price_BAO_PriceSet::getSetDetail($priceSetId);
          $priceSet = CRM_Utils_Array::value($priceSetId, $priceSet);
          $form->_values['discount'][$key] = CRM_Utils_Array::value('fields', $priceSet);
          $fieldID = key($form->_values['discount'][$key]);
          $form->_values['discount'][$key][$fieldID]['name'] = CRM_Core_DAO::getFieldValue(
            'CRM_Price_DAO_PriceSet',
            $priceSetId,
            'title'
          );
        }
      }
    }
    $eventFee = CRM_Utils_Array::value('fee', $form->_values);
    if (!is_array($eventFee) || empty($eventFee)) {
      $form->_values['fee'] = array();
    }

    //fix for non-upgraded price sets.CRM-4256.
    if (isset($form->_isPaidEvent)) {
      $isPaidEvent = $form->_isPaidEvent;
    }
    else {
      $isPaidEvent = CRM_Utils_Array::value('is_monetary', $form->_values['event']);
    }
    if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()
      && !empty($form->_values['fee'])
    ) {
      foreach ($form->_values['fee'] as $k => $fees) {
        foreach ($fees['options'] as $options) {
          if (!CRM_Core_Permission::check('add contributions of type ' . CRM_Contribute_PseudoConstant::financialType($options['financial_type_id']))) {
            unset($form->_values['fee'][$k]);
          }
        }
      }
    }
    if ($isPaidEvent && empty($form->_values['fee'])) {
      if (CRM_Utils_System::getClassName($form) != 'CRM_Event_Form_Participant') {
        CRM_Core_Error::fatal(ts('No Fee Level(s) or Price Set is configured for this event.<br />Click <a href=\'%1\'>CiviEvent >> Manage Event >> Configure >> Event Fees</a> to configure the Fee Level(s) or Price Set for this event.', array(1 => CRM_Utils_System::url('civicrm/event/manage/fee', 'reset=1&action=update&id=' . $form->_eventId))));
      }
    }
  }

  /**
   * Handle process after the confirmation of payment by User.
   *
   * @param int $contactID
   * @param null $contribution
   * @param null $payment
   */
  public function confirmPostProcess($contactID = NULL, $contribution = NULL, $payment = NULL) {
    // add/update contact information
    $fields = array();
    unset($this->_params['note']);

    //to avoid conflict overwrite $this->_params
    $this->_params = $this->get('value');

    //get the amount of primary participant
    if (!empty($this->_params['is_primary'])) {
      $this->_params['fee_amount'] = $this->get('primaryParticipantAmount');
    }

    // add participant record
    $participant = CRM_Event_Form_Registration::addParticipant($this, $contactID);
    $this->_participantIDS[] = $participant->id;

    //setting register_by_id field and primaryContactId
    if (!empty($this->_params['is_primary'])) {
      $this->set('registerByID', $participant->id);
      $this->set('primaryContactId', $contactID);

      // CRM-10032
      $this->processFirstParticipant($participant->id);
    }

    CRM_Core_BAO_CustomValueTable::postProcess($this->_params,
      'civicrm_participant',
      $participant->id,
      'Participant'
    );

    $createPayment = (CRM_Utils_Array::value('amount', $this->_params, 0) != 0) ? TRUE : FALSE;

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
      $paymentParams = array(
        'participant_id' => $participant->id,
        'contribution_id' => $contribution->id,
      );
      $ids = array();
      $paymentPartcipant = CRM_Event_BAO_ParticipantPayment::create($paymentParams, $ids);
    }

    //set only primary participant's params for transfer checkout.
    // The concept of contributeMode is deprecated.
    if (($this->_contributeMode == 'checkout' || $this->_contributeMode == 'notify') && !empty($this->_params['is_primary'])) {
      $this->_params['participantID'] = $participant->id;
      $this->set('primaryParticipant', $this->_params);
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
      // 1. free event registration.
      // 2. pay later participant.
      // 3. waiting list participant.
      // 4. require approval participant.
      if (!empty($this->_params['is_pay_later']) ||
        $this->_allowWaitlist || $this->_requireApproval || empty($this->_values['event']['is_monetary'])
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
   */
  public static function addParticipant(&$form, $contactID) {
    if (empty($form->_params)) {
      return NULL;
    }
    $params = $form->_params;
    $transaction = new CRM_Core_Transaction();

    $groupName = 'participant_role';
    $query = "
SELECT  v.label as label ,v.value as value
FROM   civicrm_option_value v,
       civicrm_option_group g
WHERE  v.option_group_id = g.id
  AND  g.name            = %1
  AND  v.is_active       = 1
  AND  g.is_active       = 1
";
    $p = array(1 => array($groupName, 'String'));

    $dao = CRM_Core_DAO::executeQuery($query, $p);
    if ($dao->fetch()) {
      $roleID = $dao->value;
    }

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
    $participantParams = array(
      'id' => CRM_Utils_Array::value('participant_id', $params),
      'contact_id' => $contactID,
      'event_id' => $form->_eventId ? $form->_eventId : $params['event_id'],
      'status_id' => CRM_Utils_Array::value('participant_status',
        $params, 1
      ),
      'role_id' => CRM_Utils_Array::value('participant_role_id',
        $params, $roleID
      ),
      'register_date' => ($registerDate) ? $registerDate : date('YmdHis'),
      'source' => CRM_Utils_String::ellipsify(
        isset($params['participant_source']) ? CRM_Utils_Array::value('participant_source', $params) : CRM_Utils_Array::value('description', $params),
        $participantFields['participant_source']['maxlength']
      ),
      'fee_level' => CRM_Utils_Array::value('amount_level', $params),
      'is_pay_later' => CRM_Utils_Array::value('is_pay_later', $params, 0),
      'fee_amount' => CRM_Utils_Array::value('fee_amount', $params),
      'registered_by_id' => CRM_Utils_Array::value('registered_by_id', $params),
      'discount_id' => CRM_Utils_Array::value('discount_id', $params),
      'fee_currency' => CRM_Utils_Array::value('currencyID', $params),
      'campaign_id' => CRM_Utils_Array::value('campaign_id', $params),
    );

    if ($form->_action & CRM_Core_Action::PREVIEW || CRM_Utils_Array::value('mode', $params) == 'test') {
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

    $participant = CRM_Event_BAO_Participant::create($participantParams);

    $transaction->commit();

    return $participant;
  }

  /**
   * Calculate the total participant count as per params.
   *
   * @param CRM_Core_Form $form
   * @param array $params
   *   User params.
   * @param bool $skipCurrent
   *
   * @return int
   */
  public static function getParticipantCount(&$form, $params, $skipCurrent = FALSE) {
    $totalCount = 0;
    if (!is_array($params) || empty($params)) {
      return $totalCount;
    }

    $priceSetId = $form->get('priceSetId');
    $addParticipantNum = substr($form->_name, 12);
    $priceSetFields = $priceSetDetails = array();
    $hasPriceFieldsCount = FALSE;
    if ($priceSetId) {
      $priceSetDetails = $form->get('priceSet');
      if (isset($priceSetDetails['optionsCountTotal'])
        && $priceSetDetails['optionsCountTotal']
      ) {
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
      $params = array($params);
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
      $cacheCount = CRM_Utils_Array::value($key, $form->_lineItemParticipantsCount);
      if ($cacheCount && is_numeric($cacheCount)) {
        $count = $cacheCount;
        $usedCache = TRUE;
      }

      if (!$usedCache && $hasPriceFieldsCount) {
        $count = 0;
        foreach ($values as $valKey => $value) {
          if (strpos($valKey, 'price_') === FALSE) {
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
   * Format user submitted price set params.
   *
   * Convert price set each param as an array.
   *
   * @param CRM_Core_Form $form
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
      $vals = array();
      if (strpos($key, 'price_') !== FALSE) {
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
          $value = array($fieldOption['id'] => $value);
        }
        else {
          $value = array($value => TRUE);
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
   * @param CRM_Core_Form $form
   *   Form object.
   *
   * @return array
   *   array of each option w/ count total.
   */
  public static function getPriceSetOptionCount(&$form) {
    $params = $form->get('params');
    $priceSet = $form->get('priceSet');
    $priceSetId = $form->get('priceSetId');

    $optionsCount = array();
    if (!$priceSetId ||
      !is_array($priceSet) ||
      empty($priceSet) ||
      !is_array($params) ||
      empty($params)
    ) {
      return $optionsCount;
    }

    $priceSetFields = $priceMaxFieldDetails = array();
    if (!empty($priceSet['optionsCountTotal'])) {
      $priceSetFields = $priceSet['optionsCountDetails']['fields'];
    }

    if (!empty($priceSet['optionsMaxValueTotal'])) {
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
        if (strpos($valKey, 'price_') === FALSE) {
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
          if (CRM_Utils_Array::value('html_type', $priceSet['fields'][$priceFieldId]) == 'Text') {
            $currentCount = $optVal;
          }
          else {
            $currentCount = 1;
          }

          if (isset($priceSetFields[$priceFieldId]) && isset($priceSetFields[$priceFieldId]['options'][$optId])) {
            $currentCount = $priceSetFields[$priceFieldId]['options'][$optId] * $optVal;
          }

          $optionsCount[$optId] = $currentCount + CRM_Utils_Array::value($optId, $optionsCount, 0);
        }
      }
    }

    return $optionsCount;
  }

  /**
   * Check if template file exists.
   *
   * @param string $suffix
   *
   * @return null|string
   */
  public function checkTemplateFileExists($suffix = '') {
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
    return $fileName ? $fileName : parent::getTemplateFileName();
  }

  /**
   * Override extra template name.
   *
   * @return null|string
   */
  public function overrideExtraTemplateFileName() {
    $fileName = $this->checkTemplateFileExists('extra.');
    return $fileName ? $fileName : parent::overrideExtraTemplateFileName();
  }

  /**
   * Reset values for all options those are full.
   *
   * @param array $optionFullIds
   * @param CRM_Core_Form $form
   */
  public static function resetElementValue($optionFullIds = array(), &$form) {
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
                $element->updateAttributes(array('placeholder' => $placeholder));
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
   * @param CRM_Core_form $form
   */
  public static function resetSubmittedValue($elementName, $optionIds = array(), &$form) {
    if (empty($elementName) ||
      !$form->elementExists($elementName) ||
      !$form->getSubmitValue($elementName)
    ) {
      return;
    }
    foreach (array(
               'constantValues',
               'submitValues',
               'defaultValues',
             ) as $val) {
      $values = $form->{"_$val"};
      if (!is_array($values) || empty($values)) {
        continue;
      }
      $eleVal = CRM_Utils_Array::value($elementName, $values);
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
   * Validate price set submitted params for price option limit.
   *
   * User should select at least one price field option.
   *
   * @param CRM_Core_Form $form
   * @param array $params
   *
   * @return array
   */
  public static function validatePriceSet(&$form, $params) {
    $errors = array();
    $hasOptMaxValue = FALSE;
    if (!is_array($params) || empty($params)) {
      return $errors;
    }

    $currentParticipantNum = substr($form->_name, 12);
    if (!$currentParticipantNum) {
      $currentParticipantNum = 0;
    }

    $priceSetId = $form->get('priceSetId');
    $priceSetDetails = $form->get('priceSet');
    if (
      !$priceSetId ||
      !is_array($priceSetDetails) ||
      empty($priceSetDetails)
    ) {
      return $errors;
    }

    $optionsCountDetails = $optionsMaxValueDetails = array();
    if (
      isset($priceSetDetails['optionsMaxValueTotal'])
      && $priceSetDetails['optionsMaxValueTotal']
    ) {
      $hasOptMaxValue = TRUE;
      $optionsMaxValueDetails = $priceSetDetails['optionsMaxValueDetails']['fields'];
    }
    if (
      isset($priceSetDetails['optionsCountTotal'])
      && $priceSetDetails['optionsCountTotal']
    ) {
      $hasOptCount = TRUE;
      $optionsCountDetails = $priceSetDetails['optionsCountDetails']['fields'];
    }
    $feeBlock = $form->_feeBlock;

    if (empty($feeBlock)) {
      $feeBlock = $priceSetDetails['fields'];
    }

    $optionMaxValues = $fieldSelected = array();
    foreach ($params as $pNum => $values) {
      if (!is_array($values) || $values == 'skip') {
        continue;
      }

      foreach ($values as $valKey => $value) {
        if (strpos($valKey, 'price_') === FALSE) {
          continue;
        }
        $priceFieldId = substr($valKey, 6);
        $noneOptionValueSelected = FALSE;
        if (!$feeBlock[$priceFieldId]['is_required'] && $value == 0) {
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
          if (CRM_Utils_Array::value('html_type', $feeBlock[$priceFieldId]) == 'Text') {
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
            $optionMaxValues[$priceFieldId][$optId]
              = $currentMaxValue + CRM_Utils_Array::value($optId, CRM_Utils_Array::value($priceFieldId, $optionMaxValues), 0);
          }
          $soldOutPnum[$optId] = $pNum;
        }
      }

      //validate for price field selection.
      if (empty($fieldSelected[$pNum])) {
        $errors[$pNum]['_qf_default'] = ts('Select at least one option from Event Fee(s).');
      }
    }

    //validate for option max value.
    foreach ($optionMaxValues as $fieldId => $values) {
      $options = CRM_Utils_Array::value('options', $feeBlock[$fieldId], array());
      foreach ($values as $optId => $total) {
        $optMax = $optionsMaxValueDetails[$fieldId]['options'][$optId];
        $opDbCount = CRM_Utils_Array::value('db_total_count', $options[$optId], 0);
        $total += $opDbCount;
        if ($optMax && ($total > $optMax)) {
          if ($opDbCount && ($opDbCount >= $optMax)) {
            $errors[$soldOutPnum[$optId]]["price_{$fieldId}"]
              = ts('Sorry, this option is currently sold out.');
          }
          elseif (($optMax - $opDbCount) == 1) {
            $errors[$soldOutPnum[$optId]]["price_{$fieldId}"]
              = ts('Sorry, currently only a single space is available for this option.', array(1 => ($optMax - $opDbCount)));
          }
          else {
            $errors[$soldOutPnum[$optId]]["price_{$fieldId}"]
              = ts('Sorry, currently only %1 spaces are available for this option.', array(1 => ($optMax - $opDbCount)));
          }
        }
      }
    }
    return $errors;
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

    $ids = $participantValues = array();
    $participantParams = array('id' => $this->_participantId);
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
   * @param string $redirect
   */
  public function checkValidEvent($redirect = NULL) {
    // is the event active (enabled)?
    if (!$this->_values['event']['is_active']) {
      // form is inactive, die a fatal death
      CRM_Core_Error::statusBounce(ts('The event you requested is currently unavailable (contact the site administrator for assistance).'));
    }

    // is online registration is enabled?
    if (!$this->_values['event']['is_online_registration']) {
      CRM_Core_Error::statusBounce(ts('Online registration is not currently available for this event (contact the site administrator for assistance).'), $redirect);
    }

    // is this an event template ?
    if (!empty($this->_values['event']['is_template'])) {
      CRM_Core_Error::statusBounce(ts('Event templates are not meant to be registered.'), $redirect);
    }

    $now = date('YmdHis');
    $startDate = CRM_Utils_Date::processDate(CRM_Utils_Array::value('registration_start_date',
      $this->_values['event']
    ));

    if (
      $startDate &&
      $startDate >= $now
    ) {
      CRM_Core_Error::statusBounce(ts('Registration for this event begins on %1', array(1 => CRM_Utils_Date::customFormat(CRM_Utils_Array::value('registration_start_date', $this->_values['event'])))), $redirect);
    }

    $regEndDate = CRM_Utils_Date::processDate(CRM_Utils_Array::value('registration_end_date',
      $this->_values['event']
    ));
    $eventEndDate = CRM_Utils_Date::processDate(CRM_Utils_Array::value('event_end_date', $this->_values['event']));
    if (($regEndDate && ($regEndDate < $now)) || (empty($regEndDate) && !empty($eventEndDate) && ($eventEndDate < $now))) {
      $endDate = CRM_Utils_Date::customFormat(CRM_Utils_Array::value('registration_end_date', $this->_values['event']));
      if (empty($regEndDate)) {
        $endDate = CRM_Utils_Date::customFormat(CRM_Utils_Array::value('event_end_date', $this->_values['event']));
      }
      CRM_Core_Error::statusBounce(ts('Registration for this event ended on %1', array(1 => $endDate)), $redirect);
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
   */
  public function processRegistration($params, $contactID = NULL) {
    $session = CRM_Core_Session::singleton();
    $this->_participantInfo = array();

    // CRM-4320, lets build array of cancelled additional participant ids
    // those are drop or skip by primary at the time of confirmation.
    // get all in and then unset those are confirmed.
    $cancelledIds = $this->_additionalParticipantIds;

    $participantCount = array();
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
            $this->_participantInfo[] = $participantEmail;
          }
          else {
            $this->_participantInfo[] = $value['first_name'] . ' ' . $value['last_name'];
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
        if (empty($value['is_primary']) || !$this->_values['event']['is_monetary']) {
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
        $this->confirmPostProcess($contactID, NULL, NULL);

        //lets get additional participant id to cancel.
        if ($this->_allowConfirmation && is_array($cancelledIds)) {
          $additonalId = CRM_Utils_Array::value('participant_id', $value);
          if ($additonalId && $key = array_search($additonalId, $cancelledIds)) {
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
    if (count($this->_participantInfo)) {
      $this->set('participantInfo', $this->_participantInfo);
    }

    //send mail Confirmation/Receipt
    if ($this->_contributeMode != 'checkout' ||
      $this->_contributeMode != 'notify'
    ) {
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
          //set as Primary Participant
          $this->assign('isPrimary', 1);

          $customProfile = CRM_Event_BAO_Event::buildCustomProfile($participantID, $this->_values, NULL, $isTest);

          if (count($customProfile)) {
            $this->assign('customProfile', $customProfile);
            $this->set('customProfile', $customProfile);
          }
        }
        else {
          $this->assign('isPrimary', 0);
          $this->assign('customProfile', NULL);
        }

        //send Confirmation mail to Primary & additional Participants if exists
        CRM_Event_BAO_Event::sendMail($contactId, $this->_values, $participantID, $isTest);
      }
    }
  }

}
