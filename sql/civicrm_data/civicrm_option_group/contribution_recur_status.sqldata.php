<?php
return CRM_Core_CodeGen_OptionGroup::create('contribution_recur_status', 'a/0080')
  ->addMetadata([
    'title' => ts('Recurring Contribution Status'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Completed'), 'Completed', 1],
    [ts('Pending'), 'Pending', 2],
    [ts('Cancelled'), 'Cancelled', 3],
    [ts('Failed'), 'Failed', 4],
    [ts('In Progress'), 'In Progress', 5],
    [ts('Overdue'), 'Overdue', 6],
    [ts('Processing'), 'Processing', 7],
    [ts('Failing'), 'Failing', 8],
  ])
  ->addDefaults([
    'is_reserved' => 1,
  ]);
