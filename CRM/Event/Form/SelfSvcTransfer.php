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
 * This class generates form components to transfer an Event to another participant
 *
 */
class CRM_Event_Form_SelfSvcTransfer extends CRM_Core_Form {
  /**
   * from participant id
   *
   * @var string
   *
   */
  protected $_from_participant_id;
  /**
   * from contact id
   *
   * @var string
   *
   */
  protected $_from_contact_id;
  /**
   * last name of the participant to transfer to
   *
   * @var string
   *
   */
  protected $_to_contact_last_name;
  /**
   * first name of the participant to transfer to
   *
   * @var string
   *
   */
  protected $_to_contact_first_name;
  /**
   * email of participant
   *
   *
   * @var string
   */
  protected $_to_contact_email;
  /**
   * _to_contact_id
   *
   * @var string
   */
  protected $_to_contact_id;
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
   * participant values
   *
   * @var string
   */
  protected $_part_values;
  /**
   * details
   *
   * @var array
   */
  protected $_details = [];
  /**
   * line items
   *
   * @var array
   */
  protected $_line_items = [];
  /**
   * contact_id
   *
   * @var int
   */
  protected $contact_id;

  /**
   * Is backoffice form?
   *
   * @var bool
   */
  protected $isBackoffice = FALSE;

  /**
   * Get source values for transfer based on participant id in URL. Line items will
   * be transferred to this participant - at this point no transaction changes processed
   *
   * return @void
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    $session = CRM_Core_Session::singleton();
    $this->_userContext = $session->readUserContext();
    $this->_from_participant_id = CRM_Utils_Request::retrieve('pid', 'Positive', $this, FALSE, NULL, 'REQUEST');
    $this->_userChecksum = CRM_Utils_Request::retrieve('cs', 'String', $this, FALSE, NULL, 'REQUEST');
    $this->isBackoffice = CRM_Utils_Request::retrieve('is_backoffice', 'String', $this, FALSE, NULL, 'REQUEST') ?? FALSE;
    $params = ['id' => $this->_from_participant_id];
    $participant = $values = [];
    $this->_participant = CRM_Event_BAO_Participant::getValues($params, $values, $participant);
    $this->_part_values = $values[$this->_from_participant_id];
    $this->set('values', $this->_part_values);
    $this->_event_id = $this->_part_values['event_id'];
    $url = CRM_Utils_System::url('civicrm/event/info', "reset=1&id={$this->_event_id}");
    $this->_from_contact_id = $this->_part_values['participant_contact_id'];
    $validUser = CRM_Contact_BAO_Contact_Utils::validChecksum($this->_from_contact_id, $this->_userChecksum);
    if (!$validUser && !CRM_Core_Permission::check('edit all events')) {
      CRM_Core_Error::statusBounce(ts('You do not have sufficient permission to transfer/cancel this participant.'), $url);
    }
    $this->assign('action', $this->_action);
    if ($this->_from_participant_id) {
      $this->assign('participantId', $this->_from_participant_id);
    }

    $daoName = 'title';
    $this->_event_title = CRM_Event_BAO_Event::getFieldValue('CRM_Event_DAO_Event', $this->_event_id, $daoName);
    $daoName = 'start_date';
    $this->_event_start_date = CRM_Event_BAO_Event::getFieldValue('CRM_Event_DAO_Event', $this->_event_id, $daoName);
    [$displayName, $email] = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_from_contact_id);
    $this->_contact_name = $displayName;
    $this->_contact_email = $email;

    $details = CRM_Event_BAO_Participant::participantDetails($this->_from_participant_id);
    $selfServiceDetails = CRM_Event_BAO_Participant::getSelfServiceEligibility($this->_from_participant_id, $url, $this->isBackoffice);
    if (!$selfServiceDetails['eligible']) {
      CRM_Core_Error::statusBounce($selfServiceDetails['ineligible_message'], $url, ts('Sorry'));
    }
    $details = array_merge($details, $selfServiceDetails);
    $this->assign('details', $details);
    //This participant row will be cancelled.  Get line item(s) to cancel
    $this->selfsvctransferUrl = CRM_Utils_System::url('civicrm/event/selfsvcupdate',
      "reset=1&id={$this->_from_participant_id}&id=0");
    $this->selfsvctransferText = ts('Update');
    $this->selfsvctransferButtonText = ts('Update');
  }

  /**
   * Build form for input of transferree email, name
   *
   * return @void
   */
  public function buildQuickForm() {
    // use entityRef select field for contact when this form is used by staff/admin user
    if ($this->isBackoffice) {
      $this->addEntityRef("contact_id", ts('Select Contact'), ['create' => TRUE], TRUE);
    }
    // for front-end user show and use the basic three fields used to create a contact
    else {
      $this->add('text', 'email', ts('To Email'), $this->_contact_email, TRUE);
      $this->add('text', 'last_name', ts('To Last Name'), $this->_to_contact_last_name, TRUE);
      $this->add('text', 'first_name', ts('To First Name'), $this->_to_contact_first_name, TRUE);
    }

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Transfer Registration'),
      ],
    ]);
    $this->addFormRule(['CRM_Event_Form_SelfSvcTransfer', 'formRule'], $this);
    parent::buildQuickForm();
  }

  /**
   * Set defaults
   *
   * return @array _defaults
   */
  public function setDefaultValues() {
    $this->_defaults = [];
    return $this->_defaults;
  }

  /**
   * Validate email and name input
   *
   * return array $errors
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];
    if (!empty($fields['contact_id'])) {
      $to_contact_id = $fields['contact_id'];
    }
    else {
      //check that either an email or firstname+lastname is included in the form(CRM-9587)
      $to_contact_id = self::checkProfileComplete($fields, $errors, $self);
    }
    //To check if the user is already registered for the event(CRM-2426)
    if (!empty($to_contact_id)) {
      self::checkRegistration($fields, $self, $to_contact_id, $errors);
    }
    //return parent::formrule($fields, $files, $self);
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Check whether profile (name, email) is complete
   *
   * return $contact_id
   */
  public static function checkProfileComplete($fields, &$errors, $self) {
    $email = '';
    foreach ($fields as $fieldname => $fieldvalue) {
      if (substr($fieldname, 0, 5) == 'email' && $fieldvalue) {
        $email = $fieldvalue;
      }
    }
    if (empty($email) && (empty($fields['first_name']) || empty($fields['last_name']))) {
      $message = ts("Mandatory fields (first name and last name, OR email address) are missing from this form.");
      $errors['_qf_default'] = $message;
    }
    $contact = CRM_Contact_BAO_Contact::matchContactOnEmail($email, "");
    $contact_id = empty($contact->contact_id) ? NULL : $contact->contact_id;
    if (!CRM_Utils_Rule::email($fields['email'])) {
      $errors['email'] = ts('Enter valid email address.');
    }
    if (empty($errors) && empty($contact_id)) {
      $params = [
        'email-Primary' => $fields['email'] ?? NULL,
        'first_name' => $fields['first_name'] ?? NULL,
        'last_name' => $fields['last_name'] ?? NULL,
        'is_deleted' => $fields['is_deleted'] ?? FALSE,
      ];
      //create new contact for this name/email pair
      //if new contact, no need to check for contact already registered
      $contact_id = CRM_Contact_BAO_Contact::createProfileContact($params, $fields, $contact_id);
    }
    return $contact_id;
  }

  /**
   * Check contact details
   *
   * return @void
   */
  public static function checkRegistration($fields, $self, $contact_id, &$errors) {
    // verify whether this contact already registered for this event
    $contact_details = CRM_Contact_BAO_Contact::getContactDetails($contact_id);
    $display_name = $contact_details[0];
    $query = 'select event_id from civicrm_participant where contact_id = ' . $contact_id;
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $to_event_id[] = $dao->event_id;
    }
    if (!empty($to_event_id)) {
      foreach ($to_event_id as $id) {
        if ($id == $self->_event_id) {
          $errors['email'] = $display_name . ts(" is already registered for this event");
        }
      }
    }
  }

  /**
   * Process transfer - first add the new participant to the event, then cancel
   * source participant - send confirmation email to transferee
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function postProcess() {
    //For transfer, process form to allow selection of transferree
    $params = $this->controller->exportValues($this->_name);
    if (!empty($params['contact_id'])) {
      $contact_id = $params['contact_id'];
    }
    else {
      //cancel 'from' participant row
      $contact_id_result = civicrm_api3('Contact', 'get', [
        'sequential' => 1,
        'return' => ['id'],
        'email' => $params['email'],
        'options' => ['limit' => 1],
      ]);
      $contact_id_result = $contact_id_result['values'][0];
      $contact_id = $contact_id_result['contact_id'];
      $contact_is_deleted = $contact_id_result['contact_is_deleted'];
      if ($contact_is_deleted || !is_numeric($contact_id)) {
        CRM_Core_Error::statusBounce(ts('Contact does not exist.'));
      }
    }

    $this->transferParticipantRegistration($contact_id, $this->_from_participant_id);

    $contact_details = CRM_Contact_BAO_Contact::getContactDetails($contact_id);
    $display_name = current($contact_details);
    $this->assign('to_participant', $display_name);
    $this->sendCancellation();
    list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contact_id);
    $statusMsg = ts('Event registration information for %1 has been updated.', [1 => $displayName]);
    $statusMsg .= ' ' . ts('A confirmation email has been sent to %1.', [1 => $email]);
    CRM_Core_Session::setStatus($statusMsg, ts('Registration Transferred'), 'success');
    if ($this->isBackoffice) {
      return;
    }
    $url = CRM_Utils_System::url('civicrm/event/info', "reset=1&id={$this->_event_id}");
    CRM_Utils_System::redirect($url);
  }

  /**
   * Based on input, create participant row for transferee and send email
   *
   * @param $participant
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function participantTransfer($participant): void {
    $contactDetails = civicrm_api3('Contact', 'getsingle', ['id' => $participant->contact_id, 'return' => ['display_name', 'email']]);

    $participantRoles = CRM_Event_PseudoConstant::participantRole();
    $participantDetails = [];
    $query = 'SELECT * FROM civicrm_participant WHERE id = ' . $participant->id;
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
    $eventParams = ['id' => $participant->event_id];
    CRM_Event_BAO_Event::retrieve($eventParams, $eventDetails);
    //get default participant role.
    $eventDetails['participant_role'] = $participantRoles[$eventDetails['default_role_id']] ?? NULL;
    //get the location info
    $locParams = [
      'entity_id' => $participant->event_id,
      'entity_table' => 'civicrm_event',
    ];
    $eventDetails['location'] = CRM_Core_BAO_Location::getValues($locParams, TRUE);
    $toEmail = $contactDetails['email'] ?? NULL;
    if ($toEmail) {
      //take a receipt from as event else domain.
      $receiptFrom = CRM_Core_BAO_Domain::getNameAndEmail(FALSE, TRUE);
      $receiptFrom = reset($receiptFrom);
      if (!empty($eventDetails['confirm_from_name']) && !empty($eventDetails['confirm_from_email'])) {
        $receiptFrom = $eventDetails['confirm_from_name'] . ' <' . $eventDetails['confirm_from_email'] . '>';
      }
      $participantName = $contactDetails['display_name'];
      $tplParams = [
        'event' => $eventDetails,
        'participant' => $participantDetails[$participant->id],
        'participantID' => $participant->id,
        'participant_status' => 'Registered',
      ];

      $sendTemplateParams = [
        'groupName' => 'msg_tpl_workflow_event',
        'valueName' => 'event_online_receipt',
        'contactId' => $participantDetails[$participant->id]['contact_id'],
        'tplParams' => $tplParams,
        'from' => $receiptFrom,
        'toName' => $participantName,
        'toEmail' => $toEmail,
        'cc' => $eventDetails['cc_confirm'] ?? NULL,
        'bcc' => $eventDetails['bcc_confirm'] ?? NULL,
      ];
      CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
    }
  }

  /**
   * Send confirmation of cancellation to source participant
   *
   * return @ void
   */
  public function sendCancellation() {
    $participantRoles = CRM_Event_PseudoConstant::participantRole();
    $participantDetails = [];
    $query = "SELECT * FROM civicrm_participant WHERE id = {$this->_from_participant_id}";
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
    //send a 'cancelled' email to user, and cc the event's cc_confirm email
    CRM_Event_BAO_Participant::sendTransitionParticipantMail($this->_from_participant_id,
      $participantDetails[$this->_from_participant_id],
      $eventDetails[$this->_event_id],
      NULL,
      'Transferred'
    );
    $statusMsg = ts('Event registration information for %1 has been updated.', [1 => $this->_contact_name]);
    $statusMsg .= ' ' . ts('A cancellation email has been sent to %1.', [1 => $this->_contact_email]);
    CRM_Core_Session::setStatus($statusMsg, ts('Thanks'), 'success');
  }

  /**
   * Move Participant registration to new contact.
   *
   * @param int $toContactID
   * @param int $fromParticipantID
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function transferParticipantRegistration($toContactID, $fromParticipantID) {
    $toParticipantValues = \Civi\Api4\Participant::get()
      ->addWhere('id', '=', $fromParticipantID)
      ->execute()
      ->first();
    $participantPayments = civicrm_api3('ParticipantPayment', 'get', [
      'return' => 'id',
      'participant_id' => $fromParticipantID,
    ])['values'];
    unset($toParticipantValues['id']);
    $toParticipantValues['contact_id'] = $toContactID;
    $toParticipantValues['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'status_id', 'Registered');
    $toParticipantValues['register_date'] = date("Y-m-d");
    //first create the new participant row -don't set registered_by yet or email won't be sent
    $participant = CRM_Event_BAO_Participant::create($toParticipantValues);
    foreach ($participantPayments as $payment) {
      civicrm_api3('ParticipantPayment', 'create', ['id' => $payment['id'], 'participant_id' => $participant->id]);
    }
    //send a confirmation email to the new participant
    $this->participantTransfer($participant);
    //now update registered_by_id
    $query = "UPDATE civicrm_participant cp SET cp.registered_by_id = %1 WHERE  cp.id = ({$participant->id})";
    $params = [1 => [$fromParticipantID, 'Integer']];
    CRM_Core_DAO::executeQuery($query, $params);
    //copy line items to new participant
    $line_items = CRM_Price_BAO_LineItem::getLineItems($fromParticipantID);
    foreach ($line_items as $id => $item) {
      //Remove contribution id from older participant line item.
      CRM_Core_DAO::singleValueQuery('UPDATE civicrm_line_item SET contribution_id = NULL WHERE id = %1', [1 => [$id, 'Integer']]);

      $item['entity_id'] = $participant->id;
      $item['id'] = NULL;
      $item['entity_table'] = "civicrm_participant";
      $tolineItem = CRM_Price_BAO_LineItem::create($item);

      //Update Financial Item for previous line item row.
      $prevFinancialItem = CRM_Financial_BAO_FinancialItem::getPreviousFinancialItem($id);
      $prevFinancialItem['contact_id'] = $toContactID;
      $prevFinancialItem['entity_id'] = $tolineItem->id;
      CRM_Financial_BAO_FinancialItem::create($prevFinancialItem);
    }
    //now cancel the from participant record, leaving the original line-item(s)
    $value_from = [];
    $value_from['id'] = $fromParticipantID;
    $tansferId = array_search('Transferred', CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Negative'"));
    $value_from['status_id'] = $tansferId;
    $value_from['transferred_to_contact_id'] = $toContactID;
    CRM_Event_BAO_Participant::create($value_from);
  }

}
