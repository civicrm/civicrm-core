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
 * Back office participant form.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\API\EntityLookupTrait;
use Civi\Api4\Contribution;
use Civi\Api4\LineItem;

/**
 * Back office participant form.
 */
class CRM_Event_Form_Participant extends CRM_Contribute_Form_AbstractEditPayment {

  use EntityLookupTrait;
  use CRM_Contact_Form_ContactFormTrait;
  use CRM_Event_Form_EventFormTrait;
  use CRM_Custom_Form_CustomDataTrait;

  /**
   * Participant ID - use getParticipantID.
   *
   * @var int
   *
   * @deprecated unused
   */
  public $_pId;

  /**
   * ID of discount record.
   *
   * @var int
   */
  public $_discountId;

  public $useLivePageJS = TRUE;

  /**
   * The values for the contribution db object.
   *
   * @var array
   *
   * @deprecated use $this->getPriceFieldMetadata()
   */
  public $_values;

  /**
   * Price Set ID, if the new price set method is used
   *
   * @var int
   *
   * @internal use getPriceSetID().
   */
  public $_priceSetId;

  /**
   * Array of fields for the price set.
   *
   * @var array
   */
  public $_priceSet;

  /**
   * The id of the participation that we are processing.
   *
   * @var int
   *
   * @internal use getParticipantID to access in a supported way.
   */
  public $_id;

  /**
   * The id of the note.
   *
   * @var int
   */
  protected $_noteId = NULL;

  /**
   *
   * Use parent $this->contactID
   *
   * The id of the contact associated with this participation.
   *
   * @var int
   * @deprecated
   */
  public $_contactId;

  /**
   * Are we operating in "single mode", i.e. adding / editing only
   * one participant record, or is this a batch add operation.
   *
   * Note the goal is to disentangle all the non-single stuff
   * to CRM_Event_Form_Task_Register and discontinue this param.
   *
   * @var bool
   */
  public $_single = TRUE;

  /**
   * If event is paid or unpaid.
   *
   * @var bool
   */
  public $_isPaidEvent;

  /**
   * Page action.
   *
   * @var int
   */
  public $_action;

  /**
   * Event Type Id.
   *
   * @var int
   */
  protected $_eventTypeId = NULL;

  /**
   * Participant status Id.
   *
   * @var int
   */
  protected $_statusId = NULL;

  /**
   * Participant mode.
   *
   * @var string
   */
  public $_mode;

  /**
   * Event ID preselect.
   *
   * @var int
   */
  public $_eID = NULL;

  public $_online;

  /**
   * Selected discount id.
   *
   * @var int
   */
  public $_originalDiscountId;

  /**
   * Event id.
   *
   * @var int
   *
   * @internal - use getEventID to access in a supported way
   */
  public $_eventId;

  /**
   * Id of payment, if any
   *
   * @var int
   *
   * @internal
   */
  public $_paymentId;

  /**
   * @var null
   * @todo add explanatory note about this
   */
  public $_onlinePendingContributionId;

  /**
   * Params for creating a payment to add to the contribution.
   *
   * @var array
   */
  protected $createPaymentParams = [];

  /**
   * @var \CRM_Financial_BAO_Order
   */
  private $order;

  /**
   * Get the selected Event ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @return int|null
   */
  public function getEventID(): ?int {
    if (!$this->_eventId) {
      if ($this->isFormBuilt()) {
        $this->_eventId = $this->getSubmittedValue('event_id');
      }
      else {
        $this->_eventId = $this->getSubmitValue('event_id');
      }
    }
    return $this->_eventId ? (int) $this->_eventId : NULL;
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
   * Get params to create payments.
   *
   * @return array
   */
  protected function getCreatePaymentParams(): array {
    return $this->createPaymentParams;
  }

  /**
   * Set params to create payments.
   *
   * @param array $createPaymentParams
   */
  protected function setCreatePaymentParams(array $createPaymentParams): void {
    $this->createPaymentParams = $createPaymentParams;
  }

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity(): string {
    return 'Participant';
  }

  /**
   * Default form context used as part of addField()
   */
  public function getDefaultContext(): string {
    return 'create';
  }

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    parent::preProcess();
    $this->assign('feeBlockPaid', FALSE);

    // @todo eliminate this duplication.
    $this->_contactId = $this->getContactID();
    $this->_eID = CRM_Utils_Request::retrieve('eid', 'Positive', $this);
    $this->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);
    $this->assign('context', $this->_context);

    if ($this->getContactID()) {
      $this->setPageTitle(ts('Event Registration for %1', [1 => $this->getContactValue('display_name')]));
    }
    else {
      $this->setPageTitle(ts('Event Registration'));
    }

    $this->assign('participantId', $this->getParticipantID());
    if ($this->getParticipantID()) {

      $this->_paymentId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment',
        $this->_id, 'id', 'participant_id'
      );

      $this->assign('hasPayment', $this->_paymentId);
      $this->assign('componentId', $this->getParticipantID());
      $this->assign('component', 'event');

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
    $this->setCustomDataTypes();

    $this->assign('participantMode', $this->_mode);

    $isOverloadFeesMode = $this->isOverloadFeesMode();
    $this->assign('showFeeBlock', $isOverloadFeesMode);
    if ($isOverloadFeesMode) {
      if (CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $_GET['eventId'], 'is_monetary')) {
        $this->assign('feeBlockPaid', TRUE);
      }
      return;
    }

    $this->assignUrlPath();

    $this->assign('single', $this->_single);

    if (!$this->getParticipantID()) {
      $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add');
    }
    $this->assign('action', $this->_action);

    // check for edit permission
    if (!CRM_Core_Permission::checkActionPermission('CiviEvent', $this->_action)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }

    // when fee amount is included in form
    if (!empty($_POST['hidden_feeblock']) || !empty($_POST['send_receipt'])) {
      if ($this->_submitValues['event_id']) {
        $this->_eventId = (int) $this->_submitValues['event_id'];
      }
      $this->buildEventFeeForm($this);
      CRM_Event_Form_EventFees::setDefaultValues($this);
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
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function setDefaultValues(): array {
    if ($this->isOverloadFeesMode()) {
      if ($this->getEventID()) {
        //get receipt text and financial type
        $returnProperities = ['confirm_email_text', 'financial_type_id', 'campaign_id', 'start_date'];
        CRM_Core_DAO::commonRetrieveAll('CRM_Event_DAO_Event', 'id', $this->getEventID(), $details, $returnProperities);
        if (!empty($details[$this->getEventID()]['financial_type_id'])) {
          $defaults['financial_type_id'] = $details[$this->getEventID()]['financial_type_id'];
        }
        if (!empty($details[$this->getEventID()]['confirm_email_text'])) {
          $defaults['receipt_text'] = $details[$this->getEventID()]['confirm_email_text'];
        }
        if (!$this->getParticipantID()) {
          $defaults['send_receipt'] = (strtotime(CRM_Utils_Array::value('start_date', $details[$this->getEventID()])) >= time()) ? 1 : 0;
          $defaults['receive_date'] = date('Y-m-d H:i:s');
        }
      }

      //CRM-11601 we should keep the record contribution
      //true by default while adding participant
      if ($this->getAction() === CRM_Core_Action::ADD && !$this->_mode && $this->_isPaidEvent) {
        $defaults['record_contribution'] = 1;
      }

      //CRM-13420
      if (empty($defaults['payment_instrument_id'])) {
        $defaults['payment_instrument_id'] = key(CRM_Core_OptionGroup::values('payment_instrument', FALSE, FALSE, FALSE, 'AND is_default = 1'));
      }
      return $defaults + CRM_Event_Form_EventFees::setDefaultValues($this);
    }

    $defaults = [];

    if ($this->getParticipantID()) {
      $ids = [];
      $params = ['id' => $this->_id];
      $defaults['send_receipt'] = 0;
      CRM_Event_BAO_Participant::getValues($params, $defaults, $ids);
      $defaults = $defaults[$this->_id];
      $sep = CRM_Core_DAO::VALUE_SEPARATOR;
      if ($defaults['role_id']) {
        $roleIDs = explode($sep, $defaults['role_id']);
      }
      $this->_contactId = $defaults['contact_id'];
      $this->_statusId = $defaults['participant_status_id'];

      //set defaults for note
      $noteDetails = CRM_Core_BAO_Note::getNote($this->_id, 'civicrm_participant');
      $defaults['note'] = array_pop($noteDetails);

      // Check if this is a primaryParticipant (registered for others) and retrieve additional participants if true  (CRM-4859)
      if (CRM_Event_BAO_Participant::isPrimaryParticipant($this->_id)) {
        $additionalParticipants = CRM_Event_BAO_Participant::getAdditionalParticipants($this->_id);
      }
      $this->assign('additionalParticipants', $additionalParticipants ?? NULL);

      // Get registered_by contact ID and display_name if participant was registered by someone else (CRM-4859)
      if (!empty($defaults['participant_registered_by_id'])) {
        $registered_by_contact_id = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant',
          $defaults['participant_registered_by_id'],
          'contact_id', 'id'
        );
        $this->assign('participant_registered_by_id', $defaults['participant_registered_by_id']);
        $this->assign('registered_by_display_name', CRM_Contact_BAO_Contact::displayName($registered_by_contact_id));
      }
      $this->assign('registered_by_contact_id', $registered_by_contact_id ?? NULL);
    }
    elseif ($this->_contactID) {
      $defaults['contact_id'] = $this->_contactID;
    }

    //setting default register date
    if ($this->_action == CRM_Core_Action::ADD) {
      $statuses = array_flip(CRM_Event_PseudoConstant::participantStatus());
      $defaults['status_id'] = $statuses['Registered'] ?? NULL;
      if (!empty($defaults['event_id'])) {
        $financialTypeID = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event',
          $defaults['event_id'],
          'financial_type_id'
        );
        if ($financialTypeID) {
          $defaults['financial_type_id'] = $financialTypeID;
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
          $defaults["email-{$this->_bltID}"] = $defaults['email-Primary'];
        }
      }

      $submittedEvent = $this->getElementValue('event_id');
      if (!empty($submittedEvent[0])) {
        $eventID = $submittedEvent[0];
      }
      $defaults['register_date'] = date('Y-m-d H:i:s');
    }
    else {
      $defaults['record_contribution'] = 0;

      if ($defaults['participant_is_pay_later']) {
        $this->assign('participant_is_pay_later', TRUE);
      }

      $eventID = $defaults['event_id'];

      $this->_eventTypeId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $eventID, 'event_type_id', 'id');

      if ($this->getDiscountID()) {
        // This doesn't seem used....
        $this->set('discountId', $this->_discountId);
      }
    }

    //assign event and role id, this is needed for Custom data building
    $sep = CRM_Core_DAO::VALUE_SEPARATOR;
    if (!empty($defaults['participant_role_id'])) {
      $roleIDs = explode($sep, $defaults['participant_role_id']);
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
        $roleIDs = (array) $defaults['participant_role_id'] = $roleID;
      }
      $defaults['event_id'] = $eventID;
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
      $defaults['role_id'] = implode(',', $roleIDs);
    }

    if (isset($eventID)) {
      $this->set('eventId', $eventID);
    }
    $this->assign('eventID', $eventID ?? NULL);

    $this->assign('eventTypeID', $this->_eventTypeId);

    $this->assign('event_is_test', $this->isTest());
    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {

    $participantStatuses = CRM_Event_PseudoConstant::participantStatus();
    $partiallyPaidStatusId = array_search('Partially paid', $participantStatuses);
    $this->assign('partiallyPaidStatusId', $partiallyPaidStatusId);

    if ($this->isOverloadFeesMode()) {
      $this->buildEventFeeForm($this);
      return;
    }

    if ($this->isSubmitted()) {
      // The custom data fields are added to the form by an ajax form.
      // However, if they are not present in the element index they will
      // not be available from `$this->getSubmittedValue()` in post process.
      // We do not have to set defaults or otherwise render - just add to the element index.
      $this->addCustomDataFieldsToForm('Participant', array_filter([
        'event_id' => $this->getEventID(),
        'role_id' => $_POST['role_id'] ?? NULL,
        'id' => $this->getParticipantID(),
      ]));
    }

    //need to assign custom data type to the template
    $this->assign('customDataType', 'Participant');

    $this->applyFilter('__ALL__', 'trim');

    if ($this->_single) {
      $contactField = $this->addEntityRef('contact_id', ts('Participant'), ['create' => TRUE, 'api' => ['extra' => ['email']]], TRUE);
      if ($this->_context !== 'standalone') {
        $contactField->freeze();
      }
    }

    $eventFieldParams = [
      'entity' => 'Event',
      'select' => ['minimumInputLength' => 0],
      'api' => [
        'extra' => ['campaign_id', 'default_role_id', 'event_type_id'],
      ],
    ];

    if ($this->_mode) {
      // exclude events which are not monetary when credit card registration is used
      $eventFieldParams['api']['params']['is_monetary'] = 1;
    }
    $this->addPaymentProcessorSelect(TRUE, FALSE, FALSE);

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
    $this->add('datepicker', 'register_date', ts('Registration Date'), [], TRUE, ['time' => TRUE]);

    $this->assign('entityID', $this->_id);

    $this->addSelect('role_id', ['multiple' => TRUE, 'class' => 'huge'], TRUE);

    // CRM-4395
    $checkCancelledJs = ['onchange' => 'return sendNotification( );'];
    $confirmJS = NULL;
    if ($this->_onlinePendingContributionId) {
      $cancelledparticipantStatusId = array_search('Cancelled', CRM_Event_PseudoConstant::participantStatus());
      $cancelledContributionStatusId = array_search('Cancelled',
        CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name')
      );
      $checkCancelledJs = [
        'onchange' => "checkCancelled( this.value, {$cancelledparticipantStatusId},{$cancelledContributionStatusId});",
      ];

      $participantStatusId = array_search('Pending from pay later',
        CRM_Event_PseudoConstant::participantStatus()
      );
      $contributionStatusId = array_search('Completed',
        CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name')
      );
      $confirmJS = ['onclick' => "return confirmStatus( {$participantStatusId}, {$contributionStatusId} );"];
    }

    // get the participant status names to build special status array which is used to show notification
    // checkbox below participant status select
    $participantStatusName = CRM_Event_PseudoConstant::participantStatus();
    $notificationStatuses = [
      'Cancelled',
      'Pending from waitlist',
      'Pending from approval',
      'Expired',
    ];

    // get the required status and then implode only ids
    $notificationStatusIds = implode(',', array_keys(array_intersect($participantStatusName, $notificationStatuses)));
    $this->assign('notificationStatusIds', $notificationStatusIds);

    $statusOptions = CRM_Event_BAO_Participant::buildOptions('status_id', 'create');

    // Only show refund status when editing
    if ($this->_action & CRM_Core_Action::ADD) {
      $pendingRefundStatusId = array_search('Pending refund', $participantStatusName);
      if ($pendingRefundStatusId) {
        unset($statusOptions[$pendingRefundStatusId]);
      }
    }

    $this->addSelect('status_id', $checkCancelledJs + [
      'options' => $statusOptions,
      'option_url' => 'civicrm/admin/participant_status',
    ], TRUE);

    $this->addElement('checkbox', 'is_notify', ts('Send Notification'), NULL);

    $this->addField('source', ['entity' => 'Participant', 'name' => 'source']);
    $noteAttributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_Note');
    $this->add('textarea', 'note', ts('Notes'), $noteAttributes['note']);

    $buttons[] = [
      'type' => 'upload',
      'name' => ts('Save'),
      'isDefault' => TRUE,
      'js' => $confirmJS,
    ];

    $path = CRM_Utils_System::currentPath();
    $excludeForPaths = [
      'civicrm/contact/search',
      'civicrm/group/search',
    ];
    if (!$this->getParticipantID() && !in_array($path, $excludeForPaths)) {
      $buttons[] = [
        'type' => 'upload',
        'name' => ts('Save and New'),
        'subName' => 'new',
        'js' => $confirmJS,
      ];
    }

    $buttons[] = [
      'type' => 'cancel',
      'name' => ts('Cancel'),
    ];

    $this->addButtons($buttons);
    if ($this->_action == CRM_Core_Action::VIEW) {
      $this->freeze();
    }
  }

  /**
   * Add local and global form rules.
   *
   * @return void
   */
  public function addRules(): void {
    $this->addFormRule(['CRM_Event_Form_Participant', 'formRule'], $this);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $values
   *   Posted values of the form.
   * @param $files
   * @param self $self
   *
   * @return array|true
   *   list of errors to be posted back to the form
   */
  public static function formRule($values, $files, $self) {
    // $values['event_id'] is empty, then return
    // instead of proceeding further - this is legacy handling
    // and it is unclear why but perhaps relates to the form's
    // 'multitasking' & can go once the form is not overloaded?
    // event_id is normally a required field..
    if (empty($values['event_id'])) {
      return TRUE;
    }

    $errorMsg = [];

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
      if (!empty($values['priceSetId'])) {
        CRM_Price_BAO_PriceField::priceSetValidation($self->getPriceSetID(), $values, $errorMsg);
      }
    }

    // do the amount validations.
    //skip for update mode since amount is freeze, CRM-6052
    if ((!$self->_id && empty($values['total_amount']) &&
        empty($self->_values['line_items'])
      ) ||
      ($self->_id && !$self->_paymentId && isset($self->_values['line_items']) && is_array($self->_values['line_items']))
    ) {
      // @todo - this seems unreachable.
      if ($self->getPriceSetID()) {
        CRM_Price_BAO_PriceField::priceSetValidation($self->getPriceSetID(), $values, $errorMsg, TRUE);
      }
    }
    // For single additions - show validation error if the contact has already been registered
    // for this event.
    if (($self->_action & CRM_Core_Action::ADD)) {
      if ($self->_context === 'standalone') {
        $contactId = $values['contact_id'] ?? NULL;
      }
      else {
        $contactId = $self->_contactId;
      }

      $eventId = $values['event_id'] ?? NULL;

      $event = new CRM_Event_DAO_Event();
      $event->id = $eventId;
      $event->find(TRUE);

      if (!$event->allow_same_participant_emails && !empty($contactId) && !empty($eventId)) {
        $cancelledStatusID = CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'status_id', 'Cancelled');
        $dupeCheck = new CRM_Event_BAO_Participant();
        $dupeCheck->contact_id = $contactId;
        $dupeCheck->event_id = $eventId;
        $dupeCheck->whereAdd("status_id != {$cancelledStatusID} ");
        $dupeCheck->find(TRUE);
        if (!empty($dupeCheck->id)) {
          $errorMsg['event_id'] = ts('This contact has already been assigned to this event.');
        }
      }
    }
    return empty($errorMsg) ? TRUE : $errorMsg;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    if ($this->getPriceSetID()) {
      $this->getOrder()->setPriceSelectionFromUnfilteredInput($this->getSubmittedValues());
    }
    $statusMsg = $this->submit($this->getSubmittedValues());
    CRM_Core_Session::setStatus($statusMsg, ts('Saved'), 'success');
    $session = CRM_Core_Session::singleton();
    $buttonName = $this->controller->getButtonName();
    if ($this->_context === 'standalone') {
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
    elseif ($this->_contactId) {
      // Refresh other tabs with related data
      $this->ajaxResponse['updateTabs'] = [
        '#tab_activity' => TRUE,
      ];
      if (CRM_Core_Permission::access('CiviContribute')) {
        $this->ajaxResponse['updateTabs']['#tab_contribute'] = CRM_Contact_BAO_Contact::getCountComponent('contribution', $this->_contactId);
      }
    }
  }

  /**
   * Submit form.
   *
   * @internal will be made protected / decommissioned once tests
   * in core & line item editor are fixed to not call it.
   *
   * @param array $params
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  public function submit(array $params) {
    if ($this->_mode && !$this->_isPaidEvent) {
      CRM_Core_Error::statusBounce(ts('Selected Event is not Paid Event '));
    }
    $participantStatus = CRM_Event_PseudoConstant::participantStatus();
    // set the contact, when contact is selected
    if (!empty($params['contact_id'])) {
      $this->_contactID = $this->_contactId = $params['contact_id'];
    }

    if ($this->_id) {
      $params['id'] = $this->_id;
    }

    if ($this->_isPaidEvent) {
      $params = $this->preparePaidEventProcessing($params);
    }

    // @todo - stop assigning these - pass financial_trxnId in token context
    // and swap template to use tokens.
    $this->assign('credit_card_type', $this->getSubmittedValue('credit_card_type'));
    $this->assign('credit_card_number', CRM_Utils_System::mungeCreditCard($this->getSubmittedValue('credit_card_number')));
    $amountOwed = NULL;
    if (isset($params['amount'])) {
      $amountOwed = $params['amount'];
      unset($params['amount']);
    }
    $params['contact_id'] = $this->_contactId;

    $now = date('YmdHis');

    if ($this->_mode) {
      $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($this->getSubmittedValue('payment_processor_id'),
        $this->_mode
      );
      $fields = [];

      // set email for primary location.
      $fields['email-Primary'] = 1;
      $params['email-Primary'] = $params["email-{$this->_bltID}"] = $this->getContactValue('email_primary.email');

      // now set the values for the billing location.
      foreach ($this->_fields as $name => $dontCare) {
        $fields[$name] = 1;
      }

      // also add location name to the array
      $params["address_name-{$this->_bltID}"]
        = ($params['billing_first_name'] ?? '') . ' ' .
        ($params['billing_middle_name'] ?? '') . ' ' .
         ($params['billing_last_name'] ?? '');

      $params["address_name-{$this->_bltID}"] = trim($params["address_name-{$this->_bltID}"]);
      $fields["address_name-{$this->_bltID}"] = 1;
      $fields["email-{$this->_bltID}"] = 1;
      $ctype = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactId, 'contact_type');

      $nameFields = ['first_name', 'middle_name', 'last_name'];

      foreach ($nameFields as $name) {
        $fields[$name] = 1;
        if (array_key_exists("billing_$name", $params)) {
          $params[$name] = $params["billing_{$name}"];
          $params['preserveDBName'] = TRUE;
        }
      }
      $contactID = CRM_Contact_BAO_Contact::createProfileContact($params, $fields, $this->_contactId, NULL, NULL, $ctype);
    }

    //do cleanup line  items if participant edit the Event Fee.
    if (($this->getLineItems() || !isset($params['proceSetId'])) && !$this->_paymentId && $this->_id) {
      CRM_Price_BAO_LineItem::deleteLineItems($this->_id, 'civicrm_participant');
    }
    $participants = [];
    $contactIDS = $this->_contactIds ?: [$this->getContactID()];
    foreach ($contactIDS as $contactID) {
      $participants[] = $this->addParticipant($contactID);
    }
    if ($this->_mode) {
      // add all the additional payment params we need
      $paymentParams = $this->prepareParamsForPaymentProcessor($this->getSubmittedValues());

      // at this point we've created a contact and stored its address etc
      // all the payment processors expect the name and address to be in the
      // so we copy stuff over to first_name etc.
      if ($this->getSubmittedValue('send_receipt')) {
        $paymentParams['email'] = $this->getContactValue('email_primary.email');
      }

      // The only reason for merging in the 'contact_id' rather than ensuring it is set
      // is that this patch is being done around the time of the stable release
      // so more conservative approach is called for.
      // In fact the use of $params and $this->_params & $this->_contactId vs $contactID
      // needs rationalising.
      $mapParams = array_merge(['contact_id' => $contactID], $this->getSubmittedValues());
      CRM_Core_Payment_Form::mapParams(NULL, $mapParams, $paymentParams, TRUE);

      $payment = $this->_paymentProcessor['object'];
      $payment->setBackOffice(TRUE);
      // CRM-15622: fix for incorrect contribution.fee_amount
      $paymentParams['fee_amount'] = NULL;
      $paymentParams['description'] = $this->getSourceText();
      $paymentParams['amount'] = $this->order->getTotalAmount();
      try {
        $paymentParams['invoiceID'] = $this->getInvoiceID();
        $paymentParams['currency'] = $this->getCurrency();
        $result = $payment->doPayment($paymentParams);
      }
      catch (\Civi\Payment\Exception\PaymentProcessorException $e) {
        // @todo un comment the following line out when we are creating a contribution before we get to this point
        // see dev/financial#53 about ensuring we create a pending contribution before we try processing payment
        // CRM_Contribute_BAO_Contribution::failPayment($contributionID);
        CRM_Core_Session::singleton()->setStatus($e->getMessage());
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view/participant',
          "reset=1&action=add&cid={$this->_contactId}&context=participant&mode={$this->_mode}"
        ));
      }

      //add contribution record
      $contributions[] = $contribution = $this->processContribution(
        $result, $contactID,
        FALSE,
        $this->_paymentProcessor
      );

      // Add participant payment
      $participantPaymentParams = [
        'participant_id' => $participants[0]->id,
        'contribution_id' => $contribution->id,
      ];
      civicrm_api3('ParticipantPayment', 'create', $participantPaymentParams);

      $this->_contactIds[] = $this->_contactId;
    }
    else {
      if ($this->_single) {
        // Still needed?
        $this->_contactIds[] = $this->_contactId;
      }

      $contributions = [];
      if (!empty($params['record_contribution'])) {
        $contributionParams = [
          'skipLineItem' => 1,
          'skipCleanMoney' => TRUE,
          'total_amount' => $this->getSubmittedValue('total_amount'),
          'revenue_recognition_date' => $this->getRevenueRecognitionDate(),
          'source' => $this->getSourceText(),
          'non_deductible_amount' => 'null',
          'financial_type_id' => $this->getSubmittedValue('financial_type_id'),
          'payment_instrument_id' => $this->getSubmittedValue('payment_instrument_id'),
          'trxn_id' => $this->getSubmittedValue('trxn_id'),
          'contribution_status_id' => $this->getSubmittedValue('contribution_status_id'),
          'check_number' => $this->getSubmittedValue('check_number'),
          'campaign_id' => $this->getSubmittedValue('campaign_id'),
          'pan_truncation' => $this->getSubmittedValue('pan_truncation'),
          'card_type_id' => $this->getSubmittedValue('card_type_id'),
          'receive_date' => $this->getSubmittedValue('receive_date') ?: $now,
        ];
        if (!empty($params['id'])) {
          if ($this->_onlinePendingContributionId) {
            $contributionParams['id'] = $this->_onlinePendingContributionId;
          }
          else {
            $contributionParams['id'] = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment',
              $params['id'],
              'contribution_id',
              'participant_id'
            );
          }
        }
        unset($params['note']);
        $contributionParams['currency'] = $this->getCurrency();
        $contributionParams['contact_id'] = $this->_contactID;

        if ($this->_id) {
          $contributionParams['contribution_mode'] = 'participant';
          $contributionParams['participant_id'] = $this->_id;
        }
        // Set is_pay_later flag for back-office offline Pending status contributions
        if ($contributionParams['contribution_status_id'] == CRM_Core_PseudoConstant::getKey('CRM_Contribute_DAO_Contribution', 'contribution_status_id', 'Pending')) {
          $contributionParams['is_pay_later'] = 1;
        }
        elseif ($contributionParams['contribution_status_id'] == CRM_Core_PseudoConstant::getKey('CRM_Contribute_DAO_Contribution', 'contribution_status_id', 'Completed')) {
          $contributionParams['is_pay_later'] = 0;
        }

        if ($params['status_id'] == array_search('Partially paid', $participantStatus)) {
          if (!$amountOwed && $this->_action & CRM_Core_Action::UPDATE) {
            $amountOwed = $params['fee_amount'];
          }

          // if multiple participants are link, consider contribution total amount as the amount Owed
          if ($this->_id && CRM_Event_BAO_Participant::isPrimaryParticipant($this->_id)) {
            $amountOwed = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution',
              $contributionParams['id'],
              'total_amount'
            );
          }

          // CRM-13964 partial payment
          if ($amountOwed > $params['total_amount']) {
            // the owed amount
            $contributionParams['total_amount'] = $amountOwed;
            $contributionParams['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending');
            $this->storePaymentCreateParams($params);
          }
        }

        if (!empty($params['tax_amount'])) {
          $contributionParams['tax_amount'] = $params['tax_amount'];
        }

        if ($this->_single) {
          $contributions[] = CRM_Contribute_BAO_Contribution::create($contributionParams);
        }
        else {
          foreach ($this->_contactIds as $contactID) {
            $contributionParams['contact_id'] = $contactID;
            $contributions[] = CRM_Contribute_BAO_Contribution::create($contributionParams);
          }
        }

        // Insert payment record for this participant
        if (empty($contributionParams['id'])) {
          foreach ($this->_contactIds as $num => $contactID) {
            $participantPaymentParams = [
              'participant_id' => $participants[$num]->id,
              'contribution_id' => $contributions[$num]->id,
            ];
            civicrm_api3('ParticipantPayment', 'create', $participantPaymentParams);
          }
        }

        // CRM-11124
        if ($this->getSubmittedValue('discount_id')) {
          CRM_Event_BAO_Participant::createDiscountTrxn(
            $this->_eventId,
            $contributionParams,
            '',
            CRM_Price_BAO_PriceSet::parseFirstPriceSetValueIDFromParams($this->getSubmittedValues())
          );
        }
      }
    }

    // also store lineitem stuff here
    if ((($this->getLineItems() && $this->_action & CRM_Core_Action::ADD) ||
      ($this->getLineItems() && CRM_Core_Action::UPDATE && !$this->_paymentId))
    ) {
      foreach ($this->_contactIds as $num => $contactID) {
        $lineItem = [$this->getPriceSetID() => $this->getLineItems()];
        CRM_Price_BAO_LineItem::processPriceSet($participants[$num]->id, $lineItem, $contributions[$num] ?? NULL, 'civicrm_participant');
      }
      foreach ($contributions as $contribution) {
        if (!empty($this->getCreatePaymentParams())) {
          civicrm_api3('Payment', 'create', array_merge(['contribution_id' => $contribution->id], $this->getCreatePaymentParams()));
        }
      }
    }

    $updateStatusMsg = NULL;
    //send mail when participant status changed, CRM-4326
    if ($this->_id && $this->_statusId &&
      $this->_statusId != ($params['status_id'] ?? NULL) && !empty($params['is_notify'])
    ) {

      $updateStatusMsg = CRM_Event_BAO_Participant::updateStatusMessage($this->_id,
        $params['status_id'],
        $this->_statusId
      );
    }

    if (!empty($params['send_receipt'])) {
      $result = $this->sendReceipts($params, $participants);
    }

    return $this->getStatusMsg($params, $result['sent'] ?? 0, $result['not_sent'] ?? 0, (string) $updateStatusMsg);
  }

  /**
   * Set the various IDs relating to custom data types.
   *
   * @internal will be made protected once line item editor unit tests
   * no longer call it.
   */
  public function setCustomDataTypes(): void {
    $this->assign('roleCustomDataTypeID', $this->getExtendsEntityColumnID('ParticipantRole'));
    $this->assign('eventNameCustomDataTypeID', $this->getExtendsEntityColumnID('ParticipantEventName'));
    $this->assign('eventTypeCustomDataTypeID', $this->getExtendsEntityColumnID('ParticipantEventType'));
  }

  /**
   * Get the relevant mapping for civicrm_custom_group.extends_entity_column_value.
   *
   * @param string $type
   *
   * @return int|null
   */
  private function getExtendsEntityColumnID(string $type): ?int {
    foreach (CRM_Core_BAO_CustomGroup::getExtendsEntityColumnIdOptions() as $item) {
      if ($item['name'] === $type) {
        return (int) $item['id'];
      }
    }
    // Should not be reachable but maybe people disable them?
    return NULL;
  }

  /**
   * Get status message
   *
   * @param array $params
   * @param int $numberSent
   * @param int $numberNotSent
   * @param string $updateStatusMsg
   *
   * @return string
   */
  protected function getStatusMsg(array $params, int $numberSent, int $numberNotSent, string $updateStatusMsg): string {
    $statusMsg = '';
    if (($this->_action & CRM_Core_Action::UPDATE)) {
      $statusMsg = ts('Event registration information for %1 has been updated.', [1 => $this->getContactValue('display_name')]);
      if (!empty($params['send_receipt']) && $numberSent) {
        $statusMsg .= ' ' . ts('A confirmation email has been sent to %1', [1 => $this->getContactValue('email_primary.email')]);
      }

      if ($updateStatusMsg) {
        $statusMsg = "{$statusMsg} {$updateStatusMsg}";
      }
    }
    elseif ($this->_action & CRM_Core_Action::ADD) {
      $statusMsg = ts('Event registration for %1 has been added.', [1 => $this->getContactValue('display_name')]);
      if (!empty($params['send_receipt']) && $numberSent) {
        $statusMsg .= ' ' . ts('A confirmation email has been sent to %1.', [1 => $this->getContactValue('email_primary.email')]);
      }
    }
    return $statusMsg;
  }

  /**
   * Build the form object.
   *
   * @internal - this will be made protected, once some notice is provided to lineItem
   * edit extension which calls it form tests.
   *
   * @param \CRM_Event_Form_Participant $form
   *
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public function buildEventFeeForm($form) {
    //as when call come from register.php
    if (!$form->_eventId) {
      $form->_eventId = CRM_Utils_Request::retrieve('eventId', 'Positive', $form);
    }

    $form->_pId = CRM_Utils_Request::retrieve('participantId', 'Positive', $form);
    $form->_discountId = CRM_Utils_Request::retrieve('discountId', 'Positive', $form);

    if ($form->_eventId) {
      $form->_isPaidEvent = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $form->_eventId, 'is_monetary');
      if ($form->_isPaidEvent) {
        $form->addElement('hidden', 'hidden_feeblock', 1);
      }
      if ($this->getEventValue('max_participants') !== NULL) {
        $eventfullMsg = CRM_Event_BAO_Participant::eventFullMessage($form->_eventId, $this->getParticipantID());
      }
      $form->addElement('hidden', 'hidden_eventFullMsg', $eventfullMsg ?? NULL, ['id' => 'hidden_eventFullMsg']);
    }

    if ($form->_isPaidEvent) {
      $params = ['id' => $form->_eventId];
      CRM_Event_BAO_Event::retrieve($params, $event);

      //retrieve custom information
      $this->_values = [];
      $this->_values['line_items'] = CRM_Price_BAO_LineItem::getLineItems($this->_id, 'participant');
      $this->initEventFee($this->getPriceSetID());
      if ($form->_context === 'standalone' || $form->_context === 'participant') {
        $discountedEvent = CRM_Core_BAO_Discount::getOptionGroup($event['id'], 'civicrm_event');
        if (is_array($discountedEvent)) {
          foreach ($discountedEvent as $key => $discountedPriceSetID) {
            $discountedPriceSet = CRM_Price_BAO_PriceSet::getSetDetail($discountedPriceSetID);
            $discountedPriceSet = $discountedPriceSet[$discountedPriceSetID] ?? NULL;
            $form->_values['discount'][$key] = $discountedPriceSet['fields'] ?? NULL;
            $fieldID = key($form->_values['discount'][$key]);
            // @todo  - this may be unused.
            $form->_values['discount'][$key][$fieldID]['name'] = CRM_Core_DAO::getFieldValue(
              'CRM_Price_DAO_PriceSet',
              $discountedPriceSetID,
              'title'
            );
          }
        }
      }
      //if payment done, no need to build the fee block.
      if (!empty($form->_paymentId)) {
        //fix to display line item in update mode.
        $form->assign('priceSet', $form->_priceSet ?? NULL);
      }
      else {
        $this->buildAmount();
      }
      $discounts = [];
      if (!empty($form->_values['discount'])) {
        foreach ($form->_values['discount'] as $key => $value) {
          $value = current($value);
          $discounts[$key] = $value['name'];
        }

        $form->add('select', 'discount_id',
          ts('Discount Set'),
          [
            0 => ts('- select -'),
          ] + $discounts,
          FALSE,
          ['class' => "crm-select2"]
        );
      }

      CRM_Core_Payment_Form::buildPaymentForm($form, $form->_paymentProcessor, FALSE, TRUE, self::getDefaultPaymentInstrumentId());
      if (!$form->_mode) {
        $form->addElement('checkbox', 'record_contribution', ts('Record Payment?'), NULL,
          ['onclick' => "return showHideByValue('record_contribution','','payment_information','table-row','radio',false);"]
        );
        $financialTypes = CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'create');

        $form->add('select', 'financial_type_id',
          ts('Financial Type'),
          ['' => ts('- select -')] + $financialTypes
        );

        $form->add('datepicker', 'receive_date', ts('Contribution Date'), [], FALSE, ['time' => TRUE]);

        $form->add('select', 'payment_instrument_id',
          ts('Payment Method'),
          ['' => ts('- select -')] + CRM_Contribute_BAO_Contribution::buildOptions('payment_instrument_id', 'create'),
          FALSE, ['onChange' => "return showHideByValue('payment_instrument_id','4','checkNumber','table-row','select',false);"]
        );
        // don't show transaction id in batch update mode
        $path = CRM_Utils_System::currentPath();
        $form->assign('showTransactionId', FALSE);
        if ($path !== 'civicrm/contact/search/basic') {
          $form->add('text', 'trxn_id', ts('Transaction ID'));
          $form->addRule('trxn_id', ts('Transaction ID already exists in Database.'),
            'objectExists', ['CRM_Contribute_DAO_Contribution', $form->_eventId, 'trxn_id']
          );
          $form->assign('showTransactionId', TRUE);
        }

        $form->add('select', 'contribution_status_id',
          ts('Payment Status'), CRM_Contribute_BAO_Contribution_Utils::getPendingAndCompleteStatuses()
        );

        $form->add('text', 'check_number', ts('Check Number'),
          CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution', 'check_number')
        );

        $form->add('text', 'total_amount', ts('Amount'),
          CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution', 'total_amount')
        );
      }
    }
    else {
      $form->add('text', 'amount', ts('Event Fee(s)'));
    }
    $form->assign('onlinePendingContributionId', $form->get('onlinePendingContributionId'));

    $form->assign('paid', $form->_isPaidEvent ?? NULL);

    $form->addElement('checkbox',
      'send_receipt',
      ts('Send Confirmation?'), NULL,
      ['onclick' => "showHideByValue('send_receipt','','notice','table-row','radio',false); showHideByValue('send_receipt','','from-email','table-row','radio',false);"]
    );

    $form->add('select', 'from_email_address', ts('Receipt From'), $form->getAvailableFromEmails()['from_email_id']);

    $form->add('wysiwyg', 'receipt_text', ts('Confirmation Message'));

    // Retrieve the name and email of the contact - form will be the TO for receipt email ( only if context is not standalone)
    if ($form->_context !== 'standalone') {
      if ($form->getContactID()) {
        // @todo - this is likely unneeded now.
        $form->assign('email', $this->getContactValue('email_primary.email'));
      }
      else {
        //show email block for batch update for event
        $form->assign('batchEmail', TRUE);
      }
    }

    $mailingInfo = Civi::settings()->get('mailing_backend');
    $form->assign('outBound_option', $mailingInfo['outBound_option']);
    $form->assign('hasPayment', $form->_paymentId);
  }

  /**
   * Initiate event fee.
   *
   * @param int|null $priceSetId
   *   ID of the price set in use.
   *
   * @internal function has had several recent signature changes & is expected to be eventually removed.
   */
  private function initEventFee($priceSetId): void {
    if (!$priceSetId) {
      CRM_Core_Error::deprecatedWarning('this should not be reachable');
      return;
    }
    $this->_priceSet = $this->getOrder()->getPriceSetMetadata();
    $this->_priceSet['fields'] = $this->order->getPriceFieldsMetadata();
    $this->_values['fee'] = $this->getPriceFieldMetaData();
    $this->set('priceSet', $this->_priceSet);
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
    return $this->order->getPriceFieldsMetadata();
  }

  /**
   * Get the emails available for the from address.
   *
   * @return array
   */
  protected function getAvailableFromEmails(): array {
    return CRM_Event_BAO_Event::getFromEmailIds($this->getEventID());
  }

  /**
   * Extracted code relating to paid events.
   *
   * @param $params
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function preparePaidEventProcessing($params): array {
    if ($this->isPaymentOnExistingContribution()) {
      //re-enter the values for UPDATE mode
      $params['fee_level'] = $params['amount_level'] = $this->getParticipantValue('fee_level');
      $params['fee_amount'] = $this->getParticipantValue('fee_amount');
    }
    else {
      //lets carry currency, CRM-4453
      $params['fee_currency'] = $this->getCurrency();
      $params['fee_level'] = $this->getOrder()->getAmountLevel();
      $params['fee_amount'] = $this->getOrder()->getTotalAmount();
      $params['amount'] = $this->getOrder()->getTotalAmount();
    }

    return $params;
  }

  /**
   * @param $eventID
   * @param $participantRoles
   * @param $receiptText
   *
   * @throws \CRM_Core_Exception
   */
  protected function assignEventDetailsToTpl($eventID, $participantRoles, $receiptText): void {
    $this->assign('event', ['confirm_email_text' => $receiptText]);
  }

  /**
   * Get the currency for the event.
   *
   * @return string
   * @noinspection PhpDocMissingThrowsInspection
   */
  public function getCurrency() {
    return $this->getEventValue('currency') ?: \Civi::settings()->get('defaultCurrency');
  }

  /**
   * Process the contribution.
   *
   * @param array $result
   * @param int $contactID
   * @param bool $pending
   * @param array $paymentProcessor
   *
   * @return \CRM_Contribute_BAO_Contribution
   *
   * @throws \CRM_Core_Exception
   */
  protected function processContribution($result, $contactID,
    $pending = FALSE,
    $paymentProcessor = NULL
  ) {
    $transaction = new CRM_Core_Transaction();

    $now = date('YmdHis');

    $contribParams = [
      'contact_id' => $contactID,
      'financial_type_id' => $this->getEventValue('financial_type_id'),
      'receive_date' => $this->getSubmittedValue('receive_date') ?: $now,
      'total_amount' => $this->getOrder()->getTotalAmount(),
      'tax_amount' => $this->getOrder()->getTotalTaxAmount(),
      'amount_level' => $this->getOrder()->getAmountLevel(),
      'invoice_id' => $this->getInvoiceID(),
      'currency' => $this->getCurrency(),
      'source' => $this->getSourceText(),
      'is_pay_later' => FALSE,
      'campaign_id' => $this->getSubmittedValue('campaign_id'),
      'card_type_id' => $this->getSubmittedValue('card_type_id'),
      'pan_truncation' => substr($this->getSubmittedValue('credit_card_number'), -4),
    ];

    if ($paymentProcessor) {
      $contribParams['payment_instrument_id'] = $paymentProcessor['payment_instrument_id'];
      $contribParams['payment_processor'] = $paymentProcessor['id'];
    }

    if (!$pending && $result) {
      $contribParams += [
        'fee_amount' => $result['fee_amount'] ?? NULL,
        'trxn_id' => $result['trxn_id'],
      ];
    }

    $allStatuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $contribParams['contribution_status_id'] = array_search('Completed', $allStatuses);
    if ($pending) {
      $contribParams['contribution_status_id'] = array_search('Pending', $allStatuses);
    }

    $contribParams['is_test'] = $this->isTest();

    if (!empty($contribParams['invoice_id'])) {
      $contribParams['id'] = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution',
        $contribParams['invoice_id'],
        'id',
        'invoice_id'
      );
    }
    $contribParams['revenue_recognition_date'] = $this->getRevenueRecognitionDate();

    $contribParams['address_id'] = CRM_Contribute_BAO_Contribution::createAddress($this->getSubmittedValues());

    $contribParams['skipLineItem'] = 1;
    $contribParams['skipCleanMoney'] = 1;
    // create contribution record
    $contribution = CRM_Contribute_BAO_Contribution::add($contribParams);
    // CRM-11124
    CRM_Event_BAO_Participant::createDiscountTrxn($this->getEventID(), $contribParams, '', CRM_Price_BAO_PriceSet::parseFirstPriceSetValueIDFromParams($this->getSubmittedValues()));

    $transaction->commit();

    return $contribution;
  }

  /**
   * Process the participant.
   *
   * @param int $contactID
   *
   * @return \CRM_Event_BAO_Participant
   * @throws \CRM_Core_Exception
   */
  protected function addParticipant($contactID) {
    $transaction = new CRM_Core_Transaction();
    $participantParams = [
      'id' => $this->getParticipantID(),
      'contact_id' => $contactID,
      'event_id' => $this->getEventID(),
      'status_id' => $this->getSubmittedValue('status_id'),
      'role_id' => $this->getSubmittedValue('role_id'),
      'register_date' => $this->getSubmittedValue('register_date'),
      'source' => $this->getSourceText(),
      'is_pay_later' => FALSE,
      'fee_currency' => $this->getCurrency(),
      'campaign_id' => $this->getSubmittedValue('campaign_id'),
      'note' => $this->getSubmittedValue('note'),
      'is_test' => $this->isTest(),
    ];
    if (!$this->getParticipantID() || !$this->getContributionID()) {
      // For new registrations, or existing ones with no contribution,
      // fill in fee detail. For existing
      // registrations with a contribution the user will have the option to
      // change the fees via a different form.
      $order = $this->getOrder();
      if ($order) {
        $participantParams['fee_level'] = $order->getAmountLevel();
        $participantParams['fee_amount'] = $order->getTotalAmount();
      }
    }
    if ($this->getSubmittedValue('discount_id')) {
      $participantParams['discount_id'] = $this->getSubmittedValue('discount_id');
    }
    $participant = CRM_Event_BAO_Participant::create($participantParams);

    // Add custom data for participant
    $submittedValues = $this->getSubmittedValues();
    CRM_Core_BAO_CustomValueTable::postProcess($submittedValues,
      'civicrm_participant',
      $participant->id,
      'Participant'
    );
    $transaction->commit();
    $this->_id = $participant->id;
    return $participant;
  }

  /**
   * Is a payment being made on an existing contribution.
   *
   * Note
   * 1) ideally we should not permit this on this form! Perhaps we don't & this is just cruft.
   * 2) _paymentID is the contribution id.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  protected function isPaymentOnExistingContribution(): bool {
    return (bool) $this->getExistingContributionID();
  }

  /**
   * @return null|int
   * @throws \CRM_Core_Exception
   */
  protected function getExistingContributionID(): ?int {
    if (!$this->getParticipantID()) {
      return NULL;
    }
    if ($this->isDefined('ExistingContribution')) {
      return $this->lookup('ExistingContribution', 'id');
    }
    // CRM-12615 - Get payment information from the primary registration if relevant.
    $participantID = $this->getParticipantValue('registered_by_id') ?: $this->getParticipantID();
    $lineItem = LineItem::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_participant')
      ->addWhere('entity_id', '=', $participantID)
      ->addWhere('contribution_id', 'IS NOT NULL')
      ->execute()->first();
    if (empty($lineItem)) {
      return NULL;
    }
    $this->define('Contribution', 'ExistingContribution', ['id' => $lineItem['contribution_id']]);
    return $lineItem['contribution_id'];
  }

  /**
   * Get id of participant being edited.
   *
   * @return int|null
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * No exception is thrown as abort is not TRUE.
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  public function getParticipantID(): ?int {
    if ($this->_id === NULL) {
      $id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
      $this->_id = $id ? (int) $id : FALSE;
    }
    return $this->_id ?: NULL;
  }

  /**
   * Get the value for the revenue recognition date field.
   *
   * @return string
   *
   * @throws \CRM_Core_Exception
   */
  protected function getRevenueRecognitionDate() {
    if (Civi::settings()->get('deferred_revenue_enabled')) {
      $eventStartDate = $this->getEventValue('start_date');
      if (strtotime($eventStartDate) > strtotime(date('Ymt'))) {
        return date('Ymd', strtotime($eventStartDate));
      }
    }
    return '';
  }

  /**
   * Store the parameters to create a payment, if appropriate, on the form.
   *
   * @param array $params
   *   Params as submitted.
   */
  protected function storePaymentCreateParams(array $params): void {
    if ('Completed' === CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $params['contribution_status_id'])) {
      $this->setCreatePaymentParams([
        'total_amount' => $params['total_amount'],
        'is_send_contribution_notification' => FALSE,
        'payment_instrument_id' => $params['payment_instrument_id'],
        'trxn_date' => $params['receive_date'] ?: date('Y-m-d'),
        'trxn_id' => $params['trxn_id'],
        'pan_truncation' => $params['pan_truncation'] ?? '',
        'card_type_id' => $params['card_type_id'] ?? '',
        'check_number' => $params['check_number'] ?? '',
        'skipCleanMoney' => TRUE,
      ]);
    }
  }

  /**
   * Get the event fee info for given participant ids
   * either from line item table / participant table.
   *
   * @param array $participantIds
   *   Participant ids.
   * @param bool $hasLineItems
   *   Do fetch from line items.
   *
   * @return array
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function getFeeDetails($participantIds, $hasLineItems = FALSE) {
    $feeDetails = [];
    if (!is_array($participantIds) || empty($participantIds)) {
      return $feeDetails;
    }

    $select = '
SELECT  participant.id         as id,
        participant.fee_level  as fee_level,
        participant.fee_amount as fee_amount';
    $from = 'FROM civicrm_participant participant';
    if ($hasLineItems) {
      $select .= ' ,
lineItem.id          as lineId,
lineItem.label       as label,
lineItem.qty         as qty,
lineItem.unit_price  as unit_price,
lineItem.line_total  as line_total,
field.label          as field_title,
field.html_type      as html_type,
field.id             as price_field_id,
value.id             as price_field_value_id,
value.description    as description,
IF( value.count, value.count, 0 ) as participant_count';
      $from .= "
INNER JOIN civicrm_line_item lineItem      ON ( lineItem.entity_table = 'civicrm_participant'
                                                AND lineItem.entity_id = participant.id )
INNER JOIN civicrm_price_field field ON ( field.id = lineItem.price_field_id )
INNER JOIN civicrm_price_field_value value ON ( value.id = lineItem.price_field_value_id )
";
    }
    $where = 'WHERE participant.id IN ( ' . implode(', ', $participantIds) . ' )';
    $query = "$select $from  $where";

    $feeInfo = CRM_Core_DAO::executeQuery($query);
    $feeProperties = ['fee_level', 'fee_amount'];
    $lineProperties = [
      'lineId',
      'label',
      'qty',
      'unit_price',
      'line_total',
      'field_title',
      'html_type',
      'price_field_id',
      'participant_count',
      'price_field_value_id',
      'description',
    ];
    while ($feeInfo->fetch()) {
      if ($hasLineItems) {
        foreach ($lineProperties as $property) {
          $feeDetails[$feeInfo->id][$feeInfo->lineId][$property] = $feeInfo->$property;
        }
      }
      else {
        foreach ($feeProperties as $property) {
          $feeDetails[$feeInfo->id][$property] = $feeInfo->$property;
        }
      }
    }

    return $feeDetails;
  }

  /**
   * Assign the url path to the template.
   */
  protected function assignUrlPath() {
    $this->assign('urlPath', 'civicrm/contact/view/participant');
    $this->assign('urlPathVar', NULL);
    if (!$this->_id && !$this->_contactId) {
      $breadCrumbs = [
        [
          'title' => ts('CiviEvent Dashboard'),
          'url' => CRM_Utils_System::url('civicrm/event', 'reset=1'),
        ],
      ];

      CRM_Utils_System::appendBreadCrumb($breadCrumbs);
    }
    else {
      $this->assign('id', $this->_id);
      $this->assign('contact_id', $this->_contactId);
    }
  }

  /**
   * @param $params
   * @param array $participants
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @throws \Brick\Money\Exception\UnknownCurrencyException
   */
  protected function sendReceipts($params, array $participants): array {
    $sent = [];
    $notSent = [];
    $this->assignEventDetailsToTpl($params['event_id'], $params['role_id'] ?? NULL, $params['receipt_text'] ?? NULL);

    if ($this->_mode) {
      $valuesForForm = CRM_Contribute_Form_AbstractEditPayment::formatCreditCardDetails($params);
      $this->assignVariables($valuesForForm, ['credit_card_exp_date', 'credit_card_type', 'credit_card_number']);
    }

    $fromEmails = CRM_Event_BAO_Event::getFromEmailIds($this->getEventID());
    foreach ($participants as $num => $participant) {
      $participantID = $participant->id;
      $contactID = $participant->contact_id;
      $key = 'contact_' . $contactID;

      $this->define('Contact', $key, ['id' => $contactID]);
      if (!$this->lookup($key, 'email_primary.email') || $this->lookup($key, 'do_not_email')) {
        // try to send emails only if email id is present
        // and the do-not-email option is not checked for that contact
        $notSent[] = $contactID;
        continue;
      }
      $waitStatus = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Waiting'");
      $waitingStatus = $waitStatus[$params['status_id']] ?? NULL;
      if ($waitingStatus) {
        $this->assign('isOnWaitlist', TRUE);
      }

      $contributionID = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment',
        $participantID, 'contribution_id', 'participant_id'
      );

      $sendTemplateParams = [
        'workflow' => 'event_offline_receipt',
        'contactId' => $contactID,
        'isTest' => $this->isTest(),
        'PDFFilename' => ts('confirmation') . '.pdf',
        'modelProps' => [
          'participantID' => $participantID,
          'userEnteredHTML' => $this->getSubmittedValue('receipt_text'),
          'eventID' => $params['event_id'],
          'contributionID' => $contributionID,
        ],
      ];

      $sendTemplateParams['from'] = $params['from_email_address'];
      $sendTemplateParams['toName'] = $this->lookup($key, 'display_name');
      $sendTemplateParams['toEmail'] = $this->lookup($key, 'email_primary.email');
      $sendTemplateParams['cc'] = $fromEmails['cc'] ?? NULL;
      $sendTemplateParams['bcc'] = $fromEmails['bcc'] ?? NULL;

      //send email with pdf invoice
      if (Civi::settings()->get('invoice_is_email_pdf')) {
        $sendTemplateParams['isEmailPdf'] = TRUE;
        $sendTemplateParams['contributionId'] = $contributionID;
      }
      [$mailSent] = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
      if ($mailSent) {
        if ($contributionID) {
          Contribution::update(FALSE)
            ->addWhere('id', '=', $contributionID)
            ->setValues(['receipt_date' => 'now'])
            ->execute();
        }
        $sent[] = $contactID;
        $participant->details = $this->getSubmittedValue('receipt_text');
        CRM_Activity_BAO_Activity::addActivity($participant, 'Email');
      }
      else {
        $notSent[] = $contactID;
      }
    }
    return ['sent' => count($sent), 'not_sent' => count($notSent)];
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
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getDiscountID(): ?int {
    if ($this->getSubmittedValue('discount_id')) {
      return $this->getSubmittedValue('discount_id');
    }
    if ($this->_discountId === NULL) {
      if ($this->getParticipantID()) {
        $this->_discountId = (int) CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $this->getParticipantID(), 'discount_id');
      }
      else {
        $this->_discountId = (int) CRM_Core_BAO_Discount::findSet($this->getEventID(), 'civicrm_event');
      }
    }
    return $this->_discountId ?: NULL;
  }

  /**
   * Get the Price Set ID in use.
   *
   * @return int|null
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
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
   * Is the form being accessed in overload fees mode.
   *
   * Overload fees mode is when we are accessing the same form for a different
   * purpose - to load the fees via ajax. We have historically fixed this for
   * some forms by creating a new form class to move the functionality to and
   * updating the path to call that (e.g CRM_Financial_Form_Payment was historically
   * split in this way).
   *
   * This is much cleaner but the trap to be
   * aware of is that the fields must be added to the quick form. It does require
   * a bit of UI testing to do this. For now, adding comment...
   *
   * @return bool
   */
  protected function isOverloadFeesMode(): bool {
    return (bool) ($_GET['eventId'] ?? NULL);
  }

  /**
   * Is the form being submitted in test mode.
   *
   * @api this function is supported for external use.
   *
   * @return bool
   */
  public function isTest(): bool {
    return $this->_mode === 'test';
  }

  /**
   * Get the contact ID in use.
   *
   * Ideally override this as appropriate to the form.
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocSignatureIsNotCompleteInspection
   */
  public function getContactID():?int {
    // Always set it back to the submitted value if there is one - this is to prevent it being set in
    // proProcess & then ignoring the actual submitted value in post-process.
    if ($this->getSubmittedValue('contact_id')) {
      $this->_contactID = $this->getSubmittedValue('contact_id');
    }
    if ($this->_contactID === NULL) {
      $contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
      if (!$contactID && $this->getParticipantID()) {
        $contactID = $this->getParticipantValue('contact_id');
      }
      $this->_contactID = $contactID ? (int) $contactID : NULL;
    }
    return $this->_contactID;
  }

  /**
   * Get the text for the participant & contribution source fields.
   *
   * @throws \CRM_Core_Exception
   */
  private function getSourceText(): string {
    $maxLength = CRM_Event_DAO_Participant::fields()['participant_source']['maxlength'];
    if ($this->getSubmittedValue('source')) {
      return CRM_Utils_String::ellipsify($this->getSubmittedValue('source'), $maxLength);
    }
    return CRM_Utils_String::ellipsify(ts('Offline Registration for Event: %2 by: %1', [
      1 => CRM_Core_Session::singleton()->getLoggedInContactDisplayName(),
      2 => $this->getEventValue('title'),
    ]), $maxLength);
  }

  /**
   * Get the order object, if the event is paid.
   *
   * @return \CRM_Financial_BAO_Order|null
   * @throws \CRM_Core_Exception
   */
  protected function getOrder(): ?CRM_Financial_BAO_Order {
    if (!$this->order && $this->getPriceSetID()) {
      $this->initializeOrder();
    }
    return $this->order;
  }

  /**
   * Instantiate the order object.
   *
   * @throws \CRM_Core_Exception
   */
  protected function initializeOrder(): void {
    $this->order = new CRM_Financial_BAO_Order();
    $this->order->setPriceSetID($this->getPriceSetID());
    $this->order->setIsExcludeExpiredFields(FALSE);
    if ($this->getExistingContributionID()) {
      $this->order->setTemplateContributionID($this->getExistingContributionID());
    }
    if ($this->getSubmittedValue('financial_type_id') && $this->isQuickConfig()) {
      $this->order->setOverrideFinancialTypeID((int) $this->getSubmittedValue('financial_type_id'));
    }
    // We set the purchase amount to be the paid amount for quick config payments,
    // unless the participant status is partially paid.
    if ($this->isQuickConfig()
      && $this->getSubmittedValue('total_amount')
      && CRM_Core_PseudoConstant::getName('CRM_Event_BAO_Participant', 'status_id', $this->getSubmittedValue('status_id')) !== 'Partially paid'
    ) {
      $this->order->setOverrideTotalAmount($this->getSubmittedValue('total_amount'));
    }
    $this->order->setForm($this);
    foreach ($this->order->getPriceFieldsMetaData() as $priceField) {
      if ($priceField['html_type'] === 'Text') {
        $this->submittableMoneyFields[] = 'price_' . $priceField['id'];
      }
    }
  }

  /**
   * Get the selected line items.
   *
   * @api Supported for external used.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getLineItems(): array {
    return $this->getOrder() ? $this->getOrder()->getLineItems() : [];
  }

  /**
   * @return string
   */
  public function getInvoiceID(): string {
    if (!$this->invoiceID) {
      $this->invoiceID = bin2hex(random_bytes(16));
    }
    return $this->invoiceID;
  }

  /**
   * Build the radio/text form elements for the amount field
   *
   * @internal function is not currently called by any extentions in our civi
   * 'universe' and is not supported for such use. Signature has changed & will
   * change again.
   */
  private function buildAmount(): void {
    //build the priceset fields.
    // This is probably not required now - normally loaded from event ....
    $this->add('hidden', 'priceSetId', $this->getPriceSetID());

    foreach ($this->getPriceFieldMetaData() as $field) {
      // public AND admin visibility fields are included for back-office registration and back-office change selections
      $fieldId = $field['id'];
      $elementName = 'price_' . $fieldId;

      $isRequire = $field['is_required'] ?? NULL;

      //user might modified w/ hook.
      $options = $field['options'] ?? NULL;

      if (!is_array($options)) {
        continue;
      }

      //soft suppress required rule when option is full.
      if (!empty($options)) {
        //build the element.
        CRM_Price_BAO_PriceField::addQuickFormElement($this,
          $elementName,
          $fieldId,
          FALSE,
          $isRequire,
          NULL,
          $options
        );
      }
    }
    $this->assign('priceSet', $this->_priceSet);
  }

}
