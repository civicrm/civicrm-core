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
 * This class generates form components to allow an Event to be cancelled or transferred from an email link
 *
 */
class CRM_Event_Form_SelfSvcUpdate extends CRM_Core_Form {
  /**
   * particpant id
   *
   * @var string
   *
   */
  protected $_participant_id;
  /**
   * contact id
   *
   * @var string
   *
   */
  protected $_contact_id;
  /**
   * name of the particpant
   *
   * @var string
   *
   */
  protected $_contact_name;
  /**
   * email of participant
   *
   * @var string
   */
  protected $_contact_email;
  /**
   * event to be cancelled/transferred
   *
   * @var string
   */
  protected $_event_id;
  /**
   * event title
   *
   * @var string
   */
  protected $_event_title;
  /**
   * event title
   *
   * @var string
   */
  protected $_event_start_date;
  /**
   * action
   *
   * @var string
   */
  public $_action;
  /**
   * participant object
   *
   * @var string
   */
  protected $_participant = [];
  /**
   * particpant values
   *
   * @var string
   */
  protected $_part_values;
  /**
   * details of event registration values
   *
   * @var array
   */
  protected $_details = [];
  /**
   * Is backoffice form?
   *
   * @var bool
   */
  protected $isBackoffice = FALSE;

  /**
   * Set variables up before form is built based on participant ID from URL
   *
   * @return void
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    $config = CRM_Core_Config::singleton();
    $session = CRM_Core_Session::singleton();
    $this->_userContext = $session->readUserContext();
    $participant = $values = [];
    $this->_participant_id = CRM_Utils_Request::retrieve('pid', 'Positive', $this, FALSE, NULL, 'REQUEST');
    $this->_userChecksum = CRM_Utils_Request::retrieve('cs', 'String', $this, FALSE, NULL, 'REQUEST');
    $this->isBackoffice = CRM_Utils_Request::retrieve('is_backoffice', 'String', $this, FALSE, NULL, 'REQUEST');
    $params = ['id' => $this->_participant_id];
    $this->_participant = CRM_Event_BAO_Participant::getValues($params, $values, $participant);
    $this->_part_values = $values[$this->_participant_id];
    $this->set('values', $this->_part_values);
    //fetch Event by event_id, verify that this event can still be xferred/cancelled
    $this->_event_id = $this->_part_values['event_id'];
    $url = CRM_Utils_System::url('civicrm/event/info', "reset=1&id={$this->_event_id}");
    $this->_contact_id = $this->_part_values['participant_contact_id'];
    $validUser = CRM_Contact_BAO_Contact_Utils::validChecksum($this->_contact_id, $this->_userChecksum);
    if (!$validUser && !CRM_Core_Permission::check('edit all events')) {
      CRM_Core_Error::statusBounce(ts('You do not have sufficient permission to transfer/cancel this participant.'), $url);
    }
    $this->assign('action', $this->_action);
    if ($this->_participant_id) {
      $this->assign('participantId', $this->_participant_id);
    }

    $daoName = 'title';
    $this->_event_title = CRM_Event_BAO_Event::getFieldValue('CRM_Event_DAO_Event', $this->_event_id, $daoName);
    $daoName = 'start_date';
    $this->_event_start_date = CRM_Event_BAO_Event::getFieldValue('CRM_Event_DAO_Event', $this->_event_id, $daoName);
    list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contact_id);
    $this->_contact_name = $displayName;
    $this->_contact_email = $email;

    $details = CRM_Event_BAO_Participant::participantDetails($this->_participant_id);
    $contributionId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment', $this->_participant_id, 'contribution_id', 'participant_id');
    $this->assign('contributionId', $contributionId);
    $selfServiceDetails = CRM_Event_BAO_Participant::getSelfServiceEligibility($this->_participant_id, $url, $this->isBackoffice);
    $details = array_merge($details, $selfServiceDetails);
    $this->assign('details', $details);
    $this->selfsvcupdateUrl = CRM_Utils_System::url('civicrm/event/selfsvcupdate', "reset=1&id={$this->_participant_id}&id=0");
    $this->selfsvcupdateText = ts('Update');
    $this->selfsvcupdateButtonText = ts('Update');
    // Based on those ids retrieve event and verify it is eligible
    // for self update (event.start_date > today, event can be 'self_updated'
    // retrieve contact name and email, and let user verify his/her identity
  }

  /**
   * buildQuickForm -populate input variables for source Event
   * to cancel or transfer to another person
   *
   * return @void
   */
  public function buildQuickForm() {
    $this->add('select', 'action', ts('Transfer or Cancel Registration'), [ts('-select-'), ts('Transfer'), ts('Cancel')], TRUE);
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Submit'),
      ],
    ]);
    $this->addFormRule(['CRM_Event_Form_SelfSvcUpdate', 'formRule'], $this);
    parent::buildQuickForm();
  }

  /**
   * Set default values for contact
   *
   * return @void
   */
  public function setDefaultValues() {
    $this->_defaults = [];
    $this->_defaults['details'] = $this->_details;
    return $this->_defaults;
  }

  /**
   * Validate action input
   * @param array $fields
   *   Posted fields of the form.
   * @param $files
   * @param $self
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];
    if (empty($fields['action'])) {
      $errors['action'] = ts("Please select Transfer OR Cancel action.");
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process submit form - based on user selection of action
   * transfer or cancel the event
   *
   * return @void
   */
  public function postProcess() {
    //if selection is cancel, cancel this participant' registration, process refund
    //if transfer, process form to allow selection of transferree
    $params = $this->controller->exportValues($this->_name);
    $action = $params['action'];
    if ($action == "1") {
      $this->transferParticipant($params);
    }
    elseif ($action == "2") {
      $this->cancelParticipant($params);
    }
  }

  /**
   * Transfer to a new form, allowing selection of a new contact
   * based on email and name. The Event will be transferred to this new participant
   *
   * return @void
   */
  public function transferParticipant($params) {
    CRM_Utils_System::redirect(CRM_Utils_System::url(
      'civicrm/event/selfsvctransfer',
      [
        'reset' => 1,
        'action' => 'add',
        'pid' => $this->_participant_id,
        'cs' => $this->_userChecksum,
        'is_backoffice' => $this->isBackoffice,
      ]
    ));
  }

  /**
   * Cancel this participant and finish, send cancellation email. At this point no
   * auto-cancellation of payment is handled, so payment needs to be manually cancelled
   *
   * return @void
   *
   * @throws \CRM_Core_Exception
   */
  public function cancelParticipant($params) {
    //set participant record status to Cancelled, refund payment if possible
    // send email to participant and admin, and log Activity
    $value = [];
    $value['id'] = $this->_participant_id;
    $cancelledId = array_search('Cancelled',
    CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Negative'"));
    $value['status_id'] = $cancelledId;
    CRM_Event_BAO_Participant::create($value);
    $domainValues = [];
    $domain = CRM_Core_BAO_Domain::getDomain();
    $tokens = [
      'domain' =>
      [
        'name',
        'phone',
        'address',
        'email',
      ],
      'contact' => CRM_Core_SelectValues::contactTokens(),
    ];
    foreach ($tokens['domain'] as $token) {
      $domainValues[$token] = CRM_Utils_Token::getDomainTokenReplacement($token, $domain);
    }

    $participantRoles = CRM_Event_PseudoConstant::participantRole();
    $participantDetails = [];
    $query = "SELECT * FROM civicrm_participant WHERE id = {$this->_participant_id}";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $participantDetails[$dao->id] = [
        'id' => $dao->id,
        'role' => $participantRoles[$dao->role_id],
        'is_test' => $dao->is_test,
        'event_id' => $dao->event_id,
        'status_id' => $dao->status_id,
        'fee_amount' => $dao->fee_amount,
        'contact_id' => $dao->contact_id,
        'register_date' => $dao->register_date,
        'registered_by_id' => $dao->registered_by_id,
      ];
    }
    $eventDetails = [];
    $eventParams = ['id' => $this->_event_id];
    CRM_Event_BAO_Event::retrieve($eventParams, $eventDetails[$this->_event_id]);
    //get default participant role.
    $eventDetails[$this->_event_id]['participant_role'] = $participantRoles[$eventDetails[$this->_event_id]['default_role_id']] ?? NULL;
    //get the location info
    $locParams = ['entity_id' => $this->_event_id, 'entity_table' => 'civicrm_event'];
    $eventDetails[$this->_event_id]['location'] = CRM_Core_BAO_Location::getValues($locParams, TRUE);
    //get contact details
    $contactIds[$this->_contact_id] = $this->_contact_id;
    list($currentContactDetails) = CRM_Utils_Token::getTokenDetails($contactIds, NULL,
      FALSE, FALSE, NULL, [],
      'CRM_Event_BAO_Participant'
    );
    foreach ($currentContactDetails as $contactId => $contactValues) {
      $contactDetails[$this->_contact_id] = $contactValues;
    }
    //send a 'cancelled' email to user, and cc the event's cc_confirm email
    $mail = CRM_Event_BAO_Participant::sendTransitionParticipantMail($this->_participant_id,
      $participantDetails[$this->_participant_id],
      $eventDetails[$this->_event_id],
      $contactDetails[$this->_contact_id],
      $domainValues,
      "Cancelled",
      ""
    );
    $statusMsg = ts('Event registration information for %1 has been updated.', [1 => $this->_contact_name]);
    $statusMsg .= ' ' . ts('A cancellation email has been sent to %1.', [1 => $this->_contact_email]);
    CRM_Core_Session::setStatus($statusMsg, ts('Thanks'), 'success');
    if (!empty($this->isBackoffice)) {
      return;
    }
    $url = CRM_Utils_System::url('civicrm/event/info', "reset=1&id={$this->_event_id}&noFullMsg=true");
    CRM_Utils_System::redirect($url);
  }

}
