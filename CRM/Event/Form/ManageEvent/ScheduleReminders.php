<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class generates form components for scheduling reminders for Event
 *
 */
class CRM_Event_Form_ManageEvent_ScheduleReminders extends CRM_Event_Form_ManageEvent {

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  function preProcess() {
    parent::preProcess();

    $newReminder = CRM_Utils_Request::retrieve('new', 'Boolean', $this, FALSE, FALSE);
    if ($this->_action & CRM_Core_Action::UPDATE &&
      !$newReminder
    ) {
      $field = 'civicrm_event';
      if ($this->_isTemplate) {
        $field = 'event_template';
      }
      $reminderList = CRM_Core_BAO_ActionSchedule::getList(FALSE, $field, $this->_id );
      if ($reminderList && is_array($reminderList)) {
        // Add action links to each of the reminders
        foreach ($reminderList as & $format) {
          $action = CRM_Core_Action::UPDATE + CRM_Core_Action::DELETE;
          if ($format['is_active']) {
            $action += CRM_Core_Action::DISABLE;
          }
          else {
            $action += CRM_Core_Action::ENABLE;
          }
          $scheduleReminder = new CRM_Admin_Page_ScheduleReminders;
          $links = $scheduleReminder->links();
          $links[CRM_Core_Action::DELETE]['qs'] .= "&context=event&eventId={$this->_id}";
          $links[CRM_Core_Action::UPDATE]['qs'] .= "&context=event&eventId={$this->_id}";
          $format['action'] = CRM_Core_Action::formLink(
            $links,
            $action,
            array('id' => $format['id']),
            ts('more'),
            FALSE,
            'event.reminder.list',
            'Event',
            $this->_id
          );
        }
      }
      else {
        $reminderList = TRUE;
      }
      $this->assign('rows', $reminderList);

      // Update tab "disabled" css class
      $this->ajaxResponse['tabValid'] = !empty($reminderList) && is_array($reminderList);
    }
    $this->setPageTitle(ts('Scheduled Reminder'));
  }

  /**
   * This function sets the default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
    $defaults = array();
    $defaults['is_active'] = 1;
    $defaults['record_activity'] = 1;
    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    $field = 'civicrm_event';
    if ($this->_isTemplate) {
      $field = 'event_template';
    }
    $this->_mappingID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_ActionMapping', $field, 'id', 'entity_value');
    if (!$this->_mappingID) {
      CRM_Core_Error::fatal('Could not find mapping for event scheduled reminders.');
    }

    parent::buildQuickForm();
    $this->add('text', 'title', ts('Reminder Name'),
      array(
        'size' => 45,
        'maxlength' => 128
      ), TRUE
    );

    $selectionOptions = CRM_Core_BAO_ActionSchedule::getSelection($this->_mappingID);
    extract($selectionOptions);

    $this->assign('recipientMapping', json_encode($recipientMapping));

    // Fixme: hack to adjust the output of CRM_Core_BAO_ActionSchedule::getSelection so it looks nice with the jQuery.select2 plugin
    // TODO: fix this upstream
    $options = $sel3[$this->_mappingID][0];
    $attributes = array('multiple' => 'multiple', 'class' => 'crm-select2 huge', 'placeholder' => $options[0]);
    unset($options[0]);
    $entity = $this->add('select', 'entity', ts('Recipient(s)'), $options, TRUE, $attributes);

    //get the frequency units.
    $this->_freqUnits = array('hour' => 'hour') + CRM_Core_OptionGroup::values('recur_frequency_units');

    $numericOptions = CRM_Core_SelectValues::getNumericOptions(0, 30);

    //reminder_interval
    $this->add('select', 'start_action_offset', ts('When'), $numericOptions);

    foreach ($this->_freqUnits as $val => $label) {
      $freqUnitsDisplay[$val] = ts('%1(s)', array(1 => $label));
    }

    $this->addDate('absolute_date', ts('Start Date'), FALSE,
      array('formatType' => 'mailing')
    );

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

    $recipient = 'event_contacts';
    $limitOptions = array(1 => ts('Limit to'), 0 => ts('Addition to'));
    $this->add('select', 'limit_to', ts('Limit Options'), $limitOptions);

    $this->add('select', 'recipient', ts('Recipients'), $sel5[$recipient],
      FALSE, array('onchange' => "showHideByValue('recipient','manual','recipientManual','table-row','select',false); showHideByValue('recipient','group','recipientGroup','table-row','select',false);")
    );
    $recipientListing = $this->add('select', 'recipient_listing', ts('Recipient Roles'),
      $sel3[$this->_mappingID][0], FALSE, array('multiple' => TRUE, 'class' => 'crm-select2 huge', 'placeholder' => TRUE)
    );

    $this->addEntityRef('recipient_manual_id', ts('Manual Recipients'), array('multiple' => true));

    $this->add('select', 'group_id', ts('Group'),
      CRM_Core_PseudoConstant::nestedGroup(), FALSE, array('class' => 'crm-select2 huge')
    );

    CRM_Mailing_BAO_Mailing::commonCompose($this);

    $this->add('text', 'subject', ts('Subject'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_ActionSchedule', 'subject')
    );

    $this->add('checkbox', 'is_active', ts('Send email'));

    $this->addFormRule(array('CRM_Event_Form_ManageEvent_ScheduleReminders', 'formRule'));
  }

  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($fields) {
    $errors = array();
    if (!empty($fields['is_active']) &&
      CRM_Utils_System::isNull($fields['subject'])
    ) {
      $errors['subject'] = ts('Subject is a required field.');
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
      return;
    }

    $values = $this->controller->exportValues($this->getName());
    $keys = array(
      'title',
      'subject',
      'absolute_date',
      'group_id',
      'record_activity',
      'limit_to'
    );
    foreach ($keys as $key) {
      $params[$key] = CRM_Utils_Array::value($key, $values);
    }

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

    $params['mapping_id']   = $this->_mappingID;

    $params['entity_value'] = $this->_id;
    $params['entity_status'] = implode(CRM_Core_DAO::VALUE_SEPARATOR, $values['entity']);
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
    $params['name'] = CRM_Utils_String::munge($params['title'], '_', 64);

    $composeFields = array(
      'template', 'saveTemplate',
      'updateTemplate', 'saveTemplateName',
    );
    $msgTemplate = NULL;
    //mail template is composed
    $composeParams = array();
    foreach ($composeFields as $key) {
      if (!empty($values[$key])) {
        $composeParams[$key] = $values[$key];
      }
    }

    if (!empty($composeParams['updateTemplate'])) {
      $templateParams = array(
        'msg_text' => $params['body_text'],
        'msg_html' => $params['body_html'],
        'msg_subject' => $params['subject'],
        'is_active' => TRUE,
      );

      $templateParams['id'] = $values['template'];

      $msgTemplate = CRM_Core_BAO_MessageTemplate::add($templateParams);
    }

    if (!empty($composeParams['saveTemplate'])) {
      $templateParams = array(
        'msg_text' => $params['body_text'],
        'msg_html' => $params['body_html'],
        'msg_subject' => $params['subject'],
        'is_active' => TRUE,
      );

      $templateParams['msg_title'] = $composeParams['saveTemplateName'];

      $msgTemplate = CRM_Core_BAO_MessageTemplate::add($templateParams);
    }

    if (isset($msgTemplate->id)) {
      $params['msg_template_id'] = $msgTemplate->id;
    }
    else {
      $params['msg_template_id'] = CRM_Utils_Array::value('template', $values);
    }

    CRM_Core_BAO_ActionSchedule::add($params);

    $status = ts("Your new Reminder titled %1 has been saved.",
      array(1 => "<strong>{$values['title']}</strong>")
    );

    CRM_Core_Session::setStatus($status, ts('Saved'), 'success');

    parent::endPostProcess();
  }
  //end of function

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Event Schedule Reminder');
  }
}

