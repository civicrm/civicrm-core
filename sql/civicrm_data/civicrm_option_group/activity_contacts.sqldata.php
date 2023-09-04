<?php
return CRM_Core_CodeGen_OptionGroup::create('activity_contacts', 'a/0060')
  ->addMetadata([
    'title' => ts('Activity Contacts'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value', 'weight'], [
    [ts('Activity Assignees'), 'Activity Assignees', 1, 3],
    [ts('Activity Source'), 'Activity Source', 2, 2],
    [ts('Activity Targets'), 'Activity Targets', 3, 1],
  ]);
