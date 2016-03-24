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
 | CiviCRM is distributed in the hope that it will be usefusul, but   |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */

/**
 * This form used for changing / updating fee selections for the events
 * event/contribution is partially paid
 *
 */
class CRM_Event_Form_ParticipantFeeSelection extends CRM_Core_Form {

  public $useLivePageJS = TRUE;

  protected $_contactId = NULL;

  protected $_contributorDisplayName = NULL;

  protected $_contributorEmail = NULL;

  protected $_toDoNotEmail = NULL;

  protected $_contributionId = NULL;

  protected $fromEmailId = NULL;

  public $_eventId = NULL;

  public $_action = NULL;

  public $_values = NULL;

  public $_participantId = NULL;

  protected $_participantStatus = NULL;

  protected $_paidAmount = NULL;

  public $_isPaidEvent = NULL;

  protected $contributionAmt = NULL;

  public function preProcess() {
    $this->_participantId = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $this->_eventId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $this->_participantId, 'event_id');
    $this->_fromEmails = CRM_Event_BAO_Event::getFromEmailIds($this->_eventId);

    $this->_contributionId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment', $this->_participantId, 'contribution_id', 'participant_id');
    if (!$this->_contributionId) {
      if ($primaryParticipantId = CRM_Core_DAO::getFieldValue('CRM_Event_BAO_Participant', $this->_participantId, 'registered_by_id')) {
        $this->_contributionId = CRM_Core_DAO::getFieldValue('CRM_Event_BAO_ParticipantPayment', $primaryParticipantId, 'contribution_id', 'participant_id');
      }
    }

    if ($this->_contributionId) {
      $this->_isPaidEvent = TRUE;
    }
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, TRUE);

    list($this->_contributorDisplayName, $this->_contributorEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactId);
    $this->assign('displayName', $this->_contributorDisplayName);
    $this->assign('email', $this->_contributorEmail);

    $this->_participantStatus = CRM_Core_DAO::getFieldValue('CRM_Event_BAO_Participant', $this->_participantId, 'status_id');
    //set the payment mode - _mode property is defined in parent class
    $this->_mode = CRM_Utils_Request::retrieve('mode', 'String', $this);

    $this->assign('contactId', $this->_contactId);
    $this->assign('id', $this->_participantId);

    $paymentInfo = CRM_Contribute_BAO_Contribution::getPaymentInfo($this->_participantId, 'event');
    $this->_paidAmount = $paymentInfo['paid'];
    $this->assign('paymentInfo', $paymentInfo);
    $this->assign('feePaid', $this->_paidAmount);

    $ids = CRM_Event_BAO_Participant::getParticipantIds($this->_contributionId);
    if (count($ids) > 1) {
      $total = 0;
      foreach ($ids as $val) {
        $total += CRM_Price_BAO_LineItem::getLineTotal($val, 'civicrm_participant');
      }
      $this->assign('totalLineTotal', $total);

      $lineItemTotal = CRM_Price_BAO_LineItem::getLineTotal($this->_participantId, 'civicrm_participant');
      $this->assign('lineItemTotal', $lineItemTotal);
    }

    $title = ts("Change selections for %1", array(1 => $this->_contributorDisplayName));
    if ($title) {
      CRM_Utils_System::setTitle($title);
    }
  }

  public function setDefaultValues() {
    $params = array('id' => $this->_participantId);

    CRM_Event_BAO_Participant::getValues($params, $defaults, $ids);
    $priceSetId = CRM_Price_BAO_PriceSet::getFor('civicrm_event', $this->_eventId);

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

  public function buildQuickForm() {

    $statuses = CRM_Event_PseudoConstant::participantStatus();
    $this->assign('partiallyPaid', array_search('Partially paid', $statuses));
    $this->assign('pendingRefund', array_search('Pending refund', $statuses));
    $this->assign('participantStatus', $this->_participantStatus);

    $config = CRM_Core_Config::singleton();
    $this->assign('currencySymbol', $config->defaultCurrencySymbol);

    // line items block
    $lineItem = $event = array();
    $params = array('id' => $this->_eventId);
    CRM_Event_BAO_Event::retrieve($params, $event);

    //retrieve custom information
    $this->_values = array();
    CRM_Event_Form_Registration::initEventFee($this, $event['id']);
    CRM_Event_Form_Registration_Register::buildAmount($this, TRUE);

    if (!CRM_Utils_System::isNull(CRM_Utils_Array::value('line_items', $this->_values))) {
      $lineItem[] = $this->_values['line_items'];
    }
    $this->assign('lineItem', empty($lineItem) ? FALSE : $lineItem);
    $event = CRM_Event_BAO_Event::getEvents(0, $this->_eventId);
    $this->assign('eventName', $event[$this->_eventId]);

    $statusOptions = CRM_Event_PseudoConstant::participantStatus(NULL, NULL, 'label');
    $this->add('select', 'status_id', ts('Participant Status'),
      array(
        '' => ts('- select -'),
      ) + $statusOptions,
      TRUE
    );

    $this->addElement('checkbox',
      'send_receipt',
      ts('Send Confirmation?'), NULL,
      array('onclick' => "showHideByValue('send_receipt','','notice','table-row','radio',false); showHideByValue('send_receipt','','from-email','table-row','radio',false);")
    );

    $this->add('select', 'from_email_address', ts('Receipt From'), $this->_fromEmails['from_email_id']);

    $this->add('textarea', 'receipt_text', ts('Confirmation Message'));

    $noteAttributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_Note');
    $this->add('textarea', 'note', ts('Notes'), $noteAttributes['note']);

    $buttons[] = array(
      'type' => 'upload',
      'name' => ts('Save'),
      'isDefault' => TRUE,
    );

    if (CRM_Event_BAO_Participant::isPrimaryParticipant($this->_participantId)) {
      $buttons[] = array(
        'type' => 'upload',
        'name' => ts('Save and Record Payment'),
        'subName' => 'new',
      );
    }
    $buttons[] = array(
      'type' => 'cancel',
      'name' => ts('Cancel'),
    );

    $this->addButtons($buttons);
    $this->addFormRule(array('CRM_Event_Form_ParticipantFeeSelection', 'formRule'), $this);
  }

  /**
   * @param $fields
   * @param $files
   * @param $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    $errors = array();
    return $errors;
  }

  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    $feeBlock = $this->_values['fee'];
    $lineItems = $this->_values['line_items'];
    CRM_Event_BAO_Participant::changeFeeSelections($params, $this->_participantId, $this->_contributionId, $feeBlock, $lineItems, $this->_paidAmount, $params['priceSetId']);
    $this->contributionAmt = CRM_Core_DAO::getFieldValue('CRM_Contribute_BAO_Contribution', $this->_contributionId, 'total_amount');
    // email sending
    if (!empty($params['send_receipt'])) {
      $fetchParticipantVals = array('id' => $this->_participantId);
      CRM_Event_BAO_Participant::getValues($fetchParticipantVals, $participantDetails, CRM_Core_DAO::$_nullArray);
      $participantParams = array_merge($params, $participantDetails[$this->_participantId]);
      $mailSent = $this->emailReceipt($participantParams);
    }

    // update participant
    CRM_Core_DAO::setFieldValue('CRM_Event_DAO_Participant', $this->_participantId, 'status_id', $params['status_id']);
    if (!empty($params['note'])) {
      $noteParams = array(
        'entity_table' => 'civicrm_participant',
        'note' => $params['note'],
        'entity_id' => $this->_participantId,
        'contact_id' => $this->_contactId,
        'modified_date' => date('Ymd'),
      );
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
   *
   * @return mixed
   */
  public function emailReceipt(&$params) {
    $updatedLineItem = CRM_Price_BAO_LineItem::getLineItems($this->_participantId, 'participant', NULL, FALSE);
    $lineItem = array();
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
      foreach (array_keys($participantRoles) as $roleId) {
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

      $this->assign('totalAmount', $this->contributionAmt);

      $this->assign('isPrimary', 1);
      $this->assign('checkNumber', CRM_Utils_Array::value('check_number', $params));
    }

    $this->assign('register_date', $params['register_date']);
    $template = CRM_Core_Smarty::singleton();

    // Retrieve the name and email of the contact - this will be the TO for receipt email
    list($this->_contributorDisplayName, $this->_contributorEmail, $this->_toDoNotEmail) = CRM_Contact_BAO_Contact::getContactDetails($this->_contactId);

    $this->_contributorDisplayName = ($this->_contributorDisplayName == ' ') ? $this->_contributorEmail : $this->_contributorDisplayName;

    $waitStatus = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Waiting'");
    if ($waitingStatus = CRM_Utils_Array::value($params['status_id'], $waitStatus)) {
      $this->assign('isOnWaitlist', TRUE);
    }
    $this->assign('contactID', $this->_contactId);
    $this->assign('participantID', $this->_participantId);

    $sendTemplateParams = array(
      'groupName' => 'msg_tpl_workflow_event',
      'valueName' => 'event_offline_receipt',
      'contactId' => $this->_contactId,
      'isTest' => FALSE,
      'PDFFilename' => ts('confirmation') . '.pdf',
    );

    // try to send emails only if email id is present
    // and the do-not-email option is not checked for that contact
    if ($this->_contributorEmail && !$this->_toDoNotEmail) {
      $sendTemplateParams['from'] = $receiptFrom;
      $sendTemplateParams['toName'] = $this->_contributorDisplayName;
      $sendTemplateParams['toEmail'] = $this->_contributorEmail;
      $sendTemplateParams['cc'] = CRM_Utils_Array::value('cc', $this->_fromEmails);
      $sendTemplateParams['bcc'] = CRM_Utils_Array::value('bcc', $this->_fromEmails);
    }

    list($mailSent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
    return $mailSent;
  }

}
