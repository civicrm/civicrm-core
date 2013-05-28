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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class generates form components for processing Event
 *
 */
class CRM_Event_Form_Registration extends CRM_Core_Form {

  /**
   * how many locationBlocks should we display?
   *
   * @var int
   * @const
   */
  CONST LOCATION_BLOCKS = 1;

  /**
   * the id of the event we are proceessing
   *
   * @var int
   * @protected
   */
  public $_eventId;

  /**
   * the array of ids of all the participant we are proceessing
   *
   * @var int
   * @protected
   */
  protected $_participantIDS = NULL;

  /**
   * the id of the participant we are proceessing
   *
   * @var int
   * @protected
   */
  protected $_participantId;

  /**
   * is participant able to walk registration wizard.
   *
   * @var Boolean
   * @protected
   */
  public $_allowConfirmation;

  /**
   * is participant requires approval
   *
   * @var Boolean
   * @public
   */
  public $_requireApproval;

  /**
   * is event configured for waitlist.
   *
   * @var Boolean
   * @public
   */
  public $_allowWaitlist;

  /**
   * store additional participant ids
   * when there are pre-registered.
   *
   * @var array
   * @public
   */
  public $_additionalParticipantIds;

  /**
   * the mode that we are in
   *
   * @var string
   * @protect
   */
  public $_mode;

  /**
   * the values for the contribution db object
   *
   * @var array
   * @protected
   */
  public $_values;

  /**
   * the paymentProcessor attributes for this page
   *
   * @var array
   * @protected
   */
  public $_paymentProcessor;

  /**
   * The params submitted by the form and computed by the app
   *
   * @var array
   * @protected
   */
  protected $_params;

  /**
   * The fields involved in this contribution page
   *
   * @var array
   * @protected
   */
  public $_fields;

  /**
   * The billing location id for this contribiution page
   *
   * @var int
   * @protected
   */
  public $_bltID;

  /**
   * Price Set ID, if the new price set method is used
   *
   * @var int
   * @protected
   */
  public $_priceSetId = NULL;

  /**
   * Array of fields for the price set
   *
   * @var array
   * @protected
   */
  public $_priceSet;

  public $_action;

  public $_pcpId;

  /* Is event already full.
     *
     * @var boolean
     * @protected
     */

  public $_isEventFull;

  public $_lineItem;
  public $_lineItemParticipantsCount;
  public $_availableRegistrations;

  public $_forcePayement;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  function preProcess() {
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
    $this->_totalParticipantCount = $this->get('totalParticipantcount');
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
      $infoUrl = CRM_Utils_System::url(
        'civicrm/event/info',
        "reset=1&id={$this->_eventId}",
        FALSE, NULL, FALSE, TRUE
      );

      // this is the first time we are hitting this, so check for permissions here
      if (!CRM_Core_Permission::event(CRM_Core_Permission::EDIT, $this->_eventId)) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to register for this event'), $infoUrl);
      }

      // get all the values from the dao object
      $this->_values = array();
      $this->_fields = array();
      $this->_forcePayement = FALSE;

      //retrieve event information
      $params = array('id' => $this->_eventId);
      CRM_Event_BAO_Event::retrieve($params, $this->_values['event']);

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

      $eventFull = CRM_Event_BAO_Participant::eventFull(
        $this->_eventId,
        FALSE,
        CRM_Utils_Array::value('has_waitlist', $this->_values['event'])
      );

      $this->_allowWaitlist = FALSE;
      $this->_isEventFull = FALSE;
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
      if (CRM_Utils_Array::value('requires_approval', $this->_values['event']) && !$this->_allowConfirmation) {
        $this->_requireApproval = TRUE;
      }
      $this->set('requireApproval', $this->_requireApproval);

      // also get the accounting code
      /* if (CRM_Utils_Array::value('financial_type_id', $this->_values['event'])) { */
      /*   $this->_values['event']['accountingCode'] = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType', */
      /*     $this->_values['event']['financial_type_id'], */
      /*     'accounting_code' */
      /*   ); */
      /* } */
      if (isset($this->_values['event']['default_role_id'])) {
        $participant_role = CRM_Core_OptionGroup::values('participant_role');
        $this->_values['event']['participant_role'] = $participant_role["{$this->_values['event']['default_role_id']}"];
      }

      // check for is_monetary status
      $isMonetary = CRM_Utils_Array::value('is_monetary', $this->_values['event']);

      //retrieve custom information
      $eventID = $this->_eventId;

      $isPayLater = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $eventID, 'is_pay_later');
      //check for variour combination for paylater, payment
      //process with paid event.
      if ($isMonetary &&
        (!$isPayLater || CRM_Utils_Array::value('payment_processor', $this->_values['event']))
      ) {
        $ppID = CRM_Utils_Array::value('payment_processor',
          $this->_values['event']
        );
        if (!$ppID) {
          CRM_Core_Error::statusBounce(ts('A payment processor must be selected for this event registration page, or the event must be configured to give users the option to pay later (contact the site administrator for assistance).'), $infoUrl);
        }

        $ppIds = explode(CRM_Core_DAO::VALUE_SEPARATOR, $ppID);
        $this->_paymentProcessors = CRM_Financial_BAO_PaymentProcessor::getPayments($ppIds,
          $this->_mode
        );

        $this->set('paymentProcessors', $this->_paymentProcessors);

        //set default payment processor
        if (!empty($this->_paymentProcessors) && empty($this->_paymentProcessor)) {
          foreach ($this->_paymentProcessors as $ppId => $values) {
            if ($values['is_default'] == 1 || (count($this->_paymentProcessors) == 1)) {
              $defaultProcessorId = $ppId;
              break;
            }
          }
        }

        if (isset($defaultProcessorId)) {
          $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($defaultProcessorId, $this->_mode);
          $this->assign_by_ref('paymentProcessor', $this->_paymentProcessor);
        }

        // make sure we have a valid payment class, else abort
        if ($this->_values['event']['is_monetary']) {

          if (!CRM_Utils_System::isNull($this->_paymentProcessors)) {
            foreach ($this->_paymentProcessors as $eachPaymentProcessor) {

              // check selected payment processor is active
              if (!$eachPaymentProcessor) {
                CRM_Core_Error::fatal(ts('The site administrator must set a Payment Processor for this event in order to use online registration.'));
              }

              // ensure that processor has a valid config
              $payment = CRM_Core_Payment::singleton($this->_mode, $eachPaymentProcessor, $this);
              $error = $payment->checkConfig();
              if (!empty($error)) {
                CRM_Core_Error::fatal($error);
              }
            }
          }
        }
      }

      //init event fee.
      self::initEventFee($this, $eventID);

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
        $ufJoinParams = array(
          'entity_table' => 'civicrm_event',
          // CRM-4377: CiviEvent for the main participant, CiviEvent_Additional for additional participants
          'module' => 'CiviEvent_Additional',
          'entity_id' => $this->_eventId,
        );
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

      $params = array('id' => $this->_eventId);

      // get the billing location type
      $locationTypes = CRM_Core_PseudoConstant::locationType();

      // CRM-8108 remove ts from Billing as the location type can not be translated in CiviCRM!
      //$this->_bltID = array_search( ts('Billing'),  $locationTypes );
      $this->_bltID = array_search('Billing', $locationTypes);

      if (!$this->_bltID) {
        CRM_Core_Error::fatal(ts('Please set a location type of %1', array(1 => 'Billing')));
      }
      $this->set('bltID', $this->_bltID);

      if ($this->_values['event']['is_monetary'] &&
        ($this->_paymentProcessor['billing_mode'] & CRM_Core_Payment::BILLING_MODE_FORM)
      ) {
        CRM_Core_Payment_Form::setCreditCardFields($this);
      }

      $params = array('entity_id' => $this->_eventId, 'entity_table' => 'civicrm_event');
      $this->_values['location'] = CRM_Core_BAO_Location::getValues($params, TRUE);

      $this->set('values', $this->_values);
      $this->set('fields', $this->_fields);

      $this->_availableRegistrations =
        CRM_Event_BAO_Participant::eventFull(
          $this->_values['event']['id'],
          TRUE,
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
      $pcp             = CRM_PCP_BAO_PCP::handlePcp($pcpId, 'event', $this->_values['event']);
      $this->_pcpId    = $pcp['pcpId'];
      $this->_pcpBlock = $pcp['pcpBlock'];
      $this->_pcpInfo  = $pcp['pcpInfo'];
    }

    if (isset($this->_pcpInfo) && CRM_Utils_Array::value('intro_text', $this->_pcpInfo)) {
      $this->_values['event']['intro_text'] = $this->_pcpInfo['intro_text'];
    }

    // assign all event properties so wizard templates can display event info.
    $this->assign('event', $this->_values['event']);
    $this->assign('location', $this->_values['location']);
    $this->assign('bltID', $this->_bltID);
    $isShowLocation = CRM_Utils_Array::value('is_show_location', $this->_values['event']);
    $this->assign('isShowLocation', $isShowLocation);
    if ($pcpId && $pcpSupporter = CRM_PCP_BAO_PCP::displayName($pcpId)) {
      $this->assign('pcpSupporterText', ts('This event registration is being made thanks to effort of <strong>%1</strong>, who supports our campaign. You can support it as well - once you complete the registration, you will be able to create your own Personal Campaign Page!', array(1 => $pcpSupporter)));
    }

    //CRM-6907
    $config = CRM_Core_Config::singleton();
    $config->defaultCurrency = CRM_Utils_Array::value('currency',
      $this->_values['event'],
      $config->defaultCurrency
    );

    //lets allow user to override campaign.
    $campID = CRM_Utils_Request::retrieve('campID', 'Positive', $this);
    if ($campID && CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Campaign', $campID)) {
      $this->_values['event']['campaign_id'] = $campID;
    }
  }

  /**
   * assign the minimal set of variables to the template
   *
   * @return void
   * @access public
   */
  function assignToTemplate() {
    //process only primary participant params
    $this->_params = $this->get('params');
    if (isset($this->_params[0])) {
      $params = $this->_params[0];
    }
    $name = '';
    if (CRM_Utils_Array::value('billing_first_name', $params)) {
      $name = $params['billing_first_name'];
    }

    if (CRM_Utils_Array::value('billing_middle_name', $params)) {
      $name .= " {$params['billing_middle_name']}";
    }

    if (CRM_Utils_Array::value('billing_last_name', $params)) {
      $name .= " {$params['billing_last_name']}";
    }
    $this->assign('billingName', $name);
    $this->set('name', $name);

    $vars = array(
      'amount', 'currencyID', 'credit_card_type',
      'trxn_id', 'amount_level', 'receive_date',
    );

    foreach ($vars as $v) {
      if (CRM_Utils_Array::value($v, $params)) {
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

    if ($this->_contributeMode == 'direct' &&
      !CRM_Utils_Array::value('is_pay_later', $params)
    ) {
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
   * Function to add the custom fields
   *
   * @return None
   * @access public
   */
  function buildCustom($id, $name, $viewOnly = FALSE) {
    $stateCountryMap = $fields = array();

    if ($id) {
      $button    = substr($this->controller->getButtonName(), -4);
      $cid       = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
      $session   = CRM_Core_Session::singleton();
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
      $fields = array_diff_assoc($fields, $this->_fields);
      if (!CRM_Utils_Array::value('additional_participants', $this->_params[0]) &&
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
          elseif ($field['add_captcha']) {
            // only add captcha for first page
            $addCaptcha = TRUE;
          }

          list($prefixName, $index) = CRM_Utils_System::explode('-', $key, 2);
          if ($prefixName == 'state_province' || $prefixName == 'country' || $prefixName == 'county') {
            if (!array_key_exists($index, $stateCountryMap)) {
              $stateCountryMap[$index] = array();
            }
            $stateCountryMap[$index][$prefixName] = $key;
          }
          CRM_Core_BAO_UFGroup::buildProfile($this, $field, CRM_Profile_Form::MODE_CREATE, $contactID, TRUE);

          $this->_fields[$key] = $field;
        }
      }

      CRM_Core_BAO_Address::addStateCountryMap($stateCountryMap);

      if ($addCaptcha && !$viewOnly) {
        $captcha = CRM_Utils_ReCAPTCHA::singleton();
        $captcha->add($this);
        $this->assign('isCaptcha', TRUE);
      }
    }
  }

  static function initEventFee(&$form, $eventID) {
    // get price info

    // retrive all active price set fields.
    $discountId = CRM_Core_BAO_Discount::findSet($eventID, 'civicrm_event');
    if (property_exists($form, '_discountId') && $form->_discountId) {
      $discountId = $form->_discountId;
    }
    if ($discountId) {
      $priceSetId = CRM_Core_DAO::getFieldValue('CRM_Core_BAO_Discount', $discountId, 'price_set_id');
      $price = CRM_Price_BAO_Set::initSet($form, $eventID, 'civicrm_event', TRUE, $priceSetId);
    }
    else {
      $price = CRM_Price_BAO_Set::initSet($form, $eventID, 'civicrm_event', TRUE);
    }
    
    if (property_exists($form, '_context') && ($form->_context == 'standalone' 
      || $form->_context == 'participant')) {
      $discountedEvent = CRM_Core_BAO_Discount::getOptionGroup($eventID, 'civicrm_event');
      if (is_array( $discountedEvent)) {
        foreach ($discountedEvent as $key => $priceSetId) {
          $priceSet = CRM_Price_BAO_Set::getSetDetail($priceSetId);
          $priceSet = CRM_Utils_Array::value($priceSetId, $priceSet);
          $form->_values['discount'][$key] = CRM_Utils_Array::value('fields', $priceSet);
          $fieldID = key($form->_values['discount'][$key]);
          $form->_values['discount'][$key][$fieldID]['name'] = CRM_Core_DAO::getFieldValue(
            'CRM_Price_DAO_Set',
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
    if ($isPaidEvent && empty($form->_values['fee'])) {
      if (CRM_Utils_System::getClassName($form) != 'CRM_Event_Form_Participant') {
        CRM_Core_Error::fatal(ts('No Fee Level(s) or Price Set is configured for this event.<br />Click <a href=\'%1\'>CiviEvent >> Manage Event >> Configure >> Event Fees</a> to configure the Fee Level(s) or Price Set for this event.', array(1 => CRM_Utils_System::url('civicrm/event/manage/fee', 'reset=1&action=update&id=' . $form->_eventId))));
      }
    }
  }

  /**
   * Function to handle  process after the confirmation of payment by User
   *
   * @return None
   * @access public
   */
  function confirmPostProcess($contactID = NULL, $contribution = NULL, $payment = NULL) {
    // add/update contact information
    $fields = array();
    unset($this->_params['note']);

    //to avoid conflict overwrite $this->_params
    $this->_params = $this->get('value');

    //get the amount of primary participant
    if (CRM_Utils_Array::value('is_primary', $this->_params)) {
      $this->_params['fee_amount'] = $this->get('primaryParticipantAmount');
    }

    // add participant record
    $participant = $this->addParticipant($this->_params, $contactID);
    $this->_participantIDS[] = $participant->id;

    //setting register_by_id field and primaryContactId
    if (CRM_Utils_Array::value('is_primary', $this->_params)) {
      $this->set('registerByID', $participant->id);
      $this->set('primaryContactId', $contactID);

      // CRM-10032
      $this->processFirstParticipant($participant->id);
    }

    CRM_Core_BAO_CustomValueTable::postProcess($this->_params,
      CRM_Core_DAO::$_nullArray,
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

    if ($createPayment && $this->_values['event']['is_monetary'] &&
      CRM_Utils_Array::value('contributionID', $this->_params)
    ) {
      $paymentParams = array(
        'participant_id' => $participant->id,
        'contribution_id' => $contribution->id,
      );
      $ids = array();
      $paymentPartcipant = CRM_Event_BAO_ParticipantPayment::create($paymentParams, $ids);
    }

    //set only primary participant's params for transfer checkout.
    if (($this->_contributeMode == 'checkout' || $this->_contributeMode == 'notify')
      && CRM_Utils_Array::value('is_primary', $this->_params)
    ) {
      $this->_params['participantID'] = $participant->id;
      $this->set('primaryParticipant', $this->_params);
    }

    $this->assign('action', $this->_action);

    // create CMS user
    if (CRM_Utils_Array::value('cms_create_account', $this->_params)) {
      $this->_params['contactID'] = $contactID;

      if (array_key_exists('email-5', $this->_params)) {
      $mail = 'email-5';
      } else {
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
      if (CRM_Utils_Array::value('is_pay_later', $this->_params) ||
        $this->_allowWaitlist || $this->_requireApproval ||
        !CRM_Utils_Array::value('is_monetary', $this->_values['event'])
      ) {
        $mail = 'email-Primary';
      }

      if (!CRM_Core_BAO_CMSUser::create($this->_params, $mail)) {
        CRM_Core_Error::statusBounce(ts('Your profile is not saved and Account is not created.'));
      }
    }
  }

  /**
   * Process the participant
   *
   * @return void
   * @access public
   */
  public function addParticipant($params, $contactID) {

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
    if ($this->_allowConfirmation && $this->_participantId) {
      $registerDate = $params['participant_register_date'];
    }
    elseif (CRM_Utils_Array::value('participant_register_date', $params) &&
      is_array($params['participant_register_date']) &&
      !empty($params['participant_register_date'])
    ) {
      $registerDate = CRM_Utils_Date::format($params['participant_register_date']);
    }

    $participantParams = array('id' => CRM_Utils_Array::value('participant_id', $params),
      'contact_id' => $contactID,
      'event_id' => $this->_eventId ? $this->_eventId : $params['event_id'],
      'status_id' => CRM_Utils_Array::value('participant_status',
        $params, 1
      ),
      'role_id' => CRM_Utils_Array::value('participant_role_id',
        $params, $roleID
      ),
      'register_date' => ($registerDate) ? $registerDate : date('YmdHis'),
      'source' => isset($params['participant_source']) ?
        CRM_Utils_Array::value('participant_source', $params) : CRM_Utils_Array::value('description', $params),
      'fee_level' => CRM_Utils_Array::value('amount_level', $params),
      'is_pay_later' => CRM_Utils_Array::value('is_pay_later', $params, 0),
      'fee_amount' => CRM_Utils_Array::value('fee_amount', $params),
      'registered_by_id' => CRM_Utils_Array::value('registered_by_id', $params),
      'discount_id' => CRM_Utils_Array::value('discount_id', $params),
      'fee_currency' => CRM_Utils_Array::value('currencyID', $params),
      'campaign_id' => CRM_Utils_Array::value('campaign_id', $params),
    );

    if ($this->_action & CRM_Core_Action::PREVIEW || CRM_Utils_Array::value('mode', $params) == 'test') {
      $participantParams['is_test'] = 1;
    }
    else {
      $participantParams['is_test'] = 0;
    }

    if (CRM_Utils_Array::value('note', $this->_params)) {
      $participantParams['note'] = $this->_params['note'];
    }
    elseif (CRM_Utils_Array::value('participant_note', $this->_params)) {
      $participantParams['note'] = $this->_params['participant_note'];
    }

    // reuse id if one already exists for this one (can happen
    // with back button being hit etc)
    if (!$participantParams['id'] &&
      CRM_Utils_Array::value('contributionID', $params)
    ) {
      $pID = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment',
        $params['contributionID'],
        'participant_id',
        'contribution_id'
      );
      $participantParams['id'] = $pID;
    }
    $participantParams['discount_id'] = CRM_Core_BAO_Discount::findSet($this->_eventId, 'civicrm_event');

    if (!$participantParams['discount_id']) {
      $participantParams['discount_id'] = "null";
    }

    $participant = CRM_Event_BAO_Participant::create($participantParams);

    $transaction->commit();

    return $participant;
  }

  /* Calculate the total participant count as per params.
   *
   * @param  array $params user params.
   *
   * @return $totalCount total participant count.
   * @access public
   */
  public static function getParticipantCount(&$form, $params, $skipCurrent = FALSE) {
    $totalCount = 0;
    if (!is_array($params) || empty($params)) {
      return $totalCount;
    }

    $priceSetId          = $form->get('priceSetId');
    $addParticipantNum   = substr($form->_name, 12);
    $priceSetFields      = $priceSetDetails = array();
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

  /* Format user submitted price set params.
   * Convert price set each param as an array.
   *
   * @param $params an array of user submitted params.
   *
   *
   * @return array $formatted, formatted price set params.
   * @access public
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

  /* Calculate total count for each price set options.
   * those are currently selected by user.
   *
   * @param $form form object.
   *
   *
   * @return array $optionsCount, array of each option w/ count total.
   * @access public
   */
  function getPriceSetOptionCount(&$form) {
    $params     = $form->get('params');
    $priceSet   = $form->get('priceSet');
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
    if (CRM_Utils_Array::value('optionsCountTotal', $priceSet)) {
      $priceSetFields = $priceSet['optionsCountDetails']['fields'];
    }

    if (CRM_Utils_Array::value('optionsMaxValueTotal', $priceSet)) {
      $priceMaxFieldDetails = $priceSet['optionsMaxValueDetails']['fields'];
    }

    $addParticipantNum = substr($form->_name, 12);
    foreach ($params as $pCnt => $values) {
      if ($values == 'skip' ||
        $pCnt == $addParticipantNum
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

  function checkTemplateFileExists($suffix = '') {
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

  function getTemplateFileName() {
    $fileName = $this->checkTemplateFileExists();
    return $fileName ? $fileName : parent::getTemplateFileName();
  }

  function overrideExtraTemplateFileName() {
    $fileName = $this->checkTemplateFileExists('extra.');
    return $fileName ? $fileName : parent::overrideExtraTemplateFileName();
  }

  function getContactID() {
    $tempID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    // force to ignore the authenticated user
    if ($tempID === '0') {
      return $tempID;
    }

    // check if the user is logged in and has a contact ID
    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');

    if ($tempID == $userID) {
      return $userID;
    }

    //check if this is a checksum authentication
    $userChecksum = CRM_Utils_Request::retrieve('cs', 'String', $this);
    if ($userChecksum) {
      //check for anonymous user.
      $validUser = CRM_Contact_BAO_Contact_Utils::validChecksum($tempID, $userChecksum);
      if ($validUser) {
        return $tempID;
      }
    }
    // check if user has permission, CRM-12062
    else if ($tempID && CRM_Contact_BAO_Contact_Permission::allow($tempID)) {
      return $tempID;
    }

    return $userID;
  }

  /* Validate price set submitted params for price option limit,
   * as well as user should select at least one price field option.
   *
   */
  static function validatePriceSet(&$form, $params) {
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
            $optionMaxValues[$priceFieldId][$optId] =
              $currentMaxValue + CRM_Utils_Array::value($optId, CRM_Utils_Array::value($priceFieldId, $optionMaxValues), 0);
          }

        }
      }
    }

    //validate for option max value.
    foreach ($optionMaxValues as $fieldId => $values) {
      $options = CRM_Utils_Array::value('options', $feeBlock[$fieldId], array());
      foreach ($values as $optId => $total) {
        $optMax = $optionsMaxValueDetails[$fieldId]['options'][$optId];
        $opDbCount = CRM_Utils_Array::value('db_total_count', $options[$optId], 0);
        $total += $opDbCount;
        if ($optMax && $total > $optMax) {
          if ($opDbCount && ($opDbCount >= $optMax)) {
            $errors[$currentParticipantNum]["price_{$fieldId}"] =
              ts('Sorry, this option is currently sold out.');
          }
          elseif (($optMax - $opDbCount) == 1) {
            $errors[$currentParticipantNum]["price_{$fieldId}"] =
              ts('Sorry, currently only a single seat is available for this option.', array(1 => ($optMax - $opDbCount)));
          }
          else {
            $errors[$currentParticipantNum]["price_{$fieldId}"] =
              ts('Sorry, currently only %1 seats are available for this option.', array(1 => ($optMax - $opDbCount)));
          }
        }
      }
    }

    //validate for price field selection.
    foreach ($params as $pNum => $values) {
      if (!is_array($values) || $values == 'skip') {
        continue;
      }
      if (!CRM_Utils_Array::value($pNum, $fieldSelected)) {
        $errors[$pNum]['_qf_default'] = ts('Select at least one option from Event Fee(s).');
      }
    }

    return $errors;
  }

  // set the first participant ID if not set, CRM-10032
  function processFirstParticipant($participantID) {
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

  function checkValidEvent($redirect = NULL) {
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
    if (CRM_Utils_Array::value('is_template', $this->_values['event'])) {
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

    $endDate = CRM_Utils_Date::processDate(CRM_Utils_Array::value('registration_end_date',
        $this->_values['event']
      ));
    if (
      $endDate &&
      $endDate < $now
    ) {
      CRM_Core_Error::statusBounce(ts('Registration for this event ended on %1', array(1 => CRM_Utils_Date::customFormat(CRM_Utils_Array::value('registration_end_date', $this->_values['event'])))), $redirect);
    }
  }
}

