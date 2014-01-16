<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This form used for changing / updating fee selections for the events
 * event/contribution is partially paid
 *
 */
class CRM_Event_Form_ParticipantFeeSelection extends CRM_Core_Form {

  protected $_contactId = NULL;

  protected $_contributorDisplayName = NULL;

  protected $_contributorEmail = NULL;

  protected $_toDoNotEmail = NULL;

  protected $_contributionId = NULL;

  protected $fromEmailId = NULL;

  protected $_eventId = NULL;

  public $_action = NULL;

  public $_values = NULL;

  public $_participantId = NULL;

  public function preProcess() {
    $this->_participantId = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $this->_eventId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $this->_participantId, 'event_id');
    $this->_fromEmails = CRM_Event_BAO_Event::getFromEmailIds($this->_eventId);

    $this->_contributionId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment', $this->_participantId, 'contribution_id', 'participant_id');
    if ($this->_contributionId) {
      $this->_isPaidEvent = TRUE;
    }
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, TRUE);

    list($this->_contributorDisplayName, $this->_contributorEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactId);
    $this->assign('displayName', $this->_contributorDisplayName);
    $this->assign('email', $this->_contributorEmail);

    //set the payment mode - _mode property is defined in parent class
    $this->_mode = CRM_Utils_Request::retrieve('mode', 'String', $this);

    $this->assign('contactId', $this->_contactId);
    $this->assign('id', $this->_participantId);

    $paymentInfo = CRM_Contribute_BAO_Contribution::getPaymentInfo($this->_participantId, 'event');
    $this->assign('paymentInfo', $paymentInfo);
    CRM_Core_Resources::singleton()->addSetting(array('feePaid' => $paymentInfo['paid']));

    $title = "Change selections for {$this->_contributorDisplayName}";
    if ($title) {
      CRM_Utils_System::setTitle(ts('%1', array(1 => $title)));
    }
  }

  public function setDefaultValues() {
    $params = array('id' => $this->_participantId);

    CRM_Event_BAO_Participant::getValues($params, $defaults, $ids);
    $priceSetId = CRM_Price_BAO_PriceSet::getFor('civicrm_event', $this->_eventId);

    $priceSetValues = CRM_Event_Form_EventFees::setDefaultPriceSet($this->_participantId, $this->_eventId);
    if (!empty($priceSetValues)) {
      $defaults[$this->_participantId] = array_merge($defaults[$this->_participantId], $priceSetValues);
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
    CRM_Core_Resources::singleton()->addScriptFile('civicrm', 'js/crm.livePage.js');

    $statuses = CRM_Event_PseudoConstant::participantStatus();
    CRM_Core_Resources::singleton()->addSetting(array(
        'partiallyPaid' => array_search('Partially paid', $statuses),
        'pendingRefund' => array_search('Pending refund', $statuses),
      ));

    $config = CRM_Core_Config::singleton();
    $this->assign('currencySymbol',  $config->defaultCurrencySymbol);

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
        '' => ts('- select -')) + $statusOptions,
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

    $buttons[] = array(
      'type' => 'upload',
      'name' => ts('Save and Record Payment'),
      'subName' => 'new'
    );

    $buttons[] = array(
      'type' => 'cancel',
      'name' => ts('Cancel'),
    );

    $this->addButtons($buttons);
    $this->addFormRule(array('CRM_Event_Form_ParticipantFeeSelection', 'formRule'), $this);
  }

  static function formRule($fields, $files, $self) {
    $errors = array();
    return $errors;
  }

  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $session = CRM_Core_Session::singleton();
    $buttonName = $this->controller->getButtonName();
    if ($buttonName == $this->getButtonName('upload', 'new')) {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/payment/add',
          "reset=1&action=add&component=event&id={$this->_participantId}&cid={$this->_contactId}"
        ));
    }
 }

  static function emailReceipt(&$form, &$params) {
    // email receipt sending
  }
}