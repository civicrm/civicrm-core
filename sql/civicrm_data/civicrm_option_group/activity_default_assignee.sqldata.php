<?php
return CRM_Core_CodeGen_OptionGroup::create('activity_default_assignee', 'a/0082')
  ->addMetadata([
    'title' => ts('Activity default assignee'),
  ])
  ->addValues([
    [
      'label' => ts('None'),
      'value' => 1,
      'name' => 'NONE',
      'is_default' => 1,
      'weight' => 1,
    ],
    [
      'label' => ts('By relationship to case client'),
      'value' => 2,
      'name' => 'BY_RELATIONSHIP',
      'weight' => 1,
    ],
    [
      'label' => ts('Specific contact'),
      'value' => 3,
      'name' => 'SPECIFIC_CONTACT',
      'weight' => 1,
    ],
    [
      'label' => ts('User creating the case'),
      'value' => 4,
      'name' => 'USER_CREATING_THE_CASE',
      'weight' => 1,
    ],
  ]);
