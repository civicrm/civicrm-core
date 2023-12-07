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
 * This form used for changing / updating fee selections for the events
 * event/contribution is partially paid
 *
 */
class CRM_Event_Form_ParticipantFeeSelection extends CRM_Core_Form {
  use CRM_Event_Form_EventFormTrait;
  use CRM_Contact_Form_ContactFormTrait;

  public $useLivePageJS = TRUE;

  /**
   * @var int
   *
   * @deprecated
   */
  protected $_contactId;

  protected $_contributorDisplayName = NULL;

  protected $_contributorEmail = NULL;

  protected $_toDoNotEmail = NULL;

  protected $contributionID;

  protected $fromEmailId = NULL;

  public $_eventId = NULL;

  public $_action = NULL;

  public $_values = NULL;

  /**
   * @var int
   *
   * @deprecated use getParticipantID().
   */
  public $_participantId;

  protected $_paidAmount = NULL;

  public $_isPaidEvent = NULL;

  protected $contributionAmt = NULL;

  public function preProcess() {
    $this->_fromEmails = CRM_Event_BAO_Event::getFromEmailIds($this->getEventID());

    if ($this->getContributionID()) {
      $this->_isPaidEvent = TRUE;
    }
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, TRUE);

    //set the payment mode - _mode property is defined in parent class
    $this->_mode = CRM_Utils_Request::retrieve('mode', 'String', $this);

    [$this->_contributorDisplayName, $this->_contributorEmail] = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->getContactID());
    $this->assign('displayName', $this->getContactValue('display_name'));
    $this->assign('email', $this->getContactValue('email_primary.email'));
    $this->assign('contactId', $this->getContactID());
    $this->assign('id', $this->getParticipantID());

    $paymentInfo = CRM_Contribute_BAO_Contribution::getPaymentInfo($this->_participantId, 'event');
    $this->_paidAmount = $paymentInfo['paid'];
    $this->assign('paymentInfo', $paymentInfo);
    $this->assign('feePaid', $this->_paidAmount);

    $ids = CRM_Event_BAO_Participant::getParticipantIds($this->getContributionID());
    if (count($ids) > 1) {
      $total = CRM_Price_BAO_LineItem::getLineTotal($this->getContributionID());
      $this->assign('totalLineTotal', $total);
      $this->assign('lineItemTotal', $total);
    }

    $title = ts("Change selections for %1", [1 => $this->_contributorDisplayName]);
    if ($title) {
      $this->setTitle($title);
    }
  }

  /**
   * Get the contribution ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getContributionID(): ?int {
    if ($this->contributionID === NULL) {
      $this->contributionID = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment', $this->getParticipantID(), 'contribution_id', 'participant_id') ?: FALSE;

      if (!$this->contributionID) {
        $primaryParticipantID = $this->getParticipantValue('registered_by_id');
        if ($primaryParticipantID) {
          $this->contributionID = CRM_Core_DAO::getFieldValue('CRM_Event_BAO_ParticipantPayment', $primaryParticipantID, 'contribution_id', 'participant_id') ?: FALSE;
        }
      }
    }
    return $this->contributionID ?: NULL;
  }

  /**
   * Set default values for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    $params = ['id' => $this->_participantId];

    CRM_Event_BAO_Participant::getValues($params, $defaults, $ids);

    $priceSetValues = CRM_Event_Form_EventFees::setDefaultPriceSet($this->_participantId, $this->_eventId, FALSE);
    $priceFieldId = (array_keys($this->_values['fee']));
    if (!empty($priceSetValues)) {
      $defaults[$this->_participantId] = array_merge($defaults[$this->_participantId], $priceSetValues);
    }
    else {
      foreach ($priceFieldId as $key => $value) {
        if (!empty($value) && ($this->_values['fee'][$value]['html_type'] == 'Radio' || $this->_values['fee'][$value]['html_type'] == 'Select') && !$this->_values['fee'][$value]['is_required']) {
          $fee_keys = array_keys($this->_values['fee']);
          $defaults[$this->_participantId]['price_' . $fee_keys[$key]] = 0;
        }
      }
    }
    $this->assign('totalAmount', CRM_Utils_Array::value('fee_amount', $defaults[$this->_participantId]));
    if ($this->_action == CRM_Core_Action::UPDATE) {
      $fee_level = $defaults[$this->_participantId]['fee_level'];
      CRM_Event_BAO_Participant::fixEventLevel($fee_level);
      $this->assign('fee_level', $fee_level);
      $this->assign('fee_amount', CRM_Utils_Array::value('fee_amount', $defaults[$this->_participantId]));
    }
    $defaults = $defaults[$this->_participantId];
    return $defaults;
  }

  /**
   * Build form.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {

    $statuses = CRM_Event_PseudoConstant::participantStatus();
    $this->assign('partiallyPaid', array_search('Partially paid', $statuses));
    $this->assign('pendingRefund', array_search('Pending refund', $statuses));
    $this->assign('participantStatus', $this->getParticipantValue('status_id'));

    $this->assign('currencySymbol', CRM_Core_BAO_Country::defaultCurrencySymbol());

    // line items block
    $lineItem = $event = [];
    $params = ['id' => $this->_eventId];
    CRM_Event_BAO_Event::retrieve($params, $event);

    //retrieve custom information
    $this->_values = [];

    $this->_values['line_items'] = CRM_Price_BAO_LineItem::getLineItems($this->_participantId, 'participant');
    CRM_Event_Form_Registration::initEventFee($this, $this->_action !== CRM_Core_Action::UPDATE, $this->getPriceSetID());
    CRM_Event_Form_Registration_Register::buildAmount($this, TRUE, NULL, $this->getPriceSetID());

    if (!CRM_Utils_System::isNull($this->_values['line_items'] ?? NULL)) {
      $lineItem[] = $this->_values['line_items'];
    }
    $this->assign('lineItem', empty($lineItem) ? FALSE : $lineItem);
    $event = CRM_Event_BAO_Event::getEvents(0, $this->_eventId);
    $this->assign('eventName', $event[$this->_eventId]);

    $statusOptions = CRM_Event_PseudoConstant::participantStatus(NULL, NULL, 'label');
    $this->add('select', 'status_id', ts('Participant Status'),
      [
        '' => ts('- select -'),
      ] + $statusOptions,
      TRUE
    );

    $this->addElement('checkbox',
      'send_receipt',
      ts('Send Confirmation?'), NULL,
      ['onclick' => "showHideByValue('send_receipt','','notice','table-row','radio',false); showHideByValue('send_receipt','','from-email','table-row','radio',false);"]
    );

    $this->add('select', 'from_email_address', ts('Receipt From'), $this->_fromEmails['from_email_id']);

    $this->add('textarea', 'receipt_text', ts('Confirmation Message'));

    $noteAttributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_Note');
    $this->add('textarea', 'note', ts('Notes'), $noteAttributes['note']);

    $buttons[] = [
      'type' => 'upload',
      'name' => ts('Save'),
      'isDefault' => TRUE,
    ];

    if (CRM_Event_BAO_Participant::isPrimaryParticipant($this->_participantId)) {
      $buttons[] = [
        'type' => 'upload',
        'name' => ts('Save and Record Payment'),
        'subName' => 'new',
      ];
    }
    $buttons[] = [
      'type' => 'cancel',
      'name' => ts('Cancel'),
    ];

    $this->addButtons($buttons);
    $this->addFormRule(['CRM_Event_Form_ParticipantFeeSelection', 'formRule'], $this);
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
    $discountID = (int) CRM_Core_BAO_Discount::findSet($this->getEventID(), 'civicrm_event');
    return $discountID ?: NULL;
  }

  /**
   * @param $fields
   * @param $files
   * @param self $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];
    return $errors;
  }

  /**
   * Post process form.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    $feeBlock = $this->_values['fee'];
    CRM_Price_BAO_LineItem::changeFeeSelections($params, $this->_participantId, 'participant', $this->getContributionID(), $feeBlock);
    $this->contributionAmt = CRM_Core_DAO::getFieldValue('CRM_Contribute_BAO_Contribution', $this->getContributionID(), 'total_amount');
    // email sending
    if (!empty($params['send_receipt'])) {
      $fetchParticipantVals = ['id' => $this->_participantId];
      CRM_Event_BAO_Participant::getValues($fetchParticipantVals, $participantDetails);
      $participantParams = array_merge($params, $participantDetails[$this->_participantId]);
      $this->emailReceipt($participantParams);
    }

    // update participant
    CRM_Core_DAO::setFieldValue('CRM_Event_DAO_Participant', $this->_participantId, 'status_id', $params['status_id']);
    if (!empty($params['note'])) {
      $noteParams = [
        'entity_table' => 'civicrm_participant',
        'note' => $params['note'],
        'entity_id' => $this->_participantId,
        'contact_id' => $this->_contactId,
      ];
      CRM_Core_BAO_Note::add($noteParams);
    }
    CRM_Core_Session::setStatus(ts("The fee selection has been changed for this participant"), ts('Saved'), 'success');

    $buttonName = $this->controller->getButtonName();
    if ($buttonName == $this->getButtonName('upload', 'new')) {
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url('civicrm/payment/add',
        "reset=1&action=add&component=event&id={$this->_participantId}&cid={$this->_contactId}"
      ));
    }
  }

  /**
   * @param array $params
   */
  private function emailReceipt(array $params): void {
    $updatedLineItem = CRM_Price_BAO_LineItem::getLineItems($this->_participantId, 'participant', FALSE, FALSE);
    $lineItem = [];
    if ($updatedLineItem) {
      $lineItem[] = $updatedLineItem;
    }
    $this->assign('lineItem', empty($lineItem) ? FALSE : $lineItem);

    // offline receipt sending
    if (array_key_exists($params['from_email_address'], $this->_fromEmails['from_email_id'])) {
      $receiptFrom = $params['from_email_address'];
    }

    $this->assign('module', 'Event Registration');
    //use of the message template below requires variables in different format
    $events = [];
    $returnProperties = ['fee_label', 'start_date', 'end_date', 'is_show_location', 'title'];

    //get all event details.
    CRM_Core_DAO::commonRetrieveAll('CRM_Event_DAO_Event', 'id', $params['event_id'], $events, $returnProperties);
    $event = $events[$params['event_id']];
    unset($event['start_date'], $event['end_date']);

    $role = CRM_Event_PseudoConstant::participantRole();
    $participantRoles = $params['role_id'] ?? NULL;
    if (is_array($participantRoles)) {
      $selectedRoles = [];
      foreach (array_keys($participantRoles) as $roleId) {
        $selectedRoles[] = $role[$roleId];
      }
      $event['participant_role'] = implode(', ', $selectedRoles);
    }
    else {
      $event['participant_role'] = $role[$participantRoles] ?? NULL;
    }
    $event['is_monetary'] = $this->_isPaidEvent;

    if ($params['receipt_text']) {
      $event['confirm_email_text'] = $params['receipt_text'];
    }

    $this->assign('event', $event);

    $this->assign('isShowLocation', $event['is_show_location']);
    if (($event['is_show_location'] ?? NULL) == 1) {
      $locationParams = [
        'entity_id' => $params['event_id'],
        'entity_table' => 'civicrm_event',
      ];
      $location = CRM_Core_BAO_Location::getValues($locationParams, TRUE);
      $this->assign('location', $location);
    }

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

      $this->assign('totalAmount', $this->contributionAmt);
      $this->assign('checkNumber', CRM_Utils_Array::value('check_number', $params));
    }

    $this->assign('register_date', $params['register_date']);

    // Retrieve the name and email of the contact - this will be the TO for receipt email
    [$this->_contributorDisplayName, $this->_contributorEmail, $this->_toDoNotEmail] = CRM_Contact_BAO_Contact::getContactDetails($this->_contactId);

    $this->_contributorDisplayName = ($this->_contributorDisplayName == ' ') ? $this->_contributorEmail : $this->_contributorDisplayName;

    $waitStatus = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Waiting'");
    $this->assign('isOnWaitlist', (bool) in_array($params['status_id'], $waitStatus));
    $this->assign('contactID', $this->_contactId);

    $sendTemplateParams = [
      'workflow' => 'event_offline_receipt',
      'contactId' => $this->_contactId,
      'isTest' => FALSE,
      'PDFFilename' => ts('confirmation') . '.pdf',
      'modelProps' => [
        'participantID' => $this->_participantId,
        'eventID' => $params['event_id'],
        'contributionID' => $this->getContributionID(),
      ],
    ];

    // try to send emails only if email id is present
    // and the do-not-email option is not checked for that contact
    if ($this->_contributorEmail && !$this->_toDoNotEmail) {
      $sendTemplateParams['from'] = $receiptFrom;
      $sendTemplateParams['toName'] = $this->_contributorDisplayName;
      $sendTemplateParams['toEmail'] = $this->_contributorEmail;
      $sendTemplateParams['cc'] = $this->_fromEmails['cc'] ?? NULL;
      $sendTemplateParams['bcc'] = $this->_fromEmails['bcc'] ?? NULL;
    }

    CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
  }

  /**
   * Get the event ID.
   *
   * This function is supported for use outside of core.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @return int
   * @throws \CRM_Core_Exception
   */
  public function getEventID(): int {
    if (!$this->_eventId) {
      $this->_eventId = $this->getParticipantValue('event_id');
    }
    return $this->_eventId;
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
    if ($this->getDiscountID()) {
      $priceSetID = CRM_Core_DAO::getFieldValue('CRM_Core_BAO_Discount', $this->getDiscountID(), 'price_set_id');
    }
    else {
      $priceSetID = CRM_Price_BAO_PriceSet::getFor('civicrm_event', $this->getEventID());
    }
    // Currently some extensions, eg. civi-discount, might look for this. Once we can be
    // sure all financial forms have the api-supported function `getPriceSetID` we can
    // add some noise to attempts to get it & move people over.
    $this->set('priceSetId', $priceSetID);
    return $priceSetID;
  }

  /**
   * Get id of participant being acted on.
   *
   * @return int
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * No exception should be thrown... as it should be unreachable/overridden.
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  public function getParticipantID(): int {
    if (!$this->_participantId) {
      $this->_participantId = (int) CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    }
    return $this->_participantId;
  }

  /**
   * Get the contact ID.
   *
   * Override this for more complex retrieval as required by the form.
   *
   * @return int|null
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  public function getContactID(): ?int {
    if (!$this->_contactId) {
      $this->_contactId = (int) $this->getParticipantValue('contact_id');
    }
    return $this->_contactId;
  }

}
