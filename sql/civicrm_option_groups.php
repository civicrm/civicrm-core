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
