<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
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
 */

/**
 * This class generates form components for Scheduling Reminders.
 */
class CRM_Admin_Form_ScheduleReminders extends CRM_Admin_Form {

  /**
   * Scheduled Reminder ID.
   * @var int
   */
  protected $_id = NULL;

  public $_freqUnits;

  protected $_compId;

  /**
   * @return mixed
   */
  public function getComponentID() {
    return $this->_compId;
  }

  /**
   * @param mixed $compId
   */
  public function setComponentID($compId) {
    $this->_compId = $compId;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->_mappingID = $mappingID = NULL;
    $providersCount = CRM_SMS_BAO_Provider::activeProviderCount();
    $this->setContext();
    $isEvent = $this->getContext() == 'event';

    if ($isEvent) {
      $this->setComponentID(CRM_Utils_Request::retrieve('compId', 'Integer', $this));
      if (!CRM_Event_BAO_Event::checkPermission($this->getComponentID(), CRM_Core_Permission::EDIT)) {
        throw new CRM_Core_Exception(ts('You do not have permission to access this page.'));
      }
    }
    elseif (!CRM_Core_Permission::check('administer CiviCRM')) {
      throw new CRM_Core_Exception(ts('You do not have permission to access this page.'));
    }

    if ($this->_action & (CRM_Core_Action::DELETE)) {
      $reminderName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_ActionSchedule', $this->_id, 'title');
      $this->assign('reminderName', $reminderName);
      return;
    }
    elseif ($this->_action & (CRM_Core_Action::UPDATE)) {
      $this->_mappingID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_ActionSchedule', $this->_id, 'mapping_id');
    }
    if ($isEvent) {
      $isTemplate = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $this->getComponentID(), 'is_template');
      $mapping = CRM_Utils_Array::first(CRM_Core_BAO_ActionSchedule::getMappings([
        'id' => $isTemplate ? CRM_Event_ActionMapping::EVENT_TPL_MAPPING_ID : CRM_Event_ActionMapping::EVENT_NAME_MAPPING_ID,
      ]));
      if ($mapping) {
        $this->_mappingID = $mapping->getId();
      }
      else {
        throw new CRM_Core_Exception('Could not find mapping for event scheduled reminders.');
      }
    }

    if (!empty($_POST) && !empty($_POST['entity']) && empty($this->getContext())) {
      $mappingID = $_POST['entity'][0];
    }
    elseif ($this->_mappingID) {
      $mappingID = $this->_mappingID;
      if ($isEvent) {
        $this->add('hidden', 'mappingID', $mappingID);
      }
    }

    $this->add(
      'text',
      'title',
      ts('Title'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_ActionSchedule', 'title'),
      TRUE
    );

    $mappings = CRM_Core_BAO_ActionSchedule::getMappings();
    $selectedMapping = $mappings[$mappingID ? $mappingID : 1];
    $entityRecipientLabels = $selectedMapping->getRecipientTypes() + CRM_Core_BAO_ActionSchedule::getAdditionalRecipients();
    $this->assign('entityMapping', json_encode(
      CRM_Utils_Array::collectMethod('getEntity', $mappings)
    ));
    $this->assign('recipientMapping', json_encode(
      array_combine(array_keys($entityRecipientLabels), array_keys($entityRecipientLabels))
    ));

    if (!$this->getContext()) {
      $sel = &$this->add(
        'hierselect',
        'entity',
        ts('Entity'),
        [
          'name' => 'entity[0]',
          'style' => 'vertical-align: top;',
        ]
      );
      $sel->setOptions([
        CRM_Utils_Array::collectMethod('getLabel', $mappings),
        CRM_Core_BAO_ActionSchedule::getAllEntityValueLabels(),
        CRM_Core_BAO_ActionSchedule::getAllEntityStatusLabels(),
      ]);

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
    }
    else {
      // Dig deeper - this code is sublimely stupid.
      $allEntityStatusLabels = CRM_Core_BAO_ActionSchedule::getAllEntityStatusLabels();
      $options = $allEntityStatusLabels[$this->_mappingID][0];
      $attributes = ['multiple' => 'multiple', 'class' => 'crm-select2 huge', 'placeholder' => $options[0]];
      unset($options[0]);
      $this->add('select', 'entity', ts('Recipient(s)'), $options, TRUE, $attributes);
      $this->assign('context', $this->getContext());
    }

    //get the frequency units.
    $this->_freqUnits = CRM_Core_SelectValues::getRecurringFrequencyUnits();

    //reminder_interval
    $this->add('number', 'start_action_offset', ts('When'), ['class' => 'six', 'min' => 0]);
    $this->addRule('start_action_offset', ts('Value should be a positive number'), 'positiveInteger');

    $isActive = ts('Scheduled Reminder Active');
    $recordActivity = ts('Record activity for automated email');
    if ($providersCount) {
      $this->assign('sms', $providersCount);
      $recordActivity = ts('Record activity for automated email or SMS');
      $options = CRM_Core_OptionGroup::values('msg_mode');
      $this->add('select', 'mode', ts('Send as'), $options);

      $providers = CRM_SMS_BAO_Provider::getProviders(NULL, NULL, TRUE, 'is_default desc');

      $providerSelect = [];
      foreach ($providers as $provider) {
        $providerSelect[$provider['id']] = $provider['title'];
      }
      $this->add('select', 'sms_provider_id', ts('SMS Provider'), $providerSelect, TRUE);
    }

    foreach ($this->_freqUnits as $val => $label) {
      $freqUnitsDisplay[$val] = ts('%1(s)', [1 => $label]);
    }

    $this->add('datepicker', 'absolute_date', ts('Start Date'), [], FALSE, ['time' => FALSE]);

    //reminder_frequency
    $this->add('select', 'start_action_unit', ts('Frequency'), $freqUnitsDisplay, TRUE);

    $condition = [
      'before' => ts('before'),
      'after' => ts('after'),
    ];
    //reminder_action
    $this->add('select', 'start_action_condition', ts('Action Condition'), $condition);

    $this->add('select', 'start_action_date', ts('Date Field'), $selectedMapping->getDateFields(), TRUE);

    $this->addElement('checkbox', 'record_activity', $recordActivity);

    $this->addElement('checkbox', 'is_repeat', ts('Repeat'),
      NULL, ['onchange' => "return showHideByValue('is_repeat',true,'repeatFields','table-row','radio',false);"]
    );

    $this->add('select', 'repetition_frequency_unit', ts('every'), $freqUnitsDisplay);
    $this->add('number', 'repetition_frequency_interval', ts('every'), ['class' => 'six', 'min' => 0]);
    $this->addRule('repetition_frequency_interval', ts('Value should be a positive number'), 'positiveInteger');

    $this->add('select', 'end_frequency_unit', ts('until'), $freqUnitsDisplay);
    $this->add('number', 'end_frequency_interval', ts('until'), ['class' => 'six', 'min' => 0]);
    $this->addRule('end_frequency_interval', ts('Value should be a positive number'), 'positiveInteger');

    $this->add('select', 'end_action', ts('Repetition Condition'), $condition, TRUE);
    $this->add('select', 'end_date', ts('Date Field'), $selectedMapping->getDateFields(), TRUE);

    $this->add('text', 'from_name', ts('From Name'));
    $this->add('text', 'from_email', ts('From Email'));

    $recipientListingOptions = [];

    if ($mappingID) {
      $mapping = CRM_Utils_Array::first(CRM_Core_BAO_ActionSchedule::getMappings([
        'id' => $mappingID,
      ]));
    }

    $limitOptions = ['' => '-neither-', 1 => ts('Limit to'), 0 => ts('Also include')];

    $recipientLabels = ['activity' => ts('Recipients'), 'other' => ts('Limit or Add Recipients')];
    $this->assign('recipientLabels', $recipientLabels);

    $this->add('select', 'limit_to', ts('Limit Options'), $limitOptions, FALSE, ['onChange' => "showHideByValue('limit_to','','recipient', 'select','select',true);"]);

    $this->add('select', 'recipient', $recipientLabels['other'], $entityRecipientLabels,
      FALSE, ['onchange' => "showHideByValue('recipient','manual','recipientManual','table-row','select',false); showHideByValue('recipient','group','recipientGroup','table-row','select',false);"]
    );

    if (!empty($this->_submitValues['recipient_listing'])) {
      if ($this->getContext()) {
        $recipientListingOptions = CRM_Core_BAO_ActionSchedule::getRecipientListing($this->_mappingID, $this->_submitValues['recipient']);
      }
      else {
        $recipientListingOptions = CRM_Core_BAO_ActionSchedule::getRecipientListing($_POST['entity'][0], $_POST['recipient']);
      }
    }
    elseif (!empty($this->_values['recipient_listing'])) {
      $recipientListingOptions = CRM_Core_BAO_ActionSchedule::getRecipientListing($this->_values['mapping_id'], $this->_values['recipient']);
    }

    $this->add('select', 'recipient_listing', ts('Recipient Roles'), $recipientListingOptions, FALSE,
      ['multiple' => TRUE, 'class' => 'crm-select2 huge', 'placeholder' => TRUE]);

    $this->addEntityRef('recipient_manual_id', ts('Manual Recipients'), ['multiple' => TRUE, 'create' => TRUE]);

    $this->add('select', 'group_id', ts('Group'),
      CRM_Core_PseudoConstant::nestedGroup('Mailing'), FALSE, ['class' => 'crm-select2 huge']
    );

    // multilingual only options
    $multilingual = CRM_Core_I18n::isMultilingual();
    if ($multilingual) {
      $smarty = CRM_Core_Smarty::singleton();
      $smarty->assign('multilingual', $multilingual);

      $languages = CRM_Core_I18n::languages(TRUE);
      $languageFilter = $languages + [CRM_Core_I18n::NONE => ts('Contacts with no preferred language')];
      $element = $this->add('select', 'filter_contact_language', ts('Recipients language'), $languageFilter, FALSE,
        ['multiple' => TRUE, 'class' => 'crm-select2', 'placeholder' => TRUE]);

      $communicationLanguage = [
        '' => ts('System default language'),
        CRM_Core_I18n::AUTO => ts('Follow recipient preferred language'),
      ];
      $communicationLanguage = $communicationLanguage + $languages;
      $this->add('select', 'communication_language', ts('Communication language'), $communicationLanguage);
    }

    CRM_Mailing_BAO_Mailing::commonCompose($this);

    $this->add('text', 'subject', ts('Subject'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_ActionSchedule', 'subject')
    );

    $this->add('checkbox', 'is_active', $isActive);

    $this->addFormRule(['CRM_Admin_Form_ScheduleReminders', 'formRule'], $this);

    $this->setPageTitle(ts('Scheduled Reminder'));
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   * @param CRM_Admin_Form_ScheduleReminders $self
   *
   * @return array|bool
   *   True if no errors, else array of errors
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];
    if ((array_key_exists(1, $fields['entity']) && $fields['entity'][1][0] === 0) ||
      (array_key_exists(2, $fields['entity']) && $fields['entity'][2][0] == 0)
    ) {
      $errors['entity'] = ts('Please select appropriate value');
    }

    $mode = CRM_Utils_Array::value('mode', $fields, FALSE);
    if (!empty($fields['is_active']) &&
      CRM_Utils_System::isNull($fields['subject']) && (!$mode || $mode != 'SMS')
    ) {
      $errors['subject'] = ts('Subject is a required field.');
    }
    if (!empty($fields['is_active']) &&
      CRM_Utils_System::isNull(trim(strip_tags($fields['html_message']))) && (!$mode || $mode != 'SMS')
    ) {
      $errors['html_message'] = ts('The HTML message is a required field.');
    }

    if (!empty($mode) && ($mode == 'SMS' || $mode == 'User_Preference') && !empty($fields['is_active']) &&
     CRM_Utils_System::isNull(trim(strip_tags($fields['sms_text_message'])))
    ) {
      $errors['sms_text_message'] = ts('The SMS message is a required field.');
    }

    if (empty($self->getContext()) && CRM_Utils_System::isNull(CRM_Utils_Array::value(1, $fields['entity']))) {
      $errors['entity'] = ts('Please select entity value');
    }

    if (!CRM_Utils_System::isNull($fields['absolute_date'])) {
      if ($fields['absolute_date'] < date('Y-m-d')) {
        $errors['absolute_date'] = ts('Absolute date cannot be earlier than the current time.');
      }
    }
    else {
      if (CRM_Utils_System::isNull($fields['start_action_offset'])) {
        $errors['start_action_offset'] = ts('Start Action Offset must be filled in or Absolute Date set');
      }
    }
    if (!CRM_Utils_Rule::email($fields['from_email']) && (!$mode || $mode != 'SMS')) {
      $errors['from_email'] = ts('Please enter a valid email address.');
    }
    $recipientKind = [
      'participant_role' => [
        'name' => 'participant role',
        'target_id' => 'recipient_listing',
      ],
      'manual' => [
        'name' => 'recipient',
        'target_id' => 'recipient_manual_id',
      ],
    ];
    if ($fields['limit_to'] != '' && array_key_exists($fields['recipient'], $recipientKind) && empty($fields[$recipientKind[$fields['recipient']]['target_id']])) {
      $errors[$recipientKind[$fields['recipient']]['target_id']] = ts('If "Also include" or "Limit to" are selected, you must specify at least one %1', [1 => $recipientKind[$fields['recipient']]['name']]);
    }

    //CRM-21523
    if (!empty($fields['is_repeat']) &&
      (empty($fields['repetition_frequency_interval']) || ($fields['end_frequency_interval'] == NULL))
    ) {
      $errors['is_repeat'] = ts('If you are enabling repetition you must indicate the frequency and ending term.');
    }

    $self->_actionSchedule = $self->parseActionSchedule($fields);
    if ($self->_actionSchedule->mapping_id) {
      $mapping = CRM_Core_BAO_ActionSchedule::getMapping($self->_actionSchedule->mapping_id);
      CRM_Utils_Array::extend($errors, $mapping->validateSchedule($self->_actionSchedule));
    }

    if (!empty($errors)) {
      return $errors;
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * @return int
   */
  public function setDefaultValues() {
    if ($this->_action & CRM_Core_Action::ADD) {
      $defaults['is_active'] = 1;
      $defaults['mode'] = 'Email';
      $defaults['record_activity'] = 1;
    }
    else {
      $defaults = $this->_values;
      $entityValue = explode(CRM_Core_DAO::VALUE_SEPARATOR, CRM_Utils_Array::value('entity_value', $defaults));
      $entityStatus = explode(CRM_Core_DAO::VALUE_SEPARATOR, CRM_Utils_Array::value('entity_status', $defaults));
      if (empty($this->getContext())) {
        $defaults['entity'][0] = CRM_Utils_Array::value('mapping_id', $defaults);
        $defaults['entity'][1] = $entityValue;
        $defaults['entity'][2] = $entityStatus;
      }
      else {
        $defaults['entity'] = $entityStatus;
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
      if ($contactLanguage = CRM_Utils_Array::value('filter_contact_language', $defaults)) {
        $defaults['filter_contact_language'] = explode(CRM_Core_DAO::VALUE_SEPARATOR, $contactLanguage);
      }
    }

    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      // delete reminder
      CRM_Core_BAO_ActionSchedule::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected Reminder has been deleted.'), ts('Record Deleted'), 'success');
      if ($this->getContext() == 'event' && $this->getComponentID()) {
        $url = CRM_Utils_System::url('civicrm/event/manage/reminder',
          "reset=1&action=browse&id=" . $this->getComponentID() . "&component=" . $this->getContext() . "&setTab=1"
        );
        $session = CRM_Core_Session::singleton();
        $session->pushUserContext($url);
      }
      return;
    }
    $values = $this->controller->exportValues($this->getName());
    if (empty($this->_actionSchedule)) {
      $bao = $this->parseActionSchedule($values)->save();
    }
    else {
      $bao = $this->_actionSchedule->save();
    }

    // we need to set this on the form so that hooks can identify the created entity
    $this->set('id', $bao->id);
    $bao->free();

    $status = ts("Your new Reminder titled %1 has been saved.",
      [1 => "<strong>{$values['title']}</strong>"]
    );

    if ($this->_action) {
      if ($this->_action & CRM_Core_Action::UPDATE) {
        $status = ts("Your Reminder titled %1 has been updated.",
          [1 => "<strong>{$values['title']}</strong>"]
        );
      }

      if ($this->getContext() == 'event' && $this->getComponentID()) {
        $url = CRM_Utils_System::url('civicrm/event/manage/reminder', "reset=1&action=browse&id=" . $this->getComponentID() . "&component=" . $this->getContext() . "&setTab=1");
        $session = CRM_Core_Session::singleton();
        $session->pushUserContext($url);
      }
    }
    CRM_Core_Session::setStatus($status, ts('Saved'), 'success');
  }

  /**
   * @param array $values
   *   The submitted form values.
   * @return CRM_Core_DAO_ActionSchedule
   */
  public function parseActionSchedule($values) {
    $params = [];

    $keys = [
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
    ];
    foreach ($keys as $key) {
      $params[$key] = CRM_Utils_Array::value($key, $values);
    }

    $params['is_repeat'] = CRM_Utils_Array::value('is_repeat', $values, 0);

    $moreKeys = [
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
    ];

    if (!CRM_Utils_Array::value('absolute_date', $params)) {
      $params['absolute_date'] = 'null';
    }
    foreach ($moreKeys as $mkey) {
      if ($params['absolute_date'] != 'null' && CRM_Utils_String::startsWith($mkey, 'start_action')) {
        $params[$mkey] = 'null';
        continue;
      }
      $params[$mkey] = CRM_Utils_Array::value($mkey, $values);
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
    elseif (isset($values['recipient_listing']) && isset($values['limit_to']) && !CRM_Utils_System::isNull($values['recipient_listing']) && !CRM_Utils_System::isNull($values['limit_to'])) {
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

    if (!empty($this->_mappingID) && !empty($this->getComponentID())) {
      $params['mapping_id'] = $this->_mappingID;
      $params['entity_value'] = $this->getComponentID();
      $params['entity_status'] = implode(CRM_Core_DAO::VALUE_SEPARATOR, $values['entity']);
    }
    else {
      $params['mapping_id'] = $values['entity'][0];
      if ($params['mapping_id'] == 1) {
        $params['limit_to'] = 1;
      }

      $entity_value = CRM_Utils_Array::value(1, $values['entity'], []);
      $entity_status = CRM_Utils_Array::value(2, $values['entity'], []);
      $params['entity_value'] = implode(CRM_Core_DAO::VALUE_SEPARATOR, $entity_value);
      $params['entity_status'] = implode(CRM_Core_DAO::VALUE_SEPARATOR, $entity_status);
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

    // multilingual options
    $params['filter_contact_language'] = CRM_Utils_Array::value('filter_contact_language', $values, []);
    $params['filter_contact_language'] = implode(CRM_Core_DAO::VALUE_SEPARATOR, $params['filter_contact_language']);
    $params['communication_language'] = CRM_Utils_Array::value('communication_language', $values, NULL);

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
    }
    elseif ($this->_action & CRM_Core_Action::ADD) {
      // we do this only once, so name never changes
      $params['name'] = CRM_Utils_String::munge($params['title'], '_', 64);
    }

    $modePrefixes = ['Mail' => NULL, 'SMS' => 'SMS'];

    if ($params['mode'] == 'Email' || empty($params['sms_provider_id'])) {
      unset($modePrefixes['SMS']);
    }
    elseif ($params['mode'] == 'SMS') {
      unset($modePrefixes['Mail']);
    }

    //TODO: handle postprocessing of SMS and/or Email info based on $modePrefixes

    $composeFields = [
      'template',
      'saveTemplate',
      'updateTemplate',
      'saveTemplateName',
    ];
    $msgTemplate = NULL;
    //mail template is composed

    foreach ($modePrefixes as $prefix) {
      $composeParams = [];
      foreach ($composeFields as $key) {
        $key = $prefix . $key;
        if (!empty($values[$key])) {
          $composeParams[$key] = $values[$key];
        }
      }

      if (!empty($composeParams[$prefix . 'updateTemplate'])) {
        $templateParams = ['is_active' => TRUE];
        if ($prefix == 'SMS') {
          $templateParams += [
            'msg_text' => $params['sms_body_text'],
            'is_sms' => TRUE,
          ];
        }
        else {
          $templateParams += [
            'msg_text' => $params['body_text'],
            'msg_html' => $params['body_html'],
            'msg_subject' => $params['subject'],
          ];
        }
        $templateParams['id'] = $values[$prefix . 'template'];

        $msgTemplate = CRM_Core_BAO_MessageTemplate::add($templateParams);
      }

      if (!empty($composeParams[$prefix . 'saveTemplate'])) {
        $templateParams = ['is_active' => TRUE];
        if ($prefix == 'SMS') {
          $templateParams += [
            'msg_text' => $params['sms_body_text'],
            'is_sms' => TRUE,
          ];
        }
        else {
          $templateParams += [
            'msg_text' => $params['body_text'],
            'msg_html' => $params['body_html'],
            'msg_subject' => $params['subject'],
          ];
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

    $actionSchedule = new CRM_Core_DAO_ActionSchedule();
    $actionSchedule->copyValues($params);
    return $actionSchedule;
  }

  /**
   * List available tokens for this form.
   *
   * @return array
   */
  public function listTokens() {
    $tokens = CRM_Core_SelectValues::contactTokens();
    $tokens = array_merge(CRM_Core_SelectValues::activityTokens(), $tokens);
    $tokens = array_merge(CRM_Core_SelectValues::eventTokens(), $tokens);
    $tokens = array_merge(CRM_Core_SelectValues::membershipTokens(), $tokens);
    $tokens = array_merge(CRM_Core_SelectValues::contributionTokens(), $tokens);
    return $tokens;
  }

}
