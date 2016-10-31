<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */

/**
 * This class generates form components for processing a participation
 * in an event
 */
class CRM_Event_Form_Participant extends CRM_Contribute_Form_AbstractEditPayment {

  public $useLivePageJS = TRUE;

  /**
   * The values for the contribution db object.
   *
   * @var array
   */
  public $_values;

  /**
   * The values for the quickconfig for priceset.
   *
   * @var boolean
   */
  public $_quickConfig = NULL;

  /**
   * Price Set ID, if the new price set method is used
   *
   * @var int
   */
  public $_priceSetId;

  /**
   * Array of fields for the price set.
   *
   * @var array
   */
  public $_priceSet;

  /**
   * The id of the participation that we are proceessing.
   *
   * @var int
   */
  public $_id;

  /**
   * The id of the note.
   *
   * @var int
   */
  protected $_noteId = NULL;

  /**
   * The id of the contact associated with this participation.
   *
   * @var int
   */
  public $_contactId;

  /**
   * Array of event values.
   *
   * @var array
   */
  protected $_event;

  /**
   * Are we operating in "single mode", i.e. adding / editing only
   * one participant record, or is this a batch add operation
   *
   * @var boolean
   */
  public $_single = FALSE;

  /**
   * If event is paid or unpaid.
   */
  public $_isPaidEvent;

  /**
   * Page action.
   */
  public $_action;

  /**
   * Role Id.
   */
  protected $_roleId = NULL;

  /**
   * Event Type Id.
   */
  protected $_eventTypeId = NULL;

  /**
   * Participant status Id.
   */
  protected $_statusId = NULL;

  /**
   * Cache all the participant statuses.
   */
  protected $_participantStatuses;

  /**
   * Participant mode.
   */
  public $_mode = NULL;

  /**
   * Event ID preselect.
   */
  public $_eID = NULL;

  /**
   * Line Item for Price Set.
   */
  public $_lineItem = NULL;

  /**
   * Contribution mode for event registration for offline mode.
   *
   * @deprecated
   */
  public $_contributeMode = 'direct';

  public $_online;

  /**
   * Store id of role custom data type ( option value )
   */
  protected $_roleCustomDataTypeID;

  /**
   * Store id of event Name custom data type ( option value)
   */
  protected $_eventNameCustomDataTypeID;

  /**
   * Selected discount id.
   */
  public $_originalDiscountId = NULL;

  /**
   * Event id.
   */
  public $_eventId = NULL;

  /**
   * Id of payment, if any
   */
  public $_paymentId = NULL;

  /**
   * @todo add explanatory note about this
   * @var null
   */
  public $_onlinePendingContributionId = NULL;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Participant';
  }

  /**
   * Set variables up before form is built.
   *
   * @return void
   */
  public function preProcess() {
    $this->_showFeeBlock = CRM_Utils_Array::value('eventId', $_GET);
    $this->assign('showFeeBlock', FALSE);
    $this->assign('feeBlockPaid', FALSE);

    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    $this->_mode = CRM_Utils_Request::retrieve('mode', 'String', $this);
    $this->_eID = CRM_Utils_Request::retrieve('eid', 'Positive', $this);
    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);
    $this->assign('context', $this->_context);

    if ($this->_contactId) {
      $displayName = CRM_Contact_BAO_Contact::displayName($this->_contactId);
      $this->assign('displayName', $displayName);
      $this->setPageTitle(ts('Event Registration for %1', array(1 => $displayName)));
    }
    else {
      $this->setPageTitle(ts('Event Registration'));
    }

    // check the current path, if search based, then dont get participantID
    // CRM-5792
    $path = CRM_Utils_System::currentPath();
    if (
      strpos($path, 'civicrm/contact/search') === 0 ||
      strpos($path, 'civicrm/group/search') === 0
    ) {
      $this->_id = NULL;
    }
    else {
      $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    }

    if ($this->_id) {
      $this->assign('participantId', $this->_id);

      $this->_paymentId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment',
        $this->_id, 'id', 'participant_id'
      );

      $this->assign('hasPayment', $this->_paymentId);

      // CRM-12615 - Get payment information from the primary registration
      if ((!$this->_paymentId) && ($this->_action == CRM_Core_Action::UPDATE)) {
        $registered_by_id = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant',
          $this->_id, 'registered_by_id', 'id'
        );
        if ($registered_by_id) {
          $this->_paymentId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment',
            $registered_by_id, 'id', 'participant_id'
          );
          $this->assign('registeredByParticipantId', $registered_by_id);
        }
      }
    }

    // get the option value for custom data type
    $this->_roleCustomDataTypeID = CRM_Core_OptionGroup::getValue('custom_data_type', 'ParticipantRole', 'name');
    $this->_eventNameCustomDataTypeID = CRM_Core_OptionGroup::getValue('custom_data_type', 'ParticipantEventName', 'name');
    $this->_eventTypeCustomDataTypeID = CRM_Core_OptionGroup::getValue('custom_data_type', 'ParticipantEventType', 'name');
    $this->assign('roleCustomDataTypeID', $this->_roleCustomDataTypeID);
    $this->assign('eventNameCustomDataTypeID', $this->_eventNameCustomDataTypeID);
    $this->assign('eventTypeCustomDataTypeID', $this->_eventTypeCustomDataTypeID);

    if ($this->_mode) {
      $this->assign('participantMode', $this->_mode);
      $this->assignPaymentRelatedVariables();
    }

    if ($this->_showFeeBlock) {
      $this->assign('showFeeBlock', TRUE);
      $isMonetary = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $this->_showFeeBlock, 'is_monetary');
      if ($isMonetary) {
        $this->assign('feeBlockPaid', TRUE);
      }
      return CRM_Event_Form_EventFees::preProcess($this);
    }

    //check the mode when this form is called either single or as
    //search task action
    if ($this->_id || $this->_contactId || $this->_context == 'standalone') {
      $this->_single = TRUE;
      $this->assign('urlPath', 'civicrm/contact/view/participant');
      if (!$this->_id && !$this->_contactId) {
        $breadCrumbs = array(
          array(
            'title' => ts('CiviEvent Dashboard'),
            'url' => CRM_Utils_System::url('civicrm/event', 'reset=1'),
          ),
        );

        CRM_Utils_System::appendBreadCrumb($breadCrumbs);
      }
    }
    else {
      //set the appropriate action
      $context = $this->get('context');
      $urlString = 'civicrm/contact/search';
      $this->_action = CRM_Core_Action::BASIC;
      switch ($context) {
        case 'advanced':
          $urlString = 'civicrm/contact/search/advanced';
          $this->_action = CRM_Core_Action::ADVANCED;
          break;

        case 'builder':
          $urlString = 'civicrm/contact/search/builder';
          $this->_action = CRM_Core_Action::PROFILE;
          break;

        case 'basic':
          $urlString = 'civicrm/contact/search/basic';
          $this->_action = CRM_Core_Action::BASIC;
          break;

        case 'custom':
          $urlString = 'civicrm/contact/search/custom';
          $this->_action = CRM_Core_Action::COPY;
          break;
      }
      CRM_Contact_Form_Task::preProcessCommon($this);

      $this->_single = FALSE;
      $this->_contactId = NULL;

      //set ajax path, this used for custom data building
      $this->assign('urlPath', $urlString);
      $this->assign('urlPathVar', "_qf_Participant_display=true&qfKey={$this->controller->_key}");
    }

    $this->assign('single', $this->_single);

    if (!$this->_id) {
      $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add');
    }
    $this->assign('action', $this->_action);

    // check for edit permission
    if (!CRM_Core_Permission::checkActionPermission('CiviEvent', $this->_action)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      // check delete permission for contribution
      if ($this->_id && $this->_paymentId && !CRM_Core_Permission::checkActionPermission('CiviContribute', $this->_action)) {
        CRM_Core_Error::fatal(ts("This Participant is linked to a contribution. You must have 'delete in CiviContribute' permission in order to delete this record."));
      }
      return;
    }

    if ($this->_id) {
      // assign participant id to the template
      $this->assign('participantId', $this->_id);
      $this->_roleId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $this->_id, 'role_id');
    }

    // when fee amount is included in form
    if (!empty($_POST['hidden_feeblock']) || !empty($_POST['send_receipt'])) {
      CRM_Event_Form_EventFees::preProcess($this);
      CRM_Event_Form_EventFees::buildQuickForm($this);
      CRM_Event_Form_EventFees::setDefaultValues($this);
    }

    // when custom data is included in this page
    if (!empty($_POST['hidden_custom'])) {
      // Custom data of type participant role
      // Note: Some earlier commits imply $_POST['role_id'] could be a comma separated string,
      //       not sure if that ever really happens
      if (!empty($_POST['role_id'])) {
        foreach ($_POST['role_id'] as $roleID) {
          CRM_Custom_Form_CustomData::preProcess($this, $this->_roleCustomDataTypeID, $roleID, 1, 'Participant', $this->_id);
          CRM_Custom_Form_CustomData::buildQuickForm($this);
          CRM_Custom_Form_CustomData::setDefaultValues($this);
        }
      }

      //custom data of type participant event
      CRM_Custom_Form_CustomData::preProcess($this, $this->_eventNameCustomDataTypeID, $_POST['event_id'], 1, 'Participant', $this->_id);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);

      // custom data of type participant event type
      $eventTypeId = NULL;
      if ($eventId = CRM_Utils_Array::value('event_id', $_POST)) {
        $eventTypeId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $eventId, 'event_type_id', 'id');
      }
      CRM_Custom_Form_CustomData::preProcess($this, $this->_eventTypeCustomDataTypeID, $eventTypeId,
        1, 'Participant', $this->_id
      );
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);

      //custom data of type participant, ( we 'null' to reset subType and subName)
      CRM_Custom_Form_CustomData::preProcess($this, 'null', 'null', 1, 'Participant', $this->_id);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
    }

    // CRM-4395, get the online pending contribution id.
    $this->_onlinePendingContributionId = NULL;
    if (!$this->_mode && $this->_id && ($this->_action & CRM_Core_Action::UPDATE)) {
      $this->_onlinePendingContributionId = CRM_Contribute_BAO_Contribution::checkOnlinePendingContribution($this->_id,
        'Event'
      );
    }
    $this->set('onlinePendingContributionId', $this->_onlinePendingContributionId);
  }

  /**
   * This function sets the default values for the form in edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @return void
   */
  public function setDefaultValues() {
    if ($this->_showFeeBlock) {
      return CRM_Event_Form_EventFees::setDefaultValues($this);
    }

    $defaults = array();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return $defaults;
    }

    if ($this->_id) {
      $ids = array();
      $params = array('id' => $this->_id);

      CRM_Event_BAO_Participant::getValues($params, $defaults, $ids);
      $sep = CRM_Core_DAO::VALUE_SEPARATOR;
      if ($defaults[$this->_id]['role_id']) {
        $roleIDs = explode($sep, $defaults[$this->_id]['role_id']);
      }
      $this->_contactId = $defaults[$this->_id]['contact_id'];
      $this->_statusId = $defaults[$this->_id]['participant_status_id'];

      //set defaults for note
      $noteDetails = CRM_Core_BAO_Note::getNote($this->_id, 'civicrm_participant');
      $defaults[$this->_id]['note'] = array_pop($noteDetails);

      // Check if this is a primaryParticipant (registered for others) and retrieve additional participants if true  (CRM-4859)
      if (CRM_Event_BAO_Participant::isPrimaryParticipant($this->_id)) {
        $this->assign('additionalParticipants', CRM_Event_BAO_Participant::getAdditionalParticipants($this->_id));
      }

      // Get registered_by contact ID and display_name if participant was registered by someone else (CRM-4859)
      if (!empty($defaults[$this->_id]['participant_registered_by_id'])) {
        $registered_by_contact_id = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant',
          $defaults[$this->_id]['participant_registered_by_id'],
          'contact_id', 'id'
        );
        $this->assign('participant_registered_by_id', $defaults[$this->_id]['participant_registered_by_id']);
        $this->assign('registered_by_contact_id', $registered_by_contact_id);
        $this->assign('registered_by_display_name', CRM_Contact_BAO_Contact::displayName($registered_by_contact_id));
      }
    }

    if ($this->_action & (CRM_Core_Action::VIEW | CRM_Core_Action::BROWSE)) {
      $inactiveNeeded = TRUE;
      $viewMode = TRUE;
    }
    else {
      $viewMode = FALSE;
      $inactiveNeeded = FALSE;
    }

    //setting default register date
    if ($this->_action == CRM_Core_Action::ADD) {
      $statuses = array_flip($this->_participantStatuses);
      $defaults[$this->_id]['status_id'] = CRM_Utils_Array::value(ts('Registered'), $statuses);
      if (!empty($defaults[$this->_id]['event_id'])) {
        $contributionTypeId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event',
          $defaults[$this->_id]['event_id'],
          'financial_type_id'
        );
        if ($contributionTypeId) {
          $defaults[$this->_id]['financial_type_id'] = $contributionTypeId;
        }
      }

      if ($this->_mode) {
        $fields["email-{$this->_bltID}"] = 1;
        $fields['email-Primary'] = 1;

        if ($this->_contactId) {
          CRM_Core_BAO_UFGroup::setProfileDefaults($this->_contactId, $fields, $defaults);
        }

        if (empty($defaults["email-{$this->_bltID}"]) &&
          !empty($defaults['email-Primary'])
        ) {
          $defaults[$this->_id]["email-{$this->_bltID}"] = $defaults['email-Primary'];
        }
      }

      $submittedRole = $this->getElementValue('role_id');
      if (!empty($submittedRole[0])) {
        $roleID = $submittedRole[0];
      }
      $submittedEvent = $this->getElementValue('event_id');
      if (!empty($submittedEvent[0])) {
        $eventID = $submittedEvent[0];
      }
    }
    else {
      $defaults[$this->_id]['record_contribution'] = 0;

      if ($defaults[$this->_id]['participant_is_pay_later']) {
        $this->assign('participant_is_pay_later', TRUE);
      }

      $this->assign('participant_status_id', $defaults[$this->_id]['participant_status_id']);
      $eventID = $defaults[$this->_id]['event_id'];

      $this->_eventTypeId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $eventID, 'event_type_id', 'id');

      $this->_discountId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $this->_id, 'discount_id');
      if ($this->_discountId) {
        $this->set('discountId', $this->_discountId);
      }
    }

    list($defaults[$this->_id]['register_date'], $defaults[$this->_id]['register_date_time'])
      = CRM_Utils_Date::setDateDefaults(CRM_Utils_Array::value('register_date', $defaults[$this->_id]), 'activityDateTime');

    //assign event and role id, this is needed for Custom data building
    $sep = CRM_Core_DAO::VALUE_SEPARATOR;
    if (!empty($defaults[$this->_id]['participant_role_id'])) {
      $roleIDs = explode($sep, $defaults[$this->_id]['participant_role_id']);
    }
    if (isset($_POST['event_id'])) {
      $eventID = $_POST['event_id'];
    }

    if ($this->_eID) {
      $eventID = $this->_eID;
      //@todo - rationalise the $this->_eID with $POST['event_id'],  $this->_eid is set when eid=x is in the url
      $roleID = CRM_Core_DAO::getFieldValue(
        'CRM_Event_DAO_Event',
        $this->_eID,
        'default_role_id'
      );
      if (empty($roleIDs)) {
        $roleIDs = (array) $defaults[$this->_id]['participant_role_id'] = $roleID;
      }
      $defaults[$this->_id]['event_id'] = $eventID;
    }
    if (!empty($eventID)) {
      $this->_eventTypeId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $eventID, 'event_type_id', 'id');
    }
    //these should take precedence so we state them last
    $urlRoleIDS = CRM_Utils_Request::retrieve('roles', 'String');
    if ($urlRoleIDS) {
      $roleIDs = explode(',', $urlRoleIDS);
    }
    if (isset($roleIDs)) {
      $defaults[$this->_id]['role_id'] = implode(',', $roleIDs);
    }

    if (isset($eventID)) {
      $this->assign('eventID', $eventID);
      $this->set('eventId', $eventID);
    }

    if (isset($this->_eventTypeId)) {
      $this->assign('eventTypeID', $this->_eventTypeId);
    }

    $this->assign('event_is_test', CRM_Utils_Array::value('event_is_test', $defaults[$this->_id]));
    return $defaults[$this->_id];
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    $participantStatuses = CRM_Event_PseudoConstant::participantStatus();
    $partiallyPaidStatusId = array_search('Partially paid', $participantStatuses);
    $this->assign('partiallyPaidStatusId', $partiallyPaidStatusId);

    if ($this->_showFeeBlock) {
      return CRM_Event_Form_EventFees::buildQuickForm($this);
    }

    //need to assign custom data type to the template
    $this->assign('customDataType', 'Participant');

    $this->applyFilter('__ALL__', 'trim');

    if ($this->_action & CRM_Core_Action::DELETE) {
      if ($this->_single) {
        $additionalParticipant = count(CRM_Event_BAO_Event::buildCustomProfile($this->_id,
            NULL,
            $this->_contactId,
            FALSE,
            TRUE
          )) - 1;
        if ($additionalParticipant) {
          $deleteParticipants = array(
            1 => ts('Delete this participant record along with associated participant record(s).'),
            2 => ts('Delete only this participant record.'),
          );
          $this->addRadio('delete_participant', NULL, $deleteParticipants, NULL, '<br />');
          $this->setDefaults(array('delete_participant' => 1));
          $this->assign('additionalParticipant', $additionalParticipant);
        }
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

    if ($this->_single && $this->_context == 'standalone') {
      $this->addEntityRef('contact_id', ts('Contact'), array(
          'create' => TRUE,
          'api' => array('extra' => array('email')),
        ), TRUE);
    }

    $eventFieldParams = array(
      'entity' => 'event',
      'select' => array('minimumInputLength' => 0),
      'api' => array(
        'extra' => array('campaign_id', 'default_role_id', 'event_type_id'),
      ),
    );

    if ($this->_mode) {
      // exclude events which are not monetary when credit card registration is used
      $eventFieldParams['api']['params']['is_monetary'] = 1;
      $this->add('select', 'payment_processor_id', ts('Payment Processor'), $this->_processors, TRUE);
    }

    $element = $this->addEntityRef('event_id', ts('Event'), $eventFieldParams, TRUE);

    //frozen the field fix for CRM-4171
    if ($this->_action & CRM_Core_Action::UPDATE && $this->_id) {
      if (CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment',
        $this->_id, 'contribution_id', 'participant_id'
      )
      ) {
        $element->freeze();
      }
    }

    //CRM-7362 --add campaigns.
    $campaignId = NULL;
    if ($this->_id) {
      $campaignId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $this->_id, 'campaign_id');
    }
    if (!$campaignId) {
      $eventId = CRM_Utils_Request::retrieve('eid', 'Positive', $this);
      if ($eventId) {
        $campaignId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $eventId, 'campaign_id');
      }
    }
    CRM_Campaign_BAO_Campaign::addCampaign($this, $campaignId);

    $this->addDateTime('register_date', ts('Registration Date'), TRUE, array('formatType' => 'activityDateTime'));

    if ($this->_id) {
      $this->assign('entityID', $this->_id);
    }

    $this->addSelect('role_id', array('multiple' => TRUE, 'class' => 'huge'), TRUE);

    // CRM-4395
    $checkCancelledJs = array('onchange' => "return sendNotification( );");
    $confirmJS = NULL;
    if ($this->_onlinePendingContributionId) {
      $cancelledparticipantStatusId = array_search('Cancelled', CRM_Event_PseudoConstant::participantStatus());
      $cancelledContributionStatusId = array_search('Cancelled',
        CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name')
      );
      $checkCancelledJs = array(
        'onchange' => "checkCancelled( this.value, {$cancelledparticipantStatusId},{$cancelledContributionStatusId});",
      );

      $participantStatusId = array_search('Pending from pay later',
        CRM_Event_PseudoConstant::participantStatus()
      );
      $contributionStatusId = array_search('Completed',
        CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name')
      );
      $confirmJS = array('onclick' => "return confirmStatus( {$participantStatusId}, {$contributionStatusId} );");
    }

    // get the participant status names to build special status array which is used to show notification
    // checkbox below participant status select
    $participantStatusName = CRM_Event_PseudoConstant::participantStatus();
    $notificationStatuses = array(
      'Cancelled',
      'Pending from waitlist',
      'Pending from approval',
      'Expired',
    );

    // get the required status and then implode only ids
    $notificationStatusIds = implode(',', array_keys(array_intersect($participantStatusName, $notificationStatuses)));
    $this->assign('notificationStatusIds', $notificationStatusIds);

    $this->_participantStatuses = $statusOptions = CRM_Event_BAO_Participant::buildOptions('status_id', 'create');

    // Only show refund status when editing
    if ($this->_action & CRM_Core_Action::ADD) {
      $pendingRefundStatusId = array_search('Pending refund', $participantStatusName);
      if ($pendingRefundStatusId) {
        unset($statusOptions[$pendingRefundStatusId]);
      }
    }

    $this->addSelect('status_id', $checkCancelledJs + array(
        'options' => $statusOptions,
        'option_url' => 'civicrm/admin/participant_status',
      ), TRUE);

    $this->addElement('checkbox', 'is_notify', ts('Send Notification'), NULL);

    $this->add('text', 'source', ts('Event Source'));
    $noteAttributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_Note');
    $this->add('textarea', 'note', ts('Notes'), $noteAttributes['note']);

    $buttons[] = array(
      'type' => 'upload',
      'name' => ts('Save'),
      'isDefault' => TRUE,
      'js' => $confirmJS,
    );

    $path = CRM_Utils_System::currentPath();
    $excludeForPaths = array(
      'civicrm/contact/search',
      'civicrm/group/search',
    );
    if (!in_array($path, $excludeForPaths)) {
      $buttons[] = array(
        'type' => 'upload',
        'name' => ts('Save and New'),
        'subName' => 'new',
        'js' => $confirmJS,
      );
    }

    $buttons[] = array(
      'type' => 'cancel',
      'name' => ts('Cancel'),
    );

    $this->addButtons($buttons);
    if ($this->_action == CRM_Core_Action::VIEW) {
      $this->freeze();
    }
  }

  /**
   * Add local and global form rules.
   *
   *
   * @return void
   */
  public function addRules() {
    $this->addFormRule(array('CRM_Event_Form_Participant', 'formRule'), $this);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $values
   *   Posted values of the form.
   * @param $files
   * @param $self
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($values, $files, $self) {
    // If $values['_qf_Participant_next'] is Delete or
    // $values['event_id'] is empty, then return
    // instead of proceeding further.

    if ((CRM_Utils_Array::value('_qf_Participant_next', $values) == 'Delete') ||
      (!$values['event_id'])
    ) {
      return TRUE;
    }

    $errorMsg = array();

    if (!empty($values['payment_processor_id'])) {
      // make sure that payment instrument values (e.g. credit card number and cvv) are valid
      CRM_Core_Payment_Form::validatePaymentInstrument($values['payment_processor_id'], $values, $errorMsg, NULL);
    }

    if (!empty($values['record_contribution'])) {
      if (empty($values['financial_type_id'])) {
        $errorMsg['financial_type_id'] = ts('Please enter the associated Financial Type');
      }
      if (empty($values['payment_instrument_id'])) {
        $errorMsg['payment_instrument_id'] = ts('Payment Method is a required field.');
      }
    }

    // validate contribution status for 'Failed'.
    if ($self->_onlinePendingContributionId && !empty($values['record_contribution']) &&
      (CRM_Utils_Array::value('contribution_status_id', $values) ==
        array_search('Failed', CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name'))
      )
    ) {
      $errorMsg['contribution_status_id'] = ts('Please select a valid payment status before updating.');
    }

    // do the amount validations.
    //skip for update mode since amount is freeze, CRM-6052
    if ((!$self->_id && empty($values['total_amount']) &&
        empty($self->_values['line_items'])
      ) ||
      ($self->_id && !$self->_paymentId && isset($self->_values['line_items']) && is_array($self->_values['line_items']))
    ) {
      if ($priceSetId = CRM_Utils_Array::value('priceSetId', $values)) {
        CRM_Price_BAO_PriceField::priceSetValidation($priceSetId, $values, $errorMsg, TRUE);
      }
    }
    // For single additions - show validation error if the contact has already been registered
    // for this event.
    if ($self->_single && ($self->_action & CRM_Core_Action::ADD)) {
      if ($self->_context == 'standalone') {
        $contactId = CRM_Utils_Array::value('contact_id', $values);
      }
      else {
        $contactId = $self->_contactId;
      }

      $eventId = CRM_Utils_Array::value('event_id', $values);
      if (!empty($contactId) && !empty($eventId)) {
        $dupeCheck = new CRM_Event_BAO_Participant();
        $dupeCheck->contact_id = $contactId;
        $dupeCheck->event_id = $eventId;
        $dupeCheck->find(TRUE);
        if (!empty($dupeCheck->id)) {
          $errorMsg['event_id'] = ts("This contact has already been assigned to this event.");
        }
      }
    }
    return CRM_Utils_Array::crmIsEmptyArray($errorMsg) ? TRUE : $errorMsg;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    if ($this->_action & CRM_Core_Action::DELETE) {
      if (CRM_Utils_Array::value('delete_participant', $params) == 2) {
        $additionalId = (CRM_Event_BAO_Participant::getAdditionalParticipantIds($this->_id));
        $participantLinks = (CRM_Event_BAO_Participant::getAdditionalParticipantUrl($additionalId));
      }
      if (CRM_Utils_Array::value('delete_participant', $params) == 1) {
        $additionalIds = CRM_Event_BAO_Participant::getAdditionalParticipantIds($this->_id);
        foreach ($additionalIds as $value) {
          CRM_Event_BAO_Participant::deleteParticipant($value);
        }
      }
      CRM_Event_BAO_Participant::deleteParticipant($this->_id);
      CRM_Core_Session::setStatus(ts('Selected participant was deleted successfully.'), ts('Record Deleted'), 'success');
      if (!empty($participantLinks)) {
        $status = ts('The following participants no longer have an event fee recorded. You can edit their registration and record a replacement contribution by clicking the links below:') . '<br/>' . $participantLinks;
        CRM_Core_Session::setStatus($status, ts('Group Payment Deleted'));
      }
      return;
    }
    // When adding a single contact, the formRule prevents you from adding duplicates
    // (See above in formRule()). When adding more than one contact, the duplicates are
    // removed automatically and the user receives one notification.
    if ($this->_action & CRM_Core_Action::ADD) {
      $event_id = $this->_eventId;
      if (empty($event_id) && !empty($params['event_id'])) {
        $event_id = $params['event_id'];
      }
      if (!$this->_single && !empty($event_id)) {
        $duplicateContacts = 0;
        while (list($k, $dupeCheckContactId) = each($this->_contactIds)) {
          // Eliminate contacts that have already been assigned to this event.
          $dupeCheck = new CRM_Event_BAO_Participant();
          $dupeCheck->contact_id = $dupeCheckContactId;
          $dupeCheck->event_id = $event_id;
          $dupeCheck->find(TRUE);
          if (!empty($dupeCheck->id)) {
            $duplicateContacts++;
            unset($this->_contactIds[$k]);
          }
        }
        if ($duplicateContacts > 0) {
          $msg = ts(
            "%1 contacts have already been assigned to this event. They were not added a second time.",
            array(1 => $duplicateContacts)
          );
          CRM_Core_Session::setStatus($msg);
        }
        if (count($this->_contactIds) == 0) {
          CRM_Core_Session::setStatus(ts("No participants were added."));
          return;
        }
        // We have to re-key $this->_contactIds so each contact has the same
        // key as their corresponding record in the $participants array that
        // will be created below.
        $this->_contactIds = array_values($this->_contactIds);
      }
    }

    $participantStatus = CRM_Event_PseudoConstant::participantStatus();
    // set the contact, when contact is selected
    if (!empty($params['contact_id'])) {
      $this->_contactId = $params['contact_id'];
    }
    if ($this->_priceSetId && $isQuickConfig = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_priceSetId, 'is_quick_config')) {
      $this->_quickConfig = $isQuickConfig;
    }

    if ($this->_id) {
      $params['id'] = $this->_id;
    }

    $config = CRM_Core_Config::singleton();
    if ($this->_isPaidEvent) {

      $contributionParams = array();
      $lineItem = array();
      $additionalParticipantDetails = array();
      if (CRM_Contribute_BAO_Contribution::checkContributeSettings('deferred_revenue_enabled')) {
        $eventStartDate = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $this->_eventId, 'start_date');
        if ($eventStartDate) {
          $contributionParams['revenue_recognition_date'] = date('Ymd', strtotime($eventStartDate));
        }
      }
      if (($this->_id && $this->_action & CRM_Core_Action::UPDATE) && $this->_paymentId) {
        $participantBAO = new CRM_Event_BAO_Participant();
        $participantBAO->id = $this->_id;
        $participantBAO->find(TRUE);
        $contributionParams['total_amount'] = $participantBAO->fee_amount;

        $params['discount_id'] = NULL;
        //re-enter the values for UPDATE mode
        $params['fee_level'] = $params['amount_level'] = $participantBAO->fee_level;
        $params['fee_amount'] = $participantBAO->fee_amount;
        if (isset($params['priceSetId'])) {
          $lineItem[0] = CRM_Price_BAO_LineItem::getLineItems($this->_id);
        }
        //also add additional participant's fee level/priceset
        if (CRM_Event_BAO_Participant::isPrimaryParticipant($this->_id)) {
          $additionalIds = CRM_Event_BAO_Participant::getAdditionalParticipantIds($this->_id);
          $hasLineItems = CRM_Utils_Array::value('priceSetId', $params, FALSE);
          $additionalParticipantDetails = CRM_Event_BAO_Participant::getFeeDetails($additionalIds,
            $hasLineItems
          );
        }
      }
      else {

        //check if discount is selected
        if (!empty($params['discount_id'])) {
          $discountId = $params['discount_id'];
        }
        else {
          $discountId = $params['discount_id'] = 'null';
        }

        //lets carry currency, CRM-4453
        $params['fee_currency'] = $config->defaultCurrency;
        CRM_Price_BAO_PriceSet::processAmount($this->_values['fee'],
          $params, $lineItem[0]
        );
        //CRM-11529 for quick config backoffice transactions
        //when financial_type_id is passed in form, update the
        //lineitems with the financial type selected in form
        $submittedFinancialType = CRM_Utils_Array::value('financial_type_id', $params);
        $isPaymentRecorded = CRM_Utils_Array::value('record_contribution', $params);
        if ($isPaymentRecorded && $this->_quickConfig && $submittedFinancialType) {
          foreach ($lineItem[0] as &$values) {
            $values['financial_type_id'] = $submittedFinancialType;
          }
        }

        $params['fee_level'] = $params['amount_level'];
        $contributionParams['total_amount'] = $params['amount'];
        if ($this->_quickConfig && !empty($params['total_amount']) &&
          $params['status_id'] != array_search('Partially paid', $participantStatus)
        ) {
          $params['fee_amount'] = $params['total_amount'];
        }
        else {
          //fix for CRM-3086
          $params['fee_amount'] = $params['amount'];
        }
      }

      if (isset($params['priceSetId'])) {
        if (!empty($lineItem[0])) {
          $this->set('lineItem', $lineItem);

          $this->_lineItem = $lineItem;
          $lineItem = array_merge($lineItem, $additionalParticipantDetails);

          $participantCount = array();
          foreach ($lineItem as $k) {
            foreach ($k as $v) {
              if (CRM_Utils_Array::value('participant_count', $v) > 0) {
                $participantCount[] = $v['participant_count'];
              }
            }
          }
        }
        if (isset($participantCount)) {
          $this->assign('pricesetFieldsCount', $participantCount);
        }
        $this->assign('lineItem', empty($lineItem[0]) || $this->_quickConfig ? FALSE : $lineItem);
      }
      else {
        $this->assign('amount_level', $params['amount_level']);
      }
    }

    $this->_params = $params;
    $amountOwed = NULL;
    if (isset($params['amount'])) {
      $amountOwed = $params['amount'];
      unset($params['amount']);
    }
    $params['register_date'] = CRM_Utils_Date::processDate($params['register_date'], $params['register_date_time']);
    $params['receive_date'] = CRM_Utils_Date::processDate(CRM_Utils_Array::value('receive_date', $params), CRM_Utils_Array::value('receive_date_time', $params));
    $params['contact_id'] = $this->_contactId;

    // overwrite actual payment amount if entered
    if (!empty($params['total_amount'])) {
      $contributionParams['total_amount'] = CRM_Utils_Array::value('total_amount', $params);
    }

    // Retrieve the name and email of the current user - this will be the FROM for the receipt email
    $userName = CRM_Core_Session::singleton()->getLoggedInContactDisplayName();

    if ($this->_contactId) {
      list($this->_contributorDisplayName, $this->_contributorEmail, $this->_toDoNotEmail) = CRM_Contact_BAO_Contact::getContactDetails($this->_contactId);
    }

    //modify params according to parameter used in create
    //participant method (addParticipant)
    $this->_params['participant_status_id'] = $params['status_id'];
    $this->_params['participant_role_id'] = is_array($params['role_id']) ? $params['role_id'] : explode(',', $params['role_id']);
    $this->_params['participant_register_date'] = $params['register_date'];
    $roleIdWithSeparator = implode(CRM_Core_DAO::VALUE_SEPARATOR, $this->_params['participant_role_id']);

    if ($this->_mode) {
      if (!$this->_isPaidEvent) {
        CRM_Core_Error::fatal(ts('Selected Event is not Paid Event '));
      }

      $eventTitle
        = CRM_Core_DAO::getFieldValue(
          'CRM_Event_DAO_Event',
          $params['event_id'],
          'title'
        );

      // set source if not set
      if (empty($params['source'])) {
        $this->_params['participant_source'] = ts('Offline Registration for Event: %2 by: %1', array(
            1 => $userName,
            2 => $eventTitle,
          ));
      }
      else {
        $this->_params['participant_source'] = $params['source'];
      }
      $this->_params['description'] = $this->_params['participant_source'];

      $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($this->_params['payment_processor_id'],
        $this->_mode
      );
      $now = date('YmdHis');
      $fields = array();

      // set email for primary location.
      $fields['email-Primary'] = 1;
      $params['email-Primary'] = $params["email-{$this->_bltID}"] = $this->_contributorEmail;

      $params['register_date'] = $now;

      // now set the values for the billing location.
      foreach ($this->_fields as $name => $dontCare) {
        $fields[$name] = 1;
      }

      // also add location name to the array
      $params["address_name-{$this->_bltID}"]
        = CRM_Utils_Array::value('billing_first_name', $params) . ' ' .
        CRM_Utils_Array::value('billing_middle_name', $params) . ' ' .
        CRM_Utils_Array::value('billing_last_name', $params);

      $params["address_name-{$this->_bltID}"] = trim($params["address_name-{$this->_bltID}"]);
      $fields["address_name-{$this->_bltID}"] = 1;
      $fields["email-{$this->_bltID}"] = 1;
      $ctype = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactId, 'contact_type');

      $nameFields = array('first_name', 'middle_name', 'last_name');

      foreach ($nameFields as $name) {
        $fields[$name] = 1;
        if (array_key_exists("billing_$name", $params)) {
          $params[$name] = $params["billing_{$name}"];
          $params['preserveDBName'] = TRUE;
        }
      }
      $contactID = CRM_Contact_BAO_Contact::createProfileContact($params, $fields, $this->_contactId, NULL, NULL, $ctype);
    }

    if (!empty($this->_params['participant_role_id'])) {
      $customFieldsRole = array();
      foreach ($this->_params['participant_role_id'] as $roleKey) {
        $customFieldsRole = CRM_Utils_Array::crmArrayMerge(CRM_Core_BAO_CustomField::getFields('Participant',
          FALSE, FALSE, $roleKey, $this->_roleCustomDataTypeID), $customFieldsRole);
      }
      $customFieldsEvent = CRM_Core_BAO_CustomField::getFields('Participant',
        FALSE,
        FALSE,
        CRM_Utils_Array::value('event_id', $params),
        $this->_eventNameCustomDataTypeID
      );
      $customFieldsEventType = CRM_Core_BAO_CustomField::getFields('Participant',
        FALSE,
        FALSE,
        $this->_eventTypeId,
        $this->_eventTypeCustomDataTypeID
      );
      $customFields = CRM_Utils_Array::crmArrayMerge($customFieldsRole,
        CRM_Core_BAO_CustomField::getFields('Participant', FALSE, FALSE, NULL, NULL, TRUE)
      );
      $customFields = CRM_Utils_Array::crmArrayMerge($customFieldsEvent, $customFields);
      $customFields = CRM_Utils_Array::crmArrayMerge($customFieldsEventType, $customFields);
      $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
        $this->_id,
        'Participant'
      );
    }

    //do cleanup line  items if participant edit the Event Fee.
    if (($this->_lineItem || !isset($params['proceSetId'])) && !$this->_paymentId && $this->_id) {
      CRM_Price_BAO_LineItem::deleteLineItems($this->_id, 'civicrm_participant');
    }

    if ($this->_mode) {
      // add all the additional payment params we need
      $this->_params["state_province-{$this->_bltID}"] = $this->_params["billing_state_province-{$this->_bltID}"] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($this->_params["billing_state_province_id-{$this->_bltID}"]);
      $this->_params["country-{$this->_bltID}"] = $this->_params["billing_country-{$this->_bltID}"] = CRM_Core_PseudoConstant::countryIsoCode($this->_params["billing_country_id-{$this->_bltID}"]);

      $this->_params['year'] = CRM_Core_Payment_Form::getCreditCardExpirationYear($this->_params);
      $this->_params['month'] = CRM_Core_Payment_Form::getCreditCardExpirationMonth($this->_params);
      $this->_params['ip_address'] = CRM_Utils_System::ipAddress();
      $this->_params['amount'] = $params['fee_amount'];
      $this->_params['amount_level'] = $params['amount_level'];
      $this->_params['currencyID'] = $config->defaultCurrency;
      $this->_params['invoiceID'] = md5(uniqid(rand(), TRUE));

      // at this point we've created a contact and stored its address etc
      // all the payment processors expect the name and address to be in the
      // so we copy stuff over to first_name etc.
      $paymentParams = $this->_params;
      if (!empty($this->_params['send_receipt'])) {
        $paymentParams['email'] = $this->_contributorEmail;
      }

      // The only reason for merging in the 'contact_id' rather than ensuring it is set
      // is that this patch is being done around the time of the stable release
      // so more conservative approach is called for.
      // In fact the use of $params and $this->_params & $this->_contactId vs $contactID
      // needs rationalising.
      $mapParams = array_merge(array('contact_id' => $contactID), $this->_params);
      CRM_Core_Payment_Form::mapParams($this->_bltID, $mapParams, $paymentParams, TRUE);

      $payment = $this->_paymentProcessor['object'];

      // CRM-15622: fix for incorrect contribution.fee_amount
      $paymentParams['fee_amount'] = NULL;
      $result = $payment->doPayment($paymentParams);

      if (is_a($result, 'CRM_Core_Error')) {
        CRM_Core_Error::displaySessionError($result);
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view/participant',
          "reset=1&action=add&cid={$this->_contactId}&context=participant&mode={$this->_mode}"
        ));
      }

      if ($result) {
        $this->_params = array_merge($this->_params, $result);
      }

      $this->_params['receive_date'] = $now;

      if (!empty($this->_params['send_receipt'])) {
        $this->_params['receipt_date'] = $now;
      }
      else {
        $this->_params['receipt_date'] = NULL;
      }

      $this->set('params', $this->_params);
      $this->assign('trxn_id', $result['trxn_id']);
      $this->assign('receive_date',
        CRM_Utils_Date::processDate($this->_params['receive_date'])
      );

      //add contribution record
      $this->_params['financial_type_id']
        = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $params['event_id'], 'financial_type_id');
      $this->_params['mode'] = $this->_mode;

      //add contribution record
      $contributions[] = $contribution = CRM_Event_Form_Registration_Confirm::processContribution($this, $this->_params, $result, $contactID, FALSE);

      // add participant record
      $participants = array();
      if (!empty($this->_params['role_id']) && is_array($this->_params['role_id'])) {
        $this->_params['role_id'] = implode(CRM_Core_DAO::VALUE_SEPARATOR,
          $this->_params['role_id']
        );
      }

      //CRM-15372 patch to fix fee amount replacing amount
      $this->_params['fee_amount'] = $this->_params['amount'];

      $participants[] = CRM_Event_Form_Registration::addParticipant($this, $contactID);

      //add custom data for participant
      CRM_Core_BAO_CustomValueTable::postProcess($this->_params,
        'civicrm_participant',
        $participants[0]->id,
        'Participant'
      );
      //add participant payment
      $paymentParticipant = array(
        'participant_id' => $participants[0]->id,
        'contribution_id' => $contribution->id,
      );
      $ids = array();

      CRM_Event_BAO_ParticipantPayment::create($paymentParticipant, $ids);
      $this->_contactIds[] = $this->_contactId;
    }
    else {
      $participants = array();
      if ($this->_single) {
        if ($params['role_id']) {
          $params['role_id'] = $roleIdWithSeparator;
        }
        else {
          $params['role_id'] = 'NULL';
        }
        $participants[] = CRM_Event_BAO_Participant::create($params);
      }
      else {
        foreach ($this->_contactIds as $contactID) {
          $commonParams = $params;
          $commonParams['contact_id'] = $contactID;
          if ($commonParams['role_id']) {
            $commonParams['role_id'] = $commonParams['role_id'] = str_replace(',', CRM_Core_DAO::VALUE_SEPARATOR, $params['role_id']);
          }
          else {
            $commonParams['role_id'] = 'NULL';
          }
          $participants[] = CRM_Event_BAO_Participant::create($commonParams);
        }
      }

      if (isset($params['event_id'])) {
        $eventTitle = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event',
          $params['event_id'],
          'title'
        );
      }

      if ($this->_single) {
        $this->_contactIds[] = $this->_contactId;
      }

      $contributions = array();
      if (!empty($params['record_contribution'])) {
        if (!empty($params['id'])) {
          if ($this->_onlinePendingContributionId) {
            $ids['contribution'] = $this->_onlinePendingContributionId;
          }
          else {
            $ids['contribution'] = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment',
              $params['id'],
              'contribution_id',
              'participant_id'
            );
          }
        }
        unset($params['note']);

        //build contribution params
        if (!$this->_onlinePendingContributionId) {
          if (empty($params['source'])) {
            $contributionParams['source'] = ts('%1 : Offline registration (by %2)', array(
                1 => $eventTitle,
                2 => $userName,
              ));
          }
          else {
            $contributionParams['source'] = $params['source'];
          }
        }

        $contributionParams['currency'] = $config->defaultCurrency;
        $contributionParams['non_deductible_amount'] = 'null';
        $contributionParams['receipt_date'] = !empty($params['send_receipt']) ? CRM_Utils_Array::value('receive_date', $params) : 'null';

        $recordContribution = array(
          'contact_id',
          'financial_type_id',
          'payment_instrument_id',
          'trxn_id',
          'contribution_status_id',
          'receive_date',
          'check_number',
          'campaign_id',
        );

        foreach ($recordContribution as $f) {
          $contributionParams[$f] = CRM_Utils_Array::value($f, $params);
          if ($f == 'trxn_id') {
            $this->assign('trxn_id', $contributionParams[$f]);
          }
        }

        //insert financial type name in receipt.
        $this->assign('financialTypeName', CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType',
          $contributionParams['financial_type_id']));
        // legacy support
        $this->assign('contributionTypeName', CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType', $contributionParams['financial_type_id']));
        $contributionParams['skipLineItem'] = 1;
        if ($this->_id) {
          $contributionParams['contribution_mode'] = 'participant';
          $contributionParams['participant_id'] = $this->_id;
        }
        // Set is_pay_later flag for back-office offline Pending status contributions
        if ($contributionParams['contribution_status_id'] == CRM_Core_OptionGroup::getValue('contribution_status', 'Pending', 'name')) {
          $contributionParams['is_pay_later'] = 1;
        }
        elseif ($contributionParams['contribution_status_id'] == CRM_Core_OptionGroup::getValue('contribution_status', 'Completed', 'name')) {
          $contributionParams['is_pay_later'] = 0;
        }

        if ($params['status_id'] == array_search('Partially paid', $participantStatus)) {
          if (!$amountOwed && $this->_action & CRM_Core_Action::UPDATE) {
            $amountOwed = $params['fee_amount'];
          }

          // if multiple participants are link, consider contribution total amount as the amount Owed
          if ($this->_id && CRM_Event_BAO_Participant::isPrimaryParticipant($this->_id)) {
            $amountOwed = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution',
              $ids['contribution'],
              'total_amount'
            );
          }

          // CRM-13964 partial_payment_total
          if ($amountOwed > $params['total_amount']) {
            // the owed amount
            $contributionParams['partial_payment_total'] = $amountOwed;
            // the actual amount paid
            $contributionParams['partial_amount_pay'] = $params['total_amount'];
          }
        }

        if (CRM_Utils_Array::value('tax_amount', $this->_params)) {
          $contributionParams['tax_amount'] = $this->_params['tax_amount'];
        }

        if ($this->_single) {
          if (empty($ids)) {
            $ids = array();
          }
          $contributions[] = CRM_Contribute_BAO_Contribution::create($contributionParams, $ids);
        }
        else {
          $ids = array();
          foreach ($this->_contactIds as $contactID) {
            $contributionParams['contact_id'] = $contactID;
            $contributions[] = CRM_Contribute_BAO_Contribution::create($contributionParams, $ids);
          }
        }

        //insert payment record for this participation
        if (empty($ids['contribution'])) {
          foreach ($this->_contactIds as $num => $contactID) {
            $ppDAO = new CRM_Event_DAO_ParticipantPayment();
            $ppDAO->participant_id = $participants[$num]->id;
            $ppDAO->contribution_id = $contributions[$num]->id;
            $ppDAO->save();
          }
        }
        // next create the transaction record
        $transaction = new CRM_Core_Transaction();

        // CRM-11124
        if ($this->_params['discount_id']) {
          CRM_Event_BAO_Participant::createDiscountTrxn(
            $this->_eventId,
            $contributionParams,
            NULL,
            CRM_Price_BAO_PriceSet::parseFirstPriceSetValueIDFromParams($this->_params)
          );
        }
        $transaction->commit();
      }
    }

    // also store lineitem stuff here
    if ((($this->_lineItem & $this->_action & CRM_Core_Action::ADD) ||
      ($this->_lineItem && CRM_Core_Action::UPDATE && !$this->_paymentId))
    ) {
      foreach ($this->_contactIds as $num => $contactID) {
        foreach ($this->_lineItem as $key => $value) {
          if (is_array($value) && $value != 'skip') {
            foreach ($value as $lineKey => $line) {
              //10117 update the line items for participants if contribution amount is recorded
              if ($this->_quickConfig && !empty($params['total_amount']) &&
                ($params['status_id'] != array_search('Partially paid', $participantStatus))
              ) {
                $line['unit_price'] = $line['line_total'] = $params['total_amount'];
                if (!empty($params['tax_amount'])) {
                  $line['unit_price'] = $line['unit_price'] - $params['tax_amount'];
                  $line['line_total'] = $line['line_total'] - $params['tax_amount'];
                }
              }
              $lineItem[$this->_priceSetId][$lineKey] = $line;
            }
            CRM_Price_BAO_LineItem::processPriceSet($participants[$num]->id, $lineItem, CRM_Utils_Array::value($num, $contributions, NULL), 'civicrm_participant');
            CRM_Contribute_BAO_Contribution::addPayments($contributions);
          }
        }
      }
    }

    $updateStatusMsg = NULL;
    //send mail when participant status changed, CRM-4326
    if ($this->_id && $this->_statusId &&
      $this->_statusId != CRM_Utils_Array::value('status_id', $params) && !empty($params['is_notify'])
    ) {

      $updateStatusMsg = CRM_Event_BAO_Participant::updateStatusMessage($this->_id,
        $params['status_id'],
        $this->_statusId
      );
    }

    $sent = array();
    $notSent = array();
    if (!empty($params['send_receipt'])) {
      if (array_key_exists($params['from_email_address'], $this->_fromEmails['from_email_id'])) {
        $receiptFrom = $params['from_email_address'];
      }

      $this->assign('module', 'Event Registration');
      //use of the message template below requires variables in different format
      $event = $events = array();
      $returnProperties = array('fee_label', 'start_date', 'end_date', 'is_show_location', 'title');

      //get all event details.
      CRM_Core_DAO::commonRetrieveAll('CRM_Event_DAO_Event', 'id', $params['event_id'], $events, $returnProperties);
      $event = $events[$params['event_id']];
      unset($event['start_date']);
      unset($event['end_date']);

      $role = CRM_Event_PseudoConstant::participantRole();
      $participantRoles = CRM_Utils_Array::value('role_id', $params);
      if (is_array($participantRoles)) {
        $selectedRoles = array();
        foreach ($participantRoles as $roleId) {
          $selectedRoles[] = $role[$roleId];
        }
        $event['participant_role'] = implode(', ', $selectedRoles);
      }
      else {
        $event['participant_role'] = CRM_Utils_Array::value($participantRoles, $role);
      }
      $event['is_monetary'] = $this->_isPaidEvent;

      if ($params['receipt_text']) {
        $event['confirm_email_text'] = $params['receipt_text'];
      }

      $this->assign('isAmountzero', 1);
      $this->assign('event', $event);

      $this->assign('isShowLocation', $event['is_show_location']);
      if (CRM_Utils_Array::value('is_show_location', $event) == 1) {
        $locationParams = array(
          'entity_id' => $params['event_id'],
          'entity_table' => 'civicrm_event',
        );
        $location = CRM_Core_BAO_Location::getValues($locationParams, TRUE);
        $this->assign('location', $location);
      }

      $status = CRM_Event_PseudoConstant::participantStatus();
      if ($this->_isPaidEvent) {
        $paymentInstrument = CRM_Contribute_PseudoConstant::paymentInstrument();
        if (!$this->_mode) {
          if (isset($params['payment_instrument_id'])) {
            $this->assign('paidBy',
              CRM_Utils_Array::value($params['payment_instrument_id'],
                $paymentInstrument
              )
            );
          }
        }

        $this->assign('totalAmount', $contributionParams['total_amount']);
        if (isset($contributionParams['partial_payment_total'])) {
          // balance amount
          $balanceAmount = $contributionParams['partial_payment_total'] - $contributionParams['partial_amount_pay'];
          $this->assign('balanceAmount', $balanceAmount);
        }
        $this->assign('isPrimary', 1);
        $this->assign('checkNumber', CRM_Utils_Array::value('check_number', $params));
      }
      if ($this->_mode) {
        $this->assignBillingName($params);
        $this->assign('address', CRM_Utils_Address::getFormattedBillingAddressFieldsFromParameters(
          $this->_params,
          $this->_bltID
        ));

        $date = CRM_Utils_Date::format($params['credit_card_exp_date']);
        $date = CRM_Utils_Date::mysqlToIso($date);
        $this->assign('credit_card_exp_date', $date);
        $this->assign('credit_card_number',
          CRM_Utils_System::mungeCreditCard($params['credit_card_number'])
        );
        $this->assign('credit_card_type', $params['credit_card_type']);
        // The concept of contributeMode is deprecated.
        $this->assign('contributeMode', 'direct');
        $this->assign('isAmountzero', 0);
        $this->assign('is_pay_later', 0);
        $this->assign('isPrimary', 1);
      }

      $this->assign('register_date', $params['register_date']);
      if ($params['receive_date']) {
        $this->assign('receive_date', $params['receive_date']);
      }

      $participant = array(array('participant_id', '=', $participants[0]->id, 0, 0));
      // check whether its a test drive ref CRM-3075
      if (!empty($this->_defaultValues['is_test'])) {
        $participant[] = array('participant_test', '=', 1, 0, 0);
      }

      $template = CRM_Core_Smarty::singleton();
      $customGroup = array();
      //format submitted data
      foreach ($params['custom'] as $fieldID => $values) {
        foreach ($values as $fieldValue) {
          $customFields[$fieldID]['id'] = $fieldID;
          $formattedValue = CRM_Core_BAO_CustomField::displayValue($fieldValue['value'], $fieldID, $participants[0]->id);
          $customGroup[$customFields[$fieldID]['groupTitle']][$customFields[$fieldID]['label']] = str_replace('&nbsp;', '', $formattedValue);
        }
      }

      foreach ($this->_contactIds as $num => $contactID) {
        // Retrieve the name and email of the contact - this will be the TO for receipt email
        list($this->_contributorDisplayName, $this->_contributorEmail, $this->_toDoNotEmail) = CRM_Contact_BAO_Contact::getContactDetails($contactID);

        $this->_contributorDisplayName = ($this->_contributorDisplayName == ' ') ? $this->_contributorEmail : $this->_contributorDisplayName;

        $waitStatus = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Waiting'");
        if ($waitingStatus = CRM_Utils_Array::value($params['status_id'], $waitStatus)) {
          $this->assign('isOnWaitlist', TRUE);
        }

        $this->assign('customGroup', $customGroup);
        $this->assign('contactID', $contactID);
        $this->assign('participantID', $participants[$num]->id);

        $this->_id = $participants[$num]->id;

        if ($this->_isPaidEvent) {
          // fix amount for each of participants ( for bulk mode )
          $eventAmount = array();
          $invoiceSettings = Civi::settings()->get('contribution_invoice_settings');
          $invoicing = CRM_Utils_Array::value('invoicing', $invoiceSettings);
          $totalTaxAmount = 0;

          //add dataArray in the receipts in ADD and UPDATE condition
          $dataArray = array();
          if ($this->_action & CRM_Core_Action::ADD) {
            $line = $lineItem[0];
          }
          elseif ($this->_action & CRM_Core_Action::UPDATE) {
            $line = $this->_values['line_items'];
          }
          if ($invoicing) {
            foreach ($line as $key => $value) {
              if (isset($value['tax_amount'])) {
                $totalTaxAmount += $value['tax_amount'];
                if (isset($dataArray[(string) $value['tax_rate']])) {
                  $dataArray[(string) $value['tax_rate']] = $dataArray[(string) $value['tax_rate']] + CRM_Utils_Array::value('tax_amount', $value);
                }
                else {
                  $dataArray[(string) $value['tax_rate']] = CRM_Utils_Array::value('tax_amount', $value);
                }
              }
            }
            $this->assign('totalTaxAmount', $totalTaxAmount);
            $this->assign('taxTerm', CRM_Utils_Array::value('tax_term', $invoiceSettings));
            $this->assign('dataArray', $dataArray);
          }
          if (!empty($additionalParticipantDetails)) {
            $params['amount_level'] = preg_replace('//', '', $params['amount_level']) . ' - ' . $this->_contributorDisplayName;
          }

          $eventAmount[$num] = array(
            'label' => preg_replace('//', '', $params['amount_level']),
            'amount' => $params['fee_amount'],
          );
          //as we are using same template for online & offline registration.
          //So we have to build amount as array.
          $eventAmount = array_merge($eventAmount, $additionalParticipantDetails);
          $this->assign('amount', $eventAmount);
        }

        $sendTemplateParams = array(
          'groupName' => 'msg_tpl_workflow_event',
          'valueName' => 'event_offline_receipt',
          'contactId' => $contactID,
          'isTest' => !empty($this->_defaultValues['is_test']),
          'PDFFilename' => ts('confirmation') . '.pdf',
        );

        // try to send emails only if email id is present
        // and the do-not-email option is not checked for that contact
        if ($this->_contributorEmail and !$this->_toDoNotEmail) {
          $sendTemplateParams['from'] = $receiptFrom;
          $sendTemplateParams['toName'] = $this->_contributorDisplayName;
          $sendTemplateParams['toEmail'] = $this->_contributorEmail;
          $sendTemplateParams['cc'] = CRM_Utils_Array::value('cc', $this->_fromEmails);
          $sendTemplateParams['bcc'] = CRM_Utils_Array::value('bcc', $this->_fromEmails);
        }

        //send email with pdf invoice
        $template = CRM_Core_Smarty::singleton();
        $taxAmt = $template->get_template_vars('dataArray');
        $contributionId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment',
          $this->_id, 'contribution_id', 'participant_id'
        );
        $prefixValue = Civi::settings()->get('contribution_invoice_settings');
        $invoicing = CRM_Utils_Array::value('invoicing', $prefixValue);
        if (count($taxAmt) > 0 && (isset($invoicing) && isset($prefixValue['is_email_pdf']))) {
          $sendTemplateParams['isEmailPdf'] = TRUE;
          $sendTemplateParams['contributionId'] = $contributionId;
        }
        list($mailSent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
        if ($mailSent) {
          $sent[] = $contactID;
          foreach ($participants as $ids => $values) {
            if ($values->contact_id == $contactID) {
              $values->details = CRM_Utils_Array::value('receipt_text', $params);
              CRM_Activity_BAO_Activity::addActivity($values, 'Email');
              break;
            }
          }
        }
        else {
          $notSent[] = $contactID;
        }
      }
    }

    // set the participant id if it is not set
    if (!$this->_id) {
      $this->_id = $participants[0]->id;
    }

    if (($this->_action & CRM_Core_Action::UPDATE)) {
      $statusMsg = ts('Event registration information for %1 has been updated.', array(1 => $this->_contributorDisplayName));
      if (!empty($params['send_receipt']) && count($sent)) {
        $statusMsg .= ' ' . ts('A confirmation email has been sent to %1', array(1 => $this->_contributorEmail));
      }

      if ($updateStatusMsg) {
        $statusMsg = "{$statusMsg} {$updateStatusMsg}";
      }
    }
    elseif ($this->_action & CRM_Core_Action::ADD) {
      if ($this->_single) {
        $statusMsg = ts('Event registration for %1 has been added.', array(1 => $this->_contributorDisplayName));
        if (!empty($params['send_receipt']) && count($sent)) {
          $statusMsg .= ' ' . ts('A confirmation email has been sent to %1.', array(1 => $this->_contributorEmail));
        }
      }
      else {
        $statusMsg = ts('Total Participant(s) added to event: %1.', array(1 => count($this->_contactIds)));
        if (count($notSent) > 0) {
          $statusMsg .= ' ' . ts('Email has NOT been sent to %1 contact(s) - communication preferences specify DO NOT EMAIL OR valid Email is NOT present. ', array(1 => count($notSent)));
        }
        elseif (isset($params['send_receipt'])) {
          $statusMsg .= ' ' . ts('A confirmation email has been sent to ALL participants');
        }
      }
    }
    CRM_Core_Session::setStatus($statusMsg, ts('Saved'), 'success');
    $session = CRM_Core_Session::singleton();
    $buttonName = $this->controller->getButtonName();
    if ($this->_context == 'standalone') {
      if ($buttonName == $this->getButtonName('upload', 'new')) {
        $urlParams = 'reset=1&action=add&context=standalone';
        if ($this->_mode) {
          $urlParams .= '&mode=' . $this->_mode;
        }
        if ($this->_eID) {
          $urlParams .= '&eid=' . $this->_eID;
        }
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/participant/add', $urlParams));
      }
      else {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view',
          "reset=1&cid={$this->_contactId}&selectedChild=participant"
        ));
      }
    }
    elseif ($buttonName == $this->getButtonName('upload', 'new')) {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view/participant',
        "reset=1&action=add&context={$this->_context}&cid={$this->_contactId}"
      ));
    }
  }

}
