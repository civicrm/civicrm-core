<?php
return CRM_Core_CodeGen_OptionGroup::create('pledge_status', 'a/0079')
  ->addMetadata([
    'title' => ts('Pledge Status'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Completed'), 'Completed', 1],
    [ts('Pending'), 'Pending', 2],
    [ts('Cancelled'), 'Cancelled', 3],
    [ts('In Progress'), 'In Progress', 5],
    [ts('Overdue'), 'Overdue', 6],
  ])
  ->addDefaults([
    'is_reserved' => 1,
  ]);
