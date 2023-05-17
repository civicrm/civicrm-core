<?php
return CRM_Core_CodeGen_OptionGroup::create('activity_default_assignee', 'a/0082')
  ->addMetadata([
    'title' => ts('Activity default assignee'),
  ])
  ->addValues(['label', 'name', 'value', 'weight'], [
    [ts('None'), 'NONE', 1, 1, 'is_default' => 1],
    [ts('By relationship to case client'), 'BY_RELATIONSHIP', 2, 1],
    [ts('Specific contact'), 'SPECIFIC_CONTACT', 3, 1],
    [ts('User creating the case'), 'USER_CREATING_THE_CASE', 4, 1],
  ]);
