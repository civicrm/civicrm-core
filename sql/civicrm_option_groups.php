<?php

$readOptionGroups = function (): array {
  $files = (array) glob(__DIR__ . '/civicrm_option_groups/*.sqldata.php');
  $result = [];
  foreach ($files as $file) {
    $basename = preg_replace('/\.sqldata\.php$/', '', basename($file));
    $result[$basename] = include $file;
  }
  uasort($result, function(CRM_Core_CodeGen_OptionGroup $a, CRM_Core_CodeGen_OptionGroup $b) {
    if ($a->historicalId === $b->historicalId) {
      return strnatcmp($a->metadata['name'], $b->metadata['name']);
    }
    else {
      return strnatcmp($a->historicalId, $b->historicalId);
    }
  });
  return $result;
};

return $readOptionGroups() + [
  'contact_edit_options' => CRM_Core_CodeGen_OptionGroup::create('contact_edit_options')
    ->addMetadata([
      'title' => ts('Contact Edit Options'),
      'is_locked' => '1',
    ]),
  'advanced_search_options' => CRM_Core_CodeGen_OptionGroup::create('advanced_search_options')
    ->addMetadata([
      'title' => ts('Advanced Search Options'),
      'is_locked' => '1',
    ]),
  'user_dashboard_options' => CRM_Core_CodeGen_OptionGroup::create('user_dashboard_options')
    ->addMetadata([
      'title' => ts('User Dashboard Options'),
      'is_locked' => '1',
    ]),
  'address_options' => CRM_Core_CodeGen_OptionGroup::create('address_options')
    ->addMetadata([
      'title' => ts('Addressing Options'),
    ]),
  'group_type' => CRM_Core_CodeGen_OptionGroup::create('group_type')
    ->addMetadata([
      'title' => ts('Group Type'),
    ]),
  'custom_search' => CRM_Core_CodeGen_OptionGroup::create('custom_search')
    ->addMetadata([
      'title' => ts('Custom Search'),
    ]),
  'activity_status' => CRM_Core_CodeGen_OptionGroup::create('activity_status')
    ->addMetadata([
      'title' => ts('Activity Status'),
      'data_type' => 'Integer',
      'option_value_fields' => 'name,label,description,color',
    ]),
  'case_type' => CRM_Core_CodeGen_OptionGroup::create('case_type')
    ->addMetadata([
      'title' => ts('Case Type'),
    ]),
  'case_status' => CRM_Core_CodeGen_OptionGroup::create('case_status')
    ->addMetadata([
      'title' => ts('Case Status'),
      'option_value_fields' => 'name,label,description,color',
    ]),
  'participant_listing' => CRM_Core_CodeGen_OptionGroup::create('participant_listing')
    ->addMetadata([
      'title' => ts('Participant Listing'),
    ]),
  'safe_file_extension' => CRM_Core_CodeGen_OptionGroup::create('safe_file_extension')
    ->addMetadata([
      'title' => ts('Safe File Extension'),
    ]),
  'from_email_address' => CRM_Core_CodeGen_OptionGroup::create('from_email_address')
    ->addMetadata([
      'title' => ts('From Email Address'),
      'description' => ts('By default, CiviCRM uses the primary email address of the logged in user as the FROM address when sending emails to contacts. However, you can use this page to define one or more general Email Addresses that can be selected as an alternative. EXAMPLE: "Client Services" <clientservices@example.org>.'),
    ]),
  'mapping_type' => CRM_Core_CodeGen_OptionGroup::create('mapping_type')
    ->addMetadata([
      'title' => ts('Mapping Type'),
      'is_locked' => '1',
    ]),
  'wysiwyg_editor' => CRM_Core_CodeGen_OptionGroup::create('wysiwyg_editor')
    ->addMetadata([
      'title' => ts('WYSIWYG Editor'),
    ]),
  'recur_frequency_units' => CRM_Core_CodeGen_OptionGroup::create('recur_frequency_units')
    ->addMetadata([
      'title' => ts('Recurring Frequency Units'),
    ]),
  'phone_type' => CRM_Core_CodeGen_OptionGroup::create('phone_type')
    ->addMetadata([
      'title' => ts('Phone Type'),
    ]),
  'custom_data_type' => CRM_Core_CodeGen_OptionGroup::create('custom_data_type')
    ->addMetadata([
      'title' => ts('Custom Data Type'),
    ]),
  'visibility' => CRM_Core_CodeGen_OptionGroup::create('visibility')
    ->addMetadata([
      'title' => ts('Visibility'),
    ]),
  'mail_protocol' => CRM_Core_CodeGen_OptionGroup::create('mail_protocol')
    ->addMetadata([
      'title' => ts('Mail Protocol'),
    ]),
  'priority' => CRM_Core_CodeGen_OptionGroup::create('priority')
    ->addMetadata([
      'title' => ts('Priority'),
    ]),
  'redaction_rule' => CRM_Core_CodeGen_OptionGroup::create('redaction_rule')
    ->addMetadata([
      'title' => ts('Redaction Rule'),
    ]),
  'report_template' => CRM_Core_CodeGen_OptionGroup::create('report_template')
    ->addMetadata([
      'title' => ts('Report Template'),
    ]),
  'email_greeting' => CRM_Core_CodeGen_OptionGroup::create('email_greeting')
    ->addMetadata([
      'title' => ts('Email Greeting Type'),
    ]),
  'postal_greeting' => CRM_Core_CodeGen_OptionGroup::create('postal_greeting')
    ->addMetadata([
      'title' => ts('Postal Greeting Type'),
    ]),
  'addressee' => CRM_Core_CodeGen_OptionGroup::create('addressee')
    ->addMetadata([
      'title' => ts('Addressee Type'),
    ]),
  'contact_autocomplete_options' => CRM_Core_CodeGen_OptionGroup::create('contact_autocomplete_options')
    ->addMetadata([
      'title' => ts('Autocomplete Contact Search'),
      'is_locked' => '1',
    ]),
  'contact_reference_options' => CRM_Core_CodeGen_OptionGroup::create('contact_reference_options')
    ->addMetadata([
      'title' => ts('Contact Reference Autocomplete Options'),
      'is_locked' => '1',
    ]),
  'website_type' => CRM_Core_CodeGen_OptionGroup::create('website_type')
    ->addMetadata([
      'title' => ts('Website Type'),
    ]),
  'tag_used_for' => CRM_Core_CodeGen_OptionGroup::create('tag_used_for')
    ->addMetadata([
      'title' => ts('Tag Used For'),
      'is_locked' => '1',
    ]),
  'note_used_for' => CRM_Core_CodeGen_OptionGroup::create('note_used_for')
    ->addMetadata([
      'title' => ts('Note Used For'),
      'is_locked' => '1',
    ]),
  'currencies_enabled' => CRM_Core_CodeGen_OptionGroup::create('currencies_enabled')
    ->addMetadata([
      'title' => ts('Currencies Enabled'),
    ]),
  'event_badge' => CRM_Core_CodeGen_OptionGroup::create('event_badge')
    ->addMetadata([
      'title' => ts('Event Name Badge'),
    ]),
  'note_privacy' => CRM_Core_CodeGen_OptionGroup::create('note_privacy')
    ->addMetadata([
      'title' => ts('Privacy levels for notes'),
    ]),
  'campaign_type' => CRM_Core_CodeGen_OptionGroup::create('campaign_type')
    ->addMetadata([
      'title' => ts('Campaign Type'),
    ]),
  'campaign_status' => CRM_Core_CodeGen_OptionGroup::create('campaign_status')
    ->addMetadata([
      'title' => ts('Campaign Status'),
    ]),
  'system_extensions' => CRM_Core_CodeGen_OptionGroup::create('system_extensions')
    ->addMetadata([
      'title' => ts('CiviCRM Extensions'),
    ]),
  'mail_approval_status' => CRM_Core_CodeGen_OptionGroup::create('mail_approval_status')
    ->addMetadata([
      'title' => ts('CiviMail Approval Status'),
    ]),
  'engagement_index' => CRM_Core_CodeGen_OptionGroup::create('engagement_index')
    ->addMetadata([
      'title' => ts('Engagement Index'),
    ]),
  'cg_extend_objects' => CRM_Core_CodeGen_OptionGroup::create('cg_extend_objects')
    ->addMetadata([
      'title' => ts('Objects a custom group extends to'),
    ]),
  'paper_size' => CRM_Core_CodeGen_OptionGroup::create('paper_size')
    ->addMetadata([
      'title' => ts('Paper Size'),
    ]),
  'pdf_format' => CRM_Core_CodeGen_OptionGroup::create('pdf_format')
    ->addMetadata([
      'title' => ts('PDF Page Format'),
    ]),
  'label_format' => CRM_Core_CodeGen_OptionGroup::create('label_format')
    ->addMetadata([
      'title' => ts('Mailing Label Format'),
    ]),
  'activity_contacts' => CRM_Core_CodeGen_OptionGroup::create('activity_contacts')
    ->addMetadata([
      'title' => ts('Activity Contacts'),
      'is_locked' => '1',
    ]),
  'account_relationship' => CRM_Core_CodeGen_OptionGroup::create('account_relationship')
    ->addMetadata([
      'title' => ts('Account Relationship'),
    ]),
  'event_contacts' => CRM_Core_CodeGen_OptionGroup::create('event_contacts')
    ->addMetadata([
      'title' => ts('Event Recipients'),
    ]),
  'conference_slot' => CRM_Core_CodeGen_OptionGroup::create('conference_slot')
    ->addMetadata([
      'title' => ts('Conference Slot'),
    ]),
  'batch_type' => CRM_Core_CodeGen_OptionGroup::create('batch_type')
    ->addMetadata([
      'title' => ts('Batch Type'),
      'is_locked' => '1',
    ]),
  'batch_mode' => CRM_Core_CodeGen_OptionGroup::create('batch_mode')
    ->addMetadata([
      'title' => ts('Batch Mode'),
      'is_locked' => '1',
    ]),
  'batch_status' => CRM_Core_CodeGen_OptionGroup::create('batch_status')
    ->addMetadata([
      'title' => ts('Batch Status'),
      'is_locked' => '1',
    ]),
  'sms_api_type' => CRM_Core_CodeGen_OptionGroup::create('sms_api_type')
    ->addMetadata([
      'title' => ts('Api Type'),
    ]),
  'sms_provider_name' => CRM_Core_CodeGen_OptionGroup::create('sms_provider_name')
    ->addMetadata([
      'title' => ts('Sms Provider Internal Name'),
    ]),
  'auto_renew_options' => CRM_Core_CodeGen_OptionGroup::create('auto_renew_options')
    ->addMetadata([
      'title' => ts('Auto Renew Options'),
      'is_locked' => '1',
    ]),
  'financial_account_type' => CRM_Core_CodeGen_OptionGroup::create('financial_account_type')
    ->addMetadata([
      'title' => ts('Financial Account Type'),
    ]),
  'financial_item_status' => CRM_Core_CodeGen_OptionGroup::create('financial_item_status')
    ->addMetadata([
      'title' => ts('Financial Item Status'),
      'is_locked' => '1',
    ]),
  'label_type' => CRM_Core_CodeGen_OptionGroup::create('label_type')
    ->addMetadata([
      'title' => ts('Label Type'),
    ]),
  'name_badge' => CRM_Core_CodeGen_OptionGroup::create('name_badge')
    ->addMetadata([
      'title' => ts('Name Badge Format'),
    ]),
  'communication_style' => CRM_Core_CodeGen_OptionGroup::create('communication_style')
    ->addMetadata([
      'title' => ts('Communication Style'),
    ]),
  'msg_mode' => CRM_Core_CodeGen_OptionGroup::create('msg_mode')
    ->addMetadata([
      'title' => ts('Message Mode'),
    ]),
  'contact_date_reminder_options' => CRM_Core_CodeGen_OptionGroup::create('contact_date_reminder_options')
    ->addMetadata([
      'title' => ts('Contact Date Reminder Options'),
      'is_locked' => '1',
    ]),
  'wysiwyg_presets' => CRM_Core_CodeGen_OptionGroup::create('wysiwyg_presets')
    ->addMetadata([
      'title' => ts('WYSIWYG Editor Presets'),
    ]),
  'relative_date_filters' => CRM_Core_CodeGen_OptionGroup::create('relative_date_filters')
    ->addMetadata([
      'title' => ts('Relative Date Filters'),
    ]),
  'pledge_status' => CRM_Core_CodeGen_OptionGroup::create('pledge_status')
    ->addMetadata([
      'title' => ts('Pledge Status'),
      'is_locked' => '1',
    ]),
  'contribution_recur_status' => CRM_Core_CodeGen_OptionGroup::create('contribution_recur_status')
    ->addMetadata([
      'title' => ts('Recurring Contribution Status'),
      'is_locked' => '1',
    ]),
  'environment' => CRM_Core_CodeGen_OptionGroup::create('environment')
    ->addMetadata([
      'title' => ts('Environment'),
    ]),
  'activity_default_assignee' => CRM_Core_CodeGen_OptionGroup::create('activity_default_assignee')
    ->addMetadata([
      'title' => ts('Activity default assignee'),
    ]),
  'entity_batch_extends' => CRM_Core_CodeGen_OptionGroup::create('entity_batch_extends')
    ->addMetadata([
      'title' => ts('Entity Batch Extends'),
    ]),
  'file_type' => CRM_Core_CodeGen_OptionGroup::create('file_type')
    ->addMetadata([
      'title' => ts('File Type'),
      'data_type' => 'Integer',
    ]),
];
