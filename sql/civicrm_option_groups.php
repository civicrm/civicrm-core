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
