<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright (C) 2011 Marty Wright                                    |
 | Licensed to CiviCRM under the Academic Free License version 3.0.   |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class generates form components for Scheduling Reminders
 *
 */
class CRM_Admin_Form_ScheduleReminders extends CRM_Admin_Form {

  /**
   * Scheduled Reminder ID
   */
  protected $_id = NULL;

  public $_freqUnits;

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->_mappingID = $mappingID = NULL;

    if ($this->_action & (CRM_Core_Action::DELETE)) {
      $reminderName =
        CRM_Core_DAO::getFieldValue('CRM_Core_DAO_ActionSchedule', $this->_id, 'title');
      $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);
      if ($this->_context == 'event') {
        $this->_eventId = CRM_Utils_Request::retrieve('eventId', 'Integer', $this);
      }
      $this->assign('reminderName', $reminderName);
      return;
    }
    elseif ($this->_action & (CRM_Core_Action::UPDATE)) {
      $this->_mappingID =
        CRM_Core_DAO::getFieldValue('CRM_Core_DAO_ActionSchedule', $this->_id, 'mapping_id');
      $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);
      if ($this->_context == 'event') {
        $this->_eventId = CRM_Utils_Request::retrieve('eventId', 'Integer', $this);
      }
    }

    if (!empty($_POST) && CRM_Utils_Array::value('entity', $_POST)) {
      $mappingID = $_POST['entity'][0];
    }
    elseif ($this->_mappingID) {
      $mappingID = $this->_mappingID;
    }

    $this->add(
      'text',
      'title',
      ts('Title'),
      array('size' => 45, 'maxlength' => 128),
      TRUE
    );

    $selectionOptions = CRM_Core_BAO_ActionSchedule::getSelection($mappingID);
    extract($selectionOptions);

    if (empty($sel1)) {
      CRM_Core_Error::fatal('Could not find mapping for scheduled reminders.');
    }
    $this->assign('entityMapping', json_encode($entityMapping));
    $this->assign('recipientMapping', json_encode($recipientMapping));

    $sel = &$this->add(
      'hierselect',
      'entity',
      ts('Entity'),
      array(
        'name' => 'entity[0]',
        'style' => 'vertical-align: top;',
      ),
      TRUE
    );
    $sel->setOptions(array($sel1, $sel2, $sel3));

    if (is_a($sel->_elements[1], 'HTML_QuickForm_select')) {
      // make second selector a multi-select -
      $sel->_elements[1]->setMultiple(TRUE);
      $sel->_elements[1]->setSize(5);
    }

    if (is_a($sel->_elements[2], 'HTML_QuickForm_select')) {
      // make third selector a multi-select -
      $sel->_elements[2]->setMultiple(TRUE);
      $sel->_elements[2]->setSize(5);
    }

    //get the frequency units.
    $this->_freqUnits = array('hour' => 'hour') + CRM_Core_OptionGroup::values('recur_frequency_units');

    //pass the mapping ID in UPDATE mode
    $mappings = CRM_Core_BAO_ActionSchedule::getMapping($mappingID);

    $numericOptions = CRM_Core_SelectValues::getNumericOptions(0, 30);

    //reminder_interval
    $this->add('select', 'start_action_offset', ts('When'), $numericOptions);

    foreach ($this->_freqUnits as $val => $label) {
      $freqUnitsDisplay[$val] = ts('%1(s)', array(1 => $label));
    }

    $this->addDate('absolute_date', ts('Start Date'), FALSE, array('formatType' => 'mailing'));

    //reminder_frequency
    $this->add('select', 'start_action_unit', ts('Frequency'), $freqUnitsDisplay, TRUE);

    $condition = array('before' => ts('before'),
      'after' => ts('after'),
    );
    //reminder_action
    $this->add('select', 'start_action_condition', ts('Action Condition'), $condition);

    $this->add('select', 'start_action_date', ts('Date Field'), $sel4, TRUE);

    $this->addElement('checkbox', 'record_activity', ts('Record activity for automated email'));

    $this->addElement('checkbox', 'is_repeat', ts('Repeat'),
      NULL, array('onclick' => "return showHideByValue('is_repeat',true,'repeatFields','table-row','radio',false);")
    );

    $this->add('select', 'repetition_frequency_unit', ts('every'), $freqUnitsDisplay);
    $this->add('select', 'repetition_frequency_interval', ts('every'), $numericOptions);
    $this->add('select', 'end_frequency_unit', ts('until'), $freqUnitsDisplay);
    $this->add('select', 'end_frequency_interval', ts('until'), $numericOptions);
    $this->add('select', 'end_action', ts('Repetition Condition'), $condition, TRUE);
    $this->add('select', 'end_date', ts('Date Field'), $sel4, TRUE);

    $recipient = 'activity_contacts';
    $recipientListingOptions = array();

    if ($mappingID) {
      $recipient = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_ActionMapping',
        $mappingID,
        'entity_recipient'
      );
    }

    $this->add('select', 'recipient', ts('Limit Recipients'), $sel5[$recipient],
      FALSE, array('onClick' => "showHideByValue('recipient','manual','recipientManual','table-row','select',false); showHideByValue('recipient','group','recipientGroup','table-row','select',false);")
    );

    if (CRM_Utils_Array::value('is_recipient_listing', $_POST)) {
      $recipientListingOptions = CRM_Core_BAO_ActionSchedule::getRecipientListing($_POST['entity'][0], $_POST['recipient']);
    }
    elseif (CRM_Utils_Array::value('recipient_listing', $this->_values)) {
      $recipientListingOptions = CRM_Core_BAO_ActionSchedule::getRecipientListing($this->_values['mapping_id'], $this->_values['recipient']);
    }
    $recipientListing = $this->add('select', 'recipient_listing', ts('Recipient Listing'), $recipientListingOptions);
    $recipientListing->setMultiple(TRUE);
    $this->add('hidden', 'is_recipient_listing', empty($recipientListingOptions) ? FALSE : TRUE, array('id' => 'is_recipient_listing'));

    //autocomplete url
    $dataUrl = CRM_Utils_System::url('civicrm/ajax/rest',
      'className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&context=activity&reset=1',
      FALSE, NULL, FALSE
    );

    $this->assign('dataUrl', $dataUrl);
    //tokeninput url
    $tokenUrl = CRM_Utils_System::url('civicrm/ajax/checkemail',
      'noemail=1',
      FALSE, NULL, FALSE
    );
    $this->assign('tokenUrl', $tokenUrl);
    $this->add('text', 'recipient_manual_id', ts('Manual Recipients'));

    $this->addElement('select', 'group_id', ts('Group'),
      CRM_Core_PseudoConstant::staticGroup()
    );

    CRM_Mailing_BAO_Mailing::commonCompose($this);

    $this->add('text', 'subject', ts('Subject'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_ActionSchedule', 'subject')
    );

    $this->add('checkbox', 'is_active', ts('Send email'));

    $this->addFormRule(array('CRM_Admin_Form_ScheduleReminders', 'formRule'));
  }
  /**
   * global form rule
   *
   * @param array $fields  the input form values
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields) {
    $errors = array();
    if ((array_key_exists(1, $fields['entity']) && $fields['entity'][1][0] == 0) ||
      (array_key_exists(2, $fields['entity']) && $fields['entity'][2][0] == 0)
    ) {
      $errors['entity'] = ts('Please select appropriate value');
    }

    if (CRM_Utils_Array::value('is_active', $fields) &&
      CRM_Utils_System::isNull($fields['subject'])
    ) {
      $errors['subject'] = ts('Subject is a required field.');
    }

    if (CRM_Utils_System::isNull(CRM_Utils_Array::value(1, $fields['entity']))) {
      $errors['entity'] = ts('Please select entity value');
    }

    if (!CRM_Utils_System::isNull($fields['absolute_date'])) {
      if (CRM_Utils_Date::format(CRM_Utils_Date::processDate($fields['absolute_date'], NULL)) < CRM_Utils_Date::format(date('YmdHi00'))) {
        $errors['absolute_date'] = ts('Absolute date cannot be earlier than the current time.');
      }
    }

    if (!empty($errors)) {
      return $errors;
    }

    return empty($errors) ? TRUE : $errors;
  }

  function setDefaultValues() {
    if ($this->_action & CRM_Core_Action::ADD) {
      $defaults['is_active'] = 1;
      $defaults['record_activity'] = 1;
    }
    else {
      $defaults = $this->_values;
      $entityValue = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        CRM_Utils_Array::value('entity_value', $defaults)
      );
      $entityStatus = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        CRM_Utils_Array::value('entity_status', $defaults)
      );
      $defaults['entity'][0] = CRM_Utils_Array::value('mapping_id', $defaults);
      $defaults['entity'][1] = $entityValue;
      $defaults['entity'][2] = $entityStatus;
      if ($absoluteDate = CRM_Utils_Array::value('absolute_date', $defaults)) {
        list($date, $time) = CRM_Utils_Date::setDateDefaults($absoluteDate);
        $defaults['absolute_date'] = $date;
      }

      if ($recipientListing = CRM_Utils_Array::value('recipient_listing', $defaults)) {
        $defaults['recipient_listing'] = explode(CRM_Core_DAO::VALUE_SEPARATOR,
          $recipientListing
        );
      }
      $defaults['text_message'] = CRM_Utils_Array::value('body_text', $defaults);
      $defaults['html_message'] = CRM_Utils_Array::value('body_html', $defaults);
      $defaults['template'] = CRM_Utils_Array::value('msg_template_id', $defaults);
      if (CRM_Utils_Array::value('group_id', $defaults)) {
        $defaults['recipient'] = 'group';
      }
      elseif (CRM_Utils_Array::value('recipient_manual', $defaults)) {
        $defaults['recipient'] = 'manual';
        $recipients = array();
        foreach (explode(',', $defaults['recipient_manual']) as $cid) {
          $recipients[$cid] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
            $cid,
            'sort_name'
          );
        }
        $this->assign('recipients', $recipients);
      }
    }

    return $defaults;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      // delete reminder
      CRM_Core_BAO_ActionSchedule::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected Reminder has been deleted.'), ts('Record Deleted'), 'success');
      if ($this->_context == 'event' && $this->_eventId) {
        $url = CRM_Utils_System::url('civicrm/event/manage/reminder',
          "reset=1&action=update&id={$this->_eventId}"
        );
        $session = CRM_Core_Session::singleton();
        $session->pushUserContext($url);
      }
      return;
    }
    $values = $this->controller->exportValues($this->getName());

    $keys = array(
      'title',
      'subject',
      'absolute_date',
      'group_id',
      'record_activity'
    );
    foreach ($keys as $key) {
      $params[$key] = CRM_Utils_Array::value($key, $values);
    }

    $moreKeys = array(
      'start_action_offset', 'start_action_unit',
      'start_action_condition', 'start_action_date',
      'repetition_frequency_unit',
      'repetition_frequency_interval',
      'end_frequency_unit',
      'end_frequency_interval',
      'end_action', 'end_date',
    );

    if ($absoluteDate = CRM_Utils_Array::value('absolute_date', $params)) {
      $params['absolute_date'] = CRM_Utils_Date::processDate($absoluteDate);
      foreach ($moreKeys as $mkey) {
        $params[$mkey] = 'null';
      }
    }
    else {
      $params['absolute_date'] = 'null';
      foreach ($moreKeys as $mkey) {
        $params[$mkey] = CRM_Utils_Array::value($mkey, $values);
      }
    }

    $params['body_text'] = CRM_Utils_Array::value('text_message', $values);
    $params['body_html'] = CRM_Utils_Array::value('html_message', $values);

    if (CRM_Utils_Array::value('recipient', $values) == 'manual') {
      $params['recipient_manual'] = CRM_Utils_Array::value('recipient_manual_id', $values);
      $params['group_id'] = $params['recipient'] = $params['recipient_listing'] = 'null';
    }
    elseif (CRM_Utils_Array::value('recipient', $values) == 'group') {
      $params['group_id'] = $values['group_id'];
      $params['recipient_manual'] = $params['recipient'] = $params['recipient_listing'] = 'null';
    }
    elseif (!CRM_Utils_System::isNull($values['recipient_listing'])) {
      $params['recipient'] = CRM_Utils_Array::value('recipient', $values);
      $params['recipient_listing'] = implode(CRM_Core_DAO::VALUE_SEPARATOR,
        CRM_Utils_Array::value('recipient_listing', $values)
      );
      $params['group_id'] = $params['recipient_manual'] = 'null';
    }
    else {
      $params['recipient'] = CRM_Utils_Array::value('recipient', $values);
      $params['group_id'] = $params['recipient_manual'] = $params['recipient_listing'] = 'null';
    }

    $params['mapping_id'] = $values['entity'][0];
    $entity_value = $values['entity'][1];
    $entity_status = $values['entity'][2];

    foreach (array(
      'entity_value', 'entity_status') as $key) {
      $params[$key] = implode(CRM_Core_DAO::VALUE_SEPARATOR, $$key);
    }

    $params['is_active'] = CRM_Utils_Array::value('is_active', $values, 0);
    $params['is_repeat'] = CRM_Utils_Array::value('is_repeat', $values, 0);

    if (CRM_Utils_Array::value('is_repeat', $values) == 0) {
      $params['repetition_frequency_unit'] = 'null';
      $params['repetition_frequency_interval'] = 'null';
      $params['end_frequency_unit'] = 'null';
      $params['end_frequency_interval'] = 'null';
      $params['end_action'] = 'null';
      $params['end_date'] = 'null';
    }

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
    }
    elseif ($this->_action & CRM_Core_Action::ADD) {
      // we do this only once, so name never changes
      $params['name'] = CRM_Utils_String::munge($params['title'], '_', 64);
    }

    $composeFields = array(
      'template', 'saveTemplate',
      'updateTemplate', 'saveTemplateName',
    );
    $msgTemplate = NULL;
    //mail template is composed

    $composeParams = array();
    foreach ($composeFields as $key) {
      if (CRM_Utils_Array::value($key, $values)) {
        $composeParams[$key] = $values[$key];
      }
    }

    if (CRM_Utils_Array::value('updateTemplate', $composeParams)) {
      $templateParams = array(
        'msg_text' => $params['body_text'],
        'msg_html' => $params['body_html'],
        'msg_subject' => $params['subject'],
        'is_active' => TRUE,
      );

      $templateParams['id'] = $values['template'];

      $msgTemplate = CRM_Core_BAO_MessageTemplates::add($templateParams);
    }

    if (CRM_Utils_Array::value('saveTemplate', $composeParams)) {
      $templateParams = array(
        'msg_text' => $params['body_text'],
        'msg_html' => $params['body_html'],
        'msg_subject' => $params['subject'],
        'is_active' => TRUE,
      );

      $templateParams['msg_title'] = $composeParams['saveTemplateName'];

      $msgTemplate = CRM_Core_BAO_MessageTemplates::add($templateParams);
    }

    if (isset($msgTemplate->id)) {
      $params['msg_template_id'] = $msgTemplate->id;
    }
    else {
      $params['msg_template_id'] = CRM_Utils_Array::value('template', $values);
    }

    CRM_Core_BAO_ActionSchedule::add($params, $ids);

    $status = ts("Your new Reminder titled %1 has been saved.",
      array(1 => "<strong>{$values['title']}</strong>")
    );
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $status = ts("Your Reminder titled %1 has been updated.",
        array(1 => "<strong>{$values['title']}</strong>")
      );

      if ($this->_context == 'event' && $this->_eventId) {
        $url = CRM_Utils_System::url('civicrm/event/manage/reminder',
          "reset=1&action=update&id={$this->_eventId}"
        );
        $session = CRM_Core_Session::singleton();
        $session->pushUserContext($url);
      }
    }
    CRM_Core_Session::setStatus($status, ts('Saved'), 'success');
  }
}

