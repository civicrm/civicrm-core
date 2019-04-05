<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                            |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */

/**
 * This class generates form components to transfer an Event to another participant
 *
 */
class CRM_Event_Form_SelfSvcTransfer extends CRM_Core_Form {
  /**
   * from particpant id
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
   * last name of the particpant to transfer to
   *
   * @var string
   *
   */
  protected $_to_contact_last_name;
  /**
   * first name of the particpant to transfer to
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
   * particpant values
   *
   * @array string
   */
  protected $_part_values;
  /**
   * details
   *
   * @array string
   */
  protected $_details = [];
  /**
   * line items
   *
   * @array string
   */
  protected $_line_items = [];
  /**
   * contact_id
   *
   * @array string
   */
  protected $contact_id;

  /**
   * Is backoffice form?
   *
   * @array bool
   */
  protected $isBackoffice = FALSE;

  /**
   * Get source values for transfer based on participant id in URL. Line items will
   * be transferred to this participant - at this point no transaction changes processed
   *
   * return @void
   */
  public function preProcess() {
    $config = CRM_Core_Config::singleton();
    $session = CRM_Core_Session::singleton();
    $this->_userContext = $session->readUserContext();
    $this->_from_participant_id = CRM_Utils_Request::retrieve('pid', 'Positive', $this, FALSE, NULL, 'REQUEST');
    $this->_userChecksum = CRM_Utils_Request::retrieve('cs', 'String', $this, FALSE, NULL, 'REQUEST');
    $this->isBackoffice = CRM_Utils_Request::retrieve('is_backoffice', 'String', $this, FALSE, NULL, 'REQUEST');
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
    $event = [];
    $daoName = 'title';
    $this->_event_title = CRM_Event_BAO_Event::getFieldValue('CRM_Event_DAO_Event', $this->_event_id, $daoName);
    $daoName = 'start_date';
    $this->_event_start_date = CRM_Event_BAO_Event::getFieldValue('CRM_Event_DAO_Event', $this->_event_id, $daoName);
    list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_from_contact_id);
    $this->_contact_name = $displayName;
    $this->_contact_email = $email;
    $details = [];
    $details = CRM_Event_BAO_Participant::participantDetails($this->_from_participant_id);
    $optionGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'participant_role', 'id', 'name');
    $query = "
      SELECT cpst.name as status, cov.name as role, cp.fee_level, cp.fee_amount, cp.register_date, civicrm_event.start_date
      FROM civicrm_participant cp
      LEFT JOIN civicrm_participant_status_type cpst ON cpst.id = cp.status_id
      LEFT JOIN civicrm_option_value cov ON cov.value = cp.role_id and cov.option_group_id = {$optionGroupId}
      LEFT JOIN civicrm_event ON civicrm_event.id = cp.event_id
      WHERE cp.id = {$this->_from_participant_id}";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $details['status']  = $dao->status;
      $details['role'] = $dao->role;
      $details['fee_level']   = $dao->fee_level;
      $details['fee_amount'] = $dao->fee_amount;
      $details['register_date'] = $dao->register_date;
      $details['event_start_date'] = $dao->start_date;
    }
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
      $this->add('text', 'email', ts('To Email'), ts($this->_contact_email), TRUE);
      $this->add('text', 'last_name', ts('To Last Name'), ts($this->_to_contact_last_name), TRUE);
      $this->add('text', 'first_name', ts('To First Name'), ts($this->_to_contact_first_name), TRUE);
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
    if (!$email && !(CRM_Utils_Array::value('first_name', $fields) &&
      CRM_Utils_Array::value('last_name', $fields))) {
      $defaults = $params = ['id' => $eventId];
      CRM_Event_BAO_Event::retrieve($params, $defaults);
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
        'email-Primary' => CRM_Utils_Array::value('email', $fields, NULL),
        'first_name' => CRM_Utils_Array::value('first_name', $fields, NULL),
        'last_name' => CRM_Utils_Array::value('last_name', $fields, NULL),
        'is_deleted' => CRM_Utils_Array::value('is_deleted', $fields, FALSE),
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
    $query = "select event_id from civicrm_participant where contact_id = " . $contact_id;
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $to_event_id[]  = $dao->event_id;
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
        'return' => ["id"],
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
    $from_participant = $params = [];
    $query = "select role_id, source, fee_level, is_test, is_pay_later, fee_amount, discount_id, fee_currency,campaign_id, discount_amount from civicrm_participant where id = " . $this->_from_participant_id;
    $dao = CRM_Core_DAO::executeQuery($query);
    $value_to = [];
    while ($dao->fetch()) {
      $value_to['role_id'] = $dao->role_id;
      $value_to['source'] = $dao->source;
      $value_to['fee_level'] = $dao->fee_level;
      $value_to['is_test'] = $dao->is_test;
      $value_to['is_pay_later'] = $dao->is_pay_later;
      $value_to['fee_amount'] = $dao->fee_amount;
    }
    $value_to['contact_id'] = $contact_id;
    $value_to['event_id'] = $this->_event_id;
    $value_to['status_id'] = 1;
    $value_to['register_date'] = date("Y-m-d");
    //first create the new participant row -don't set registered_by yet or email won't be sent
    $participant = CRM_Event_BAO_Participant::create($value_to);
    //send a confirmation email to the new participant
    $this->participantTransfer($participant);
    //now update registered_by_id
    $query = "UPDATE civicrm_participant cp SET cp.registered_by_id = %1 WHERE  cp.id = ({$participant->id})";
    $params = [1 => [$this->_from_participant_id, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    //copy line items to new participant
    $line_items = CRM_Price_BAO_LineItem::getLineItems($this->_from_participant_id);
    foreach ($line_items as $item) {
      $item['entity_id'] = $participant->id;
      $item['id'] = NULL;
      $item['entity_table'] = "civicrm_participant";
      $new_item = CRM_Price_BAO_LineItem::create($item);
    }
    //now cancel the from participant record, leaving the original line-item(s)
    $value_from = [];
    $value_from['id'] = $this->_from_participant_id;
    $tansferId = array_search('Transferred', CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Negative'"));
    $value_from['status_id'] = $tansferId;
    $value_from['transferred_to_contact_id'] = $contact_id;
    $contact_details = CRM_Contact_BAO_Contact::getContactDetails($contact_id);
    $display_name = current($contact_details);
    $this->assign('to_participant', $display_name);
    CRM_Event_BAO_Participant::create($value_from);
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
   * return @ void
   */
  public function participantTransfer($participant) {
    $contactDetails = [];
    $contactIds[] = $participant->contact_id;
    list($currentContactDetails) = CRM_Utils_Token::getTokenDetails($contactIds, NULL,
      FALSE, FALSE, NULL, [], 'CRM_Event_BAO_Participant');
    foreach ($currentContactDetails as $contactId => $contactValues) {
      $contactDetails[$contactId] = $contactValues;
    }
    $participantRoles = CRM_Event_PseudoConstant::participantRole();
    $participantDetails = [];
    $query = "SELECT * FROM civicrm_participant WHERE id = " . $participant->id;
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
    $domainValues = [];
    if (empty($domainValues)) {
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
    }
    $eventDetails = [];
    $eventParams = ['id' => $participant->event_id];
    CRM_Event_BAO_Event::retrieve($eventParams, $eventDetails);
    //get default participant role.
    $eventDetails['participant_role'] = CRM_Utils_Array::value($eventDetails['default_role_id'], $participantRoles);
    //get the location info
    $locParams = [
      'entity_id' => $participant->event_id,
      'entity_table' => 'civicrm_event',
    ];
    $eventDetails['location'] = CRM_Core_BAO_Location::getValues($locParams, TRUE);
    $toEmail = CRM_Utils_Array::value('email', $contactDetails[$participant->contact_id]);
    if ($toEmail) {
      //take a receipt from as event else domain.
      $receiptFrom = $domainValues['name'] . ' <' . $domainValues['email'] . '>';
      if (!empty($eventDetails['confirm_from_name']) && !empty($eventDetails['confirm_from_email'])) {
        $receiptFrom = $eventDetails['confirm_from_name'] . ' <' . $eventDetails['confirm_from_email'] . '>';
      }
      $participantName = $contactDetails[$participant->contact_id]['display_name'];
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
        'cc' => CRM_Utils_Array::value('cc_confirm', $eventDetails),
        'bcc' => CRM_Utils_Array::value('bcc_confirm', $eventDetails),
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
    $participantRoles = [];
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
    $eventDetails[$this->_event_id]['participant_role'] = CRM_Utils_Array::value($eventDetails[$this->_event_id]['default_role_id'], $participantRoles);
    //get the location info
    $locParams = ['entity_id' => $this->_event_id, 'entity_table' => 'civicrm_event'];
    $eventDetails[$this->_event_id]['location'] = CRM_Core_BAO_Location::getValues($locParams, TRUE);
    //get contact details
    $contactIds[$this->_from_contact_id] = $this->_from_contact_id;
    list($currentContactDetails) = CRM_Utils_Token::getTokenDetails($contactIds, NULL,
      FALSE, FALSE, NULL, [],
      'CRM_Event_BAO_Participant'
    );
    foreach ($currentContactDetails as $contactId => $contactValues) {
      $contactDetails[$this->_from_contact_id] = $contactValues;
    }
    //send a 'cancelled' email to user, and cc the event's cc_confirm email
    $mail = CRM_Event_BAO_Participant::sendTransitionParticipantMail($this->_from_participant_id,
      $participantDetails[$this->_from_participant_id],
      $eventDetails[$this->_event_id],
      $contactDetails[$this->_from_contact_id],
      $domainValues,
      "Transferred",
      ""
    );
    $statusMsg = ts('Event registration information for %1 has been updated.', [1 => $this->_contact_name]);
    $statusMsg .= ' ' . ts('A cancellation email has been sent to %1.', [1 => $this->_contact_email]);
    CRM_Core_Session::setStatus($statusMsg, ts('Thanks'), 'success');
  }

}
