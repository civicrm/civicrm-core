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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This class generates form components for processing Event.
 */
class CRM_Event_Form_ManageEvent_EventInfo extends CRM_Event_Form_ManageEvent {

  /**
   * Event type.
   */
  protected $_eventType = NULL;

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    parent::preProcess();

    if ($this->_id) {
      $this->assign('entityID', $this->_id);
      $eventType = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event',
        $this->_id,
        'event_type_id'
      );
    }
    else {
      $eventType = 'null';
    }

    $showLocation = FALSE;
    // when custom data is included in this page
    if (!empty($_POST['hidden_custom'])) {
      $this->set('type', 'Event');
      $this->set('subType', CRM_Utils_Array::value('event_type_id', $_POST));
      $this->assign('customDataSubType', CRM_Utils_Array::value('event_type_id', $_POST));
      $this->set('entityId', $this->_id);

      CRM_Custom_Form_CustomData::preProcess($this, NULL, $this->_eventType, 1, 'Event', $this->_id);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
    }
  }

  /**
   * Set default values for the form.
   *
   * For edit/view mode he default values are retrieved from the database.
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    // in update mode, we need to set custom data subtype to tpl
    if (!empty($defaults['event_type_id'])) {
      $this->assign('customDataSubType', $defaults['event_type_id']);
    }

    $this->_showHide = new CRM_Core_ShowHideBlocks();
    // Show waitlist features or event_full_text if max participants set
    if (!empty($defaults['max_participants'])) {
      $this->_showHide->addShow('id-waitlist');
      if (!empty($defaults['has_waitlist'])) {
        $this->_showHide->addShow('id-waitlist-text');
        $this->_showHide->addHide('id-event_full');
      }
      else {
        $this->_showHide->addHide('id-waitlist-text');
        $this->_showHide->addShow('id-event_full');
      }
    }
    else {
      $this->_showHide->addHide('id-event_full');
      $this->_showHide->addHide('id-waitlist');
      $this->_showHide->addHide('id-waitlist-text');
    }

    $this->_showHide->addToTemplate();
    $this->assign('elemType', 'table-row');

    $this->assign('description', CRM_Utils_Array::value('description', $defaults));

    // Provide suggested text for event full and waitlist messages if they're empty
    $defaults['event_full_text'] = CRM_Utils_Array::value('event_full_text', $defaults, ts('This event is currently full.'));

    $defaults['waitlist_text'] = CRM_Utils_Array::value('waitlist_text', $defaults, ts('This event is currently full. However you can register now and get added to a waiting list. You will be notified if spaces become available.'));
    list($defaults['start_date'], $defaults['start_date_time']) = CRM_Utils_Date::setDateDefaults(CRM_Utils_Array::value('start_date', $defaults), 'activityDateTime');

    if (!empty($defaults['end_date'])) {
      list($defaults['end_date'], $defaults['end_date_time']) = CRM_Utils_Date::setDateDefaults($defaults['end_date'], 'activityDateTime');
    }
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    //need to assign custom data type and subtype to the template
    $this->assign('customDataType', 'Event');
    if ($this->_eventType) {
      $this->assign('customDataSubType', $this->_eventType);
    }
    $this->assign('entityId', $this->_id);

    $this->_first = TRUE;
    $this->applyFilter('__ALL__', 'trim');
    $attributes = CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event');

    if ($this->_isTemplate) {
      $this->add('text', 'template_title', ts('Template Title'), $attributes['template_title'], TRUE);
    }

    if ($this->_action & CRM_Core_Action::ADD) {
      $eventTemplates = CRM_Event_PseudoConstant::eventTemplates();
      if (CRM_Utils_System::isNull($eventTemplates) && !$this->_isTemplate) {
        $url = CRM_Utils_System::url('civicrm/admin/eventTemplate', array('reset' => 1));
        CRM_Core_Session::setStatus(ts('If you find that you are creating multiple events with similar settings, you may want to use the <a href="%1">Event Templates</a> feature to streamline your workflow.', array(1 => $url)), ts('Tip'), 'info');
      }
      if (!CRM_Utils_System::isNull($eventTemplates)) {
        $this->add('select', 'template_id', ts('From Template'), array('' => ts('- select -')) + $eventTemplates, FALSE, array('class' => 'crm-select2 huge'));
      }
      // Make sure this form redirects properly
      $this->preventAjaxSubmit();
    }

    // add event title, make required if this is not a template
    $this->add('text', 'title', ts('Event Title'), $attributes['event_title'], !$this->_isTemplate);

    $this->addSelect('event_type_id',
      array('onChange' => "CRM.buildCustomData( 'Event', this.value );"),
      TRUE
    );

    //CRM-7362 --add campaigns.
    $campaignId = NULL;
    if ($this->_id) {
      $campaignId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $this->_id, 'campaign_id');
    }
    CRM_Campaign_BAO_Campaign::addCampaign($this, $campaignId);

    $this->addSelect('default_role_id', array(), TRUE);

    $this->addSelect('participant_listing_id', array('placeholder' => ts('Disabled'), 'option_url' => NULL));

    $this->add('textarea', 'summary', ts('Event Summary'), $attributes['summary']);
    $this->add('wysiwyg', 'description', ts('Complete Description'), $attributes['event_description']);
    $this->addElement('checkbox', 'is_public', ts('Public Event'));
    $this->addElement('checkbox', 'is_share', ts('Allow sharing through social media?'));
    $this->addElement('checkbox', 'is_map', ts('Include Map to Event Location'));

    $this->addDateTime('start_date', ts('Start Date'), FALSE, array('formatType' => 'activityDateTime'));
    $this->addDateTime('end_date', ts('End Date / Time'), FALSE, array('formatType' => 'activityDateTime'));

    $this->add('text', 'max_participants', ts('Max Number of Participants'),
      array('onchange' => "if (this.value != '') {cj('#id-waitlist').show(); showHideByValue('has_waitlist','0','id-waitlist-text','table-row','radio',false); showHideByValue('has_waitlist','0','id-event_full','table-row','radio',true); return;} else {cj('#id-event_full, #id-waitlist, #id-waitlist-text').hide(); return;}")
    );
    $this->addRule('max_participants', ts('Max participants should be a positive number'), 'positiveInteger');

    $participantStatuses = CRM_Event_PseudoConstant::participantStatus();
    $waitlist = 0;
    if (in_array('On waitlist', $participantStatuses) and in_array('Pending from waitlist', $participantStatuses)) {
      $this->addElement('checkbox', 'has_waitlist', ts('Offer a Waitlist?'), NULL, array('onclick' => "showHideByValue('has_waitlist','0','id-event_full','table-row','radio',true); showHideByValue('has_waitlist','0','id-waitlist-text','table-row','radio',false);"));
      $this->add('textarea', 'waitlist_text', ts('Waitlist Message'), $attributes['waitlist_text']);
      $waitlist = 1;
    }
    $this->assign('waitlist', $waitlist);

    $this->add('textarea', 'event_full_text', ts('Message if Event Is Full'), $attributes['event_full_text']);

    $this->addElement('checkbox', 'is_active', ts('Is this Event Active?'));

    $this->addFormRule(array('CRM_Event_Form_ManageEvent_EventInfo', 'formRule'));

    parent::buildQuickForm();
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $values
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($values) {
    $errors = array();

    if (!$values['is_template']) {
      if (CRM_Utils_System::isNull($values['start_date'])) {
        $errors['start_date'] = ts('Start Date and Time are required fields');
      }
      else {
        $start = CRM_Utils_Date::processDate($values['start_date']);
        $end = CRM_Utils_Date::processDate($values['end_date']);
        if (($end < $start) && ($end != 0)) {
          $errors['end_date'] = ts('End date should be after Start date.');
        }
      }
    }

    //CRM-4286
    if (strstr($values['title'], '/')) {
      $errors['title'] = ts("Please do not use '/' in Event Title.");
    }

    return $errors;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    //format params
    $params['start_date'] = CRM_Utils_Date::processDate($params['start_date'], $params['start_date_time']);
    $params['end_date'] = CRM_Utils_Date::processDate(CRM_Utils_Array::value('end_date', $params),
      CRM_Utils_Array::value('end_date_time', $params),
      TRUE
    );
    $params['has_waitlist'] = CRM_Utils_Array::value('has_waitlist', $params, FALSE);
    $params['is_map'] = CRM_Utils_Array::value('is_map', $params, FALSE);
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['is_public'] = CRM_Utils_Array::value('is_public', $params, FALSE);
    $params['is_share'] = CRM_Utils_Array::value('is_share', $params, FALSE);
    $params['default_role_id'] = CRM_Utils_Array::value('default_role_id', $params, FALSE);
    $params['id'] = $this->_id;

    $customFields = CRM_Core_BAO_CustomField::getFields('Event', FALSE, FALSE,
      CRM_Utils_Array::value('event_type_id', $params)
    );
    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
      $this->_id,
      'Event'
    );

    //merge params with defaults from templates
    if (!empty($params['template_id'])) {
      $params = array_merge(CRM_Event_BAO_Event::getTemplateDefaultValues($params['template_id']), $params);
    }

    $event = CRM_Event_BAO_Event::create($params);

    // now that we have the event’s id, do some more template-based stuff
    if (!empty($params['template_id'])) {
      CRM_Event_BAO_Event::copy($params['template_id'], $event, TRUE);
    }

    $this->set('id', $event->id);

    $this->postProcessHook();

    if ($this->_action & CRM_Core_Action::ADD) {
      $url = 'civicrm/event/manage/location';
      $urlParams = "action=update&reset=1&id={$event->id}";
      // special case for 'Save and Done' consistency.
      if ($this->controller->getButtonName('submit') == '_qf_EventInfo_upload_done') {
        $url = 'civicrm/event/manage';
        $urlParams = 'reset=1';
        CRM_Core_Session::setStatus(ts("'%1' information has been saved.",
          array(1 => $this->getTitle())
        ), ts('Saved'), 'success');
      }

      CRM_Utils_System::redirect(CRM_Utils_System::url($url, $urlParams));
    }

    parent::endPostProcess();
  }

  /**
   * Return a descriptive name for the page, used in wizard header.
   *
   * @return string
   */
  public function getTitle() {
    return ts('Event Information and Settings');
  }

}
