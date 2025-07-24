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

use Civi\Token\TokenProcessor;

/**
 * ActionSchedule (aka Scheduled Reminder) create/edit/delete form.
 */
class CRM_Admin_Form_ScheduleReminders extends CRM_Admin_Form {

  protected $retrieveMethod = 'api4';

  /**
   * Temporary override to solve https://lab.civicrm.org/dev/core/-/issues/4971
   * This regressed in https://github.com/civicrm/civicrm-core/pull/27003 which
   * switched $this->retrieveMethod to 'api' - this had the unintended effect of checking
   * permissions during retrieveValues(), but the API is not sophisticated enough: we need to add
   * a `CRM_Core_BAO_ActionSchedule::addSelectWhereClause()` function that can handle the logic
   * of "if the reminder is for an event, check user has edit permission for that specific event".
   *
   * Meanwhile we can skip permission checks in the form layer, because that logic is implemented here,
   * specifically in `\CRM_Event_ActionMapping::checkAccess()`.
   *
   * @return array
   */
  protected function retrieveValues(): array {
    $this->_values = [];
    if (isset($this->_id) && CRM_Utils_Rule::positiveInteger($this->_id)) {
      $this->_values = civicrm_api4($this->getDefaultEntity(), 'get', [
        'checkPermissions' => FALSE,
        'where' => [['id', '=', $this->_id]],
      ])->single();
    }
    return $this->_values;
  }

  /**
   * @return string
   */
  public function getDefaultEntity(): string {
    return 'ActionSchedule';
  }

  /**
   * @return array
   */
  protected function getFieldsToExcludeFromPurification(): array {
    return ['body_html', 'html_message'];
  }

  /**
   * Because `CRM_Mailing_BAO_Mailing::commonCompose` uses different fieldNames than `CRM_Core_DAO_ActionSchedule`.
   * @var array
   */
  private static $messageFieldMap = [
    'text_message' => 'body_text',
    'html_message' => 'body_html',
    'sms_text_message' => 'sms_body_text',
    'template' => 'msg_template_id',
    'SMStemplate' => 'sms_template_id',
  ];

  /**
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    parent::preProcess();
    // Pre-selected mapping_id and entity_value for embedded forms
    if (!$this->_id) {
      if (CRM_Utils_Request::retrieve('mapping_id', 'Alphanumeric', $this, FALSE, NULL, 'GET')) {
        $this->_values['mapping_id'] = $this->get('mapping_id');
      }
      if (CRM_Utils_Request::retrieve('entity_value', 'CommaSeparatedIntegers', $this, FALSE, NULL, 'GET')) {
        $this->_values['entity_value'] = explode(',', $this->get('entity_value'));
      }
    }
    if (!empty($this->_values['mapping_id'])) {
      $mapping = CRM_Core_BAO_ActionSchedule::getMapping($this->_values['mapping_id']);
      $this->setPageTitle(ts('%1 Reminder', [1 => $mapping->getLabel()]));
    }
    // Allow pre-selected mapping to check its own permissions
    if (!CRM_Core_Permission::check('administer CiviCRM data')) {
      if (empty($mapping) || !$mapping->checkAccess($this->_values['entity_value'] ?? [])) {
        throw new CRM_Core_Exception(ts('You do not have permission to access this page.'));
      }
    }
  }

  public function buildQuickForm(): void {
    parent::buildQuickForm();

    if ($this->getAction() == CRM_Core_Action::DELETE) {
      $this->assign('reminderName', $this->_values['title']);
      return;
    }

    // Select fields whose option lists either control or are controlled by another field
    $dynamicSelectFields = [
      'mapping_id' => [],
      'entity_value' => [],
      'entity_status' => [],
      'recipient' => ['class' => 'twelve', 'placeholder' => ts('None')],
      'limit_to' => ['class' => 'twelve', 'placeholder' => ts('None')],
      'start_action_date' => ['class' => 'twelve'],
      'end_date' => ['class' => 'twelve'],
      'recipient_listing' => [],
    ];
    // Load dynamic metadata based on current values
    // Get values from submission (if rebuilding due to form errors) or saved reminder (in update mode) or preselected values from the preProcess fn.
    $fieldMeta = Civi\Api4\ActionSchedule::getFields(FALSE)
      ->setValues($this->_submitValues ?: $this->_values)
      ->setAction('create')
      ->setLoadOptions(['id', 'label', 'icon'])
      ->addWhere('name', 'IN', array_keys($dynamicSelectFields))
      ->execute()
      ->indexBy('name');
    $controlFields = [];

    // Add dynamic select fields
    foreach ($fieldMeta as $field) {
      $attr = $dynamicSelectFields[$field['name']] + ['multiple' => !empty($field['input_attrs']['multiple']), 'placeholder' => ts('Select')];
      if (!empty($field['input_attrs']['control_field'])) {
        $controlFields[] = $attr['controlField'] = $field['input_attrs']['control_field'];
      }
      $this->add('select2', $field['name'], $field['label'], $field['options'] ?: [], !empty($field['required']), $attr);
    }

    // Javascript will reload metadata when these fields are changed
    $this->assign('controlFields', array_values(array_unique($controlFields)));

    // These 2 are preselected if form is embedded
    if ($this->get('entity_value')) {
      $this->getElement('entity_value')->freeze();
    }
    if ($this->get('mapping_id') || $this->getAction() == CRM_Core_Action::UPDATE) {
      $this->getElement('mapping_id')->freeze();
    }
    // Pre-assigned mapping_id will cause the field to be hidden by the tpl
    $this->assign('mappingId', $this->get('mapping_id'));

    // Units fields will be pluralized by javascript
    $this->addField('start_action_unit', ['placeholder' => FALSE])->setAttribute('class', 'crm-form-select');
    $this->addField('repetition_frequency_unit', ['placeholder' => FALSE])->setAttribute('class', 'crm-form-select');
    $this->addField('end_frequency_unit', ['placeholder' => FALSE])->setAttribute('class', 'crm-form-select');
    // Data for js pluralization
    $this->assign('recurringFrequencyOptions', [
      'plural' => CRM_Utils_Array::makeNonAssociative(CRM_Core_SelectValues::getRecurringFrequencyUnits(2)),
      'single' => CRM_Utils_Array::makeNonAssociative(CRM_Core_SelectValues::getRecurringFrequencyUnits()),
    ]);

    $this->addField('title', [], TRUE);
    $this->addField('absolute_date', [], FALSE, FALSE);
    $this->addField('start_action_offset', ['class' => 'four']);
    $this->addField('start_action_condition', ['placeholder' => FALSE])->setAttribute('class', 'crm-form-select');
    $this->addField('record_activity', ['type' => 'advcheckbox']);
    $this->addField('is_repeat', ['type' => 'advcheckbox']);
    $this->addField('repetition_frequency_interval', ['label' => ts('Every'), 'class' => 'four']);
    $this->addField('end_frequency_interval', ['label' => ts('Until'), 'class' => 'four']);
    $this->addField('end_action', ['placeholder' => FALSE])->setAttribute('class', 'crm-form-select');
    $this->addField('effective_start_date', ['label' => ts('Effective From')], FALSE, FALSE);
    $this->addField('effective_end_date', ['label' => ts('To')], FALSE, FALSE);
    $this->addField('is_active', ['type' => 'advcheckbox']);
    $this->addAutocomplete('recipient_manual', ts('Manual Recipients'), ['select' => ['multiple' => TRUE]]);
    $this->addAutocomplete('group_id', ts('Group'), ['entity' => 'Group', 'select' => ['minimumInputLength' => 0]]);

    // From email address (optional, defaults to domain email)
    $domainDefault = CRM_Core_BAO_Domain::getNameAndEmail(TRUE);
    $this->addField('from_name', ['placeholder' => $domainDefault[0] ?? '', 'class' => 'big']);
    $this->addField('from_email', ['placeholder' => $domainDefault[1] ?? '', 'class' => 'big']);

    // Relative/absolute date toggle (not a real field, just a widget for the form)
    $this->add('select', 'absolute_or_relative_date', ts('When (trigger date)'), ['relative' => ts('Relative Date'), 'absolute' => ts('Choose Date')], TRUE);

    // SMS-only fields
    $providersCount = CRM_SMS_BAO_SmsProvider::activeProviderCount();
    $this->assign('sms', $providersCount);
    if ($providersCount) {
      $this->addField('mode', ['placeholder' => FALSE, 'option_url' => FALSE], TRUE)->setAttribute('class', 'crm-form-select');
      $this->addField('sms_provider_id');
    }

    // Multilingual-only fields
    $multilingual = CRM_Core_I18n::isMultilingual();
    $this->assign('multilingual', $multilingual);
    if ($multilingual) {
      $filterLanguages = \CRM_Core_BAO_ActionSchedule::getFilterContactLanguageOptions();
      $this->addField('filter_contact_language', ['placeholder' => ts('Any language'), 'options' => $filterLanguages]);
      $communicationLanguages = \CRM_Core_BAO_ActionSchedule::getCommunicationLanguageOptions();
      $this->addField('communication_language', ['placeholder' => 'System default language', 'options' => $communicationLanguages]);
    }

    // Message fields
    $this->addField('subject');
    CRM_Mailing_BAO_Mailing::commonCompose($this);

    $this->addFormRule([__CLASS__, 'formRule'], $this);
  }

  /**
   * Global form rule.
   *
   * @param array $values
   *   The input form values.
   * @param array $files
   * @param CRM_Admin_Form_ScheduleReminders $self
   *
   * @return array|bool
   *   True if no errors, else array of errors
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function formRule(array $values, $files, $self) {
    $errors = [];
    $values = self::normalizeFormValues($values);
    $fieldMeta = Civi\Api4\ActionSchedule::getFields(FALSE)
      ->setValues($values)
      ->setAction('create')
      ->execute()
      ->indexBy('name');

    foreach ($fieldMeta as $fieldName => $fieldInfo) {
      $fieldValue = $values[$fieldName] ?? NULL;
      $formFieldName = array_search($fieldName, self::$messageFieldMap) ?: $fieldName;
      // TODO: This snippet could be an api action e.g. `civicrm_api4('ActionSchedule', 'validate'...)`
      if ($fieldValue === NULL || $fieldValue === '' || $fieldValue === []) {
        if (
          (!empty($fieldInfo['required']) && !isset($fieldInfo['default_value'])) ||
          (!empty($fieldInfo['required_if']) && \Civi\Api4\Generic\AbstractAction::evaluateCondition($fieldInfo['required_if'], ['values' => $values]))
        ) {
          $errors[$formFieldName] = ts('%1 is a required field.', [1 => $fieldInfo['label']]);
        }
      }
      elseif (empty($fieldInfo['input_attrs']['multiple']) && is_array($fieldValue) && count($fieldValue) > 1) {
        $errors[$formFieldName] = ts('Please select only 1 %1.', [1 => $fieldInfo['label']]);
      }
    }

    // Suppress irrelevant error messages depending on chosen date mode
    if ($values['absolute_or_relative_date'] === 'absolute') {
      unset($errors['start_action_offset'], $errors['start_action_unit'], $errors['start_action_condition'], $errors['start_action_date']);
    }
    else {
      unset($errors['absolute_date']);
      if (($values['start_action_date'] == 'next_sched_contribution_date') && (!in_array('2', $values['entity_status']) || count($values['entity_status']) != 1)) {
        $errors['start_action_date'] = ts('Membership Auto-Renewal Date can only work with Auto-renewal Memberships');
      }
    }

    return $errors ?: TRUE;
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    $defaults = $this->_values ?? [];
    if ($this->_action & CRM_Core_Action::ADD) {
      $defaults['is_active'] = 1;
      $defaults['record_activity'] = 1;
    }
    else {
      // Set values for the fields added by CRM_Mailing_BAO_Mailing::commonCompose
      foreach (self::$messageFieldMap as $messageFieldName => $actionScheduleFieldName) {
        $defaults[$messageFieldName] = $defaults[$actionScheduleFieldName] ?? NULL;
      }
      $defaults['absolute_or_relative_date'] = !empty($defaults['absolute_date']) ? 'absolute' : 'relative';

      // This is weird - the form used to nullify `recipient` if it was 'group' or 'manual',
      // but I see no good reason for doing that.
      if (empty($defaults['recipient'])) {
        if (!empty($defaults['group_id'])) {
          $defaults['recipient'] = 'group';
        }
        elseif (!empty($defaults['recipient_manual'])) {
          $defaults['recipient'] = 'manual';
        }
      }
    }
    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Core_BAO_ActionSchedule::deleteRecord(['id' => $this->_id]);
      CRM_Core_Session::setStatus(ts('Selected Reminder has been deleted.'), ts('Record Deleted'), 'success');
      return;
    }
    $values = $this->controller->exportValues($this->getName());
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $values['id'] = $this->_id;
    }

    $values = self::normalizeFormValues($values);
    self::saveMessageTemplates($values);

    $bao = CRM_Core_BAO_ActionSchedule::writeRecord($values);

    // we need to set this on the form so that hooks can identify the created entity
    $this->set('id', $bao->id);

    $status = ts('Reminder "%1" has been saved.', [1 => $values['title']]);

    CRM_Core_Session::setStatus($status, ts('Saved'), 'success');
  }

  private static function normalizeFormValues(array $values): array {
    // Ensure multivalued fields are formatted as an array
    $serialized = \Civi\Api4\ActionSchedule::getFields(FALSE)
      ->addWhere('serialize', 'IS NOT EMPTY')
      ->execute()->column('name');
    foreach ($serialized as $fieldName) {
      if (isset($values[$fieldName]) && is_string($values[$fieldName])) {
        $values[$fieldName] = explode(',', $values[$fieldName]);
      }
    }

    // Absolute or relative date
    if ($values['absolute_or_relative_date'] === 'absolute') {
      $values['start_action_offset'] = $values['start_action_unit'] = $values['start_action_condition'] = $values['start_action_date'] = '';
    }
    else {
      $values['absolute_date'] = '';
    }

    // Convert values for the fields added by CRM_Mailing_BAO_Mailing::commonCompose
    foreach (self::$messageFieldMap as $messageFieldName => $actionScheduleFieldName) {
      $values[$actionScheduleFieldName] = $values[$messageFieldName] ?? NULL;
    }
    return $values;
  }

  /**
   * Add or update message templates (for both email & sms, according to mode)
   *
   * @param array $params
   * @throws CRM_Core_Exception
   */
  private static function saveMessageTemplates(array &$params): void {
    $mode = $params['mode'] ?? 'Email';
    $modePrefixes = ['msg' => '', 'sms' => 'SMS'];

    if ($mode === 'Email' || empty($params['sms_provider_id'])) {
      unset($modePrefixes['sms']);
    }
    elseif ($mode === 'SMS') {
      unset($modePrefixes['msg']);
    }

    $msgTemplate = NULL;

    foreach ($modePrefixes as $mode => $prefix) {
      // Update existing template
      if (!empty($params[$prefix . 'updateTemplate']) && !empty($params[$prefix . 'template'])) {
        $templateParams = ['is_active' => TRUE];
        if ($prefix === 'SMS') {
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
        $templateParams['id'] = $params[$prefix . 'template'];

        $msgTemplate = CRM_Core_BAO_MessageTemplate::add($templateParams);
      }

      // Save new template
      if (!empty($params[$prefix . 'saveTemplate'])) {
        $templateParams = ['is_active' => TRUE];
        if ($prefix === 'SMS') {
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
        $templateParams['msg_title'] = $params[$prefix . 'saveTemplateName'];

        $msgTemplate = CRM_Core_BAO_MessageTemplate::add($templateParams);
      }

      if (isset($msgTemplate->id)) {
        $params[$mode . '_template_id'] = $msgTemplate->id;
      }
    }
  }

  /**
   * List available tokens for this form.
   *
   * @return array
   */
  public function listTokens(): array {
    $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
      'controller' => __CLASS__,
      'smarty' => FALSE,
      'schema' => ['activityId', 'participantId', 'membershipId', 'contactId', 'eventId', 'contributionId'],
    ]);
    return $tokenProcessor->listTokens();
  }

}
