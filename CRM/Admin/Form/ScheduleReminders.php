<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
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
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->_mappingID = $mappingID = NULL;
    $providersCount = CRM_SMS_BAO_Provider::activeProviderCount();

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

    if (!empty($_POST) && !empty($_POST['entity'])) {
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

    $sel = & $this->add(
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
    $this->_freqUnits = CRM_Core_SelectValues::getScheduleReminderFrequencyUnits();

    //pass the mapping ID in UPDATE mode
    $mappings = CRM_Core_BAO_ActionSchedule::getMapping($mappingID);

    $numericOptions = CRM_Core_SelectValues::getNumericOptions(0, 30);

    //reminder_interval
    $this->add('select', 'start_action_offset', ts('When'), $numericOptions);
    $isActive = ts('Send email');
    $recordActivity = ts('Record activity for automated email');
    if ($providersCount) {
      $this->assign('sms', $providersCount);
      $isActive = ts('Send email or SMS');
      $recordActivity = ts('Record activity for automated email or SMS');
      $options = CRM_Core_OptionGroup::values('msg_mode');
      $this->add('select', 'mode', ts('Send as'), $options);

      $providers = CRM_SMS_BAO_Provider::getProviders(NULL, NULL, TRUE, 'is_default desc');

      $providerSelect = array();
      foreach ($providers as $provider) {
        $providerSelect[$provider['id']] = $provider['title'];
      }
      $this->add('select', 'sms_provider_id', ts('SMS Provider'), $providerSelect, TRUE);
    }

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

    $this->addElement('checkbox', 'record_activity', $recordActivity);

    $this->addElement('checkbox', 'is_repeat', ts('Repeat'),
      NULL, array('onchange' => "return showHideByValue('is_repeat',true,'repeatFields','table-row','radio',false);")
    );

    $this->add('select', 'repetition_frequency_unit', ts('every'), $freqUnitsDisplay);
    $this->add('select', 'repetition_frequency_interval', ts('every'), $numericOptions);
    $this->add('select', 'end_frequency_unit', ts('until'), $freqUnitsDisplay);
    $this->add('select', 'end_frequency_interval', ts('until'), $numericOptions);
    $this->add('select', 'end_action', ts('Repetition Condition'), $condition, TRUE);
    $this->add('select', 'end_date', ts('Date Field'), $sel4, TRUE);

    $this->add('text', 'from_name', ts('From Name'));
    $this->add('text', 'from_email', ts('From Email'));

    $recipient = 'activity_contacts';
    $recipientListingOptions = array();

    if ($mappingID) {
      $recipient = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_ActionMapping',
        $mappingID,
        'entity_recipient'
      );
    }

    $limitOptions = array(1 => ts('Limit to'), 0 => ts('Also include'));
    $this->add('select', 'limit_to', ts('Limit Options'), $limitOptions);

    $this->add('select', 'recipient', ts('Recipients'), $sel5[$recipient],
      FALSE, array('onchange' => "showHideByValue('recipient','manual','recipientManual','table-row','select',false); showHideByValue('recipient','group','recipientGroup','table-row','select',false);")
    );

    if (!empty($_POST['is_recipient_listing'])) {
      $recipientListingOptions = CRM_Core_BAO_ActionSchedule::getRecipientListing($_POST['entity'][0], $_POST['recipient']);
    }
    elseif (!empty($this->_values['recipient_listing'])) {
      $recipientListingOptions = CRM_Core_BAO_ActionSchedule::getRecipientListing($this->_values['mapping_id'], $this->_values['recipient']);
    }
    $this->add('select', 'recipient_listing', ts('Recipient Roles'), $recipientListingOptions, FALSE,
      array('multiple' => TRUE, 'class' => 'crm-select2 huge', 'placeholder' => TRUE));
    $this->add('hidden', 'is_recipient_listing', (int) !empty($recipientListingOptions));

    $this->addEntityRef('recipient_manual_id', ts('Manual Recipients'), array('multiple' => TRUE, 'create' => TRUE));

    $this->add('select', 'group_id', ts('Group'),
      CRM_Core_PseudoConstant::nestedGroup(), FALSE, array('class' => 'crm-select2 huge')
    );

    CRM_Mailing_BAO_Mailing::commonCompose($this);

    $this->add('text', 'subject', ts('Subject'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_ActionSchedule', 'subject')
    );

    $this->add('checkbox', 'is_active', $isActive);

    $this->addFormRule(array('CRM_Admin_Form_ScheduleReminders', 'formRule'));

    $this->setPageTitle(ts('Scheduled Reminder'));
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
    if ((array_key_exists(1, $fields['entity']) && $fields['entity'][1][0] === 0) ||
      (array_key_exists(2, $fields['entity']) && $fields['entity'][2][0] == 0)
    ) {
      $errors['entity'] = ts('Please select appropriate value');
    }

    if (array_key_exists(1, $fields['entity']) && !is_numeric($fields['entity'][1][0])) {
      if (count($fields['entity'][1]) > 1) {
        $errors['entity'] = ts('You may only select one contact field per reminder');
      }
      elseif (!(array_key_exists(2, $fields['entity']) && $fields['entity'][2][0] > 0)) {
        $errors['entity'] = ts('Please select whether the reminder is sent each year.');
      }
    }

    if (!empty($fields['is_active']) && $fields['mode'] != 'SMS' &&
      CRM_Utils_System::isNull($fields['subject'])
    ) {
      $errors['subject'] = ts('Subject is a required field.');
    }

    if (CRM_Utils_System::isNull(CRM_Utils_Array::value(1, $fields['entity']))) {
      $errors['entity'] = ts('Please select entity value');
    }

    if (!CRM_Utils_System::isNull($fields['absolute_date'])) {
      if (CRM_Utils_Date::format(CRM_Utils_Date::processDate($fields['absolute_date'], NULL)) < CRM_Utils_Date::format(date('Ymd'))) {
        $errors['absolute_date'] = ts('Absolute date cannot be earlier than the current time.');
      }
    }

    if (!empty($errors)) {
      return $errors;
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * @return int
   */
  function setDefaultValues() {
    if ($this->_action & CRM_Core_Action::ADD) {
      $defaults['is_active'] = 1;
      $defaults['mode'] = 'Email';
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
      $defaults['sms_text_message'] = CRM_Utils_Array::value('sms_body_text', $defaults);
      $defaults['template'] = CRM_Utils_Array::value('msg_template_id', $defaults);
      $defaults['SMStemplate'] = CRM_Utils_Array::value('sms_template_id', $defaults);
      if (!empty($defaults['group_id'])) {
        $defaults['recipient'] = 'group';
      }
      elseif (!empty($defaults['recipient_manual'])) {
        $defaults['recipient'] = 'manual';
        $defaults['recipient_manual_id'] = $defaults['recipient_manual'];
      }
    }

    return $defaults;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return void
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
      'record_activity',
      'limit_to',
      'mode',
      'sms_provider_id',
      'from_name',
      'from_email',
    );
    foreach ($keys as $key) {
      $params[$key] = CRM_Utils_Array::value($key, $values);
    }

    $params['is_repeat'] = CRM_Utils_Array::value('is_repeat', $values, 0);

    $moreKeys = array(
      'start_action_offset',
      'start_action_unit',
      'start_action_condition',
      'start_action_date',
      'repetition_frequency_unit',
      'repetition_frequency_interval',
      'end_frequency_unit',
      'end_frequency_interval',
      'end_action',
      'end_date',
    );

    if ($absoluteDate = CRM_Utils_Array::value('absolute_date', $params)) {
      $params['absolute_date'] = CRM_Utils_Date::processDate($absoluteDate);
      $params['is_repeat'] = 0;
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
    $params['sms_body_text'] = CRM_Utils_Array::value('sms_text_message', $values);
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

    $modePrefixes = array('Mail' => NULL, 'SMS' => 'SMS');

    if ($params['mode'] == 'Email' || empty($params['sms_provider_id'])) {
      unset($modePrefixes['SMS']);
    }
    elseif ($params['mode'] == 'SMS') {
      unset($modePrefixes['Mail']);
    }

    //TODO: handle postprocessing of SMS and/or Email info based on $modePrefixes

    $composeFields = array(
      'template', 'saveTemplate',
      'updateTemplate', 'saveTemplateName',
    );
    $msgTemplate = NULL;
    //mail template is composed

    foreach ($modePrefixes as $prefix) {
      $composeParams = array();
      foreach ($composeFields as $key) {
        $key = $prefix . $key;
        if (!empty($values[$key])) {
          $composeParams[$key] = $values[$key];
        }
      }

      if (!empty($composeParams[$prefix . 'updateTemplate'])) {
        $templateParams = array('is_active' => TRUE);
        if ($prefix == 'SMS') {
          $templateParams += array(
            'msg_text' => $params['sms_body_text'],
            'is_sms' => TRUE,
        );
        }
        else {
          $templateParams += array(
            'msg_text' => $params['body_text'],
            'msg_html' => $params['body_html'],
            'msg_subject' => $params['subject'],
          );
        }
        $templateParams['id'] = $values[$prefix . 'template'];

        $msgTemplate = CRM_Core_BAO_MessageTemplate::add($templateParams);
      }

      if (!empty($composeParams[$prefix . 'saveTemplate'])) {
        $templateParams = array('is_active' => TRUE);
        if ($prefix == 'SMS') {
          $templateParams += array(
            'msg_text' => $params['sms_body_text'],
            'is_sms' => TRUE,
          );
        }
        else {
          $templateParams += array(
            'msg_text' => $params['body_text'],
            'msg_html' => $params['body_html'],
            'msg_subject' => $params['subject'],
          );
        }
        $templateParams['msg_title'] = $composeParams[$prefix . 'saveTemplateName'];

        $msgTemplate = CRM_Core_BAO_MessageTemplate::add($templateParams);
      }

      if ($prefix == 'SMS') {
        if (isset($msgTemplate->id)) {
          $params['sms_template_id'] = $msgTemplate->id;
        }
        else {
          $params['sms_template_id'] = CRM_Utils_Array::value('SMStemplate', $values);
        }
      }
      else {
        if (isset($msgTemplate->id)) {
          $params['msg_template_id'] = $msgTemplate->id;
        }
        else {
          $params['msg_template_id'] = CRM_Utils_Array::value('template', $values);
        }
      }
    }

    $bao = CRM_Core_BAO_ActionSchedule::add($params);
    // we need to set this on the form so that hooks can identify the created entity
    $this->set('id', $bao->id);
    $bao->free();

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

