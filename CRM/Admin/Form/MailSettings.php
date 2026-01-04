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
 * This class handles mail account settings.
 *
 */
class CRM_Admin_Form_MailSettings extends CRM_Admin_Form {

  protected $_testButtonName;

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->_testButtonName = $this->getButtonName('upload', 'test');
    $buttons = $this->getElement('buttons')->getElements();
    $buttons[] = $this->createElement(
      'xbutton',
      $this->_testButtonName,
      CRM_Core_Page::crmIcon('fa-chain') . ' ' . ts('Save & Test'),
      ['type' => 'submit', 'class' => 'crm-button']
    );
    $this->getElement('buttons')->setElements($buttons);

    $this->applyFilter('__ALL__', 'trim');

    //get the attributes.
    $attributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_MailSettings');

    //build setting form
    $this->add('text', 'name', ts('Name'), $attributes['name'], TRUE);

    $this->add('text', 'domain', ts('Email Domain'), $attributes['domain'], TRUE);
    $this->addRule('domain', ts('Email domain must use a valid internet domain format (e.g. \'example.org\').'), 'domain');

    $this->add('text', 'localpart', ts('Localpart'), $attributes['localpart']);

    $this->add('text', 'return_path', ts('Return-Path'), $attributes['return_path']);
    $this->addRule('return_path', ts('Return-Path must use a valid email address format.'), 'email');

    $this->add('select', 'protocol',
      ts('Protocol'),
      ['' => ts('- select -')] + CRM_Core_DAO_MailSettings::buildOptions('protocol'),
      TRUE
    );

    $this->add('text', 'server', ts('Server'), $attributes['server']);

    $this->add('text', 'username', ts('Username'), ['autocomplete' => 'off']);

    $this->add('password', 'password', ts('Password'), ['autocomplete' => 'off']);

    $this->add('text', 'source', ts('Mail Folder'), $attributes['source']);

    $this->add('checkbox', 'is_ssl', ts('Use SSL?'));

    $usedfor = [
      1 => ts('Bounce Processing'),
      0 => ts('Email-to-Activity Processing'),
    ];
    $this->add('select', 'is_default', ts('Used For?'), $usedfor);

    $activityTypes =
      [CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Inbound Email') => 'Inbound Email']
      + [CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Email') => 'Email']
      + CRM_Core_PseudoConstant::ActivityType(FALSE);
    $this->add('select', 'activity_type_id', ts('Activity Type'),
      $activityTypes,
      TRUE,
      ['class' => 'crm-select2 required']
    );
    $this->addField('activity_status', ['placeholder' => FALSE]);
    CRM_Campaign_BAO_Campaign::addCampaign($this);

    $this->add('checkbox', 'is_non_case_email_skipped', ts('Skip emails which do not have a Case ID or Case hash'));
    $this->add('checkbox', 'is_contact_creation_disabled_if_no_match', ts('Do not create new contacts when filing emails'));

    $emailRecipients = ['from' => 'From', 'to' => 'To', 'cc' => 'CC', 'bcc' => 'BCC'];
    $this->add('select', 'activity_source', ts('Activity Source'), $emailRecipients, TRUE, ['class' => 'crm-select2 required']);
    $this->add('select', 'activity_targets', ts('Activity Targets'), $emailRecipients, FALSE, ['class' => 'crm-select2', 'multiple' => TRUE]);
    $this->add('select', 'activity_assignees', ts('Activity Assignees'), $emailRecipients, FALSE, ['class' => 'crm-select2', 'multiple' => TRUE]);

    $this->add('checkbox', 'is_active', ts('Enabled'));
  }

  /**
   * Add local and global form rules.
   */
  public function addRules() {
    $this->addFormRule(['CRM_Admin_Form_MailSettings', 'formRule'], $this);
  }

  public function getDefaultEntity() {
    return 'MailSettings';
  }

  /**
   * Add local and global form rules.
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    $defaults['is_ssl'] ??= TRUE;
    $defaults['is_default'] ??= 0;
    $defaults['activity_type_id'] = $defaults['activity_type_id'] ??
      CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Inbound Email');
    $defaults['activity_status'] ??= 'Completed';
    $defaults['activity_source'] ??= 'from';
    $defaults['activity_targets'] ??= 'to,cc,bcc';
    $defaults['activity_assignees'] ??= 'from';
    $defaults['is_active'] ??= TRUE;

    return $defaults;
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   * @param array $files
   *   Not used here.
   * @param CRM_Core_Form $form
   *   This form.
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields, $files, $form) {
    $errors = [];
    if ($form->_action != CRM_Core_Action::DELETE) {
      // Check for default from email address and organization (domain) name. Force them to change it.
      if ($fields['domain'] == 'EXAMPLE.ORG') {
        $errors['domain'] = ts('Please enter a valid domain for this mailbox account (the part after @).');
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Core_BAO_MailSettings::deleteMailSettings($this->_id);
      CRM_Core_Session::setStatus("", ts('Mail Setting Deleted.'), "success");
      return;
    }

    //get the submitted form values.
    $formValues = $this->controller->exportValues($this->_name);

    //form fields.
    $fields = [
      'name',
      'domain',
      'localpart',
      'server',
      'return_path',
      'protocol',
      'port',
      'username',
      'password',
      'source',
      'is_ssl',
      'is_default',
      'activity_status',
      'is_non_case_email_skipped',
      'is_contact_creation_disabled_if_no_match',
      'activity_type_id',
      'campaign_id',
      'activity_source',
      'activity_targets',
      'activity_assignees',
      'is_active',
    ];

    $params = [];
    foreach ($fields as $f) {
      if (in_array($f, [
        'is_default',
        'is_ssl',
        'is_non_case_email_skipped',
        'is_contact_creation_disabled_if_no_match',
        'is_active',
      ])) {
        $params[$f] = $formValues[$f] ?? FALSE;
      }
      else {
        $params[$f] = $formValues[$f] ?? NULL;
      }
    }

    $params['domain_id'] = CRM_Core_Config::domainID();

    // assign id only in update mode
    $status = ts('Your New Email Settings have been saved.');
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
      $status = ts('Your Email Settings have been updated.');
    }

    $mailSettings = CRM_Core_BAO_MailSettings::create($params);

    if ($mailSettings->id) {
      CRM_Core_Session::setStatus($status, ts("Saved"), "success");
    }
    else {
      CRM_Core_Session::setStatus("", ts('Changes Not Saved.'), "info");
    }

    if ($this->controller->getButtonName() == $this->_testButtonName) {
      $test = civicrm_api4('MailSettings', 'testConnection', [
        'where' => [['id', '=', $mailSettings->id]],
      ])->single();
      CRM_Core_Session::setStatus($test['details'], $test['title'],
        $test['error'] ? 'error' : 'success');
    }
  }

}
